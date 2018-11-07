<?php

namespace Drupal\omnipay_sagepay\Plugin\Payment\Method;

use Drupal\omnipay\Plugin\Payment\Method\OmnipayDeriver;

/**
 * Derives payment method plugin definitions based on configuration entities.
 */
class SagePayFormDeriver extends OmnipayDeriver {

  /**
   * {@inheritdoc}
   */
  protected function getId() {
    return 'omnipay_sagepay_form';
  }

}
