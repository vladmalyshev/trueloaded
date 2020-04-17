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

class LocationLongDescription extends Widget
{

    public $file;
    public $params;
    public $content;
    public $settings;

    public $box_text = '';

    public function init()
    {
        if ( isset(Yii::$app->controller->deliveryLocationData) && is_array(Yii::$app->controller->deliveryLocationData) ) {
            $this->box_text = Yii::$app->controller->deliveryLocationData['location_description_long'];
        }
        parent::init();
    }

    public function run()
    {
        if ( $this->box_text ) {
            return IncludeTpl::widget(['file' => 'boxes/delivery-location/location-long-description.tpl', 'params' => ['text' => $this->box_text]]);
        }
        return '';
    }
}