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

class ExternalOrders extends Widget {
    
    public $order;
            
    public function init(){
        parent::init();        
    }
    
    public function run(){
        if ($this->order->info['external_orders_id']){
            return $this->render('external-orders', [
                'extra' => $this->order->info['external_orders_id'],
            ]);
        }
    }
}
