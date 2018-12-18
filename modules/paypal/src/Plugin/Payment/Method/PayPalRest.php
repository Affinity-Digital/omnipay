<?php

namespace Drupal\omnipay_paypal\Plugin\Payment\Method;

use Drupal\Core\Url;
use Drupal\payment\Response\Response as PaymentResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * PayPal Express payment method.
 *
 * @PaymentMethod(
 *   deriver = "\Drupal\omnipay_paypal\Plugin\Payment\Method\PayPalRestDeriver",
 *   id = "omnipay_paypal_rest",
 *   operations_provider = "\Drupal\omnipay_paypal\Plugin\Payment\Method\PayPalRestOperationsProvider",
 * )
 */
class PayPalRest extends PayPalBasic {

  /**
   * {@inheritdoc}
   */
  public function getGatewayName() {
    return 'PayPal_Rest';
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

    if ($this->getPayment()) {
      $id = $this->getPayment()->id();
      if (!empty($id)) {
        $configuration['returnUrl'] = Url::fromRoute(
          'omnipay.paypal.redirect.success',
          ['payment' => $id],
          ['absolute' => TRUE, 'https' => TRUE]
        )
          ->toString(TRUE)
          ->getGeneratedUrl();

        $configuration['cancelUrl'] = Url::fromRoute(
          'omnipay.paypal.redirect.cancel',
          ['payment' => $id],
          ['absolute' => TRUE, 'https' => TRUE]
        )
          ->toString(TRUE)
          ->getGeneratedUrl();
      }

      $configuration['description'] = $this->getPayment()->label();
    }

    $this->gateway->setClientId($configuration['clientId']);
    $this->gateway->setSecret($configuration['secret']);
    $this->gateway->setTestMode($configuration['testMode']);
    $configuration['token'] = $this->gateway->getToken();

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setPaymentExecutionResult($response, $request) {
    $url = '';
    $status = 200;
    $headers = array();

    if ($response->isRedirect()) {
      $url = $response->getRedirectUrl();
      $status = Response::HTTP_FOUND;
      $content = $response->getRedirectData();
      if (is_array($content)) {
        $lines = [];
        foreach ($content as $key => $value) {
          $lines[] = $key . '=' . $value;
        }
        $content = \implode("\n", $lines);
      }
    }
    else {
      $content = $response->getData();
      if (is_array($content)) {
        $lines = [];
        foreach ($content as $key => $value) {
          $lines[] = $key . '=' . $value;
        }
        $content = \implode("\n", $lines);
      }
    }

    $this->paymentExecutionResult = new PaymentResponse(
      Url::fromUri($url),
      new Response($content, $status, $headers)
    );
  }

}
