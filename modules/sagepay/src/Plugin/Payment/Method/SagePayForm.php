<?php

namespace Drupal\omnipay_sagepay\Plugin\Payment\Method;

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

}
