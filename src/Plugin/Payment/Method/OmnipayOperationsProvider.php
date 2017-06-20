<?php

namespace Drupal\omnipay\Plugin\Payment\Method;

use Drupal\payment\Plugin\Payment\Method\BasicOperationsProvider;

/**
 * Provides omnipay_* operations based on config entities.
 */
abstract class OmnipayOperationsProvider extends BasicOperationsProvider {

  /**
   * {@inheritdoc}
   */
  protected function getPaymentMethodConfiguration($plugin_id) {
    $entity_id = \explode(':', $plugin_id)[1];

    return $this->paymentMethodConfigurationStorage->load($entity_id);
  }

}
