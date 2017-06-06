<?php

namespace Drupal\omnipay\Tests\Plugin\Payment\Method;

use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\omnipay\Plugin\Payment\Method\PaymentMethodBase
 *
 * @group Omnipay
 */
class PaymentMethodBaseUnitTest extends UnitTestCase {

  /**
   * The gateway under test.
   *
   * @var \Omnipay\Common\GatewayInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $gateway;

  /**
   * The payment method under test.
   *
   * @var \Drupal\omnipay\Plugin\Payment\Method\PaymentMethodBase
   */
  protected $paymentMethod;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->gateway = $this->getMock('\Omnipay\Common\GatewayInterface');

    $this->paymentMethod = $this->getMockBuilder('\Drupal\omnipay\Plugin\Payment\Method\PaymentMethodBase')
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();
    $property = new \ReflectionProperty($this->paymentMethod, 'gateway');
    $property->setAccessible(TRUE);
    $property->setValue($this->paymentMethod, $this->gateway);
  }

  /**
   * @covers ::defaultConfiguration
   */
  public function testDefaultConfiguration() {
    $configuration = [
      'foo' => $this->randomName(),
    ];
    $this->gateway->expects($this->once())
      ->method('getDefaultParameters')
      ->will($this->returnValue($configuration));

    $this->assertSame($configuration, $this->paymentMethod->defaultConfiguration());
  }

}
