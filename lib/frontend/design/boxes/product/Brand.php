<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\product;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;

class Brand extends Widget
{

    public $file;
    public $params;
    public $settings;

    public function init()
    {
        parent::init();
    }

    public function run()
    {
        $languages_id = \Yii::$app->settings->get('languages_id');
        $params = Yii::$app->request->get();

        if (!$params['products_id']) {
            return '';
        }

        $products = Yii::$container->get('products');

        if(!$products->checkAttachedDetails($params['products_id'], $products::TYPE_MANUFACTURER)){
            $manufacture = tep_db_fetch_array(tep_db_query("
          select m.manufacturers_name, m.manufacturers_image, mi.manufacturers_url, p.manufacturers_id
          from " . TABLE_PRODUCTS . " p, " . TABLE_MANUFACTURERS . " m, " . TABLE_MANUFACTURERS_INFO . " mi
          where 
            p.products_id = '" . (int)$params['products_id'] . "' and 
            p.manufacturers_id = m.manufacturers_id and
            p.manufacturers_id = mi.manufacturers_id and
            mi.languages_id = '" . (int)$languages_id . "'
          "));
            $products->attachDetails($params['products_id'], [$products::TYPE_MANUFACTURER => $manufacture]);
        } else {
            $product = $products->getProduct($params['products_id']);
            $manufacture = $product[$products::TYPE_MANUFACTURER];
        }

        if ($manufacture['manufacturers_name']) {
            \frontend\design\JsonLd::addData(['Product' => [
                'brand' => [
                    '@type' => 'Thing',
                    'name' => $manufacture['manufacturers_name']
                ],
            ]]);
        }
        if ($manufacture['manufacturers_image'] && is_file(\common\classes\Images::getFSCatalogImagesPath() . $manufacture['manufacturers_image'])) {
            \frontend\design\JsonLd::addData(['Product' => [
                'brand' => [
                    'image' => Yii::$app->urlManager->createAbsoluteUrl($manufacture['manufacturers_image'])
                ],
            ]]);
        }

        return IncludeTpl::widget(['file' => 'boxes/product/brand.tpl', 'params' => [
            'manufacture' => $manufacture,
            'params'=> $this->params,
            //'link' => tep_href_link('catalog/manufacturers', 'manufacturers_id=' . $manufacture['manufacturers_id'])
            'link' => tep_href_link(FILENAME_ADVANCED_SEARCH_RESULT, 'brand=' . $manufacture['manufacturers_id'])
        ]]);
    }
}