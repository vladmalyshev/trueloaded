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

class LocationHeadlineImage extends Widget
{

    public $file;
    public $params;
    public $content;
    public $settings;

    public $image = [];

    public function init()
    {
        if ( isset(Yii::$app->controller->deliveryLocationData) && is_array(Yii::$app->controller->deliveryLocationData) ) {
            if ( Yii::$app->controller->deliveryLocationData['image_headline_src'] ) {
                $this->image['src'] = Yii::$app->controller->deliveryLocationData['image_headline_src'];
                $this->image['width'] = Yii::$app->controller->deliveryLocationData['image_headline_width'];
                $this->image['height'] = Yii::$app->controller->deliveryLocationData['image_headline_height'];
                $this->image['alt'] = Yii::$app->controller->deliveryLocationData['image_headline_alt'];
                $this->image['href'] = Yii::$app->controller->deliveryLocationData['image_headline_href'];
            }
        }
        parent::init();
    }

    public function run()
    {
        if ( $this->image ) {
            return IncludeTpl::widget(['file' => 'boxes/delivery-location/location-headline-image.tpl', 'params' => [
                'image' => $this->image
            ]]);
        }
        return '';
    }
}