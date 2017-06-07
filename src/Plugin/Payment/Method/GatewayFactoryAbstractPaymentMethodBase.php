<?php

namespace Drupal\omnipay\Plugin\Payment\Method;

/**
 * Provides a basis for payment methods that use \Omnipay\Common\AbstractGateway gateways.
 */
abstract class GatewayFactoryAbstractPaymentMethodBase extends AbstractPaymentMethodBase {

  /**
   * Returns the gateway name.
   *
   * @return string
   *   The Gateway name.
   */
  abstract protected function getGatewayName();

}
