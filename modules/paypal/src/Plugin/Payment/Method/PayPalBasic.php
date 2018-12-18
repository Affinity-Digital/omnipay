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

  /**
   * Limits the description text to 127 characters or less.
   *
   * @param string $description
   *   Current description string.
   * @param int $limit
   *   Optional description character limit.
   *
   * @return mixed
   *   New description string
   *
   * @see https://developer.paypal.com/docs/api/orders/v1/#definition-purchase_unit
   */
  public function preprocessDescription($description, $limit = 127) {
    return parent::preprocessDescription($description, $limit);
  }

}
