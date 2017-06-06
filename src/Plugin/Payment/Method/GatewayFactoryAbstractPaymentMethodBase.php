<?php

namespace Drupal\omnipay\Plugin\Payment\Method;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Utility\Token;
use GuzzleHttp\ClientInterface;
use Omnipay\Common\GatewayFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a basis for payment methods that use \Omnipay\Common\AbstractGateway gateways.
 *
 * That want to instantiate gateways through \Omnipay\Common\GatewayFactory.
 */
abstract class GatewayFactoryAbstractPaymentMethodBase extends AbstractPaymentMethodBase {

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Utility\Token $token
   *   The token API.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ModuleHandlerInterface $module_handler, EventDispatcherInterface $event_dispatcher, Token $token, ClientInterface $http_client, Request $request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $module_handler, $event_dispatcher, $token);
    $this->gateway = GatewayFactory::create($this->getGatewayName(), $http_client, $request);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('module_handler'), $container->get('event_dispatcher'), $container->get('token'), $container->get('http_client'), $container->get('request'));
  }

  /**
   * Returns the gateway name.
   *
   * @return string
   *   The Gateway name.
   */
  abstract protected function getGatewayName();

}
