<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\controllers;

use Yii;
use common\models\promotions\Promotions;
use common\models\promotions\PromotionsSets;
use common\models\promotions\PromotionsConditions;
use common\models\promotions\PromotionsSetsConditions;
use common\models\promotions\PromotionService;

class PromotionsController extends Sceleton
{

    public function actionIndex()
    {
        $promotions = Promotions::getCurrentPromotions(\common\classes\platform::currentId())->all();
        $info = [];
        $hasPromo = false;
        if ($promotions){
            $service = new PromotionService;
            foreach($promotions as $pIdx=> &$promo){
                if (is_array($promo->sets) && count($promo->sets)){
                    $hasPromo = true;
                    if ($promo->promo_date_start) {
                        $promo->promo_date_start = date(DATE_FORMAT_DATEPICKER_PHP, strtotime($promo->promo_date_start));
                    }
                    if ($promo->promo_date_expired) {
                        $promo->promo_date_expired = date(DATE_FORMAT_DATEPICKER_PHP, strtotime($promo->promo_date_expired));
                    }
                    $class = $service($promo->promo_class);
                    $class->loadSettings(['platform_id' => (int)PLATFORM_ID, 'promo_id' => $promo->promo_id]);
                    $info[$promo->promo_id] = $class->getPromotionInfo($promo->promo_id);
                } else {
                    unset($promotions[$pIdx]);
                }
            }
            
        }
        
        return $this->render('index', [
            'params' => [
                'promotions' => ($hasPromo?$promotions:false),
                'info' => $info,
            ],
            'promotions' => ($hasPromo?$promotions:false),
            'info' => $info,
        ]);
    }
    
    public function actionActions(){
        
        return $this->render('actions', [
        ]);
    }

}
