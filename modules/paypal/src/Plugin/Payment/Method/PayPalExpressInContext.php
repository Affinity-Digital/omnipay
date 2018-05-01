<?php

namespace Drupal\omnipay_paypal\Plugin\Payment\Method;

/**
 * PayPal Express in Context payment method.
 *
 * @PaymentMethod(
 *   deriver = "\Drupal\omnipay_paypal\Plugin\Payment\Method\PayPalExpressInContextDeriver",
 *   id = "omnipay_paypal_expressincontext",
 *   operations_provider = "\Drupal\omnipay_paypal\Plugin\Payment\Method\PayPalExpressInContextOperationsProvider",
 * )
 */
class PayPalExpressInContext extends PayPalExpress {

  /**
   * {@inheritdoc}
   */
  public function getGatewayName() {
    return 'PayPal_ExpressInContext';
  }

}
