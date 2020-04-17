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
use common\classes\platform;
use common\helpers\Product;

class NewProducts extends Widget
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
        global $current_category_id;
        $languages_id = \Yii::$app->settings->get('languages_id');

        if ($this->settings[0]['params']) {
            $max = $this->settings[0]['params'];
        } else {
            $max = MAX_DISPLAY_NEW_PRODUCTS;
        }

        /** 0x1 simple
         *  0x2 bundle
         *  0x4 PC Conf
         */
        if ($this->settings[0]['product_types']>0) {
            $type_where = ' and ( 0 ';
            if ($this->settings[0]['product_types'] & 1) {
                $type_where .= ' or (p.is_bundle=0 and p.products_pctemplates_id=0)';
            }
            if ($this->settings[0]['product_types'] & 2) {
                $type_where .= ' or p.is_bundle>0';
            }
            if ($this->settings[0]['product_types'] & 4) {
                $type_where .= ' or p.products_pctemplates_id>0';
            }
            $type_where .= ')';
        } else {
            $type_where = ' ';
        }

        $q = new \common\components\ProductsQuery([
            'orderBy' => ['products_date_added' => SORT_DESC],
            'limit' => (int)$max,
            'customAndWhere' => $type_where,
        ]);

        $this->settings['listing_type'] = 'new-products';

        $products = Info::getListProductsDetails($q->buildQuery()->allIds(), $this->settings);

        if (count($products) > 0) {
            if (in_array($this->settings[0]['listing_type'], ['type-1', 'type-1_2', 'type-1_3', 'type-1_4', 'type-2', 'type-2_2'])) {
                return IncludeTpl::widget([
                    'file' => 'boxes/new-products.tpl',
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

        return '';
    }
}