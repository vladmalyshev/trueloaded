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
use frontend\design\Info;

class ProductCategories extends Widget
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
        if (!Yii::$app->request->get('id')) {
            return $this->hideBox();
        }

        $idList = \common\models\SeoDeliveryLocationCategories::find()
            ->where(['id' => Yii::$app->request->get('id')])
            ->asArray()
            ->all();

        if (!$idList) {
            return $this->hideBox();
        }

        $ids = [];
        foreach ($idList as $item) {
            $ids[] = $item['categories_id'];
        }


        $categories = \common\models\Categories::find()->andWhere(['{{%categories}}.categories_id' => $ids])->andWhere(['categories_status' => 1])
            ->joinWith('description')
            ->joinWith('currentPlatform', false)
            ->with('platformSettings')
            ->select('{{%categories}}.categories_id, parent_id, {{%categories}}.maps_id, {{%categories}}.categories_image, {{%categories}}.categories_image_3, {{%categories}}.show_on_home')
            ->orderBy("sort_order, categories_name");

        if ($this->settings[0]['max_products']) {
            $categories->limit((int)$this->settings[0]['max_products']);
        }

        $cats = $categories->asArray()->all();

        if (!$cats || !is_array($cats)) {
            return $this->hideBox();
        }

        foreach ($cats as $k => $category) {
            if (!Info::themeSetting('show_empty_categories') && \common\helpers\Categories::count_products_in_category($category['categories_id']) == 0) {
                unset($cats[$k]);
                continue;
            }

            $cats[$k]['link'] = Yii::$app->urlManager->createUrl(['catalog', 'cPath' => $category['categories_id']]);
            if (isset($category['description'])) {
                $cats[$k]['categories_h2_tag'] = $category['description']['categories_h2_tag'];
                $cats[$k]['categories_name'] = $category['description']['categories_name'];
                unset($cats[$k]['description']);
            }

            if (!empty($category['platformSettings']['categories_image'])) {
                $img = $category['platformSettings']['categories_image'];
            } else {
                $img = $category['categories_image'];
            }
            $cats[$k]['img'] = \common\classes\Images::getImageSet(
                $img,
                'Category gallery',
                [
                    'alt' => $cats[$k]['categories_name'],
                    'title' => $cats[$k]['categories_name'],
                ],
                Info::themeSetting('na_category', 'hide'),
                $this->settings[0]['lazy_load']
            );

            unset($cats[$k]['platformSettings']);
        }

        $cats = array_values($cats);

        if (count($cats) == 0) {
            return false;
        }

        return IncludeTpl::widget([
            'file' => 'boxes/categories.tpl',
            'params' => [
                'categories' => $cats,
                'themeImages' => DIR_WS_THEME_IMAGES,
                'lazy_load' => $this->settings[0]['lazy_load'],
            ]
        ]);
    }
}