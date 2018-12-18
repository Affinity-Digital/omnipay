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

}
