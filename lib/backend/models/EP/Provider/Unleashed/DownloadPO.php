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

class DownloadPO implements DatasourceInterface
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

    protected $POExists = null;


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

        $message->info("PO for check - ".$this->total_count);
    }

    protected function fetchPage(Messages $message)
    {
        $response = $this->client->get('PurchaseOrders/'.$this->fetchPage,  'pageSize=500&modifiedSince=' . date('Y-m-d\TH:i:s', strtotime('-2 years')) )->send();
        $data = $response->getData();

        $Pagination = $data['Pagination'];
        $this->hasMoreData = (int)$Pagination['PageNumber'] < (int)$Pagination['NumberOfPages'];
        $this->total_count = (int)$Pagination['NumberOfItems'];
        foreach ($data['Items'] as $item){
            $this->process_list[$item['Guid']] = $item;
        }
        return true;
    }

    public function processRow(Messages $message)
    {
        $guid = key($this->process_list);

        $this->processPO( $guid, $this->process_list[$guid], $message);

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

    protected function processPO($guid, $data, Messages $message)
    {
        \Yii::$container->setSingleton('products', '\common\components\ProductsContainer');

        $platform_config = \Yii::$app->get('platform')->config(\common\classes\platform::defaultId());
        $platform_config->constant_up();

        $tmp = \common\helpers\Language::get_language_id($platform_config->getDefaultLanguage());
        $languages_id = $tmp['languages_id'];
        \Yii::$app->settings->set('languages_id', $languages_id);
        
//echo "#### input <PRE>" .print_r($data, 1) ."</PRE>";
            /*
 {
      "OrderNumber": "PO-00000002",
      "OrderDate": "/Date(1499957160146)/",
      "RequiredDate": "/Date(1499957160146)/",
      "CompletedDate": null,
      "Supplier": {
        "Guid": "bad7f433-44bd-4760-9ebd-c865c1ba4859",
        "SupplierCode": "RANMEGA",
        "SupplierName": "Ranwell Megastore"
      },
      "SupplierRef": null,
      "Comments": null,
      "Printed": null,
      "OrderStatus": "Complete",
      "ReceivedDate": null,
      "DeliveryName": null,
      "DeliveryStreetAddress": "21143 Falls Ridge Way",
      "DeliveryStreetAddress2": "Palm Beach",
      "DeliverySuburb": "Boca Raton",
      "DeliveryRegion": "Florida",
      "DeliveryCity": "Boca Raton",
      "DeliveryCountry": "United States",
      "DeliveryPostCode": "33428",
      "Currency": {
        "CurrencyCode": "AUD",
        "Description": "Australia, Dollars",
        "Guid": "0eedca9e-b8db-4f3f-b7bb-ad1b129f0c48",
        "LastModifiedOn": "/Date(1562535217383)/"
      },
      "ExchangeRate": 0.9492,
      "Tax": {
        "TaxCode": "NONE",
        "Description": null,
        "TaxRate": 0,
        "CanApplyToExpenses": false,
        "CanApplyToRevenue": false,
        "Obsolete": false,
        "Guid": "00000000-0000-0000-0000-000000000000",
        "LastModifiedOn": null
      },
      "TaxRate": 0,
      "XeroTaxCode": "NONE",
      "SubTotal": 5774.8,
      "TaxTotal": 0,
      "Total": 5774.8,
      "TotalVolume": 0,
      "TotalWeight": 0,
      "SupplierInvoiceDate": "/Date(1499957160146)/",
      "BCSubTotal": 6083.85,
      "BCTaxTotal": 0,
      "BCTotal": 6083.85,
      "PurchaseOrderLines": [
        {
          "Guid": "6227a33d-ed13-4967-b54b-fc03604367a6",
          "LineNumber": 1,
          "Product": {
            "Guid": "928fb35c-9d26-4266-9e54-4ab11771c430",
            "ProductCode": "DININGCHAIR",
            "ProductDescription": "Dining Chair"
          },
          "DueDate": "/Date(1499957160147)/",
          "OrderQuantity": 84,
          "UnitPrice": 12.2,
          "LineTotal": 1024.8,
          "Volume": null,
          "Weight": null,
          "Comments": null,
          "ReceiptQuantity": null,
          "BCUnitPrice": 12.8529,
          "BCSubTotal": 1079.64,
          "Tax": null,
          "LineTax": 0,
          "LastModifiedOn": "/Date(1499957160146)/",
          "DiscountedUnitPrice": 12.2,
          "DiscountRate": 0
        },

      ],
      "Warehouse": {
        "WarehouseCode": "MAIN",
        "WarehouseName": "Main Warehouse",
        "IsDefault": true,
        "StreetNo": null,
        "AddressLine1": null,
        "AddressLine2": null,
        "Suburb": null,
        "City": null,
        "Region": null,
        "Country": null,
        "PostCode": null,
        "PhoneNumber": null,
        "FaxNumber": null,
        "MobileNumber": null,
        "DDINumber": null,
        "ContactName": null,
        "Obsolete": false,
        "Guid": "a538ffec-a633-4b01-9be8-ce975e2e787c",
        "LastModifiedOn": "/Date(1562683560176)/"
      },
      "DiscountRate": 0,
      "SalesOrders": [],
      "CreatedOn": "/Date(1499957160146)/",
      "CreatedBy": "admin",
      "LastModifiedBy": "admin",
      "Guid": "c2934b70-19de-4daf-9c0c-0c0503e7fb99",
      "LastModifiedOn": "/Date(1499957160146)/"
    },

       */

      static::findPO($data, $this->config['directoryId']);

        if ($data && is_array($data)) {
          if (!$data['POExists'] && in_array($data['OrderStatus'], ['Parked', 'Unapproved' /*, 'Deleted'*/])) {
            return true;
          }

          if (true) {

            if ($data['POExists']&&  isset($data['LastModifiedOn']) && $data['LastModifiedOn']>0 ) {
              $_last_modified = $data['LastModifiedOn'];
              $local = date_create($data['POExists']['last_modified']);
              if ($_last_modified <= $local) {
                //not changed
                return true;
              }
            }


          /////////////////////////////////////////////
          //
            $supplier = self::findSupplier($data, $message);
            if (!is_array($supplier)) {
              return false;
            } elseif( !$supplier['status']) {
              $message->info('Inactive supplier - skipped ' . $data['OrderNumber'] . ' ' . $data['Supplier']['SupplierCode']);
              return false;
            }
            $warehouse = self::findWarehouse($data, $message);
            if (!is_array($warehouse)) {
              return false;
            }

            if ($data['OrderStatus'] == 'Complete') {
              $poStatus = $this->config['order']['po_complete_status'];
            } elseif ($data['OrderStatus'] == 'Deleted') {
              $poStatus = $this->config['order']['po_cancel_status'];
            } else {
              $poStatus = null;
            }
            
            $order = new \common\extensions\PurchaseOrders\classes\PurchaseOrder(empty($data['POExists'])?'':$data['POExists']['orders_id']);
            $currency = !empty($data['Currency']['CurrencyCode'])?strtoupper($data['Currency']['CurrencyCode']):DEFAULT_CURRENCY;
            $order->setInfo([
              'currency' => $currency,
              'currency_value' => !empty($data['ExchangeRate'])?$data['ExchangeRate']:1,
              'language_id' => $languages_id
                ]);
            $order->setWarehouse($warehouse['warehouse_id']);
            $order->setSupplier($supplier['suppliers_id']);

            if (!is_array($data['PurchaseOrderLines'])) {
              $message->info('No products - skipped ' . $data['OrderNumber'] . ' ' . $data['Supplier']['SupplierCode']);
              return false;
            }
            
            ///vl2check $purchase->not_available = ($data['OrderStatus'] == 'Complete'?1:0);

            if (empty($data['POExists']) && (!empty($data['Comments']) || !empty($data['OrderNumber'])) ) {
              $order->setInfo(['comments' => 
                                (!empty($data['OrderNumber'])?$data['OrderNumber'] . "\n\n":'') .
                                (!empty($data['SupplierRef'])?$data['SupplierRef'] . "\n\n":'') .
                                $data['Comments']
                  ]);
            }
/*
            if ( !empty($data['SupplierRef']) ) {
              $order->info['purchase_order'] = $data['SupplierRef'];
            }
 */
            if ( isset($data['RequiredDate']) && $data['RequiredDate']>0 ) {
              $order->setInfo(['delivery_date' => date_format($data['RequiredDate'], 'Y-m-d H:i:s')]);
            }
            if ( isset($data['CompletedDate']) ) {
              if ( $data['CompletedDate']>0 ) {
                $order->setInfo(['delivery_date' => date_format($data['CompletedDate'], 'Y-m-d H:i:s')]);
              }
            }
            if ( isset($data['OrderDate']) && $data['OrderDate']>0 ) {
                $order->setInfo(['date_purchased' => date_format($data['OrderDate'], 'Y-m-d H:i:s')]);
            }
            if ( isset($data['LastModifiedOn']) && $data['LastModifiedOn']>0 ) {
              $_last_modified = date_format($data['LastModifiedOn'], 'Y-m-d H:i:s');
              $order->setInfo(['last_modified' => $_last_modified]);
            }

            if (!is_null($poStatus)) {
              $order->info['orders_status'] = $poStatus;
            }

            try {
  /*
                'warehouse_id' => $warehouseId,
                'delivery_name' => $warehouse_address['owner'],
                'delivery_firstname' => $warehouse_address['firstname'],
                'delivery_lastname' => $warehouse_address['lastname'],
                'delivery_company' => $warehouse_address['company'],
                'delivery_street_address' => $warehouse_address['street_address'],
                'delivery_suburb' => $warehouse_address['suburb'],
                'delivery_city' => $warehouse_address['city'],
                'delivery_postcode' => $warehouse_address['postcode'],
                'delivery_state' => $warehouse_address['state'],
                'delivery_country' => $warehouse_address['country_name'],
                'delivery_email_address' => $warehouse_address['email_address'],
                'delivery_telephone' => $warehouse_address['telephone'],
                'delivery_company_vat' => $warehouse_address['entry_company_vat'],
                'delivery_address_format_id' => $warehouse_address['address_format_id'],
   */
              if (!empty($data['DeliveryName'])) {
                $order->setInfo(['delivery_company' => $data['DeliveryName']]);
              }
              if (!empty($data['DeliveryStreetAddress'])) {
                $order->setInfo(['delivery_street_address' => $data['DeliveryStreetAddress']]);
              }
              if (!empty($data['DeliveryStreetAddress2']) || !empty($data['DeliverySuburb'])) {
                $order->setInfo(['delivery_suburb' => trim($data['DeliveryStreetAddress2'] . ' ' . $data['DeliverySuburb'])]);
              }
              if (!empty($data['DeliveryCity'])) {
                $order->setInfo(['delivery_city' => $data['DeliveryCity']]);
              }
              if (!empty($data['DeliveryPostCode'])) {
                $order->setInfo(['delivery_postcode' => $data['DeliveryPostCode']]);
              }
              if (!empty($data['DeliveryRegion'])) {
                $order->setInfo(['delivery_state' => $data['DeliveryRegion']]);
              }
              if (!empty($data['DeliveryCountry'])) {
                $order->setInfo(['delivery_country' => $data['DeliveryCountry']]);
              }

              $_notUpdatedProducts = $order->products;
              $_addedQtys = [];
              //$order->products = [];
              foreach ($data['PurchaseOrderLines'] as $prod) {
                $qty = $prod['OrderQuantity'];
                $supplierProduct = self::findProduct($prod, $supplier['suppliers_id'],  $message);
                if (!is_array($supplierProduct)) {
                  return false;
                }
                $uprid = $supplierProduct['uprid'];
                if ($qty > 0) {
                    $attrib = array();
                    if (strpos($uprid, '{') !== false) {
                        $ar = preg_split('/[\{\}]/', $uprid);
                        for ($i = 1; $i < sizeof($ar); $i = $i + 2) {
                            if (isset($ar[$i + 1])) {
                                $attrib[$ar[$i]] = $ar[$i + 1];
                            }
                        }
                    }

                    try {
                      //$purchase->add_cart(\common\helpers\Inventory::get_prid($uprid), $qty, $attrib);
                      $product = $prod;
                      $product['id'] = $uprid;
                      if (!empty($_addedQtys[$uprid])) {
                        $qty += $_addedQtys[$uprid];
                      }
                      $_addedQtys[$uprid] = $qty;
                      
                      $product['qty'] = $qty;
                      $product['name'] = $prod['Product']['ProductDescription'];
                      $product['model'] = $prod['Product']['ProductCode'];
                      $product['tax'] = (is_null($prod['Tax'])?0:$prod['Tax']*100);
                      $product['price'] = (is_null($prod['UnitPrice'])?0:$prod['UnitPrice']);
                      //$product['final_price'] = (is_null($prod['UnitPrice'])?0:$prod['UnitPrice']);
                      if ($data['OrderStatus'] == 'Complete') {
                        $product['qty_rcvd'] = $product['qty'];
                        $product['orders_products_status'] = \common\helpers\OrderProduct::OPS_RECEIVED;
                      } elseif ($data['OrderStatus'] == 'Deleted') {
                        $product['qty_rcvd'] = 0;
                        $product['orders_products_status'] = \common\helpers\OrderProduct::OPS_CANCELLED;
                      } else {
                        $product['orders_products_status'] = \common\helpers\OrderProduct::OPS_STOCK_ORDERED;
                      }
                      $product['attribute'] = ''; ///vl2 :( not array any more
                      $product['sort_order'] = $prod['LineNumber'];
                      if (!empty($data['POExists'])) {
                        $order->update_product($product);
                        unset($_notUpdatedProducts[$product['id']]);
                      } else {
                        $order->add_product($product);
                      }

                      /* */
                    } catch (\Exception $ex) {
                      $message->info('addProductToOrder exception' . $ex->getCode() . ' ' . $ex->getTraceAsString());
                      return false;
                    }
                }

              }
              
              if (is_array($_notUpdatedProducts)) {
                foreach ($_notUpdatedProducts as $key => $value) {
                  $order->remove_product($key);
                }
              }


            } catch (\Exception $ex) {
              $message->info('Process PO exception ' . $ex->getMessage() . ' ' . $ex->getTraceAsString());
              return false;
            }


            

/*
            $currencies = \Yii::$container->get('currencies');
            $order->totals = [];
            if (isset($data['SubTotal'])) {
              $total = [
                'class' => 'ot_subtotal',
                'value' => $data['SubTotal'],
                'title' => 'Subtotal:',
                'text' => $currencies->format($data['SubTotal'], false, $order->info['currency'], $order->info['currency_value']),
                'sort_order' => 1,
                'text_exc_tax' => $currencies->format($data['SubTotal'], false, $order->info['currency'], $order->info['currency_value']),
                'text_inc_tax' => $currencies->format($data['SubTotal']+$data['TaxTotal'], false, $order->info['currency'], $order->info['currency_value']),
                'value_exc_vat' => $data['SubTotal'],
                'value_inc_tax' => $data['SubTotal']+$data['TaxTotal'],
                'is_removed' => 0,
                'currency' => $order->info['currency'],
                'currency_value' => $order->info['currency_value'],
              ];
              $order->totals[] = $total;
            }
            if (!empty($data['TaxTotal'])) {
              $total = [
                'class' => 'ot_tax',
                'value' => $data['TaxTotal'],
                'title' => 'Tax:',
                'text' => $currencies->format($data['TaxTotal'], false, $order->info['currency'], $order->info['currency_value']),
                'sort_order' => 2,
                'text_exc_tax' => $currencies->format($data['TaxTotal'], false, $order->info['currency'], $order->info['currency_value']),
                'text_inc_tax' => $currencies->format($data['TaxTotal'], false, $order->info['currency'], $order->info['currency_value']),
                'value_exc_vat' => $data['TaxTotal'],
                'value_inc_tax' => $data['TaxTotal'],
                'is_removed' => 0,
                'currency' => $order->info['currency'],
                'currency_value' => $order->info['currency_value'],
              ];
              $order->totals[] = $total;
            }
            if (isset($data['Total'])) {
              $total = [
                'class' => 'ot_total',
                'value' => $data['Total'],
                'title' => 'Total:',
                'text' => $currencies->format($data['Total'], false, $order->info['currency'], $order->info['currency_value']),
                'sort_order' => 1,
                'text_exc_tax' => $currencies->format($data['Total'], false, $order->info['currency'], $order->info['currency_value']),
                'text_inc_tax' => $currencies->format($data['Total'], false, $order->info['currency'], $order->info['currency_value']),
                'value_exc_vat' => $data['Total'],
                'value_inc_tax' => $data['Total'],
                'is_removed' => 0,
                'currency' => $order->info['currency'],
                'currency_value' => $order->info['currency_value'],
              ];
              $order->totals[] = $total;
            }
*/


          try {
            //$order->total_process(); //debug

//echo "#### <PRE>"  . __FILE__ .':' . __LINE__ . ' ' . print_r($order, 1) ."</PRE>"; return;

            $order->save_order(empty($data['POExists'])?'':$data['POExists']['orders_id']);

            $order->total_process();
            $order->save_products();
            $order->save_details();


          } catch (\Exception $ex) {
            $message->info('Save PO Exception '. $ex->getMessage() . ' ' . $ex->getTraceAsString());
            return false;
          }

          if (!$data['POExists']) {
            tep_db_perform(
                'ep_holbi_soap_link_orders',
                [
                    'ep_directory_id' => (int)$this->config['directoryId'],
                    'remote_orders_id' => $order->order_id,
                    'local_orders_id' => $order->order_id,
                    'remote_order_number' => $data['OrderNumber'],
                    'track_remote_order' => 1,
                    'date_exported' => 'now()',
                    'cfg_export_as' => 'PO_PO',
                    'local_order_last_modified' => 'now()',
                ]
            );
            $message->info('[!] Created ' . $order->order_id . ' from ' . $data['OrderNumber']);
          } else {
            $sql_data =
                [
                    'local_order_last_modified' => 'now()',
                ];
            if ( in_array($data['OrderStatus'], ['Complete', 'Deleted'])) {
              $sql_data['track_remote_order'] = 0;
            }
            tep_db_perform(
                'ep_holbi_soap_link_orders', $sql_data,
                'update',
                "ep_directory_id = '" .(int)$this->config['directoryId'] . "' and local_orders_id = '" . $order->order_id . "' and cfg_export_as = 'PO_PO'"
            );
            $message->info('[+] Updated ' . $order->order_id . ' from ' . $data['OrderNumber']);
          }

          $this->row_count++;
          return true;
        }
      }
      return false;

    }

  public static function findPO(&$d, $directoryId) {
    if (!isset($d['POExists']) ) {
      $q = new \yii\db\Query();
      $q->from('ep_holbi_soap_link_orders');
      $q->select('local_orders_id as orders_id, local_order_last_modified as last_modified ');
      $q->andWhere([
        'ep_directory_id' => (int)$directoryId,
        'remote_order_number' => $d['OrderNumber'],
        'cfg_export_as' => 'PO_PO',
      ]);

      if ($q->count()>0) {
        $d['POExists'] = $q->one();
      } else {
        $d['POExists'] = false;
      }
    }
  }

/**
 * find supplier by $d['SupplierCode'] or $d['SupplierName'] (any first :( )
 * @param array $d
 * @param type $message
 * @return array|false
 */
  public static function findSupplier($data, $message)
  {
    $ret = false;
    $d = $data['Supplier'];
    $q = \common\models\Suppliers::find()->andWhere([
      'or',
      ['suppliers_name' => [$d['SupplierCode'], $d['SupplierName']]],
      ['company' => [$d['SupplierCode'], $d['SupplierName']]],
    ]);
    if ($q->count()>0) {
      $ret = $q->asArray()->one();
    } else {
      $message->info('Supplier not found ' . implode(' ', [$d['SupplierCode'], $d['SupplierName']]));
    }
    return $ret;
  }

/**
 * find Warehouse by $d['WarehouseCode'] or $d['WarehouseName'] (any first :( )
 * @param array $d
 * @param type $message
 * @return array|false
 */
  public static function findWarehouse($data, $message)
  {
    $ret = false;
    $d = $data['Warehouse'];
    /*      "Warehouse": {
        "WarehouseCode": "MAIN",
        "WarehouseName": "Main Warehouse",*/
    $q = \common\models\Warehouses::find()->andWhere([
      'or',
      ['warehouse_name' => [$d['WarehouseCode'], $d['WarehouseName']]],
      ['warehouse_owner' => [$d['WarehouseCode'], $d['WarehouseName']]],
    ]);
    if ($q->count()>0) {
      $ret = $q->asArray()->one();
    } else {
      $message->info('Warehouse not found ' . implode(' ', [$d['WarehouseCode'], $d['WarehouseName']]));
    }
    return $ret;
  }


/**
 * find supplier Product
 * @param array $data
 * @param int $suppliers_id
 * @param type $message
 * @return array|false
 */
  public static function findProduct($data, $suppliers_id, $message)
  {
    $ret = false;
    $model = $data['Product']['ProductCode'];
    $q = \common\models\SuppliersProducts::find()->andWhere([
      'and',
      ['suppliers_id' => $suppliers_id],
      [ 'or',
        ['suppliers_model' => $model],
        ['suppliers_ean' => $model],
        ['suppliers_asin' => $model],
        ['suppliers_isbn' => $model],
        ['suppliers_upc' => $model],
        ['products_id' => (new \yii\db\Query())->from('products')->select('products_id')->where(['products_model' => $model])]
      ]
    ]);

    if ($q->count()==1) {
      $ret = $q->asArray()->one();
    } else {
      $q = (new \yii\db\Query())->from('products')->select('products_id')->where(['products_model' => $model]);
      if ($q->count()==1) {
        try {
          $p = $q->one();
          $sp = new \common\models\SuppliersProducts();
          /*
          "UnitPrice": 12.2,
          "LineTotal": 1024.8,
          "Volume": null,
          "Weight": null,
          "Comments": null,
          "ReceiptQuantity": null,
          "BCUnitPrice": 12.8529,
          "BCSubTotal": 1079.64,
          "Tax": null,
          "LineTax": 0,
          "LastModifiedOn": "/Date(1499957160146)/",
          "DiscountedUnitPrice": 12.2,
          "DiscountRate": 0
           */
          // default currency - could be incorrect.....
          $sp->loadDefaultValues();
          $sp->setAttributes([
            'products_id' => $p['products_id'],
            'uprid' => $p['products_id'],
            'suppliers_id' => $suppliers_id,
            'date_added' => date('Y-m-d H:i:s'),
            'suppliers_price' => floatval($data['UnitPrice']),
          ]);
          if (!empty($data['Tax']) && floatval($data['Tax'])>0) {
            $sp->tax_rate = floatval($data['Tax'])*100;
          }
          if (!empty($data['DiscountRate']) && floatval($data['DiscountRate'])>0) {
            $sp->supplier_discount = floatval($data['DiscountRate'])*100;
          }
          if (!empty($data['Comments'])) {
            $sp->notes = $data['Comments'];
          }
          $sp->save(false);
          $ret = $sp->getAttributes();
        } catch (Exception $ex) {
          $message->info('Cant link product to supplier ' . implode(' ', $data['Product']));
        }

      }
      $message->info('Product not found ' . ($q->count()>0?'(not unique model)':'') . ' ' . implode(' ', $data['Product']));
      \Yii::warning('Product not found (not unique model) ' . implode(' ', $data['Product']) . ' count ' . $q->count() . ' sql ' . $q->createCommand()->rawSql, 'UNLEASHED_PO');
    }
    return $ret;
  }

/**
 * @deprecated
 * @param Messages $message
 * @param type $product
 * @param type $order
 */
  private function addProductToOrder(Messages $message, $product, &$order)
  {

        $ordered_attributes = [];
        /*
        if ( isset($product['attributes']) ){
            $attributes = isset($product['attributes']['attribute'])?$product['attributes']['attribute']:[];
            if ( !ArrayHelper::isIndexed($attributes) ) $attributes = [$attributes];
            unset($product['attributes']);

            foreach( $attributes as $attribute ) {
                $ordered_attributes[] = [
                    'option' => $attribute['option_name'],
                    'value' => $attribute['option_value_name'],
                    'option_id' => $this->lookupLocalProductOptionId($attribute['option_id']),
                    'value_id' => $this->lookupLocalProductOptionValueId($attribute['value_id']),
                ];
            }
        }*/
        $order->products[] = [
            'qty' => $product['qty'],
            //'reserved_qty' => $products[$i]['reserved_qty'],
            'name' => $product['name'],
            'model' => $product['model'],
            //'stock_info' => $products[$i]['stock_info'],
            //'products_file' => $products[$i]['products_file'],
            'is_virtual' => isset($product['is_virtual']) ? intval($product['is_virtual']) : 0,
            'gv_state' => (preg_match('/^GIFT/', $product['model']) ? 'pending' : 'none'),
            'tax' => $product['tax'], //\common\helpers\Tax::get_tax_rate($products[$i]['tax_class_id'], $this->tax_address['entry_country_id'], $this->tax_address['entry_zone_id']),
            //'tax_class_id' => $products[$i]['tax_class_id'],
            //'tax_description' => \common\helpers\Tax::get_tax_description($products[$i]['tax_class_id'], $this->tax_address['entry_country_id'], $this->tax_address['entry_zone_id']),
            'ga' => isset($product['ga']) ? intval($product['ga']) : 0,
            'price' => $product['price'],
            'final_price' => $product['final_price'], //$products[$i]['price'] + $cart->attributes_price($products[$i]['id'], $products[$i]['quantity']),
            //'weight' => $products[$i]['weight'],
            'gift_wrap_price' => $product['gift_wrap_price'],
            'gift_wrapped' => $product['gift_wrapped'],
            //'gift_wrap_allowed' => $products[$i]['gift_wrap_allowed'],
            //'virtual_gift_card' => $products[$i]['virtual_gift_card'],
            'id' => \common\helpers\Inventory::normalize_id($product['id']),
            //'subscription' => $products[$i]['subscription'],
            //'subscription_code' => $products[$i]['subscription_code'],
            //'overwritten' => $products[$i]['overwritten']
            'attributes' => $ordered_attributes,
            //'packs' => (int)$product['packs'],
            //'units'=> (int)$product['units'],
            //'packagings' => (int)$product['packagings'],
            //'packs_price' => $product['packs_price'],
            //'units_price'=> $product['units_price'],
            //'packagings_price' => $product['packagings_price'],
          'qty_rcvd' => $product['completed']?$product['qty']:0,
          'orders_products_status' => $product['completed']?\common\helpers\OrderProduct::OPS_RECEIVED :\common\helpers\OrderProduct::OPS_STOCK_ORDERED,

        ];

    }

}