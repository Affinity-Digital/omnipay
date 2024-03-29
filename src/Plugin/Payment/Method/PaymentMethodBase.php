<?php

namespace Drupal\omnipay\Plugin\Payment\Method;

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use Drupal\payment\EventDispatcherInterface;
use Drupal\payment\OperationResult;
use Drupal\payment\OperationResultInterface;
use Drupal\payment\Payment;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodBase as GenericPaymentMethodBase;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface;
use Http\Adapter\Guzzle6\Client;
use Omnipay\Common\CreditCard;
use Omnipay\Common\Http\Client as OmnipayClient;
use Omnipay\Common\Http\ClientInterface;
use Omnipay\Common\Item;
use Omnipay\Common\ItemBag;
use Omnipay\Common\GatewayInterface;
use Omnipay\Common\Message\RedirectResponseInterface;
use Omnipay\Common\Message\RequestInterface;
use Omnipay\Common\Message\ResponseInterface;
use Omnipay\Omnipay;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a basis for payment methods that use Omnipay gateways.
 */
abstract class PaymentMethodBase extends GenericPaymentMethodBase {

  /**
   * The wrapped Omnipay gateway.
   *
   * @var \Omnipay\Common\GatewayInterface
   */
  protected $gateway;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Execution Result.
   *
   * @var mixed
   */
  protected $paymentExecutionResult = NULL;

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\payment\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Utility\Token $token
   *   The token API.
   * @param \Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface $payment_status_manager
   *   The payment status manager.
   * @param \Omnipay\Common\Http\ClientInterface $http_client
   *   The HTTP client.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\Core\Database\Connection $connection
   *   The current Database connection.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    ModuleHandlerInterface $module_handler,
    EventDispatcherInterface $event_dispatcher,
    Token $token,
    PaymentStatusManagerInterface $payment_status_manager,
    ClientInterface $http_client,
    Request $request,
    Connection $connection
  ) {

    $this->setConnection($connection);

    $this->setGateway(Omnipay::create(
      $this->getGatewayName(),
      $http_client,
      $request
    ));

    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $module_handler,
      $event_dispatcher,
      $token,
      $payment_status_manager
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $containerClient = $container->get('http_client');

    // Onmipay 3.x Client class is \Omnipay\Common\Http\ClientInterface.
    if ($containerClient instanceof ClientInterface) {
      $client = $containerClient;
    }
    else {
      $config = $containerClient->getConfig();
      $client = new OmnipayClient(Client::createWithConfig($config));
    }

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('payment.event_dispatcher'),
      $container->get('token'),
      $container->get('plugin.manager.payment.status'),
      $client,
      $container->get('request_stack')->getCurrentRequest(),
      \Drupal::database()
    );
  }

  /**
   * Return the current database connection to use.
   *
   * @return \Drupal\Core\Database\Connection
   *   Requested database connection to use.
   */
  public function getConnection() {
    return $this->connection;
  }

  /**
   * Set the database connection object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection to use.
   */
  public function setConnection(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * Get the gateway interface.
   *
   * @return \Omnipay\Common\GatewayInterface
   *   Omnipay gateway to use.
   */
  public function getGateway() {
    return $this->gateway;
  }

  /**
   * Set the gateway object.
   *
   * @param \Omnipay\Common\GatewayInterface $gateway
   *   Database connection to use.
   */
  public function setGateway(GatewayInterface $gateway) {
    $this->gateway = $gateway;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return $this->gateway->getDefaultParameters();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $configuration = \array_merge(parent::getConfiguration(), $this->gateway->getParameters());

    $definition = $this->getPluginDefinition();
    $configuration['testMode'] = !$definition['production'];

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    parent::setConfiguration($configuration);
    $this->gateway->initialize($configuration);
  }

  /**
   * {@inheritdoc}
   */
  protected function doExecutePayment() {
    $items = $this->getItemBag();

    if ($items instanceof OperationResultInterface) {
      return $items;
    }

    $this->gateway->setTestMode(!$this->isProduction());

    $description = $this->preprocessDescription($this->payment->label());

    $configuration = $this->getConfiguration();
    $configuration['amount'] = $this->payment->getAmount();
    $configuration['description'] = $description;
    $configuration['currency'] = $this->getCurrency();
    $configuration['transactionId'] = $this->getTransactionId();
    $configuration['items'] = $items->all();
    if ($this->needCard()) {
      $configuration['card'] = $this->getCard();
    }

    // Throw an Exception before we call the function.
    if (!$this->gateway->supportsPurchase()) {
      throw new \Exception('Gateway does not support purchase()');
    }

    /** @var \Omnipay\Common\Message\RequestInterface $request */
    $request = $this->gateway->purchase($configuration);

    // Allow the Payment Method to change the request.
    $this->preProcessRequest($request);

    // Not all gateways support send() so use sendData().
    $data = $request->getData();
    /** @var \Omnipay\Common\Message\ResponseInterface $response */
    $response = $request->sendData($data);

    if (!($response instanceof ResponseInterface)) {
      $response = $this->process($response);
    }

    if ($tref = $this->getTransactionReference($response)) {
      // Save some information.
      $now = \Drupal::time()->getRequestTime();
      $fields = [
        'pid' => $this->getPayment()->id(),
        'tid' => $configuration['transactionId'],
        'tref' => $tref,
        'created' => $now,
        'changed' => $now,
      ];
      $this
        ->getConnection()
        ->insert('omnipay')
        ->fields($fields)
        ->execute();
    }

    $this->setConfiguration($this->gateway->getParameters());

    $this->updateConfiguration($response);

    if ($response->isRedirect() && $response instanceof RedirectResponseInterface) {
      $this->setPaymentExecutionResult($response, $request);
      $response->redirect();
    }
    else {
      $payment_status = ($response->isSuccessful())
        ? 'payment_success'
        : 'payment_failed';
      $this
        ->getPayment()
        ->setPaymentStatus(
          Payment::statusManager()->createInstance($payment_status)
        )
        ->save();

      if ($payment_status == 'payment_failed') {
        $data = $response->getData();
        if (\is_array($data)) {
          $message = [];
          foreach ($data as $key => $value) {
            $message[] = $key . ' => ' . $value;
          }
          \Drupal::logger('omnipay')->error(\implode("\n", $message));
        }
      }

      $response = $this
        ->getPayment()
        ->getPaymentType()
        ->getResumeContextResponse();
    }
    $this->setPaymentExecutionResult($response, $request);
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentExecutionResult() {
    return new OperationResult($this->paymentExecutionResult);
  }

  /**
   * Store the response from request.
   *
   * @param mixed $response
   * @param mixed $request
   */
  public function setPaymentExecutionResult($response, $request) {
    $this->paymentExecutionResult = $response;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedCurrencies() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getTransactionId() {
    return \Drupal::service('uuid')->generate();
  }

  /**
   * Redirection URL.
   *
   * @var \Drupal\Core\Url|null
   */
  private $redirectUrl;

  /**
   * Set the redirection URL value.
   *
   * @param \Drupal\Core\Url|null $url
   *   New redirection URL.
   */
  public function setRedirectUrl($url = NULL) {
    if (($url === NULL) || ($url instanceof Url)) {
      $this->redirectUrl = $url;
    }
  }

  /**
   * Get the Redirection URL.
   *
   * @return \Drupal\Core\Url|null
   *   Url value.
   */
  public function getRedirectUrl() {
    return $this->redirectUrl;
  }

  /**
   * Currency.
   *
   * @var string|null
   */
  protected $currency;

  /**
   * Set the Currency value.
   *
   * @param string|null $currency
   *   New currency value.
   */
  public function setCurrency($currency = NULL) {
    if (($currency === NULL) || is_string($currency)) {
      $this->currency = $currency;
    }
  }

  /**
   * Get the Currency.
   *
   * @return string|null
   *   Currency string value.
   */
  public function getCurrency() {
    return $this->currency;
  }

  /**
   * Generic extract the transaction reference.
   *
   * @param \Omnipay\Common\Message\ResponseInterface $response
   *   The returned response.
   *
   * @return string
   *   The transaction reference.
   */
  public function getTransactionReference(ResponseInterface $response) {
    $transaction_reference = $response->getTransactionReference();
    if (empty($transaction_reference)) {
      $transaction_reference = 'NULL';
    }
    return $transaction_reference;
  }

  /**
   * Return the class to use for ItemBag.
   *
   * @return string
   *   The class name to use.
   */
  public function getItemBagClass() {
    return ItemBag::class;
  }

  /**
   * Return the class to use for Item.
   *
   * @return string
   *   The class name to use.
   */
  public function getItemClass() {
    return Item::class;
  }

  /**
   * Gets the setting for the production server.
   *
   * @return bool
   *   Whether it is the production server or not.
   */
  public function isProduction() {
    return !empty($this->configuration['production']);
  }

  /**
   * Gets the production of this configuration.
   *
   * @return string
   *   Configured production.
   */
  public function getProduction() {
    return isset($this->configuration['production']) ? $this->configuration['production'] : '';
  }

  /**
   * Sets the production of this configuration.
   *
   * @param string $production
   *   New Production value.
   */
  public function setProduction($production) {
    $this->configuration['production'] = $production;
  }

  /**
   * Get the items into the correct Collection.
   *
   * @return mixed
   *   Items collection.
   */
  public function getItemBag() {
    $item_classname = $this->getItemClass();
    $item_bag_classname = $this->getItemBagClass();
    $items = new $item_bag_classname();
    $currency = NULL;
    foreach ($this->getPayment()->getLineItems() as $line_item) {
      $item = new $item_classname();
      $item->setPrice($line_item->getAmount());
      $item->setQuantity($line_item->getQuantity());
      $item->setDescription($line_item->getDescription());
      $item->setName($line_item->getName());
      $items->add($item);

      $line_item_currency = $line_item->getCurrencyCode();

      if ($line_item_currency != $currency) {
        if ($currency != NULL) {
          // This is the second time we are changing the currency which means
          // that our line items have mixed currencies. This ain't gonna work!
          drupal_set_message($this->t('Mixed currencies detected which is not yet supported.'), 'error');
          return new OperationResult(NULL);
        }
        $currency = $line_item_currency;
      }
    }
    $this->setCurrency($currency);
    return $items;
  }

  /**
   * Payment methods set this to TRUE if they need card details.
   *
   * @return bool
   *   TRUE if card details are needed by payment method.
   */
  public function needCard() {
    return FALSE;
  }

  /**
   * Card details.
   *
   * @var \Omnipay\Common\CreditCard|null
   */
  protected $card;

  /**
   * Set the card details.
   *
   * See payment methods for which parameters are mandatory.
   *
   * @param array|null $parameters
   *   NULL or associative array of values.
   *
   * @see \Omnipay\Common\CreditCard
   */
  public function setCard($parameters = NULL) {

    if ($parameters === NULL) {
      $this->card = $parameters;
      return;
    }

    if (is_array($parameters)) {
      $this->card = (empty($parameters) ? NULL : new CreditCard($parameters));
    }
  }

  /**
   * Get the card details.
   *
   * @return \Omnipay\Common\CreditCard|null
   *   Card details.
   */
  public function getCard() {
    return $this->card;
  }

  /**
   * Update the configuration based upon the response.
   *
   * @param \Omnipay\Common\Message\ResponseInterface|\Omnipay\Common\Message\RedirectResponseInterface $response
   *   The response from the online payment gateway.
   */
  public function updateConfiguration($response) {
  }

  /**
   * Give the opportunity for the payment to process the payment description.
   *
   * @param string $description
   *   Current payment description.
   * @param null|int $limit
   *   Optionally limit the description to this number of characters.
   *
   * @return bool|string
   *   Optionally limited description string.
   */
  public function preprocessDescription($description, $limit = NULL) {
    if ($limit) {
      if (strlen($description) > $limit) {
        $description = substr($description, 0, $limit - 3);
        $last_space = strripos(' ', $description);
        if ($last_space !== FALSE) {
          $description = substr($description, 0, $last_space);
        }
        $description .= '...';
      }
    }

    return $description;
  }

  /**
   * Allow the Payment Method access to the request object.
   *
   * @param \Omnipay\Common\Message\RequestInterface $request
   *   The request object created for this payment.
   */
  public function preProcessRequest(RequestInterface &$request) {}

  /**
   * Allow the Payment Method access to the response object.
   *
   * @param mixed $response
   *   The response object created for this payment.
   *
   * @return mixed
   *   Update response object.
   */
  public function process($response) {
    return $response;
  }

}
