<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\checkout;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;

class ShippingChoice extends Widget
{


    public $manager;
    public $params;
    public $settings;

    public function init()
    {
        parent::init();
    }

    public function run()
    {
        if (!$this->manager) {
            $this->manager = $this->params['manager'];
        }

        $_shippingChoice = $this->manager->getPickupOrDeliveryChoice();

        $pickupShippingQuote = $this->manager->getPickupShippingQuotes();

        if (!$_shippingChoice || !$pickupShippingQuote){
            return '';
        }

        return IncludeTpl::widget([
            'file' => 'boxes/checkout/shipping-choice.tpl',
            'params' => [
                'params' => $this->params,
                'model' => $_shippingChoice,
                'url' => Yii::$app->getUrlManager()->createUrl('checkout/worker'),
                'manager' => $this->manager
            ],
        ]);

    }
}