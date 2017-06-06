<?php

namespace Drupal\omnipay\Controller\SagePay;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\payment\Entity\PaymentInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles the "webhook" route.
 */
class Webhook extends ControllerBase {

  public function access($payment_method_id) {
    return AccessResult::allowedIf($this->verify($payment_method_id));
  }

  /**
   * SagePay is redirecting the visitor here after the payment process. At
   * this point we don't know the status of the payment yet so we can only
   * load the payment and give control back to the payment context.
   *
   * @param PaymentInterface $payment
   * @return Response
   */
  public function execute(PaymentInterface $payment) {
    $request = \Drupal::request();
    $paymentId = $request->get('paymentId');
    $payerID = $request->get('PayerID');

    /** @var PayPalBasic $payment_method */
    $payment_method = $payment->getPaymentMethod();
    /** @var ApiContext $api_context */
    $api_context = $payment_method->getApiContext($payment_method::PAYPAL_CONTEXT_TYPE_REDIRECT);

    $p = Payment::get($paymentId, $api_context);
    $execution = new PaymentExecution();
    $execution->setPayerId($payerID);
    try {
      $p->execute($execution, $api_context);
      $payment_method->doCapturePayment();
    } catch (\Exception $ex) {
      // TODO: Error handling
    }

    return $this->getResponse($payment);
  }

  public function notify(PaymentInterface $payment) {
    return $this->getResponse($payment);
  }

  private function getResponse(PaymentInterface $payment) {
    $response = $payment->getPaymentType()->getResumeContextResponse();
    return $response->getResponse();
  }

}