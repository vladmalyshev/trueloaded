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

class OrderStatuses extends Widget {
    
    public $manager;
    public $admin;
        
    public function init(){
        parent::init();
    }
    
    public function run(){
        $cart = $this->manager->getCart();
        if ($cart->order_id){
            return $this->render('order-statuses',[
                'url' => Yii::$app->urlManager->createAbsoluteUrl(array_merge(['editor/checkout', 'action'=> 'show_statuses'], Yii::$app->request->getQueryParams())),
            ]);
        }
    }
}
