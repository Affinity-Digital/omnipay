<?php

namespace Drupal\omnipay\Response;

use Drupal\payment\Response\ResponseInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
   * @return null|\Symfony\Component\HttpFoundation\Response
   *   Current Response object.
   */
  public function getResponse() {
    if ($url = $this->getRedirectUrl()) {
      $response = new RedirectResponse($url->toString());
    }
    else {
      $response = new HttpResponse();
    }

    return $response;
  }

}
