<?php


namespace Seleda\LPostPs;

use \ObjectModel;

class PickupPoint extends ObjectModel
{
    public $ID_PickupPoint;
    public $Latitude;
    public $Longitude;
    public $DayLogistic;
    public $IsCourier;
    public $IsCash;
    public $IsCard;
    public $Address;
    public $PickupDop;
    public $Metro;
    public $Photo;
    public $PickupPointWorkHours;
    public $Zone;
    public $ID_Region;
    public $CityName;

    public $force_id = true;

    public static $definition = [
        'table' => 'lpost_pickup_point',
        'primary' => 'ID_PickupPoint',
        'fields' => [
            'Latitude' => ['type' => self::TYPE_STRING, 'validate' => 'isFloat'],
            'Longitude' => ['type' => self::TYPE_STRING, 'validate' => 'isFloat'],
            'DayLogistic' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'IsCourier' => ['type' => self::TYPE_INT, 'validate' => 'isBool', 'required' => true],
            'IsCash' => ['type' => self::TYPE_INT, 'validate' => 'isBool', 'required' => true],
            'IsCard' => ['type' => self::TYPE_INT, 'validate' => 'isBool', 'required' => true],
            'Address' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'PickupDop' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'Metro' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'Photo' => ['type' => self::TYPE_STRING, 'validate' => 'isJson'],
            'PickupPointWorkHours' => ['type' => self::TYPE_STRING, 'validate' => 'isJson'],
            'Zone' => ['type' => self::TYPE_STRING, 'validate' => 'isJson'],
            'ID_Region' => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
            'CityName' => ['type' => self::TYPE_STRING, 'validate' => 'isCityName', 'required' => true],
        ],
    ];

    public function __construct($id = null, $id_lang = null, $id_shop = null, $translator = null)
    {
        parent::__construct($id, $id_lang, $id_shop, $translator);

        $this->Photo = json_decode($this->Photo, true);
        $this->PickupPointWorkHours = json_decode($this->PickupPointWorkHours, true);
        $this->Zone = json_decode($this->Zone, true);
    }

    private function toStringFields()
    {
        $this->Photo = json_encode($this->Photo);
        $this->PickupPointWorkHours = json_encode($this->PickupPointWorkHours);
        $this->Zone = json_encode($this->Zone);
    }

    public function add($auto_date = true, $null_values = false)
    {
        $this->toStringFields();
        return parent::add($auto_date, $null_values);
    }

    public function update($null_values = false)
    {
        $this->toStringFields();
        return parent::update($null_values);
    }
}