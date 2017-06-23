<?php

namespace Drupal\omnipay_sagepay\Plugin\Payment\Method;

use Drupal\Core\Url;
use Drupal\omnipay\Plugin\Payment\Method\GatewayFactoryAbstractPaymentMethodBase;
use Drupal\Component\Serialization\Json;
use Omnipay\Common\CreditCard;
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

    $card = new CreditCard();

    $card->setFirstName('TestFirstName');
    $card->setLastName('TestLatsName');
    $card->setBillingAddress1('test');
    $card->setBillingCity('TRURO');
    $card->setBillingPostcode('TR12BY');
    $card->setBillingCountry('GB');

    $card->setShippingCountry($card->getBillingCountry());
    $card->setShippingAddress1($card->getBillingAddress1());
    $card->setShippingCity($card->getBillingCity());
    $card->setShippingPostcode($card->getBillingPostcode());

    $configuration['card'] = $card;

    $configuration['description'] = 'YY';

    return $configuration;
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

}
