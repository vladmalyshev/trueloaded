<?php

/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models\EP\Provider\Competitor;

use Yii;
use backend\models\EP\Messages;
use backend\models\EP\Provider\DatasourceInterface;
use backend\models\EP\Exception;
use backend\models\EP\Tools;
use common\models\Competitors;
use common\models\CompetitorsProducts;

class CompetitorBot implements DatasourceInterface {

    protected $total_count = 0;
    protected $row_count = 0;
    protected $products_list;
    protected $config = [];
    protected $check_order_ids = [];
    protected $startJobServerGmtTime = '';
    protected $useModifyTimeCheck = true;
    protected $isErrorOccurredDuringCheck = false;
    protected $client;

    public function __construct($config) {
        try {
            $this->client = new \yii\httpclient\Client();
        } catch (\Exception $ex) {
            throw new Exception('Client is not ready');
        }
    }

    public function allowRunInPopup() {
        return true;
    }

    public function getProgress() {
        if ($this->total_count > 0) {
            $percentDone = min(100, ($this->row_count / $this->total_count) * 100);
        } else {
            $percentDone = 100;
        }
        return number_format($percentDone, 1, '.', '');
    }

    public function prepareProcess(Messages $message) {
        $this->products_list = CompetitorsProducts::find()->where([
            'and', 
            ['status' => 1 ],
            ['<', 'last_modified', date("Y-m-d 00:00:00")]])
                ->with('competitor')->all();

        if (count($this->products_list) == 0) {
            $message->info('No data');
            $message->progress(100);
            return false;
        }
        $this->total_count = count($this->products_list);

        $this->afterProcessFilename = tempnam($this->config['workingDirectory'], 'after_process');
        $this->afterProcessFile = fopen($this->afterProcessFilename, 'w+');
    }

    public function processRow(Messages $message) {
        set_time_limit(0);

        $currentProduct = current($this->products_list);
        if (!$currentProduct)
            return false;
        try {
            $this->processRemoteProduct($currentProduct, $message);
        } catch (\Exception $ex) {
            throw new \Exception('Processing product error ' . $ex->getMessage() . " Trace:" . $ex->getTraceAsString());
        }

        $this->row_count++;
        next($this->products_list);
        return true;
    }

    public function postProcess(Messages $message) {
        return;
    }

    public function processRemoteProduct($currentProduct, Messages $message, $useAfterProcess = false) {
        $currencies = Yii::$container->get('currencies');
        $aNotifier = new \backend\models\AdminNotifier();
        if ( (empty($currentProduct->products_url) && empty($currentProduct->products_url_short)) || (
                empty($currentProduct->products_price_mask) && empty($currentProduct->competitor->competitors_mask)
                )) {
            $localProduct = \common\models\Products::find()->where(['products_id' => (int) $currentProduct->products_id])->one();
            if ($localProduct) {
                $aNotifier->addNotification($message, \yii\helpers\Html::a($localProduct->products_model . " has no details", Yii::$app->urlManager->createAbsoluteUrl(['categories/productedit', 'pID' => $currentProduct->products_id])));
                $message->info('Product ' . $localProduct->products_model . " has no details");
                return true;
            }
        }
        $url = null;
        if (!empty($currentProduct->products_url) && (mb_strlen($currentProduct->products_url) > mb_strlen($currentProduct->competitor->competitors_site) + 3 )) {
            $url = $currentProduct->products_url;
        } elseif (!empty($currentProduct->products_url_short) && !empty($currentProduct->competitor->competitors_site)) {
            $url = rtrim($currentProduct->competitor->competitors_site, '/') . '/' . ltrim($currentProduct->products_url_short, '/');
        }

        if ($url) {
            $request = $this->client->get($url, null, $this->getHeaders());
            try{
                $response = $request->send();
            } catch (\Exception $ex){
                $aNotifier->addNotification($message, $currentProduct->competitor->competitors_name . " has access denied on {$url}");
                $currentProduct->status = 0;
                $currentProduct->save(false);
                return ;
            }
            if ($response->getIsOk()) {
                $content = $response->getContent();
                if (!empty($currentProduct->products_price_mask)) {
                    $mask = $currentProduct->products_price_mask;
                } else {
                    $mask = $currentProduct->competitor->competitors_mask;
                }
                //$mask = preg_quote($mask);
                $content = preg_replace("/\s/", " ", $content);
                $toMask = ['+', '*', '?', '[', '^', ']', '$', '(', ')', '{', '}', '!', '|', '-', '/'];
                foreach ($toMask as $item) {
                    $mask = str_replace($item, "\\" . $item, $mask);
                }

                $mask = str_replace("\s/", " ", $mask);
                //get currency
                $wMask = $mask;
                $remoteCurrency = $currentProduct->competitor->competitors_currency;

                if (strpos($wMask, '##CURRENCY##') !== false) { //need currency detect
                    if (strpos($wMask, '####') !== false) { //together
                        $wMask = preg_replace("/##PRICE##/", "", $wMask);
                    } else {
                        $wMask = preg_replace("/##PRICE##/", ".*", $wMask);
                    }
                    $wMask = preg_replace("/##CURRENCY##/", "(.*?)", $wMask);

                    preg_match("/{$wMask}/i", $content, $matches);
                    if (is_array($matches) && isset($matches[1])) {
                        $found = $matches[1];
                        $found = preg_replace("/[^\S]/", "", $found);
                        $found = preg_replace("/[\d\.,]/", "", $found);
                        $detect = $this->getPossibleCurrency($found);
                        if (is_null($detect)) {
                            $detect = $this->getPossibleCurrency(html_entity_decode($found));
                        }
                        if (!is_null($detect)) {
                            $remoteCurrency = $detect;
                        }
                    } else {
                        
                    }
                }

                $wMask = $mask;
                if (strpos($wMask, '##PRICE##') !== false) { //need price detect
                    if (strpos($wMask, '####') !== false) { //together
                        $wMask = preg_replace("/##CURRENCY##/", "", $wMask);
                    } else {
                        $wMask = preg_replace("/##CURRENCY##/", ".*", $wMask);
                    }
                    $wMask = preg_replace("/##PRICE##/", "(.*?)", $wMask);

                    preg_match("/{$wMask}/i", $content, $matches);

                    if (is_array($matches) && isset($matches[1])) {
                        $found = $matches[1];
                        $price = preg_replace("/[^\d\.,]/", "", $found);
                        if (!empty($price)) {
                            if (preg_match("/\./", $price) && preg_match("/,/", $price)) {
                                $price = preg_replace("/,/", "", $price);
                            } else {
                                $price = preg_replace("/,/", ".", $price);
                            }
                            if ($remoteCurrency != $currentProduct->competitor->competitors_currency) {
                                $price *= $currencies->get_market_price_rate($remoteCurrency, $currentProduct->competitor->competitors_currency);
                            }
                            $currentProduct->setAttribute('products_price', (float) $price);
                            $currentProduct->setAttribute('products_currency', $remoteCurrency);
                            $currentProduct->save(false);
                        }
                    } else {
                        
                    }
                }
            } else {
                $currentProduct->status = 0;
                $currentProduct->save(false);
                $message->info($url . ' is unavailable');
                return;
            }
        }
        //throw new Exception('fuck');
    }
    
    public function getHeaders() {
        return ['User-Agent' => "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.99 Safari/537.36"];
    }
    
    public function getPossibleCurrency($value) {
        if (empty($value)) {
            return null;
        }
        $currencies = Yii::$container->get('currencies');

        $code = null;

        if (isset($currencies->currencies[$value])) {
            $code = $value;
        }
        if (is_null($code)) {
            $check = str_replace("/", "\/", preg_quote($value));
            foreach ($currencies->currencies as $_code => $_curr) {
                if (preg_match("/{$check}/", $_curr['symbol_left'])) {
                    $code = $_code;
                } elseif (preg_match("/{$check}/", $_curr['symbol_right'])) {
                    $code = $_code;
                } elseif (preg_match("/{$check}/", $_curr['title'])) {
                    $code = $_code;
                }
            }
        }
        return $code;
    }

}
