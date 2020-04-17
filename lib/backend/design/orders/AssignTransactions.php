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

class AssignTransactions extends Widget {
    
    public $manager;
    public $orders_id;
            
    public function init(){
        parent::init();
    }
    
    public function run(){
        $modules = $this->manager->getPaymentCollection()->getTransactionSearchModules();
        $list = [];
        if ($modules){
            foreach($modules as $module){
                $list[$module->code] = $module->title;
            }
        }
        return $this->render('assign-transaction',[
            'list' => $list,
            'url' => Yii::$app->urlManager->createUrl(['orders/transactions', 'orders_id' => $this->orders_id])
        ]);
    }
}
