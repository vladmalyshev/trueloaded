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

class CrossPages extends Widget
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
        if (!Yii::$app->request->get('id')) {
            return $this->hideBox();
        }

        $idList = \common\models\SeoDeliveryLocationCrosspages::find()
            ->where([
                'id' => Yii::$app->request->get('id'),
            ])
            ->limit($this->settings[0]['max_products'] ? $this->settings[0]['max_products'] : 5)
            ->asArray()
            ->all();

        if (!$idList) {
            return $this->hideBox();
        }

        $location_list = [];
        foreach ($idList as $data) {
            $item = \common\helpers\SeoDeliveryLocation::getItem($data['crosspage_id'], (int)\common\classes\platform::currentId(), " AND fd.status=1 ");
            $item['href'] = \Yii::$app->urlManager->createUrl([FILENAME_DELIVERY_LOCATION,'id'=>$data['crosspage_id']]);

            $location_list[] = $item;
        }

        return IncludeTpl::widget(['file' => 'boxes/delivery-location/list-by-page.tpl', 'params' => [
            'locations_list' => $location_list,
        ]]);
    }
}