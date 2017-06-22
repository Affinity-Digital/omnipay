<?php

namespace Drupal\omnipay_paypal\Plugin\Payment\Method;

use Drupal\omnipay\Plugin\Payment\Method\GatewayFactoryAbstractPaymentMethodBase;
use Omnipay\PayPal\PayPalItem;
use Omnipay\PayPal\PayPalItemBag;

/**
 * Abstract class for PayPal payment methods.
 */
abstract class PayPalBasic extends GatewayFactoryAbstractPaymentMethodBase {

  const PAYPAL_CONTEXT_TYPE_ADMIN    = 'admin';
  const PAYPAL_CONTEXT_TYPE_CREATE   = 'create';
  const PAYPAL_CONTEXT_TYPE_WEBHOOK  = 'webhook';
  const PAYPAL_CONTEXT_TYPE_REDIRECT = 'redirect';

  /**
   * Return the local API URL.
   *
   * @return string
   *   Local API URL.
   */
  abstract public function getWebhookUrl();

  /**
   * Return the webhook identifer value.
   *
   * @return string
   *   Webhook identifer value.
   */
  abstract public function getWebhookId();

  /**
   * Get the current API Context.
   *
   * @param string $type
   *   Pal Pay context type.
   *
   * @return \PayPal\Rest\ApiContext
   *   Current API Context.
   */
  abstract public function getApiContext($type);

  /**
   * Set Payment Id.
   *
   * @param string $paymentId
   *   New Payment Id.
   */
  private function setPaymentId($paymentId) {
    $this->configuration['paymentID'] = $paymentId;
    $this->getPayment()->save();
  }

  /**
   * Return the Payment Id from configuration.
   *
   * @return null|mixed
   *   Payment Id if present.
   */
  public function getPaymentId() {
    return isset($this->configuration['paymentID']) ? $this->configuration['paymentID'] : NULL;
  }

  /**
   * Return the class to use for ItemBag.
   *
   * @return string
   *   The class name to use.
   */
  public function getItemBagClass() {
    return PayPalItemBag::class;
  }

  /**
   * Return the class to use for Item.
   *
   * @return string
   *   The class name to use.
   */
  public function getItemClass() {
    return PayPalItem::class;
  }

}
