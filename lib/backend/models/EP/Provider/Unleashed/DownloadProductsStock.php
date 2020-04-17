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

use \common\models\ProductsImages;
use backend\models\EP\Messages;
use backend\models\EP\Provider\DatasourceInterface;
use \common\helpers\Warehouses;
use \common\helpers\Suppliers;

class DownloadProductsStock implements DatasourceInterface
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
    protected $defaultSupplierName = 'Unknown';


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
    }

    protected function fetchPage(Messages $message)
    {
      
      $warehouses = \common\models\Warehouses::find()->asArray()->all();
      // $message->info("#### <PRE>"  . __FILE__ .':' . __LINE__ . ' ' . print_r($warehouses, 1) ."</PRE>");
      foreach ($warehouses as $warehouse) { 
        $this->fetchPage = 1;
        do {
          $response = $this->client->get('StockOnHand/'.$this->fetchPage, 'pageSize=700&warehouseCode=' . $warehouse['warehouse_owner'])->send();
          $data = $response->getData();

          $Pagination = $data['Pagination'];
          $this->hasMoreData = (int)$Pagination['PageNumber'] < (int)$Pagination['NumberOfPages'];
          
          if (is_array($data['Items'])) {
            foreach ($data['Items'] as $item){
              if (true || $item['QtyOnHand']>0) {
                if (isset($this->process_list[$item['ProductGuid']])) {

                  $this->process_list[$item['ProductGuid']]['QtyOnHand'] += $item['QtyOnHand'];
                  $this->process_list[$item['ProductGuid']]['Items'][] = [
                        'WarehouseId' => $item['WarehouseId'],
                        'Warehouse' => $item['Warehouse'],
                        'WarehouseCode' => $item['WarehouseCode'],
                        'AvailableQty' => $item['AvailableQty'],
                        'QtyOnHand' => $item['QtyOnHand'],
                        'AllocatedQty' => $item['AllocatedQty']
                  ];
                } else {
                  $this->process_list[$item['ProductGuid']] = [
                     'ProductCode' => $item['ProductCode'],
                     'QtyOnHand' => $item['QtyOnHand'],
                     'Items' => [[
                        'WarehouseId' => $item['WarehouseId'],
                        'Warehouse' => $item['Warehouse'],
                        'WarehouseCode' => $item['WarehouseCode'],
                        'AvailableQty' => $item['AvailableQty'],
                        'QtyOnHand' => $item['QtyOnHand'],
                        'AllocatedQty' => $item['AllocatedQty']
                      ]]
                  ];
                }
              }
            }
          }
          $this->fetchPage++;
          if ($this->fetchPage>10) {
          //something wrong?
          \Yii::warning("#### wrong?<PRE>"  . __FILE__ .':' . __LINE__ . ' ' . print_r($this->process_list, 1) ."</PRE>");
            die;
          }
        } while ($this->hasMoreData);
      }
      //reset not empty (seems they are not shown in the feed);
      $q = \common\models\Products::find()
          ->andWhere('products_quantity>0')
          ->andWhere(['not in', 'products_model', \yii\helpers\ArrayHelper::getColumn($this->process_list, 'ProductCode')])
          ->select(['ProductCode' => 'products_model'])
          ->asArray()->all();
          ;
      $q = array_map(function($e) {$e['Items']=[]; return $e;}, $q);
      $this->process_list += $q;
      $this->total_count = count($this->process_list);
//\Yii::warning("#### <PRE>"  . __FILE__ .':' . __LINE__ . ' ' . print_r($this->process_list, 1) ."</PRE>");
      return true;
    }
/*
  "Items": [
    {
      "ProductCode": "BF1001-BS",
      "ProductDescription": "Whale Bay | Bedside | Elm",
      "ProductGuid": "610d3f27-3126-43d9-a760-578fcc11c7c5",
      "ProductSourceId": null,
      "ProductGroupName": "Bedroom Furniture",
      "WarehouseId": "cf70cbf9-fae6-491a-b0c4-c59de6ceb718",
      "Warehouse": "3PL Warehouse",
      "WarehouseCode": "3PL",
      "DaysSinceLastSale": null,
      "OnPurchase": 0,
      "AllocatedQty": 0,
      "AvailableQty": 46,
      "QtyOnHand": 46,
      "AvgCost": 111.46160714285713,
      "TotalCost": 5127.233929,
      "Guid": "610d3f27-3126-43d9-a760-578fcc11c7c5",
      "LastModifiedOn": "/Date(1562783765593)/"
    },
  */
    public function processRow(Messages $message)
    {
        $guid = key($this->process_list);

        $data = $this->process_list[$guid];
/* fewer calls
        $warehouses = Warehouses::get_warehouses();

        if ($data['QtyOnHand']>0  && count($warehouses)>1) {
          /// fetch stock by warehouses
          $response = $this->client->get('StockOnHand/' . $guid . '/AllWarehouses' )->send();
          $data = array_merge($data, $response->getData());
        } elseif ($data['QtyOnHand']>0 ) {
          $data['Items'][] = [
                    'Warehouse' => Warehouses::get_warehouse_name(Warehouses::get_default_warehouse()),
                    'AvailableQty' => $data['QtyOnHand']
          ];
        } else {
          $data['Items'] = [];
        }
        */
        /*if (is_null($this->defaultSupplierName) ) {
          $this->defaultSupplierName = Suppliers::getSupplierName( Suppliers::getDefaultSupplierId() );
        }*/
        $data['Supplier'] = $this->defaultSupplierName;

        $this->processProduct( $guid, $data, $message);

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
      $message->info('Done ');
    }

    protected function processProduct($guid, $data, Messages $message)
    {

      $keys = [
        'ProductCode' => ['set' => 'setModels'],
        //'ProductCode' => 'Main Model',
        'Items' => ['set' => 'setStocks'],
        //'Obsolete' => '',

      ];

      /*
   (
    [ProductCode] => CB-AK12-QUILT-Q
    [ProductDescription] => Pacific Sleep Medium | Quilt | Queen
    [ProductGuid] => 153c4f84-6622-41d4-9c32-62f6389e4c96
    [ProductSourceId] =>
    [ProductGroupName] => Quilt
    [WarehouseId] =>
    [Warehouse] =>
    [WarehouseCode] =>
    [DaysSinceLastSale] =>
    [OnPurchase] => 20
    [AllocatedQty] => 0
    [AvailableQty] => 11
    [QtyOnHand] => 11
    [AvgCost] => 99.510181818182
    [TotalCost] => 1094.612
    [Guid] => 153c4f84-6622-41d4-9c32-62f6389e4c96
    [LastModifiedOn] => DateTime Object
        (
            [date] => 2019-03-23 06:32:05.000000
            [timezone_type] => 3
            [timezone] => Pacific/Auckland
        )

    [Items] => Array
        (
            [0] => Array
                (
                    [WarehouseId] => ebb445e8-04b1-4445-8fac-1b497ad9493c
                    [Warehouse] => Main Warehouse
                    [AvailableQty] => 11
                )

            [1] => Array
                (
                    [WarehouseId] => 537138b4-a31f-4ae0-acf3-51dec0038e15
                    [Warehouse] => 3PL Warehouse
                    [AvailableQty] => 0
                )

        )

)

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
//$message->info(print_r($data,1));
        if ($data && is_array($data) && count($data) > 0) {
          try {
            $providerObj = new \backend\models\EP\Provider\WarehouseStock();

            $transform = new \backend\models\EP\Transform();
            $transform->setProviderColumns($providerObj->getColumns());
            $data = $transform->transform($data);

            $providerObj->importRow($data, $message);

          } catch (\Exception $e) {
            $message->info($e->getMessage());
            //$message->info(print_r($data,1));
          }
          $this->row_count++;
          return true;
        }

        return false;

    }

    public static function setModels(&$data, $fileField, $message) {
      $ret = true;

      $d = $data[$fileField];
      if (!empty($data[$fileField])) {
        $data['Main Model'] = $data[$fileField];
        $data['Inventory Model'] = $data[$fileField];
      } else {
        $message->info('Empty product code field - stock update is skipped');
        $ret = false;
      }
      unset($data[$fileField]);
      return $ret;
    }

    public static function setStocks(&$data, $fileField, $message) {
      $ret = true;
      \Yii::$app->storage->set('guid', 'unleashed-external-allocated-stock');

      $d = $data[$fileField];

      /*
            [0] => Array
                (
                    [WarehouseId] => ebb445e8-04b1-4445-8fac-1b497ad9493c
                    [Warehouse] => Main Warehouse
                    [AvailableQty] => 11
                )

            [1] => Array
                (
                    [WarehouseId] => 537138b4-a31f-4ae0-acf3-51dec0038e15
                    [Warehouse] => 3PL Warehouse
                    [AvailableQty] => 0
                       'AvailableQty' => $item['AvailableQty'],
                        'QtyOnHand' => $item['QtyOnHand'],
                        'AllocatedQty' => $item['AllocatedQty']
                )
       */
      $p = \common\models\Products::find()->andWhere(['products_model' => $data['Main Model']])
          ->select('products_id, products_model')
          ->asArray()->all();
      if ($p && count($p)==1) {
        $product = $p[0];
      } else {
        $product = false;
      }

      if (is_array($d) && $product) {
        $stockData = \yii\helpers\ArrayHelper::map($d, 'Warehouse', function ($e) {return $e;} );
        $warehouses = \common\helpers\Warehouses::get_warehouses(1);
        if (is_array($warehouses)) {
          $localAllocated = \common\helpers\Product::getAllocated($product['products_id']);

          foreach ($warehouses as $warehouse) {
            $qty = 0;
            if (isset($stockData[trim($warehouse['text'])])) {
              $rQtys = $stockData[trim($warehouse['text'])];
/*
  'WarehouseId' => $item['WarehouseId'],
 'Warehouse' => $item['Warehouse'],
 'WarehouseCode' => $item['WarehouseCode'],
 'AvailableQty' => $item['AvailableQty'],
 'QtyOnHand' => $item['QtyOnHand'],
 'AllocatedQty' => $item['AllocatedQty']
 */
              //some qty coud be allocated externaly, we suppose all orders are sent to unleashed (checked in 5 mins) stock import - hourly or so.
              /*if ($rQtys['AvailableQty'] > 0) {
                $qty = $rQtys['QtyOnHand'];
                if (!empty($rQtys['AllocatedQty']) && $localAllocated < $rQtys['AllocatedQty']) {
                  $qty += $localAllocated - $rQtys['AllocatedQty'];
                  $localAllocated = 0;// subtract from 1 warehouse only. Not good result as we don't have allocate per warehouse here
                }
              } else {
                $qty = $rQtys['AvailableQty'];
              }*/
              $qty = $rQtys['QtyOnHand'];

              /// save qty allocated externaly in temporaray stock
              \common\helpers\Product::remove_customers_temporary_stock_quantity($product['products_id'], $warehouse['id']);
              if (!empty($rQtys['AllocatedQty']) && $localAllocated < $rQtys['AllocatedQty']) {
                \common\helpers\Product::update_customers_temporary_stock_quantity($product['products_id'], $rQtys['AllocatedQty'] - $localAllocated , $warehouse['id'], 0, false , '', ' +72 hours ');
              }

            }
            $data['Warehouse ' . trim($warehouse['text'])] = $qty;
          }
        }
      }
      unset($data[$fileField]);
      return $ret;
    }


}