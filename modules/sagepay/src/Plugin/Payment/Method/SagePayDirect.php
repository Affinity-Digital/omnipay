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
   * Payment methods set this to TRUE if they need card details.
   *
   * @return bool
   *   TRUE if card details are needed by payment method.
   */
  public function needCard() {
    return TRUE;
  }

}
