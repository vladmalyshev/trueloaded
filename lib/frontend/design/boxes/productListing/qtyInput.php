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

class qtyInput extends Widget
{

    public $file;
    public $params;
    public $settings;

    public function run()
    {
        return '
<div class="qtyInput ">    <span class="qty-box"><span class="smaller disabled"></span><input type="text" name="qty" value="1" class="qty-inp" data-max="99999" data-min="1" data-step="1"><span class="bigger"></span></span>
</div>';
    }
}