<?php

namespace Drupal\omnipay_paypal\Plugin\Payment\Method;

use Drupal\Core\PhpStorage\PhpStorageFactory;
use Drupal\Core\Url;
use Omnipay\PayPal\Message\RestAuthorizeResponse;

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
  public function getWebhookUrl() {
    $configuration = $this->getPluginDefinition();
    list(, $id) = \explode(':', $configuration['id']);
    return self::webhookUrl($id);
  }

  /**
   * {@inheritdoc}
   */
  public function getWebhookId() {
    $configuration = $this->getPluginDefinition();
    return $configuration['webhookId'];
  }

  /**
   * {@inheritdoc}
   */
  public function getApiContext($type) {
    return self::apiContext($this->getPluginDefinition(), $type);
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
      $configuration['returnUrl'] = Url::fromRoute(
        'omnipay.paypal.redirect.success',
        ['payment' => $this->getPayment()->id()],
        ['absolute' => TRUE, 'https' => TRUE]
        )
        ->toString(TRUE)
        ->getGeneratedUrl();

      $configuration['cancelUrl'] = Url::fromRoute(
        'omnipay.paypal.redirect.cancel',
        ['payment' => $this->getPayment()->id()],
        ['absolute' => TRUE, 'https' => TRUE]
        )
        ->toString(TRUE)
        ->getGeneratedUrl();
    }

    $configuration['description'] = 'This is a test';

    $this->gateway->setClientId($configuration['clientId']);
    $this->gateway->setSecret($configuration['secret']);
    $configuration['token'] = $this->gateway->getToken();

    return $configuration;
  }

  /**
   * Create new API Context.
   *
   * @param array $configuration
   *   Array of configuration values.
   * @param string $type
   *   Logging level.
   *
   * @return \Drupal\omnipay\Plugin\Payment\Method\ApiContext
   *   New API context.
   */
  public static function apiContext(array $configuration, $type) {
    $apiContext = new ApiContext(
      new OAuthTokenCredential(
        $configuration['clientId'],
        $configuration['clientSecret']
      )
    );

    $storage = PhpStorageFactory::get('paypal_api_context');
    if (!$storage->exists('auth.cache')) {
      $storage->save('auth.cache', '');
    }

    $apiContext->setConfig([
      'mode' => $configuration['production'] ? 'live' : 'sandbox',
      'log.LogEnabled' => $configuration['logging'][$type],
      'log.FileName' => file_directory_temp() . '/DrupalPayPal.log',
      'log.LogLevel' => $configuration['loglevel'],
      'cache.enabled' => TRUE,
      'cache.FileName' => DRUPAL_ROOT . '/' . $storage->getFullPath('auth.cache'),
    ]);

    return $apiContext;
  }

  /**
   * Create the local API URL.
   *
   * @param string $id
   *   Payment Method Identifer.
   *
   * @return string
   *   Local API URL.
   */
  public static function webhookUrl($id) {
    $webhook = new Url(
      'omnipay.paypal.webhook',
      ['payment_method_id' => $id],
      ['absolute' => TRUE, 'https' => TRUE]
    );
    return $webhook->toString(TRUE)->getGeneratedUrl();
  }

}
