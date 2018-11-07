<?php

namespace Drupal\omnipay_sagepay\Plugin\Payment\Method;

use Omnipay\Common\Message\RequestInterface;

/**
 * SagePay Form payment method.
 *
 * @PaymentMethod(
 *   deriver = "\Drupal\omnipay_sagepay\Plugin\Payment\Method\SagePayFormDeriver",
 *   id = "omnipay_sagepay_form",
 *   operations_provider = "\Drupal\omnipay_sagepay\Plugin\Payment\Method\SagePayFormOperationsProvider",
 * )
 */
class SagePayForm extends SagePayBase {

  /**
   * {@inheritdoc}
   */
  public function getGatewayName() {
    return 'SagePay_Form';
  }

  /**
   * {@inheritdoc}
   */
  public function doExecutePayment() {
    $this->gateway->setEncryptionKey($this->getEncryptionKey());

    parent::doExecutePayment();
  }

  /**
   * Return the configured encryption Key.
   *
   * @return string
   *   Configured encryption Key.
   */
  public function getEncryptionKey() {
    return empty($this->getPluginDefinition()['encryptionKey']) ? '' : $this->getPluginDefinition()['encryptionKey'];
  }

  /**
   * Update the Request object.
   *
   * In this case set the Return URL.
   *
   * @param \Omnipay\Common\Message\RequestInterface $request
   *   The request object created for this payment.
   */
  public function preProcessRequest(RequestInterface &$request) {
    $request->setReturnUrl($this
      ->getPayment()
      ->getPaymentType()
      ->getReturnUrl()
      ->toString()
    );

    if (method_exists($this->getPayment()->getPaymentType(), 'getFailureUrl')) {
      $request->setFailureUrl($this
        ->getPayment()
        ->getPaymentType()
        ->getFailureUrl()
        ->toString()
      );
    }
  }

}
