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

class LocationList extends Widget
{

    public $file;
    public $params;
    public $content;
    public $settings;

    public $child_locations = [];
    public $parent_locations = [];

    public function init()
    {
        if ( isset(Yii::$app->controller->deliveryLocationData) && is_array(Yii::$app->controller->deliveryLocationData) ) {
            $this->parent_locations = Yii::$app->controller->deliveryLocationData['parents'];
            $this->child_locations = Yii::$app->controller->deliveryLocationData['locations_list'];
        }

        parent::init();
    }

    public function run()
    {
        return IncludeTpl::widget(['file' => 'boxes/delivery-location/locations-list.tpl', 'params' => [
            'parents' => $this->parent_locations,
            'locations_list' => $this->child_locations,
        ]]);
    }
}