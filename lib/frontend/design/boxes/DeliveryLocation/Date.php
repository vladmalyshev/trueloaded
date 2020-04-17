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

class Date extends Widget
{

    public $file;
    public $params;
    public $content;
    public $settings;

    public $child_locations = [];
    public $parent_locations = [];

    public function init()
    {
        parent::init();
    }

    public function run()
    {
        $id = \Yii::$app->request->get('id',0);

        $deliveryLocation = \common\models\SeoDeliveryLocation::find()->where(['id' => $id])->asArray()->one();

        if ($this->settings[0]['date_format']) {
            $dateFormat = $this->settings[0]['date_format'];
        } else {
            $dateFormat = \common\helpers\Date::DATE_FORMAT;
        }

        return date($dateFormat, strtotime($deliveryLocation['date_added']));
    }
}