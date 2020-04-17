<?php

/*
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group Ltd
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models\EP\Provider\Unleashed;

use backend\models\EP\Directory;
use backend\models\EP\Exception;
use backend\models\EP\Messages;
use backend\models\EP\Provider\DatasourceInterface;
use backend\models\EP\Tools;
use \common\api\models\AR\Categories;
use \common\api\models\AR\Products;
use \common\classes\language;
use \common\helpers\Acl;

class ExportOrders implements DatasourceInterface
{
  const ORDER_STATUS = 'Parked';
/***
 * {
	"Guid": "0a524af8-62a2-4545-bcc7-f1da3e29f700",
	"OrderStatus": "Completed",
	"Customer": {
		"CustomerCode": "GAR123"
	},
	"CustomerRef": "54321",
	"DeliveryName": "test shipping name",
	"DeliveryStreetAddress": "stree 11",
	"DeliveryStreetAddress2": "street 2",
	"DeliveryCity": "City1",
	"DeliveryCountry": "New Zealand",
	"DeliveryPostCode": "1234",
	"Tax": {
		"TaxCode": "G.S.T."
	},
	"TaxRate": 0.15,
	"Total": 115,
	"SubTotal": 100.00,
	"TaxTotal": 15.00000,
	"ExchangeRate": 1,
	"OrderDate": "2019-07-09T15:15:15.444",
	"Warehouse": {
		"WarehouseCode": "MAIN"
	},
	"SalesOrderLines": [{
		"DiscountRate": 0,
		"LineNumber": 1,
		"Product": {
			"ProductCode": "SHELF"
		},
		"OrderQuantity": 5,
		"UnitPrice": 20,
		"LineTotal": 100,
		"LineTax": 15

	}]
}
 */
    protected $total_count = 0;
    protected $row_count = 0;
    protected $process_orders_r;
    protected $warehouses = [];
    protected $defaultWarehouseCode;
    protected $taxes = [];

    protected $config = [];

    private $allow_update_order = true;
    private $debug = false;
    private $simulate = false;
    private $extraNotes = false;

    /**
     * @var Client
     */
    protected $client;

    function __construct($config)
    {
        $this->config = $config;
        if ( isset($this->config['order']['disable_order_update']) && $this->config['order']['disable_order_update']==1 ) {
            $this->allow_update_order = false;
        }
    }

    public function allowRunInPopup()
    {
        return true;
    }

    public function getProgress()
    {
        if ($this->total_count > 0) {
            $percentDone = min(100, ($this->row_count / $this->total_count) * 100);
        } else {
            $percentDone = 100;
        }
        return number_format($percentDone, 1, '.', '');
    }

    public function prepareProcess(Messages $message)
    {

        $configured_statuses_string = is_array($this->config['order']['export_statuses'])?implode(",", $this->config['order']['export_statuses']):$this->config['order']['export_statuses'];
        if ( strpos($configured_statuses_string,'*')!==false ) {
            $need_status_list = true;
        } else {
            $need_status_list = \common\helpers\Order::extractStatuses($configured_statuses_string);

            if ( count($need_status_list)==0 ) {
                throw new Exception('Order statuses for export not configured');
            }
        }

        $limitOrderIds = false;
        if ( isset($this->config['job_configure']) && !empty($this->config['job_configure']['forceProcessOrders']) ) {
            $limitOrderIds = array_map('intval', $this->config['job_configure']['forceProcessOrders']);
            $limitOrderIds = array_unique($limitOrderIds);
        }

        if ( $this->allow_update_order ) { // allow send order updates 
            $orders_sql = (
                "SELECT distinct o.orders_id, IF(lo.remote_orders_id IS NULL,0,1) AS update_order, " .
                " lo.local_order_last_modified_hash " .
                "FROM " . TABLE_ORDERS . " o " .
                " LEFT JOIN ep_holbi_soap_link_orders lo ON lo.local_orders_id=o.orders_id AND lo.ep_directory_id='" . (int)$this->config['directoryId'] . "' and cfg_export_as='order'" .
                "WHERE " .
                " ( ".
                "  ( 1 " .
                (is_array($need_status_list) ? " AND o.orders_status IN('" . implode("','", $need_status_list) . "') " : " ") .
                "    AND lo.remote_orders_id IS NULL " .
                "  ) " .
                " OR " .
                "  (lo.local_order_last_modified < IFNULL(o.last_modified, o.date_purchased)) " .
                " ) ".
                (is_array($limitOrderIds) && count($limitOrderIds)>0?" AND o.orders_id IN('".implode("','",$limitOrderIds)."') ":'').
                "GROUP BY o.orders_id " .
                "ORDER BY o.orders_id "
            );
        }else{
            $orders_sql = (
                "SELECT distinct o.orders_id, 0 AS update_order " .
                //always null not required " ,lo.local_order_last_modified_hash " .
                "FROM " . TABLE_ORDERS . " o " .
                " LEFT JOIN ep_holbi_soap_link_orders lo ON lo.local_orders_id=o.orders_id AND lo.ep_directory_id='" . (int)$this->config['directoryId']."' and cfg_export_as = 'order' " .
                "WHERE " .
                " ( ".
                "  ( 1 " .
                (is_array($need_status_list) ? " AND o.orders_status IN('" . implode("','", $need_status_list) . "') " : " ") .
                "    AND lo.remote_orders_id IS NULL " .
                "  ) " .
                " ) ".
                (is_array($limitOrderIds) && count($limitOrderIds)>0?" AND o.orders_id IN('".implode("','",$limitOrderIds)."') ":'').
                //"GROUP BY o.orders_id " .
                "ORDER BY o.orders_id "
            );
        }

        if ($this->debug) {
          \Yii::warning("Orders sql" . $orders_sql
              , "UNLEASHED query");
        }

        $this->process_orders_r = tep_db_query($orders_sql);
        $this->total_count = tep_db_num_rows($this->process_orders_r);
        $message->info("Found ".$this->total_count." orders for export ");
           // init client
        if ($this->total_count>0) {
          try {
            $this->client = new Client($this->config['client']['API_ID'],$this->config['client']['API_KEY']);
            $this->fetchWarehouses($message);
            $this->fetchTaxes($message);
          } catch (\Exception $ex) {
            throw new Exception('Configuration error');
          }
        }

    }

    public function processRow(Messages $message)
    {
        $data = tep_db_fetch_array($this->process_orders_r);
        if ( !is_array($data) ) return $data;

        if ($this->debug) {
          \Yii::warning("Order's data" . print_r($data,1)
              , "UNLEASHED PROCESS ORDER");
        }

        if ( $data['update_order'] ) {
                tep_db_query(
                    "UPDATE ep_holbi_soap_link_orders epol ".
                    " INNER JOIN ".TABLE_ORDERS." o ON o.orders_id=epol.local_orders_id ".
                    "SET epol.local_order_last_modified=IFNULL(o.last_modified, o.date_purchased) ".
                    //", epol.local_order_last_modified_hash='".Helper::generateOrderHash($data['orders_id'])."' ".
                    "WHERE epol.ep_directory_id = '" . (int)$this->config['directoryId'] . "' ".
                    " and cfg_export_as='order' AND epol.local_orders_id = '" . $data['orders_id'] . "'"
                );
          return $data;
          //2do?? $this->updateOrder($message, $data['orders_id'], $data['local_order_last_modified_hash']);
        }else {
            $this->exportOrder($message, $data['orders_id']);
        }

        $this->row_count++;

        return $data;
    }

    public function postProcess(Messages $message)
    {

    }

    protected function updateOrder(Messages $message, $orderId, $checkHash='')
    {
        try {
            $order = new \common\classes\Order($orderId);
        }catch (\Exception $ex){
            $message->info(" [!] order #{$orderId} skipped. Couldn't load local order");
            return;
        }
        $currentOrderHash = Helper::generateOrderHash($order);
        if ( $checkHash==$currentOrderHash ) {
            $message->info(" [!] order #{$orderId} same");
            return;
        }

        $orderData['status_history_array'] = [];
        if (is_array($this->config['status_map_local_to_server']) && count($this->config['status_map_local_to_server'])>0) {
            $get_history_r = tep_db_query(
                "SELECT * " .
                "FROM " . TABLE_ORDERS_STATUS_HISTORY . " " .
                "WHERE orders_id = '" . $order->order_id . "' " .
                " AND orders_status_id IN ('" . implode("','", array_keys($this->config['status_map_local_to_server'])) . "') " .
                " AND orders_status_id!=0 ".
                "ORDER BY date_added, orders_status_history_id"
            );
            if (tep_db_num_rows($get_history_r) > 0) {
                while ($_history = tep_db_fetch_array($get_history_r)) {
                    $_history['orders_status_id'] = isset($this->config['status_map_local_to_server'][$_history['orders_status_id']])?$this->config['status_map_local_to_server'][$_history['orders_status_id']]:0;
                    if ( empty($_history['orders_status_id']) ) continue;
                    $_history['date_added'] = (new \DateTime($_history['date_added']))->format(DATE_ISO8601);
                    $orderData['status_history_array'][] = $_history;
                }
            }
        }

        try {
            $response = $this->client->updateOrder(
                $orderData
            );

            if ( $response->status!='ERROR' ) {
                $remote_order_id = $response->orders_id;

                $message->info(" [+] order #{$orderId} updated. Remote order #{$remote_order_id}");

                tep_db_query(
                    "UPDATE ep_holbi_soap_link_orders epol ".
                    " INNER JOIN ".TABLE_ORDERS." o ON o.orders_id=epol.local_orders_id ".
                    "SET epol.local_order_last_modified=IFNULL(o.last_modified, o.date_purchased), ".
                    " epol.local_order_last_modified_hash='".md5(json_encode($order))."' ".
                    "WHERE epol.ep_directory_id = '".(int)$this->config['directoryId']."' ".
                    " and cfg_export_as='order' AND epol.local_orders_id = '".$order->order_id."'"
                );

                tep_db_query(
                    "UPDATE ".TABLE_ORDERS." ".
                    "SET _api_order_time_modified=_api_order_time_modified, _api_order_time_processed=_api_order_time_modified ".
                    "WHERE orders_id='".$orderId."' "
                );

            }elseif( isset($response->messages) && isset($response->messages->message) ){
                $response_messages = $response->messages->message;
                if ( !is_array($response_messages) ) $response_messages = [$response_messages];
                foreach( $response_messages as $response_message ) {
                    $message->info(" [!] order #{$orderId} {$response_message->code}: {$response_message->text}");
                }
            }
        }catch (\Exception $ex){
            $message->info(" [!] order #{$orderId} update error: ".$ex->getCode().':'.$ex->getMessage()."");
        }

    }

    protected function exportOrder(Messages $message, $orderId)
    {
        try {
          $order = new \common\classes\Order($orderId);
        }catch (\Exception $ex){
          $message->info(" [!] order #{$orderId} skipped. Couldn't load local order");
          return;
        }

        try {
          $orderData = new \stdClass();
          $this->extraNotes = false;
          $this->prepareData($message, $order, $orderData);
          if ($this->simulate) {
            $message->info(print_r($orderData,1));
            $message->info(print_r($order,1));
            \Yii::warning('orderData ' . print_r($orderData,1), "UNLEASHED ");
            \Yii::warning('order ' . print_r($order,1), "UNLEASHED ");
            return true;
          }
          $response = $this->client->post('SalesOrders/' . $orderData->Guid, '', $orderData)->send();

          if ($this->debug) {
            \Yii::warning("\n Response Data:" . (method_exists($response, 'getData')?print_r($response->getData(),1):'')
                . "\n Response Headers:" . (method_exists($response, 'getHeaders')?print_r($response->getHeaders(),1):'')
                , "UNLEASHED_RESPONSE POST");
          }
//$message->info(print_r($response,1));

          if ($response->getIsOk()) {

            $data = $response->getData();
            if (!empty($data['Guid'])) {
              $remote_order_id = $orderId;
              $remote_order_number = $data['OrderNumber'];
              $remote_order_guid = $data['Guid'];
            }
            
          } elseif (in_array($response->getStatusCode(), [400, 599])) { // problem with data
            try {
              $data = $response->getData();
              if (is_array($data['Items'])) {
                foreach ($data['Items'] as $item) {
                  $message->info($item['Description']);
                }
                $errDescription = implode("\n", \yii\helpers\ArrayHelper::getColumn($data['Items'], 'Description'));
                \Yii::$app->db->createCommand()->insert('ep_order_issues',
                    [
                      'orders_id' => (int)$orderId,
                      'ep_directory_id' => (int)$this->config['directoryId'],
                      'status' => '2',
                      'date_added' => new \yii\db\Expression('now()'),
                      'issue_text' => $errDescription,
                      ]
                  )->execute();
              } else {
                \Yii::$app->db->createCommand()->insert('ep_order_issues',
                    [
                      'orders_id' => (int)$orderId,
                      'ep_directory_id' => (int)$this->config['directoryId'],
                      'status' => '2',
                      'date_added' => new \yii\db\Expression('now()'),
                      'issue_text' => 'Order Data error ' . (!empty($data['Description'])?$data['Description']:''),
                      ]
                  )->execute();
              }
              \Yii::$app->db->createCommand()->insert(
                'ep_holbi_soap_link_orders',
                [
                    'ep_directory_id' => (int)$this->config['directoryId'],
                    'remote_orders_id' => 0,
                    'local_orders_id' => $order->order_id,
                    'remote_guid' => '',
                    'remote_order_number' => '',
                    'track_remote_order' => 0,
                    'cfg_export_as' => 'order',
                    'date_exported' => new \yii\db\Expression('now()'),
                ]
              )->execute();
            } catch (\Exception $exc) { // 500 errors shouldn't be logged here

            }

          } else {
            try {
              $data = $response->getData();
              if (is_array($data['Items'])) {
                foreach ($data['Items'] as $item) {
                  $message->info($item['Description']);
                }
                $errDescription = implode("\n", \yii\helpers\ArrayHelper::getColumn($data['Items'], 'Description'));
                \Yii::$app->db->createCommand()->insert('ep_order_issues',
                    [
                      'orders_id' => (int)$orderId,
                      'ep_directory_id' => (int)$this->config['directoryId'],
                      'status' => '2',
                      'date_added' => new \yii\db\Expression('now()'),
                      'issue_text' => $errDescription,
                      ]
                  )->execute();
              } else {
                \Yii::$app->db->createCommand()->insert('ep_order_issues',
                    [
                      'orders_id' => (int)$orderId,
                      'ep_directory_id' => (int)$this->config['directoryId'],
                      'status' => '2',
                      'date_added' => new \yii\db\Expression('now()'),
                      'issue_text' => 'Internal error',
                      ]
                  )->execute();
              }

              \Yii::$app->db->createCommand()->insert(
                  'ep_holbi_soap_link_orders',
                  [
                      'ep_directory_id' => (int)$this->config['directoryId'],
                      'remote_orders_id' => 0,
                      'local_orders_id' => $order->order_id,
                      'remote_guid' => '',
                      'remote_order_number' => '',
                      'track_remote_order' => 0,
                      'cfg_export_as' => 'order',
                      'date_exported' => new \yii\db\Expression('now()'),
                  ]
                )->execute();
            } catch (\Exception $exc) {
            }
         }

            if ( !is_array($remote_order_id) && $remote_order_id>0) {
              //save external order ID
                tep_db_perform(
                    'ep_holbi_soap_link_orders',
                    [
                        'ep_directory_id' => (int)$this->config['directoryId'],
                        'remote_orders_id' => $remote_order_id,
                        'local_orders_id' => $order->order_id,
                        'remote_guid' => $remote_order_guid,
                        'remote_order_number' => $remote_order_number,
                        'track_remote_order' => 1,
                        'cfg_export_as' => 'order',
                        'date_exported' => 'now()',
                    ]
                );
                // switch order status if required
                $export_success_status = $this->config['order']['export_success_status'];
                if ( $export_success_status ) {
                    $comments = 'Remote order ID '.$remote_order_number;

                    $oID = $order->order_id;
                    $status = $export_success_status;
                    $customer_notified = 0;

                    tep_db_query(
                        "update " . TABLE_ORDERS . " ".
                        "set orders_status = '" . tep_db_input($status) . "', last_modified = now() ".
                        "where orders_id = '" . (int) $oID . "'"
                    );
                    tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, array(
                        'orders_id' => (int) $oID,
                        'orders_status_id' => (int) $status,
                        'date_added' => 'now()',
                        'customer_notified' => $customer_notified,
                        'comments' => $comments,
                        'admin_id' => 0,//$adminId,
                    ));
                    $commentid = tep_db_insert_id();

                    if ($sms = Acl::checkExtension('SMS', 'sendSMS')){
                        $response = $sms::sendSMS($oID, $commentid);
                        if (is_array($response) && count($response)){
                            $messages[] = ['message' => $response['message'], 'messageType' => $response['messageType']];
                        }
                    }

                    if ($ext = Acl::checkExtension('ReferFriend', 'rf_release_reference')){
                        $ext::rf_release_reference((int)$oID);
                    }

                    if ($ext = Acl::checkExtension('CustomerLoyalty', 'afterOrderUpdate')){
                        $ext::afterOrderUpdate((int)$oID);
                    }
/*
                    if (method_exists('\common\helpers\Coupon', 'credit_order_check_state')){
                        \common\helpers\Coupon::credit_order_check_state((int) $oID);
                    }
 
 */
                }

                tep_db_query(
                    "UPDATE ep_holbi_soap_link_orders epol ".
                    " INNER JOIN ".TABLE_ORDERS." o ON o.orders_id=epol.local_orders_id ".
                    "SET epol.local_order_last_modified=IFNULL(o.last_modified, o.date_purchased), ".
                    " epol.local_order_last_modified_hash='".

                    //Helper::generateOrderHash($order->order_id)
                    md5(json_encode($order))
                    ."' ".
                    "WHERE epol.ep_directory_id = '".(int)$this->config['directoryId']."' ".
                    " and cfg_export_as='order' AND epol.local_orders_id = '".$order->order_id."'"
                );

                $message->info(" [+] order #{$orderId} exported. Remote order #{$remote_order_number}");

                tep_db_query(
                    "UPDATE ".TABLE_ORDERS." ".
                    "SET _api_order_time_modified=_api_order_time_modified, _api_order_time_processed=_api_order_time_modified ".
                    "WHERE orders_id='".$orderId."' "
                );
            } 
        } catch (\Exception $ex){
            $message->info(" [!] order #{$orderId} export error: ".$ex->getCode().':'.$ex->getMessage()."");
            \Yii::warning('UNLEASHED error ' . $ex->getMessage() . ' ' . $ex->getTraceAsString());
        }

    }

    protected function fetchWarehouses(Messages $message)
    {
      $fetchPage=1;
      do {

        $response = $this->client->get('Warehouses/' . $fetchPage)->send();
        $data = $response->getData();

        $Pagination = $data['Pagination'];
        $hasMoreData = (int)$Pagination['PageNumber'] < (int)$Pagination['NumberOfPages'];
        if (is_array($data['Items'])) {
          foreach ($data['Items'] as $item){
              $this->warehouses[$item['WarehouseCode']] = $item;
              if ($item['IsDefault']) {
                if (!empty($this->defaultWarehouseCode)) {
                  $message->info('! Multiple default warehouses ' . $this->defaultWarehouseCode . ' ' . $item['warehouseCode']);
                }
                $this->defaultWarehouseCode = $item['WarehouseCode'];
              }
          }
        }
        $fetchPage++;
      } while ($hasMoreData);
        return true;
    }

    protected function fetchTaxes(Messages $message)
    {
      $response = $this->client->get('Taxes')->send();
      $data = $response->getData();

      if (is_array($data['Items'])) {
        foreach ($data['Items'] as $item){
            $this->taxes[$item['Guid']] = $item;
        }
      }

      return true;
    }

    private function matchTax(Messages $message, $rate, $description='')
    {
      $ret = false;
      if (is_array($this->taxes)) {
        foreach ($this->taxes as $guid => $tax) {
          if (abs($rate-$tax['TaxRate']) < 0.00001 ){
            if (!$ret || $tax['Description']==$description) {
              //$ret = $guid;
              $ret = $tax['TaxCode'];
            }
          }
        }
      } else {
        $message->info('No taxes available (set up) at unleashed');
      }
      if (!$ret) {
        $message->info('Incorrect tax rate ' . $rate);
      }
      return $ret;
    }

    private function matchWarehouse(Messages $message, $name='')
    {
      $ret = false;
      static $cache = [], $defaultName = '';

      if (empty($name) && empty($defaultName)) {
        $name = \common\helpers\Warehouses::get_warehouse_name(\common\helpers\Warehouses::get_default_warehouse());
      } elseif (empty($name)){
        $name = $defaultName;
      }
      if (!isset($cache[$name])) {
        if (is_array($this->warehouses)) {
          foreach ($this->warehouses as $code => $warehouse) {
            if ($warehouse['WarehouseName'] == $name) {
              $ret = $code;
            }
          }
        } else {
          $message->info('No warehouses available (set up) at unleashed or unleashed API is not available (try again later)');
        }
      } else {
        $ret = $cache[$name];
      }
      if (!$ret) {
        $message->info('Ignore warehouse name ' . $name);
        $ret = $this->defaultWarehouseCode;
      }
      return $ret;
    }
    
    private function getCustomerCode($id, $email, $currency) {
      static $cache = [];
      $key = $id . '_' . $email . '_' . $currency;
      if (!isset($cache[$key])) {
        if ($id>0) {
          $customer = \common\models\Customers::findOne($id);
          if (!empty($customer->erp_customer_code) /*&& $customer->customers_currency_id == \common\helpers\Currencies::getCurrencyId($currency)*/){
            $cache[$key] = $customer->erp_customer_code;
          } else {
            $response = $this->client->get('Customer', 'currency=' . $currency . '&contactEmail=' . $email)->send();
            $data = $response->getData();
            if (is_array($data['Items']) && !empty($data['Items'][0]['CustomerCode'])) {
              $cache[$key] = $data['Items'][0]['CustomerCode'];
              $customer->erp_customer_code = $cache[$key];
              try {
                $customer->save(false);
              } catch (\Exception $ex) {
                // silent - not important error here.
              }
            }
          }
        }
      }
      return (!isset($cache[$key]))?false:$cache[$key];
    }
    
    private function matchShipping($info) {
      $ret = '';
      if(!empty($info['shipping_class'])) {
        if (isset($this->config['shipping'][$info['shipping_class']])) {
          $ret = $this->config['shipping'][$info['shipping_class']];
        } elseif (strpos($info['shipping_class'], '_')) {
          $s = explode('_', $info['shipping_class']);
          if (isset($this->config['shipping'][$s[0]])) {
            $ret = $this->config['shipping'][$s[0]];
          }
        }
      }
      return $ret;
    }

/**
 *
 * @param array  $products
 * @param array  $totals
 * @param stdClass  $ret
 * @param string $currency
 * @param string $currency_value
 * @return \stdClass
 */
    private function getProducts($products, $totals, &$ret, $currency='', $currency_value='', $group_id=0, Messages $message) {
      $chkTotal = $subtotal = $tax = 0;
      if (is_array($products)) {
        $childrenPrices = [];
        foreach ($products as $product) {
          if (!empty($product['parent_product'] )) {
            if (!isset($childrenPrices[$product['parent_product']])) {
              $childrenPrices[$product['parent_product']] = 0;
            }
            $childrenPrices[$product['parent_product']] += $product['final_price'];
          }
        }

        $cnt = 1;
        foreach ($products as $product) {


          if (!empty($product['parent_product'] )) {
            continue;
          }

          // skip free products w/o model and add note to comments
          if (empty($product['model']) && abs($product['final_price'])<0.0086) {
            $this->extraNotes .= '"Free" website-only product: ' . $product['name'] . ' x '. $product['qty'] ." pcs\n";
            continue;
          }

          $products_id = (!empty($product['template_uprid'])?$product['template_uprid']:$product['id']);
          $fPrice = $product['final_price'];
          if (!empty($childrenPrices[$products_id])) {
            $fPrice += $childrenPrices[$products_id];
          }
          
          if (!empty($product['standard_price'])) {

            $standardPrice = $product['standard_price'];
          } else {

            
            try {
              $standardPrice = \common\helpers\Product::get_products_price($products_id, 1, $fPrice, \common\helpers\Currencies::getCurrencyId($currency), $group_id);
            } catch (\Exception $e) {
              $standardPrice = 0;
              \Yii::warning($e->getMessage() . ' ' . $e->getTraceAsString());
            }
          }

          if ($fPrice>0 && ($standardPrice - $fPrice) > 0.007) {
          //its possible to pass discounted price only

            $discountRate = round($fPrice/$standardPrice, 4);
            $pPrice = round($fPrice/$discountRate ,6);

            $discountRate = 1 - $discountRate;
            
            // skip price with discount if line total != saved
            //Calculation is as follows: (OrderQuantity:'3' * UnitPrice:'179.00' * (1 - DiscountRate:'0.24580000000000002')) rounded to 2 Decimal Places
            $linePriceCheck = \backend\design\editor\Formatter::priceClear(
                $product['qty'] * \backend\design\editor\Formatter::priceClear($pPrice, 0, 1, $currency, $currency_value)*(1-$discountRate)
                , 0, 1, $currency, $currency_value);
            if ($linePriceCheck != \backend\design\editor\Formatter::priceClear($fPrice, 0, $product['qty'], $currency, $currency_value)) {
              $discountRate = 0;
              $pPrice = $fPrice;
            }
            if ($this->debug) {
              \Yii::warning("\$linePriceCheck $linePriceCheck \$discountRate $discountRate");
            }


          } else {
            
            $discountRate = 0;
            $pPrice = $fPrice;
          }

          $p = new \stdClass();
          $p->LineNumber = $cnt++;
          $p->DiscountRate = $discountRate;///
          $p->Product = new \stdClass();
          $p->Product->ProductCode = $product['model'];
          $p->OrderQuantity = $product['qty'];
          $p->UnitPrice = \backend\design\editor\Formatter::priceClear($pPrice, 0, 1, $currency, $currency_value);
          $p->LineTotal = \backend\design\editor\Formatter::priceClear($fPrice, 0, $product['qty'], $currency, $currency_value); //subtotal
          //$p->LineTax = \backend\design\editor\Formatter::priceClear($fPrice/100*$product['tax'], 0, $product['qty'], $currency, $currency_value);
          $p->LineTax = \backend\design\editor\Formatter::priceClear($p->LineTotal/100*$product['tax'], 0, 1, $currency, 1);

          $subtotal += $p->LineTotal;
          $tax += $p->LineTax;

          $ret->SalesOrderLines[] = $p;
        }
      }
      if (is_array($totals)) {
        foreach ($totals as $total) {
          if (abs($total['value_exc_vat'])<0.0001) {
            continue;
          }
          switch ($total['class']) {

            case 'ot_shipping':


              $p = new \stdClass();
              $p->Product = new \stdClass();
              $p->Product->ProductDescription = rtrim(strip_tags($total['title']), ':');
              $p->LineTotal =
                $p->UnitPrice = \backend\design\editor\Formatter::priceClear($total['value_exc_vat'], 0, 1, $currency, $currency_value);
              $p->LineTax = \backend\design\editor\Formatter::priceClear($p->LineTotal * $ret->TaxRate, 0, 1, $currency, 1); //$currency_value

              if ($this->config['order']['export_surcharge'] == 'charge') {
                $p->LineType = "Charge";
              } elseif (!empty($this->config['order']['shipping_product'])) {
                $p->Product->ProductCode = $this->config['order']['shipping_product'];
              } else {
                continue 2;
              }

              break;
            
            case 'ot_total':
            case 'ot_tax':
              $chkTotal += $total['value_exc_vat'];
              continue 2;
              break;
            case 'ot_paymentfee':
            case 'ot_loworderfee':
            case 'ot_shippingfee':
            case 'ot_custom':
            case 'ot_fixed_payment_chg':
            case 'ot_gift_wrap':
            case 'ot_coupon':

              if (in_array($total['class'], ['ot_coupon'])) {
                $total['value_exc_vat'] *= -1;
              }

              $p = new \stdClass();
              $p->Product = new \stdClass();
              $p->Product->ProductDescription = rtrim(strip_tags(html_entity_decode($total['title'])), ':');
              $p->LineTotal =
                $p->UnitPrice = \backend\design\editor\Formatter::priceClear($total['value_exc_vat'], 0, 1, $currency, $currency_value);
              $p->LineTax = \backend\design\editor\Formatter::priceClear($p->LineTotal * $ret->TaxRate, 0, 1, $currency, 1); //$currency_value - already multiplied in LineTotal

              if ($this->config['order']['export_surcharge'] == 'charge') {
                $p->LineType = "Charge";
                //$p->Product->Guid = '00000000-0000-0000-0000-000000000000';
              } elseif (!empty($this->config['order']['fee_product'])) {
                $p->Product->ProductCode = $this->config['order']['fee_product'];
              } else {
                continue 2;
              }

              break;

            default:
              continue 2;
          }

          $p->LineNumber = $cnt++;
          $p->DiscountRate = 0;
          $p->OrderQuantity = 1;

          $subtotal += $p->LineTotal;
          $tax += $p->LineTax;

          $ret->SalesOrderLines[] = $p;
        }
      }
      if (round(abs($chkTotal -$subtotal-$tax),2) >=0.01) {
        $message->info(" order #{$ret->CustomerRef} Descripancy:  $chkTotal - $subtotal - $tax = " . ($chkTotal -$subtotal-$tax));
/* somethiing was not processed :( */
        $p = new \stdClass();
        $p->Product = new \stdClass();
        $p->Product->ProductDescription = 'Descripancy';
        if ($this->config['order']['export_surcharge'] == 'charge') {
          $p->LineType = "Charge";
          //$p->Product->Guid = '00000000-0000-0000-0000-000000000000';
        } elseif (!empty($this->config['order']['fee_product'])) {
          $p->Product->ProductCode = $this->config['order']['fee_product'];
        }
        if (true) {
          //suppose inc tax
          $dPrice = ($chkTotal -$subtotal-$tax)/(1+$ret->TaxRate);
        } else {
          // ex tax
          $dPrice = ($chkTotal -$subtotal-$tax);
        }

        $p->UnitPrice = \backend\design\editor\Formatter::priceClear($dPrice, 0, 1, $currency, $currency_value);
        $p->LineTotal = $p->UnitPrice;
        $p->LineTax = \backend\design\editor\Formatter::priceClear($p->LineTotal*$ret->TaxRate, 0, 1, $currency, $currency_value);

        $p->LineNumber = $cnt++;
        $p->DiscountRate = 0;
        $p->OrderQuantity = 1;

        $subtotal += $p->LineTotal;
        $tax += $p->LineTax;

        $ret->SalesOrderLines[] = $p;
      }

      $ret->SubTotal = \backend\design\editor\Formatter::priceClear($subtotal, 0, 1, $currency, 1);
      $ret->TaxTotal = \backend\design\editor\Formatter::priceClear($tax, 0, 1, $currency, 1);
      $ret->Total = \backend\design\editor\Formatter::priceClear($subtotal + $tax, 0, 1, $currency, 1);

    }

    private function utcTime($date) {
      return gmdate('Y-m-d\TH:i:s', strtotime($date));
    }
    
    private function generateGuid() {
      $ret = false;
      $tmp = explode('-', $this->config['client']['API_ID']);
      $lastIndex = count($tmp) - 1;
      if ($lastIndex>0) {
        $len = strlen($tmp[$lastIndex]);
        $n = (string)time();
        if (strlen($n)<$len) {
          for ($i=0, $k=($len-strlen($n)); $i<$k; $i++) {
            $n .= substr('0123456789abcdef', rand(0, 15), 1);
          }
        } elseif (strlen($n)>$len) {
          $n = substr($n, ($len - strlen($n)));
        }
        $tmp[$lastIndex] = $n;
        $ret = implode('-', $tmp);
      }
      return $ret;
      //    return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));


    }

    private function setAddressDetails($prefix, &$ret, $address){
      $match = [
        'Name' => $address['name'],
        'StreetAddress' => $address['street_address'],
        'Suburb' => $address['suburb'],
        'City' => $address['city'],
        'PostCode' => $address['postcode'],
        'Region' => $address['state'],
        'Country' => $address['country']['title'],
      ];
      foreach ($match as $k => $v) {
        if (!empty($k) && !empty($v)) {
          $ret->{$prefix.$k} = $v;
        }
      }
    }

    private function getOrderComment($orderId) {
      $ret = '';
      if ($this->extraNotes) {
        $ret .= 'EXPORT NOTES:' . "\n\n" . trim($this->extraNotes) . "\n====\n" ;
      }
      
      $q = \common\models\OrdersStatusHistory::find()->andWhere(['orders_id' => $orderId])->orderBy('date_added')->asArray()->one();
      if (!empty(trim($q['comments']))){
        $ret .= trim($q['comments']);
      }
      return $ret;
    }


    private function prepareData(Messages $message, $order, &$ret) {
      //$ret = new \stdClass();
      $ret->Guid = $this->generateGuid();
      if (!$ret->Guid) {
        $message->info('Unsupported Client API Id format');
        //important '\' php exception - interrupt import, EP exception - skip order.
        throw new Exception('Configuration error');
      }
      $ret->OrderStatus = self::ORDER_STATUS;
      $ret->Customer = new \stdClass();
      $ret->Customer->CustomerCode = $this->getCustomerCode($order->customer['id'], $order->customer['email_address'], $order->info['currency']);
      if (!$ret->Customer->CustomerCode) {
        $message->info('Create customer at Unleashed first ' . $order->customer['email_address'] . ' ' . $order->info['currency']);
        throw new Exception('Configuration error');
      }
      
      //add comments after all other stuff to cover internal notes

      $ret->CustomerRef = $order->getOrderId() . (!empty($order->info['purchase_order'])?' / ' . $order->info['purchase_order']:'');
      $this->setAddressDetails('Delivery', $ret, $order->delivery);
      $ret->Tax = new \stdClass();

      if (is_array($order->info['tax_groups'])) {
        $taxDescription = key($order->info['tax_groups']);
        $rate = abs(round(floatval(\common\helpers\Tax::get_tax_rate_from_desc($taxDescription))/100, 6));
        if ($rate==0 && !empty($order->info['tax'])) {
          //tax rate was not found by description
          //try by total
          if (isset($order->totals) && is_array($order->totals)) {
            $tmp = array_values(array_filter($order->totals, function ($e) { $ret = false; if (!empty($e['class']) && $e['class']=='ot_tax') { $ret = true; } return $ret;  } ));
            $rate = abs(round(floatval(\common\helpers\Tax::get_tax_rate_from_desc(trim($tmp[0]['title'], ': ')))/100, 6));
            // :( calculate from order
            if ($rate==0){
              if ($order->info['total']>0 && $order->info['total'] > $order->info['tax']) {
                $rate = abs(round( ($order->info['total'] - $order->info['tax'])/$order->info['total']/100, 6));
              }
            }
            // :( calculate from totals
            if ($rate==0){
              $ttl = array_values(array_filter($order->totals, function ($e) { $ret = false; if (!empty($e['class']) && $e['class']=='ot_total') { $ret = true; } return $ret;  } ));
              if ($ttl[0]['value'] >0 && $ttl[0]['value'] > $tmp[0]['value']) {
                $rate = abs(round( ($ttl[0]['value'] - $tmp[0]['value'])/$ttl[0]['value']/100, 6));
              }
            }

          }
          
        }
      } else {
        $rate = 0;
        $taxDescription = '';
      }
      $ret->Tax->TaxCode = $this->matchTax($message, $rate, $taxDescription);
      if (!$ret->Tax->TaxCode /*|| count($order->tax_groups)>1*/) {
        throw new Exception('Unsupported tax rate');
      }
      $ret->TaxRate = $rate;
      $ret->ExchangeRate = $order->info['currency_value'];
/*
      if (is_array($order->totals)) {
        foreach ($order->totals as $value) {
          if ($value['class'] == 'ot_subtotal') {
            $_total = $value['value_inc_tax'];
            $_subtotal = $value['value_exc_vat'];
            $ret->Total = \backend\design\editor\Formatter::priceClear($_total, 0, 1, $order->info['currency'], $order->info['currency_value']);
            $ret->SubTotal = \backend\design\editor\Formatter::priceClear($_subtotal, 0, 1, $order->info['currency'], $order->info['currency_value']);
            $ret->TaxTotal = \backend\design\editor\Formatter::priceClear($_total-$_subtotal, 0, 1, $order->info['currency'], $order->info['currency_value']);
            
            break;
          }
        }
      }
/* only products part (subtotals)
      $ret->Total = \backend\design\editor\Formatter::priceClear($order->info['total_inc_tax'], 0, 1, $order->info['currency'], $order->info['currency_value']);
      $ret->SubTotal = \backend\design\editor\Formatter::priceClear($order->info['total_exc_tax'], 0, 1, $order->info['currency'], $order->info['currency_value']);
      $ret->TaxTotal = \backend\design\editor\Formatter::priceClear(($order->info['total_inc_tax'] - $order->info['total_exc_tax']), 0, 1, $order->info['currency'], $order->info['currency_value']);
*/
      $ret->Warehouse = new \stdClass();
      $ret->Warehouse->WarehouseCode = $this->matchWarehouse($message, ''); // 2do? config??
      $ret->OrderDate = $this->utcTime($order->info['date_purchased']);
      if (!is_null($order->info['delivery_date'])) {
        $ret->RequiredDate = $this->utcTime($order->info['delivery_date']);
      }
      $ret->LastModifiedOn = $this->utcTime($order->info['last_modified']);
      $ret->SalesOrderLines = [];
      $customer_group = false;
      if(!empty($order->customer['customer_id'])) {
        $q = \common\models\Customers::findOne($order->customer['customer_id']);
        if ($q) {
          $customer_group = $q->groups_id;
        }
      }

      $this->getProducts($order->products, $order->totals, $ret, $order->info['currency'], $order->info['currency_value'], $customer_group, $message);

      $ret->DeliveryMethod = $this->matchShipping($order->info);

      $adminEmail = self::salesPersonEmail($order);
      if (!empty($adminEmail)) {
        $ret->SalesPerson = new \stdClass();
        $ret->SalesPerson->Email = $adminEmail;
      }

      $ret->Comments = $this->getOrderComment($order->getOrderId());

    }

    private static function salesPersonEmail($order) {

      $adminEmail = false;

      if(!empty($order->info['admin_id'])) {

        $q = \common\models\Admin::findOne($order->info['admin_id']);
        if (!empty($q->admin_email_address)) {
          $adminEmail = $q->admin_email_address;
        }

      } elseif(!empty($order->customer['customer_id'])) {


        $q = (new \yii\db\Query())->from(['c' => \common\models\Customers::tableName(), 'a' => \common\models\Admin::tableName()])
            ->andWhere('c.admin_id=a.admin_id')
            ->andWhere([
              'c.customers_id' => (int)$order->customer['customer_id']
            ])
            ->select('a.admin_email_address')
            ->one();

        if (!empty($q['admin_email_address'])) {
          $adminEmail = $q['admin_email_address'];
        }
      }

      return $adminEmail;
    }
}
