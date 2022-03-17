<?php


namespace Seleda\LPostPs;

use \Db;
use Matrix\Exception;


class Configuration
{
    private static $instance;

    // general tab
    private $test = 0;
    private $secret_key = 'tFSMWYaJ9YuxkeB6'; //product tFSMWYaJ9YuxkeB6 //test 9m8D4azqhYL8pMsn
    private $yandex_api_key;
    private $issue_type = 0;
    private $products_is_one_package = 0;
    private $all_is_one_package = 0;
    // location tab
    private $id_sklad = 3;
    private $address_sklad = '';
    //
    private $seller_name;
    private $delivery_cost_impact;
    private $value = 100;
    private $weight_unit = 1;
    private $volume_unit = 1;
    private $default_weight = 1;
    private $default_length = 1;
    private $default_width = 1;
    private $default_height = 1;
    private $free_shipping_courier;
    private $free_shipping_pickup;
    private $delay = 0;
    private $total_correction;
    private $product_price_reduction;
    private $impact_percent_of_cart;
    // log tab
    private $write_log = 1;

    private $default_categories = array();
    private $statuses = array('create' => array(), 'delete' => array(), 'cod_ship' => array(), 'cod' => array());

    private function __construct()
    {
        foreach (Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'lpost_configuration`') as $val) {
            if (!property_exists($this, $val['name'])) {
                continue;
            }
            $this->{$val['name']} = $val['value'];
        }

        $sql = 'SELECT CONCAT(`City`, ", ", `Address`) as `concat_address` FROM `'._DB_PREFIX_.'lpost_receive_point` WHERE `ID_Sklad` = '.(int)$this->id_sklad;
        $this->address_sklad = Db::getInstance()->getValue($sql);

        $this->setDefaultCategories();
        $this->setStatuses();
    }

    public function getArray()
    {
        return get_object_vars($this);
    }

    private static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function get($name = null)
    {
        $conf = self::getInstance();
        if (is_null($name)) {
            return $conf;
        }

        if (property_exists($conf, $name)) {
            return $conf->{$name};
        }
        return null;
    }

    public static function set($name, $value)
    {
        $conf = self::getInstance();
        $conf->{$name} = $value;
        return self::save(array($name => $value));
    }

    private function setStatuses()
    {
        $statuses = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'lpost_status`');
        foreach ($statuses as $status) {
            if ($status['create']) {
                $this->statuses['create'][] = $status['id_status'];
            }
            if ($status['delete']) {
                $this->statuses['delete'][] = $status['id_status'];
            }
            if ($status['cod_ship']) {
                $this->statuses['cod_ship'][] = $status['id_status'];
            }
            if ($status['cod']) {
                $this->statuses['cod'][] = $status['id_status'];
            }
        }
    }

    private function setDefaultCategories()
    {
        $categories = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'lpost_category`');
        foreach ($categories as $category) {
            $this->default_categories[$category['id_category']] = array(
                'weight' => $category['weight'],
                'length' => $category['length'],
                'width' => $category['width'],
                'height' => $category['height']
            );
        }
    }

    public function __set($name, $value)
    {
        if (!property_exists($this, $name)) {
            throw new Exception('The property does not exist');
        }
        $this->{$name} = $value;
    }

    public function __get($name)
    {
        if (!property_exists($this, $name)) {
            throw new Exception('The property does not exist');
        }
        return $this->{$name};
    }

    public static function save($data = null)
    {
        $res = true;

        if (is_null($data)) {
            $data = self::getInstance()->getArray();
            unset($data['default_categories']);
            unset($data['statuses']);
        }
        foreach ($data as $name => $value) {
            $res &= Db::getInstance()->insert(
                'lpost_configuration',
                array('name' => $name, 'value' => $value),
                false,
                true,
                Db::ON_DUPLICATE_KEY
            );
        }
        return $res;
    }
}