<?php

namespace Drupal\omnipay_sagepay\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\Payment;
use Http\Adapter\Guzzle6\Client;
use Omnipay\Common\GatewayFactory;
use Omnipay\Common\Http\Client as OmnipayClient;
use Omnipay\Common\Http\ClientInterface;
use Omnipay\SagePay\ConstantsInterface as SagePayConstants;
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
   * @param \Drupal\Core\Database\Connection $connection
   *   The current Database connection.
   */
  public function __construct(
      ClientInterface $http_client,
      Connection $connection
  ) {
    $this->setConnection($connection);
    $this->setClient($http_client);
  }

  /**
   * Create an instance of this class.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Dependancy Container.
   *
   * @return \Drupal\omnipay_sagepay\Controller\Redirect
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
   * @param \Omnipay\Common\Http\ClientInterface $client
   *   Client to use.
   */
  public function setClient(ClientInterface $client) {
    $this->client = $client;
  }

  /**
   * Sage Pay is redirecting the visitor here after the payment process.
   *
   * At this point we don't know the status of the payment yet so we can only
   * load the payment and give control back to the payment context.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request structure.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response to the action.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Omnipay\Common\Exception\InvalidResponseException
   */
  public function execute(Request $request) {
    $gatewayFactory = new GatewayFactory();
    /** @var \Omnipay\SagePay\FormGateway $gateway */
    $gateway = $gatewayFactory->create(
      'SagePay_Form',
      $this->getClient(),
      $request
    );

    // Once the transaction has been approved, we need to complete it.
    /** @var \Omnipay\SagePay\Message\Form\CompletePurchaseRequest $sagepay_request */
    $sagepay_request = $gateway->completePurchase();
    $response = $sagepay_request->getData();

    /** @var \Drupal\Core\Database\Query\SelectInterface $select */
    $select = $this
      ->getConnection()
      ->select('omnipay', 'o');

    $info = $select
      ->condition('tref', $response->getTransactionId())
      ->fields('o', ['pid'])
      ->execute()
      ->fetchAssoc();

    /** @var \Drupal\payment\Entity\PaymentInterface $payment */
    $payment = PaymentInterface::load($info['pid']);

    if ($payment->getOwnerId() != $this->currentUser()->id()) {
      throw new \InvalidArgumentException('Invalid Transaction Id');
    }

    /** @var \Drupal\omnipay_sagepay\Plugin\Payment\Method\SagePayForm $payment_method */
    $payment_method = $payment->getPaymentMethod();
    $payment_method->setGateway($gateway);

    switch ($response['Status']) {
      // If the transaction was authorised.
      case SagePayConstants::SAGEPAY_STATUS_OK:
        $payment
          ->setPaymentStatus(
            Payment::statusManager()->createInstance('payment_success')
          )
          ->save();
        break;

      case SagePayConstants::SAGEPAY_STATUS_ABORT:
        $payment
          ->setPaymentStatus(
            Payment::statusManager()->createInstance('payment_cancelled')
          )
          ->save();
        break;

      // If the authorisation was failed by the bank.
      case SagePayConstants::SAGEPAY_STATUS_NOTAUTHED:

        // If your fraud screening rules were not met.
      case SagePayConstants::SAGEPAY_STATUS_REJECTED:
        // If an error has occurred at Sage Pay.
        // These are very infrequent, but your site should handle them
        // anyway. They normally indicate a problem with bank connectivity.
      case SagePayConstants::SAGEPAY_STATUS_ERROR:
      default:
        $this
          ->getLogger('omnipay_sagepay_payment')
          ->error(
            'Sagepay-error: @status -> @detail',
            ['@status' => $response['Status'], '@detail' => $response['StatusDetail']]
          );
        $payment
          ->setPaymentStatus(
            Payment::statusManager()->createInstance('payment_failed')
          )
          ->save();
        break;
    }

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
