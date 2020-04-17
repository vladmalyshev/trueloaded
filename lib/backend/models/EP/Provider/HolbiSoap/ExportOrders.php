<?php

/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models\EP\Provider\HolbiSoap;

use backend\models\EP\Directory;
use backend\models\EP\Exception;
use backend\models\EP\Messages;
use backend\models\EP\Provider\DatasourceInterface;
use backend\models\EP\Tools;
use common\api\models\AR\Categories;
use common\api\models\AR\Products;
use common\classes\language;
use common\classes\Order;
use common\helpers\Acl;

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
        // init client
        try {
            $this->client = new \SoapClient(
                $this->config['client']['wsdl_location'],
                [
                    'trace' => 1,
                    //'proxy_host'     => "localhost",
                    //'proxy_port'     => 8080,
                    //'proxy_login'    => "some_name",
                    //'proxy_password' => "some_password",
                    'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP,
                    'stream_context' => stream_context_create([
                        'http' => [
                            //'header'  => "APIToken: $api_token\r\n",
                        ]
                    ]),
                ]
            );
            $auth = new \stdClass();
            $auth->api_key = $this->config['client']['department_api_key'];
            $soapHeaders = new \SoapHeader('http://schemas.xmlsoap.org/ws/2002/07/utility', 'auth', $auth, false);
            $this->client->__setSoapHeaders($soapHeaders);
        }catch (\Exception $ex) {
            throw new Exception('Soap Configuration error');
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

        $this->syncOrderStatuses();

        $limitOrderIds = false;
        if ( isset($this->config['job_configure']) && !empty($this->config['job_configure']['forceProcessOrders']) ) {
            $limitOrderIds = array_map('intval', $this->config['job_configure']['forceProcessOrders']);
            $limitOrderIds = array_unique($limitOrderIds);
        }

        if ( $this->allow_update_order ) {
            $orders_sql = (
                "SELECT o_r.* FROM ( ".
                "(".
                "  SELECT o_c.orders_id, 0 AS update_order, lo_c.cfg_export_as, NULL AS local_order_last_modified_hash ".
                "  FROM ".TABLE_ORDERS." o_c ".
                "    LEFT JOIN ep_holbi_soap_link_orders lo_c ".
                "      ON lo_c.ep_directory_id='" . (int)$this->config['directoryId'] . "' AND lo_c.local_orders_id=o_c.orders_id ".
                "  WHERE lo_c.local_orders_id IS NULL ".
                (is_array($limitOrderIds) && count($limitOrderIds)>0?" AND o_c.orders_id IN('".implode("','",$limitOrderIds)."') ":'').
                (is_array($need_status_list) ? " AND o_c.orders_status IN('" . implode("','", $need_status_list) . "') " : " ") .
                ") ".
                "UNION ".
                "(".
                "  SELECT o_u.orders_id, 1 AS update_order, lo_u.cfg_export_as, lo_u.local_order_last_modified_hash ".
                "  FROM ".TABLE_ORDERS." o_u ".
                "    INNER JOIN ep_holbi_soap_link_orders lo_u ".
                "      ON lo_u.ep_directory_id='" . (int)$this->config['directoryId'] . "' AND lo_u.local_orders_id=o_u.orders_id ".
                "  WHERE lo_u.local_order_last_modified != IFNULL(o_u.last_modified, o_u.date_purchased) ".
                (is_array($limitOrderIds) && count($limitOrderIds)>0?" AND o_u.orders_id IN('".implode("','",$limitOrderIds)."') ":'').
                ")".
                ") AS o_r ORDER BY o_r.orders_id"
            );
        }else{
            $orders_sql = (
                "SELECT o.orders_id, 0 AS update_order, " .
                " lo.cfg_export_as, ".
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
            if ( $data['cfg_export_as']=='po_order' ) {
                $this->updatePoOrder($message, $data['orders_id'], $data['local_order_last_modified_hash']);
            }else{
                $this->updateOrder($message, $data['orders_id'], $data['local_order_last_modified_hash']);
            }
        }else{
            if (isset($this->config['order']['export_as']) && $this->config['order']['export_as']=='po_order') {
                $this->exportAsPoOrder($message, $data['orders_id']);
            }else{
                $this->exportOrder($message, $data['orders_id']);
            }
        }
        $this->row_count++;

        return $data;
    }

    public function postProcess(Messages $message)
    {

    }

    protected function updatePoOrder(Messages $message, $orderId, $checkHash='')
    {
        tep_db_query(
            "UPDATE ep_holbi_soap_link_orders epol ".
            " INNER JOIN ".TABLE_ORDERS." o ON o.orders_id=epol.local_orders_id ".
            "SET epol.local_order_last_modified=IFNULL(o.last_modified, o.date_purchased), ".
            " epol.local_order_last_modified_hash='".Helper::generateOrderHash($orderId)."' ".
            "WHERE epol.ep_directory_id = '".(int)$this->config['directoryId']."' ".
            " AND epol.local_orders_id = '".$orderId."'"
        );

        tep_db_query(
            "UPDATE ".TABLE_ORDERS." ".
            "SET _api_order_time_modified=_api_order_time_modified, _api_order_time_processed=_api_order_time_modified ".
            "WHERE orders_id='".$orderId."' "
        );
    }

    protected function updateOrder(Messages $message, $orderId, $checkHash='')
    {
        try {
            $order = new \common\classes\Order($orderId);
        }catch (\Exception $ex){
            $message->info(" [!] order #{$orderId} skipped. Couldn't load local order");
            \Yii::error("Exception[1] updateOrder #{$orderId} skipped. Couldn't load local order: ".$ex->getMessage()."\n".$ex->getTraceAsString()."\n",'datasource');
            return;
        }
        if ( !is_array($order->customer['country']) || empty($order->customer['country']['iso_code_2']) ) {
            $message->info(" [!] order #{$orderId} skipped. Couldn't load local order [1]");
            return;
        }
        if ( !is_array($order->billing['country']) || empty($order->billing['country']['iso_code_2']) ) {
            $message->info(" [!] order #{$orderId} skipped. Couldn't load local order [2]");
            return;
        }
        if ( !is_array($order->delivery['country']) || empty($order->delivery['country']['iso_code_2']) ) {
            $message->info(" [!] order #{$orderId} skipped. Couldn't load local order [3]");
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
            \Yii::error("Exception[2] updateOrder #{$orderId} skipped. Remote customer get: ".$ex->getMessage()."\n".$ex->getTraceAsString()."\n",'datasource');
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
                        if ( $product['id'] ) {
                            $remoteOptionId = Helper::lookupRemoteOptionId((int)$this->config['directoryId'], $attribute['option_id']);
                            if ( $remoteOptionId===false ) $remoteOptionId = -2;
                            $remoteOptionValueId = Helper::lookupRemoteOptionValueId((int)$this->config['directoryId'], $remoteOptionId, $attribute['value_id']);
                            if ( $remoteOptionValueId===false ) $remoteOptionValueId = -2;
                        }else{
                            $remoteOptionId = -2;
                            $remoteOptionValueId = -2;
                        }
                        $product['attributes']['attribute'][] = [
                            'option_id' => $remoteOptionId,
                            'value_id' => $remoteOptionValueId,
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
            \Yii::error("Exception[1] exportOrder #{$orderId} skipped. Couldn't load local order: ".$ex->getMessage()."\n".$ex->getTraceAsString()."\n",'datasource');
            return false;
        }

        $orderData = $this->makeOrderData($message, $order);
        if ( $orderData===false ) {
            return;
        }

        try {
            $response = $this->client->createOrder(
                $orderData
            );

            $retryWithCustomerLink = false;
            if ( $response->messages && $response->messages->message ) {
                $response_messages = $response->messages->message;
                if ( !is_array($response_messages) ) $response_messages = [$response_messages];
                foreach( $response_messages as $response_message ) {
                    if ($response_message->code=='ERROR_CUSTOMER_NOT_FOUND' /*|| $response_message->code=='ERROR_CUSTOMER_EMAIL_MISMATCH'*/){
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

            if ( $response->status!='ERROR' ) {
                $remote_order_id = $response->orders_id;
                tep_db_perform(
                    'ep_holbi_soap_link_orders',
                    [
                        'ep_directory_id' => (int)$this->config['directoryId'],
                        'remote_orders_id' => $remote_order_id,
                        'local_orders_id' => $order->order_id,
                        'cfg_export_as' => empty($this->config['order']['export_as'])?'order':$this->config['order']['export_as'],
                        'track_remote_order' => 1,
                        'date_exported' => 'now()',
                    ]
                );
                $export_success_status = $this->config['order']['export_success_status'];
                if ( $export_success_status ) {
                    $comments = '';// 'Remote order ID '.$remote_order_id;

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
            }
        }catch (\Exception $ex){
            $message->info(" [!] order #{$orderId} export error: ".$ex->getCode().':'.$ex->getMessage()."");
            \Yii::info("Export As SaleOrder Exception:\n".$this->client->__getLastRequest()."\n\n".$this->client->__getLastResponse()."\n\n",'datasource');
        }

    }

    protected function exportAsPoOrder(Messages $message, $orderId)
    {
        try {
            $order = new \common\classes\Order($orderId);
        }catch (\Exception $ex){
            $message->info(" [!] order #{$orderId} skipped. Couldn't load local order");
            \Yii::error("Exception[1] exportOrder #{$orderId} skipped. Couldn't load local order: ".$ex->getMessage()."\n".$ex->getTraceAsString()."\n",'datasource');
            return false;
        }


        $platformConfig = \Yii::$app->get('platform')->getConfig(\common\classes\platform::defaultId());
        /**
         * @var \common\classes\platform_config $platformConfig
         */
        $address = $platformConfig->getPlatformAddress();
        $address['telephone'] = ''; //
        $address['landline'] = ''; //
        $address['email_address'] = $platformConfig->const_value('STORE_OWNER_EMAIL_ADDRESS');
        if ( empty($address['name']) ) {
            $address['name'] = $platformConfig->const_value('STORE_OWNER');
        }
        if ( empty($address['firstname']) && empty($address['lastname']) ) {
            list($firstname, $lastname) = explode(' ',$address['name']);
            $address['firstname'] = (string)$firstname;
            $address['lastname'] = (string)$lastname;
        }

        $countryInfo = \common\helpers\Country::get_country_info_by_id($address['country_id']);
        $address['country'] = array(
            'id' => $address['country_id'],
            'title' => $countryInfo['countries_name'],
            'iso_code_2' => $countryInfo['countries_iso_code_2'],
            'iso_code_3' => $countryInfo['countries_iso_code_3'],
        );

        foreach (['customer', 'billing', 'delivery'] as $patchKey) {
            foreach (array_keys($order->{$patchKey}) as $addressKey){
                $order->{$patchKey}[$addressKey] = isset($address[$addressKey])?$address[$addressKey]:'';
            }
        }

        $remoteCustomerId = 0;
/*
        try {
            $searchResult = $this->client->searchCustomer([
                'customers_email_address' => $address['email_address'],
            ]);
            if ($searchResult && $searchResult->customers && $searchResult->customers->customer) {
                $remoteCustomerId = $searchResult->customers->customer->customers_id;

            } else {
                $response = $this->client->createCustomer($customerDataArray);
                if ($response && $response->customer && $response->customer->customers_id) {
                    $remoteCustomerId = $response->customer->customers_id;
                }
            }
        }catch (\Exception $ex){

        }
*/
        $orderData = $this->makeOrderData($message, $order, $remoteCustomerId);

        if ( $orderData===false ) {
            return;
        }

        try {
            $response = $this->client->createPurchaseOrder(
                $orderData
            );
            \Yii::info("Export As PO:\n".$this->client->__getLastRequest()."\n\n".$this->client->__getLastResponse()."\n\n",'datasource');

            if ( $response->messages && $response->messages->message ) {
                $response_messages = $response->messages->message;
                if ( !is_array($response_messages) ) $response_messages = [$response_messages];
                foreach( $response_messages as $response_message ) {
                    $message->info(" [!] order #{$orderId} {$response_message->code}: {$response_message->text}");
                }
            }

            if ( $response->status!='ERROR' ) {
                $remote_order_id = $response->orders_id;
                tep_db_perform(
                    'ep_holbi_soap_link_orders',
                    [
                        'ep_directory_id' => (int)$this->config['directoryId'],
                        'remote_orders_id' => $remote_order_id,
                        'local_orders_id' => $order->order_id,
                        'cfg_export_as' => 'po_order',
                        'track_remote_order' => 1,
                        'date_exported' => 'now()',
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
            }
        }catch (\Exception $ex){
            $message->info(" [!] order #{$orderId} export error: ".$ex->getCode().':'.$ex->getMessage()."");
        }
    }

    protected function makeOrderData(Messages $message, Order $order, $remoteCustomerId=false)
    {
        $orderId = $order->info['orders_id'];
        if ( !is_array($order->customer['country']) || empty($order->customer['country']['iso_code_2']) ) {
            $message->info(" [!] order #{$orderId} skipped. Couldn't load local order [1]");
            return false;
        }
        if ( !is_array($order->billing['country']) || empty($order->billing['country']['iso_code_2']) ) {
            $message->info(" [!] order #{$orderId} skipped. Couldn't load local order [2]");
            return false;
        }
        if ( !is_array($order->delivery['country']) || empty($order->delivery['country']['iso_code_2']) ) {
            $message->info(" [!] order #{$orderId} skipped. Couldn't load local order [3]");
            return false;
        }

        $order->info['platform_name'] = \common\classes\platform::name($order->info['platform_id']);

        if ( $remoteCustomerId===false ) {
            try {
                $remoteCustomerId = $this->getRemoteCustomerId($order->customer['customer_id']);
            } catch (\Exception $ex) {
                $message->info(" [!] order #{$orderId} skipped. " . $ex->getMessage());
                \Yii::error("Exception[2] exportOrder #{$orderId} skipped. Remote customer get: " . $ex->getMessage() . "\n" . $ex->getTraceAsString() . "\n", 'datasource');
                return false;
            }
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
                        if ( $product['id'] ) {
                            $remoteOptionId = Helper::lookupRemoteOptionId((int)$this->config['directoryId'], $attribute['option_id']);
                            if ( $remoteOptionId===false ) $remoteOptionId = -2;
                            $remoteOptionValueId = Helper::lookupRemoteOptionValueId((int)$this->config['directoryId'], $remoteOptionId, $attribute['value_id']);
                            if ( $remoteOptionValueId===false ) $remoteOptionValueId = -2;
                        }else{
                            $remoteOptionId = -2;
                            $remoteOptionValueId = -2;
                        }
                        $product['attributes']['attribute'][] = [
                            'option_id' => $remoteOptionId,
                            'value_id' => $remoteOptionValueId,
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

        if ( true ) {
            $orderData['status_history_array'] = [];
            if (is_array($this->config['status_map_local_to_server']) && count($this->config['status_map_local_to_server']) > 0) {
                $get_history_r = tep_db_query(
                    "SELECT * " .
                    "FROM " . TABLE_ORDERS_STATUS_HISTORY . " " .
                    "WHERE orders_id = '" . $order->order_id . "' " .
                    " AND orders_status_id IN ('" . implode("','", array_keys($this->config['status_map_local_to_server'])) . "') " .
                    " AND orders_status_id!=0 " .
                    "ORDER BY date_added, orders_status_history_id"
                );
                if (tep_db_num_rows($get_history_r) > 0) {
                    while ($_history = tep_db_fetch_array($get_history_r)) {
                        $_history['orders_status_id'] = isset($this->config['status_map_local_to_server'][$_history['orders_status_id']]) ? $this->config['status_map_local_to_server'][$_history['orders_status_id']] : 0;
                        if (empty($_history['orders_status_id'])) continue;
                        $_history['date_added'] = (new \DateTime($_history['date_added']))->format(DATE_ISO8601);
                        $orderData['status_history_array'][] = $_history;
                    }
                }
            }
        }
        return $orderData;
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

        $customer = \common\api\models\AR\Customer::findOne(['customers_id'=>$localCustomerId]);
        if ( !$customer || empty($customer->customers_email_address) ) {
            return 0;
        }

        $localCustomer = $customer->exportArray([]);
        $allCountriesValid = true;
        foreach ($localCustomer['addresses'] as $localAB) {
            if ( empty($localAB['entry_country_iso2']) ) {
                $allCountriesValid = false;
                break;
            }
        }
        if ( !$allCountriesValid ) {
            throw new Exception("Customer export error - address with invalid country");
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

            $searchResult = $this->client->searchCustomer([
                'customers_email_address' => $customer->customers_email_address,
            ]);

            if ($searchResult && $searchResult->customers && $searchResult->customers->customer) {
                $remoteCustomerId = $searchResult->customers->customer->customers_id;

                $response = $this->client->updateCustomer($customerDataArray);
                if ($response && $response->customer && $response->customer->customers_id) {
                    $remoteCustomerId = $response->customer->customers_id;
                }
            } else {
                $response = $this->client->createCustomer($customerDataArray);
                if ($response && $response->customer && $response->customer->customers_id) {
                    $remoteCustomerId = $response->customer->customers_id;
                }
            }
        }catch (\Exception $ex){
            
        }
        if ( $remoteCustomerId ) {
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

    protected function getRemoteProductId($uprid)
    {
        $remote_id = 0;

        $get_remote_id_r = tep_db_query(
            "SELECT remote_products_id ".
            "FROM ep_holbi_soap_link_products ".
            "WHERE ep_directory_id='".(int)$this->config['directoryId']."' ".
            " AND local_products_id='".$uprid."'"
        );
        if ( tep_db_num_rows($get_remote_id_r)>0 ) {
            $_remote_id_arr = tep_db_fetch_array($get_remote_id_r);
            $remote_id = $_remote_id_arr['remote_products_id'];
        }
        return $remote_id;
    }

    private function syncOrderStatuses()
    {
        $datasource = Directory::loadById($this->config['directoryId'])->getDatasource();
        if ( $datasource ) {
            Helper::syncOrderStatuses($this->client, $datasource);
        }
    }

}