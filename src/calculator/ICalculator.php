<?php


namespace Seleda\LPostPs\Calculator\Calculator;


interface ICalculator
{
    public function getDeliveryTime();
    public function getDeliveryCost();
    public function getPossibleDelivDates();
}