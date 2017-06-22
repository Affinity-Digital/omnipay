<?php

namespace Drupal\omnipay_paypal\Plugin\Payment\MethodConfiguration;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the configuration for the PayPal Express payment method plugin.
 *
 * @PaymentMethodConfiguration(
 *   description = @Translation("PayPal Rest API payment method type."),
 *   id = "omnipay_paypal_rest",
 *   label = @Translation("PayPal Rest API (Omnipay)")
 * )
 */
class PayPalRest extends PayPalBasic {

  /**
   * Gets the client ID of this configuration.
   *
   * @return string
   *   Configured Client ID.
   */
  public function getClientId() {
    return isset($this->configuration['clientId']) ? $this->configuration['clientId'] : '';
  }

  /**
   * Gets the client secret of this configuration.
   *
   * @return string
   *   Configured Client Secret.
   */
  public function getClientSecret() {
    return isset($this->configuration['secret']) ? $this->configuration['secret'] : '';
  }

  /**
   * Gets the webhook ID of this configuration.
   *
   * @return string
   *   Configured webhook Id.
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
    $element['paypal']['secret'] = [
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
    $this->configuration['secret'] = $values['paypal']['secret'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeConfiguration() {
    return parent::getDerivativeConfiguration() + [
      'clientId' => $this->getClientId(),
      'secret' => $this->getClientSecret(),
      'webhookId' => $this->getWebhookId(),
    ];
  }

}
