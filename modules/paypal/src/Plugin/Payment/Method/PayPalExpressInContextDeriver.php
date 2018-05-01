<?php

namespace Drupal\omnipay_paypal\Plugin\Payment\Method;

/**
 * Derives payment method plugin definitions based on configuration entities.
 */
class PayPalExpressInContextDeriver extends PayPalExpressDeriver {

  /**
   * {@inheritdoc}
   */
  protected function getId() {
    return 'omnipay_paypal_expressincontext';
  }

}
