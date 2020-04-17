<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\catalog;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;
use common\models\promotions\PromotionService;
use common\models\promotions\Promotions;

class PromoOnCategory extends Widget {

    public $file;
    public $params;
    public $settings;

    public function init() {
        parent::init();
    }

    public function run() {
        global $current_category_id;
        if (!$current_category_id){
            return '';
        }
        $promotions = Promotions::getCurrentPromotions(\common\classes\platform::currentId())->all();
        if (!$promotions){
            return '';
        }

        $info = [];
        $service = new PromotionService;
        foreach($promotions as $pIdx=> &$promo){
            if (is_array($promo->sets) && count($promo->sets)){
                if ($promo->promo_date_start) {
                    $promo->promo_date_start = date(DATE_FORMAT_DATEPICKER_PHP, strtotime($promo->promo_date_start));
                }
                if ($promo->promo_date_expired) {
                    $promo->promo_date_expired = date(DATE_FORMAT_DATEPICKER_PHP, strtotime($promo->promo_date_expired));
                }
                $class = $service($promo->promo_class);
                $class->loadSettings(['platform_id' => (int)PLATFORM_ID, 'promo_id' => $promo->promo_id]);
                if (method_exists($class, 'setIdentifier'))
                    $class->setIdentifier($current_category_id, PromotionService::SLAVE_CATEGORY);

                $text = $class->getPromotionInfo($promo->promo_id);
                if ($text) {
                    $info[$promo->promo_id] = $text;
                }

            } else {
                unset($promotions[$pIdx]);
            }
        }

        if (!count($info)) {
            return '';
        }
        return IncludeTpl::widget([
            'file' => 'boxes/catalog/promo-on-category.tpl',
            'params' => [
                'promotions' => $promotions,
                'info' => $info,
            ]
        ]);

    }

}
