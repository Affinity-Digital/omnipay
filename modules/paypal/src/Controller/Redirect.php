<?php

namespace Drupal\omnipay_paypal\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\payment\Entity\PaymentInterface;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;

/**
 * Handles the "redirect" route.
 */
class Redirect extends ControllerBase {

  /**
   * Determine if access is allowed.
   *
   * @param string $payment_method_id
   *   Payment Method Identifer.
   *
   * @return bool
   *   The access status.
   */
  public function access(PaymentInterface $payment) {
    return AccessResult::allowedIf($this->verify($payment));
  }

  /**
   * @inheritDoc
   */
  private function verify(PaymentInterface $payment) {
    $request = \Drupal::request();
    /** @var Drupal\omnipay\Plugin\Payment\Method\PayPalBasic $payment_method */
    $payment_method = $payment->getPaymentMethod();
    return (
      $payment->getOwnerId() == \Drupal::currentUser()->id() &&
      $request->get('paymentId') == $payment_method->getPaymentId()
    );
  }

  /**
   * PayPal is redirecting the visitor here after the payment process. At this
   * point we don't know the status of the payment yet so we can only load
   * the payment and give control back to the payment context.
   *
   * @param PaymentInterface $payment
   * @return Response
   */
  public function execute(PaymentInterface $payment) {
    $request = \Drupal::request();
    $paymentId = $request->get('paymentId');
    $payerID = $request->get('PayerID');

    /** @var Drupal\omnipay\Plugin\Payment\Method\PayPalBasic $payment_method */
    $payment_method = $payment->getPaymentMethod();
    /** @var PayPal\Rest\ApiContext $api_context */
    $api_context = $payment_method->getApiContext($payment_method::PAYPAL_CONTEXT_TYPE_REDIRECT);

    $p = Payment::get($paymentId, $api_context);
    $execution = new PaymentExecution();
    $execution->setPayerId($payerID);
    try {
      $p->execute($execution, $api_context);
      $payment_method->doCapturePayment();
    }
    catch (\Exception $ex) {
      // TODO: Error handling
    }

    return $this->getResponse($payment);
  }

  public function cancel(PaymentInterface $payment) {
    return $this->getResponse($payment);
  }

  private function getResponse(PaymentInterface $payment) {
    $response = $payment->getPaymentType()->getResumeContextResponse();
    return $response->getResponse();
  }

}
