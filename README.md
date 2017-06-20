# Omnipay Common

**Core components for the Omnipay PHP payment processing library**

This module provides Payment Methods using the [Omnipay](https://omnipay.thephpleague.com/)
PHP library

Each of the payment providers are in their own module. The different supported
payment methods by that provider are in the module.

# Omnipay Library Version
Currently this uses the latest version of the 2.x library.

# Naming conventions

## module namespace 
Drupal\\omnipay__provider_\\...

for example

<code>
namespace Drupal\omnipay_paypal\Plugin\Payment\Method;

namespace Drupal\omnipay_sagepay\Plugin\Payment\Method;
</code>

## routing namespace path
/omnipay/_provider_/...

for example

<code>
  path: '/omnipay/paypal/redirect/success/{payment}'

  path: '/omnipay/sagepay/notify'
</code>

## payment id namespace
omnipay__provider___method_

for example

<code>
  id = "omnipay\_paypal\_rest",

  id = "omnipay\_sagepay\_server",
</code>

# Generic Payment Method Configuration
There is a checkbox to indicate whether the system is to set the payment gateway
into test mode or not. Please check the box if it is to use the production
servers of the provider.

# Module Developers Notes
I suggest that the following class are extend in the payment method modules

## Payment Method
Drupal\omnipay\Plugin\Payment\Method\GatewayFactoryAbstractPaymentMethodBase

## Payment Method Deriver
Drupal\omnipay\Plugin\Payment\Method\OmnipayDriver

## Payment Method Operations
Drupal\omnipay\Plugin\Payment\Method\OmnipayOperationsProvider

## Payment Method Configuration
Drupal\omnipay\Plugin\Payment\MethodConfiguration\OmnipayBasic

## composer.json
Add the specific omnipay package to this module's composer.json

# Acknowledgements
* The [Omnipay](https://omnipay.thephpleague.com/) library developers.
* The [Omnipay](https://www.drupal.org/project/omnipay) Drupal module developers.
* The [Payment](https://www.drupal.org/project/payment) Drupal module developers.

