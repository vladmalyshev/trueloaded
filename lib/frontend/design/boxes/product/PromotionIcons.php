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

class PromotionIcons extends Widget
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
        $products_id = Yii::$app->request->get('products_id', false);

        if ($this->params['product']) {
            if ($this->params['product']['products_id']){//listing
                $product = Yii::$container->get('products')->getProduct($this->params['product']['products_id']);
            } elseif ($this->params['product']['id']){//cart
                $product = Yii::$container->get('products')->getProduct($this->params['product']['id']);
            }
        }else if ($products_id) {
            $product = Yii::$container->get('products')->getProduct($products_id);
        } else {
            return '';
        }

        if (!(is_array($product['promo_details']) && count($product['promo_details']))) {
            //return '';
        }

        return IncludeTpl::widget([
            'file' => 'boxes/product/promotion-icons.tpl',
            'params' => [
                'product' => $product,
            ]
        ]);
    }
}