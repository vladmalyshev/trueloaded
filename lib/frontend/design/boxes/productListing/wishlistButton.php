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

class wishlistButton extends Widget
{

    public $file;
    public $params;
    public $settings;

    public function run()
    {
        return '
<div class="wishlistButton ">
<label class="checkbox">
    <input type="checkbox" name="add_to_whishlist" value="1162"><span></span> ' . TEXT_WISHLIST_SAVE . '
</label>
</div>';
    }
}