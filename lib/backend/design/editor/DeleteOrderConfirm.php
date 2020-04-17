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

class DeleteOrderConfirm extends Widget {
    
    public $manager;    
        
    public function init(){
        parent::init();
    }
    
    public function run(){
        
        if($this->manager->isInstance() && $this->manager->getCart()->order_id){
            if (\common\helpers\Order::is_stock_updated(intval($this->manager->getCart()->order_id))) {
                $restock_disabled = '';
                $restock_selected = ' checked ';
            } else {
                $restock_disabled = ' disabled="disabled" readonly="readonly" ';
                $restock_selected = '';
            }
            $params = Yii::$app->request->getQueryParams();
            unset($params['action']);
            return $this->render('delete-order-confirm', [
                'url' => Yii::$app->urlManager->createAbsoluteUrl(array_merge(['editor/checkout'], $params)),
                'restock_disabled' => $restock_disabled,
                'restock_selected' => $restock_selected
            ]);
        }
    }

}
