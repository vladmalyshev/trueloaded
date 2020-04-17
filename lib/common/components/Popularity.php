<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\components;

use Yii;
use common\models\Products;
use common\models\ProductsPopularity;

class Popularity {

    private $days = 30;

    public function __construct() {
        if (defined('POPULARITY_DAYS')) {
            $this->days = intval(POPULARITY_DAYS);
        }
        ProductsPopularity::deleteAll("viewed_date < now() - interval {$this->days} day");
    }

    public function clearCurrentPopularity() {
        Products::updateAll(['products_popularity' => 0]);
    }

    public function process() {
        $this->clearCurrentPopularity();

        $result = (new \yii\db\Query)->select(['count(o.orders_id) as amount', 'op.products_id', 'if(pp.products_viewed>0,pp.products_viewed,1) as products_viewed'])
                ->from(['orders_products op'])
                ->innerJoin('products p', 'op.products_id = p.products_id')
                ->innerJoin('orders o', 'o.orders_id = op.orders_id')
                ->innerJoin('products_popularity pp', 'p.products_id = pp.products_id')
                ->where('o.date_purchased >= now() - interval :days day', [':days' => $this->days])
                ->groupBy('op.products_id')
                ->all();
        if (is_array($result) && count($result) > 0) {
            foreach ($result as $data) {
                try {
                    $popularity = $data['amount'] * $data['amount'] / $data['products_viewed'];
                    Products::updateAll(['products_popularity' => $popularity], 'products_id = "' . (int) $data['products_id'] . '"');
                } catch (\Exception $ex) {
                    
                }
            }
        }
    }

    public function updateProductVisit($products_id) {
        if ($products_id && !isset($_SESSION['viewed_products'][(int) $products_id])) {
            $todayVisit = ProductsPopularity::find()->where('products_id=:prid and viewed_date = CURDATE()', [':prid' => (int) $products_id ])->one();
            if ($todayVisit){
                $todayVisit->products_viewed = $todayVisit->products_viewed + 1;
            } else {
                $todayVisit = new ProductsPopularity();
                $todayVisit->setAttributes([
                    'products_viewed' => 1,
                    'products_id' => (int)$products_id,
                    'viewed_date' => date("Y-m-d"),
                ], false);
            }            
            $todayVisit->save(false);
            $_SESSION['viewed_products'][(int) $products_id] = (int) $products_id;
        }
    }

}
