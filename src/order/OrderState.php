<?php


namespace Seleda\LPostPs\Order;

use \ObjectModel;
use \Db;


class OrderState extends ObjectModel
{
    private $OrderReturn = array();

    public $ID_Order;
    public $StateDelivery;
    public $DateChangeStateDelivery;
    public $StateInf;
    public $PaymentMethod;
    public $CheckUml;
    public $Summ;
    public $StateReturn;
    public $DateChangeStateReturn;
    public $Message;

    public static $definition = array(
        'table' => 'lpost_order_state',
        'primary' => 'id_order_state',
        'fields' => array(
            'ID_Order' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'StateDelivery' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'DateChangeStateDelivery' => array('type' => self::TYPE_DATE, 'validate' => 'isDateOrNull'),
            'StateInf' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'PaymentMethod' => array('type' => self::TYPE_STRING, 'validate' => 'isString'), // заполняется, если StateDelivery == DONE
            'CheckUml' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'Summ' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat'),
            'StateReturn' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'DateChangeStateReturn' => array('type' => self::TYPE_DATE, 'validate' => 'isDateOrNull'),
            'Message' => array('type' => self::TYPE_STRING, 'validate' => 'isString')
        )
    );

    public function __construct($id = null, $id_lang = null, $id_shop = null, $translator = null)
    {
        parent::__construct($id, $id_lang, $id_shop, $translator);

        foreach (Db::getInstance()->executeS('SELECT `id_order_return` FROM `'._DB_PREFIX_.'lpost_order_return` WHERE `id_order_state` = '.(int)$this->id) as $val) {
            $this->OrderReturn[] = new OrderReturn($val['id_order_return']);
        }


    }

    public static function createTableDb()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'lpost_order_state` (
            `id_order_state` INT(10) NOT NULL AUTO_INCREMENT,
            `ID_Order` VARCHAR(32) NOT NULL,
            `StateDelivery` VARCHAR(32) NOT NULL,
            `DateChangeStateDelivery` DATETIME NOT NULL,
            `StateInf` VARCHAR(256) NOT NULL,
            `PaymentMethod` VARCHAR(32) NOT NULL,
            `CheckUml` VARCHAR(256) NOT NULL,
            `Summ` DECIMAL(9,2) NOT NULL,
            `StateReturn` VARCHAR(32) NOT NULL,
            `DateChangeStateReturn` DATETIME NOT NULL,
            `Message` TEXT NOT NULL,
            PRIMARY KEY  (`id_order_state`),
            KEY `ID_Order` (`ID_Order`),
            KEY `StateDelivery` (`StateDelivery`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        return Db::getInstance()->execute($sql);
    }

    public static function deleteTableDb()
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'lpost_order_state`';
        return Db::getInstance()->execute($sql);
    }
}