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

class Cart
{
    private static $instance;
    public $id_cart;
    public $lang;
    public $currency;
    public $id_address_delivery;
    public $order_total;
    public $products = array();

    private function __construct($cart)
    {
        $this->id_address_delivery = $cart->id_address_delivery;
        $this->currency = CurrencyCdek::getCurrency($cart->id_currency);
        $this->lang = LangCdek::getInstance($cart->id_lang)->getLang();
    }

    public static function getInstance($cart)
    {
        if (!self::$instance) {
            self::$instance = new CartCdek($cart);
            if ($cart->orderExists()) {
                if (method_exists('Order', 'getByCartId')) {
                    $order = Order::getByCartId($cart->id);
                } else {
                    $id_order = Order::getOrderByCartId($cart->id);
                    $order = new Order($id_order);
                }
                self::$instance->createFromOrder($order);
            } else {
                self::$instance->createFromCart($cart);
            }
        }
        return self::$instance;
    }

    private function createFromOrder($order)
    {
        $this->id_cart = $order->id_cart;

        $this->order_total = $order->total_products_wt;

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

            $this->products[$key]['id_product'] = $product['product_id'];
            $this->products[$key]['id_product_attribute'] = $product['product_attribute_id'];
            $this->products[$key]['cart_quantity'] = $product['product_quantity'];
            $this->products[$key]['name'] = $product['product_name'];
            $this->products[$key]['reference'] = $product['product_reference'];
            $this->products[$key]['id_category'] = $product['id_category'];
            $this->products[$key]['price'] = $product['total_price_tax_incl'];
            $this->products[$key]['cost'] = $product['original_product_price'];
            $this->products[$key]['width'] = self::getProductDimension($product, 'width');
            $this->products[$key]['height'] = self::getProductDimension($product, 'height');
            $this->products[$key]['depth'] = self::getProductDimension($product, 'length');
            $this->products[$key]['weight'] = self::getProductWeight($product);
        }
    }

    private function createFromCart($cart)
    {
        $this->id_cart = $cart->id;

        $this->order_total = $cart->getOrderTotal(true, Cart::ONLY_PRODUCTS, null, $cart->id_carrier);

        $products = $cart->getProducts();

        foreach ($products as $key => $product) {
            $product['id_category'] = $product['id_category_default'];
            $this->products[$key]['id_product'] = $product['id_product'];
            $this->products[$key]['id_product_attribute'] = $product['id_product_attribute'];
            $this->products[$key]['cart_quantity'] = $product['cart_quantity'];
            $this->products[$key]['name'] = $product['name'];
            $this->products[$key]['reference'] = $product['reference'];
            $this->products[$key]['id_category'] = $product['id_category'] = $product['id_category_default'];
            $this->products[$key]['price'] = $product['total_wt'];
            $this->products[$key]['cost'] = $product['price'];
            $this->products[$key]['width'] = self::getProductDimension($product, 'width');
            $this->products[$key]['height'] = self::getProductDimension($product, 'height');
            $this->products[$key]['depth'] = self::getProductDimension($product, 'length');
            $this->products[$key]['weight'] = self::getProductWeight($product);
        }
    }

    public function getPackages()
    {
        $packages = array();
        foreach ($this->products as $key => $product) {
            $packages[$key] = array(
                'weight' => $product['weight'] * $product['cart_quantity']
            );

//            if ($length = self::getProductDimension($product, 'length')) {
//                if ($width = self::getProductDimension($product, 'width')) {
//                    if ($height = self::getProductDimension($product, 'height')) {
//                        $packages[$key]['length'] = $length;
//                        $packages[$key]['width'] = $width;
//                        $packages[$key]['height'] = $height;
//                    }
//                }
//            }
        }
        return $packages;
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
        ($weight_unit = ConfigurationCdek::get('weight_unit')) || ($weight_unit = 1); // 1 - gr or 1000 - kg // TODO lb 453,59237 g
        ($volume_unit = ConfigurationCdek::get('volume_unit')) || ($volume_unit = 1); // 1 - sm or 0.1 - mm

        $impact = 0;
        if (Combination::isFeatureActive()) {
            $impact = Db::getInstance()->getValue('SELECT `weight`
                        FROM `'._DB_PREFIX_.'product_attribute` 
                        WHERE `id_product_attribute` = '.(int)$product['id_product_attribute']);
        }

        $default_categories = ConfigurationCdek::get('default_categories');

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
            $default_categories = ConfigurationCdek::get('default_categories');
            if (isset($default_categories[$product['id_category']]) && (int)$default_categories[$product['id_category']]['weight'] > 0) {
                $weight += $default_categories[$product['id_category']]['weight'] * $weight_unit;
            }
        }

        if ($weight == 0) {
            $weight = ConfigurationCdek::get('default_weight') * $weight_unit;
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
        ($volume_unit = ConfigurationCdek::get('volume_unit')) || $volume_unit = 1; // 1 - sm or 0.1 - mm

        $default_categories = ConfigurationCdek::get('default_categories');

        $p_dimension = $dimension == 'length' ? 'depth' : $dimension;

        if ((float)$product[$p_dimension]) {
            $cache[$cache_key] = (int)$product[$p_dimension] * $volume_unit;
        } elseif (isset($default_categories[$product['id_category']]) && $default_categories[$product['id_category']][$dimension]) {
            $cache[$cache_key] = (int)$default_categories[$product['id_category']][$dimension] * $volume_unit;
        } elseif (!$for_weight) {
            $cache[$cache_key] = (int)ConfigurationCdek::get('default_'.$dimension) * $volume_unit;
        } else {
            $cache[$cache_key] = 0;
        }

        return $cache[$cache_key];
    }

    /**
     * https://chilihelp.ru/portfolio/vychislenie-gabaritov-posylki-iz-neskolkikh-tovarov/
     */
    public function getTotalDimensions($cell)
    {
        $volume = 0;
        foreach ($this->products as $product) {
            $volume += $product['depth'] * $product['width'] * $product['height'] * $product['cart_quantity'];
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
}
