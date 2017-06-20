<?php

namespace Drupal\omnipay_paypal\Plugin\Payment\MethodConfiguration;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the configuration for the PayPal Standard payment method plugin.
 *
 * @PaymentMethodConfiguration(
 *   description = @Translation("PayPal Standard payment method type."),
 *   id = "omnipay_paypal_standard",
 *   label = @Translation("PayPal Standard (Omnipay)")
 * )
 */
class PayPalStandard extends PayPalBasic {

  /**
   * Gets the email of this configuration.
   *
   * @return string
   *   Configured Email address.
   */
  public function getEmail() {
    return isset($this->configuration['email']) ? $this->configuration['email'] : '';
  }

  /**
   * Implements a form API #process callback.
   */
  public function processBuildConfigurationForm(array &$element, FormStateInterface $form_state, array &$form) {
    parent::processBuildConfigurationForm($element, $form_state, $form);

    $element['paypal']['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#default_value' => $this->getEmail(),
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
    $this->configuration['email'] = $values['paypal']['email'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeConfiguration() {
    return parent::getDerivativeConfiguration() + [
      'email' => $this->getEmail(),
    ];
  }

}
