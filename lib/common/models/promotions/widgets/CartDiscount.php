<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\models\promotions\widgets;

use Yii;
use yii\base\Widget;

class CartDiscount extends Widget {

    public $details;

    public function init() {
        parent::init();
    }

    public function run() {
        //return $this->render('cart-discount',['details' => $this->details]);

        return \frontend\design\IncludeTpl::widget(['file' => 'promotions/cart-discount.tpl', 'params' => [
            'details' => $this->details
        ]]);
    }

}
