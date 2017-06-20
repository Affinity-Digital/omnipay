<?php

namespace Drupal\omnipay_sagepay\Plugin\Payment\MethodConfiguration;

/**
 * Provides the configuration for the SagePay Server payment method plugin.
 *
 * @PaymentMethodConfiguration(
 *   description = @Translation("SagePay Server payment method type."),
 *   id = "omnipay_sagepay_server",
 *   label = @Translation("SagePay Server (Omnipay)")
 * )
 */
class SagePayServer extends SagePayDirect {

}
