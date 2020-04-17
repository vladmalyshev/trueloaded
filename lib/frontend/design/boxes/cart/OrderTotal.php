<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\cart;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;

class OrderTotal extends Widget
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
        global $cart;
        
        $manager = $this->params['manager'];

        $currencies = \Yii::$container->get('currencies');

        $result = [];
        if ($cart->count_contents() == 0) {
            $result[] = [
                'title' => TEXT_TOTAL,
                'text' => $currencies->format( 0 ),
                'code' => 'empty_cart',
            ];
        } else {
            $result = $manager->getTotalOutput(true, 'TEXT_SHOPPING_CART');
        }
        
        return IncludeTpl::widget(['file' => 'boxes/cart/order-total.tpl', 'params' => ['order_total_output' => $result]]);
    }
}