<?php

namespace Drupal\omnipay\Plugin\Payment\MethodConfiguration;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\payment\Plugin\Payment\MethodConfiguration\Basic;

/**
 * Abstract class for Omnipay payment method configurations.
 */
abstract class OmnipayBasic extends Basic {

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
   * Gets the production of this configuration.
   *
   * @return string
   *   Configured production.
   */
  public function getProduction() {
    return isset($this->configuration['production']) ? $this->configuration['production'] : '';
  }

  /**
   * Sets the production of this configuration.
   *
   * @param string $production
   *   New Production value.
   *
   * @return \Drupal\omnipay\Plugin\Payment\MethodConfiguration\OmniPayBasic
   *   Fluent interface.
   */
  public function setProduction($production) {
    $this->configuration['production'] = $production;
    return $this;
  }

  /**
   * Implements a form API #process callback.
   */
  public function processBuildConfigurationForm(array &$element, FormStateInterface $form_state, array &$form) {
    parent::processBuildConfigurationForm($element, $form_state, $form);

    $element['omnipay'] = [
      '#type' => 'container',
    ];
    $element['omnipay']['production'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Production payment services'),
      '#default_value' => $this->isProduction(),
      '#description' =>
      $this->t('Check this to use production payment services, otherwise test payment services will be used.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $parents = $form['plugin_form']['omnipay']['#parents'];
    array_pop($parents);
    $values = $form_state->getValues();
    $values = NestedArray::getValue($values, $parents);
    $this->configuration['production'] = !empty($values['omnipay']['production']);
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeConfiguration() {
    return [
      'production' => $this->isProduction(),
    ];
  }

}
