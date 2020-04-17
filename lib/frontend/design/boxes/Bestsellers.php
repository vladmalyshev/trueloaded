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
use common\helpers\Product;
use common\classes\platform;

class Bestsellers extends Widget
{
    use \common\helpers\SqlTrait;

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

        if ($this->settings[0]['params']) {
            $max = $this->settings[0]['params'];
        } else {
            $max = 4;
        }

        $q = new \common\components\ProductsQuery([
          'orderBy' => ['bestsellers' => SORT_DESC],
          'limit' => (int)$max,
          'customAndWhere' => 'p.products_ordered > 0',
        ]);

        $this->settings['listing_type'] = 'bestsellers';

        $products = Info::getListProductsDetails($q->buildQuery()->allIds(), $this->settings);

        if (count($products) <= 0) {
            return '';
        }

        if (
            in_array($this->settings[0]['listing_type'], ['type-1', 'type-1_2', 'type-1_3', 'type-1_4', 'type-2', 'type-2_2']) ||
            !$this->settings[0]['view_as']
        ) {
            return IncludeTpl::widget([
                'file' => 'boxes/bestsellers.tpl',
                'params' => [
                    'products' => Yii::$container->get('products')->getAllProducts($this->settings['listing_type']),
                    'settings' => $this->settings,
                    'languages_id' => $languages_id,
                    'id' => $this->id
                ]
            ]);
        } else {
            return \frontend\design\boxes\ProductListing::widget([
                'products' => Yii::$container->get('products')->getAllProducts($this->settings['listing_type']),
                'settings' => $this->settings,
                'id' => $this->id
            ]);
        }

    }
}