<?php

namespace Drupal\omnipay\Plugin\Payment\Method;

/**
 * PayPal Standard payment method.
 *
 * @PaymentMethod(
 *   deriver = "\Drupal\omnipay\Plugin\Payment\Method\PayPalStandardDeriver",
 *   id = "omnipay_paypal_standard",
 *   operations_provider = "\Drupal\omnipay\Plugin\Payment\Method\PayPalStandardOperationsProvider",
 * )
 */
class PayPalStandard extends PayPalBasic {

  /**
   * {@inheritdoc}
   */
  public function getGatewayName() {
    return 'PayPal_Pro';
  }

  /**
   * {@inheritdoc}
   */
  public function getWebhookUrl() {
    $configuration = $this->getPluginDefinition();
    list(, $id) = \explode(':', $configuration['id']);
    return self::webhookUrl($id);
  }

  /**
   * {@inheritdoc}
   */
  public function getWebhookId() {
    $configuration = $this->getPluginDefinition();
    return $configuration['webhookId'];
  }

  /**
   * {@inheritdoc}
   */
  public function getApiContext($type) {
    return self::apiContext($this->getPluginDefinition(), $type);
  }

}
