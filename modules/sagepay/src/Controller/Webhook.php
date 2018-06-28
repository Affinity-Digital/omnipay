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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

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
   * Construct the class using passed paramters.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection object.
   */
  public function __construct(Connection $connection) {
    $this->setConnection($connection);
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
    return new static(\Drupal::database());
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
    /** @var \Drupal\omnipay_sagepay\Plugin\Payment\Method\SagePayBasic $payment_method */
    $payment_method = $payment->getPaymentMethod();
    return $payment->getOwnerId() == \Drupal::currentUser()->id();
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
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Where to redirect to.
   */
  public function notify(Request $request) {
    $containerClient = \Drupal::service('http_client');

    // Onmipay 3.x Client class is \Omnipay\Common\Http\ClientInterface.
    if ($containerClient instanceof ClientInterface) {
      $client = $containerClient;
    }
    else {
      // Create a new instance and use the passed instance's configuration.
      $config = $containerClient->getConfig();
      $client = new OmnipayClient(Client::createWithConfig($config));
    }

    /** @var \Omnipay\SagePay\ServerGateway $gateway */
    $gateway = GatewayFactory::create(
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

        $status = 'INVALID';
        if ($sagepay->isValid()) {
          $status = 'OK';
          switch ($sagepay->getStatus()) {
            // If the transaction was authorised.
            case 'OK':
              $payment
                ->setPaymentStatus(
                  Payment::statusManager()->createInstance('payment_success')
                )
                ->save();
              break;
              // (for European Payment Types only), if the transaction
              // ... has yet to be accepted or rejected.
            case 'PENDING':
              $payment
                ->setPaymentStatus(
                  Payment::statusManager()->createInstance('payment_pending')
                )
                ->save();
              break;
              
              // If the user decided to cancel the transaction whilst
              // ... on our payment pages.
            case 'ABORT':
              $payment
                ->setPaymentStatus(
                  Payment::statusManager()->createInstance('payment_cancelled')
                )
                ->save();
              break;
            // If the authorisation was failed by the bank.
            case 'NOTAUTHED':

              // If your fraud screening rules were not met.
            case 'REJECTED':
              // If an error has occurred at Sage Pay.
              // These are very infrequent, but your site should handle them
              // anyway. They normally indicate a problem with bank connectivity.
            case 'ERROR':
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
    echo $content;
    exit;
  }

}
