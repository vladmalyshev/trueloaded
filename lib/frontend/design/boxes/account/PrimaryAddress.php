<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\account;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;

class PrimaryAddress extends Widget
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
        $customer = Yii::$app->user->getIdentity();

        $address = \common\helpers\Address::address_label($customer->customers_id, $customer->customers_default_address_id, true, ' ', '<br>');
        return IncludeTpl::widget(['file' => 'boxes/account/primary-address.tpl', 'params' => [
            'address' => $address,
        ]]);
    }
}