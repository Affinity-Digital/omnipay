<?php

namespace Drupal\omnipay_paypal\Plugin\Payment\MethodConfiguration;

/**
 * Provides the configuration for the PayPal Express payment method plugin.
 *
 * @PaymentMethodConfiguration(
 *   description = @Translation("PayPal Express payment method type."),
 *   id = "omnipay_paypal_express",
 *   label = @Translation("PayPal Express (Omnipay)")
 * )
 */
class PayPalExpress extends PayPalStandard {

  /**
   * Gets the brand name of this configuration.
   *
   * @return string
   *   Configured Brand name address.
   */
  public function getBrandName() {
    return isset($this->configuration['brandName']) ? $this->configuration['brandName'] : '';
  }

  /**
   * Gets the header image URL of this configuration.
   *
   * @return string
   *   Configured header image URL.
   */
  public function getHeaderImageUrl() {
    return isset($this->configuration['headerImageUrl']) ? $this->configuration['headerImageUrl'] : '';
  }

  /**
   * Gets the logo image URL of this configuration.
   *
   * @return string
   *   Configured logo image URL.
   */
  public function getLogoImageUrl() {
    return isset($this->configuration['logoImageUrl']) ? $this->configuration['logoImageUrl'] : '';
  }

  /**
   * Gets the border color of this configuration.
   *
   * @return string
   *   Configured border color.
   */
  public function getBorderColor() {
    return isset($this->configuration['borderColor']) ? $this->configuration['borderColor'] : '';
  }

  /**
   * Gets the solution type of this configuration.
   *
   * @return array
   *   Configured border color.
   */
  public function getSolutionType() {
    // Currently hard code these until we have a suitable UI for them.
    return ['Sole', 'Mark'];
  }

    /**
   * Gets the landing page of this configuration.
   *
   *
   * @return array
   *   Configured border color.
   */
  public function getLandingPage() {
    // Currently hard code these until we have a suitable UI for them.
    return ['Billing', 'Login'];
  }

  /**
   * Implements a form API #process callback.
   */
  public function processBuildConfigurationForm(array &$element, FormStateInterface $form_state, array &$form) {
    parent::processBuildConfigurationForm($element, $form_state, $form);

    $element['paypal']['brandName'] = [
    '#type' => 'textfield',
    '#title' => $this->t('Brand name'),
    '#default_value' => $this->getBrandName(),
    '#maxlength' => 255,
    '#required' => TRUE,
    ];
    $element['paypal']['headerImageUrl'] = [
    '#type' => 'url',
    '#title' => $this->t('Header Image URL'),
    '#default_value' => $this->getHeaderImageUrl(),
    '#maxlength' => 127,
    '#description' => $this->t('URL for the image you want to appear at the top left of the payment page.')
      . '<br />'
      . $this->t('The image has a maximum size of 750 pixels wide by 90 pixels high.')
      . '<br />'
      . $this->t('PayPal recommends that you provide an image that is stored on a secure (HTTPS) server.')
      . '<br />'
      . $this->t('If you do not specify an image, the business name displays.')
      . '<br />'
      . $this->t('Character length and limitations: 127 single-byte alphanumeric character')
    ];
    $element['paypal']['logoImageUrl'] = [
    '#type' => 'url',
    '#title' => $this->t('Logo Image URL'),
    '#default_value' => $this->getLogoImageUrl(),
    '#maxlength' => 255,
    '#description' => $this->t('URL for the image to appear above the order summary, in place of the brand name.')
      . '<br />'
      . $this->t('The recommended size is 190 pixels wide and 60 pixels high.'),
    ];

    $element['paypal']['borderColor'] = [
    '#type' => 'textfield',
    '#title' => $this->t('Border Color'),
    '#default_value' => $this->getBorderColor(),
    '#maxlength' => 6,
    '#description' => $this->t('The color of the border gradient on payment pages.')
      . '<br />'
      . $this->t('Should be a six character hexadecimal code (i.e. C0C0C0).'),
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
    $this->configuration['brandName'] = $values['paypal']['brandName'];
    $this->configuration['headerImageUrl'] = $values['paypal']['headerImageUrl'];
    $this->configuration['logoImageUrl'] = $values['paypal']['logoImageUrl'];
    $this->configuration['borderColor'] = $values['paypal']['borderColor'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeConfiguration() {
    return parent::getDerivativeConfiguration() + [
      'brandName' => $this->getBrandName(),
      'headerImageUrl' => $this->getHeaderImageUrl(),
      'logoImageUrl' => $this->getLogoImageUrl(),
      'borderColor' => $this->getBorderColor(),
      'solutionType' => $this->getSolutionType(),
      'landingPage' => $this->getLandingPage(),
    ];
  }

}
