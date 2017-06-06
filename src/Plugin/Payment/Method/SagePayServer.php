<?php

namespace Drupal\omnipay\Plugin\Payment\Method;

/**
 * SagePay Server payment method.
 *
 * @PaymentMethod(
 *   deriver = "\Drupal\omnipay\Plugin\Payment\Method\SagePayServerDeriver",
 *   id = "omnipay",
 *   operations_provider = "\Drupal\omnipay\Plugin\Payment\Method\SagePayServerOperationsProvider",
 * )
 */
class SagePayServer extends GatewayFactoryAbstractPaymentMethodBase {

  /**
   * {@inheritdoc}
   */
  public function getGatewayName() {
    return 'SagePay_Server';
  }

}
