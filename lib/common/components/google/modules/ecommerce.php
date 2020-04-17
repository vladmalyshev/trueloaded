<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\components\google\modules;

use common\classes\platform;
use common\classes\Order;

final class ecommerce extends AbstractGoogle {

    public $config;
    public $code = 'ecommerce';

    public function getParams() {

        $this->config = [
            $this->code => [
                'name' => 'ECommerce Tracking',
                'fields' => [
                ],
                'pages' => [
                    'checkout',
                ],
                'type' => [
                    'selected' => 'js',
                    //'server' => 'ServerSide Transaction <div class="ord-total" style="text-align:left!important;float: right;"><div class="ord-total-info" style="left:0!important;">To Use ServerSide Api, load json credentials for service account and allow it to push data to Analytics, fill View ID, enable Analytics Api </div></div>',
                    'js' => 'JS Transaction',
                ],
                'pages_only' => true,
                'priority' => 2,
                'example' => true,
            //'flow' => [__NAMESPACE__ . '\ecommerce', 'serverSideProcess'],
            ],
        ];
        return $this->config;
    }

    public function renderWidget() {
        $manager = \common\services\OrderManager::loadManager();
        if ($manager->isInstance()) {
            $order = $manager->getOrderInstance();
            if (class_exists('\common\components\google\widgets\GoogleCommerce') && $order instanceof Order) {
                return \common\components\google\widgets\GoogleCommerce::widget(['order' => $order]);
            }
        }
    }

    public function renderExample() {
        if ($this->params['platform_id']) {
            $installed = $this->provider->getInstalledModules($this->params['platform_id']);
            $deps = [];
            foreach (['analytics', 'tagmanger'] as $dep) {
                if (isset($installed[$dep])) {
                    $deps[$dep] = $installed[$dep];
                }
            }
            if ($deps) {
                foreach ($deps as $dep) {
                    $installed = $this->provider->getInstalledById($dep->google_settings_id);
                    if ($installed instanceof analytics) {
                        if ($installed->config[$installed->code]['type']['selected'] == 'ga') {
                            return "<pre>" . <<<EOD
ga('require', 'ecommerce');
ga('ecommerce:addTransaction', {"id":"228517","affiliation":"Trueloaded","revenue":"7.05","shipping":"2.50","tax":"0.76"});
ga('ecommerce:addItem', {"id":"228517","name":"Test product","sku":"402","category":"Category name","price":"3.79","quantity":"1"});
ga('ecommerce:send', []);
        
EOD
                                    . "</pre>";
                        } else {
                            return "<pre>" . <<<EOD
_gaq.push(['_addTrans', "228517", "Trueloaded", "7.05", "0.76", "2.50", "Swindon", "Wilshire", "United Kingdom"]);
_gaq.push(['_addItem', "228517", "402", "Test product", "Category name", "3.79", "1"]);
_gaq.push(['_trackTrans']);
          
EOD
                                    . "</pre>";
                        }
                    } else if ($installed instanceof tagmanger) {
                        return "<pre>" . <<<EOD
dataLayer.push({
  'ecommerce': {
    'purchase': {
      'actionField': {
        'id': 'T12345',                         // Transaction ID. Required for purchases and refunds.
        'affiliation': 'Online Store',
        'revenue': '35.43',                     // Total transaction value (incl. tax and shipping)
        'tax':'4.90',
        'shipping': '5.99',
        'coupon': 'SUMMER_SALE'
      },
      'products': [{                            // List of productFieldObjects.
        'name': 'Triblend Android T-Shirt',     // Name or ID is required.
        'id': '12345',
        'price': '15.25',
        'brand': 'Google',
        'category': 'Apparel',
        'variant': 'Gray',
        'quantity': 1,
        'coupon': ''                            // Optional fields may be omitted or set to empty string.
       },
       {
        'name': 'Donut Friday Scented T-Shirt',
        'id': '67890',
        'price': '33.75',
        'brand': 'Google',
        'category': 'Apparel',
        'variant': 'Black',
        'quantity': 1
       }]
    }
  }
});
EOD
                                . "</pre>";
                    } else {
                        return 'Google Analytics or Tag Manager ' . TEXT_FIELD_REQUIRED;
                    }
                }
            } else {
                return '<div class="alert alert-danger">Ecommerce Tracking works only with Analytics or Tag Manage, please disactive one </div>';
            }
        }
        return;
    }

    //Depricated
    public static function serverSideProcess(array $params = []) {
        return false;
    }

}
