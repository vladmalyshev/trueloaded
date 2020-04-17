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

class FoundTransactionsList extends Widget {
    
    public $manager;
    public $transactions;
    public $payment;
    
    public function init(){
        parent::init();
    }
    
    public function run(){
                
        return $this->render('found-transactions-list',[
            'transactions' => $this->transactions,
            'payment' => $this->payment,
            'url' => Yii::$app->urlManager->createUrl(['orders/transactions', 'orders_id' => $this->manager->getOrderInstance()->order_id])
        ]);
    }
}
