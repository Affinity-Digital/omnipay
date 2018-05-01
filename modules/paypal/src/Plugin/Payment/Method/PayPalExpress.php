<?php

namespace Drupal\omnipay_paypal\Plugin\Payment\Method;

/**
 * PayPal Express payment method.
 *
 * @PaymentMethod(
 *   deriver = "\Drupal\omnipay_paypal\Plugin\Payment\Method\PayPalExpressDeriver",
 *   id = "omnipay_paypal_express",
 *   operations_provider = "\Drupal\omnipay_paypal\Plugin\Payment\Method\PayPalExpressOperationsProvider",
 * )
 */
class PayPalExpress extends PayPalStandard {

  /**
   * {@inheritdoc}
   */
  public function getGatewayName() {
    return 'PayPal_Express';
  }

}
