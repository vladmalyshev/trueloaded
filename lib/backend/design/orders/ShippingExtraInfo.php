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

class ShippingExtraInfo extends Widget {
    
    public $manager;
    public $order;
            
    public function init(){
        parent::init();
    }
    
    public function run(){
        $extra = '';
        if (!empty($this->order->info['shipping_class'])){
            if (strpos($this->order->info['shipping_class'], 'collect') !== false){
                $shipping = $this->manager->getShippingCollection()->get('collect');
                if ($shipping){
                    $extra .= $shipping->getCollectAddress($this->order->info['shipping_class']);
                }
            } else {
                $moduleName = explode('_' , $this->order->info['shipping_class']);
                $shipping = $this->manager->getShippingCollection()->get($moduleName[0]);
                if(is_object($shipping) && method_exists($shipping, 'getAdditionalOrderParams')){
                    $extra .= $shipping->getAdditionalOrderParams([], $this->order->order_id, $this->order->table_prefix);
                }
            }
            return $this->render('shipping-extra-info', [
                'extra' => $extra
            ]);
        }
    }
}
