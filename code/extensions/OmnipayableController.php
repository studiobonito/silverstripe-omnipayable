<?php

/**
 * OmnipayableController.
 *
 * @author Tom Densham <tom.densham@studiobonito.co.uk>
 * @copyright (c) 2012, Studio Bonito Ltd.
 * @version 1.0
 */
class OmnipayableController extends Extension
{
    public static $allowed_actions = array(
        'PaymentForm'
    );

    public function PaymentForm()
    {
        $gateway = Config::inst()->get('Omnipayable', 'Gateway');

        $paymentForm = "OmnipayableForm_{$gateway}";

        $form = new $paymentForm($this->owner, 'PaymentForm');

        $form->setAmount($this->owner->Price);

        return $form;
    }
}