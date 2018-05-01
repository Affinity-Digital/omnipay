<?php

namespace Drupal\omnipay_paypal\Plugin\Payment\Method;

/**
 * Derives payment method plugin definitions based on configuration entities.
 */
class PayPalExpressDeriver extends PayPalStandardDeriver {

  /**
   * {@inheritdoc}
   */
  protected function getId() {
    return 'omnipay_paypal_express';
  }

}
