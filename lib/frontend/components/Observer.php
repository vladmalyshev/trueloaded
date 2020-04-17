<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\components;


class Observer extends \yii\base\Component {
    
    /*
     * registering Observer Events to avoid using controolers 
     */
    public function registerEvents(){
        
        \common\models\promotions\PromotionService::setEventPromoCode();
        
        \common\models\promotions\PromotionService::checkEventPromoCode();
        
        if ($ext = \common\helpers\Acl::checkExtension('SupplierPurchase', 'allowed')){
            if ($ext::allowed()){
                $ext::recalculateTotal();
            }
        }
    }
    
}
