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

class DeliveryLocations extends Widget
{

    public $file;
    public $params;
    public $content;
    public $settings;

    public function init()
    {
        parent::init();
    }

    public function hideBox(){
        if (!$this->settings[0]['hide_parents'] || $this->settings[0]['hide_parents'] == 1) {
            return '';
        } else {
            return IncludeTpl::widget(['file' => 'boxes/hide-box.tpl','params' => [
                'settings' => $this->settings,
                'id' => $this->id
            ]]);
        }
    }

    public function run()
    {
        global $current_category_id;

        $idList = \common\models\SeoDeliveryLocationCategories::find()
            ->where(['categories_id' => $current_category_id])
            ->limit($this->settings[0]['max_products'] ? $this->settings[0]['max_products'] : 5)
            ->asArray()
            ->all();

        if (!$idList) {
            return $this->hideBox();
        }

        $location_list = [];
        foreach ($idList as $data) {
            $item = \common\helpers\SeoDeliveryLocation::getItem($data['id'], (int)\common\classes\platform::currentId(), " AND fd.status=1 ");
            $item['href'] = \Yii::$app->urlManager->createUrl([FILENAME_DELIVERY_LOCATION,'id'=>$data['crosspage_id']]);

            $location_list[] = $item;
        }

        if (count($location_list) == 0) {
            return $this->hideBox();
        }

        return IncludeTpl::widget(['file' => 'boxes/catalog/delivery-locations.tpl', 'params' => [
            'locations_list' => $location_list,
        ]]);
    }
}