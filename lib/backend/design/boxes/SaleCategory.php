<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\design\boxes;

use Yii;
use yii\base\Widget;

class SaleCategory extends Widget
{

    public $id;
    public $params;
    public $settings;
    public $visibility;

    public function init()
    {
        parent::init();
    }

    public function run()
    {

        $promotions = \common\models\promotions\Promotions::find()
            ->andWhere('promo_date_expired >= curdate() or promo_date_expired = "0000-00-00" or promo_date_expired is null')
            ->andWhere('promo_class = "specials_category"')
            ->orderBy('promo_priority')
            ->all();

        $promotion = [];
        foreach ($promotions as $promo) {
            $promotion[] = [
                'id' => $promo->getAttribute('promo_id'),
                'label' => $promo->getAttribute('promo_label')
            ];
        }

        return $this->render('sale-category.tpl', [
            'id' => $this->id,
            'params' => $this->params,
            'settings' => $this->settings,
            'visibility' => $this->visibility,
            'promotions' => $promotion
        ]);
    }
}