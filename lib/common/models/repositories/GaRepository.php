<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\models\repositories;

use common\models\Ga;

class GaRepository {

    public function getBasketDetectedInstance($customer_id, $basket_id) :Ga
    {
        $instance = Ga::findOne(['customers_id' => $customer_id, 'basket_id' => $basket_id, 'orders_id' => 0 ]);
        if (!$instance){
            Ga::deleteAll(['customers_id' => $customer_id, 'orders_id' => 0]);
            $instance = new Ga(['customers_id' => $customer_id, 'basket_id' => $basket_id]);
        }
        return $instance;
    }
    
    public function updateInstance(Ga $instance,  array $params, $order_id = 0){
        if ($instance){
            $instance->setAttribute('orders_id', (int)$order_id);
            $instance->setAttributes($params);
            $instance->validate();
            $instance->save();
        }
    }
    
    public function getInstanceByOrderId(int $orderId){
        return Ga::find()->where(['orders_id' => $orderId])->one();
    }

    public function getInstanceByBasketId(int $customer_id, int $basket_id){
        return Ga::find()->where(['customers_id' => $customer_id, 'basket_id' => $basket_id ])->one();
    }
}