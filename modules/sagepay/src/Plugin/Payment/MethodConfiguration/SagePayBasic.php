<?php

namespace Drupal\omnipay_sagepay\Plugin\Payment\MethodConfiguration;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\omnipay\Plugin\Payment\MethodConfiguration\OmnipayBasic;

/**
 * Abstract class for SagePay payment method configurations.
 */
class SagePayBasic extends OmnipayBasic {

  /**
   * Implements a form API #process callback.
   */
  public function processBuildConfigurationForm(array &$element, FormStateInterface $form_state, array &$form) {
    parent::processBuildConfigurationForm($element, $form_state, $form);

    $element['sagepay'] = [
      '#type' => 'container',
    ];

    $element['sagepay']['vendor'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Vendor name'),
      '#default_value' => $this->getVendorName(),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];

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
    $this->setVendorName($values['sagepay']['vendor']);
    $this->setReferrerId($values['sagepay']['referrerId']);
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeConfiguration() {
    return parent::getDerivativeConfiguration() + [
      'vendor' => $this->getVendorName(),
      'referrerId' => $this->getReferrerId(),
    ];
  }

  /**
   * Gets the vendor_name of this configuration.
   *
   * @return string
   *   Configured Vendor name.
   */
  public function getVendorName() {
    return isset($this->configuration['vendor']) ? $this->configuration['vendor'] : '';
  }

  /**
   * Sets the vendor_name of this configuration.
   *
   * @param string $vendorName
   *   New Vendor Name.
   *
   * @return \Drupal\omnipay\Plugin\Payment\MethodConfiguration\SagePayBasic
   *   Fluent interface.
   */
  public function setVendorName($vendorName) {
    $this->configuration['vendor'] = $vendorName;
    return $this;
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
   *
   * @return \Drupal\omnipay\Plugin\Payment\MethodConfiguration\SagePayBasic
   *   Fluent interface.
   */
  public function setReferrerId($referrerId) {
    $this->configuration['referrerId'] = $referrerId;
    return $this;
  }

}
