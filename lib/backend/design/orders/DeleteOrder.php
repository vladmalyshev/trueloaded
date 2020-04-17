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

class DeleteOrder extends Widget {
    
    public $order;    
            
    public function init(){
        parent::init();        
    }
    
    public function run(){
        if (\common\helpers\Order::is_stock_updated(intval($this->order->order_id))) {
            $restock_disabled = '';
            $restock_selected = ' checked ';
        } else {
            $restock_disabled = ' disabled="disabled" readonly="readonly" ';
            $restock_selected = '';
        }
        return $this->render('delete-order', [
            'order' => $this->order,
            'restock_selected' => $restock_selected,
            'restock_disabled' => $restock_disabled,
        ]);
    }
}
