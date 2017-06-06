<?php

namespace Drupal\omnipay\Plugin\Payment\Method;

/**
 * Derives payment method plugin definitions based on configuration entities.
 */
class PayPalExpressDeriver extends PayPalBasicDeriver {

  /**
   * {@inheritdoc}
   */
  protected function getId() {
    return 'paypal_express';
  }

}
