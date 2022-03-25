<?php


namespace Seleda\LPostPs\Order;

use Matrix\Exception;
use \ObjectModel;
use \Db;

class Product extends ObjectModel
{
    public $id_cargo;

    public $isFragile;
    public $DocumentType;
    public $IDProductPartner;
    public $NameShort;
    public $Price;
    public $Barcode;
    public $NDS;
    public $Quantity;

    public static $definition = array(
        'table' => 'lpost_product',
        'primary' => 'id_product',
        'fields' => array(
            'id_cargo' => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true),
            'isFragile' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'DocumentType' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'IDProductPartner' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'NameShort' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'Price' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat'), // обязательное кроме документов
            'Barcode' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'NDS' => array('type' => self::TYPE_INT, 'validate' => 'isInt'), // обязательное кроме документов
            'Quantity' => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true),
        )
    );

    public function __construct($id = null, $id_lang = null, $id_shop = null, $translator = null)
    {
        parent::__construct($id, $id_lang, $id_shop, $translator);
    }

    public function add($auto_date = true, $null_values = false)
    {
        if (empty($this->DocumentType) && empty($this->Price)) {
            throw new Exception('Price required for product!!!');
        }
        if (empty($this->DocumentType) && empty($this->NDS)) {
            throw new Exception('NDS required for product!!!');
        }
        return parent::add($auto_date, $null_values);
    }

    public function update($null_values = false)
    {
        if (empty($this->DocumentType) && empty($this->Price)) {
            throw new Exception('Price required for product!!!');
        }
        if (empty($this->DocumentType) && empty($this->NDS)) {
            throw new Exception('NDS required for product!!!');
        }
        return parent::update($null_values);
    }

    public function getCreateOrdersParams()
    {
        return array(
            'IDProductPartner' => $this->IDProductPartner,
            'NameShort' => $this->NameShort,
            'Price' => $this->Price,
            'Barcode' => $this->Barcode,
            'NDS' => $this->NDS,
            'Quantity' => $this->Quantity,
            'isFragile' => $this->isFragile
        );
    }

    public static function createTableDb()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'lpost_product` (
            `id_product` INT(10) NOT NULL AUTO_INCREMENT,
            `id_cargo` INT(10) NOT NULL,
            `isFragile` INT(1) NOT NULL,
            `DocumentType` INT(1) NOT NULL,
            `IDProductPartner` VARCHAR(32) NOT NULL,
            `ID_PartnerWarehouse` INT(10) NOT NULL,
            `NameShort` VARCHAR(256) NOT NULL,
            `Price` DECIMAL(9,2) NOT NULL,
            `Barcode` VARCHAR(64) NOT NULL,
            `NDS` INT(2) NOT NULL,
            `Quantity` INT(5) NOT NULL,
            PRIMARY KEY  (`id_product`),
            KEY `id_cargo` (`id_cargo`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        return Db::getInstance()->execute($sql);
    }

    public static function deleteTableDb()
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'lpost_product`';
        return Db::getInstance()->execute($sql);
    }
}