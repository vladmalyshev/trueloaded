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
use common\helpers\Acl;
use backend\models\Admin;

class StatusTable extends Widget {
    
    public $enquire;
    public $order;
    public $manager;
            
    public function init(){
        parent::init();
    }
    
    public function getHistoryRows(array $enquirer){
        $_admins = $history = [];
        foreach($enquirer as $orders_history){
            $orders_history['admin'] = '';
            if ($orders_history['admin_id'] > 0) {
                if (!array_key_exists($orders_history['admin_id'], $_admins)){
                    $admin[$orders_history['admin_id']] = new Admin($orders_history['admin_id']);
                }
                $orders_history['admin'] = $admin[$orders_history['admin_id']]->getInfo('admin_firstname') .' '
                        .$admin[$orders_history['admin_id']]->getInfo('admin_lastname');
            }

            $history[] = $orders_history;
        }
        return $history;
    }
    
    public function run(){
        $smsEnabled = false;
        if (Acl::checkExtensionAllowed('SMS','showOnOrderPage') && $sms = Acl::checkExtension('SMS', 'allowed')){
            \common\helpers\Translation::init('admin/sms');
            $smsEnabled = true;
        }
        
        return $this->render('status-table', [
            'manager' => $this->manager,
            'smsEnabled' => $smsEnabled,
            'orders_history_items' => $this->getHistoryRows($this->enquire),
        ]);
    }
}
