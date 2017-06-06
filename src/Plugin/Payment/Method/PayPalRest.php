<?php

namespace Drupal\omnipay\Plugin\Payment\Method;

/**
 * PayPal Standard payment method.
 *
 * @PaymentMethod(
 *   deriver = "\Drupal\omnipay\Plugin\Payment\Method\PayPalRestDeriver",
 *   id = "omnipay",
 *   operations_provider = "\Drupal\omnipay\Plugin\Payment\Method\PayPalRestOperationsProvider",
 * )
 */
class PayPalRest extends PayPalBasic {

  /**
   * {@inheritdoc}
   */
  public function getGatewayName() {
    return 'PayPal_Rest';
  }

}
