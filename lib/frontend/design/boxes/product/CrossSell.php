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
use frontend\design\Info;

class CrossSell extends Widget
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
        $params = Yii::$app->request->get();

        if (!$params['products_id']) {
            return '';
        }

        $xsell_type_id = 0;
        if ( isset($this->settings[0]['xsell_type_id']) ){
            $xsell_type_id = (int)$this->settings[0]['xsell_type_id'];
        }

        $max = $this->settings[0]['max_products'] ? $this->settings[0]['max_products'] : 4;

        $cW = ['exists', \common\models\ProductsXsell::find()->alias('xp')
                                          ->andWhere("p.products_id = xp.xsell_id")
                                          ->andWhere([
                                                  'xp.products_id' => (int)$params['products_id'],
                                                  'xp.xsell_type_id' => $xsell_type_id,
                                                ])
            ];
        if ($this->settings[0]['show_cart_button']) {
          $cW = ['or',
                 ['p.products_id' => (int)$params['products_id']],
                 $cW
                ];
        }

        $q = new \common\components\ProductsQuery([
          'limit' => (int)$max,
          'customAndWhere' => $cW,
          'orderBy' => ['FIELD (products_id, '. (int)$params['products_id'] . ') DESC' => ''],
        ]);
        
        $this->settings['listing_type'] = 'cross-sell-' . $xsell_type_id;
        $this->settings['options_prefix'] = 'list';
        
        $products = Info::getListProductsDetails($q->buildQuery()->allIds(), $this->settings);

        if (count($products) > ($this->settings[0]['show_cart_button']?1:0)) {

            if (in_array($this->settings[0]['listing_type'], ['type-1', 'type-1_2', 'type-1_3', 'type-1_4', 'type-2', 'type-2_2'])) {
                return IncludeTpl::widget([
                    'file' => 'boxes/product/cross-sell.tpl',
                    'params' => [
                        'products' => $products,
                        'settings' => $this->settings,
                        'id' => $this->id
                    ]
                ]);
            } else {
                return \frontend\design\boxes\ProductListing::widget([
                    'products' => $products,
                    'settings' => $this->settings,
                    'id' => $this->id
                ]);
            }
        }

    }
}