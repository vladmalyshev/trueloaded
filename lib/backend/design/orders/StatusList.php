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
use common\helpers\Acl;
use backend\models\Admin;

class StatusList extends Widget {
    
    public $order;
    public $manager;
            
    public function init(){
        parent::init();        
    }
    
    public function run(){
        
        $ordersStatuses = [];
        $ordersStatusesOptions = [];
        $orders_statuses = array();
        $orders_status_array = array();
        
        $ordersStatuses = \common\helpers\Order::getStatusList(false, true, $this->order->info['order_status']);
        
        foreach(\common\helpers\Order::getStatuses(true, $this->order->info['order_status']) as $orders_status){
            if (is_array($orders_status->statuses)){
                foreach($orders_status->statuses as $status){
                    if ($status->order_evaluation_state_id > 0) {
                        $ordersStatusesOptions[$status->orders_status_id]['evaluation_state_id'] = $status->order_evaluation_state_id;
                    }
                }
            }
        }
        
        return $this->render('status-list', [
            'manager' => $this->manager,
            'order' => $this->order,
            'ordersStatuses' => $ordersStatuses,
            'ordersStatusesOptions' => $ordersStatusesOptions,
        ]);
    }
}
