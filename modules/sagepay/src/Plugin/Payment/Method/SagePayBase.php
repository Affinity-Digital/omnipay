<?php

namespace Drupal\omnipay_sagepay\Plugin\Payment\Method;

use Drupal\omnipay\Plugin\Payment\Method\GatewayFactoryAbstractPaymentMethodBase;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\payment\Response\Response as PaymentResponse;
use Omnipay\Common\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * SagePay Base payment method.
 */
abstract class SagePayBase extends GatewayFactoryAbstractPaymentMethodBase {

  /**
   * {@inheritdoc}
   */
  public function doExecutePayment() {
    $this->gateway->setVendor($this->getVendorName());

    parent::doExecutePayment();
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

    return $configuration;
  }

  /**
   * Return the configured vendor name.
   *
   * @return string
   *   Configured Vendor Name.
   */
  public function getVendorName() {
    return empty($this->getPluginDefinition()['vendor']) ? '' : $this->getPluginDefinition()['vendor'];
  }

  /**
   * Generic extract the transaction reference.
   *
   * The transaction reference is a JSON encoded string.
   * We just need the 'VPSTxId' field.
   *
   * @param \Omnipay\Common\Message\ResponseInterface $response
   *   The returned response.
   *
   * @return string
   *   The transaction reference.
   */
  public function getTransactionReference(ResponseInterface $response) {
    $transaction_reference = Json::decode($response->getTransactionReference());
    return $transaction_reference['VPSTxId'];
  }

  /**
   * Update the configuration based upon the response.
   *
   * @param \Omnipay\Common\Message\RedirectResponseInterface|\Omnipay\Common\Message\ResponseInterface $response
   *   The response object.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateConfiguration($response) {
    if ($transaction_reference = Json::decode($response->getTransactionReference())) {
      foreach ($transaction_reference as $key => $value) {
        $this->configuration[$key] = $value;
      }
      $this->getPayment()->save();
    }
  }

  /**
   * Limits the description text to 100 characters or less.
   *
   * @param string $description
   *   Current description string.
   * @param int $limit
   *   Optional description character limit.
   *
   * @return mixed
   *   New description string
   */
  public function preprocessDescription($description, $limit = 100) {
    return parent::preprocessDescription($description, $limit);
  }

  /**
   * Payment methods set this to TRUE if they need card details.
   *
   * @return bool
   *   TRUE if card details are needed by payment method.
   */
  public function needCard() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function setPaymentExecutionResult($response, $request) {

    // Set some default values for the payment execution result.
    $url = '';
    $status = Response::HTTP_OK;
    $headers = [];

    // Check if instance of \Omnipay\SagePay\Message\ServerAuthorizeResponse.
    if($response instanceof \Omnipay\SagePay\Message\ServerAuthorizeResponse) {
      /** @var \Omnipay\SagePay\Message\ServerAuthorizeResponse */
      // Check if the response is a redirect.
      if ($response->isRedirect()) {
        // If the response is a redirect, set the redirect URL and status.
        $url =  Url::fromUri($response->getRedirectUrl());
        $status = Response::HTTP_FOUND;
        // Get the redirect data and format it as a string.
        $content = $response->getRedirectData();
      }
      else {
        $content = $response->getData();
      }
      if (is_array($content)) {
        $lines = [];
        foreach ($content as $key => $value) {
          $lines[] = $key . '=' . $value;
        }
        $content = \implode("\n", $lines);
      }
    }
    else {

      /** @var \Drupal\payment\Response\Response $response */
      /** @var \Drupal\Core\Routing\TrustedRedirectResponse $reply */
      // If the response is not a redirect, get the response object.
      $reply = $response->getResponse();

      // Get the content of the response and format it as a string.
      $content = $reply->getContent();
      if (is_array($content)) {
        $lines = [];
        foreach ($content as $key => $value) {
          $lines[] = $key . '=' . $value;
        }
        $content = \implode("\n", $lines);
      }
    }

    // Create a new payment response object using the URL, status, and content.
    $this->paymentExecutionResult = new PaymentResponse(
        $url,
        new Response($content, $status, $headers)
    );
  }

}
