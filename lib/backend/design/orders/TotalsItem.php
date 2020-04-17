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

class TotalsItem extends Widget {
    
    public $order;
    public $manager;
            
    public function init(){
        parent::init();        
    }
    
    public function run(){
        
        $pData = false;
        if($this->order->info['pointto'] > 0){
            $pData = \common\models\CollectionPoints::findOne($this->order->info['pointto']);
        }

        $parent = $this->order->getParent();
        
        return $this->render('totals-item', [
            'items' => array_sum(\yii\helpers\ArrayHelper::getColumn($this->order->products, 'qty')),
            'order' => $this->order,
            'shipping_weight' => $this->order->info['shipping_weight'],
            'parent' => $parent,
            'pData' => $pData,
        ]);
    }
}
