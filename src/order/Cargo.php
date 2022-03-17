<?php


namespace Seleda\LPostPs\Order;

use \ObjectModel;

class Cargo extends ObjectModel
{
    public $id_order_lpost;

    public $Barcode;
    public $Weight;
    public $Length;
    public $Width;
    public $Height;
    public $Product = array();
    public static $definition = array(
        'table' => 'lpost_cargo',
        'primary' => 'id_cargo',
        'fields' => array(
            'id_order_lpost' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'Barcode' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'Weight' => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true),
            'Length' => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true),
            'Width' => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true),
            'Height' => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true)
        )
    );
}