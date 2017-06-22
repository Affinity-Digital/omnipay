<?php

namespace Drupal\omnipay\Plugin\Payment\Method;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Utility\Token;
use Drupal\Core\Database\Connection;
use Drupal\payment\EventDispatcherInterface;
use Drupal\payment\OperationResult;
use Drupal\payment\Payment;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodBase as GenericPaymentMethodBase;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface;
use Guzzle\Http\Client;
use Guzzle\Http\ClientInterface;
use Omnipay\Common\Item;
use Omnipay\Common\ItemBag;
use Omnipay\Common\GatewayFactory;
use Omnipay\Common\GatewayInterface;
use Omnipay\Common\Message\RedirectResponseInterface;
use Omnipay\Common\Message\ResponseInterface;
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
   * @param \Guzzle\Http\ClientInterface $http_client
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

    $this->setGateway(GatewayFactory::create(
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

    // Onmipay 2.x Client class is \Guzzle\Http\ClientInterface.
    if ($containerClient instanceof ClientInterface) {
      $client = $containerClient;
    }
    else {
      $config = $containerClient->getConfig();
      // Create a new instance and use the passed instance's configuration.
      $client = new Client('', $config);
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
    return $this->gateway->getParameters();
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->gateway->initialize($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentExecutionResult() {
    $this->gateway->setTestMode(!$this->isProduction());

    $item_classname = $this->getItemClass();
    $item_bag_classname = $this->getItemBagClass();
    $items = new $item_bag_classname();
    $totalAmount = 0;
    $currency = NULL;
    foreach ($this->getPayment()->getLineItems() as $line_item) {
      $item = new $item_classname();
      $item->setPrice($line_item->getAmount());
      $item->setQuantity($line_item->getQuantity());
      $item->setDescription($line_item->getDescription());
      $item->setName($line_item->getName());
      $items->add($item);

      $totalAmount += $line_item->getTotalAmount();
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

    $configuration = $this->getConfiguration();
    $configuration['amount'] = $totalAmount;
    $configuration['currency'] = $currency;
    $configuration['transactionId'] = $this->getTransactionId();
    $configuration['items'] = $items->all();

    /** @var \Omnipay\Common\Message\RequestInterface $request */
    $request = $this->gateway->purchase($configuration);
    /** @var \Omnipay\Common\Message\ResponseInterface $response */
    $response = $request->send();

    if (!($response instanceof ResponseInterface)) {
      $response = $this->process($response);
    }

    // Save some information.
    $now = \Drupal::time()->getRequestTime();
    $fields = [
      'pid' => $this->getPayment()->id(),
      'tid' => $configuration['transactionId'],
      'tref' => $this->getPayment()->getPaymentMethod()->getTransactionReference($response),
      'created' => $now,
      'changed' => $now,
    ];
    $this
      ->getConnection()
      ->insert('omnipay')
      ->fields($fields)
      ->execute();

    $this->setConfiguration($this->gateway->getParameters());
    $this->getPayment()->save();

    if ($response->isRedirect() && $response instanceof RedirectResponseInterface) {
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

      $response = $this
        ->getPayment()
        ->getPaymentType()
        ->getResumeContextResponse();
    }
    return new OperationResult($response);
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
   * Generic extract the transaction reference.
   *
   * @param \Omnipay\Common\Message\ResponseInterface $response
   *   The returned response.
   *
   * @return string
   *   The tranasction reference.
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
   *
   * @return \Drupal\omnipay\Plugin\Payment\MethodConfiguration\OmniPayBasic
   *   Fluent interface.
   */
  public function setProduction($production) {
    $this->configuration['production'] = $production;
    return $this;
  }

}
