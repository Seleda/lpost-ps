<?php


namespace Seleda\LPostPs\Order;

use \ObjectModel;
use \Context;
use \Db;

class Order extends ObjectModel implements IOrder
{
    const ENTITY_PERSON = 0;
    const ENTITY_LEGAL = 1;

    public $id_order;

    public $PartnerNumber;
    public $ID_PickupPoint;
    public $ID_Sklad;
    public $ID_PartnerWarehouse;
    public $IssueType;
    public $Address;
    public $Porch;
    public $Floor;
    public $Flat;
    public $Code;
    public $Latitude;
    public $Longitude;
    public $Comment;
    public $DateDeliv;
    public $TypeIntervalDeliv;
    public $Value;
    public $SumPayment;
    public $SumPrePayment;
    public $SumDelivery;
    public $SumServices;
    public $PaymentSettings = array();
    public $CustomerNumber;
    public $isEntity;
    public $Fitting;
    public $CustomerName;
    public $Phone;
    public $Email;
    public $SellerName;
    public $Cargoes = array();

    public static $definition = array(
        'table' => 'lpost_order',
        'primary' => 'id_order_lpost',
        'fields' => array(
            'id_order' => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true),
            'PartnerNumber' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true),
            'ID_PickupPoint' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'ID_Sklad' => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true),
            'ID_PartnerWarehouse' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'IssueType' => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true),
            'Address' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true),
            'Porch' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'Floor' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'Flat' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'Code' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'Latitude' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat'),
            'Longitude' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat'),
            'Comment' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'DateDeliv' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'TypeIntervalDeliv' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'Value' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true),
            'SumPayment' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true),
            'SumPrePayment' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat'),
            'SumDelivery' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat'),
            'SumServices' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat'),
            'CustomerNumber' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true),
            'isEntity' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'Fitting' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'CustomerName' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true),
            'Phone' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true),
            'Email' => array('type' => self::TYPE_STRING, 'validate' => 'isString') // для юр лица обязателное
        )
    );

    public function __construct($id = null, $id_lang = null, $id_shop = null, $translator = null)
    {
        parent::__construct($id, $id_lang, $id_shop, $translator);

        $this->SellerName = Context::getContext()->shop->name;

        foreach (Db::getInstance()->executeS('SELECT `id_payment_setting` FROM `'._DB_PREFIX_.'lpost_payment_setting` WHERE `id_order_lpost` = '.(int)$this->id) as $val) {
            $this->PaymentSettings[] = new PaymentSetting($val['id_payment_setting']);
        }

        foreach (Db::getInstance()->executeS('SELECT `id_cargo` FROM `'._DB_PREFIX_.'lpost_payment_setting` WHERE `id_order_lpost` = '.(int)$this->id) as $val) {

        }
    }

    public function add($auto_date = true, $null_values = false)
    {
        if ($this->isEntity == self::ENTITY_LEGAL && empty($this->Email)) {
            throw new Exception('Email required for legal entity!!!');
        }

        return parent::add($auto_date, $null_values);
    }

    public function update($null_values = false)
    {
        if ($this->isEntity == self::ENTITY_LEGAL && empty($this->Email)) {
            throw new Exception('Email required for legal entity!!!');
        }

        return parent::update($null_values);
    }

    public function getTypeIntervalDelivString()
    {
        //TODO
    }

    public static function createTableDb()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'lpost_order` (
            `id_order_lpost` INT(10) NOT NULL AUTO_INCREMENT,
            `id_order` INT(10) NOT NULL,
            `PartnerNumber` VARCHAR(32) NOT NULL,
            `ID_PickupPoint` INT(10) NOT NULL,
            `ID_Sklad` INT(10) NOT NULL,
            `ID_PartnerWarehouse` INT(10) NOT NULL,
            `IssueType` INT(10) NOT NULL,
            `Address` VARCHAR(256) NOT NULL,
            `Porch` INT(2) NOT NULL,
            `Floor` INT(4) NOT NULL,
            `Flat` INT(5) NOT NULL,
            `Code` VARCHAR(32) NOT NULL,
            `Latitude` DECIMAL(10,8) NOT NULL,
            `Longitude` DECIMAL(11,8) NOT NULL,
            `Comment` TEXT NOT NULL,
            `DateDeliv` DATE NOT NULL,
            `TypeIntervalDeliv` INT(2) NOT NULL,
            `Value` DECIMAL(9,2) NOT NULL,
            `SumPayment` DECIMAL(9,2) NOT NULL,
            `SumPrePayment` DECIMAL(9,2) NOT NULL,
            `SumDelivery` DECIMAL(9,2) NOT NULL,
            `SumServices` DECIMAL(9,2) NOT NULL,
            `CustomerNumber` VARCHAR(32) NOT NULL,
            `isEntity` INT(1) NOT NULL,
            `Fitting` INT(1) NOT NULL,
            `CustomerName` VARCHAR(64) NOT NULL,
            `Phone` VARCHAR(32) NOT NULL,
            `Email` VARCHAR(32) NOT NULL,
            PRIMARY KEY  (`id_order_lpost`),
            KEY `id_order` (`id_order`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        return Db::getInstance()->execute($sql);
    }

    public static function deleteTableDb()
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'lpost_order`';
        return Db::getInstance()->execute($sql);
    }
}