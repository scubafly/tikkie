General
=======

A minimal API to integrate Tikkie with Drupal.

This module:
- Uses the PHP Tikkie library of Jarno van Leeuwen
- Handles Drupal configuration for communication with Tikkie API.
- Handles and logs errors.
- Provides a Tikkie status page.

Installation
============

Install as usual.

Tikkie configuration
====================

No configuration interface available (yet).

Your Tikkie configuration is probably different for different environments.
Set/override Tikkie configuration in the appropriate settings.php:

@code
  $config['tikkie']['consumer_key'] = '';
  $config['tikkie']['consumer_secret'] = '';
  $config['tikkie']['private_key_path'] = '../path/to/private_rsa.pem';
@endcode

ABN Amro developer portal
=========================

You need to create an App at the ABN Amro Developer Portal:
https://developer.abnamro.com/ Create an app at
https://developer.abnamro.com/user/me/apps and enter the 'Consumer Key' and
'Consumer Secret' in your configuration.

Public/private key pair
=======================

Public/private key pair is used for OAuth identification of the client (your
Drupal site). Generate a key pair for each environment that must be identified
separately (e.g. dev and production).

Make sure you place the private key file outside of the webroot. The path
is set in the 'private_key_path' configuration.

For more details, and about issuing the public key to Tikkie, see
https://developer.abnamro.com/api/tikkie-v1/overview under "1. Create
public/private key pair".

@code
  #generates RSA private key of 2048 bit size
  openssl genrsa -out private_rsa.pem 2048

  #generates public key from the private key
  openssl rsa -in private_rsa.pem -outform PEM -pubout -out public_rsa.pem
@endcode

Tikkie Terminology
==================

See https://developer.abnamro.com/api/tikkie-v1/functional-details

Platform
  A group of Users. Usually the person or the organisation.

User
  The person or organisation that issues payment requests. Usually the
  beneficiary of the payment, the one that receives the money. A User has one or
  multiple bank accounts.

Payment Request
  A request for payment sent to the Tikkie API on behalf of the
  Platform:User:BankAccount. The API responds with a Payment Request URL. At
  this URL the visitor/purchaser can perform the requested payment.

Payment
  The payment performed by the visitor/purchaser. A single Payment Request (URL)
  may result in one or multiple payments.


Developer information
=====================

https://developer.abnamro.com/api/tikkie-v1/technical-details
https://github.com/jarnovanleeuwen/php-tikkie
