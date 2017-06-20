<?php

namespace Drupal\omnipay_sagepay\Plugin\Payment\Method;

/**
 * Derives payment method plugin definitions based on configuration entities.
 */
class SagePayServerDeriver extends SagePayDirectDeriver {

  /**
   * {@inheritdoc}
   */
  protected function getId() {
    return 'omnipay_sagepay_server';
  }

}
