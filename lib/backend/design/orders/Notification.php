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

class Notification extends Widget {
    
    public $manager;
            
    public function init(){
        parent::init();
    }
    
    public function run(){
        $messages = [];
        if ($this->manager->getOrderSplitter()->hasUnclosedRma($this->manager->getOrderInstance()->order_id)){
            $messages[] = ['type' => 'danger', 'message' => TEXT_CHECK_UNCLOSED_CREDITNNOTE];
        }
        
        if ($messages){
            return $this->render('notification', [
                'messages' => $messages
            ]);
        }
    }
}
