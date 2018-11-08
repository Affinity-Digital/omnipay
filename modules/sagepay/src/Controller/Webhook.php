<?php

namespace Drupal\omnipay_sagepay\Controller;

use Drupal\Core\Access\AccessResult;
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
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles the "webhook" route.
 */
class Webhook extends ControllerBase {

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
   * @return \Drupal\omnipay_sagepay\Controller\Webhook
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
   * Determine if access is allowed.
   *
   * @param \Drupal\payment\Entity\PaymentInterface $payment
   *   Payment .
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
    return $payment->getOwnerId() == $this->currentUser()->id();
  }

  /**
   * SagePay is redirecting the visitor here after the payment process.
   *
   * At this point we don't know the status of the payment yet so we can only
   * load the payment and give control back to the payment context.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request from Sage Pay.
   * @param \Drupal\payment\Entity\PaymentInterface $payment
   *   The payment that is being worked on.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Replies with a response object.
   */
  public function finished(Request $request, PaymentInterface $payment) {
    return $payment->getPaymentType()->getResumeContextResponse()->getResponse();
  }

  /**
   * Sage Pay is notify us of the payment progress.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request structure.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Replies to Sage Pay with expected information.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function notify(Request $request) {
    $client = $this->getClient();
    $gatewayFactory = new GatewayFactory();

    /** @var \Omnipay\SagePay\ServerGateway $gateway */
    $gateway = $gatewayFactory->create(
      'SagePay_Server',
      $client,
      $request
    );

    /** @var \Omnipay\SagePay\Message\ServerNotifyRequest $sagepay */
    $sagepay = $gateway->acceptNotification();

    // Assume for the moment that TransactionId is good so that we can get
    // VendorName.
    /** @var \Drupal\Core\Database\Query\SelectInterface $select */
    $select = $this
      ->getConnection()
      ->select('omnipay', 'o');

    $info = $select
      ->condition('tid', $sagepay->getTransactionId())
      ->fields('o', ['pid'])
      ->execute()
      ->fetchAssoc();

    $status = 'ERROR';
    $payment_id = 0;
    $payment = NULL;

    if (!empty($info['pid'])) {
      $payment_id = $info['pid'];
      /** @var \Drupal\payment\Entity\PaymentInterface $payment */
      $payment = $this
        ->entityTypeManager()
        ->getStorage('payment')
        ->load($payment_id);

      if ($payment) {
        $gateway->setVendor($payment->getPaymentMethod()->getVendorName());
        $gateway->setReferrerId($payment->getPaymentMethod()->getReferrerId());

        $parameters = $payment->getPaymentMethod()->getConfiguration();

        /** @var \Omnipay\SagePay\Message\ServerNotifyRequest $sagepay */
        $sagepay = $gateway->acceptNotification($parameters);

        $status = SagePayConstants::SAGEPAY_STATUS_INVALID;
        if ($sagepay->isValid()) {
          $status = SagePayConstants::SAGEPAY_STATUS_OK;
          switch ($sagepay->getStatus()) {
            // If the transaction was authorised.
            case SagePayConstants::SAGEPAY_STATUS_OK:
              $payment
                ->setPaymentStatus(
                  Payment::statusManager()->createInstance('payment_success')
                )
                ->save();
              break;

            // (for European Payment Types only), if the transaction
            // ... has yet to be accepted or rejected.
            case SagePayConstants::SAGEPAY_STATUS_PENDING:
              $payment
                ->setPaymentStatus(
                  Payment::statusManager()->createInstance('payment_pending')
                )
                ->save();
              break;

            // If the user decided to cancel the transaction whilst
            // ... on our payment pages.
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
                  ['@status' => $status, '@detail' => $sagepay->getMessage()]
              );
              $payment
                ->setPaymentStatus(
                  Payment::statusManager()->createInstance('payment_failed')
                )
                ->save();
              break;
          }
        }
      }
    }

    $redirection = $this->redirect(
      'omnipay.sagepay.redirect.finished',
      ['payment' => $payment_id],
      ['absolute' => TRUE, 'https' => TRUE]
    );
    $content = 'Status=' . $status . PHP_EOL . 'RedirectURL=' . $redirection->getTargetUrl();

    return new Response($content);
  }

}
