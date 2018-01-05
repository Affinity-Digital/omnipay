<?php

namespace Drupal\omnipay_sagepay\Plugin\Payment\Method;

use Drupal\Core\Url;
use Drupal\omnipay\Plugin\Payment\Method\GatewayFactoryAbstractPaymentMethodBase;
use Drupal\Component\Serialization\Json;
use Omnipay\Common\Message\ResponseInterface;

/**
 * SagePay Base payment method.
 */
abstract class SagePayBase extends GatewayFactoryAbstractPaymentMethodBase {

  /**
   * {@inheritdoc}
   */
  public function doExecutePayment() {
    $this->gateway->setVendor($this->getVendorName());
    $this->gateway->setReferrerId($this->getReferrerId());

    parent::doExecutePayment();
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

    $configuration['notifyUrl'] = Url::fromRoute(
      'omnipay.sagepay.redirect.notify',
      [],
      ['absolute' => TRUE, 'https' => TRUE]
    )
      ->toString();

    return $configuration;
  }

  /**
   * Return the configured vendor name.
   *
   * @return string
   *   Configured Vendor Name.
   */
  public function getVendorName() {
    return empty($this->getPluginDefinition()['vendor']) ? '' : $this->getPluginDefinition()['vendor'];
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

  /**
   * Generic extract the transaction reference.
   *
   * The transaction reference is a JSON encoded string.
   * We just need the 'VPSTxId' field.
   *
   * @param \Omnipay\Common\Message\ResponseInterface $response
   *   The returned response.
   *
   * @return string
   *   The tranasction reference.
   */
  public function getTransactionReference(ResponseInterface $response) {
    $transaction_reference = Json::decode($response->getTransactionReference());
    return $transaction_reference['VPSTxId'];
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

  /**
   * Update the configuration based upon the response.
   *
   * @param \Omnipay\Common\Message\ResponseInterface $response
   *   The response object.
   */
  public function updateConfiguration($response) {
    $transaction_reference = Json::decode($response->getTransactionReference());
    foreach ($transaction_reference as $key => $value) {
      $this->configuration[$key] = $value;
    }
    $this->getPayment()->save();
  }

}
