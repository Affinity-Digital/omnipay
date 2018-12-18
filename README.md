# Omnipay Common

**Core components for the Omnipay PHP payment processing library**

This module provides Payment Methods using the [Omnipay](https://omnipay.thephpleague.com/)
PHP library

Each of the payment providers are in their own module. The different supported
payment methods by that provider are in the module.

The [Drupal module omnipay](https://www.drupal.org/project/omnipay) has not had
any development since [16 Jul 2014 at 19:28 UTC](https://cgit.drupalcode.org/omnipay/commit/src/Plugin/Payment/Method/AbstractPaymentMethodBase.php?id=35979787741d04e96baa8753ef8776c87a241df8).

# Omnipay Library Version
Currently this uses the latest version of the developing 3.x library.

# Naming conventions

## module namespace 
Drupal\\omnipay\__provider_\\...

for example

```php
namespace Drupal\omnipay_paypal\Plugin\Payment\Method;

namespace Drupal\omnipay_sagepay\Plugin\Payment\Method;
```

## routing path namespace
/omnipay/_provider_/...

for example

```yaml
  path: '/omnipay/paypal/redirect/success/{payment}'

  path: '/omnipay/sagepay/notify'
```

## payment method plugin id namespace
omnipay\__provider_\__method_

for example

```php
  id = "omnipay_paypal_rest",

  id = "omnipay_sagepay_server",
```

## payment method configuration plugin id namespace
omnipay\__provider_\__method_

for example

```php
  id = "omnipay_paypal_rest",

  id = "omnipay_sagepay_server",
```

# Generic Payment Method Configuration
There is a checkbox to indicate whether the system is to set the payment gateway
into test mode or not. Please check the box if it is to use the production
servers of the provider.

# Module Developers Notes
I suggest that the following class are extend in the payment method modules

## Payment Method
Drupal\omnipay\Plugin\Payment\Method\GatewayFactoryAbstractPaymentMethodBase

### Card details
If card details are necessary for this payment method then these are added in
getConfiguration() method and needCard() is overridden and returns TRUE. This
allows the web site to determine if the card details are needed for this payment
method. The card details can be set by using the setCard() method and passing an
associative array of the minimum fields.

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
