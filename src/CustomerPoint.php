<?php


namespace Seleda\LPostPs;

use Matrix\Exception;
use \ObjectModel;
use \Db;
use \Validate;
use \Address;
use \Seleda\LPostPs\Configuration as ConfL;
use \ToolsModuleLP;

class CustomerPoint extends ObjectModel
{
    private static $main = false;
    private static $instancies = array();

    public $force_id = true;

    public $city_courier;
    public $city_pickup;
    public $street_courier;
    public $house_courier;
    public $courier_point_latitude;
    public $courier_point_longitude;
    public $pickup_point;
    public $saved;

    public static $definition = [
        'table' => 'lpost_customer_point',
        'primary' => 'id_address',
        'fields' => [
            'city_courier' => ['type' => self::TYPE_STRING, 'validate' => 'isCityName'],
            'street_courier' => ['type' => self::TYPE_STRING, 'validate' => 'isAddress'],
            'house_courier' => ['type' => self::TYPE_STRING, 'validate' => 'isAddress'],
            'city_pickup' => ['type' => self::TYPE_STRING, 'validate' => 'isCityName'],
            'courier_point_latitude' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'courier_point_longitude' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'pickup_point' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'saved' => ['type' => self::TYPE_INT, 'validate' => 'isBool'],
        ],
    ];

    public function __construct($id = null, $id_lang = null, $id_shop = null, $translator = null)
    {
        if (!self::$main) {
            throw new Exception('Create an object through a static function');
        }
        parent::__construct($id, $id_lang, $id_shop, $translator);
        $this->id = (int)$id; // if not saved
    }

    public static function getInstance($id_address_delivery)
    {
        if (!isset(self::$instancies[$id_address_delivery])) {
            self::$main = true;
            $customer_point = new CustomerPoint($id_address_delivery);

//            if (!Validate::isLoadedObject($customer_point)) {
//                $customer_point = CustomerPoint::getDefaultCustomerPoint(new Address($id_address_delivery));
//            }
            self::$main = false;
            self::$instancies[$id_address_delivery] = $customer_point;
        }

        return self::$instancies[$id_address_delivery];
    }

    public function updateCityPickup($city)
    {
//        throw new \Symfony\Component\Config\Definition\Exception\Exception('TODO');
        if($city == $this->city_pickup) {
            return true;
        }
        $this->city_pickup = $city;
        return $this->save();
    }

    public function check()
    {
        return $this->city_courier && $this->pickup_point && $this->courier_point_longitude;
    }

    public function getAddress($type)
    {
        if ($type == 'pickup') {
            if (is_null($this->pickup_point)) {
                return null;
            }
            return Db::getInstance()->getValue('SELECT CONCAT(`CityName`, ", ", `Address`) as `Address` FROM `'._DB_PREFIX_.'lpost_pickup_point` WHERE `ID_PickupPoint` = '.(int)$this->pickup_point);
        } else {
            return $this->city_courier.($this->street_courier ? ', ' : '').$this->street_courier.($this->house_courier ? ', ' : '').$this->house_courier;
        }
    }

    public function getFullAddress($type)
    {
        return $this->{'city_'.$type}.', '.$this->getAddress($type);
    }

    public static function getDefaultCustomerPoint($address) // remove
    {
        $cp = new CustomerPoint($address->id);
        if (!\Validate::isLoadedObject($cp) && \Validate::isLoadedObject($address)) {
            $cp = self::updatePoint($address, $cp);
        }
        return $cp;
    }

    public static function updatePoint(Address $address, CustomerPoint $point) // remove
    {
        $default_pickup_point = Db::getInstance()->getRow('SELECT DISTINCT `CityName`, `ID_PickupPoint` FROM `'._DB_PREFIX_.'lpost_pickup_point` WHERE `IsCourier` = 0 AND `CityName` = "'.pSQL($point->city_courier).'"');
        $city_pickup = $default_pickup_point ? $default_pickup_point['CityName'] : 'Москва';
        $point->id = $address->id;
        $point->city_pickup = $city_pickup;
        $point->pickup_point = Db::getInstance()->getValue('SELECT `ID_PickupPoint` FROM `'._DB_PREFIX_.'lpost_pickup_point` WHERE `IsCourier` = 0 AND `CityName` = "'.pSQL($point->city_courier).'"');
        $point->save();

        return $point;
    }

    public function save($null_values = false, $auto_date = true)
    {
        return (int) $this->saved > 0 ? $this->update($null_values) : $this->add($auto_date, $null_values);
    }

    public function add($auto_date = true, $null_values = false)
    {
        $this->saved = true;
        return parent::add($auto_date, $null_values);
    }

    public static function getPointsByCoords($coords, $type = false)
    {
        $sql = 'SELECT (POWER(`Longitude` - '.$coords[0].', 2) + POWER(`Latitude` - '.$coords[1].', 2)) as `length`,
                `ID_Region`
                FROM `'._DB_PREFIX_.'lpost_pickup_point` WHERE `IsCourier` = 0 
                ORDER BY `length` ASC';
        $res = Db::getInstance()->getRow($sql);
        if ($res['ID_Region'] == 47 && $type == 'courier') {
            $res['ID_Region'] = 78;
        } elseif ($res['ID_Region'] == 50 && $type == 'courier') {
            $res['ID_Region'] = 77;
        }
        $type_sql = '';
        if ($type) {
            $type_sql = $type == 'courier' ? ' AND `IsCourier` = 1' : ' AND `IsCourier` = 0';
        }
        $sql = 'SELECT * FROM `'._DB_PREFIX_.'lpost_pickup_point` WHERE 1'.$type_sql.' AND `ID_Region` = '.(int)$res['ID_Region'];
        return PickupPoint::jsonDecodeFields(Db::getInstance()->executeS($sql));
    }

    public static function getPointsByCity($city, $type)
    {
        $sql = 'SELECT * FROM `'._DB_PREFIX_.'lpost_pickup_point` WHERE `IsCourier` = '.(int)($type == 'courier').' AND `CityName` = "'.pSQL(trim($city)).'"';
        $points = PickupPoint::jsonDecodeFields(Db::getInstance()->executeS($sql));

        if (!$points && $type == 'courier') {
            $sql = 'SELECT `ID_Region` FROM `'._DB_PREFIX_.'lpost_pickup_point` WHERE `IsCourier` = 0 AND `CityName` = "'.pSQL(trim($city)).'"';
            $id_region = Db::getInstance()->getValue($sql);
            if ($id_region == 50) {
                $id_region = 77;
            } elseif ($id_region == 47) {
                $id_region = 78;
            }
            $sql = 'SELECT * FROM `'._DB_PREFIX_.'lpost_pickup_point` WHERE `IsCourier` = 1 AND `ID_Region` = '.(int)$id_region;
            $points = PickupPoint::jsonDecodeFields(Db::getInstance()->executeS($sql));
        }

        return $points;
    }

    public static function getGeoCodeByAddressObject(Address $address)// remove
    {
        $customer_point = CustomerPoint::getInstance($address->id);
        $res = json_decode(file_get_contents('https://geocode-maps.yandex.ru/1.x?geocode='.(urlencode($address->city.', '.$address->address1.' '.$address->address2)).'&apikey='.ConfL::get('yandex_api_key').'&sco=longlat&format=json&lang=ru_RU'), true);
        if (isset($res['response']) && isset($res['response']['GeoObjectCollection']) && isset($res['response']['GeoObjectCollection']['featureMember'])) {
            $need_fields = ['locality' => 'city_courier', 'street' => 'street_courier', 'house' => 'house_courier'];
            foreach ($res['response']['GeoObjectCollection']['featureMember'] as $geoObject) {
                foreach ($geoObject['GeoObject']['metaDataProperty']['GeocoderMetaData']['Address']['Components'] as $component) {
                    if (key_exists($component['kind'], $need_fields)) {
                        $customer_point->{$need_fields[$component['kind']]} = $component['name'];
                    }
                }
                $pos = explode(' ', $geoObject['GeoObject']['Point']['pos']);
                $customer_point->courier_point_longitude = $pos[0];
                $customer_point->courier_point_latitude = $pos[1];
                break;
            }
            $customer_point->save();
        }

        return $customer_point;
    }

    public static function getGeocodeByAddressString($address, $id_address_delivery) // remove
    {
        $customer_point = CustomerPoint::getInstance($id_address_delivery);
        $res = json_decode(file_get_contents('https://geocode-maps.yandex.ru/1.x?geocode='.(urlencode($address)).'&apikey='.ConfL::get('yandex_api_key').'&sco=longlat&format=json&lang=ru_RU'), true);
        if (isset($res['response']) && isset($res['response']['GeoObjectCollection']) && isset($res['response']['GeoObjectCollection']['featureMember'])) {
            $need_fields = ['locality' => 'city_courier', 'street' => 'street_courier', 'house' => 'house_courier'];
            foreach ($res['response']['GeoObjectCollection']['featureMember'] as $geoObject) {
                foreach ($geoObject['GeoObject']['metaDataProperty']['GeocoderMetaData']['Address']['Components'] as $component) {
                    if (key_exists($component['kind'], $need_fields)) {
                        $customer_point->{$need_fields[$component['kind']]} = $component['name'];
                    }
                }
                $pos = explode(' ', $geoObject['GeoObject']['Point']['pos']);
                $customer_point->courier_point_longitude = $pos[0];
                $customer_point->courier_point_latitude = $pos[1];
                break;
            }
            $customer_point->save();
        }

        return $customer_point;
    }
}