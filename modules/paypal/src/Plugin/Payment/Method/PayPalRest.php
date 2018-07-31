<?php

namespace Drupal\omnipay_paypal\Plugin\Payment\Method;

use Drupal\Core\Url;

/**
 * PayPal Express payment method.
 *
 * @PaymentMethod(
 *   deriver = "\Drupal\omnipay_paypal\Plugin\Payment\Method\PayPalRestDeriver",
 *   id = "omnipay_paypal_rest",
 *   operations_provider = "\Drupal\omnipay_paypal\Plugin\Payment\Method\PayPalRestOperationsProvider",
 * )
 */
class PayPalRest extends PayPalBasic {

  /**
   * {@inheritdoc}
   */
  public function getGatewayName() {
    return 'PayPal_Rest';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $configuration = parent::getConfiguration();

    $definition = $this->getPluginDefinition();

    foreach ($configuration as $key => $value) {
      if (empty($value) && !empty($definition[$key])) {
        $configuration[$key] = $definition[$key];
      }
    }

    if ($this->getPayment()) {
      $id = $this->getPayment()->id();
      if (!empty($id)) {
        $configuration['returnUrl'] = Url::fromRoute(
          'omnipay.paypal.redirect.success',
          ['payment' => $id],
          ['absolute' => TRUE, 'https' => TRUE]
        )
          ->toString(TRUE)
          ->getGeneratedUrl();

        $configuration['cancelUrl'] = Url::fromRoute(
          'omnipay.paypal.redirect.cancel',
          ['payment' => $id],
          ['absolute' => TRUE, 'https' => TRUE]
        )
          ->toString(TRUE)
          ->getGeneratedUrl();
      }

      $configuration['description'] = $this->getPayment()->label();
    }

    $this->gateway->setClientId($configuration['clientId']);
    $this->gateway->setSecret($configuration['secret']);
    $this->gateway->setTestMode($configuration['testMode']);
    $configuration['token'] = $this->gateway->getToken();

    return $configuration;
  }

}
