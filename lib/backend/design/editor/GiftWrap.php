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
use yii\base\Widget;

class GiftWrap extends Widget {
    
    public $manager;
    public $price;    
    public $qty;
    public $tax = 0;
    public $currency;   
    
    
    public function init(){
        parent::init();
    }
    
    public function run(){
        
        $currencies = Yii::$container->get('currencies');
        $prefix = ($this->price > 0 ? '+': '-');
        if (defined('MODULE_ORDER_TOTAL_GIFT_WRAP_TAX_CLASS')){
            $this->tax = \common\helpers\Tax::get_tax_rate(MODULE_ORDER_TOTAL_GIFT_WRAP_TAX_CLASS);
        }
        $cart = $this->manager->getCart();
        $currency_value = $currencies->currencies[$cart->currency]['value'];
        return $prefix . Formatter::price($this->price, $this->tax, 1, $cart->currency, $currency_value);
    }
    
}
