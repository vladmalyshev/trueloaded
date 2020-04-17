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

class AdminCarts extends Widget {
    
    public $manager;
    public $admin;
    
    public function init(){
        parent::init();
    }    
        
    public function run(){
        
        $unsavedCarts = $this->admin->getVirtualCartIDs();
        if (is_array($unsavedCarts) && count($unsavedCarts)){
            $_carts = $this->admin->getCarts();
            foreach ($unsavedCarts as $_ids) {
                $_customerId = $_carts[$_ids]['customers_id'] ?? 0;
                
                $admin_choice[] = $this->render('mini', [
                    'cart' => $_ids,
                    'basketId' => $_carts[$_ids]['basket_id'],
                    'orders_id' => $_carts[$_ids]['order_id'],
                    'customer' => ($_customerId? \common\helpers\Customer::getCustomerData($_customerId):''),
                    'opened' => ($_ids == $this->admin->getCurrentCartID()),
                    ]
                );
            }
            return $this->render('admin-carts', [
                'admin_choice' => $admin_choice,
                'saved' => $this->admin->isCartSaved($this->admin->getCurrentCartID()),
            ]);
        }
    }
    
}
