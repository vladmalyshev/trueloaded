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

class Rating extends Widget
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

        $rating = tep_db_fetch_array(tep_db_query("
                select count(*) as count, 
                AVG(reviews_rating) as average 
                from " . TABLE_REVIEWS . " 
                where products_id = '" . (int)$params['products_id'] . "' and status
            "));

        if ($rating['count']) {
            \frontend\design\JsonLd::addData(['Product' => [
                'aggregateRating' => [
                    '@type' => 'AggregateRating',
                    'ratingValue' => round($rating['average']),
                    'ratingCount' => $rating['count'],
                ]
            ]], ['Product', 'aggregateRating']);
        }

        return IncludeTpl::widget(['file' => 'boxes/product/rating.tpl', 'params' => [
            'rating' => round($rating['average']),
            'count' => $rating['count'],
            'settings' => $this->settings
        ]]);
    }
}