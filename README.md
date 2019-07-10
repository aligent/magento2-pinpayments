# PinPayments for Magento 2

## Overview

This module adds a payment method to Magento 2 checkout using the PinPayments hosted fields service.

## Installation

Installation via Composer:

```
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/aligent/magento2-pinpayments"
        }
    ],
    "require": {
        "aligent/pinpay": "dev-master"
    }
}
```

## Payment Flow

The hosted-field solution embeds an iframe within the M2 checkout and avoids the need to have credit card details ever pass through the application server.

* User hits `Place Order`
* The iframe billing address is configured using the selected billing address
* The hosted iframe window is messaged with a set-token request to generate a card token
* The card token is messaged back to the parent window (Magento checkout)
* The card token is attached to the order payload
* The server sends a request using the generated card token to authorize and/or capture funds.

## Issues

Payment reviews are not currently supported, a suspected_fraud response will be treated the same as any other error, and the order will not be created.

## Legal

(c) 2016-2019 Aligent Consulting

Licensed under Open Source License (OSL) v3.0
