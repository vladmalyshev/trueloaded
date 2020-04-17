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

class Price extends Widget {
    
    public $manager;
    public $price;
    public $price_variant;
    public $qty;
    public $tax;
    public $currency;
    public $field;
    public $classname;
    public $isEditInGrid = false;
    
    
    public function init(){
        parent::init();
        if (!is_null($this->price_variant)){
            if (is_null($this->price)){
                $this->price = $this->price_variant;
            }
        }
    }
    
    public function run(){
        
        $currencies = Yii::$container->get('currencies');
        
        return $this->render('price', [
            'isEditInGrid' => $this->isEditInGrid,
            'currencies' => $currencies,
            'price' => $this->price,
            'tax' => $this->tax,
            'qty' => $this->qty,
            'currency' => $this->currency,
            'field' => $this->field,
            'class' => $this->classname,
            'currency_value' => $currencies->currencies[$this->currency]['value'],
        ]);
    }
    
}
