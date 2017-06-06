<?php

namespace Drupal\omnipay\Plugin\Payment\MethodConfiguration;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\omnipay\Plugin\Payment\Method\PayPalExpress as PayPalExpressMethod;
use Omnipay\Omnipay;

/**
 * Provides the configuration for the PayPal Express payment method plugin.
 *
 * @PaymentMethodConfiguration(
 *   description = @Translation("PayPal Express (Omnipay) payment method type."),
 *   id = "omnipay:paypal_express",
 *   label = @Translation("PayPal Express (Omnipay)")
 * )
 */
class PayPalExpress extends PayPalBasic {

  /**
   * Gets the client ID of this configuration.
   *
   * @return string
   */
  public function getClientId() {
    return isset($this->configuration['clientId']) ? $this->configuration['clientId'] : '';
  }

  /**
   * Gets the client secret of this configuration.
   *
   * @return string
   */
  public function getClientSecret() {
    return isset($this->configuration['clientSecret']) ? $this->configuration['clientSecret'] : '';
  }

  /**
   * Gets the webhook ID of this configuration.
   *
   * @return string
   */
  public function getWebhookId() {
    return isset($this->configuration['webhookId']) ? $this->configuration['webhookId'] : '';
  }

  /**
   * Implements a form API #process callback.
   */
  public function processBuildConfigurationForm(array &$element, FormStateInterface $form_state, array &$form) {
    parent::processBuildConfigurationForm($element, $form_state, $form);

    $element['paypal']['clientId'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#default_value' => $this->getClientId(),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $element['paypal']['clientSecret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Secret'),
      '#default_value' => $this->getClientSecret(),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $parents = $form['plugin_form']['paypal']['#parents'];
    array_pop($parents);
    $values = $form_state->getValues();
    $values = NestedArray::getValue($values, $parents);
    $this->configuration['clientId'] = $values['paypal']['clientId'];
    $this->configuration['clientSecret'] = $values['paypal']['clientSecret'];
    $this->configuration['webhookId'] = $this->updateWebhook($this->configuration, $form_state->getValue('id'));
  }

  /**
   * @param $configuration
   * @param $id
   * @return string
   */
  private function updateWebhook($configuration, $id) {
    $webhookId  = $this->getWebhookId();
    $webhookUrl = PayPalExpressMethod::webhookUrl($id);

    // Use the Rest interface.
    $gateway = Omnipay::create('PayPal_Rest');
    $gateway->initialize(array(
      'clientId' => $this->configuration['clientId'],
      'secret'   => $this->configuration['clientSecret'],
      'testMode' => true, // Or false when you are ready for live transactions
    ));

    var_dump($settings);

    $apiContext = PayPalExpressMethod::apiContext($configuration, PayPalExpressMethod::PAYPAL_CONTEXT_TYPE_ADMIN);
    if (!empty($webhookId)) {
      try {
        $webhook = Webhook::get($webhookId, $apiContext);
        if ($webhookUrl != $webhook->getUrl()) {
          $patch = new Patch();
          $patch->setOp('replace')
            ->setPath('/url')
            ->setValue($webhookUrl);
          $patchRequest = new PatchRequest();
          $patchRequest->addPatch($patch);
          try {
            $webhook->update($patchRequest, $apiContext);
          } catch (PayPalConnectionException $ppex) {
            $this->handlePayPalException('Error updating webhook for PayPal payment method:', $ppex);
          }
        }
      } catch (\Exception $ex) {
        $webhookId = '';
      }
    }

    if (empty($webhookId)) {
      try {
        // Create a new webhook
        $webhook = new Webhook();
        $webhook->setUrl($webhookUrl);
        $eventType = new WebhookEventType();
        $eventType->setName('*');
        $webhook->setEventTypes([$eventType]);
        $webhook = $webhook->create($apiContext);
        $webhookId = $webhook->getId();
      } catch (PayPalConnectionException $ppex) {
        $this->handlePayPalException('Error creating webhook for PayPal payment method:', $ppex);
      } catch (\Exception $ex) {
        drupal_set_message($this->t('Something went wrong when creating the webhook for your PayPal Express payment method.'), 'error');
      }
    }
    return $webhookId;
  }

  /**
   * @param string $msg
   * @param PayPalConnectionException $ppex
   */
  private function handlePayPalException($msg, $ppex) {
    $data = \GuzzleHttp\json_decode($ppex->getData());
    drupal_set_message($this->t($msg), 'error');
    foreach ($data->details as $detail) {
      drupal_set_message($this->t('%issue', [
        '%issue' => $detail->issue,
      ]), 'error');
    }
  }

  /**
   * @inheritDoc
   */
  public function getDerivativeConfiguration() {
    return parent::getDerivativeConfiguration() + [
      'clientId' => $this->getClientId(),
      'clientSecret' => $this->getClientSecret(),
      'webhookId' => $this->getWebhookId(),
    ];
  }

}
