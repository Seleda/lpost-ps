<?php


namespace Seleda\LPostPs;

use \Order;
use \Carrier;
use \Seleda\LPostPs\Cart as CartL;

class CalculatorOrder extends Calculator
{
    public function __construct(Order $order, $type = null)
    {
        $this->cart = CartL::createFromOrder($order);

        parent::__construct($order, $type);
    }
}