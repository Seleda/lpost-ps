<?php


namespace Seleda\LPostPs;

use \Carrier;
use \Seleda\LPostPs\Carrier\AbstractCarrier;
use \Db;
use \Address;
use \Seleda\LPostPs\Configuration as ConfL;

abstract class Calculator
{
    private $id_cart;
    private $id_order;
    private $type;

    private $SumCost = false;
    private $DeliveryCost = false;
    private $ServicesCost;
    private $OptionsCost;
    private $DayLogistic;
    private $DateClose = '0000';

    private $PossibleDelivDates;

    protected $receive_point;

    public $cart;
    public $customer_point;

    /**
     * Calculator constructor.
     * @param \Seleda\LPostPs\Cart $cart
     * @param string $type  (pickup/courier)
     */
    public function __construct($object, $type)
    {
        $this->type = pSQL($type);
        $this->customer_point = CustomerPoint::getInstance($object->id_address_delivery);
        $this->receive_point = new ReceivePoint(Configuration::get('id_sklad'));
        if ($res = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'lpost_calculator` 
            WHERE `type` = "'.$this->type.'" 
            AND '.($this->getSourceFieldWhere($object)).' = '.(int)$object->id)) {

                $this->id_cart = $res['id_cart'];
                $this->id_order = $res['id_order'];
                $this->type = $res['type'];
                $this->SumCost = $res['SumCost'];
                $this->DeliveryCost = $res['DeliveryCost'];
                $this->ServicesCost = $res['ServicesCost'];
                $this->OptionsCost = $res['OptionsCost'];
                $this->DayLogistic = $res['DayLogistic'];
                $this->DateClose = $res['DateClose'];
        }

        if (!$this->validate()) {
            Db::getInstance()->delete('lpost_calculator', $this->getSourceFieldWhere($object).'='.$object->id.' AND `type` = "'.$this->type.'"');
            $pickup_point = false;
            if ($this->type == 'pickup' && !is_null($this->customer_point->pickup_point)) {
                $pickup_point = new PickupPoint($this->customer_point->pickup_point);
            } elseif ($this->type == 'pickup' && $this->customer_point->city_courier) {
                // TODO
                $pickup_points = CustomerPoint::getPointsByCity($this->customer_point->city_courier, 'pickup');
                $pickup_point = new PickupPoint();
            } elseif ($this->type == 'pickup') {
                // TODO
                $address = new Address($this->cart->id_address_delivery);
            }
            if ($response = Client::getInstance()->GetServicesCalc(array(
                'ID_Sklad' => ConfL::get('id_sklad'),
                'ID_PickupPoint' => $this->type == 'pickup' ? $this->customer_point->pickup_point : null,
                'Latitude' => $pickup_point ? $pickup_point->Latitude : $this->customer_point->courier_point_latitude,
                'Longitude' => $pickup_point ? $pickup_point->Longitude : $this->customer_point->courier_point_longitude,
                'Address' => $this->customer_point->getFullAddress($this->type),
                'Weight' => $this->cart->getTotalWeight(),
                'Volume' => array_product($this->cart->getTotalDimensions()),
                'SumPayment' => $this->cart->getSumPayment(),
                'Value' => $this->cart->getValue(),
                'Options' => array(
                    'Fitting' => false, // Примерка
                    'ReturnDocuments' => false // Возврат документов
                )
            ))) {
                $this->id_cart = $this->getSourceFieldWhere($object) == 'id_cart' ? $object->id : null;
                $this->id_order = $this->getSourceFieldWhere($object) == 'id_order' ? $object->id : null;
                $this->SumCost = $response['SumCost'];
                $this->DeliveryCost = $response['DeliveryCost'];
                $this->ServicesCost = $response['ServicesCost'];
                $this->OptionsCost = $response['OptionsCost'];
                $this->DayLogistic = $response['DayLogistic'];
                $this->DateClose = $response['DateClose'];
                $this->PossibleDelivDates = isset($response['PossibleDelivDates']) ? $response['PossibleDelivDates'] : array();
                $this->save();
            }
        }

    }

    private function getSourceFieldWhere($object)
    {
        return $object instanceOf \Cart ? 'id_cart' : 'id_order';
    }

    private function validate()
    {
        return time() < strtotime($this->DateClose);
    }

    public function getDeliveryTime()
    {
        return $this->DayLogistic;
    }

    public function getDeliveryCost()
    {
        return $this->DeliveryCost;
    }

    public function getPossibleDelivDates()
    {
        return $this->PossibleDelivDates;
    }

    private function save()
    {
        return Db::getInstance()->insert('lpost_calculator', array(
            'id_cart' => $this->id_cart,
            'id_order' => $this->id_order,
            'type' => $this->type,
            'SumCost' => $this->SumCost,
            'DeliveryCost' => $this->DeliveryCost,
            'ServicesCost' => $this->ServicesCost,
            'OptionsCost' => $this->OptionsCost,
            'DayLogistic' => $this->DayLogistic,
            'DateClose' => $this->DateClose
        ));
    }
}