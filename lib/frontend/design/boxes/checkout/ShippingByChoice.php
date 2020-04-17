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
use frontend\design\Info;

class ShippingByChoice extends Widget
{    
    public $manager;
    public $params;

    public function init()
    {
        parent::init();
    }

    public function run()
    {   
        $params = ['manager' => $this->manager ];
        if (Info::themeSetting('checkout_view') == 1 && !$this->manager->is('\common\extensions\Samples\SampleCart')){
            if (true){
                $params['page_name'] = 'index';
            } else {
                $params['page_name'] = 'index_2';
            }
            
            $response['page'] = [
                'blocks' => [
                    'shipping-step' => \frontend\design\Block::widget(['name' => $this->getStepTemplate('checkout_delivery'), 'params' => ['type' => 'checkout', 'params' => $params]]),
                    'payment-step' => \frontend\design\Block::widget(['name' => $this->getStepTemplate('checkout_payment'), 'params' => ['type' => 'checkout', 'params' => $params]]),
                    'products-totals' => \frontend\design\Block::widget(['name' => $this->getStepTemplate('checkout_step_bottom'), 'params' => ['type' => 'checkout', 'params' => $params]])
                ] 
            ];
        } else {
            $params['page_name'] = 'index';
            //$response['page'] = \frontend\design\Block::widget(['name' => $this->manager->getTemplate(), 'params' => ['type' => 'checkout', 'params' => $params]]);


            $response['page'] = [
                'widgets' => [
                    '.w-checkout-shipping' => \frontend\design\boxes\checkout\Shipping::widget(['params' => $params]),
                    '.w-checkout-shipping-address' => \frontend\design\boxes\checkout\ShippingAddress::widget(['params' => $params]),
                    '.w-checkout-totals' => \frontend\design\boxes\checkout\Totals::widget(['params' => $params]),
                    '.w-checkout-payment-method' => \frontend\design\boxes\checkout\PaymentMethod::widget(['params' => $params]),
                    '.w-checkout-billing-address' => \frontend\design\boxes\checkout\BillingAddress::widget(['params' => $params]),
                    '.w-delayed-despatch-checkout' => \common\extensions\DelayedDespatch\Checkout\Checkout::widget(['params' => $params]),
                    '.w-neighbour-checkout' => \common\extensions\Neighbour\Checkout\Checkout::widget(['params' => $params]),
                    '.w-checkout-shipping-choice' => \frontend\design\boxes\checkout\ShippingChoice::widget(['params' => $params]),
                ]
            ];
        }
        return $response;
    }
    
    private function getStepTemplate($name){
        $_template = $this->manager->getTemplate();
        if ($_template){
            return preg_replace("/checkout/", $name, $_template);
        }
        return $name;
    }
    
}