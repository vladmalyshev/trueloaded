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

class AddressDetails extends Widget {
    
    public $manager;
    public $order;
            
    public function init(){
        parent::init();        
    }
    
    public function run(){
        
        return $this->render('address-details', [
            'order' => $this->order,
            'sameAddress' => ($this->order->delivery == $this->order->billing && $this->order->delivery['format_id'] == $this->order->billing['format_id']),
            'manager' => $this->manager,
            ]);
    }
}
