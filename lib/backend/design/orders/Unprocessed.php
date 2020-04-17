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

class Unprocessed extends Widget {
    
    public $order;
    public $manager;
            
    public function init(){
        parent::init();        
    }
    
    public function run(){
        
        $orders_not_processed = \common\models\Orders::find()->select('orders_id')
            ->where(['and', ['orders_status' => intval(DEFAULT_ORDERS_STATUS_ID)], ['!=', 'orders_id', $this->order->order_id]])
            ->orderBy('orders_id DESC')->limit(1)->one();
        if ($orders_not_processed){
            return $this->render('unprocessed', ['order_id' => $orders_not_processed->orders_id]);
        }
    }
}
