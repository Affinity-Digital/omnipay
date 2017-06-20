<?php

namespace Drupal\omnipay_paypal\Plugin\Payment\Method;

use Drupal\omnipay\Plugin\Payment\Method\OmnipayDeriver;

/**
 * Derives payment method plugin definitions based on configuration entities.
 */
class PayPalRestDeriver extends OmnipayDeriver {

  /**
   * {@inheritdoc}
   */
  protected function getId() {
    return 'omnipay_paypal_rest';
  }

}
