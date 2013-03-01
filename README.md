# Omnipayable Module

## Overview

The Omnipayable module is designed to provide simple payment forms for [SilverStripe](http://silverstripe.org/)
using [Omnipay](https://github.com/adrianmacneil/omnipay/).

The following forms are currently implemented:

 * GoCardless
 * PayPal Express Checkout
 * PayPal Payments Pro

## Requirements

- SilverStripe 3.0 or newer.
- Omnipay 0.8 or newer.

## Installation Instructions

### Composer

The Omnipayable module can be installed via [Composer](http://getcomposer.org/).
To do so, simply add it to the `composer.json` file in the root of your SilverStripe installation:

```json
{
    "require": {
        "studiobonito/silverstripe-omnipayable": "0.1.*"
    }
}
```

Then run composer to update your dependencies:

    $ curl -s http://getcomposer.org/installer | php
    $ php composer.phar update

### Manual

Copy the 'omnipayable' folder to the root of your SilverStripe installation.

## Usage Overview

There is a factory method provided to with form creation.
This will create the correct form for the currently configured gateway:

```php
$form = OmnipayableForm::create($this, 'PaymentForm');
```

Alternatively you can create the form directly:

```php
$form = new OmnipayableForm_GoCardless($this, 'PaymentForm');
```

Once the form is created you can set the amount that will be charged:

```php
$form->setAmount('10.00');
```

### Complete Example

```php
class Page_Controller extends ContentController
{
    public static $allowed_actions = array(
        'PaymentForm'
    );

    public function PaymentForm()
    {
        $form = OmnipayableForm::create($this, 'PaymentForm');

        $form->setAmount('10.00');

        return $form;
    }
}
```

When adding the form to a `Controller` remember to add the forms name to the `$allowed_actions` array.

### Payment Form Fields

The base form provides form fields for all of the currently supported properties of the Omnipay `CreditCard` object.
Each gateway specific form is intended to show only the relevant subset of fields that apply to that gateway.

The following is a list of all the base form fields and their type:

#### Personal Details `FieldGroup`
* FirstName `TextField`
* LastName `TextField`
* Company `TextField`
* Email `EmailField`

#### Card Details `FieldGroup`
* Number `CreditCardField`
* Cvv `TextField`
* ExpiryMonth `DropdownField`
* ExpiryYear `DropdownField`
* StartMonth `DropdownField`
* StartYear `DropdownField`
* IssueNumber `TextField`
* Type `DropdownField`

#### Billing Address `FieldGroup`
* BillingAddress1 `TextField`
* BillingAddress2 `TextField`
* BillingCity `TextField`
* BillingPostcode `TextField`
* BillingState `TextField`
* BillingCountry `DropdownField`
* BillingPhone `PhoneField`

#### Shipping Address `FieldGroup`
* ShippingAddress1 `TextField`
* ShippingAddress2 `TextField`
* ShippingCity `TextField`
* ShippingPostcode `TextField`
* ShippingState `TextField`
* ShippingCountry `DropdownField`
* ShippingPhone `PhoneField`