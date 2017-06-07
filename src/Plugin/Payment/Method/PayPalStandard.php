<?php

namespace Drupal\omnipay\Plugin\Payment\Method;

/**
 * PayPal Standard payment method.
 *
 * @PaymentMethod(
 *   deriver = "\Drupal\omnipay\Plugin\Payment\Method\PayPalStandardDeriver",
 *   id = "omnipay_paypal_standard",
 *   operations_provider = "\Drupal\omnipay\Plugin\Payment\Method\PayPalStandardOperationsProvider",
 * )
 */
class PayPalStandard extends PayPalBasic {

  /**
   * {@inheritdoc}
   */
  public function getGatewayName() {
    return 'PayPal_Rest';
  }

}
