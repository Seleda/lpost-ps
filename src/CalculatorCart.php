<?php


namespace Seleda\LPostPs;

use \Cart;
use \Carrier;
use \Seleda\LPostPs\Cart as CartL;

class CalculatorCart extends Calculator
{
    public function __construct(Cart $cart, $type = null)
    {
        $this->cart = CartL::createFromCart($cart);

        parent::__construct($cart, $type);
    }
}