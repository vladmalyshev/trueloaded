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

class DownloadBOM implements DatasourceInterface {

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
  protected $transform = null;

  function __construct($config) {
    $this->config = $config;
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
    $this->fetchPage = 1;
    $this->hasMoreData = false;
    $this->client = new Client($this->config['client']['API_ID'], $this->config['client']['API_KEY']);

    $this->process_list = [];
    $this->fetchPage($message);

    reset($this->process_list);
    $message->info("Products for update - " . $this->total_count);
    $this->providerObj = new \backend\models\EP\Provider\Bundles();

    $this->transform = new \backend\models\EP\Transform();
    $this->transform->setProviderColumns($this->providerObj->getColumns());
  }

  protected function fetchPage(Messages $message) {
    $response = $this->client->get('BillOfMaterials/' . $this->fetchPage)->send();
    $data = $response->getData();

    $Pagination = $data['Pagination'];
    $this->hasMoreData = (int) $Pagination['PageNumber'] < (int) $Pagination['NumberOfPages'];
    $this->total_count = (int) $Pagination['NumberOfItems'];
    if (is_array($data['Items'])) {
      foreach ($data['Items'] as $item) {
        $this->process_list[$item['Guid']] = $item;
      }
    }
    return true;
  }

  public function processRow(Messages $message) {
    $guid = key($this->process_list);

    $this->processProduct($guid, $this->process_list[$guid], $message);

    if (next($this->process_list)) {
      return true;
    } elseif ($this->hasMoreData) {
      $this->process_list = [];
      $this->fetchPage++;
      $message->info('fetch page ' . $this->fetchPage);
      return $this->fetchPage($message);
    }
  }

  public function postProcess(Messages $message) {
    $this->providerObj->postProcess($message);
    $message->info('Done ');
  }

  protected function processProduct($guid, $data, Messages $message) {
//$message->info( "#### <PRE>" .print_r($data, 1) ."</PRE>");

    $keys = [
      'Product' => ['set' => 'setModels'],
      'BillOfMaterialsLines' => ['set' => 'setChildren'],
        //'ProductCode' => 'Main Model',
        //'Obsolete' => '',
    ];

    /*
      "Items": [
      {
      "BillNumber": "BOM-00000001",
      "CanAutoAssemble": true,
      "CanAutoDisassemble": false,
      "SortByProductCode": true,
      "Product": {
      "ProductCode": "BOOKSHELF",
      "ProductDescription": "Wooden Bookshelf",
      "Barcode": null,
      "PackSize": null,
      "Width": null,
      "Height": null,
      "Depth": null,
      "Weight": null,
      "MinStockAlertLevel": null,
      "MaxStockAlertLevel": null,
      "ReOrderPoint": null,
      "UnitOfMeasure": {
      "Guid": "32083b21-51ad-4593-b7ea-aa726501ac83",
      "Name": "EA"
      },
      "NeverDiminishing": false,
      "LastCost": 1790.7124,
      "DefaultPurchasePrice": 1987,
      "DefaultSellPrice": 2499.99,
      "CustomerSellPrice": null,
      "AverageLandPrice": null,
      "Obsolete": false,
      "Notes": null,
      "Images": null,
      "ImageUrl": null,
      "SellPriceTier1": null,
      "SellPriceTier2": null,
      "SellPriceTier3": null,
      "SellPriceTier4": null,
      "SellPriceTier5": null,
      "SellPriceTier6": null,
      "SellPriceTier7": null,
      "SellPriceTier8": null,
      "SellPriceTier9": null,
      "SellPriceTier10": null,
      "XeroTaxCode": null,
      "XeroTaxRate": null,
      "TaxablePurchase": true,
      "TaxableSales": true,
      "XeroSalesTaxCode": null,
      "XeroSalesTaxRate": null,
      "IsComponent": false,
      "IsAssembledProduct": true,
      "ProductGroup": {
      "GroupName": "Furniture",
      "Guid": "668e4652-0180-4529-86ee-16ddf713056e",
      "LastModifiedOn": "/Date(1499611560146)/"
      },
      "XeroSalesAccount": null,
      "XeroCostOfGoodsAccount": null,
      "PurchaseAccount": null,
      "BinLocation": null,
      "Supplier": null,
      "AttributeSet": null,
      "SourceId": null,
      "SourceVariantParentId": null,
      "IsSerialized": false,
      "IsBatchTracked": false,
      "IsSellable": true,
      "CreatedBy": "admin",
      "CreatedOn": "/Date(1499611560146)/",
      "LastModifiedBy": null,
      "Guid": "76276056-6218-4987-be89-846ecbfe64b2",
      "LastModifiedOn": "/Date(1562683560200)/"
      },
      "BillOfMaterialsLines": [
      {
      "LineNumber": 1,
      "Product": {
      "ProductCode": "SIDE",
      "ProductDescription": "Bookshelf Side",
      "Barcode": null,
      "PackSize": null,
      "Width": null,
      "Height": null,
      "Depth": null,
      "Weight": null,
      "MinStockAlertLevel": 2,
      "MaxStockAlertLevel": 15,
      "ReOrderPoint": null,
      "UnitOfMeasure": {
      "Guid": "32083b21-51ad-4593-b7ea-aa726501ac83",
      "Name": "EA"
      },
      "NeverDiminishing": false,
      "LastCost": 827.0122,
      "DefaultPurchasePrice": 785,
      "DefaultSellPrice": 1699,
      "CustomerSellPrice": null,
      "AverageLandPrice": null,
      "Obsolete": false,
      "Notes": null,
      "Images": null,
      "ImageUrl": null,
      "SellPriceTier1": null,
      "SellPriceTier2": null,
      "SellPriceTier3": null,
      "SellPriceTier4": null,
      "SellPriceTier5": null,
      "SellPriceTier6": null,
      "SellPriceTier7": null,
      "SellPriceTier8": null,
      "SellPriceTier9": null,
      "SellPriceTier10": null,
      "XeroTaxCode": null,
      "XeroTaxRate": null,
      "TaxablePurchase": true,
      "TaxableSales": true,
      "XeroSalesTaxCode": null,
      "XeroSalesTaxRate": null,
      "IsComponent": true,
      "IsAssembledProduct": false,
      "ProductGroup": {
      "GroupName": "Material",
      "Guid": "8e05619b-2d52-4ac3-8a45-9ff3b5237cab",
      "LastModifiedOn": "/Date(1499611560146)/"
      },
      "XeroSalesAccount": null,
      "XeroCostOfGoodsAccount": null,
      "PurchaseAccount": null,
      "BinLocation": null,
      "Supplier": null,
      "AttributeSet": null,
      "SourceId": null,
      "SourceVariantParentId": null,
      "IsSerialized": false,
      "IsBatchTracked": false,
      "IsSellable": true,
      "CreatedBy": "admin",
      "CreatedOn": "/Date(1499611560146)/",
      "LastModifiedBy": null,
      "Guid": "7c2f2c95-a5ce-4ca9-ae44-eb5195033ef2",
      "LastModifiedOn": "/Date(1562683560200)/"
      },
      "Quantity": 1,
      "WastageQuantity": 0,
      "LineTotalCost": 804.39,
      "UnitCost": 804.3902,
      "SubBillOfMaterialGuid": null,
      "ExpenseAccount": null,
      "CreatedOn": "/Date(1499611560146)/",
      "CreatedBy": "admin",
      "LastModifiedBy": "admin",
      "Guid": "a8e93867-ace1-49cc-a83a-793370bb8bdc",
      "LastModifiedOn": "/Date(1499611560146)/"
      },
      ....
      ],
      "TotalCost": 1790.71,
      "Obsolete": false,
      "CreatedOn": "/Date(1499611560146)/",
      "CreatedBy": "admin",
      "LastModifiedBy": "admin",
      "AssemblyLayoutId": null,
      "Guid": "b12cebb8-bc7a-4ed8-a29c-c08e0b540671",
      "LastModifiedOn": "/Date(1499611560146)/"
      }
      ]

     */

    $secondChance = []; //not used now
    foreach ($keys as $fileField => $d) {
      if (isset($data[$fileField]) && is_array($d)) {

        if (isset($d['set']) && method_exists($this, $d['set'])) {
          $r = call_user_func_array(array($this, $d['set']), array(&$data, $fileField, &$message));
          if ($r !== true) {
            $message->info($r);
            return;
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

      if (!empty($data['children']) && is_array($data['children'])) {
        $children = $data['children'];
        unset($data['children']);
        $product = $data;

        foreach ($children as $child) {
          $data = $this->transform->transform(array_merge($product, $child));
          try {
            $this->providerObj->importRow($data, $message);
          } catch (\Exception $e) {
            $message->info('Error: ' . $e->getMessage());
            \Yii::warning($e->getMessage() . ' ' . print_r($data, 1) . ' ' . $e->getTraceAsString());
          }
          $this->row_count++;
        }
        return true;
      } else {
        $message->info('empty child data');
      }
    } else {
      $message->info('empty data');
    }

    return false;
  }

  public static function setModels(&$data, $fileField, $message) {
    $ret = true;

    $d = $data[$fileField]['ProductCode'];
    if (!empty($d)) {
      $data['Products Model'] = $d;
    } else {
      $message->info('Empty product code field - update is skipped');
      $ret = false;
    }
    unset($data[$fileField]);
    return $ret;
  }

  public static function setChildren(&$data, $fileField, $message) {
    $ret = true;
    if (empty($data['children'])) {
      $data['children'] = [];
    }
/*
    {
      "LineNumber": 1,
      "Product": {
      "ProductCode": "SIDE",
      "ProductDescription": "Bookshelf Side",
      "Barcode": null,
      "PackSize": null,
      "Width": null,
      "Height": null,
      "Depth": null,
      "Weight": null,
      "MinStockAlertLevel": 2,
      "MaxStockAlertLevel": 15,
      "ReOrderPoint": null,
      "UnitOfMeasure": {
      "Guid": "32083b21-51ad-4593-b7ea-aa726501ac83",
      "Name": "EA"
      },
      "NeverDiminishing": false,
      "LastCost": 827.0122,
      "DefaultPurchasePrice": 785,
      "DefaultSellPrice": 1699,
      "CustomerSellPrice": null,
      "AverageLandPrice": null,
      "Obsolete": false,
      "Notes": null,
      "Images": null,
      "ImageUrl": null,
      "SellPriceTier1": null,
      "SellPriceTier2": null,
      "SellPriceTier3": null,
      "SellPriceTier4": null,
      "SellPriceTier5": null,
      "SellPriceTier6": null,
      "SellPriceTier7": null,
      "SellPriceTier8": null,
      "SellPriceTier9": null,
      "SellPriceTier10": null,
      "XeroTaxCode": null,
      "XeroTaxRate": null,
      "TaxablePurchase": true,
      "TaxableSales": true,
      "XeroSalesTaxCode": null,
      "XeroSalesTaxRate": null,
      "IsComponent": true,
      "IsAssembledProduct": false,
      "ProductGroup": {
      "GroupName": "Material",
      "Guid": "8e05619b-2d52-4ac3-8a45-9ff3b5237cab",
      "LastModifiedOn": "/Date(1499611560146)/"
      },
      "XeroSalesAccount": null,
      "XeroCostOfGoodsAccount": null,
      "PurchaseAccount": null,
      "BinLocation": null,
      "Supplier": null,
      "AttributeSet": null,
      "SourceId": null,
      "SourceVariantParentId": null,
      "IsSerialized": false,
      "IsBatchTracked": false,
      "IsSellable": true,
      "CreatedBy": "admin",
      "CreatedOn": "/Date(1499611560146)/",
      "LastModifiedBy": null,
      "Guid": "7c2f2c95-a5ce-4ca9-ae44-eb5195033ef2",
      "LastModifiedOn": "/Date(1562683560200)/"
      },
      "Quantity": 1,
      "WastageQuantity": 0,
      "LineTotalCost": 804.39,
      "UnitCost": 804.3902,
      "SubBillOfMaterialGuid": null,
      "ExpenseAccount": null,
      "CreatedOn": "/Date(1499611560146)/",
      "CreatedBy": "admin",
      "LastModifiedBy": "admin",
      "Guid": "a8e93867-ace1-49cc-a83a-793370bb8bdc",
      "LastModifiedOn": "/Date(1499611560146)/"
    */

    $d = $data[$fileField];

    if (!empty($d) && is_array($d)) {

      foreach ($d as $v) {
        $child = [];
        $child['Child Products Model'] = !empty($v['Product']['ProductCode'])?$v['Product']['ProductCode']:''; // provider should show correct error message
        $child['Products Quantity'] = $v['Quantity'];
        $child['Products Sort order'] = $v['LineNumber'];
        $data['children'][] = $child;
      }

    } else {
      $message->info('Empty children field - skipped');
      $ret = false;
    }
    unset($data[$fileField]);
    return $ret;
  }


}
