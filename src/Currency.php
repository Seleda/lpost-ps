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

use \Db;
use \Exception;

class Currency
{
    private static $currencies = array(
        'RUB' => 1,
        'KZT' => 2,
        'USD' => 3,
        'EUR' => 4,
        'GBP' => 5,
        'CNY' => 6,
        'BYN' => 7,
        'UAH' => 8,
        'KGS' => 9,
        'AMD' => 10,
        'TRY' => 11,
        'THB' => 12,
        'KRW' => 13, // южнокорейская вона
        'KPW' => 13, // северокорейская вона
        'AED' => 14,
        'UZS' => 15, // Узбекский сум
        'MNT' => 16,
        'PLN' => 17,
        'AZN' => 18,
        'GEL' => 19
    );
    public static function getCurrency($currency)
    {
        if (is_numeric($currency)) {
            $iso_code = Db::getInstance()->getValue('SELECT `iso_code` FROM `'._DB_PREFIX_.'currency` WHERE `id_currency` = '.(int)$currency);
        } elseif ($currency instanceof Currency) {
            $iso_code = $currency->iso_code;
        } elseif (is_string($currency)) {
            $iso_code = $currency;
        } else {
            throw new Exception('Bad param');
        }

        if (array_key_exists($iso_code, self::$currencies)) {
            return self::$currencies[$iso_code];
        }

        return 0;
    }
}
