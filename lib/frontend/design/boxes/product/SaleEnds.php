<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\product;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;

class SaleEnds extends Widget
{

    public $file;
    public $params;
    public $settings;

    public function init()
    {
        parent::init();
    }

    public function run()
    {
        $params = Yii::$app->request->get();

        if (!$params['products_id']) {
            return '';
        }

        $product = \common\models\Specials::find()
                ->select('expires_date')
                ->where(['products_id' => (int)$params['products_id'], 'status' => 1])->asArray()->one();

        if (!$product) {
            $product['expires_date'] = \common\components\Salemaker::getFirstExpiringPromoTo($params['products_id']);
            if (!$product['expires_date']){
                return '';
            }
        }
        
        $interval = \common\helpers\Date::getLeftIntervalTo($product['expires_date']);
        
        if (!$interval || $interval->invert){
            return '';
        }
        
        return IncludeTpl::widget(['file' => 'boxes/product/sale-ends.tpl', 'params' => [
            'expiresDate' => \common\helpers\Date::date_short($product['expires_date']),
            'interval' => $interval,
            'id'=> $this->id
        ]]);
    }
}