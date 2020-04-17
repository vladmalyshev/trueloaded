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

class Tax extends Widget {
    
    public $manager;
    public $tax_address;
    public $tax_class_array;
    public $product;
    public $onchange;
    public $wrap = false;
    public $uprid = ''; //use for products in bundle
    
    public function init(){
        parent::init();        
    }
    
    public function run(){
        
        if (!$this->uprid){
            $this->uprid = $this->product['current_uprid'] ?? $this->product['products_id'];
        }
        
        return $this->render('tax', [
            'product' => $this->product,
            'tax_address' => $this->tax_address,
            'tax_class_array' => $this->tax_class_array,
            'onchange' => $this->onchange,
            'wrap' => $this->wrap,
            'uprid' => $this->uprid,
        ]);
    }
    
}
