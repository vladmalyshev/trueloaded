<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;
use frontend\design\Info;

class Categories extends Widget
{

    public $file;
    public $params;
    public $settings;

    public function init()
    {
        parent::init();

        Info::addJsData(['widgets' => [
            $this->id => [ 'lazyLoad' => $this->settings[0]['lazy_load']]
        ]]);
    }

    public function run()
    {
        global $current_category_id;

        if (!$current_category_id) {
            return '';
        }

        $categories = \common\models\Categories::find()->andWhere(['parent_id' => (int)$current_category_id])->andWhere(['categories_status' => 1])
            ->joinWith('description')
            ->joinWith('currentPlatform', false)
            ->with('platformSettings')
            ->select('{{%categories}}.categories_id, parent_id, {{%categories}}.maps_id, {{%categories}}.categories_image, {{%categories}}.categories_image_3, {{%categories}}.show_on_home')
            ->orderBy("sort_order, categories_name");

        if ($this->settings[0]['max_items']) {
            $categories->limit((int)$this->settings[0]['max_items']);
        }

        $cats = $categories->asArray()->all();

        if (!$cats || !is_array($cats)) {
            return '';
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
            $cats[$k]['categories_h2_tag'] = \common\helpers\Html::fixHtmlTags($cats[$k]['categories_h2_tag']);
            $cats[$k]['categories_name'] = \common\helpers\Html::fixHtmlTags($cats[$k]['categories_name']);

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
