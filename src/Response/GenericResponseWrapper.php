<?php

namespace Drupal\omnipay\Response;

use Drupal\payment\Response\ResponseInterface;

/**
 * Class GenericResponseWrapper.
 *
 * Shim object to wrap other response objects.
 *
 * @package Drupal\omnipay\Response
 */
class GenericResponseWrapper implements ResponseInterface {

  /**
   * Current response object.
   *
   * @var \Omnipay\Common\Message\ResponseInterface|\Drupal\payment\Response\ResponseInterface
   */
  protected $response = NULL;

  /**
   * GenericResponseWrapper constructor.
   *
   * @param $response
   *   Response object.
   */
  public function __construct($response) {
    $this->response = $response;
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectUrl() {
    if (method_exists($this->response, 'getRedirectUrl')) {
      return Url::fromUri($this->response->getRedirectUrl());
    }
    return NULL;
  }

  /**
   * Return the current response object.
   *
   * @return null|\Omnipay\Common\Message\ResponseInterface|\Drupal\payment\Response\ResponseInterface
   *   Current Response object.
   */
  public function getResponse() {
    return $this->response;
  }

}
