<?php

namespace Drupal\omnipay\Plugin\Payment\Method;

/**
 * Derives payment method plugin definitions based on configuration entities.
 */
class PayPalExpressDeriver extends OmniPayDeriver {

  /**
   * {@inheritdoc}
   */
  protected function getId() {
    return 'omnipay_paypal_express';
  }

}
