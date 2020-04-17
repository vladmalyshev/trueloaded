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

class name extends Widget
{

    public $file;
    public $params;
    public $settings;

    public function run()
    {
        return '
<div class="name"><table class="wrapper"><tbody><tr><td>
    <a href="">
        <h2>Demo product name</h2>
    </a>
</td></tr></tbody></table></div>';
    }
}