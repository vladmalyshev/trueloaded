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

class ButtonsQuote extends Widget
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

        if (!$params['products_id'] || GROUPS_DISABLE_CHECKOUT) {
            return '';
        }

        $products = Yii::$container->get('products');
        $product = $products->getProduct($params['products_id']);
        $stock_info = $product[$products::TYPE_STOCK];

        if (!$stock_info['flags']['request_for_quote']) {
            return '';
        }

        return IncludeTpl::widget(['file' => 'boxes/product/button-quote.tpl', 'params' => [
            'product_has_attributes' => \common\helpers\Attributes::has_product_attributes($params['products_id']),
            'customer_is_logged' => !Yii::$app->user->isGuest,

        ]]);
    }
}