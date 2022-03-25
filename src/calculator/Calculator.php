<?php

namespace Seleda\LPostPs\Calculator\Calculator;

use \Db;
use \Seleda\LPostPs\Cart;
use \Seleda\LPostPs\Configuration as ConfLP;
use \Seleda\LPostPs\CustomerPoint;

class Calculator implements ICalculator
{
    public $id_cart;
    public $ID_Sklad;
    public $ID_PickupPoint;
    public $Latitude;
    public $Longitude;
    public $Address;
    public $Weight;
    public $Volume;
    public $SumPayment;
    public $Value;

    public $SumCost;
    public $DeliveryCost;
    public $ServicesCost;
    public $OptionsCost;
    public $DayLogistic;
    public $PossibleDelivDates;
    public $DateClose;

    public $Options = array(
        'Fitting' => false, // Примерка
        'ReturnDocuments' => false // Возврат документов
    );

    public $cart;
    public $customer_point;
    public $type;
    private $cache_valid = true;

    public function __construct(Cart $cart, CustomerPoint $customer_point, $type)
    {
        $this->cart = $cart;
        $this->customer_point = $customer_point;
        $this->type = $type;

        $sql = 'SELECT * FROM `'._DB_PREFIX_.'lpost_calculator` WHERE `id_cart` = '.(int)$cart->id_cart.' AND `type` = '.pSQL($type);
        $res = Db::getInstance($sql);
        if ($res) {
            $this->setRequestParams($res);
            if ($this->cache_valid) {
                $this->setResponseParams($res);
            }
        }
        if (!$this->isValid()) {
            if ($response = Client::getInstance()->GetServicesCalc($this->getRequstParams())) {
                $this->setResponseParams($response);
                $this->save();
            }
        }
    }

    private function getRequstParams()
    {
        $params = array(
            'ID_Sklad' => $this->ID_Sklad,
            'Latitude' => $this->Latitude,
            'Longitude' => $this->Longitude,
            'Address' => $this->Address,
            'Weight' => $this->Weight,
            'Volume' => $this->Volume,
            'SumPayment' => $this->SumPayment,
            'Value' => $this->Value,
            'Options' => $this->Options
        );
        if ($this->type == 'pickup') {
            $params['ID_PickupPoint'] = $this->ID_PickupPoint;
        }
        return $params;
    }

    private function setRequestParams($params)
    {
        $this->id_cart = $params['id_cart'];
        $this->type = $params['type'];
        $this->setIDSklad($params['ID_Sklad']);
        $this->setCoordinates($params['Longitude'], $params['Latitude']);
        $this->setIDPickupPoint($params['ID_PickupPoint']);
        $this->setAddress($params['Address']);
        $this->setWeight($params['Weight']);
        $this->setVolume($params['Volume']);
        $this->setSumPayment($params['SumPayment']);
        $this->setValue($params['Value']);
        $this->Options['Fitting'] = ConfLP::get('Fitting');
        $this->Options['ReturnDocuments'] = ConfLP::get('ReturnDocuments');
    }

    private function setResponseParams($params)
    {
        $this->SumCost = $params['SumCost'];
        $this->DeliveryCost = $params['DeliveryCost'];
        $this->ServicesCost = $params['ServicesCost'];
        $this->OptionsCost = $params['OptionsCost'];
        $this->DayLogistic = $params['DayLogistic'];
        $this->PossibleDelivDates = isset($params['PossibleDelivDates']) ? $params['PossibleDelivDates'] : false;
        $this->DateClose = $params['DateClose'];
    }

    private function setIDSklad($ID_Sklad)
    {
        if ($ID_Sklad != ConfL::get('id_sklad')) {
            $this->ID_Sklad = ConfL::get('id_sklad');
            $this->cache_valid = false;
        } else {
            $this->ID_Sklad = $ID_Sklad;
        }
    }

    private function setIDPickupPoint($ID_PickupPoint)
    {
        if ($this->type == 'courier') {
            return false;
        }
        if ($ID_PickupPoint != $this->customer_point->pickup_point) {
            $this->ID_PickupPoint = $this->customer_point->pickup_point;
            $this->cache_valid = false;
        } else {
            $this->ID_PickupPoint = $ID_PickupPoint;
        }
    }

    private function setCoordinates($Longitude, $Latitude)
    {
        if ($this->type == 'pickup') {
            $pickup_point = new PickupPoint($this->customer_point->pickup_point);
            $control_latitude = $pickup_point->Latitude;
            $control_longitude = $pickup_point->Longitude;
        } else {
            $control_latitude = $this->customer_point->courier_point_latitude;
            $control_longitude = $this->customer_point->courier_point_longitude;
        }
        if ($Latitude != $control_latitude) {
            $this->Latitude = $control_latitude;
            $this->Longitude = $control_longitude;
            $this->cache_valid = false;
        } else {
            $this->Latitude = $Latitude;
            $this->Longitude = $Longitude;
        }
    }

    private function setAddress($Address)
    {
        if ($Address != $this->customer_point->getFullAddress($this->type)) {
            $this->Address = $this->customer_point->getFullAddress($this->type);
            $this->cache_valid = false;
        } else {
            $this->Address = $Address;
        }
    }

    private function setWeight($Weight)
    {
        if ($Weight != $this->cart->getTotalWeight()) {
            $this->Weight = $this->cart->getTotalWeight();
            $this->cache_valid = false;
        } else {
            $this->Weight = $Weight;
        }
    }

    private function setVolume($Volume)
    {
        if ($Volume != array_product($this->cart->getTotalDimensions())) {
            $this->Volume = array_product($this->cart->getTotalDimensions());
            $this->cache_valid = false;
        } else {
            $this->Volume = $Volume;
        }
    }

    private function setSumPayment($SumPayment)
    {
        if ($SumPayment != $this->cart->getSumPayment()) {
            $this->SumPayment = $this->cart->getSumPayment();
            $this->cache_valid = false;
        } else {
            $this->SumPayment = $SumPayment;
        }
    }

    private function setValue($Value)
    {
        if ($Value != $this->cart->getValue()) {
            $this->Value = $this->cart->getValue();
            $this->cache_valid = false;
        } else {
            $this->Value = $Value;
        }
    }

    private function isValid()
    {
        return $this->DateClose && time() < strtotime($this->DateClose);
    }

    public function getDeliveryCost()
    {
        return $this->DeliveryCost;
    }

    public function getDeliveryTime()
    {
        return $this->DayLogistic;
    }

    public function getPossibleDelivDates()
    {
        return $this->PossibleDelivDates;
    }

    public static function createTableDb()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'lpost_calculator` (
            `id_cart` INT(10) unsigned NOT NULL,
            `type` VARCHAR(8) NOT NULL,
            `ID_Sklad` INT(10) NOT NULL,
            `ID_PickupPoint` INT(10) NOT NULL,
            `Latitude` DECIMAL(10,8) NOT NULL,
            `Longitude` DECIMAL(11,8) NOT NULL,
            `Address` VARCHAR(256) NOT NULL,
            `Weight` INT(11) NOT NULL,
            `Volume` INT(10) NOT NULL,
            `SumPayment` DECIMAL(8,2) NOT NULL,
            `Value` DECIMAL(8,2) NOT NULL,
            `SumCost` DECIMAL(8,2) NOT NULL,
            `DeliveryCost` DECIMAL(8,2) NOT NULL,
            `ServicesCost` DECIMAL(8,2) NOT NULL,
            `OptionsCost` DECIMAL(8,2) NOT NULL,
            `DayLogistic` INT(2) NOT NULL,
            `PossibleDelivDates` VARCHAR(64) NOT NULL,
            `DateClose` VARCHAR(32) NOT NULL,
            PRIMARY KEY  (`id_cart`, `id_order`, `type`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        return Db::getInstance()->execute($sql);
    }

    public static function deleteTableDb()
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'lpost_calculator`';
        return Db::getInstance()->execute($sql);
    }
}