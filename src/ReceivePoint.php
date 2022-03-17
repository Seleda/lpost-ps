<?php


namespace Seleda\LPostPs;

use \ObjectModel;

class ReceivePoint extends ObjectModel
{
    public $ID_Sklad;
    public $ID_Region;
    public $City;
    public $Address;
    public $Shedule;
    public $Break;

    public $force_id = true;

    public static $definition = [
        'table' => 'lpost_receive_point',
        'primary' => 'ID_Sklad',
        'fields' => [
            'ID_Region' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'City' => ['type' => self::TYPE_STRING, 'validate' => 'isCityName'],
            'Address' => ['type' => self::TYPE_STRING, 'validate' => 'isAddress'],
            'Shedule' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'Break' => ['type' => self::TYPE_STRING, 'validate' => 'isString']
        ],
    ];
}