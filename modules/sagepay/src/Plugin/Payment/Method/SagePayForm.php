<?php

namespace Drupal\omnipay_sagepay\Plugin\Payment\Method;

use Omnipay\Common\Message\ResponseInterface;

/**
 * SagePay Form payment method.
 *
 * @PaymentMethod(
 *   deriver = "\Drupal\omnipay_sagepay\Plugin\Payment\Method\SagePayFormDeriver",
 *   id = "omnipay_sagepay_form",
 *   operations_provider = "\Drupal\omnipay_sagepay\Plugin\Payment\Method\SagePayFormOperationsProvider",
 * )
 */
class SagePayForm extends SagePayBase {

  /**
   * {@inheritdoc}
   */
  public function getGatewayName() {
    return 'SagePay_Form';
  }

  /**
   * {@inheritdoc}
   */
  public function doExecutePayment() {
    $this->gateway->setEncryptionKey($this->getEncryptionKey());

    parent::doExecutePayment();
  }

  /**
   * Return the configured encryption Key.
   *
   * @return string
   *   Configured encryption Key.
   */
  public function getEncryptionKey() {
    return empty($this->getPluginDefinition()['encryptionKey']) ? '' : $this->getPluginDefinition()['encryptionKey'];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {

    $configuration = parent::getConfiguration();

    $configuration['returnUrl'] = Url::fromRoute(
      'omnipay.sagepay.redirect.return',
      [],
      ['absolute' => TRUE, 'https' => TRUE]
    )
      ->toString();

    return $configuration;
  }

  /**
   * Generic extract the transaction reference.
   *
   * Return the internal Transaction Id as the Sage Pay one is not always
   * available.
   *
   * @param \Omnipay\Common\Message\ResponseInterface $response
   *   The returned response.
   *
   * @return string
   *   The transaction reference.
   */
  public function getTransactionReference(ResponseInterface $response) {
    return $response->getTransactionId();
  }

}
