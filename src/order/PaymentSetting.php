<?php


namespace Seleda\LPostPs\Order;

use \ObjectModel;
use \Db;

class PaymentSetting extends ObjectModel
{
    public $id_order_lpost;

    public $ValueSumRansom;
    public $ValueSumDelivery;

    public static $definition = array(
        'table' => 'lpost_payment_setting',
        'primary' => 'id_payment_setting',
        'fields' => array(
            'id_order_lpost' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'ValueSumRansom' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat'),
            'ValueSumDelivery' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat')
        )
    );

    public static function createTableDb()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'lpost_payment_setting` (
            `id_payment_setting` INT(10) NOT NULL AUTO_INCREMENT,
            `id_order_lpost` VARCHAR(32) NOT NULL,
            `ValueSumRansom` DECIMAL(9,2) NOT NULL,
            `ValueSumDelivery` DECIMAL(9,2) NOT NULL,
            PRIMARY KEY  (`id_payment_setting`),
            KEY `ID_Order` (`id_order_lpost`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        return Db::getInstance()->execute($sql);
    }

    public static function deleteTableDb()
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'lpost_payment_setting`';
        return Db::getInstance()->execute($sql);
    }
}