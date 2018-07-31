<?php

namespace Drupal\omnipay\Plugin\Payment\Method;

use Drupal\Core\Session\AccountInterface;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodCapturePaymentInterface;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodRefundPaymentInterface;

/**
 * Provides a basis for payment methods that use \Omnipay\Common\AbstractGateway gateways.
 */
abstract class AbstractPaymentMethodBase extends PaymentMethodBase implements PaymentMethodRefundPaymentInterface, PaymentMethodCapturePaymentInterface {

  /**
   * {@inheritdoc}
   */
  public function setPayment(PaymentInterface $payment) {
    parent::setPayment($payment);
    $this->gateway->setCurrency($payment->getCurrencyCode());
  }

  /**
   * {@inheritdoc}
   */
  public function doRefundPaymentAccess(AccountInterface $account) {
    return $this->gateway->supportsRefund();
  }

  /**
   * {@inheritdoc}
   */
  public function doRefundPayment() {
    return $this->gateway->refund();
  }

  /**
   * {@inheritdoc}
   */
  public function doCapturePaymentAccess(AccountInterface $account) {
    return $this->gateway->supportsCapture();
  }

  /**
   * {@inheritdoc}
   */
  public function doCapturePayment() {
    return $this->gateway->capture();
  }

}
