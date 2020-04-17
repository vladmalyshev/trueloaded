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

final class reviews extends AbstractGoogle {

    public $config;
    public $code = 'reviews';

    public function getParams() {

        $this->config = [
            $this->code => [
                'name' => 'Google Customer Reviews',
                'fields' => [
                    [
                        'name' => 'merchant_code',
                        'value' => '',
                        'type' => 'text'
                    ],
                    [
                        'name' => 'estimated_delivery',
                        'value' => '3',
                        'type' => 'text',
                        'comment' => ' (Customer will receive survey after)'
                    ],
                    [
                        'name' => 'add_gtins',
                        'value' => '0',
                        'type' => 'checkbox',
                        //'comment' => ' (Add Products GTIN)'
                    ]
                ],
                'example' => true,
                'priority' => 4,
            ],
        ];
        return $this->config;
    }

    public function renderWidget($example = false) {
        global $request_type;
        if (\Yii::$app->response->getIsNotFound())
            return;
        if ($example) {
            return "//survay code\n". $this->getSurvayCode(true) . "\n//badge code"  .$this->getBadgeCode(true);
        } else {
            return $this->getSurvayCode();
        }
    }
    
    public function getAvailablePages()
    {
        $this->config[$this->code]['pages'] = ['checkout'];
        return parent::getAvailablePages();
    }

    private function getElements() {
        return $this->config[$this->code];
    }

    private function getMerchantId() {
        $elements = $this->getElements();
        $merchantId = $elements['fields'][0]['value'];
        return !empty($merchantId) ? $merchantId : '123456789';
    }

    private function getSurvayCode($isExample = false) {
        $elements = $this->getElements();
        $days = intval($elements['fields'][1]['value']);
        $days = $days > 0 ? $days : 3;
        $eDate = date("Y-m-d", strtotime("+{$days} days"));
        $orderId = "ORDER_ID";
        $customerMail = "CUSTOMER_EMAIL";
        $deliveryCountry = "COUNTRY_CODE";
        $useGtin = $elements['fields'][2]['value'];$gtins = [];
        $showCode = true;
        if (!$isExample) {
            $showCode = false;
            /*do we need on Quote or Sample??*/
            if (\Yii::$app->controller->id == 'checkout' && \Yii::$app->controller->action->id == 'success') {
                $manager = \common\services\OrderManager::loadManager();
                if ($manager->isInstance()){
                    $order = $manager->getOrderInstance();
                    if (is_object($order) && $order instanceof \common\classes\extended\OrderAbstract) {
                        $showCode = true;
                        $orderId = $order->order_id;
                        $customerMail = $order->customer['email_address'];
                        $deliveryCountry = $order->delivery['country']['iso_code_2'];
                        if ($useGtin){
                            if (is_array($order->products)){
                                foreach($order->products as $product){
                                    $pM = \common\models\Products::find()->select('IF(LENGTH(`products_upc`)>0, `products_upc`, `products_ean`) as products_gtin')->where(['products_id' => (int)$product['id']])->asArray()->one();
                                    if (isset($pM['products_gtin']) && !empty($pM['products_gtin'])){
                                        $gtins[] = ["gtin" => $pM['products_gtin']];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!$showCode)
            return '';
        $products = json_encode($gtins);
        return $this->wrapScript(
                        <<<EOD
  window.renderOptIn = function() {
    window.gapi.load("surveyoptin", function() {
      window.gapi.surveyoptin.render(
        {
          "merchant_id": {$this->getMerchantId()},
          "order_id": "{$orderId}",
          "email": "{$customerMail}",
          "delivery_country": "{$deliveryCountry}",
          "estimated_delivery_date": "{$eDate}",
          "products": {$products}
        });
    });
  }
EOD
                        , 'survay', $isExample);
    }

    public function getBadgeCode($isExample = false, $position = null) {
        if (is_null($position)) $position = "BOTTOM_RIGHT";
        if ($position == "INLINE"){
            return "<g:ratingbadge merchant_id={$this->getMerchantId()}></g:ratingbadge>​";
        }
        
        return $this->wrapScript(
                        <<<EOD

   window.renderBadge = function() {
    var ratingBadgeContainer = document.createElement("div");
    document.body.appendChild(ratingBadgeContainer);
    window.gapi.load('ratingbadge', function() {
      window.gapi.ratingbadge.render(ratingBadgeContainer, {"merchant_id": "{$this->getMerchantId()}",  "position": "{$position}" });
    });
  }
EOD
                        , 'badge', $isExample);
    }

    private function wrapScript($string, $jsType, $isExample = false) {

        if ($isExample) {
            return $string;
        }

        if ($jsType == 'survay') {
            return '<script src="https://apis.google.com/js/platform.js?onload=renderOptIn" async defer></script> <script>' . $string . '</script>';
        } else if ($jsType == 'badge') {
            return '<script src="https://apis.google.com/js/platform.js?onload=renderBadge" async defer></script> <script>' . $string . '</script>';
        }
    }

    public function renderExample() {
        return "<pre>" . $this->renderWidget(true) . "</pre>";
    }

}
