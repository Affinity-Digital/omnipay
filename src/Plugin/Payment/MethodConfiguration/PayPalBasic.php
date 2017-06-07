<?php

namespace Drupal\omnipay\Plugin\Payment\MethodConfiguration;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\payment\Plugin\Payment\MethodConfiguration\Basic;
use Drupal\omnipay\Plugin\Payment\Method\PayPalBasic as PayPalBasicMethod;

/**
 * Abstract class for PayPal payment method configurations.
 */
abstract class PayPalBasic extends Basic {

  /**
   * Gets the setting for the production server.
   *
   * @return bool
   *   Whether it is the production server or not.
   */
  public function isProduction() {
    return !empty($this->configuration['production']);
  }

  /**
   * Gets the setting for logging the PayPal API traffic.
   *
   * @param string $type
   *   Logging type.
   *
   * @return bool
   *   Whether logging level is enabled or not.
   */
  public function isLogging($type) {
    return !empty($this->configuration['logging'][$type]);
  }

  /**
   * Gets the setting for the log level.
   *
   * @return string
   *   Default logging level.
   */
  public function getLogLevel() {
    return isset($this->configuration['loglevel']) ? $this->configuration['loglevel'] : 'DEBUG';
  }

  /**
   * Implements a form API #process callback.
   */
  public function processBuildConfigurationForm(array &$element, FormStateInterface $form_state, array &$form) {
    parent::processBuildConfigurationForm($element, $form_state, $form);

    $element['paypal'] = [
      '#type' => 'container',
    ];
    $element['paypal']['production'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Production Server'),
      '#default_value' => $this->isProduction(),
    ];
    $element['paypal']['logging'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Logging'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $element['paypal']['logging']['loglevel'] = [
      '#type' => 'select',
      '#title' => $this->t('Log Level'),
      '#options' => [
        'DEBUG' => $this->t('Debugging'),
      ],
      '#default_value' => $this->getLogLevel(),
    ];
    $element['paypal']['logging'][PayPalBasicMethod::PAYPAL_CONTEXT_TYPE_ADMIN] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Logging admin tasks'),
      '#default_value' => $this->isLogging(PayPalBasicMethod::PAYPAL_CONTEXT_TYPE_ADMIN),
    ];
    $element['paypal']['logging'][PayPalBasicMethod::PAYPAL_CONTEXT_TYPE_CREATE] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Logging when creating payment'),
      '#default_value' => $this->isLogging(PayPalBasicMethod::PAYPAL_CONTEXT_TYPE_CREATE),
    ];
    $element['paypal']['logging'][PayPalBasicMethod::PAYPAL_CONTEXT_TYPE_WEBHOOK] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Logging the webhooks'),
      '#default_value' => $this->isLogging(PayPalBasicMethod::PAYPAL_CONTEXT_TYPE_WEBHOOK),
    ];
    $element['paypal']['logging'][PayPalBasicMethod::PAYPAL_CONTEXT_TYPE_REDIRECT] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Logging the redirects back from PayPal'),
      '#default_value' => $this->isLogging(PayPalBasicMethod::PAYPAL_CONTEXT_TYPE_REDIRECT),
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
    $this->configuration['production'] = !empty($values['paypal']['production']);
    $this->configuration['loglevel'] = $values['paypal']['logging']['loglevel'];
    $this->configuration['logging'][PayPalBasicMethod::PAYPAL_CONTEXT_TYPE_ADMIN] = !empty($values['paypal']['logging'][PayPalBasicMethod::PAYPAL_CONTEXT_TYPE_ADMIN]);
    $this->configuration['logging'][PayPalBasicMethod::PAYPAL_CONTEXT_TYPE_CREATE] = !empty($values['paypal']['logging'][PayPalBasicMethod::PAYPAL_CONTEXT_TYPE_CREATE]);
    $this->configuration['logging'][PayPalBasicMethod::PAYPAL_CONTEXT_TYPE_WEBHOOK] = !empty($values['paypal']['logging'][PayPalBasicMethod::PAYPAL_CONTEXT_TYPE_WEBHOOK]);
    $this->configuration['logging'][PayPalBasicMethod::PAYPAL_CONTEXT_TYPE_REDIRECT] = !empty($values['paypal']['logging'][PayPalBasicMethod::PAYPAL_CONTEXT_TYPE_REDIRECT]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeConfiguration() {
    return [
      'production' => $this->isProduction(),
      'loglevel' => $this->getLogLevel(),
      'logging' => [
        PayPalBasicMethod::PAYPAL_CONTEXT_TYPE_ADMIN => $this->isLogging(PayPalBasicMethod::PAYPAL_CONTEXT_TYPE_ADMIN),
        PayPalBasicMethod::PAYPAL_CONTEXT_TYPE_CREATE => $this->isLogging(PayPalBasicMethod::PAYPAL_CONTEXT_TYPE_CREATE),
        PayPalBasicMethod::PAYPAL_CONTEXT_TYPE_WEBHOOK => $this->isLogging(PayPalBasicMethod::PAYPAL_CONTEXT_TYPE_WEBHOOK),
        PayPalBasicMethod::PAYPAL_CONTEXT_TYPE_REDIRECT => $this->isLogging(PayPalBasicMethod::PAYPAL_CONTEXT_TYPE_REDIRECT),
      ],
    ];
  }

}
