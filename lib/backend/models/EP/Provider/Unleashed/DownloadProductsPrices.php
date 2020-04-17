<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models\EP\Provider\Unleashed;

use backend\models\EP\Messages;
use backend\models\EP\Provider\DatasourceInterface;
use \common\helpers\Warehouses;
use \common\helpers\Suppliers;

class DownloadProductsPrices implements DatasourceInterface
{
    protected $total_count = 0;
    protected $row_count = 0;
    protected $process_list = [];
    protected $config = [];

    /**
     * @var Client
     */
    protected $client;
    protected $hasMoreData = false;
    protected $fetchPage = 1;
    protected $providerObj = null;
    protected $lastCustomer = ['exId'=>null, 'email' => null];


    function __construct($config)
    {
        $this->config = $config;
    }

    public function getProgress()
    {
        if ( $this->total_count>0 ) {
            $percentDone = min(100, ($this->row_count / $this->total_count) * 100);
        }else{
            $percentDone = 100;
        }
        return number_format(  $percentDone,1,'.','');
    }

    public function prepareProcess(Messages $message)
    {
        $this->fetchPage = 1;
        $this->hasMoreData = false;
        $this->client = new Client($this->config['client']['API_ID'],$this->config['client']['API_KEY']);

        $this->process_list = [];
        $this->fetchPage($message);

        reset($this->process_list);
        
        $message->info("Products for update - ".$this->total_count);
        $this->providerObj = new \common\extensions\CustomerProducts\ImportPrices\ImportPrices();
    }

    protected function fetchPage(Messages $message)
    {
        $response = $this->client->get('ProductPrices/'.$this->fetchPage)->send();
        $data = $response->getData();
        
        $Pagination = $data['Pagination'];
        $this->hasMoreData = (int)$Pagination['PageNumber'] < (int)$Pagination['NumberOfPages'];
        $this->total_count = (int)$Pagination['NumberOfItems'];
        if (is_array($data['Items'])) {
          foreach ($data['Items'] as $item){
              $this->process_list[$item['Guid']] = $item;
          }
        }
        return true;
    }

    public function processRow(Messages $message)
    {
        $guid = key($this->process_list);

        $this->processProduct( $guid, $this->process_list[$guid], $message);

        if (next($this->process_list)){
            return true;
        }elseif($this->hasMoreData){
            $this->process_list = [];
            $this->fetchPage++;
            $message->info('fetch page ' . $this->fetchPage);
            return $this->fetchPage($message);
        }
    }

    public function postProcess(Messages $message)
    {
      $this->providerObj->postProcess($message);
      $message->info('Done ');
    }

    protected function processProduct($guid, $data, Messages $message)
    {
      if (!empty($data['MinimumQuantity']) && strtolower($data['MinimumQuantity']) != 'null') {
        $message->info('Cant process Minimum Quantity ' . $data['MinimumQuantity']);
        return false;
      }
//$message->info( "#### <PRE>" .print_r($data, 1) ."</PRE>");

      $keys = [
        'Product' => ['set' => 'setModels'],
        'Customer' => ['set' => 'setCustomer'],
        'CustomerPrice' => 'Products Price',
        'ValidFrom' => 'Start Date',
        'ValidTo' => 'End Date',
        //'ProductCode' => 'Main Model',
        //'Obsolete' => '',

      ];

      /*
 "Items": [
    {
      "Product": {
        "Guid": "e5bfa4d3-8405-4e65-bc39-acefe42a0ef4",
        "ProductCode": "PR651",
        "ProductDescription": "Liverpool | 107x203 King Single | Mattress | Tight Top | Pocket Spring"
      },
      "ProductGroup": {
        "GroupName": "Mattress",
        "Guid": "80f83a05-d7e0-473a-88f4-fde23f2ad0d5",
        "LastModifiedOn": "/Date(1463195343171)/"
      },
      "Customer": {
        "CustomerCode": "TBH",
        "CustomerName": "TBH NZ Limited T/A The Bunk House",
        "CurrencyId": 110,
        "Guid": "4bb87662-9f4f-4385-9e81-d241fc51fdfd",
        "LastModifiedOn": "/Date(1560295066802)/"
      },
      "PriceType": "Fixed Price",
      "DiscountValue": 155,
      "MinimumQuantity": null,
      "DefaultSellPrice": 265,
      "CustomerPrice": 155,
      "ValidFrom": null,
      "ValidTo": null,
      "Comments": null,
      "Guid": "df5a3925-ff5b-4924-bc77-2914d65931f7",
      "LastModifiedOn": "/Date(1560295064842)/"
    },
    {
      "Product": {

       */

        $secondChance = []; //not used now
        foreach ($keys as $fileField => $d) {
          if (isset($data[$fileField]) && is_array($d)) {

            if (isset($d['set']) && method_exists($this, $d['set'])) {
              $r = call_user_func_array(array($this, $d['set']), array(&$data, $fileField, &$message));
              if ($r !== true) {
                $message->info($r);
                return ;
              }
            } elseif (!is_array($d)) {
              $data[$d] = $data[$fileField];
            }
            $secondChance[$fileField] = $data[$fileField];
            unset($data[$fileField]);
          } elseif (isset($data[$fileField]) && !is_array($d)) {
            $data[$d] = $data[$fileField];
          }

        }

        if ($data && is_array($data) && count($data) > 0) {
         

          $transform = new \backend\models\EP\Transform();
          $transform->setProviderColumns($this->providerObj->getColumns());
          $data = $transform->transform($data);

          try {

            $this->providerObj->importRow($data, $message);
          } catch (\Exception $e) {
            $message->info($e->getMessage());
            $message->info(print_r($data,1));
          }
          $this->row_count++;
          return true;
        } else {
          $message->info('empty data');
        }

        return false;

    }

    public static function setModels(&$data, $fileField, $message) {
      $ret = true;

      $d = $data[$fileField]['ProductCode'];
      if (!empty($d)) {
        $data['Product Model'] = $d;
      } else {
        $message->info('Empty product code field - price update is skipped');
        $ret = false;
      }
      unset($data[$fileField]);
      return $ret;
    }

    public static function setCustomer(&$data, $fileField, $message) {
//vl2do
      $ret = true;

      $d = $data[$fileField];


      /* 
        "Customer": {
        "CustomerCode": "TBH",
        "CustomerName": "TBH NZ Limited T/A The Bunk House",
        "CurrencyId": 110,
        "Guid": "4bb87662-9f4f-4385-9e81-d241fc51fdfd",
        "LastModifiedOn": "/Date(1560295066802)/"
                )
       */
        //$data['Currency'] = strtoupper($data[$fileField]['CurrencyCode']);

      
        if (isset($d['CustomerCode']) && !empty($d['CustomerCode'])) {
            $data['Customer Code'] = $d['CustomerCode'];
        } else {
            $message->info('Empty customer code field - price update is skipped');
            $ret = false;
        }
      unset($data[$fileField]);
      return $ret;
    }


}