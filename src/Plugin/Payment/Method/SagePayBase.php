<?php

namespace Drupal\omnipay\Plugin\Payment\Method;

/**
 * SagePay Base payment method.
 */
abstract class SagePayBase extends GatewayFactoryAbstractPaymentMethodBase {

  /**
   * {@inheritdoc}
   */
  public function doExecutePayment() {
    $this->gateway->setTestMode(!$this->isProduction());
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

    return $configuration;
  }

  /**
   * Return if not in test mode.
   *
   * @return bool
   *   True if configured for Production.
   */
  protected function isProduction() {
    return empty($this->configuration['testMode']) || !$this->configuration['testMode'];
  }

  /**
   * Return the configured vendor name.
   *
   * @return string
   *   Configured Vendor Name.
   */
  protected function getVendorName() {
    return empty($this->configuration['vendor']) ? '' : $this->configuration['vendor'];
  }

  /**
   * Return the configured referrer id.
   *
   * @return string
   *   Configured Referrer Id.
   */
  protected function getReferrerId() {
    return empty($this->configuration['referrerId']) ? '' : $this->configuration['referrerId'];
  }

}
