<?php


namespace Seleda\LPostPs\Order;

use \ObjectModel;
use \Db;

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

    public function __construct($id = null, $id_lang = null, $id_shop = null, $translator = null)
    {
        parent::__construct($id, $id_lang, $id_shop, $translator);

        foreach (Db::getInstance()->executeS('SELECT `id_product` FROM `'._DB_PREFIX_.'lpost_product` WHERE `id_cargo` = '.(int)$this->id) as $val) {
            $this->Product[] = new Product($val['id_product']);
        }
    }

    public function deleteProducts()
    {
        foreach ($this->Product as $product) {
            $product->delete();
        }
        $this->Product = array();
    }

    public function getCreateOrdersParams()
    {
        $params = array(
            'Barcode' => '',
            'Weight' => $this->Weight,
            'Length' => $this->Length,
            'Width' => $this->Width,
            'Height' => $this->Height,
            'Product' => array()
        );

        foreach ($this->Product as $product) {
            $params['Product'][] = $product->getCreateOrdersParams();
        }

        return $params;
    }

    public static function createTableDb()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'lpost_cargo` (
            `id_cargo` INT(10) NOT NULL AUTO_INCREMENT,
            `id_order_lpost` INT(10) NOT NULL,
            `Barcode` VARCHAR(32) NOT NULL,
            `Weight` INT(10) NOT NULL,
            `Length` INT(10) NOT NULL,
            `Width` INT(10) NOT NULL,
            `Height` INT(10) NOT NULL,
            PRIMARY KEY  (`id_cargo`),
            KEY `id_order_lpost` (`id_order_lpost`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        return Db::getInstance()->execute($sql) && Product::createTableDb();
    }

    public static function deleteTableDb()
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'lpost_cargo`';
        return Db::getInstance()->execute($sql);
    }
}