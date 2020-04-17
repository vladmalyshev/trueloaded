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

class Qty extends Widget {
    
    public $manager;    
    public $product;
    public $isPack = false;
    public $max;
    public $min = "data-min='1'";
    public $step = "data-step='1'";
    
    public function init(){
        parent::init();        
    }
    
    public function run(){
        
        $this->max = $this->product['stock_info']['max_qty'] + $this->product['reserved_qty'];
        
        if (\common\helpers\Acl::checkExtension('MinimumOrderQty', 'setLimit')){
            $this->min = \common\extensions\MinimumOrderQty\MinimumOrderQty::setLimit($this->product['stock_limits']);
        }
        
        if (\common\helpers\Acl::checkExtension('OrderQuantityStep', 'setLimit')){
            $this->step = \common\extensions\OrderQuantityStep\OrderQuantityStep::setLimit($this->product['stock_limits']);
        }
        if ($this->isPack){
            $insulator = new \backend\services\ProductInsulatorService( $this->product['id'], $this->manager);
            $_product = $insulator->getProduct();
            if ($_product){
                $this->product['data'] = $_product->getAttributes();
                $m = [
                    $this->max,
                    floor($this->product['data']['pack_unit'] ? $this->max/$this->product['data']['pack_unit']  :0 ),
                    floor($this->product['data']['packaging'] ? $this->max/$this->product['data']['packaging']  :0 ),
                ];
                $this->max = $m;
            }
            $this->min = "data-min='0'";//thais is becuase very cool extension!!!
        }
        
        return $this->render('qty', [
            'product' => $this->product,
            'isPack' => $this->isPack,
            'max' => $this->max,
            'min' => $this->min,
            'step' => $this->step,
        ]);
    }
    
}

