<?php

/**
 * Omnipayable.
 *
 * @author Tom Densham <tom.densham@studiobonito.co.uk>
 * @copyright (c) 2012, Studio Bonito Ltd.
 * @version 1.0
 */
class Omnipayable extends DataExtension
{
    /**
     * List of database fields. {@link DataObject::$db}
     *
     * @access public
     * @static
     * @var array
     */
    public static $db = array(
        'Price' => 'Currency'
    );
}