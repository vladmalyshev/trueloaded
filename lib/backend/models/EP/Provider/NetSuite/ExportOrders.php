<?php

/*
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group Ltd
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models\EP\Provider\NetSuite;

use backend\models\EP\Directory;
use backend\models\EP\Exception;
use backend\models\EP\Messages;
use backend\models\EP\Provider\DatasourceInterface;
use backend\models\EP\Tools;
use common\api\models\AR\Categories;
use common\api\models\AR\Products;
use common\classes\language;
use common\helpers\Acl;
use backend\models\EP\Datasource\NetSuiteLink;

class ExportOrders implements DatasourceInterface
{

    protected $total_count = 0;
    protected $row_count = 0;
    protected $process_orders_r;

    protected $config = [];

    private $allow_update_order = true;

    /**
     * @var \SoapClient
     */
    protected $client;

    function __construct($config)
    {
      if (empty($config['client']['email']) && isset($config['client']['username'])) {
        $config['client']['email'] = $config['client']['username'];
        unset($config['client']['username']);
      }
      if (empty($config['client']['app_id']) && isset($config['client']['appid'])) {
        $config['client']['app_id'] = $config['client']['appid'];
        unset($config['client']['appid']);
      }

        $this->config = $config;
        if ( isset($this->config['order']['disable_order_update']) && $this->config['order']['disable_order_update']==1 ) {
            $this->allow_update_order = false;
        }
        if (empty(Helper::$config)) {
          Helper::$config = $this->config;
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
   // init client
        try {

          /*$ds = new NetSuiteLink();
          $this->client = &$ds->getClient($this->config['client']);
          */

          $this->client = new \NetSuite\NetSuiteService(array_merge(NetSuiteLink::$config, $this->config['client']));

        } catch (\Exception $ex) {
          throw new Exception('Configuration error');
        }
    
        $configured_statuses_string = is_array($this->config['order']['export_statuses'])?implode(",", $this->config['order']['export_statuses']):$this->config['order']['export_statuses'];
        if ( strpos($configured_statuses_string,'*')!==false ) {
            $need_status_list = true;
        }else{
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

        if ( $this->allow_update_order ) { // allow send order updates to NS
            $orders_sql = (
                "SELECT o.orders_id, IF(lo.remote_orders_id IS NULL,0,1) AS update_order, " .
                " lo.local_order_last_modified_hash " .
                "FROM " . TABLE_ORDERS . " o " .
                " LEFT JOIN ep_holbi_soap_link_orders lo ON lo.local_orders_id=o.orders_id AND lo.ep_directory_id='" . (int)$this->config['directoryId'] . "' " .
                "WHERE " .
                " ( ".
                "  ( 1 " .
                (is_array($need_status_list) ? " AND o.orders_status IN('" . implode("','", $need_status_list) . "') " : " ") .
                "    AND lo.remote_orders_id IS NULL " .
                "  ) " .
                " OR " .
                "  (lo.local_order_last_modified != IFNULL(o.last_modified, o.date_purchased)) " .
                " ) ".
                (is_array($limitOrderIds) && count($limitOrderIds)>0?" AND o.orders_id IN('".implode("','",$limitOrderIds)."') ":'').
                "GROUP BY o.orders_id " .
                "ORDER BY o.orders_id "
            );
        }else{
            $orders_sql = (
                "SELECT o.orders_id, 0 AS update_order, " .
                " lo.local_order_last_modified_hash " .
                "FROM " . TABLE_ORDERS . " o " .
                " LEFT JOIN ep_holbi_soap_link_orders lo ON lo.local_orders_id=o.orders_id AND lo.ep_directory_id='" . (int)$this->config['directoryId'] . "' " .
                "WHERE " .
                " ( ".
                "  ( 1 " .
                (is_array($need_status_list) ? " AND o.orders_status IN('" . implode("','", $need_status_list) . "') " : " ") .
                "    AND lo.remote_orders_id IS NULL " .
                "  ) " .
                " ) ".
                (is_array($limitOrderIds) && count($limitOrderIds)>0?" AND o.orders_id IN('".implode("','",$limitOrderIds)."') ":'').
                "GROUP BY o.orders_id " .
                "ORDER BY o.orders_id "
            );
        }
        $this->process_orders_r = tep_db_query($orders_sql);
        $this->total_count = tep_db_num_rows($this->process_orders_r);
        $message->info("Found ".$this->total_count." orders for export");
    }

    public function processRow(Messages $message)
    {
        $data = tep_db_fetch_array($this->process_orders_r);
        if ( !is_array($data) ) return $data;

        if ( $data['update_order'] ) {
            $this->updateOrder($message, $data['orders_id'], $data['local_order_last_modified_hash']);
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

        $order->info['platform_name'] = \common\classes\platform::name($order->info['platform_id']);

        try {
            $remoteCustomerId = $this->getRemoteCustomerId($order->customer['customer_id']);
        }catch (\Exception $ex){
            $message->info(" [!] order #{$orderId} skipped. ".$ex->getMessage());
            return;
        }
        $orderData = [
            'client_order_id' => $order->order_id,
            'customer' =>[
                'customer_id' => $remoteCustomerId/*$order->customer['customer_id']*/,
                'company_vat' => $order->customer['company_vat'],
                'company_vat_status' => $order->customer['company_vat_status'],
                'telephone' => $order->customer['telephone'],
                'landline' =>  $order->customer['landline'],
                'email_address' => $order->customer['email_address'],
                'name'=> $order->customer['name'],
                'firstname'=> $order->customer['firstname'],
                'lastname'=> $order->customer['lastname'],
                'company'=> $order->customer['company'],
                'street_address' => $order->customer['street_address'],
                'suburb'=> $order->customer['suburb'],
                'city'=> $order->customer['city'],
                'postcode'=> $order->customer['postcode'],
                'state'=>$order->customer['state'],
                'country_iso2' => $order->customer['country']['iso_code_2'],
            ],
            'billing' => [
                'gender'=> $order->billing['gender'],
                'name'=> $order->billing['name'],
                'firstname'=> $order->billing['firstname'],
                'lastname'=> $order->billing['lastname'],
                'company'=> $order->billing['company'],
                'street_address' => $order->billing['street_address'],
                'suburb'=> $order->billing['suburb'],
                'city'=> $order->billing['city'],
                'postcode'=> $order->billing['postcode'],
                'state'=>$order->billing['state'],
                'country_iso2' => $order->billing['country']['iso_code_2'],
                'address_book_id' => $order->billing['address_book_id'],
            ],
            'delivery' => [
                'gender'=> $order->delivery['gender'],
                'name'=> $order->delivery['name'],
                'firstname'=> $order->delivery['firstname'],
                'lastname'=> $order->delivery['lastname'],
                'company'=> $order->delivery['company'],
                'street_address' => $order->delivery['street_address'],
                'suburb'=> $order->delivery['suburb'],
                'city'=> $order->delivery['city'],
                'postcode'=> $order->delivery['postcode'],
                'state'=>$order->delivery['state'],
                'country_iso2' => $order->delivery['country']['iso_code_2'],
                'address_book_id' => $order->delivery['address_book_id'],
            ],
            'products' => [
                'product' => [],
            ],
            'totals' => [
                'total' => [],
            ],
            'info' => $order->info,
        ];
        $orderData['info']['language'] = \common\classes\language::get_code($order->info['language_id']);
        unset($orderData['info']['order_status']);
        if ( isset($orderData['info']['sap_export']) && !is_numeric($orderData['info']['sap_export']) ) {
             unset($orderData['info']['sap_export']);
        }

        if ( isset($order->products) && is_array($order->products) ) {
            foreach ($order->products as $product) {
                $product['id'] = $this->getRemoteProductId($product['id']);

                $attributes = isset($product['attributes'])?$product['attributes']:false;
                unset($product['attributes']);
                if ( is_array($attributes) ) {
                    $product['attributes'] = [
                        'attribute' => [],
                    ];
                    foreach ($attributes as $attribute) {
                        $product['attributes']['attribute'][] = [
                            'option_id' => ($product['id'] ? $attribute['option_id'] : -2),
                            'value_id' => ($product['id'] ? $attribute['value_id'] : -2),
                            'option_name' => $attribute['option'],
                            'option_value_name' => $attribute['value'],
                        ];
                    }
                }
                $orderData['products']['product'][] = $product;
            }
        }

        if ( isset($order->totals) && is_array($order->totals) ) {
            foreach ($order->totals as $total) {
                $orderData['totals']['total'][] = $total;
            }
        }

        if ($orderData['info']['date_purchased'] && $orderData['info']['date_purchased']>1000) {
            $orderData['info']['date_purchased'] = (new \DateTime($orderData['info']['date_purchased']))->format(DATE_ISO8601);
        }
        if ($orderData['info']['last_modified'] && $orderData['info']['last_modified']>1000) {
            $orderData['info']['last_modified'] = (new \DateTime($orderData['info']['last_modified']))->format(DATE_ISO8601);
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
                    " epol.local_order_last_modified_hash='".Helper::generateOrderHash($order->order_id)."' ".
                    "WHERE epol.ep_directory_id = '".(int)$this->config['directoryId']."' ".
                    " AND epol.local_orders_id = '".$order->order_id."'"
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
        $order->info['platform_name'] = \common\classes\platform::name($order->info['platform_id']);
//echo "#### <PRE>" .print_r($order , 1) ."</PRE>";        die;
//locationId
/*

    [info] => Array
        (
            [currency] => GBP
            [currency_value] => 1.000000
            [platform_id] => 1
            [language_id] => 1
            [admin_id] => 0
            [orders_id] => 28271
            [payment_method] => Cash on Delivery
            [cc_type] =>
            [cc_owner] =>
            [cc_number] =>
            [cc_expires] =>
            [date_purchased] => 2018-05-17 20:51:51
            [tracking_number] => Array
                (
                )

            [orders_status_name] => Exported
            [order_status] => 100026
            [last_modified] => 2018-06-06 20:20:09
            [total] => 998.06
            [payment_class] => cod
            [shipping_class] => flat_flat
            [shipping_method] => Flat Rate (Best Way)
            [shipping_cost] => 0
            [subtotal] => 998.06
            [subtotal_inc_tax] => 998.06
            [subtotal_exc_tax] => 881.55
            [tax] => 116.51
            [tax_groups] => Array
                (
                    [VAT 20%] => 176.3097022
                )

            [comments] =>
            [external_orders_id] =>
            [basket_id] => 19057
            [pointto] => 0
            [shipping_weight] => 100.000000
            [total_paid_inc_tax] => 0.000000
            [total_paid_exc_tax] => 0.000000
            [delivery_date] => 0000-00-00
            [products_price_qty_round] => 0
            [cash_data_summ] => 0.00
            [cash_data_change] => 0.00
            [total_inc_tax] => 1100.06
            [total_exc_tax] => 966.55
            [platform_name] => TL 3.3 - Front end one
        )

    [totals] => Array
        (
            [0] => Array
                (
                    [title] => Sub-Total:
                    [value] => 998.060000
                    [class] => ot_subtotal
                    [code] => ot_subtotal
                    [text] => £998.06
                    [text_exc_tax] => £881.55
                    [text_inc_tax] => £998.06
                    [tax_class_id] => 0
                    [value_exc_vat] => 881.548511
                    [value_inc_tax] => 998.058214
                )

  */

        $locations = array_flip(Helper::getWarehousesMap());
        $tmp = \common\helpers\Warehouses::get_default_warehouse();
        if (!is_array($locations ) || empty($locations[$tmp])) {
          $message->info(" [!] order #{$orderId} skipped. Location (warehouse) not found. ");
          return;
        }
        $defaultLocationId = $locations[$tmp];

        try { /* vl2check - maybe by email???*/
            $remoteCustomerId = $this->getRemoteCustomerId($order->customer['customer_id']);
            if (is_array($remoteCustomerId) || $remoteCustomerId==0) {
              $message->info(" [!] order #{$orderId} skipped. Customer not found. ". (isset($remoteCustomerId['error'])?$remoteCustomerId['error']:print_r($remoteCustomerId,1)));
              return;
            } else {
              $orderData['customerId'] = $remoteCustomerId;
            }
        }catch (\Exception $ex){
          echo "#### <PRE>" .print_r($ex, 1) ."</PRE>";
          die;

            $message->info(" [!] order #{$orderId} skipped. Error in customer sync. ".$ex->getMessage());
            return;
        }

        try {// find/add addresses
          $billingAddressId = $this->getRemoteAddressId($order->billing, $remoteCustomerId);
            if (is_array($billingAddressId) || $billingAddressId==0) {
              $message->info(" [!] order #{$orderId} skipped. Address not found. " . (!empty($billingAddressId['error'])?$billingAddressId['error']:''));
              return;
            } else {
              $orderData['billingAddressId'] = $billingAddressId;
            }
            $shippingAddressId = $this->getRemoteAddressId($order->delivery, $remoteCustomerId);
            if (is_array($shippingAddressId) || $shippingAddressId==0) {
              $message->info(" [!] order #{$orderId} skipped. Address not found. " . (!empty($shippingAddressId['error'])?$shippingAddressId['error']:''));
              return;
            } else {
              $orderData['shippingAddressId'] = $shippingAddressId;
            }
        } catch (\Exception $ex){
          $message->info(" [!] order #{$orderId} skipped. Error in customer address sync. ".$ex->getMessage());
            return;
        }

        $order->info['language'] = \common\classes\language::get_code($order->info['language_id']);

        $orderData['currencyId'] = Helper::getNsCurrency($order->info['currency']);
        $orderData['locationId'] = $defaultLocationId;
        $orderData['taxItemId'] = $this->getTaxItemId($order->info['tax_groups'], false);
        $orderData['id'] = $order->getOrderId();

        if ($order->info['shipping_cost']>0 || is_array($order->totals)) {
          if (is_array($order->totals)) {
            foreach ($order->totals as $total) {
              if ($total['class'] == 'ot_shipping') {
                $orderData['shippingCost'] = $total['value_exc_vat'];
              }
            }
          }
          if (empty($orderData['shippingCost'])) { //2check tax twice??
            $orderData['shippingCost'] = $order->info['shipping_cost'];
          }
        }
        
        $orderData['billingAddress'] = Helper::nsFromTlAddress($order->billing);
        $orderData['shippingAddress'] = Helper::nsFromTlAddress($order->delivery);

/*echo "#### <PRE>" .print_r($order->products, 1) ."</PRE>";          die;
 *         (
            [packs] => 0
            [units] => 0
            [packagings] => 0
            [qty] => 1
            [id] => 1047{3}279{4}281
            [name] => marix 1 DISPLAY NAME/CODE
            [model] => marix1 name/number-B-15BB
            [tax] => 20
            [ga] => 0
            [is_virtual] => 0
            [gv_state] => none
            [gift_wrap_price] => 0.00
            [gift_wrapped] =>
            [price] => 10.588173
            [final_price] => 10.588173
            [sets_array] =>
            [template_uprid] => 1047{3}279{4}281
            [parent_product] =>
            [sub_products] =>
            [status] => 0
            [orders_products_id] => 98037
            [promo_id] => 0
            [tax_selected] => 3_4
            [tax_class_id] => 3
            [tax_description] => VAT 20%
            [attributes] => Array
                (
                    [0] => Array
                        (
                            [option] => Cable Length
                            [value] => 15 ft
                            [prefix] =>
                            [price] => 0.000000
                            [option_id] => 3
                            [value_id] => 279
                        )

                )


  [sets_array] => Array
                (
                    [0] => Array
                        (
                            [id] => 815
                            [qty] => 1
                            [model] => KI7895
                            [name] =>
                            [price] => 49.99
                            [weight] => 0.00
                        )
  )
 )
 */

        if ( isset($order->products) && is_array($order->products) ) {
          $missedProducts = [];
          foreach ($order->products as $product) {
            /* set product in both systems now
            if (!empty($product['sets_array'])) {
              $product['id'] = $this->getRemoteProductId($product['id']);
            }
             */
            if (!empty($product['attributes']) && empty($product['sets_array'])) {
            // sets details are also saved as attributes.
            // find by uprid
              $tmp = $this->getRemoteProductId($product['id'], 1);
              if ($tmp) {
                $product['id'] = $tmp;
              } else {
                $missedProducts[] = $product['model']  . ' "' . $product['name'] . '"';
              }
            } else {
              //simple product or bundle
              $tmp = $this->getRemoteProductId($product['id'], 0);
              if ($tmp) {
                $product['id'] = $tmp;
              } else {
                $missedProducts[] = $product['model']  . ' "' . $product['name'] . '"';
              }
            }
            //warehouse_id not filled yet. TL issue
            if (is_array($locations ) && isset($product['warehouse_id']) && !empty($locations[$product['warehouse_id']])) {
              $product['locationId'] = $locations[$product['warehouse_id']];
            }


            $product['taxItemId'] = $this->getTaxItemId(false,
                 [
                  'tax' => $product['tax'],
                  'tax_selected' => $product['tax_selected'],
                  'tax_class_id' => $product['tax_class_id'],
                  'tax_description' => $product['tax_description']
                ]
                );
            if ($product['tax']>0 && !empty($orderData['taxItemId']) && empty($product['taxItemId'])) {
              $product['taxItemId'] = $orderData['taxItemId'];
            }
            $orderData['products'][] = $product;
          }
          if (count($missedProducts)>0) {
            $message->info(" [!] order #{$orderId} skipped. Couldn't find product(s): <br>\n" . implode("<br>\n", $missedProducts));
            return;
          }
        }

        /* add shipping as total
         ?? discounts??
         if ( isset($order->totals) && is_array($order->totals) ) {
            foreach ($order->totals as $total) {
                $orderData['totals']['total'][] = $total;
            }
        }*/

        if ($order->info['date_purchased'] && $order->info['date_purchased']>1000) {
            $orderData['date_purchased'] = (new \DateTime($order->info['date_purchased']))->format(DATE_ISO8601);
        }
        if ($order->info['last_modified'] && $order->info['last_modified']>1000) {
            $orderData['last_modified'] = (new \DateTime($order->info['last_modified']))->format(DATE_ISO8601);
        }


        try {
            $remote_order_id = Helper::createOrder($this->client, $orderData);
/*
            $retryWithCustomerLink = false;
            if ( $response->messages && $response->messages->message ) {
                $response_messages = $response->messages->message;
                if ( !is_array($response_messages) ) $response_messages = [$response_messages];
                foreach( $response_messages as $response_message ) {
                    if ($response_message->code=='ERROR_CUSTOMER_NOT_FOUND' /*|| $response_message->code=='ERROR_CUSTOMER_EMAIL_MISMATCH'* /){
                        $retryWithCustomerLink = true;
                    }
                    $message->info(" [!] order #{$orderId} {$response_message->code}: {$response_message->text}");
                }
            }
            if ( $retryWithCustomerLink ) {
                $message->info(" [!] order #{$orderId} try to recreate customer \"".$order->customer['email_address']."\"");
                $newRemoteCustomerId = $this->getRemoteCustomerId($order->customer['customer_id'], false, [
                    'customers_email_address' => $order->customer['email_address'],
                ]);
                if ( $newRemoteCustomerId ) {
                    $this->exportOrder($message, $orderId);
                    return;
                }
            }
*/
            if ( !is_array($remote_order_id) && $remote_order_id>0) {
                tep_db_perform(
                    'ep_holbi_soap_link_orders',
                    [
                        'ep_directory_id' => (int)$this->config['directoryId'],
                        'remote_orders_id' => $remote_order_id,
                        'local_orders_id' => $order->order_id,
                        'track_remote_order' => 1,
                    ]
                );
                $export_success_status = $this->config['order']['export_success_status'];
                if ( $export_success_status ) {
                    $comments = 'Remote order ID '.$remote_order_id;

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

                    if (method_exists('\common\helpers\Coupon', 'credit_order_check_state')){
                        \common\helpers\Coupon::credit_order_check_state((int) $oID);
                    }
                }

                tep_db_query(
                    "UPDATE ep_holbi_soap_link_orders epol ".
                    " INNER JOIN ".TABLE_ORDERS." o ON o.orders_id=epol.local_orders_id ".
                    "SET epol.local_order_last_modified=IFNULL(o.last_modified, o.date_purchased), ".
                    " epol.local_order_last_modified_hash='".Helper::generateOrderHash($order->order_id)."' ".
                    "WHERE epol.ep_directory_id = '".(int)$this->config['directoryId']."' ".
                    " AND epol.local_orders_id = '".$order->order_id."'"
                );

                $message->info(" [+] order #{$orderId} exported. Remote order #{$remote_order_id}");

                tep_db_query(
                    "UPDATE ".TABLE_ORDERS." ".
                    "SET _api_order_time_modified=_api_order_time_modified, _api_order_time_processed=_api_order_time_modified ".
                    "WHERE orders_id='".$orderId."' "
                );
            } else {
              $message->info(" [!] order #{$orderId} export error. " . (!empty($remote_order_id['error'])?$remote_order_id['error']:'') . "");
            }
        }catch (\Exception $ex){
            $message->info(" [!] order #{$orderId} export error: ".$ex->getCode().':'.$ex->getMessage()."");
        }

    }

/**
 * get NS address ID or add new address record 
 * @param array $tlAddress
 * @param int $remoteCustomerId
 * @return int/[error =>'']
 */
    protected function getRemoteAddressId($tlAddress, $remoteCustomerId, $allowInsert = true)
    {
      static $cached = [];
      $ret = 0;
      if (is_array($tlAddress)) {
        $key = $remoteCustomerId . '_' . md5(implode(' ', $tlAddress));
        if (isset($cached[$key]) && $cached[$key]>0) {
          $ret = $cached[$key];
        } else {
          try {
            $response = Helper::get($this->client, 'customer', $remoteCustomerId);
            if ($response->status->isSuccess && $response->record) {
              if (isset($response->record->addressbookList->addressbook) && is_array($response->record->addressbookList->addressbook)) {
                foreach ($response->record->addressbookList->addressbook as $address ) {
                  $nsAddress = json_decode(json_encode($address->addressbookAddress),true);
                  if (Helper::compareAddresses($nsAddress, $tlAddress)) {
                    $ret = $address->internalId;
                    $cached[$key] = $ret;
                    break;
                  }
                }
                // address not found - add new record into NS addressbook
                if ($ret == 0 && $allowInsert) {
                  $ret = Helper::addAddress($this->client, $tlAddress, $remoteCustomerId);
                  if ($ret) { //customer ID is returned call itsef to get address
                    $ret = $this->getRemoteAddressId($tlAddress, $remoteCustomerId, false);
                  }
                }
              } elseif ($allowInsert) {
                // add new - new record into NS addressbook
                  $ret = Helper::addAddress($this->client, $tlAddress, $remoteCustomerId);
                  if ($ret) { //customer ID is returned call itsef to get address
                    $ret = $this->getRemoteAddressId($tlAddress, $remoteCustomerId, false);
                  }
              }
            }

    /*

                [customForm] => NetSuite\Classes\RecordRef Object
                    (
                        [internalId] => -2
                        [externalId] =>
                        [type] =>
                        [name] => Standard Customer Form
                    )

                [entityId] => Test1 Testl
                [altName] =>
                [isPerson] => 1
                [phoneticName] =>
                [salutation] =>
                [firstName] => Test1
                [middleName] =>
                [lastName] => Testl
                [companyName] =>
                [entityStatus] => NetSuite\Classes\RecordRef Object
                    (
                        [internalId] => 13
                        [externalId] =>
                        [type] =>
                        [name] => CUSTOMER-Closed Won
                    )

                [parent] =>
                [phone] => 0123456789
                [fax] =>
                [email] => vkoshelev@trianic.com
                [url] =>
                [defaultAddress] =>
                [isInactive] =>
                [category] =>
                [title] =>
                [printOnCheckAs] =>
                [altPhone] =>
                [homePhone] =>
                [mobilePhone] =>
                [altEmail] =>
                [language] => _englishUS
                [comments] =>
                [numberFormat] =>
                [negativeNumberFormat] =>
                [dateCreated] => 2012-09-24T09:57:02.000-07:00
                [image] =>
                [emailPreference] => _default
                [subsidiary] =>
                [representingSubsidiary] =>
                [salesRep] => NetSuite\Classes\RecordRef Object
                    (
                        [internalId] => -5
                        [externalId] =>
                        [type] =>
                        [name] => MYACCT1
                    )

                [territory] =>
                [contribPct] =>
                [partner] =>
                [salesGroup] =>
                [vatRegNumber] =>
                [accountNumber] =>
                [taxExempt] =>
                [terms] =>
                [creditLimit] => 0
                [creditHoldOverride] => _auto
                [monthlyClosing] =>
                [overrideCurrencyFormat] =>
                [displaySymbol] =>
                [symbolPlacement] =>
                [balance] => 0
                [overdueBalance] => 0
                [daysOverdue] =>
                [unbilledOrders] => 0
                [consolUnbilledOrders] => 0
                [consolOverdueBalance] => 0
                [consolDepositBalance] => 0
                [consolBalance] => 0
                [consolAging] => 0
                [consolAging1] => 0
                [consolAging2] => 0
                [consolAging3] => 0
                [consolAging4] => 0
                [consolDaysOverdue] =>
                [priceLevel] =>
                [currency] => NetSuite\Classes\RecordRef Object
                    (
                        [internalId] => 1
                        [externalId] =>
                        [type] =>
                        [name] => USD
                    )

                [prefCCProcessor] =>
                [depositBalance] => 0
                [shipComplete] =>
                [taxable] => 1
                [taxItem] =>
                [resaleNumber] =>
                [aging] => 0
                [aging1] => 0
                [aging2] => 0
                [aging3] => 0
                [aging4] => 0
                [startDate] =>
                [alcoholRecipientType] =>
                [endDate] =>
                [reminderDays] =>
                [shippingItem] =>
                [thirdPartyAcct] =>
                [thirdPartyZipcode] =>
                [thirdPartyCountry] =>
                [giveAccess] =>
                [estimatedBudget] =>
                [accessRole] => NetSuite\Classes\RecordRef Object
                    (
                        [internalId] => 14
                        [externalId] =>
                        [type] =>
                        [name] => Customer Center
                    )

                [sendEmail] =>
                [password] =>
                [password2] =>
                [requirePwdChange] =>
                [campaignCategory] =>
                [leadSource] =>
                [receivablesAccount] => NetSuite\Classes\RecordRef Object
                    (
                        [internalId] => -10
                        [externalId] =>
                        [type] =>
                        [name] => Use System Preference
                    )

                [drAccount] =>
                [fxAccount] =>
                [defaultOrderPriority] =>
                [webLead] => No
                [referrer] =>
                [keywords] =>
                [clickStream] =>
                [lastPageVisited] =>
                [visits] =>
                [firstVisit] =>
                [lastVisit] =>
                [billPay] =>
                [openingBalance] =>
                [lastModifiedDate] => 2012-09-24T09:57:02.000-07:00
                [openingBalanceDate] =>
                [openingBalanceAccount] =>
                [stage] => _customer
                [emailTransactions] =>
                [printTransactions] =>
                [faxTransactions] =>
                [syncPartnerTeams] =>
                [isBudgetApproved] =>
                [globalSubscriptionStatus] => _softOptIn
                [salesReadiness] =>
                [salesTeamList] =>
                [buyingReason] =>
                [downloadList] =>
                [buyingTimeFrame] =>
                [addressbookList] =>
                [subscriptionsList] =>
                [contactRolesList] => NetSuite\Classes\ContactAccessRolesList Object
                    (
                        [contactRoles] => Array
                            (
                                [0] => NetSuite\Classes\ContactAccessRoles Object
                                    (
                                        [giveAccess] =>
                                        [contact] => NetSuite\Classes\RecordRef Object
                                            (
                                                [internalId] => 1382
                                                [externalId] =>
                                                [type] =>
                                                [name] => Test1 Testl
                                            )

                                        [email] => vkoshelev@trianic.com
                                        [role] => NetSuite\Classes\RecordRef Object
                                            (
                                                [internalId] => 14
                                                [externalId] =>
                                                [type] =>
                                                [name] => Customer Center
                                            )

                                        [password] =>
                                        [password2] =>
                                        [sendEmail] =>
                                    )

                            )

                        [replaceAll] => 1
                    )

                [currencyList] => NetSuite\Classes\CustomerCurrencyList Object
                    (
                        [currency] => Array
                            (
                                [0] => NetSuite\Classes\CustomerCurrency Object
                                    (
                                        [currency] => NetSuite\Classes\RecordRef Object
                                            (
                                                [internalId] => 1
                                                [externalId] =>
                                                [type] =>
                                                [name] => USD
                                            )

                                        [balance] => 0
                                        [consolBalance] => 0
                                        [depositBalance] => 0
                                        [consolDepositBalance] => 0
                                        [overdueBalance] => 0
                                        [consolOverdueBalance] => 0
                                        [unbilledOrders] => 0
                                        [consolUnbilledOrders] => 0
                                        [overrideCurrencyFormat] =>
                                        [displaySymbol] => $
                                        [symbolPlacement] => _beforeNumber
                                    )

                            )

                        [replaceAll] => 1
                    )

                [creditCardsList] =>
                [partnersList] =>
                [groupPricingList] =>
                [itemPricingList] =>
                [customFieldList] =>
                [internalId] => 1382
                [externalId] =>
                [nullFieldList] =>
            )
      */

          } catch (\Exception $ex) {
            $ret['error'] = $ex->getMessage();
          }
        }
      }
      return $ret;

    }

    protected function getRemoteCustomerId($localCustomerId, $useLocalId = false, $patchArray=[])
    {
        if ( $useLocalId ) {
            $check_mapped_r = tep_db_query(
                "SELECT remote_customers_id " .
                "FROM ep_holbi_soap_link_customers " .
                "WHERE ep_directory_id='" . $this->config['directoryId'] . "' AND local_customers_id='" . (int)$localCustomerId . "' "
            );
            if (tep_db_num_rows($check_mapped_r) > 0) {
                $check_mapped = tep_db_fetch_array($check_mapped_r);
                return $check_mapped['remote_customers_id'];
            }
        }
//customer could be already deleted
        $customer = \common\api\models\AR\Customer::findOne(['customers_id'=>$localCustomerId]);
        if ( !$customer || empty($customer->customers_email_address) ) {
            return 0;
        }

        $localCustomer = $customer->exportArray([]);
        $allCountriesValid = true;
        foreach ($localCustomer['addresses'] as $localAB) {
          $tmp = Helper::lookupNSCountry($localAB['entry_country_iso2']);
          if ( empty($tmp) ) {
            $allCountriesValid = false;
            break;
          }
        }
        if ( !$allCountriesValid ) {
          throw new Exception("Customer export error - address with invalid country " . $localAB['entry_country_iso2']);
        }

        $remoteCustomerId = 0;

        $customerDataArray = Helper::makeCustomerRequestData($localCustomer);

        if ( is_array($patchArray) && count($patchArray)>0 ) {
            $customerDataArray = array_merge($customerDataArray, $patchArray);
        }

        // first - try search customer by email on server
        try {
            if ( is_array($patchArray) && count($patchArray)>0 && !empty($patchArray['customers_email_address']) ) {
                $customer->customers_email_address = $patchArray['customers_email_address'];
            }

            $response = Helper::basicSearch($this->client, 'customer', ['email' => ['value' => $customer->customers_email_address] ]);
/*
[0] => NetSuite\Classes\Customer Object
                        (
                            [customForm] =>
                            [entityId] => Alex Test atkach@holbi.co.uk GBP
                            [altName] =>
                            [isPerson] => 1
                            [phoneticName] =>
                            [salutation] =>
                            [firstName] => Alex
                            [middleName] =>
                            [lastName] => Test
                            [companyName] => Check Inc.
                            [entityStatus] => NetSuite\Classes\RecordRef Object
                                (
                                    [internalId] => 13
                                    [externalId] =>
                                    [type] =>
                                    [name] => CUSTOMER-Closed Won
                                )

                            [parent] =>
                            [phone] => 11222222
                            [fax] =>
                            [email] => atkach@holbi.co.uk
                            [url] =>
                            [defaultAddress] => Alex Test<br>Alex Test<br>Street addr<br>LD8 343<br>United Kingdom (GB)
                            [isInactive] =>
                            [category] =>
                            [title] =>
                            [printOnCheckAs] =>
                            [altPhone] =>
                            [homePhone] =>
                            [mobilePhone] =>
                            [altEmail] =>
                            [language] =>
                            [comments] =>
                            [numberFormat] =>
                            [negativeNumberFormat] =>
                            [dateCreated] => 2012-10-05T02:15:48.000-07:00
                            [image] =>
                            [emailPreference] => _default
                            [subsidiary] =>
                            [representingSubsidiary] =>
                            [salesRep] => NetSuite\Classes\RecordRef Object
                                (
                                    [internalId] => -5
                                    [externalId] =>
                                    [type] =>
                                    [name] => MYACCT1
                                )

                            [territory] =>
                            [contribPct] =>
                            [partner] =>
                            [salesGroup] =>
                            [vatRegNumber] => GB117223643
                            [accountNumber] =>
                            [taxExempt] =>
                            [terms] =>
                            [creditLimit] => 0
                            [creditHoldOverride] => _auto
                            [monthlyClosing] =>
                            [overrideCurrencyFormat] =>
                            [displaySymbol] =>
                            [symbolPlacement] =>
                            [balance] =>
                            [overdueBalance] =>
                            [daysOverdue] =>
                            [unbilledOrders] => 5052.44
                            [consolUnbilledOrders] => 5052.44
                            [consolOverdueBalance] => 0
                            [consolDepositBalance] => 0
                            [consolBalance] => 0
                            [consolAging] => 0
                            [consolAging1] => 0
                            [consolAging2] => 0
                            [consolAging3] => 0
                            [consolAging4] => 0
                            [consolDaysOverdue] =>
                            [priceLevel] => NetSuite\Classes\RecordRef Object
                                (
                                    [internalId] => 2
                                    [externalId] =>
                                    [type] =>
                                    [name] => Corporate Discount Price
                                )

                            [currency] => NetSuite\Classes\RecordRef Object
                                (
                                    [internalId] => 2
                                    [externalId] =>
                                    [type] =>
                                    [name] => British pound
                                )

                            [prefCCProcessor] =>
                            [depositBalance] =>
                            [shipComplete] =>
                            [taxable] => 1
                            [taxItem] =>
                            [resaleNumber] =>
                            [aging] => 0
                            [aging1] => 0
                            [aging2] => 0
                            [aging3] => 0
                            [aging4] => 0
                            [startDate] =>
                            [alcoholRecipientType] => _consumer
                            [endDate] =>
                            [reminderDays] =>
                            [shippingItem] =>
                            [thirdPartyAcct] =>
                            [thirdPartyZipcode] =>
                            [thirdPartyCountry] =>
                            [giveAccess] =>
                            [estimatedBudget] =>
                            [accessRole] => NetSuite\Classes\RecordRef Object
                                (
                                    [internalId] => 14
                                    [externalId] =>
                                    [type] =>
                                    [name] => Customer Center
                                )

                            [sendEmail] =>
                            [password] =>
                            [password2] =>
                            [requirePwdChange] =>
                            [campaignCategory] =>
                            [leadSource] =>
                            [receivablesAccount] => NetSuite\Classes\RecordRef Object
                                (
                                    [internalId] => -10
                                    [externalId] =>
                                    [type] =>
                                    [name] => Use System Preference
                                )

                            [drAccount] =>
                            [fxAccount] =>
                            [defaultOrderPriority] =>
                            [webLead] => No
                            [referrer] =>
                            [keywords] =>
                            [clickStream] =>
                            [lastPageVisited] =>
                            [visits] =>
                            [firstVisit] =>
                            [lastVisit] =>
                            [billPay] =>
                            [openingBalance] =>
                            [lastModifiedDate] => 2018-04-23T11:32:08.000-07:00
                            [openingBalanceDate] =>
                            [openingBalanceAccount] =>
                            [stage] => _customer
                            [emailTransactions] =>
                            [printTransactions] =>
                            [faxTransactions] =>
                            [syncPartnerTeams] =>
                            [isBudgetApproved] =>
                            [globalSubscriptionStatus] => _softOptIn
                            [salesReadiness] =>
                            [salesTeamList] =>
                            [buyingReason] =>
                            [downloadList] =>
                            [buyingTimeFrame] =>
                            [addressbookList] =>
                            [subscriptionsList] =>
                            [contactRolesList] =>
                            [currencyList] =>
                            [creditCardsList] =>
                            [partnersList] =>
                            [groupPricingList] =>
                            [itemPricingList] =>
                            [customFieldList] =>
                            [internalId] => 1582
                            [externalId] =>
                            [nullFieldList] =>
                        )
 *  */
            $matchFields = 0;
            if (isset($response->totalRecords) && $response->totalRecords>0 && is_array($response->recordList->record)) {
              if ($response->totalRecords>1 ) {
                foreach ($response->recordList->record as $record) {
                  if ($response->totalRecords==1) {
                    $remoteCustomerId = $record->internalId;
                    break;
                  }
                  $tmpA = [
                    'customers_company_vat' => $record->vatRegNumber,
                    //'' => $record->salutation,
                    'customers_firstname' => $record->firstName,
                    'customers_lastname' => $record->lastName,
                    'customers_company' => $record->companyName,
                    'customers_telephone' => $record->phone,
                    //'' => $record->altPhone,
                    'customers_landline' => $record->homePhone,
                    //'' => $record->mobilePhone,
                    //'' => $record->language,
                  ];
                  $tmp = count(array_intersect_assoc($tmpA, $customerDataArray));
                  if ($tmp>$matchFields) {
                    $matchFields = $tmp;
                    $remoteCustomerId = $record->internalId;
                    $remoteCustomer = $record;
                  }

                }
              } else {
                $record = $response->recordList->record[0];
                $remoteCustomerId = $record->internalId;
                $remoteCustomer = $record;
              }
/* VL2do - update NS customer - only some (which?) fields should be updated*/
              $usedNsCurrencies = [];
              $r = tep_db_query("select distinct currency from " . TABLE_ORDERS . " where customers_id = '" . (int)$localCustomerId . "'");
              while ($d = tep_db_fetch_array($r)) {
                $usedNsCurrencies[] = Helper::getNsCurrency($d['currency']);
              }
              if (count($usedNsCurrencies) == 0) {
                $usedNsCurrencies[] = Helper::getNsCurrency();
              }
              $customerDataArray['usedNsCurrencies'] = $usedNsCurrencies;

              if ($remoteCustomerId>0) {
                if (is_array($remoteCustomer->currencyList->currency)) {
                  $tmp = array_flip($customerDataArray['usedNsCurrencies']);
                  foreach ($remoteCustomer->currencyList->currency as $ci) {
                    if (isset($tmp[$ci->currency->internalId])) {
                      unset($tmp[$ci->currency->internalId]);
                    }
                  }
                  if (count($tmp)) {
                    $customerDataArray['usedNsCurrencies'] = array_values(array_flip($tmp));
                  } else {
                    unset($customerDataArray['usedNsCurrencies']);
                  }
                }
                $remoteCustomerId = Helper::updateCustomer($this->client, $customerDataArray, $remoteCustomerId);
              } else {
                $remoteCustomerId = Helper::createCustomer($this->client, $customerDataArray);
              }
            } else {
              $usedNsCurrencies = [];
              $r = tep_db_query("select distinct currency from " . TABLE_ORDERS . " where customers_id = '" . (int)$localCustomerId . "'");
              while ($d = tep_db_fetch_array($r)) {
                $usedNsCurrencies[] = Helper::getNsCurrency($d['currency']);
              }
              if (count($usedNsCurrencies) == 0) {
                $usedNsCurrencies[] = Helper::getNsCurrency();
              }
              $customerDataArray['usedNsCurrencies'] = $usedNsCurrencies;
              $remoteCustomerId = Helper::createCustomer($this->client, $customerDataArray);
            }
        }catch (\Exception $ex){
          $remoteCustomerId['error'] = "Search/create customer error " . $ex->getMessage();
        }
        if ( !is_array($remoteCustomerId) ) {
            tep_db_query(
                "DELETE FROM ep_holbi_soap_link_customers ".
                "WHERE ep_directory_id = '".$this->config['directoryId']."' ".
                " AND local_customers_id = '".(int)$localCustomerId."' "
            );
            tep_db_perform('ep_holbi_soap_link_customers',[
                'ep_directory_id' => $this->config['directoryId'],
                'local_customers_id' => (int)$localCustomerId,
                'remote_customers_id' => $remoteCustomerId,
            ]);
        }
        return $remoteCustomerId;
    }

    /**
     * return locally saved NS id
     * @param string  $uprid
     * @param bool $inventory - default false
     * @return int
     */
    protected function getRemoteProductId($uprid, $inventory = false)
    {
      $remote_id = 0;
      if ($inventory) {
        $get_remote_id_r = tep_db_query(
            "SELECT remote_products_id ".
            "FROM ep_holbi_soap_link_inventory ".
            "WHERE ep_directory_id='".(int)$this->config['directoryId']."' ".
            " AND local_uprid='" . tep_db_input($uprid) . "'"
        );
      } else {
        $get_remote_id_r = tep_db_query(
            "SELECT remote_products_id ".
            "FROM ep_holbi_soap_link_products ".
            "WHERE ep_directory_id='".(int)$this->config['directoryId']."' ".
            " AND local_products_id='" . (int)\common\helpers\Inventory::get_prid($uprid) . "'"
        );
      }


      if ( tep_db_num_rows($get_remote_id_r)>0 ) {
          $_remote_id_arr = tep_db_fetch_array($get_remote_id_r);
          $remote_id = $_remote_id_arr['remote_products_id'];
      } 
      return $remote_id;
    }

    protected function getTaxItemId($order, $product) {
      $ret = '';
      $tax_rates_id = 0;
      if (is_array($order)) {
        $taxDescriptions = array_unique(array_keys($order));
        if (count($taxDescriptions)==1) {
          $r = tep_db_query("select tax_rates_id from " . TABLE_TAX_RATES . " where tax_description='" . tep_db_input($taxDescriptions[0]). "'");
          if (tep_db_num_rows($r)==1) {
            $d = tep_db_fetch_array($r);
            $tax_rates_id = $d['tax_rates_id'];
          }
        }

      } elseif (is_array($product)) {
        if (!empty($product['tax_selected'])) { // class_zone
          $tmp = explode('_', $product['tax_selected']);
          if (count($tmp)==2) {
            $r = tep_db_query("select tax_rates_id from " . TABLE_TAX_RATES . " where tax_class_id='" . (int)$tmp[0] . "' and tax_zone_id='" . (int)$tmp[1] . "'");
            if (tep_db_num_rows($r)==1) {
              $d = tep_db_fetch_array($r);
              $tax_rates_id = $d['tax_rates_id'];
            }
          }
        }
        if ($tax_rates_id ==0 &&  !empty($product['tax_description'])) { // class_zone
          $r = tep_db_query("select tax_rates_id from " . TABLE_TAX_RATES . " where tax_description='" . tep_db_input($product['tax_description']). "'");
          if (tep_db_num_rows($r)==1) {
            $d = tep_db_fetch_array($r);
            $tax_rates_id = $d['tax_rates_id'];
          }
        }
        if ($tax_rates_id ==0 && !empty($product['tax']) && !empty($product['tax_class_id'])) { // class_zone
          $r = tep_db_query("select tax_rates_id from " . TABLE_TAX_RATES . " where tax_class_id='" . (int)$product['tax_class_id'] . "' and tax_rate='" . tep_db_input($product['tax']). "'");
          if (tep_db_num_rows($r)==1) {
            $d = tep_db_fetch_array($r);
            $tax_rates_id = $d['tax_rates_id'];
          }
        }
      }
      if ($tax_rates_id>0) {
        $d = tep_db_fetch_array(tep_db_query(
            "SELECT remote_id ".
            "FROM ep_holbi_soap_mapping ".
            "WHERE ep_directory_id='" . (int)$this->config['directoryId'] . "' ".
            " AND mapping_type='taxrate'" .
            " AND local_id='" . (int)$tax_rates_id . "'"
        ));
        if (!empty($d['remote_id'])) {
          $ret = $d['remote_id'];
        }
      }
      return $ret;
    }
}
