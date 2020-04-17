<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\DeliveryLocation;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;

class FeaturedList extends Widget
{

    public $file;
    public $params;
    public $content;
    public $settings;

    public $featured_locations = [];

    public function init()
    {
        if ( isset(Yii::$app->controller->deliveryLocationData) && is_array(Yii::$app->controller->deliveryLocationData) ) {
            if ( isset(Yii::$app->controller->deliveryLocationData['location_featured_list']) && count(Yii::$app->controller->deliveryLocationData['location_featured_list'])>0 ){
                $this->featured_locations = Yii::$app->controller->deliveryLocationData['location_featured_list'];
            }
        }


        parent::init();
    }

    public function run()
    {
        return IncludeTpl::widget(['file' => 'boxes/delivery-location/locations-featured-list.tpl', 'params' => [
            'locations_list' => $this->featured_locations,
        ]]);
    }
}