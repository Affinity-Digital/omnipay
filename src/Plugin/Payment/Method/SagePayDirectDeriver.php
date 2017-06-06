<?php

namespace Drupal\omnipay\Plugin\Payment\Method;

/**
 * Derives payment method plugin definitions based on configuration entities.
 */
class SagePayDirectDeriver extends OmniPayDeriver {

  /**
   * {@inheritdoc}
   */
  protected function getId() {
    return 'sagepay_direct';
  }

}
