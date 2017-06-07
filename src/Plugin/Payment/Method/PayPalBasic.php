<?php

namespace Drupal\omnipay\Plugin\Payment\Method;

use Drupal\Core\Url;
use Drupal\payment\OperationResult;
use Drupal\payment\Response\Response;
use Omnipay\PayPal\PayPalItem as Item;
use Omnipay\PayPal\PayPalBag as ItemList;

/**
 * Abstract class for PayPal payment methods.
 */
abstract class PayPalBasic extends GatewayFactoryAbstractPaymentMethodBase {

  const PAYPAL_CONTEXT_TYPE_ADMIN    = 'admin';
  const PAYPAL_CONTEXT_TYPE_CREATE   = 'create';
  const PAYPAL_CONTEXT_TYPE_WEBHOOK  = 'webhook';
  const PAYPAL_CONTEXT_TYPE_REDIRECT = 'redirect';

  const PAYPAL_DEFAULT_CURRENCY = 'USD';

  /**
   * Return the local API URL.
   *
   * @return string
   *   Local API URL.
   */
  abstract public function getWebhookUrl();

  /**
   * Return the webhook identifer value.
   *
   * @return string
   *   Webhook identifer value.
   */
  abstract public function getWebhookId();

  /**
   * Get the current API Context.
   *
   * @param string $type
   *   Pal Pay context type.
   *
   * @return \PayPal\Rest\ApiContext
   *   Current API Context.
   */
  abstract public function getApiContext($type);

  /**
   * Set Payment Id.
   *
   * @param string $paymentId
   *   New Payment Id.
   */
  private function setPaymentId($paymentId) {
    $this->configuration['paymentID'] = $paymentId;
    $this->getPayment()->save();
  }

  /**
   * Return the Payment Id from configuration.
   *
   * @return null|mixed
   *   Payment Id if present.
   */
  public function getPaymentId() {
    return isset($this->configuration['paymentID']) ? $this->configuration['paymentID'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentExecutionResult() {
    $payer = new Payer();
    $payer->setPaymentMethod('paypal');

    $itemList = new ItemList();
    $totalAmount = 0;
    $currency = self::PAYPAL_DEFAULT_CURRENCY;
    foreach ($this->getPayment()->getLineItems() as $line_item) {
      $totalAmount += $line_item->getTotalAmount();
      $line_item_currency = $line_item->getCurrencyCode();

      $item = new Item();
      $item->setName($line_item->getName())
        ->setCurrency($line_item_currency)
        ->setQuantity($line_item->getQuantity())
        ->setPrice($line_item->getTotalAmount());
      $itemList->addItem($item);

      if ($line_item_currency != $currency) {
        if ($currency != self::PAYPAL_DEFAULT_CURRENCY) {
          // This is the second time we are changing the currency which means
          // that our line items have mixed currencies. This aion't gonna work!
          // TODO: clarify with the payment maintainer how we should handle this.
          drupal_set_message($this->t('Mixed currencies detected which is not yet supported.'), 'error');
          return new OperationResult(NULL);
        }
        $currency = $line_item_currency;
      }
    }

    $redirectSuccess = new Url('omnipay.paypal.redirect.success',
      ['payment' => $this->getPayment()->id()], ['absolute' => TRUE]);
    $redirectCancel = new Url('omnipay.paypal.redirect.cancel',
      ['payment' => $this->getPayment()->id()], ['absolute' => TRUE]);

    $redirectUrls = new RedirectUrls();
    $redirectUrls->setReturnUrl($redirectSuccess->toString(TRUE)->getGeneratedUrl())
      ->setCancelUrl($redirectCancel->toString(TRUE)->getGeneratedUrl());

    $amount = new Amount();
    $amount->setCurrency($currency)
      ->setTotal($totalAmount);

    $transaction = new Transaction();
    $transaction->setAmount($amount)
      ->setItemList($itemList)
      ->setDescription($this->getPayment()->id())
      ->setInvoiceNumber($this->getPayment()->id())
      ->setNotifyUrl($this->getWebhookUrl());

    $payment = new Payment();
    $payment->setIntent('sale')
      ->setPayer($payer)
      ->setRedirectUrls($redirectUrls)
      ->setTransactions([$transaction]);

    try {
      $payment->create($this->getApiContext(self::PAYPAL_CONTEXT_TYPE_CREATE));
      $this->setPaymentId($payment->getId());
      $url = Url::fromUri($payment->getApprovalLink());
      $response = new Response($url);
    }
    catch (\Exception $ex) {
      // TODO: clarify with the payment maintainer how we should handle Exceptions.
      $response = NULL;
    }

    return new OperationResult($response);
  }

}
