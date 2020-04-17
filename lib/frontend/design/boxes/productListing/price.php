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

class price extends Widget
{

    public $file;
    public $params;
    public $settings;

    public function run()
    {
        return '
<div class="price">
    <span class="old" style="display:none;"></span>
    <span class="specials" style="display:none;"></span>
    <span class="current">
        <span itemprop="priceCurrency" content="GBP">£</span>
        <span itemprop="price" content="12.07">12.07</span>
    </span>
</div>';
    }
}