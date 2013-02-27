<?php

use Omnipay\Common\GatewayFactory;

/**
 * OmnipayableForm.
 *
 * @author Tom Densham <tom.densham@studiobonito.co.uk>
 * @copyright (c) 2012, Studio Bonito Ltd.
 * @version 1.0
 */
abstract class OmnipayableForm extends Form
{
    protected $gateway;

    protected $amount;

    public function __construct($controller, $name = 'OmnipayableForm')
    {
        $fields = $this->getPaymentFields();
        $actions = $this->getPaymentActions();
        $validator = $this->getRequiredFields();

        parent::__construct($controller, $name, $fields, $actions, $validator);

        $gateway = Config::inst()->get('Omnipayable', 'Gateway');
        $gateways = Config::inst()->get('Omnipayable', 'Gateways');

        $gatewayConfig = $gateways[$gateway];

        $this->gateway = GatewayFactory::create($gateway);

        foreach ($gatewayConfig as $key => $value) {
            $method = "set$key";
            if (method_exists($this->gateway, $method)) {
                $this->gateway->$method($value);
            }
        }

        $this->disableSecurityToken();
    }

    public function setAmount($amount)
    {
        $this->amount = (int) preg_replace('/([0-9]*)\.([0-9]*)/', '$1$2', $amount);
    }

    public function doProcessPayment(array $data, Form $form)
    {
        try {
            $data = $this->processPaymentData($data);

            $data['Amount'] = $this->amount;

            $response = $this->gateway->purchase($data)->send();

            if ($response->isSuccessful()) {
                // Payment is complete
                $this->sessionMessage(_t('Omnipayable.SUCCESS', 'Payment successful!'), 'good');
                $this->extend('onPaymentSuccess');
            } elseif ($response->isRedirect()) {
                return $this->controller->redirect($response->getRedirectUrl()); // Redirect to offsite payment page
            } else {
                // Payment failed
                $this->sessionMessage(_t('Omnipayable.FAILURE', '{message}', array('message' => $response->getMessage())), 'bad');
                $this->extend('onPaymentFailure');
            }
        }
        catch (Exception $exception) {
            // Log any errors and present a user friendly message
            SS_Log::log($exception, SS_Log::ERR);
            $this->sessionMessage(_t('Omnipayable.ERROR', 'There was an error processing your payment. Please try agian later.'), 'bad');
        }

        return $this->controller->redirectBack();
    }

    public function doProcessPaymentRedirect(SS_HTTPRequest $request)
    {
        try {
            $data = $request->getVars();

            $data['Amount'] = $this->amount;

            $response = $this->gateway->completePurchase($data)->send();

            if ($response->isSuccessful()) {
                // Payment is complete
                $this->sessionMessage(_t('Omnipayable.SUCCESS', 'Payment successful!'), 'good');
                $this->extend('onPaymentSuccess');
            } else {
                // Payment failed
                $this->sessionMessage(_t('Omnipayable.FAILURE', '{message}', array('message' => $response->getMessage())), 'bad');
                $this->extend('onPaymentFailure');
            }
        }
        catch (Exception $exception) {
            // Log any errors and present a user friendly message
            SS_Log::log($exception, SS_Log::ERR);
            $this->sessionMessage(_t('Omnipayable.ERROR', 'There was an error processing your payment. Please try agian later.'), 'bad');
        }

        return $this->controller->redirectBack();
    }

    protected function getCreditCardTypes()
    {
        return array(
            'visa'               => 'Visa',
            'mastercard'         => 'Mastercard',
            'discover'           => 'Discover',
            'americanexpress'    => 'American Express',
            'diners_club'        => 'Diners Club',
            'jcb'                => 'JCB',
            'switch'             => 'Switch',
            'solo'               => 'Solo',
            'dankort'            => 'Dankort',
            'maestro'            => 'Maestro',
            'forbrugsforeningen' => 'Forbrugs Foriningen',
            'laser'              => 'Laser'
        );
    }

    protected function getMonths()
    {
        return array(
            '01' => _t('OmnipayableForm.JANUARY', 'Jan'),
            '02' => _t('OmnipayableForm.FEBRUARY', 'Feb'),
            '03' => _t('OmnipayableForm.MARCH', 'Mar'),
            '04' => _t('OmnipayableForm.APRIL', 'Apr'),
            '05' => _t('OmnipayableForm.MAY', 'May'),
            '06' => _t('OmnipayableForm.JUNE', 'Jun'),
            '07' => _t('OmnipayableForm.JULY', 'Jul'),
            '08' => _t('OmnipayableForm.AUGUST', 'Aug'),
            '09' => _t('OmnipayableForm.SEPTEMBER', 'Sep'),
            '10' => _t('OmnipayableForm.OCTOBER', 'Oct'),
            '11' => _t('OmnipayableForm.NOVEMBER', 'Nov'),
            '12' => _t('OmnipayableForm.DECEMBER', 'Dec'),
        );
    }

    protected function getYears($range = 20)
    {
        $years = array();
        $currentYear = date('Y');
        $endOfRangeYear = $currentYear + $range;

        if($currentYear < $endOfRangeYear) {
            $endOfRangeYear--;
            for ($year = $endOfRangeYear; $year >= $currentYear; $year--) {
                $years[$year] = $year;
            }
        } else {
            for ($year = $currentYear; $year > $endOfRangeYear; $year--) {
                $years[$year] = $year;
            }
        }

        return $years;
    }

    protected function getCreditCardFields()
    {
        $fields = new FieldList();

        $currentMonth = date('m');
        $currentYear = date('Y');

        // Create personal detail fields
        $firstNameTextField = new TextField('FirstName', _t('OmnipayableForm.FIRSTNAME', 'First name'));
        $lastNameTextField = new TextField('LastName', _t('OmnipayableForm.LASTNAME', 'Last name'));
        $companyTextField = new TextField('Company', _t('OmnipayableForm.COMPANY', 'Company'));
        $emailEmailField = new EmailField('Email', _t('OmnipayableForm.EMAIL', 'Email'));

        // Create personal details group
        $personalFieldGroup = new FieldGroup();
        $personalFieldGroup->setTitle(_t('OmnipayableForm.PERSONALDETAILS', 'Personal detials'));

        // Add basic fields to personal details group
        $personalFieldGroup->push($firstNameTextField);
        $personalFieldGroup->push($lastNameTextField);
        $personalFieldGroup->push($companyTextField);
        $personalFieldGroup->push($emailEmailField);

        // Add personal details group to fields
        $fields->push($personalFieldGroup);

        // Create credit card detail fields
        $numberCreditCardField = new CreditCardField('Number', _t('OmnipayableForm.NUMBER', 'Number'));
        $cvvTextField = new TextField('Cvv', _t('OmnipayableForm.CVV', 'CVV'));
        $expiryMonthTextField = new DropdownField('ExpiryMonth', _t('OmnipayableForm.EXPIRYMONTH', 'Expiry month'), $this->getMonths(), $currentMonth);
        $expiryYearTextField = new DropdownField('ExpiryYear', _t('OmnipayableForm.EXPIRYYEAR', 'Expiry year'), $this->getYears(20), $currentYear);
        $startMonthTextField = new DropdownField('StartMonth', _t('OmnipayableForm.STARTMONTH', 'Start month'), $this->getMonths(), $currentMonth);
        $startYearTextField = new DropdownField('StartYear', _t('OmnipayableForm.STARTYEAR', 'Start year'), $this->getYears(-20), $currentYear);
        $issueNumberTextField = new TextField('IssueNumber', _t('OmnipayableForm.ISSUENUMBER', 'Issue number'));
        $typeDropdownField = new DropdownField('Type', _t('OmnipayableForm.TYPE', 'Type'), $this->getCreditCardTypes());

        $expiryDateCompositeField = new CompositeField();
        $expiryDateCompositeField->push($expiryMonthTextField);
        $expiryDateCompositeField->push($expiryYearTextField);

        $startDateCompositeField = new CompositeField();
        $startDateCompositeField->push($startMonthTextField);
        $startDateCompositeField->push($startYearTextField);

        // Create credit card details group
        $creditCardFieldGroup = new FieldGroup();
        $creditCardFieldGroup->setTitle(_t('OmnipayableForm.CREDITCARDDETAILS', 'Credit card detials'));

        // Add credit card fields to credit card details group
        $creditCardFieldGroup->push($numberCreditCardField);
        $creditCardFieldGroup->push($cvvTextField);
        $creditCardFieldGroup->push($expiryDateCompositeField);
        $creditCardFieldGroup->push($startDateCompositeField);
        $creditCardFieldGroup->push($issueNumberTextField);
        $creditCardFieldGroup->push($typeDropdownField);

        // Add credit card details group to fields
        $fields->push($creditCardFieldGroup);

        // Create billing address fields
        $billingAddress1TextField = new TextField('BillingAddress1', _t('OmnipayableForm.BILLINGADDRESS1', 'Address 1'));
        $billingAddress2TextField = new TextField('BillingAddress2', _t('OmnipayableForm.BILLINGADDRESS2', 'Address 2'));
        $billingCity = new TextField('BillingCity', _t('OmnipayableForm.BILLINGCITY', 'City'));
        $billingPostcode = new TextField('BillingPostcode', _t('OmnipayableForm.BILLINGPOSTCODE', 'Postcode'));
        $billingState = new TextField('BillingState', _t('OmnipayableForm.BILLINGSTATE', 'State'));
        $billingCountry = new CountryDropdownField('BillingCountry', _t('OmnipayableForm.BILLINGCOUNTRY', 'Country'));
        $billingPhone = new PhoneNumberField('BillingPhone', _t('OmnipayableForm.BILLINGPHONE', 'Phone'));

        // Create billing details group
        $billingFieldGroup = new FieldGroup();
        $billingFieldGroup->setTitle(_t('OmnipayableForm.BILLING', 'Billing Address'));

        // Add billiing fields to billing group
        $billingFieldGroup->push($billingAddress1TextField);
        $billingFieldGroup->push($billingAddress2TextField);
        $billingFieldGroup->push($billingCity);
        $billingFieldGroup->push($billingPostcode);
        $billingFieldGroup->push($billingState);
        $billingFieldGroup->push($billingCountry);
        $billingFieldGroup->push($billingPhone);

        // Add billing details group to fields
        $fields->push($billingFieldGroup);

        // Create shipping address fields
        $shippingAddress1TextField = new TextField('ShippingAddress1', _t('OmnipayableForm.SHIPPINGADDRESS1', 'Address 1'));
        $shippingAddress2TextField = new TextField('ShippingAddress2', _t('OmnipayableForm.SHIPPINGADDRESS2', 'Address 2'));
        $shippingCity = new TextField('ShippingCity', _t('OmnipayableForm.SHIPPINGCITY', 'City'));
        $shippingPostcode = new TextField('ShippingPostcode', _t('OmnipayableForm.SHIPPINGPOSTCODE', 'Postcode'));
        $shippingState = new TextField('ShippingState', _t('OmnipayableForm.SHIPPINGSTATE', 'State'));
        $shippingCountry = new CountryDropdownField('ShippingCountry', _t('OmnipayableForm.SHIPPINGCOUNTRY', 'Country'));
        $shippingPhone = new PhoneNumberField('ShippingPhone', _t('OmnipayableForm.SHIPPINGPHONE', 'Phone'));

        // Create shipping details group
        $shippingFieldGroup = new FieldGroup();
        $shippingFieldGroup->setTitle(_t('OmnipayableForm.SHIPPING', 'Shipping Address'));

        // Add billiing fields to shipping group
        $shippingFieldGroup->push($shippingAddress1TextField);
        $shippingFieldGroup->push($shippingAddress2TextField);
        $shippingFieldGroup->push($shippingCity);
        $shippingFieldGroup->push($shippingPostcode);
        $shippingFieldGroup->push($shippingState);
        $shippingFieldGroup->push($shippingCountry);
        $shippingFieldGroup->push($shippingPhone);

        // Add shipping details group to fields
        $fields->push($shippingFieldGroup);

        return $fields;
    }

    protected function getPaymentFields()
    {
        $fields = new FieldList();

        $fields->merge($this->getCreditCardFields());

        // Allow easy customisatin of the payment fields
        $this->extend('updatePaymentFields', $fields);

        return $fields;
    }

    protected function getPaymentActions()
    {
        $actions = new FieldList();

        $this->extend('updatePaymentActions', $actions);

        $actions->push(new FormAction('doProcessPayment', _t('Omnipayable.PAY', 'Pay')));

        return $actions;
    }

    protected function getRequiredFields()
    {
        $required = array();

        $this->extend('updateRequiredFields', $required);

        return new RequiredFields($required);
    }

    protected function processPaymentData($data)
    {
        $creditCardData = array();
        $creditCardFields = $this->getCreditCardFields()->dataFields();

        foreach ($creditCardFields as $fieldName => $field) {
            $field->setValue($data[$fieldName]);
            $creditCardData[$fieldName] = $field->dataValue();
            unset($data[$fieldName]);
        }

        $data['Card'] = $creditCardData;

        return $data;
    }
}

/**
 * OmnipayableForm_GoCardless.
 *
 * @author Tom Densham <tom.densham@studiobonito.co.uk>
 * @copyright (c) 2012, Studio Bonito Ltd.
 * @version 1.0
 */
class OmnipayableForm_GoCardless extends OmnipayableForm
{
    protected function processPaymentData($data)
    {
        $data = parent::processPaymentData($data);

        $returnUrl = Controller::join_links(
            Director::absoluteBaseURL(),
            $this->controller->Link(),
            'PaymentForm', 'doProcessPaymentRedirect',
            "?BackURL={$this->request->getHeader('Referer')}"
        );

        $data['returnUrl'] = $returnUrl;

        return $data;
    }
}

/**
 * OmnipayableForm_PayPal_Express.
 *
 * @author Tom Densham <tom.densham@studiobonito.co.uk>
 * @copyright (c) 2012, Studio Bonito Ltd.
 * @version 1.0
 */
class OmnipayableForm_PayPal_Express extends OmnipayableForm
{
    protected function processPaymentData($data)
    {
        $data = parent::processPaymentData($data);

        $returnUrl = Controller::join_links(
            Director::absoluteBaseURL(),
            $this->controller->Link(),
            'PaymentForm', 'doProcessPaymentRedirect',
            "?BackURL={$this->request->getHeader('Referer')}"
        );

        $data['returnUrl'] = $returnUrl;

        $data['cancelUrl'] = $returnUrl;

        return $data;
    }
}

/**
 * OmnipayableForm_PayPal_Pro.
 *
 * @author Tom Densham <tom.densham@studiobonito.co.uk>
 * @copyright (c) 2012, Studio Bonito Ltd.
 * @version 1.0
 */
class OmnipayableForm_PayPal_Pro extends OmnipayableForm
{
    protected function processPaymentData($data)
    {
        $data = parent::processPaymentData($data);

        return $data;
    }

    protected function getCreditCardTypes()
    {
        return array(
            'visa'               => 'Visa',
            'mastercard'         => 'Mastercard',
            'discover'           => 'Discover',
            'americanexpress'    => 'American Express',
            'maestro'            => 'Maestro'
        );
    }

    protected function getPaymentFields()
    {
        $fields = parent::getPaymentFields();

        return $fields;
    }

    protected function getRequiredFields()
    {
        $require = parent::getRequiredFields();

        $extraRequiredFields = new RequiredFields(array(
            'FirstName',
            'LastName',
            'Number',
            'ExpiryMonth',
            'ExpiryYear',
            'Cvv',
            'BillingAddress1',
            'BillingCity',
            'BillingState',
            'BillingCountry',
            'BillingPostcode'
        ));

        $require->appendRequiredFields($extraRequiredFields);

        return $require;
    }
}