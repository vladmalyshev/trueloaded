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

class StatusTable extends Widget {
    
    public $enquire;
    public $order;
    public $manager;
            
    public function init(){
        parent::init();
    }
    
    public function run(){
        $this->enquire = $this->order->getStatusHistoryARModel()
                ->joinWith('group')->where(['orders_id' => $this->order->order_id])
                ->asArray()->all();
                
        return $this->render('status-table', [
            'manager' => $this->manager,
            'enquire' => $this->enquire
        ]);
    }
}
