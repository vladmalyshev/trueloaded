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

class model extends Widget
{

    public $file;
    public $params;
    public $settings;

    public function run()
    {
        return '
<div class="model ">
    <div class="products-model">
        <strong>' . TEXT_MODEL . '<span class="colon">:</span></strong>
        <span>0123456</span>
    </div>
</div>';
    }
}