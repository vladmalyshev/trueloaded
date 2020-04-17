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
use common\classes\Images;

class GiveAway extends Widget
{

    public $type;
    public $settings;
    public $params;

    public function init()
    {
        parent::init();
    }

    public function run()
    {
        global $cart;
        if ($cart->count_contents() == 0) {
            return IncludeTpl::widget(['file' => 'boxes/hide-box.tpl','params' => [
                'settings' => $this->settings,
                'id' => $this->id
            ]]);
        }

        $products = \common\helpers\Gifts::getGiveAways();

        if ( !is_array($products) || count($products)==0 ) {
            return IncludeTpl::widget(['file' => 'boxes/hide-box.tpl','params' => [
                'settings' => $this->settings,
                'id' => $this->id
            ]]);
        }

        return IncludeTpl::widget([
            'file' => 'boxes/cart/give-away.tpl',
            'params' => [
                'products' => $products,
            ]]);
    }
}