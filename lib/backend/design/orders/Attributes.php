<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\design\orders;


use Yii;
use yii\base\Widget;

class Attributes extends Widget {
    
    public $product;
    public $currency;
    public $currency_value;
            
    public function init(){
        parent::init();        
    }
    
    public function run(){
        $attributes = [];
        if ( isset($this->product['attributes']) && is_array($this->product['attributes']) ){
            foreach ($this->product['attributes'] as $attribute){
                $attribute['display_price'] = '';
                if ($attribute['price']){
                    if ( strpos($attribute['prefix'], '%')!==false ) {
                        $attribute['display_price'] = substr($attribute['prefix'],0,1).\common\helpers\Output::percent($attribute['price']);
                    }else{
                        $attribute['display_price'] = \backend\design\editor\Formatter::price($attribute['price'], $this->product['tax'], $this->product['qty'], $this->currency, $this->currency_value);
                    }
                }
                $attributes[] = $attribute;
            }
        }
        return $this->render('attributes',[
            //'product' => $this->product,
            'attributes' => $attributes,
            //'currency' => $this->currency,
            //'currency_value' => $this->currency_value,
        ]);
    }
}
