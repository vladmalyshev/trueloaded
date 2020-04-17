<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\order;

use Yii;
use yii\base\Widget;

class BillingAddress extends Widget
{
    public $file;
    public $params;
    public $settings;
    public $manager;

    public function init()
    {
        parent::init();
    }

    public function run()
    {
        return \common\helpers\Address::address_format($this->params['order']->billing['format_id'], $this->params['order']->billing, 1, ' ', '<br>');
    }
}