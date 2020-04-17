<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\design\editor;

use Yii;

class Formatter  {
    
    public $manager;
    
    public static function price($price, $tax, $qty, $currency, $currency_value){
        static $currencies = null;
        if (is_null($currencies)) $currencies = Yii::$container->get('currencies');
        $ppqr = (defined('PRODUCTS_PRICE_QTY_ROUND') && PRODUCTS_PRICE_QTY_ROUND == 'true');
        $ump = (USE_MARKET_PRICES == 'True' ? false : true);
        return $currencies->format($currencies->calculate_price_in_order(['currency' => $currency, 'products_price_qty_round' => $ppqr], $price, $tax, $qty), $ump, $currency, $currency_value);
    }
    
    public static function priceClear($price, $tax, $qty, $currency, $currency_value){
        static $currencies = null;
        if (is_null($currencies)) $currencies = Yii::$container->get('currencies');
        $ppqr = (defined('PRODUCTS_PRICE_QTY_ROUND') && PRODUCTS_PRICE_QTY_ROUND == 'true');
        $ump = (USE_MARKET_PRICES == 'True' ? false : true);
        return $currencies->format_clear($currencies->calculate_price_in_order(['currency' => $currency, 'products_price_qty_round' => $ppqr], $price, $tax, $qty), $ump, $currency, $currency_value);
    }
    
    public static function priceEx($price, $tax, $qty, $currency, $currency_value){
        if (defined('PRICE_WITH_BACK_TAX') && PRICE_WITH_BACK_TAX == 'True') {
            $price = \common\helpers\Tax::reduce_tax_always($price, $tax);
        }
        $tax = 0;
        return self::price($price, $tax, $qty, $currency, $currency_value);
    }
    
}
