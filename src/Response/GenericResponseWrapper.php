<?php

namespace Drupal\omnipay\Response;

use Drupal\payment\Response\ResponseInterface;

class GenericResponseWrapper implements ResponseInterface {

  protected $response = NULL;

  public function __construct($response) {
    $this->response = $response;
  }

  /**
   * @inheritdoc
   */
  public function getRedirectUrl() {
    if (method_exists($this->response, 'getRedirectUrl')) {
      return Url::fromUri($this->response->getRedirectUrl());
    }
    return NULL;
  }

  public function getResponse() {
    return $this->response;
  }

}
