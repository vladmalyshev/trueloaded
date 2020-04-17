<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;
use common\models\promotions\PromotionService;

class PredefinedPromotion extends Widget {

    public $file;
    public $params;
    public $settings;

    public function init() {
        parent::init();
    }

    public function run() {
        
        $params = Yii::$app->request->get();
    
        if (!$params['products_id']){
            return '';
        }
        
        if (isset($this->settings[0]['promo_id']) && (int) $this->settings[0]['promo_id'] > 0) {
            $promoId = (int) $this->settings[0]['promo_id'];
            $promo = \common\models\promotions\Promotions::findOne(['promo_id' => $promoId]);
            if ($promo){
                if ($promo->promo_class == 'multidiscount'){
                    return product\Promotions::widget(['params' => ['preview' => true]]);
                }
                $service = new PromotionService();
                $promoObj = $service($promo->promo_class);
                if (method_exists($promoObj, 'getPromotionToProduct')){
                    $promoObj->loadSettings(['platform_id' => (int) \common\classes\platform::currentId(), 'promo_id' => $promo->promo_id]);
                    return $promoObj->getPromotionToProduct($promo, (int)$params['products_id']);
                }
            }
        }
    }
}
