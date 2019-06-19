INTRODUCTION
------------

This module integrates https://iyzico.com with 
https://www.drupal.org/project/commerce providing a credit card payment 
gateway. You can add an On-Site credit card payment gateway to your Drupal
Commerce shop. Iyzipay is an Iyzico product to modernize credit card payments,
make the credit card payments mechanisms easy for the individuals and 
companies. Iyzipay supports amex, dinersclub, discover, jcb, maestro, 
mastercard, visa, troy and visa electron types. More information about the 
service can be found https://bit.ly/2KsA3Jk.


FEATURES
--------

* Encrypt credit card information completely using the 
https://www.drupal.org/project/encryption module.
* 3DSecure payments are supported out of the box. The system checks if the 
bank is forcing for 3Dsecure payment method and then automatically handles the
request. Otherwise it handles the process without the 3DSecure authentication.
* Turkish banks do not except foreign currency payments on Turkish cards, so 
the system checks if the card is Turkish first, if the product price currency
is foreign, than it converts the price to TRY automatically via 
https://exchangeratesapi.io API
* Sends all the cart data to Iyzico so later you can monitor your customer's
behavior via Iyzico dashboard
* A Credit Card front-end library added https://github.com/jessepollak/card
to make the look and feel better.


REQUIREMENTS
------------

* Drupal Commerce
* Encryption
* Iyzipay PHP library


INSTALLATION
------------

* This module depends on the Iyzipay-php library, if you are installing via
composer, the dependencies should install automatically. Otherwise you need to
install https://github.com/iyzico/iyzipay-php library under 
`/sites/all/libraries/iyzipay-php` and must install Libraries module
* The credit card data is encrypted by the 
https://www.drupal.org/project/encryption module. Therefore you need to instal
the module first and then generate an encryption key and finally put it on the
settings.php file. The details are explained on the encryption module project
page.


CONFIGURATION
-------------

* After the installation, go to `admin/commerce/config/payment-gateways` and
add a payment gateway. Choose Iyzipay On-Site plugin and add your credentials.
* That's it, now you should be able to see the credit card payment screen on
the payment page


MAINTAINERS
-----------

Current maintainers:
 * Bekir Dağ (bekirdag) - https://www.drupal.org/u/bekirdag


TESTING INSTRUCTIONS
--------------------

* Go to https://sandbox-api.iyzipay.com and create an account. Copy the
credentials generated via their dashboard and then put the information as
described above.
* You can see test credit cards on the https://github.com/iyzico/iyzipay-php.
Try a few credit cards to see if the gateway is working properly.

**This payment gateway requires cURL and OpenSSL.**
