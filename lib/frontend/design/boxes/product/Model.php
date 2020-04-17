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

class Model extends Widget
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
        $params = Yii::$app->request->get();

        if (!$params['products_id']) {
            return '';
        }

        $products = Yii::$container->get('products');
        $data = $products->getProduct($params['products_id']);

        if ($data['model'] && $this->settings[0]['show_model'] != 'no') {
            \frontend\design\JsonLd::addData(['Product' => [
                'sku' => $data['model']
            ]]);
        }
        if ($data['ean'] && $this->settings[0]['show_ean'] != 'no') {
            \frontend\design\JsonLd::addData(['Product' => [
                'gtin13' => $data['ean']
            ]]);
        }
        if ($data['isbn'] && $this->settings[0]['show_isbn'] != 'no') {
            \frontend\design\JsonLd::addData(['Product' => [
                'isbn' => $data['isbn']
            ]]);
        }
        if ($data['upc'] && $this->settings[0]['show_upc'] != 'no') {
            \frontend\design\JsonLd::addData(['Product' => [
                'upc' => $data['upc']
            ]]);
        }

        return IncludeTpl::widget(['file' => 'boxes/product/model.tpl', 'params' => [
            'data' => $data,
            'settings' => $this->settings[0]
        ]]);

    }
}