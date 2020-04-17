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
use backend\models\Admin;

class OrderStatusesList extends Widget {
    
    public $manager;
    public $admin;
    public $hide = false;
        
    public function init(){
        parent::init();
    }
    
    public function run(){
        $enquire = [];
        $orders_statuses = [];
        $detectedStatus = NULL;
        if ($this->manager->hasCart() && $this->manager->isInstance()){
            $cart = $this->manager->getCart();
            $type = $this->manager->getInstanceType();
            if ($cart->order_id){
                $_admins = [];
                $enquire = $this->manager->getOrderInstance()->getStatusHistoryARModel()
                        ->joinWith('group')->where(['orders_id' => $cart->order_id])
                        ->asArray()->all();
            }
            
            if ($type == 'order'){
                $totals = \yii\helpers\ArrayHelper::index($this->manager->getTotalOutput(false), 'code');
                if ($totals['ot_due']){
                    if (defined('ORDER_STATUS_PART_AMOUNT') && (int) ORDER_STATUS_PART_AMOUNT > 0 && defined('ORDER_STATUS_FULL_AMOUNT') && (int)ORDER_STATUS_FULL_AMOUNT >0){
                        $detectedStatus = floatval($totals['ot_due']['value_inc_tax']) >0 ? ORDER_STATUS_PART_AMOUNT : ORDER_STATUS_FULL_AMOUNT;
                    }
                }
            }
            
            if (is_null($detectedStatus)){
                $oStatus = $this->manager->getOrderInstance()->getARModel()
                        ->select('orders_status')
                        ->where(['orders_id' => $cart->order_id])
                        ->one();
                $detectedStatus = $oStatus->orders_status ?? DEFAULT_ORDERS_STATUS_ID;
            }
            
            if ($type){
                $orders_statuses = $this->getStatuses();
            }
            
            return $this->render('order-statuses-list',[
                'CommentsWithStatus' => true,
                'enquire' => $enquire,
                'orders_statuses' => $orders_statuses,
                'hide' => $this->hide,
                'status' => $detectedStatus,
                'manager' => $this->manager
            ]);
        }
    }
    
    public function getStatuses(){
        $type = \yii\helpers\Inflector::camelize($this->manager->getInstanceType());
        if (class_exists('\common\helpers\\'. $type)){
            $type = '\common\helpers\\'.$type;
            return $type::getStatusList();
        }
        return [];
    }
}
