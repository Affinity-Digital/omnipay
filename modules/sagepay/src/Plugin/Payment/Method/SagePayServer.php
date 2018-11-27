<?php

namespace Drupal\omnipay_sagepay\Plugin\Payment\Method;

use Drupal\Core\Url;

/**
 * SagePay Server payment method.
 *
 * @PaymentMethod(
 *   deriver = "\Drupal\omnipay_sagepay\Plugin\Payment\Method\SagePayServerDeriver",
 *   id = "omnipay_sagepay_server",
 *   operations_provider = "\Drupal\omnipay_sagepay\Plugin\Payment\Method\SagePayServerOperationsProvider",
 * )
 */
class SagePayServer extends SagePayDirect {

  /**
   * {@inheritdoc}
   */
  public function getGatewayName() {
    return 'SagePay_Server';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {

    $configuration = parent::getConfiguration();
    $configuration['returnUrl'] = self::webhookUrl();

    return $configuration;
  }

  /**
   * Return the fully URL of the redirection page.
   *
   * @return string
   *   Fully qualified URL of the redirection page handler.
   */
  public static function webhookUrl() {
    $webhook = new Url('omnipay.sagepay.redirect.return',
      [],
      ['absolute' => TRUE, 'https' => TRUE]
    );
    return $webhook->toString(TRUE)->getGeneratedUrl();
  }

}
