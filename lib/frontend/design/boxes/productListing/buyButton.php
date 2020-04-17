<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\productListing;

use yii\base\Widget;

class buyButton extends Widget
{

    public $file;
    public $params;
    public $settings;

    public function run()
    {
        return '
<div class="buyButton ">
    <a href="" class="btn-1 btn-buy add-to-cart" rel="nofollow" title="Add to Basket">Add to Basket</a>
    <a href="" class=" btn-1 btn-cart in-cart" rel="nofollow" title="In your cart" style="display: none">In your cart</a>
    <span class="btn-1 btn-preloader" style="display: none"></span>
</div>';
    }
}