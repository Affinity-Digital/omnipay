<?php

namespace Drupal\omnipay_sagepay\Plugin\Payment\Method;

use Omnipay\Common\Message\RequestInterface;
use Omnipay\SagePay\Message\AbstractRequest;

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

  /**
   * {@inheritdoc}
   */
  public function preProcessRequest(RequestInterface &$request) {
    /** @var  \Omnipay\SagePay\Message\AbstractRequest $request */
    $request->setProfile(AbstractRequest::PROFILE_NORMAL);
  }

}
