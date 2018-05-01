<?php

namespace Drupal\omnipay_paypal\Plugin\Payment\MethodConfiguration;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the configuration for the PayPal Pro payment method plugin.
 *
 * @PaymentMethodConfiguration(
 *   description = @Translation("PayPal Pro payment method type."),
 *   id = "omnipay_paypal_standard",
 *   label = @Translation("PayPal Pro (Omnipay)")
 * )
 */
class PayPalStandard extends PayPalBasic {

  /**
   * Gets the username of this configuration.
   *
   * @return string
   *   Configured user name.
   */
  public function getUsername() {
    return isset($this->configuration['username']) ? $this->configuration['username'] : '';
  }

  /**
   * Gets the password of this configuration.
   *
   * @return string
   *   Configured password.
   */
  public function getPassword() {
    return isset($this->configuration['password']) ? $this->configuration['password'] : '';
  }

  /**
   * Gets the signature of this configuration.
   *
   * @return string
   *   Configured signature.
   */
  public function getSignature() {
    return isset($this->configuration['signature']) ? $this->configuration['signature'] : '';
  }

  /**
   * Implements a form API #process callback.
   */
  public function processBuildConfigurationForm(array &$element, FormStateInterface $form_state, array &$form) {
    parent::processBuildConfigurationForm($element, $form_state, $form);

    $element['paypal']['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $this->getUsername(),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $element['paypal']['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#default_value' => $this->getPassword(),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $element['paypal']['signature'] = [
      '#type' => 'email',
      '#title' => $this->t('Signature'),
      '#default_value' => $this->getSignature(),
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
    \array_pop($parents);
    $values = $form_state->getValues();
    $values = NestedArray::getValue($values, $parents);
    $this->configuration['username'] = $values['paypal']['username'];
    $this->configuration['password'] = $values['paypal']['password'];
    $this->configuration['signature'] = $values['paypal']['signature'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeConfiguration() {
    return parent::getDerivativeConfiguration() + [
      'username' => $this->getUserame(),
      'password' => $this->getPassword(),
      'signature' => $this->getSignature(),
    ];
  }

}
