<?php


namespace Seleda\LPostPs\Order;

use \ObjectModel;

class OrderReturn extends ObjectModel
{
    const _TYPE_REJECTION_ = 1;
    const _TYPE_SHORTAGE_ = 2;
    const _TYPE_SURPLUS_ = 4;

    public $id_order_state;

    public $IDProductPartner;
    public $NameShort;
    public $Quantity;
    public $ReturnType;

    public static $definition = array(
        'table' => 'lpost_order_return',
        'primary' => 'id_order_return',
        'fields' => array(
            'IDProductPartner' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'NameShort' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'Quantity' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'ReturnType' => array('type' => self::TYPE_INT, 'validate' => 'isInt')
        )
    );

    public function getReturnTypeString()
    {
        $lang = array('', 'отказ', 'недостача', '', 'излишек');
        return $lang[$this->ReturnType];
    }
}