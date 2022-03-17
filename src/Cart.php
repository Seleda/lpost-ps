<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

namespace Seleda\LPostPs;

use \Order as OrderPs;
use \Cart as CartPs;
use \Product;
use \Combination;
use \Db;
use \Tools;

class Cart
{
    private static $instance;

    /**
     * Source for lpost cart
     * @var string cart or order
     */
    public $source;

    public $currency;

    public $id_address_delivery;

    public $order_total;

    private $products = array();

    private function __construct($obj)
    {
        $this->id_address_delivery = $obj->id_address_delivery;
        $this->currency = Currency::getCurrency($obj->id_currency);
    }

    public function createFromOrder(OrderPs $order)
    {
        $obj = new self($order);
        $obj->source = 'order';
        $obj->id_cart = $order->id_cart;

        $obj->order_total = $order->total_products_wt;

        $order_detail = $order->getOrderDetailList();

        foreach ($order_detail as $key => $product) {
            $product_obj = new Product($product['product_id']);
            $product['id_product'] = $product_obj->id;
            $product['weight'] = $product_obj->weight;
            $product['width'] = $product_obj->width;
            $product['height'] = $product_obj->height;
            $product['depth'] = $product_obj->depth;
            $product['id_category'] = $product_obj->id_category_default;
            $product['id_product_attribute'] = $product['product_attribute_id'];

            $obj->products[$key]['id_product'] = $product['product_id'];
            $obj->products[$key]['id_product_attribute'] = $product['product_attribute_id'];
            $obj->products[$key]['cart_quantity'] = $product['product_quantity'];
            $obj->products[$key]['name'] = $product['product_name'];
            $obj->products[$key]['reference'] = $product['product_reference'];
            $obj->products[$key]['id_category'] = $product['id_category'];
            $obj->products[$key]['price'] = $product['total_price_tax_incl'];
            $obj->products[$key]['cost'] = $product['original_product_price'];
            $obj->products[$key]['width'] = self::getProductDimension($product, 'width');
            $obj->products[$key]['height'] = self::getProductDimension($product, 'height');
            $obj->products[$key]['depth'] = self::getProductDimension($product, 'length');
            $obj->products[$key]['weight'] = self::getProductWeight($product);
        }
    }

    public static function createFromCart(CartPs $cart)
    {
        $obj = new self($cart);
        $obj->source = 'cart';

        $obj->order_total = $cart->getOrderTotal(true, CartPs::ONLY_PRODUCTS, null, $cart->id_carrier);

        $products = $cart->getProducts();

        foreach ($products as $key => $product) {
            $obj->products[$key]['id_product'] = $product['id_product'];
            $obj->products[$key]['id_product_attribute'] = $product['id_product_attribute'];
            $obj->products[$key]['cart_quantity'] = $product['cart_quantity'];
            $obj->products[$key]['name'] = $product['name'];
            $obj->products[$key]['reference'] = $product['reference'];
            $obj->products[$key]['id_category'] = $product['id_category'] = $product['id_category_default'];
            $obj->products[$key]['price'] = $product['total_wt'];
            $obj->products[$key]['cost'] = $product['price'];
            $obj->products[$key]['width'] = self::getProductDimension($product, 'width');
            $obj->products[$key]['height'] = self::getProductDimension($product, 'height');
            $obj->products[$key]['depth'] = self::getProductDimension($product, 'length');
            $obj->products[$key]['weight'] = self::getProductWeight($product);
        }
        return $obj;
    }

    public function getTotalWeight()
    {
        $total_weight = 0;
        foreach ($this->products as &$product) {
            $total_weight += $product['weight'] * $product['cart_quantity'];
        }

        return $total_weight;
    }

    public static function getProductWeight($product)
    {
        $weight = 0;
        ($weight_unit = Configuration::get('weight_unit')) || ($weight_unit = 1); // 1 - gr or 1000 - kg // TODO lb 453,59237 g
        ($volume_unit = Configuration::get('volume_unit')) || ($volume_unit = 1); // 1 - sm or 0.1 - mm

        $impact = 0;
        if (Combination::isFeatureActive()) {
            $impact = Db::getInstance()->getValue('SELECT `weight`
                        FROM `'._DB_PREFIX_.'product_attribute` 
                        WHERE `id_product_attribute` = '.(int)$product['id_product_attribute']);
        }

        $default_categories = Configuration::get('default_categories');

        if ((float)$product['weight'] == 0 || $impact != 0) {
            $weight += $impact * $weight_unit;
        } elseif ((float)$product['weight'] && $product['weight'] != $impact) {
            $weight += $product['weight'] * $weight_unit;
        } elseif (isset($default_categories[$product['id_category']]) && $default_categories[$product['id_category']]['weight']) {
            $weight += $default_categories[$product['id_category']]['weight'] * $weight_unit;
        } elseif (($length = self::getProductDimension($product, 'length', true)) &&
            ($width = self::getProductDimension($product, 'width', true)) &&
            ($height = self::getProductDimension($product, 'height', true))) {
            $volume = $width * $height * $length / 5; // объемный вес
            $weight += round($volume);
        } else {
            $default_categories = Configuration::get('default_categories');
            if (isset($default_categories[$product['id_category']]) && (int)$default_categories[$product['id_category']]['weight'] > 0) {
                $weight += $default_categories[$product['id_category']]['weight'] * $weight_unit;
            }
        }

        if ($weight == 0) {
            $weight = Configuration::get('default_weight') * $weight_unit;
        }

        return (int) Tools::ps_round($weight, 0);
    }

    public static function getProductDimension($product, $dimension, $for_weight = false)
    {
        $cache_key = $dimension.'_'.$product['id_product'].'_'.(int)$for_weight;
        static $cache = array();
        if (isset($cache[$cache_key])) {
            return $cache[$cache_key];
        }
        ($volume_unit = Configuration::get('volume_unit')) || $volume_unit = 1; // 1 - sm or 0.1 - mm

        $default_categories = Configuration::get('default_categories');

        $p_dimension = $dimension == 'length' ? 'depth' : $dimension;

        if ((float)$product[$p_dimension]) {
            $cache[$cache_key] = (int)$product[$p_dimension] * $volume_unit;
        } elseif (isset($default_categories[$product['id_category']]) && $default_categories[$product['id_category']][$dimension]) {
            $cache[$cache_key] = (int)$default_categories[$product['id_category']][$dimension] * $volume_unit;
        } elseif (!$for_weight) {
            $cache[$cache_key] = (int)Configuration::get('default_'.$dimension) * $volume_unit;
        } else {
            $cache[$cache_key] = 0;
        }

        return $cache[$cache_key];
    }

    /**
     * https://chilihelp.ru/portfolio/vychislenie-gabaritov-posylki-iz-neskolkikh-tovarov/
     */
    public function getTotalDimensions($cell = null)
    {
        $volume = 0;
        foreach ($this->products as $product) {
            $volume += $product['depth'] * $product['width'] * $product['height'] * $product['cart_quantity'];
        }
        if (is_null($cell)) {
            $cell = array('width' => 100, 'height' => 100, 'depth' => 100);
        }
        // увеличить объем на 10%
        $volume *= 1.1;

        $ratio = array(
            'length' => 1,
            'width' => $cell['width'] / $cell['depth'],
            'height' => $cell['height'] / $cell['depth']
        );

        $length = pow($volume / ($ratio['width'] * $ratio['height']), 1/3);

        $dimensions = array(
            'length' => Tools::ps_round($length),
            'width' => Tools::ps_round($length * $ratio['width']),
            'height' => Tools::ps_round($length * $ratio['height'])
        );

        return $dimensions;
    }

    public function createPackages()
    {
        $packages = array();
        if (ConfigurationCdek::get('all_is_one_package')) {
            $packages[] = self::createPackage($this->products, 1);
        } else {
            foreach ($this->products as $product) {
                if (ConfigurationCdek::get('one_package')) {
                    for ($i = 0; $i < $product['cart_quantity']; $i++) {
                        $packages[] = self::createPackage($product,count($packages) + 1, 1);
                    }
                } else {
                    $packages[] = self::createPackage($product, count($packages) + 1, $product['cart_quantity']);
                }
            }
        }
        return $packages;
    }

    public static function createItem($product, $product_qty = false)
    {
        $payment = new MoneyCdek();
        $payment->setValue($product['price']); //цена продукта в корзине с учетом скидки

        $item = new ItemCdek();
        $item->setName(str_replace('\'', '', $product['name']))
            ->setWareKey($product['reference'])
            ->setPayment($payment)
            ->setCost($product['cost'])
            ->setWeight($product['weight'])
            ->setWeightGross($product['weight'])
            ->setAmount($product_qty ? $product_qty : $product['cart_quantity']);
        return $item;
    }

    public static function createPackage($products, $package_number, $product_qty = false)
    {
        if (isset($products['id_product'])) { // передан один товар, а не массив products
            $products = array($products);
        }
        foreach ($products as $product) {
            $item = self::createItem($product, $product_qty);

            $package = new PackageCdek();
            $package->setNumber($package_number)
                ->setWeight($product['weight'] * ($product_qty ? $product_qty : $item->getAmount()))
                ->setItems(array($item));
        }

        return $package;
    }

    public function getSumPayment()
    {
        // $this->order_total
        return 0.0;
    }

    public function getValue()
    {
        return $this->order_total;
    }
}
