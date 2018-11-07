# Sage Pay Provider
The Omnipay library provides three payment gateways
* Direct
* Server
* Form

of which both are implemented. Server is an extension of Direct as it has
notification feedback from Sage Pay.

# Method Configuration
## Common
Specially for these payment methods, the following is required
* Vendor name

## Direct and Server
Specially for these payment methods, the following is also required
* Referrer ID

## Form
Specially for this payment methods, the following is also required
* Encryption Key

# Extra functions 

## Form
### Mandatory
PaymentTypeBase::getReturnUrl()

returns \Drupal\Core\Url

### Optional
PaymentTypeBase::getFailureUrl()

returns \Drupal\Core\Url

# Acknowledgements
* The [Omnipay](https://omnipay.thephpleague.com/) developers.
* The [Sagepay Payment](https://www.drupal.org/project/sagepay_payment) Drupal
module developers
