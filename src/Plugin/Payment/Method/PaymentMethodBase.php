<?php

namespace Drupal\omnipay\Plugin\Payment\Method;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Utility\Token;
use Drupal\payment\EventDispatcherInterface;
use Drupal\payment\OperationResult;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodBase as GenericPaymentMethodBase;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface;
use Guzzle\Http\Client;
use Guzzle\Http\ClientInterface;
use Omnipay\Common\GatewayFactory;
use Omnipay\Common\Message\RedirectResponseInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Omnipay\Common\ItemBag;
use Omnipay\Common\Item;

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
    Request $request
  ) {

    $this->gateway = GatewayFactory::create(
      $this->getGatewayName(),
      $http_client,
      $request
    );

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
      $container->get('request_stack')->getCurrentRequest()
    );
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
  public function doExecutePayment() {
    $items = new ItemBag();
    $totalAmount = 0;
    $currency = NULL;
    foreach ($this->getPayment()->getLineItems() as $line_item) {
      $item = new Item();
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
    // $configuration['card'] = $card; .
    $configuration['transactionId'] = $this->getTransactionId();
    $configuration['items'] = $items->all();

    $request = $this->gateway->purchase($configuration);
    $response = $request->send();
    $this->setConfiguration($this->gateway->getParameters());
    $this->getPayment()->save();
    if ($response->isRedirect() && $response instanceof RedirectResponseInterface) {
      $response->redirect();
    }
    else {
      $this->getPayment()->getPaymentType()->resumeContext();
    }
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

}
