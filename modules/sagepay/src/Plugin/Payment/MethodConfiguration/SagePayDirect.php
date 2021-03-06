<?php

namespace Drupal\omnipay_sagepay\Plugin\Payment\MethodConfiguration;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the configuration for the SagePay Direct payment method plugin.
 *
 * @PaymentMethodConfiguration(
 *   description = @Translation("SagePay Direct payment method type."),
 *   id = "omnipay_sagepay_direct",
 *   label = @Translation("SagePay Direct (Omnipay)")
 * )
 */
class SagePayDirect extends SagePayBasic {

  /**
   * Implements a form API #process callback.
   */
  public function processBuildConfigurationForm(array &$element, FormStateInterface $form_state, array &$form) {
    parent::processBuildConfigurationForm($element, $form_state, $form);

    $element['sagepay']['referrerId'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Referrer Id'),
      '#description' => $this->t('Also known as Partner Id'),
      '#default_value' => $this->getReferrerId(),
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
    $this->setReferrerId($values['sagepay']['referrerId']);
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeConfiguration() {
    return parent::getDerivativeConfiguration() + [
      'referrerId' => $this->getReferrerId(),
    ];
  }

  /**
   * Gets the referrer_id of this configuration.
   *
   * @return string
   *   Configured Referrer Id.
   */
  public function getReferrerId() {
    return isset($this->configuration['referrerId']) ? $this->configuration['referrerId'] : '';
  }

  /**
   * Sets the referrer_id of this configuration.
   *
   * @param string $referrerId
   *   New Referrer Id.
   */
  public function setReferrerId($referrerId) {
    $this->configuration['referrerId'] = $referrerId;
  }

}
