<?php


namespace Seleda\LPostPs\Carrier;

use \Db;
use \Carrier;
use Seleda\LPostPs\Cache;

class AbstractCarrier
{
    public static function getCarriers()
    {

    }

    public static function getTypeByIdCarrier($id_carrier)
    {
        $key = 'getTypeByIdCarrier'.$id_carrier;
        if (Cache::isStored($key)) {
            return Cache::retrieve($key);
        }
        Cache::store($key, Db::getInstance()->getValue('SELECT `type` FROM `'._DB_PREFIX_.'lpost_carrier_type` ct
            LEFT JOIN `'._DB_PREFIX_.'carrier` c ON ct.`carrier_reference` = c.`id_reference`
            WHERE c.`id_carrier` = '.(int)$id_carrier));

        return Cache::retrieve($key);
    }

    public static function getTypeByCarrierReference($reference)
    {
        return Db::getInstance()->getValue('SELECT `type`  FROM `'._DB_PREFIX_.'lpost_carrier_type` WHERE `carrier_reference` = '.(int)$reference);
    }

    public static function getCarrierPsByType($type)
    {
        $sql = 'SELECT c.`id_carrier` FROM `'._DB_PREFIX_.'lpost_carrier_type` ct
            LEFT JOIN `'._DB_PREFIX_.'carrier` c ON ct.`carrier_reference` = c.`id_reference`
            WHERE ct.`type` = "'.pSQL($type).'" AND c.`deleted` = 0';
        $id_carrier = Db::getInstance()->getValue($sql);
        return new Carrier((int)$id_carrier);
    }
}