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

class Image extends Widget
{

    public $file;
    public $params;
    public $content;
    public $settings;

    public function init()
    {
        parent::init();
    }

    public function run()
    {
        global $current_category_id;
        $languages_id = \Yii::$app->settings->get('languages_id');

        if ($current_category_id > 0) {

            $category = \common\models\Categories::find()->andWhere(['categories_id' => (int)$current_category_id])->with(['description', 'platformSettings'])->asArray()->one();

            $category = array_merge($category, $category['description']);
            unset($category['description']);

            if (!empty($category['platformSettings']['categories_image_2'])) {
                $category = array_merge($category, $category['platformSettings']);
                unset($category['platformSettings']);
            }
            if (!$category['categories_image_2']) {
                return '';
            }

            $category['img'] = \common\classes\Images::getImageSet(
                $category['categories_image_2'],
                'Category hero',
                [
                    'alt' => $category['categories_name'],
                    'title' => $category['categories_name'],
                ],
                false
            );

            if ($category['img'] === false) {
                $category['img'] = 'no';
            }

        } elseif ($_GET['manufacturers_id'] > 0) {

            // Get the manufacturer name and image
            $manufacturer_query = tep_db_query("select m.manufacturers_name as categories_name,  m.manufacturers_image_2 as categories_image from " . TABLE_MANUFACTURERS . " m left join " . TABLE_MANUFACTURERS_INFO . " mi on (m.manufacturers_id = mi.manufacturers_id and mi.languages_id = '" . (int)$languages_id . "')  where m.manufacturers_id = '" . (int)$_GET['manufacturers_id'] . "'");
            $category = tep_db_fetch_array($manufacturer_query);

            if (!$category['categories_image']) {
                return '';
            }
            $category['img'] = \common\classes\Images::getImageSet(
                $category['categories_image'],
                'Brand hero',
                [
                    'alt' => $category['categories_name'],
                    'title' => $category['categories_name'],
                ],
                false
            );

            if ($category['img'] === false) {
                $category['img'] = 'no';
            }

        }


        return IncludeTpl::widget(['file' => 'boxes/catalog/image.tpl', 'params' => ['category' => $category]]);
    }
}