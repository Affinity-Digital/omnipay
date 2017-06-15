<?php

namespace Drupal\omnipay\Plugin\Payment\Method;

/**
 * SagePay Direct payment method.
 *
 * @PaymentMethod(
 *   deriver = "\Drupal\omnipay\Plugin\Payment\Method\SagePayDirectDeriver",
 *   id = "omnipay_sagepay_direct",
 *   operations_provider = "\Drupal\omnipay\Plugin\Payment\Method\SagePayDirectOperationsProvider",
 * )
 */
class SagePayDirect extends SagePayBase {

  /**
   * {@inheritdoc}
   */
  public function getGatewayName() {
    return 'SagePay_Direct';
  }

}
