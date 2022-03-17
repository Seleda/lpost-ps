<?php


namespace Seleda\LPostPs\Order;


interface IOrder
{
    public static function createTableDb();
    public static function deleteTableDb();
}