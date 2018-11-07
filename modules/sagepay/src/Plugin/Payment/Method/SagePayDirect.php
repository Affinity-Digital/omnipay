<?php

namespace Drupal\omnipay_sagepay\Plugin\Payment\Method;

/**
 * SagePay Direct payment method.
 *
 * @PaymentMethod(
 *   deriver = "\Drupal\omnipay_sagepay\Plugin\Payment\Method\SagePayDirectDeriver",
 *   id = "omnipay_sagepay_direct",
 *   operations_provider = "\Drupal\omnipay_sagepay\Plugin\Payment\Method\SagePayDirectOperationsProvider",
 * )
 */
class SagePayDirect extends SagePayBase {

  /**
   * {@inheritdoc}
   */
  public function getGatewayName() {
    return 'SagePay_Direct';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {

    $configuration = parent::getConfiguration();

    $configuration['notifyUrl'] = Url::fromRoute(
      'omnipay.sagepay.redirect.notify',
      [],
      ['absolute' => TRUE, 'https' => TRUE]
    )
      ->toString();

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function doExecutePayment() {
    $this->gateway->setReferrerId($this->getReferrerId());

    parent::doExecutePayment();
  }

  /**
   * Return the configured referrer id.
   *
   * @return string
   *   Configured Referrer Id.
   */
  public function getReferrerId() {
    return empty($this->getPluginDefinition()['referrerId']) ? '' : $this->getPluginDefinition()['referrerId'];
  }

}
