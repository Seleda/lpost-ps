<?php


namespace Seleda\LPostPs\Order;

use \ObjectModel;
use \Db;

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
            'id_order_state' => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true),
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

    public static function createTableDb()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'lpost_order_return` (
            `id_order_return` INT(10) NOT NULL AUTO_INCREMENT,
            `id_order_state` INT(10) NOT NULL,
            `IDProductPartner` VARCHAR(32) NOT NULL,
            `NameShort` VARCHAR(256) NOT NULL,
            `Quantity` INT(8) NOT NULL,
            `ReturnType` INT(1) NOT NULL,
            PRIMARY KEY  (`id_order_return`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        return Db::getInstance()->execute($sql);
    }

    public static function deleteTableDb()
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'lpost_order_return`';
        return Db::getInstance()->execute($sql);
    }
}