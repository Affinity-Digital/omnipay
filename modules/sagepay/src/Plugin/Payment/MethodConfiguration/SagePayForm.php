<?php

namespace Drupal\omnipay_sagepay\Plugin\Payment\MethodConfiguration;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the configuration for the SagePay Form payment method plugin.
 *
 * @PaymentMethodConfiguration(
 *   description = @Translation("SagePay Form payment method type."),
 *   id = "omnipay_sagepay_form",
 *   label = @Translation("SagePay Form (Omnipay)")
 * )
 */
class SagePayForm extends SagePayBasic {

  /**
   * Implements a form API #process callback.
   */
  public function processBuildConfigurationForm(array &$element, FormStateInterface $form_state, array &$form) {
    parent::processBuildConfigurationForm($element, $form_state, $form);

    $element['sagepay']['encryptionKey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Encryption Key'),
      '#default_value' => $this->getEncryptionKey(),
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

    $parents = $form['plugin_form']['sagepay']['#parents'];
    array_pop($parents);
    $values = $form_state->getValues();
    $values = NestedArray::getValue($values, $parents);
    $this->setEncryptionKey($values['sagepay']['encryptionKey']);
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeConfiguration() {
    return parent::getDerivativeConfiguration() + [
      'encryptionKey' => $this->getEncryptionKey(),
    ];
  }

  /**
   * Gets the encryption key of this configuration.
   *
   * @return string
   *   Configured encryption key.
   */
  public function getEncryptionKey() {
    return isset($this->configuration['encryptionKey']) ? $this->configuration['encryptionKey'] : '';
  }

  /**
   * Sets the encryption key of this configuration.
   *
   * @param string $encryptionKey
   *   New Encryption key.
   */
  public function setEncryptionKey($encryptionKey) {
    $this->configuration['encryptionKey'] = $encryptionKey;
  }

}
