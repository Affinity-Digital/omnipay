<?php

namespace Drupal\omnipay_sagepay\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\Payment;
use Guzzle\Http\Client;
use Guzzle\Http\ClientInterface;
use Omnipay\Common\GatewayFactory;
use Omnipay\SagePay\Message\ServerNotifyRequest;
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
   * Determine access to this route.
   *
   * @param string $payment_method_id
   *   The payment method id value.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Access value.
   */
  public function access($payment_method_id) {
    return AccessResult::allowedIf($this->verify($payment_method_id));
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
    // Do nothing as there is no finished functionality.
  }

  /**
   * Sage Pay is notify us of the payment progress.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request structure.
   */
  public function notify(Request $request) {
    $containerClient = \Drupal::service('http_client');

    // Onmipay 2.x Client class is \Guzzle\Http\ClientInterface.
    if ($containerClient instanceof ClientInterface) {
      $client = $containerClient;
    }
    else {
      $config = $containerClient->getConfig();
      // Create a new instance and use the passed instance's configuration.
      $client = new Client('', $config);
    }

    /** @var \Omnipay\SagePay\ServerGateway $gateway */
    $gateway = GatewayFactory::create(
      'SagePay_Server',
      $client,
      $request
    );

    $parameters = [];

    /** @var \Omnipay\SagePay\Message\ServerNotifyRequest $sagepay */
    $sagepay = $gateway->acceptNotification($parameters);

    /** @var \Drupal\Core\Database\Query\SelectInterface $select */
    $select = $this
      ->getConnection()
      ->select('omnipay', 'o');

    $info = $select
      ->condition('tid', $sagepay->getTransactionId())
      ->fields('o', ['pid'])
      ->execute()
      ->fetchAssoc();

    $payment_id = $info['pid'];
    /** @var \Drupal\payment\Entity\PaymentInterface $payment */
    $payment = $this
      ->entityTypeManager
      ->getStorage('payment')
      ->load($payment_id);

    $status = $sagepay->getStatus();
    switch ($status) {
      // If the transaction was authorised.
      case ServerNotifyRequest::SAGEPAY_STATUS_OK:

        // (for European Payment Types only), if the transaction
        // ... has yet to be accepted or rejected.
      case ServerNotifyRequest::SAGEPAY_STATUS_PENDING:

        if ($sagepay->isValid()) {
          $payment
            ->setStatus(
              Payment::statusManager()->createInstance('payment_success')
            )
            ->save();
        }
        else {
          $payment
            ->setStatus(
              Payment::statusManager()->createInstance('payment_failed')
            )
            ->save();
        }
        break;

      // If the authorisation was failed by the bank.
      case ServerNotifyRequest::SAGEPAY_STATUS_NOTAUTHED:

        // If your fraud screening rules were not met.
      case ServerNotifyRequest::SAGEPAY_STATUS_REJECTED:
        // If the user decided to cancel the transaction whilst
        // ... on our payment pages.
      case ServerNotifyRequest::SAGEPAY_STATUS_ABORT:
        // If an error has occurred at Sage Pay.
        // These are very infrequent, but your site should handle them anyway.
        // They normally indicate a problem with bank connectivity.
      case ServerNotifyRequest::SAGEPAY_STATUS_ERROR:
      default:
        $this
          ->getLogger('omnipay_sagepay_payment')
          ->error(
            'Sagepay-error: @status -> @detail',
            ['@status' => $status, '@detail' => $sagepay->getMessage()]
        );
        $payment->setStatus(
          Payment::statusManager()->createInstance('payment_failed')
        );
        break;
    }
    $payment->save();
    $this->redirect(
      'omnipay.sagepay.redirect.finished',
      ['payment' => $payment->id()],
      ['absolute' => TRUE]
    );
  }

}
