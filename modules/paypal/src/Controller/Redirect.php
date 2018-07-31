<?php

namespace Drupal\omnipay_paypal\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\Payment;
use Http\Adapter\Guzzle6\Client;
use Omnipay\Common\GatewayFactory;
use Omnipay\Common\Http\Client as OmnipayClient;
use Omnipay\Common\Http\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles the "redirect" route.
 */
class Redirect extends ControllerBase {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Client object.
   *
   * @var \Omnipay\Common\Http\ClientInterface
   */
  protected $client;

  /**
   * Construct the class using passed parameters.
   *
   * @param \Omnipay\Common\Http\ClientInterface $http_client
   *   The HTTP client.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\Core\Database\Connection $connection
   *   The current Database connection.
   */
  public function __construct(
      ClientInterface $http_client,
      Request $request,
      Connection $connection
  ) {
    $this->setConnection($connection);
    $this->setRequest($request);
    $this->setClient($http_client);
  }

  /**
   * Create an instance of this class.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Dependancy Container.
   *
   * @return \Drupal\omnipay_paypal\Controller\Redirect
   *   Instance of this object to use.
   */
  public static function create(ContainerInterface $container) {
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
   * Return the current client to use.
   *
   * @return \Omnipay\Common\Http\ClientInterface
   *   Requested client connection to use.
   */
  public function getClient() {
    return $this->client;
  }

  /**
   * Set the database connection object.
   *
   * @param \Http\Client\HttpClient $client
   *   Client to use.
   */
  public function setClient(ClientInterface $client) {
    $this->client = $client;
  }

  /**
   * Return the current request object to use.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   Request object to use.
   */
  public function getRequest() {
    return $this->request;
  }

  /**
   * Set the request object.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object to use.
   */
  public function setRequest(Request $request) {
    $this->request = $request;
  }

  /**
   * Determine if access is allowed.
   *
   * @param \Drupal\payment\Entity\PaymentInterface $payment
   *   Payment object.
   *
   * @return bool
   *   The access status.
   */
  public function access(PaymentInterface $payment) {
    return AccessResult::allowedIf($this->verify($payment));
  }

  /**
   * {@inheritdoc}
   */
  private function verify(PaymentInterface $payment) {
    /** @var \Drupal\Core\Database\Query\SelectInterface $select */
    $select = $this
      ->getConnection()
      ->select('omnipay', 'o');

    $info = $select
      ->condition('tref', $this->getRequest()->get('paymentId'))
      ->fields('o', ['pid'])
      ->execute()
      ->fetchAssoc();

    return (
      ($payment->getOwnerId() == \Drupal::currentUser()->id())
      && ($info['pid'] == $payment->id())
    );
  }

  /**
   * PayPal is redirecting the visitor here after the payment process.
   *
   * At this point we don't know the status of the payment yet so we can only
   * load the payment and give control back to the payment context.
   *
   * @param \Drupal\payment\Entity\PaymentInterface $payment
   *   The payment we are dealing with.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response to the action.
   */
  public function execute(PaymentInterface $payment) {
    $request = $this->getRequest();

    $gatewayFactory = new GatewayFactory();
    /** @var \Omnipay\PayPal\RestGateway $gateway */
    $gateway = $gatewayFactory->create(
      'PayPal_Rest',
      $this->getClient(),
      $request
    );

    /** @var \Drupal\omnipay_paypal\Plugin\Payment\Method\PayPalBasic $payment_method */
    $payment_method = $payment->getPaymentMethod();

    $payment_method->setGateway($gateway);
    $configuration = $payment_method->getConfiguration();

    // Once the transaction has been approved, we need to complete it.
    /** @var \Omnipay\PayPal\Message\AbstractRestRequest $transaction */
    $transaction = $gateway->completePurchase([
      'payer_id' => $request->get('PayerID'),
      'transactionReference' => $request->get('paymentId'),
    ]);
    $response = $transaction->send();
    if ($response->isSuccessful()) {
      // The customer has successfully paid.
      $payment_method->doCapturePayment();
    }
    else {
      // There was an error returned by completePurchase().  You should
      // check the error code and message from PayPal, which may be something
      // like "card declined", etc.
      $payment
        ->setPaymentStatus(
          Payment::statusManager()->createInstance('payment_failed')
        )
        ->save();
    }

    return $this->getResponse($payment);
  }

  /**
   * Process the cancel request.
   *
   * @param \Drupal\payment\Entity\PaymentInterface $payment
   *   The payment we are currently using.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   */
  public function cancel(PaymentInterface $payment) {
    $payment
      ->setPaymentStatus(
        Payment::statusManager()->createInstance('payment_cancelled')
      )
      ->save();

    return $this->getResponse($payment);
  }

  /**
   * Get the response object for this payment object.
   *
   * @param \Drupal\payment\Entity\PaymentInterface $payment
   *   The payment we are currently using.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   */
  private function getResponse(PaymentInterface $payment) {
    $response = $payment->getPaymentType()->getResumeContextResponse();
    return $response->getResponse();
  }

}
