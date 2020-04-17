<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\components\google;

use Yii;
use Google\Google_Client;
use Google\Google_Service_Analytics;
use Google\Google_Service_AnalyticsReporting;
use Google\Google_Service_AnalyticsReporting_DateRange;
use Google\Google_Service_AnalyticsReporting_Metric;
use Google\Google_Service_AnalyticsReporting_ReportRequest;
use Google\Google_Service_AnalyticsReporting_GetReportsRequest;
use Google\Google_Service_AnalyticsReporting_GetReportsResponse;
use Google\Google_Service_AnalyticsReporting_Dimension;
use Google\Google_Service_AnalyticsReporting_DimensionFilter;
use Google\Google_Service_AnalyticsReporting_DimensionFilterClause;
use common\classes\Order;

/*
 * Google Analytics Ecoomerce tracker Server Side
 */

class GoogleEcommerceSS {

    public $platform_id;
    public $order_id;
    public $ga;
    public $platform;
    private $google_collect_url = 'https://www.google-analytics.com/collect';

    public function __construct($order_id, $platform_id = null) {
        $this->order_id = $order_id;

        if (!$this->order_id) {
            throw new Exception('Invalid Order Id');
        }

        $this->platform_id = $platform_id;

        if (!$this->platform_id)
            $this->platform_id = \common\classes\platform::defaultId();

        if (!$this->platform_id) {
            throw new \Exception('Invalid Platform Id');
        }

        $this->platform = (new \common\classes\platform())->config($this->platform_id);

        $analytics = (new \common\components\GoogleTools)->getAnalyticsProvider();

        try {
            $this->ga = new \common\components\GoogleAnalytics($analytics->getFileKey($this->platform_id), $analytics->getViewId($this->platform_id));
            if ($this->ga)
                $this->ga->prepareReporting();
        } catch (\Exception $ex) {
            $noty = new \backend\models\AdminNotifier();
            $noty->addNotification(null, $ex->getMessage(), 'warning');
            \Yii::info($ex->getMessage(), 'Google analytics Exception');
        }
    }

    public function getFilters() {
        return [
            'date_range' => ['5daysAgo', 'today'],
            'dimensions' => ['ga:transactionId'],
            'dimensionsFilter' => [
                [
                    'dimension' => 'ga:transactionId',
                    'expression' => (string)$this->order_id,
                    'operator' => 'EXACT'
                ],
            ],
            'metrics' => ['ga:transactions'],
        ];
    }

    public function isOrderPlacedToAnalytics() {
        if ($this->ga) {
            try {
                $response = $this->ga->getReport($this->getFilters());
                if (is_object($response) && property_exists($response, 'reports')) {
                    $report = $response->reports[0];

                    if ($report instanceof \Google\Google_Service_AnalyticsReporting_Report) {
                        $rows = $report->getData()->getRows();
                        if ($rows) {
                            $row = $rows[0];
                            $placed = $row->getDimensions();
                            return in_array($this->order_id, $placed);
                        }
                    }
                }
            } catch (\Exception $ex) {
              \Yii::info($ex->getMessage(), 'Google analytics Exception');
                //var_dump($ex->getMessage());
            }
        }
        return false;
    }

    public function pushDataToAnalytics(Order $order = null) {
        if($this->ga){
            $gCommerce = new \common\components\google\widgets\GoogleCommerce();
            if (!$order) {
                $gCommerce->order = new Order($this->order_id);
            } else {
                $gCommerce->order = $order;
            }
            if (!is_object($gCommerce->order)) throw new \Exception('Order is not loaded');
            $gCommerce->prepareData();
            $uaCode = $this->ga->getUACode();
            if (isset($gCommerce->used_module) && $uaCode !== false) {
                $languageCode = \common\helpers\Language::get_language_code($gCommerce->order->info['language_id']);
                if ($languageCode){
                    $languageCode = $languageCode['code'];
                } else {
                    $languageCode = 'en';
                }
                $data = [
                    'v' => 1, //protocol
                    'ds' => 'web', //
                    'uid' => $gCommerce->order->customer['id'],
                    'ua' => '', //user agen, can be ua from payment bot
                    'geoid' => $gCommerce->order->billing['country']['iso_code_2'], //get country ISO-2
                    'de' => 'UTF-8',
                    'ul' => $languageCode . "-" . $languageCode,
                    't' => 'pageview',
                    'dl' => $this->platform->getCatalogBaseUrl(true),
                    'ti' => $this->order_id,
                    'pa' => 'purchase',
                    'tid' => $uaCode,
                    'cu' => $gCommerce->order->info['currency'],
                    'dp' => '/checkout/success?order_id=' . $this->order_id
                ];

                if (($ga = \common\helpers\System::get_ga_detection($this->order_id)) !== false) {
                    $data['cn'] = $ga['utmccn'];
                    $data['cid'] = $ga['utmcmd'];
                    $data['ck'] = $ga['utmctr'];
                    $data['gclid'] = $ga['utmgclid'];
                    //$data['ua'] = $ga['user_agent'];//serialized
                    $data['sr'] = $ga['resolution'];
                    $data['uip'] = $ga['ip_address'];
                    if ($data['sr']) {
                        $data['je'] = 1;
                    }
                }
                if ($gCommerce->used_module == 'tagmanger') {
                    $data['ta'] = $gCommerce->gtm['actionField']['affiliation'];
                    $data['tr'] = $gCommerce->gtm['actionField']['revenue'];
                    $data['ts'] = $gCommerce->gtm['actionField']['shipping'];
                    $data['tt'] = $gCommerce->gtm['actionField']['tax'];
                    $data += $this->getGtmProducts($gCommerce);
                } else {//analytics                
                    if (is_array($gCommerce->ga['ecommerce:addTransaction']) && count($gCommerce->ga['ecommerce:addTransaction'])) { //ga
                        $data['ta'] = $gCommerce->ga['ecommerce:addTransaction']['affiliation'];
                        $data['tr'] = $gCommerce->ga['ecommerce:addTransaction']['revenue'];
                        $data['ts'] = $gCommerce->ga['ecommerce:addTransaction']['shipping'];
                        $data['tt'] = $gCommerce->ga['ecommerce:addTransaction']['tax'];
                        $data += $this->getGaProducts($gCommerce);
                    } else { //_gaq
                        $data['ta'] = $gCommerce->_gaq['_addTrans'][1]; //affiliate
                        $data['tr'] = $gCommerce->_gaq['_addTrans'][2]; //total
                        $data['tt'] = $gCommerce->_gaq['_addTrans'][3]; //tax
                        $data['ts'] = $gCommerce->_gaq['_addTrans'][4]; //shipping
                        $data += $this->getGaqProducts($gCommerce);
                    }
                }
                if ($data) {
                    return $this->collectGoogleData($data);
                }
            }
        }
        return false;
    }

    public function getGtmProducts($gCommerce) {
        $data = [];
        if (is_array($gCommerce->gtm['products'])) {
            $i = 1;
            foreach ($gCommerce->gtm['products'] as $product) {
                $data["pr{$i}id"] = $product['id'];
                $data["pr{$i}nm"] = $product['name'];
                $data["pr{$i}ca"] = $product['category'];
                $data["pr{$i}br"] = $product['brand'];
                $data["pr{$i}va"] = $product['variant'];
                $data["pr{$i}pr"] = $product['price'];
                $data["pr{$i}qt"] = $product['quantity'];
                $data["pr{$i}ps"] = $i;
                $i++;
            }
        }
        return $data;
    }

    public function getGaProducts($gCommerce) {
        $data = [];
        if (is_array($gCommerce->ga['ecommerce:addItem'])) {
            $i = 1;
            foreach ($gCommerce->ga['ecommerce:addItem'] as $product) {
                $data["pr{$i}id"] = $product['sku'];
                $data["pr{$i}nm"] = $product['name'];
                $data["pr{$i}ca"] = $product['category'];
                $data["pr{$i}pr"] = $product['price'];
                $data["pr{$i}qt"] = $product['quantity'];
                $data["pr{$i}ps"] = $i;
                $i++;
            }
        }
        return $data;
    }

    public function getGaqProducts($gCommerce) {
        $data = [];
        if (is_array($gCommerce->ga['_addItem'])) {
            $i = 1;
            foreach ($gCommerce->ga['_addItem'] as $product) {
                $data["pr{$i}id"] = $product[1];
                $data["pr{$i}nm"] = $product[2];
                $data["pr{$i}ca"] = $product[3];
                $data["pr{$i}pr"] = $product[4];
                $data["pr{$i}qt"] = $product[5];
                $data["pr{$i}ps"] = $i;
                $i++;
            }
        }
        return $data;
    }

    public function collectGoogleData($data = []) {
        $httpClient = new \yii\httpclient\Client;
        $request = $httpClient->post($this->google_collect_url, $data);
        $response = $request->send();
        if ($response->isOk) {
            return true;
        } else {
            return false;
        }
    }

}
