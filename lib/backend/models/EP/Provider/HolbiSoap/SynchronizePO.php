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
use backend\models\EP\Messages;
use backend\models\EP\Provider\DatasourceInterface;
use backend\models\EP\Exception;
use backend\models\EP\Tools;
use common\classes\Order;
use common\helpers\Acl;
use yii\BaseYii;
use yii\helpers\ArrayHelper;

class SynchronizePO implements DatasourceInterface
{

    protected $total_count = 0;
    protected $row_count = 0;
    protected $process_orders_r;

    protected $config = [];

    /**
     * @var \SoapClient
     */
    protected $client;

    protected $check_order_ids = [];
    protected $startJobServerGmtTime = '';
    protected $useModifyTimeCheck = true;
    protected $isErrorOccurredDuringCheck = false;

    private $allow_update_order = true;

    protected $local_modified_orders_r = false;

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
        global $languages_id;

        $lng = new \common\classes\language();
        $languages_id = $lng->language['id'];
        $lng->set_locale();
        $lng->load_vars();

        \common\helpers\Translation::init('admin/main');
        \common\helpers\Translation::init('admin/orders');

        $limitOrderIds = false;
        if ( isset($this->config['job_configure']) && !empty($this->config['job_configure']['forceProcessOrders']) ) {
            $limitOrderIds = array_map('intval', $this->config['job_configure']['forceProcessOrders']);
            $limitOrderIds = array_unique($limitOrderIds);
        }

        // init client
        try {
            $this->client = new \SoapClient(
                $this->config['client']['wsdl_location'],
                [
                    'trace' => 1,
                    'cache_wsdl' =>  WSDL_CACHE_MEMORY,
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

        $this->syncOrderStatuses();

        $need_status_list = $this->config['order']['server_dispatched_statuses'];

        if ( !is_array($need_status_list) || count($need_status_list)==0 ) {
            throw new Exception('Server dispatched statuses not configured');
        }

        if ( is_array($limitOrderIds) && count($limitOrderIds)>0 ) {
            $this->useModifyTimeCheck = false;
            $this->getOrderInfoByOrderId($message, $limitOrderIds);
            return;
        }
        try {
            $response = $this->client->getServerTime();
            $this->startJobServerGmtTime = gmdate('Y-m-d H:i:s',strtotime($response->time));
        }catch(\Exception $ex){
            throw new Exception('Server time fetch error: '.$ex->getMessage());
        }
        if ( empty($this->startJobServerGmtTime) ) {
            throw new Exception('Server time fetch error');
        }

        if ( $this->useModifyTimeCheck ) {
            $this->getOrderInfoByOrderModifyDate($message);
        }else {
            $this->getOrderInfoByOrderId($message);
        }

        $this->local_modified_orders_r = tep_db_query(
            "  SELECT o_u.orders_id, lo_u.remote_orders_id AS server_order_id, 1 AS update_order, lo_u.cfg_export_as, lo_u.local_order_last_modified_hash ".
            "  FROM ".TABLE_ORDERS." o_u ".
            "    INNER JOIN ep_holbi_soap_link_orders lo_u ".
            "      ON lo_u.ep_directory_id='" . (int)$this->config['directoryId'] . "' AND lo_u.local_orders_id=o_u.orders_id ".
            "  WHERE lo_u.local_order_last_modified != IFNULL(o_u.last_modified, o_u.date_purchased) ".
            (is_array($limitOrderIds) && count($limitOrderIds)>0?" AND o_u.orders_id IN('".implode("','",$limitOrderIds)."') ":'')
        );
    }

    protected function getOrderInfoByOrderModifyDate($message)
    {
        $startFromDate = Helper::getKeyValue($this->config['directoryId'],'TrackPurchaseOrders/lastOrderModifyGmt');

        $need_track = tep_db_fetch_array(tep_db_query(
            "SELECT COUNT(DISTINCT o.orders_id) AS total_for_check ".
            "FROM ".TABLE_ORDERS." o ".
            " INNER JOIN ep_holbi_soap_link_orders lo ON lo.local_orders_id=o.orders_id AND lo.ep_directory_id='".(int)$this->config['directoryId']."' ".
            "WHERE 1 ".
            " AND lo.remote_orders_id IS NOT NULL ".
            " AND lo.track_remote_order = 1 ".
            ""
        ));
//$this->total_count = tep_db_num_rows($this->process_orders_r);
        $message->info("Found ".$need_track['total_for_check']." orders for track");

        if ( empty($startFromDate) ) {
            $startFromDate = '1970-01-01 00:00:00';
        }else{
            $startFromDate = date('Y-m-d H:i:s', strtotime('-40 minutes',strtotime($startFromDate)));
        }

        $this->startJobServerGmtTime;

        $this->check_order_ids = [];
        $page = 1;
        while(true) {
            try {
                $response = $this->client->getPurchaseOrdersInfo(
                    [
                        'searchCondition' => [
                            [
                                'operator' => '>=',
                                'column' => 'last_modified',
                                'values' => ['value'=>(new \DateTime($startFromDate, new \DateTimeZone('UTC')))->format(DATE_ISO8601)],
                            ]
                        ],
                    ],
                    [
                        'page' => $page,
                        //'perPage' => 2,
                    ]
                );

                if ($response && $response->status != 'ERROR') {
                    //$message->info("Found ".$response->paging->totalRows." orders modified since last check");

                    $orderInfoList = (isset($response->ordersInfo->order_info) && is_object($response->ordersInfo->order_info)) ? [$response->ordersInfo->order_info] : $response->ordersInfo->order_info;
                    if (is_array($orderInfoList)) {
                        foreach ($orderInfoList as $orderInfo) {
                            $remote_order_id = $orderInfo->order_id;
                            $check_track_required_r = tep_db_query(
                                "SELECT o.orders_id, lo.remote_orders_id, lo.cfg_export_as ".
                                "FROM ".TABLE_ORDERS." o ".
                                " INNER JOIN ep_holbi_soap_link_orders lo ON lo.local_orders_id=o.orders_id AND lo.ep_directory_id='".(int)$this->config['directoryId']."' ".
                                "WHERE 1 ".
                                " AND lo.remote_orders_id='".tep_db_input($remote_order_id)."' ".
                                //" AND lo.track_remote_order = 1 ".
                                "GROUP BY o.orders_id "
                            );
                            if ( tep_db_num_rows($check_track_required_r)>0 ) {
                                $check_track_required = tep_db_fetch_array($check_track_required_r);
                                $check_track_required['info'] = $orderInfo;
                                $this->check_order_ids[$remote_order_id] = $check_track_required;
                            }else/*if(empty($orderInfo->client_order_id))*/{
                                // missing local order - created on server
                                $this->check_order_ids[$remote_order_id] = [
                                    'remote_orders_id' => $remote_order_id,
                                    'orders_id' => 0,
                                    'cfg_export_as' => '',
                                ];
                            }
                        }
                    }
                }else{
                    $this->startJobServerGmtTime = '';
                }

            } catch (\Exception $ex) {
                $message->info("Error fetching server orders: ".$ex->getMessage());
                \Yii::error('getOrdersInfo Exception : '.$ex->getMessage()."\n\n".$this->client->__getLastRequest()."\n\n".$this->client->__getLastResponse(), 'datasource');
                $this->startJobServerGmtTime = '';
            }
            if ( $response->paging->totalPages>$page ) {
                $page++;
            }else{
                break;
            }
        }

        $this->total_count = count($this->check_order_ids);
        $message->info("Found ".$this->total_count." orders modified since last check");
        reset($this->check_order_ids);
    }

    protected function getOrderInfoByOrderId(Messages $message, $limitOrderIds=false)
    {
        $this->process_orders_r = tep_db_query(
            "SELECT o.orders_id, lo.remote_orders_id, lo.cfg_export_as ".
            "FROM ".TABLE_ORDERS." o ".
            " INNER JOIN ep_holbi_soap_link_orders lo ON lo.local_orders_id=o.orders_id AND lo.ep_directory_id='".(int)$this->config['directoryId']."' ".
            "WHERE 1 ".
            " AND lo.remote_orders_id IS NOT NULL ".
            " AND lo.track_remote_order = 1 ".
            (is_array($limitOrderIds) && count($limitOrderIds)>0?" AND o.orders_id IN ('".implode("','",$limitOrderIds)."') ":'').
            "GROUP BY o.orders_id ".
            "ORDER BY o.orders_id "
        );
        $this->total_count = tep_db_num_rows($this->process_orders_r);
        $message->info("Found ".$this->total_count." orders");
        $this->check_order_ids = [];
        if ( tep_db_num_rows($this->process_orders_r)>0 ) {
            while($check_order = tep_db_fetch_array($this->process_orders_r)){
                $this->check_order_ids[ $check_order['remote_orders_id'] ] = [
                    'orders_id' => $check_order['orders_id'],
                    'remote_orders_id' => $check_order['remote_orders_id'],
                    'cfg_export_as' => $check_order['cfg_export_as'],
                ];
            }
            $message->info('Get server order information');

            $remote_order_ids = array_keys($this->check_order_ids);
            do{
                $check_remote_order_ids = array_splice($remote_order_ids,0,200);

                $page = 1;
                while(true) {
                    try {
                        $response = $this->client->getPurchaseOrdersInfo(
                            [
                                'searchCondition' => [
                                    [
                                        'operator' => 'IN',
                                        'column' => 'orders_id',
                                        'values' => [$check_remote_order_ids]
                                    ]
                                ],
                            ],
                            [
                                'page' => $page,
                                //'perPage' => 2,
                            ]
                        );
                        if ($response && $response->status != 'ERROR') {
                            $orderInfoList = (isset($response->ordersInfo->order_info) && is_object($response->ordersInfo->order_info)) ? [$response->ordersInfo->order_info] : $response->ordersInfo->order_info;
                            if (is_array($orderInfoList)) {
                                foreach ($orderInfoList as $orderInfo) {
                                    $remote_order_id = $orderInfo->order_id;
                                    $this->check_order_ids[$remote_order_id]['info'] = $orderInfo;
                                }
                            }
                        }

                    } catch (\Exception $ex) {
                    }
                    if ( $response->paging->totalPages>$page ) {
                        $page++;
                    }else{
                        break;
                    }
                }
            }while(count($remote_order_ids)>0);

        }
        reset($this->check_order_ids);

    }

    public function processRow(Messages $message)
    {
        $data = current($this->check_order_ids);// tep_db_fetch_array($this->process_orders_r);
        if ( !is_array($data) ) return $data;

        if ( empty($data['orders_id']) ) {
            $this->copyRemoteOrder($message, $data['remote_orders_id']);
        }else {
            $this->trackRemoteOrder($message, $data['orders_id'], $data['remote_orders_id']);
        }
        $this->row_count++;

        next($this->check_order_ids);

        return $data;
    }

    public function postProcess(Messages $message)
    {
        if ( $this->local_modified_orders_r && tep_db_num_rows($this->local_modified_orders_r)>0 ) {
            $this->total_count = tep_db_num_rows($this->local_modified_orders_r);
            $this->row_count = 0;
            $message->info('Update '.tep_db_num_rows($this->local_modified_orders_r).' locally modified orders');
            $message->progress(0);
            while ( $data = tep_db_fetch_array($this->local_modified_orders_r) ){
                $this->updateOrder($message, $data['orders_id'], $data['local_order_last_modified_hash'], $data['server_order_id']);
                $this->row_count++;
                $message->progress($this->getProgress());
            }
            $message->progress(100);
        }

        if ( $this->useModifyTimeCheck && !empty($this->startJobServerGmtTime) && !$this->isErrorOccurredDuringCheck ) {
            Helper::setKeyValue(
                $this->config['directoryId'],
                'TrackPurchaseOrders/lastOrderModifyGmt',
                $this->startJobServerGmtTime
            );
        }

    }

    protected function copyRemoteOrder(Messages $message, $remote_orders_id)
    {
        $message->info('[*] Create remote order #'.$remote_orders_id);

        $orderData = false;
        try {
            $response = $this->client->getPurchaseOrder($remote_orders_id);
            if ($response && $response->status != 'ERROR') {
                $orderData = $response->order;
            }else{
                $messagesText = '';
                if ( isset($response->messages) && !empty($response->messages) ) {
                    $messages = ArrayHelper::isIndexed($response->messages)?$response->messages:[$response->messages];
                    foreach( $messages as $messageObj ) {
                        $messagesText.= "\n * ".$messageObj->code.': '.$messageObj->text;
                    }
                }
                $message->info('[!] Fetch remote order error '.$messagesText);
                \Yii::info("getPurchaseOrder Error:\n".$this->client->__getLastRequest()."\n\n".$this->client->__getLastResponse()."\n\n",'datasource');
                $this->isErrorOccurredDuringCheck = true;
            }
        }catch (\Exception $ex){
            $this->isErrorOccurredDuringCheck = true;
            $message->info('[!] Fetch remote order error' .$ex->getMessage());
            \Yii::info("getPurchaseOrder Error Exception:\n".$this->client->__getLastRequest()."\n\n".$this->client->__getLastResponse()."\n\n",'datasource');
            return;
        }

        if ( $orderData===false ) return;

        $localOrderId = $this->createLocalOrder($message, $orderData);
        if ( $localOrderId ) {
            tep_db_perform(
                'ep_holbi_soap_link_orders',
                [
                    'ep_directory_id' => (int)$this->config['directoryId'],
                    'remote_orders_id' => $remote_orders_id,
                    'local_orders_id' => $localOrderId,
                    'track_remote_order' => 1,
                    'date_exported' => 'now()',
                ]
            );
            tep_db_query(
                "UPDATE ep_holbi_soap_link_orders epol ".
                " INNER JOIN ".TABLE_ORDERS." o ON o.orders_id=epol.local_orders_id ".
                "SET epol.local_order_last_modified=IFNULL(o.last_modified, o.date_purchased), ".
                " epol.local_order_last_modified_hash='".Helper::generateOrderHash($localOrderId)."' ".
                "WHERE epol.ep_directory_id = '".(int)$this->config['directoryId']."' ".
                " AND epol.local_orders_id = '".$localOrderId."'"
            );

//            try {
//                $this->client->createOrderAcknowledgment($remote_orders_id, $localOrderId);
//            }catch (\Exception $ex){}
        }

    }

    /**
     * Must update orders statuses FROM server PO to local PO
     * @param Messages $message
     * @param $orders_id
     * @param $remote_orders_id
     */
    protected function trackRemoteOrder(Messages $message, $orders_id, $remote_orders_id)
    {
        global $languages_id;
        $message->info('[*] Check order # '.$orders_id.'; remote order #'.$remote_orders_id);

        $oID = $orders_id;
        try {
            $order = new \common\classes\Order($oID);
        }catch (\Exception $ex){
            $message->info('[!] Load local order error # '.$orders_id);
            return;
        }
        $admin_id = 0;

        $get_order_modify_state = tep_db_fetch_array(tep_db_query(
            "SELECT _api_order_time_processed, _api_order_time_modified ".
            "FROM  ".TABLE_ORDERS." ".
            "WHERE orders_id='".$orders_id."' "
        ));

        $isOrderLocallyModified = false;
        if ( !empty($get_order_modify_state['_api_order_time_processed']) && $get_order_modify_state['_api_order_time_processed']!=$get_order_modify_state['_api_order_time_modified'] ) {
            $isOrderLocallyModified = true;
        }

        $platform_config = \Yii::$app->get('platform')->config($order->info['platform_id']);
        // {{
        $link = \Yii::$app->get('platform')->config()->getCatalogBaseUrl( true );
        \Yii::$app->getUrlManager()->setBaseUrl($link);
        // }}
        try{

            $remoteOrderInfo = $this->check_order_ids[$remote_orders_id]['info'];

            $response = $this->client->getPurchaseOrder($remote_orders_id);

            if ($response && $response->status != 'ERROR') {
                $remoteOrderInfo = $response->order->info;
                $this->updateLocalOrderStatusHistory($message,$order, $response->order);
//                if ($this->allow_update_order) {
//                    $this->updateLocalOrder($message, $order, $response->order);
//                }else{
//                    $this->updateLocalOrderPartial($message, $order, $remoteOrderInfo);
//                }
            }

            if ( $remoteOrderInfo!==false  ) {
                if ( !$isOrderLocallyModified ) {
                    tep_db_query(
                        "UPDATE ep_holbi_soap_link_orders epol " .
                        " INNER JOIN " . TABLE_ORDERS . " o ON o.orders_id=epol.local_orders_id " .
                        "SET epol.local_order_last_modified=IFNULL(o.last_modified, o.date_purchased), " .
                        " epol.local_order_last_modified_hash='" . Helper::generateOrderHash($order->order_id) . "' " .
                        "WHERE epol.ep_directory_id = '" . (int)$this->config['directoryId'] . "' " .
                        " AND epol.local_orders_id = '" . $order->order_id . "'"
                    );
                }

                $message->info('[+] Order # '.$orders_id.'; Server status "'.$remoteOrderInfo->orders_status_name.'"');

//                try {
//                    $this->client->updateOrderAcknowledgment([$remote_orders_id]);
//                }catch (\Exception $ex){}

                if ( !$isOrderLocallyModified && in_array($remoteOrderInfo->order_status, $this->config['order']['server_dispatched_statuses']) ) {

                    tep_db_query(
                        "UPDATE ep_holbi_soap_link_orders ".
                        "SET track_remote_order=0 ".
                        "WHERE ep_directory_id='".(int)$this->config['directoryId']."' ".
                        " AND local_orders_id='".(int)$orders_id."' ".
                        " AND remote_orders_id='".(int)$remote_orders_id."' "
                    );

                    // {{
                    $comments = '';

                    //$oID = $order->order_id;
                    $status = $this->config['order']['local_dispatch_status']?$this->config['order']['local_dispatch_status']:$order->info['order_status'];
                    $customer_notified = ($status!=$order->info['order_status']) || !empty($comments);

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

                    /*
                    if ($sms = Acl::checkExtension('SMS', 'sendSMS')){
                        $response = $sms::sendSMS($oID, $commentid);
                        if (is_array($response) && count($response)){
                            $messages[] = ['message' => $response['message'], 'messageType' => $response['messageType']];
                        }
                    }
                    */

                    /*
                    if ($ext = Acl::checkExtension('ReferFriend', 'rf_release_reference')){
                        $ext::rf_release_reference((int)$oID);
                    }

                    if ($ext = Acl::checkExtension('CustomerLoyalty', 'afterOrderUpdate')){
                        $ext::afterOrderUpdate((int)$oID);
                    }
                    */

                    if (method_exists('\common\helpers\Coupon', 'credit_order_check_state')){
                        \common\helpers\Coupon::credit_order_check_state((int) $oID);
                    }
                    if ( $customer_notified ) {
                        $this->sendOrderStatusUpdateEmail($order, $status, $comments);
                    }
                    // }}
                }

                if ( !$isOrderLocallyModified ) {
                    tep_db_query(
                        "UPDATE ".TABLE_ORDERS." ".
                        "SET _api_order_time_modified=_api_order_time_modified, _api_order_time_processed=_api_order_time_modified ".
                        "WHERE orders_id='".$orders_id."' "
                    );
                }
            }
        }catch (\Exception $ex) {
            \Yii::error('Exception '.$ex->getMessage()."\n".$ex->getTraceAsString(),'datasource');
            $message->info('[!] ERROR order # '.$orders_id.'; Message is: '.$ex->getMessage());
            $this->isErrorOccurredDuringCheck = true;
        }
    }

    /**
     * @deprecated
     * @param Messages $message
     * @param $orders_id
     * @param $remote_orders_id
     */
    protected function trackRemotePurchaseOrder(Messages $message, $orders_id, $remote_orders_id)
    {
        global $languages_id;
        $message->info('[*] Check order # '.$orders_id.'; remote order #'.$remote_orders_id);

        $oID = $orders_id;
        try {
            $order = new \common\classes\Order($oID);
        }catch (\Exception $ex){
            $message->info('[!] Load local order error # '.$orders_id);
            return;
        }
        $admin_id = 0;

        $isOrderLocallyModified = false;
        if ( !empty($get_order_modify_state['_api_order_time_processed']) && $get_order_modify_state['_api_order_time_processed']!=$get_order_modify_state['_api_order_time_modified'] ) {
            $isOrderLocallyModified = true;
        }

        $platform_config = \Yii::$app->get('platform')->config($order->info['platform_id']);
        // {{
        $link = \Yii::$app->get('platform')->config()->getCatalogBaseUrl( true );
        \Yii::$app->getUrlManager()->setBaseUrl($link);
        // }}
        try{
            $remoteOrderInfo = false;

            $response = $this->client->getPurchaseOrder($remote_orders_id);

            if ($response && $response->status != 'ERROR') {
                $remoteOrderInfo = $response->order->info;
                $this->updateLocalOrderStatusHistory($message, $order, $response->order);
                $order = new \common\classes\Order($order->order_id);
            }

            if ( $remoteOrderInfo!==false  ) {
                if ( !$isOrderLocallyModified ) {
                    tep_db_query(
                        "UPDATE ep_holbi_soap_link_orders epol " .
                        " INNER JOIN " . TABLE_ORDERS . " o ON o.orders_id=epol.local_orders_id " .
                        "SET epol.local_order_last_modified=IFNULL(o.last_modified, o.date_purchased), " .
                        " epol.local_order_last_modified_hash='" . Helper::generateOrderHash($order->order_id) . "' " .
                        "WHERE epol.ep_directory_id = '" . (int)$this->config['directoryId'] . "' " .
                        " AND epol.local_orders_id = '" . $order->order_id . "'"
                    );
                }

                $message->info('[+] Order # '.$orders_id.'; Server status "'.$remoteOrderInfo->orders_status_name.'"');

                try {
                    $this->client->updateOrderAcknowledgment([$remote_orders_id]);
                }catch (\Exception $ex){}

                if ( !$isOrderLocallyModified && in_array($remoteOrderInfo->order_status, $this->config['order']['server_dispatched_statuses']) ) {

                    if ( !$this->useModifyTimeCheck ) {
                        tep_db_query(
                            "UPDATE ep_holbi_soap_link_orders " .
                            "SET track_remote_order=0 " .
                            "WHERE ep_directory_id='" . (int)$this->config['directoryId'] . "' " .
                            " AND local_orders_id='" . (int)$orders_id . "' " .
                            " AND remote_orders_id='" . (int)$remote_orders_id . "' "
                        );
                    }

                    // {{
                    $comments = '';

                    //$oID = $order->order_id;
                    $status = $this->config['order']['local_dispatch_status']?$this->config['order']['local_dispatch_status']:$order->info['order_status'];
                    $customer_notified = 0;

                    tep_db_query(
                        "update " . TABLE_ORDERS . " ".
                        "set orders_status = '" . tep_db_input($status) . "', last_modified = now() ".
                        "where orders_id = '" . (int) $oID . "'"
                    );

                    if ( $status!=$order->info['order_status'] ) {
                        tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, array(
                            'orders_id' => (int)$oID,
                            'orders_status_id' => (int)$status,
                            'date_added' => 'now()',
                            'customer_notified' => $customer_notified,
                            'comments' => $comments,
                            'admin_id' => 0,//$adminId,
                        ));
                        $commentid = tep_db_insert_id();

                        /*
                        if ($sms = Acl::checkExtension('SMS', 'sendSMS')){
                            $response = $sms::sendSMS($oID, $commentid);
                            if (is_array($response) && count($response)){
                                $messages[] = ['message' => $response['message'], 'messageType' => $response['messageType']];
                            }
                        }
                        */

                        /*
                        if ($ext = Acl::checkExtension('ReferFriend', 'rf_release_reference')){
                            $ext::rf_release_reference((int)$oID);
                        }

                        if ($ext = Acl::checkExtension('CustomerLoyalty', 'afterOrderUpdate')){
                            $ext::afterOrderUpdate((int)$oID);
                        }
                        */

                        if (method_exists('\common\helpers\Coupon', 'credit_order_check_state')){
                            \common\helpers\Coupon::credit_order_check_state((int) $oID);
                        }
                        if ($customer_notified) {
                            $this->sendOrderStatusUpdateEmail($order, $status, $comments);
                        }
                    }
                    // }}
                }

                if ( !$isOrderLocallyModified ) {
                    tep_db_query(
                        "UPDATE ".TABLE_ORDERS." ".
                        "SET _api_order_time_modified=_api_order_time_modified, _api_order_time_processed=_api_order_time_modified ".
                        "WHERE orders_id='".$orders_id."' "
                    );
                }
            }
        }catch (\Exception $ex) {
            \Yii::error('Exception '.$ex->getMessage()."\n".$ex->getTraceAsString(),'datasource');
            $message->info('[!] ERROR order # '.$orders_id.'; Message is: '.$ex->getMessage());
            $this->isErrorOccurredDuringCheck = true;
        }
    }

    protected function updateLocalOrderStatusHistory(Messages $message, $order, $orderData)
    {
        $orderData = json_decode(json_encode($orderData), true);

        $infoData = $orderData['info'];

        $orderId = $order->info['orders_id'];

        global $insert_id;
        $insert_id = $orderId;

        $order = new \common\classes\Order($orderId);

        $orderOriginal = clone $order;

        $order->info['last_modified'] = $orderOriginal->info['last_modified'];
        if ( isset($infoData['last_modified']) && $infoData['last_modified']>0 ) {
            $_last_modified = date('Y-m-d H:i:s', strtotime($infoData['last_modified']));
            if ( $_last_modified>$order->info['last_modified'] )
                $order->info['last_modified'] = $_last_modified;
        }

        $order->info['order_status'] = $orderOriginal->info['order_status'];
        if ( !empty($infoData['order_status']) ) {
            $OrderStatusIdFromServer = array_search($infoData['order_status'], $this->config['status_map_local_to_server']);
            if (!empty($OrderStatusIdFromServer)) {
                $order->info['order_status'] = $OrderStatusIdFromServer;
            }
        }

        $new_tracking_codes = [];
        $missing_history_new_tracking_codes = [];

        $update_status_history = [];
        if ( isset($orderData['status_history_array']) ){
            $orderData['status_history_array'] = json_decode(json_encode($orderData['status_history_array']),true);
        }
        if ( isset($orderData['status_history_array']) && is_array($orderData['status_history_array']) && isset($orderData['status_history_array']['status_history']) ) {
            $status_history = ArrayHelper::isIndexed($orderData['status_history_array']['status_history'])?$orderData['status_history_array']['status_history']:[$orderData['status_history_array']['status_history']];
            foreach ( $status_history as $idx=>$history_row ) {
                if (!isset($history_row['orders_status_id']) || empty($history_row['orders_status_id']) || array_search($history_row['orders_status_id'],$this->config['status_map_local_to_server'])===false ) {
                    unset($status_history[$idx]);
                    continue;
                }
                $status_history[$idx]['orders_status_id'] = array_search($history_row['orders_status_id'], $this->config['status_map_local_to_server']);
                $status_history[$idx]['comments'] = isset($history_row['comments'])?trim($history_row['comments']):'';
                if ( isset($history_row['date_added']) && $history_row['date_added']>0 ) {
                    $status_history[$idx]['date_added'] = date('Y-m-d H:i:s', strtotime($history_row['date_added']));
                }
            }
            $update_status_history = array_values($status_history);
        }

        if ( count($update_status_history)>0 ) {
            // {{ match status history by date, then status and comment
            $db_status_history = [];
            $get_status_history_r = tep_db_query(
                "SELECT * ".
                "FROM ".TABLE_ORDERS_STATUS_HISTORY." ".
                "WHERE orders_id='".(int)$insert_id."' ".
                "ORDER BY date_added, orders_status_history_id"
            );
            $db_pk_first_id = 0;
            $max_order_status_date = '';
            if ( tep_db_num_rows($get_status_history_r)>0 ) {
                while($db_history = tep_db_fetch_array($get_status_history_r)) {
                    if ( empty($db_pk_first_id) ) $db_pk_first_id = $db_history['orders_status_history_id'];
                    $db_status_history[$db_history['orders_status_history_id']] = $db_history;
                    if ( $db_history['date_added'] > $max_order_status_date ){
                        $max_order_status_date = $db_history['date_added'];
                    }
                }
            }
            // new tracking codes mail & store formatted tracking for skip mail status history
            $tracking_codes_track = [];
            foreach ($new_tracking_codes as $_t_idx=>$new_tracking_code) {
                $tracking_data = \common\helpers\Order::parse_tracking_number($new_tracking_code);
                $carrier = isset($tracking_data['carrier']) ? ($tracking_data['carrier'] . ' ') : '';
                $tracking_codes_track[$_t_idx] = ': ' . $carrier . $tracking_data['number'];
            }

            foreach ( $update_status_history as $income_history ){
                if ( !isset($income_history['orders_status_id']) || empty($income_history['orders_status_id']) ) continue;
                if ( !isset($income_history['date_added']) || empty($income_history['date_added']) ) continue;

                $skip_income_history = false;
                foreach ( $db_status_history as $pk_idx=>$db_comment ){
                    if ( $db_comment['date_added']!=$income_history['date_added'] ) continue;
                    if ( $db_pk_first_id==$pk_idx ) {
                        // comment
                        if ( trim($db_comment['comments'])==$income_history['comments'] ) {
                            $skip_income_history = true;
                            unset($db_status_history[$pk_idx]);
                            break;
                        }
                    }else{
                        if ( $income_history['orders_status_id']==$db_comment['orders_status_id'] && trim($db_comment['comments'])==$income_history['comments'] ) {
                            $skip_income_history = true;
                            unset($db_status_history[$pk_idx]);
                            break;
                        }
                    }
                }

                if ( $skip_income_history ) continue;

                /*foreach ($missing_history_new_tracking_codes as $idx=>$missing_history_new_tracking_code) {
                    if ( strpos((string)$income_history['comments'],$missing_history_new_tracking_code)!==false )
                        unset($missing_history_new_tracking_codes[$idx]);
                }*/

                $notify_flag = (isset($income_history['customer_notified']) && $income_history['customer_notified'])?1:0;
                tep_db_perform(TABLE_ORDERS_STATUS_HISTORY,[
                    'orders_id' => $insert_id,
                    'orders_status_id' => $income_history['orders_status_id'],
                    'date_added' => $income_history['date_added'],
                    'customer_notified' => $notify_flag,
                    'comments' => $income_history['comments'],
                ]);

                if ( $notify_flag ) {
                    $skip_send_comment = false;
                    if ( !empty($income_history['comments']) ) {
                        foreach($tracking_codes_track as $_track_idx=>$track_pattern) {
                            if ( strpos($income_history['comments'], $track_pattern)!==false && isset($missing_history_new_tracking_codes[$_track_idx]) ) {
                                $this->sendTrackingNumberEmail($order,[$missing_history_new_tracking_codes[$_track_idx]]);
                                $skip_send_comment = true;
                                unset($missing_history_new_tracking_codes[$_track_idx]);
                                unset($tracking_codes_track[$_track_idx]);
                                break;
                            }
                        }
                    }
                    if ( !$skip_send_comment ) {
                        $this->sendOrderStatusUpdateEmail($order, $income_history['orders_status_id'], $income_history['comments']);
                    }
                }

                if ( $income_history['date_added']>$max_order_status_date ){
                    tep_db_query(
                        "UPDATE ".TABLE_ORDERS." ".
                        "SET orders_status='".(int)$income_history['orders_status_id']."', last_modified='".tep_db_input($income_history['date_added'])."' ".
                        "WHERE orders_id = '".(int)$insert_id."'"
                    );
                    $order->info['order_status'] = (int)$income_history['orders_status_id'];
                }
            }
        }
        // }} status history

        $history_last_order_status = tep_db_fetch_array(tep_db_query(
            "SELECT orders_status_id ".
            "FROM ".TABLE_ORDERS_STATUS_HISTORY." ".
            "WHERE orders_id = '".(int)$insert_id."' ".
            "ORDER BY date_added DESC, orders_status_history_id DESC ".
            "LIMIT 1"
        ));

        if ( $history_last_order_status['orders_status_id'] ) {
            $order->info['order_status'] = $history_last_order_status['orders_status_id'];
            tep_db_query(
                "UPDATE " . TABLE_ORDERS . " " .
                "SET orders_status='" . (int)$order->info['order_status'] . "' " .
                "WHERE orders_id = '" . (int)$insert_id . "'"
            );
        }

    }

    /**
     * Update full order info including products and address
     * @deprecated
     * @param Messages $message
     * @param $order
     * @param $orderData
     */
    protected function updateLocalOrder(Messages $message, $order, $orderData)
    {
        $orderData = json_decode(json_encode($orderData),true);

        $tools = new Tools();

        $orderId = $order->info['orders_id'];

        global $cart, $order_total_modules, $order;
        if ( !is_object($cart) ) {
            $cart = new \common\classes\shopping_cart();
        }
        $cart->reset(true);

        $order = new \common\classes\Order($orderId);

        $orderOriginal = clone $order;

        // check customer id
        if ( isset($orderData['customer']['customer_id']) && (int)$orderData['customer']['customer_id']>0 ) {
            $remote_customer_id = $orderData['customer']['customer_id'];
            $get_customer_link_r = tep_db_query(
                "SELECT lc.local_customers_id ".
                "FROM ep_holbi_soap_link_customers lc ".
                " INNER JOIN ".TABLE_CUSTOMERS." c ON c.customers_id=lc.local_customers_id ".
                "WHERE lc.ep_directory_id='".$this->config['directoryId']."' AND lc.remote_customers_id='".(int)$remote_customer_id."' "
            );
            if ( tep_db_num_rows($get_customer_link_r)>0 ) {
                $get_customer_link = tep_db_fetch_array($get_customer_link_r);
                if ( $get_customer_link['local_customers_id']!=$order->customer['customer_id'] ) {
                    $order->customer['customer_id'] = $get_customer_link['local_customers_id'];
                    $order->customer['id'] = $get_customer_link['local_customers_id'];
                    \Yii::warning('HolbiSoap['.$this->config['directoryId'].']::TrackOrders - Customer ID in order #'.$orderId.' CHANGED '.$orderOriginal->customer['customer_id'].'=>'.$order->customer['customer_id'],'datasource');
                }
            }
        }

        foreach (['customer', 'billing', 'delivery'] as $orderABKey) {

            if (isset($orderData[$orderABKey]['country_iso2'])) {
                $countryId = $tools->getCountryId($orderData[$orderABKey]['country_iso2']);
                $orderData[$orderABKey]['country_id'] = $countryId;

                $country_info = \common\helpers\Country::get_country_info_by_id($countryId);
                $orderData[$orderABKey]['country'] = [
                    'id' => $countryId,
                    'title' => $country_info['countries_name'],
                    'iso_code_2' => $country_info['countries_iso_code_2'],
                    'iso_code_3' => $country_info['countries_iso_code_3'],
                ];

                $orderData[$orderABKey]['format_id'] = \common\helpers\Address::get_address_format_id($countryId);
            }
            if (!empty($orderData[$orderABKey]['country_id']) && !empty($orderData[$orderABKey]['state'])) {
                $orderData[$orderABKey]['zone_id'] = \common\helpers\Zones::get_zone_id($orderData[$orderABKey]['country_id'],$orderData[$orderABKey]['state']);
            }


            foreach (array_keys($order->{$orderABKey}) as $key) {
                if ( $orderABKey=='customer' && ($key=='customer_id' || $key=='id') ) continue;
                $order->{$orderABKey}[$key] = isset($orderData[$orderABKey][$key]) ? $orderData[$orderABKey][$key] : null;
            }
            $order->{$orderABKey}['address_book_id'] = $tools->addressBookFind($order->customer['customer_id'], $order->{$orderABKey});
        }


        $order->products = [];

        if (isset($orderData['products']) && isset($orderData['products']['product'])) {
            $products = $orderData['products']['product'];
            if (!is_array($products) || !ArrayHelper::isIndexed($products)) $products = [$products];
            foreach ($products as $product) {
                $this->addProductToOrder($message, $product, $order);
            }
        }

        $order->totals = [];
        if (isset($orderData['totals']) && isset($orderData['totals']['total'])) {
            $totals = (array)$orderData['totals']['total'];
            if (!ArrayHelper::isIndexed($totals)) $totals = [$totals];
            foreach ($totals as $total) {
                $total = (array)$total;
                $total['class'] = $total['code'];
                $order->totals[] = $total;
            }
        }

        $infoData = isset($orderData['info']) ? (array)$orderData['info'] : [];
        if ( isset($infoData['language']) ) {
            $infoData['language_id'] = \common\classes\language::get_id($infoData['language']);
        }
        if ( isset($infoData['sap_export_date']) && (int)$infoData['sap_export_date']>0) {
            $infoData['sap_export_date'] = date('Y-m-d H:i:s', strtotime($infoData['sap_export_date']));
        }
        foreach (array_keys($order->info) as $key) {
            if ( $key=='tracking_number' ) {
                if (isset($orderOriginal->info['tracking_number']) && !empty($orderOriginal->info['tracking_number'])) {
                    //$infoData[$key] = array_unique(array_merge($orderOriginal->info['tracking_number'], is_array($infoData[$key])?$infoData[$key]:[]));
                }
            }
            $order->info[$key] = isset($infoData[$key]) ? $infoData[$key] : $orderOriginal->info[$key];
        }
        $order->info['orders_id'] = $orderOriginal->info['orders_id'];

        if ( empty($order->info['language_id']) ) {
            $order->info['language_id'] = \common\classes\language::defaultId();
        }

        global $order_delivery_date;
        if ( isset($infoData['delivery_date']) ) {
            if ( $infoData['delivery_date']>0 ) {
                $order->info['delivery_date'] = date('Y-m-d', strtotime($infoData['delivery_date']));
                $order_delivery_date = $order->info['delivery_date'];
            }
        }else{
            $order_delivery_date = $orderOriginal->info['delivery_date'];
        }

        if ( isset($infoData['date_purchased']) && $infoData['date_purchased']>0 ) {
            $order->info['date_purchased'] = date('Y-m-d H:i:s', strtotime($infoData['date_purchased']));
        }
        $order->info['last_modified'] = $orderOriginal->info['last_modified'];
        if ( isset($infoData['last_modified']) && $infoData['last_modified']>0 ) {
            $_last_modified = date('Y-m-d H:i:s', strtotime($infoData['last_modified']));
            if ( $_last_modified>$order->info['last_modified'] )
                $order->info['last_modified'] = $_last_modified;
        }

        $order->info['order_status'] = $orderOriginal->info['order_status'];
        if ( !empty($infoData['order_status']) ) {
            $OrderStatusIdFromServer = array_search($infoData['order_status'], $this->config['status_map_local_to_server']);
            if (!empty($OrderStatusIdFromServer)) {
                $order->info['order_status'] = $OrderStatusIdFromServer;
            }
        }

        $update_status_history = [];
        if ( isset($orderData['status_history_array']) ){
            $orderData['status_history_array'] = json_decode(json_encode($orderData['status_history_array']),true);
        }
        if ( isset($orderData['status_history_array']) && is_array($orderData['status_history_array']) && isset($orderData['status_history_array']['status_history']) ) {
            $status_history = ArrayHelper::isIndexed($orderData['status_history_array']['status_history'])?$orderData['status_history_array']['status_history']:[$orderData['status_history_array']['status_history']];
            foreach ( $status_history as $idx=>$history_row ) {
                if (!isset($history_row['orders_status_id']) || empty($history_row['orders_status_id']) || array_search($history_row['orders_status_id'],$this->config['status_map_local_to_server'])===false ) {
                    unset($status_history[$idx]);
                    continue;
                }
                $status_history[$idx]['orders_status_id'] = array_search($history_row['orders_status_id'], $this->config['status_map_local_to_server']);
                $status_history[$idx]['comments'] = isset($history_row['comments'])?trim($history_row['comments']):'';
                if ( isset($history_row['date_added']) && $history_row['date_added']>0 ) {
                    $status_history[$idx]['date_added'] = date('Y-m-d H:i:s', strtotime($history_row['date_added']));
                }
            }
            $update_status_history = array_values($status_history);
        }

        $new_tracking_codes = [];
        foreach( $order->info['tracking_number'] as $new_tracking_code ) {
            if ( in_array($new_tracking_code, $orderOriginal->info['tracking_number']) ) continue;
            $new_tracking_codes[] = $new_tracking_code;
        }
        $missing_history_new_tracking_codes = $new_tracking_codes;

        $insert_id = $order->save_order($orderId);
        $order->info['orders_id'] = $insert_id;
        if ( $insert_id ) {
            $post_patch_order = [];
            if ( is_array($order->info['tracking_number']) ) {
                $post_patch_order['tracking_number'] = implode(';', $order->info['tracking_number']);
            }
            if (!empty($order->info['sap_order_id'])){
                $post_patch_order['sap_order_id'] = $order->info['sap_order_id'];
            }
            if ($order->info['sap_export_date']>1000){
                $post_patch_order['sap_export_date'] = $order->info['sap_export_date'];
            }
            if ( isset($infoData['sap_export']) && is_numeric($infoData['sap_export']) ) {
                $post_patch_order['sap_export'] = $infoData['sap_export'];
            }
            if ( isset($infoData['delivery_date']) && $infoData['delivery_date']>1000 ) {
                $post_patch_order['delivery_date'] = $infoData['delivery_date'];
            }

            if ( count($post_patch_order)>0 ) {
                tep_db_perform(TABLE_ORDERS, $post_patch_order, 'update', "orders_id='" . (int)$insert_id . "'");
            }

            if ( class_exists('\common\helpers\SapCommon') ) {
                tep_db_query("DELETE FROM ep_sap_order_issues WHERE orders_id='".(int)$insert_id."'");
                if ( isset($infoData['sap_export_issues']) && is_array($infoData['sap_export_issues']) ) {
                    foreach ($infoData['sap_export_issues'] as $line) {
                        list($issue_date, $issue_message) = explode(';',$line, 2);
                        $issue_date = date('Y-m-d H:i:s', strtotime($issue_date));
                        tep_db_perform('ep_sap_order_issues', [
                            'orders_id' => (int)$insert_id,
                            'date_added' => $issue_date,
                            'issue_text' => $issue_message,
                        ]);
                    }
                }
            }

            $order_totals = $order->totals;

            // {{ match totals by ot_class
            foreach ( array_keys($order_totals) as $i ) {
                $sql_data_array = array(
                    'orders_id' => $insert_id,
                    'title' => $order_totals[$i]['title'],
                    'text' => $order_totals[$i]['text'],
                    'value' => $order_totals[$i]['value'],
                    'class' => $order_totals[$i]['code'],
                    'sort_order' => $order_totals[$i]['sort_order'],
                    'text_exc_tax' => $order_totals[$i]['text_exc_tax'],
                    'text_inc_tax' => $order_totals[$i]['text_inc_tax'],
                    'tax_class_id' => $order_totals[$i]['tax_class_id'],
                    'value_exc_vat' => $order_totals[$i]['value_exc_vat'],
                    'value_inc_tax' => $order_totals[$i]['value_inc_tax'],
                    'is_removed' => 0,
                    'currency' => $order->info['currency'],
                    'currency_value' => $order->info['currency_value'],
                );
                $order_totals[$i]['sql_array'] = $sql_data_array;
            }
            $get_order_total_r = tep_db_query("SELECT * FROM ".TABLE_ORDERS_TOTAL." WHERE orders_id='".(int)$insert_id."' order by sort_order");
            if ( tep_db_num_rows($get_order_total_r)>0 ) {
                while( $db_order_total = tep_db_fetch_array($get_order_total_r) ){
                    $db_row_processed = false;
                    foreach( array_keys($order_totals) as $idx ) {
                        if ( isset($order_totals[$idx]['processed']) ) continue;

                        if ( $order_totals[$idx]['code']==$db_order_total['class'] ) {
                            $order_totals[$idx]['processed'] = 'update';

                            tep_db_perform(TABLE_ORDERS_TOTAL,$order_totals[$idx]['sql_array'],'update',"orders_total_id='".$db_order_total['orders_total_id']."'");

                            $db_row_processed = true;
                            break;
                        }
                    }
                    if ( !$db_row_processed ) {
                        tep_db_query("DELETE FROM ".TABLE_ORDERS_TOTAL." WHERE orders_total_id='".$db_order_total['orders_total_id']."'");
                    }
                }
            }
            foreach( array_keys($order_totals) as $idx ) {
                if ( isset($order_totals[$idx]['processed']) ) continue;
                tep_db_perform(TABLE_ORDERS_TOTAL, $order_totals[$idx]['sql_array']);
            }
            // }} totals

            if ( count($update_status_history)>0 ) {
                // {{ match status history by date, then status and comment
                $db_status_history = [];
                $get_status_history_r = tep_db_query(
                    "SELECT * ".
                    "FROM ".TABLE_ORDERS_STATUS_HISTORY." ".
                    "WHERE orders_id='".(int)$insert_id."' ".
                    "ORDER BY date_added, orders_status_history_id"
                );
                $db_pk_first_id = 0;
                $max_order_status_date = '';
                if ( tep_db_num_rows($get_status_history_r)>0 ) {
                    while($db_history = tep_db_fetch_array($get_status_history_r)) {
                        if ( empty($db_pk_first_id) ) $db_pk_first_id = $db_history['orders_status_history_id'];
                        $db_status_history[$db_history['orders_status_history_id']] = $db_history;
                        if ( $db_history['date_added'] > $max_order_status_date ){
                            $max_order_status_date = $db_history['date_added'];
                        }
                    }
                }
                // new tracking codes mail & store formatted tracking for skip mail status history
                $tracking_codes_track = [];
                foreach ($new_tracking_codes as $_t_idx=>$new_tracking_code) {
                    $tracking_data = \common\helpers\Order::parse_tracking_number($new_tracking_code);
                    $carrier = isset($tracking_data['carrier']) ? ($tracking_data['carrier'] . ' ') : '';
                    $tracking_codes_track[$_t_idx] = ': ' . $carrier . $tracking_data['number'];
                }

                foreach ( $update_status_history as $income_history ){
                    if ( !isset($income_history['orders_status_id']) || empty($income_history['orders_status_id']) ) continue;
                    if ( !isset($income_history['date_added']) || empty($income_history['date_added']) ) continue;

                    $skip_income_history = false;
                    foreach ( $db_status_history as $pk_idx=>$db_comment ){
                        if ( $db_comment['date_added']!=$income_history['date_added'] ) continue;
                        if ( $db_pk_first_id==$pk_idx ) {
                            // comment
                            if ( trim($db_comment['comments'])==$income_history['comments'] ) {
                                $skip_income_history = true;
                                unset($db_status_history[$pk_idx]);
                                break;
                            }
                        }else{
                            if ( $income_history['orders_status_id']==$db_comment['orders_status_id'] && trim($db_comment['comments'])==$income_history['comments'] ) {
                                $skip_income_history = true;
                                unset($db_status_history[$pk_idx]);
                                break;
                            }
                        }
                    }

                    if ( $skip_income_history ) continue;

                    /*foreach ($missing_history_new_tracking_codes as $idx=>$missing_history_new_tracking_code) {
                        if ( strpos((string)$income_history['comments'],$missing_history_new_tracking_code)!==false )
                            unset($missing_history_new_tracking_codes[$idx]);
                    }*/

                    $notify_flag = (isset($income_history['customer_notified']) && $income_history['customer_notified'])?1:0;
                    tep_db_perform(TABLE_ORDERS_STATUS_HISTORY,[
                        'orders_id' => $insert_id,
                        'orders_status_id' => $income_history['orders_status_id'],
                        'date_added' => $income_history['date_added'],
                        'customer_notified' => $notify_flag,
                        'comments' => $income_history['comments'],
                    ]);

                    if ( $notify_flag ) {
                        $skip_send_comment = false;
                        if ( !empty($income_history['comments']) ) {
                            foreach($tracking_codes_track as $_track_idx=>$track_pattern) {
                                if ( strpos($income_history['comments'], $track_pattern)!==false && isset($missing_history_new_tracking_codes[$_track_idx]) ) {
                                    $this->sendTrackingNumberEmail($order,[$missing_history_new_tracking_codes[$_track_idx]]);
                                    $skip_send_comment = true;
                                    unset($missing_history_new_tracking_codes[$_track_idx]);
                                    unset($tracking_codes_track[$_track_idx]);
                                    break;
                                }
                            }
                        }
                        if ( !$skip_send_comment ) {
                            $this->sendOrderStatusUpdateEmail($order, $income_history['orders_status_id'], $income_history['comments']);
                        }
                    }

                    if ( $income_history['date_added']>$max_order_status_date ){
                        tep_db_query(
                            "UPDATE ".TABLE_ORDERS." ".
                            "SET orders_status='".(int)$income_history['orders_status_id']."', last_modified='".tep_db_input($income_history['date_added'])."' ".
                            "WHERE orders_id = '".(int)$insert_id."'"
                        );
                        $order->info['order_status'] = (int)$income_history['orders_status_id'];
                    }
                }
            }
            // }} status history
            $history_last_order_status = tep_db_fetch_array(tep_db_query(
                "SELECT orders_status_id ".
                "FROM ".TABLE_ORDERS_STATUS_HISTORY." ".
                "WHERE orders_id = '".(int)$insert_id."' ".
                "ORDER BY date_added DESC, orders_status_history_id DESC ".
                "LIMIT 1"
            ));
            if ( $history_last_order_status['orders_status_id'] ) {
                $order->info['order_status'] = $history_last_order_status['orders_status_id'];
                tep_db_query(
                    "UPDATE " . TABLE_ORDERS . " " .
                    "SET orders_status='" . (int)$order->info['order_status'] . "' " .
                    "WHERE orders_id = '" . (int)$insert_id . "'"
                );
            }

            $order->save_products(false);
        }

        if ( count($missing_history_new_tracking_codes)>0 ) {
            $notify_comments = $this->sendTrackingNumberEmail($order, $missing_history_new_tracking_codes);
            tep_db_query(
                "insert into " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments, admin_id) ".
                "values ".
                "('" . (int)$orderId . "', '" . tep_db_input($order->info['order_status']) . "', now(), '1', '" . tep_db_input($notify_comments) . "', '" . tep_db_input(isset($admin_id)?$admin_id:0) . "')"
            );
        }

        $order = new \common\classes\Order($orderId);

    }

    /**
     * Only tracking update ...
     * @deprecated
     * @param Messages $message
     * @param $order
     * @param $remoteOrderInfo
     */
    protected function updateLocalOrderPartial(Messages $message, $order, $remoteOrderInfo)
    {
        $oID = $order->info['orders_id'];
        $orders_id = $order->info['orders_id'];

        $remoteOrderInfo->tracking_number = is_array($remoteOrderInfo->tracking_number)?$remoteOrderInfo->tracking_number:( empty($remoteOrderInfo->tracking_number)?[]:explode(';',$remoteOrderInfo->tracking_number) );

        $new_tracking_codes = [];
        if ( !is_array($order->info['tracking_number']) ) {
            $order->info['tracking_number'] = empty($order->info['tracking_number'])?[]:explode(';',$order->info['tracking_number']);
        }

        $existing_tracking = array_flip($order->info['tracking_number']);
        foreach( $remoteOrderInfo->tracking_number as $remote_tracking_code ) {
            if ( !isset($existing_tracking[$remote_tracking_code]) ) {
                $new_tracking_codes[] = $remote_tracking_code;
                $order->info['tracking_number'][] = $remote_tracking_code;
            }
        }
        $tracking_number = implode(';',$order->info['tracking_number']);

        if ( isset($remoteOrderInfo['sap_export_date']) && (int)$remoteOrderInfo['sap_export_date']>0) {
            $remoteOrderInfo['sap_export_date'] = date('Y-m-d H:i:s', strtotime($remoteOrderInfo['sap_export_date']));
        }

        $sql_data_array = array();

        if (!empty($tracking_number) && count($new_tracking_codes)>0) {
            $sql_data_array['tracking_number'] = $tracking_number;
        }
        if ( !empty($order->info['sap_order_id']) ) {
            $sql_data_array['sap_order_id'] = $order->info['sap_order_id'];
        }
        if ( $order->info['sap_export_date']>1000 ) {
            $sql_data_array['sap_export_date'] = $order->info['sap_export_date'];
        }
        if ( isset($remoteOrderInfo->sap_export) && is_numeric($remoteOrderInfo->sap_export) ) {
            $post_patch_order['sap_export'] = (int)$remoteOrderInfo->sap_export;
        }
        if ( isset($remoteOrderInfo->sap_export_mode) && !empty($remoteOrderInfo->sap_export_mode) ) {
            $post_patch_order['sap_export_mode'] = $remoteOrderInfo->sap_export_mode;
        }

        if ( count($sql_data_array)>0 && !$this->allow_update_order ) {
            tep_db_perform(TABLE_ORDERS, $sql_data_array, 'update', "orders_id = '" . (int)$oID . "'");
        }
        if ( !$this->allow_update_order && class_exists('\common\helpers\SapCommon') ) {
            tep_db_query("DELETE FROM ep_sap_order_issues WHERE orders_id='" . (int)$oID . "'");
            if (isset($remoteOrderInfo->sap_export_issues) && is_array($remoteOrderInfo->sap_export_issues)) {
                foreach ($remoteOrderInfo->sap_export_issues as $line) {
                    list($issue_date, $issue_message) = explode(';', $line, 2);
                    $issue_date = date('Y-m-d H:i:s', strtotime($issue_date));
                    tep_db_perform('ep_sap_order_issues', [
                        'orders_id' => (int)$oID,
                        'date_added' => $issue_date,
                        'issue_text' => $issue_message,
                    ]);
                }
            }
        }


        if (!empty($tracking_number) && count($new_tracking_codes)>0) {

            foreach( $new_tracking_codes as $new_tracking_code ) {
                $notify_comments = $this->sendTrackingNumberEmail($order, [$new_tracking_code]);

                if (!$this->allow_update_order) {
                    tep_db_query("insert into " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments, admin_id) values ('" . (int)$oID . "', '" . tep_db_input($order->info['order_status']) . "', now(), '1', '" . tep_db_input($notify_comments) . "', '" . tep_db_input(isset($admin_id) ? $admin_id : 0) . "')");
                }
            }

            $message->info('[+] Order # '.$orders_id.'; tracking updated "'.$tracking_number.'"');
        }
    }

    protected function sendTrackingNumberEmail($order, $new_tracking_codes)
    {
        $oID = $order->info['orders_id'];
        $platform_config = \Yii::$app->get('platform')->config();
        $link = $platform_config->getCatalogBaseUrl( true );
        // {{
        $orderViewLink = $link.'account/history-info?order_id=' . $oID;
        //$qrCodeLink = tep_catalog_href_link('account/order-qrcode', 'oID=' . (int) $oID . '&cID=' . (int) $order->customer['customer_id'] . '&tracking=1', 'SSL')
        $qrCodeLink = $link.'account/order-qrcode?oID=' . (int) $oID . '&cID=' . (int) $order->customer['customer_id'] . '&tracking=1';
        // }}

        $notify_comments = '';
        foreach ($new_tracking_codes as $new_tracking_code) {
            $notify_comments_mail = '';

            $tracking_data = \common\helpers\Order::parse_tracking_number($new_tracking_code);
            $carrier = isset($tracking_data['carrier'])?($tracking_data['carrier'].' '):'';

            $notify_comments .= TEXT_TRACKING_NUMBER . ': '.$carrier . $tracking_data['number']."\n";
            $notify_comments_mail .=
                TEXT_TRACKING_NUMBER . ': '.$carrier . $tracking_data['number'] . "\n" .
                '<a href="' . $tracking_data['url'] . '" target="_blank">'.
                '<img border="0"'.
                ' alt="' . \common\helpers\Output::output_string($tracking_data['number']) . '"'.
                ' src="' . $qrCodeLink .'&tracking_number='.urlencode($new_tracking_code). '"'.
                '>'.
                '</a>' ."\n";

            $STORE_NAME = $platform_config->const_value('STORE_NAME');
            $STORE_OWNER_EMAIL_ADDRESS = $platform_config->const_value('STORE_OWNER_EMAIL_ADDRESS');
            $STORE_OWNER = $platform_config->const_value('STORE_OWNER');

            $eMail_store = $STORE_NAME;
            $eMail_address = $STORE_OWNER_EMAIL_ADDRESS;
            $eMail_store_owner = $STORE_OWNER;

            $email = $eMail_store . "<br>" .
                EMAIL_SEPARATOR . "<br>" .
                EMAIL_TEXT_ORDER_NUMBER . ' ' . $oID . "<br>" .
                EMAIL_TEXT_INVOICE_URL . ' ' . \common\helpers\Output::get_clickable_link($orderViewLink) . "<br>" .
                EMAIL_TEXT_DATE_ORDERED . ' ' . \common\helpers\Date::date_long($order->info['date_purchased']) . "<br><br>" .
                $notify_comments_mail . "<br>";

            \common\helpers\Mail::send($order->customer['name'], $order->customer['email_address'], EMAIL_TEXT_SUBJECT, $email, $STORE_OWNER, $eMail_address);

        }

        return $notify_comments;
    }

    private function lookupLocalProductId($remoteId)
    {
        $localId = 0;

        $get_local_id_r = tep_db_query(
            "SELECT local_products_id ".
            "FROM ep_holbi_soap_link_products ".
            "WHERE ep_directory_id='".(int)$this->config['directoryId']."' ".
            " AND remote_products_id='".(int)$remoteId."'"
        );
        if ( tep_db_num_rows($get_local_id_r)>0 ) {
            $_local_id = tep_db_fetch_array($get_local_id_r);
            tep_db_free_result($get_local_id_r);
            $localId = $_local_id['local_products_id'];
            if ( strpos($remoteId,'{')!==false && preg_match_all('/\{(\d+)\}(\d+)/',$remoteId,$remoteAttr) ){
                foreach ( $remoteAttr[1] as $_idx=>$remoteOptId ) {
                    $remoteValId = $remoteAttr[2][$_idx];

                    $localOptId = $this->lookupLocalProductOptionId($remoteOptId);
                    $localValId = $this->lookupLocalProductOptionValueId($remoteValId);

                    if ( $localOptId && $localValId ) {
                        $localId .= '{'.$localOptId.'}'.$localValId;
                    }else{
                        $localId = (int)$localId;
                        break;
                    }
                }
            }
        }

        return \common\helpers\Inventory::normalize_id($localId);
    }

    private function lookupLocalProductOptionId($remoteOptId)
    {
        $localOptId = 0;
        $getMapping_r = tep_db_query(
            "SELECT m.local_id, po.products_options_id " .
            "FROM ep_holbi_soap_mapping m " .
            " INNER JOIN ".TABLE_PRODUCTS_OPTIONS." po ON po.products_options_id=m.local_id ".
            "WHERE m.ep_directory_id='" . intval($this->config['directoryId']) . "' AND m.mapping_type='attr_option' " .
            " AND m.remote_id='" . $remoteOptId . "' ".
            "LIMIT 1"
        );
        if ( tep_db_num_rows($getMapping_r)>0 ) {
            $getMapping = tep_db_fetch_array($getMapping_r);
            $localOptId = $getMapping['products_options_id'];
        }
        return $localOptId;
    }

    private function lookupLocalProductOptionValueId($remoteValId)
    {
        $localValId = 0;
        $getMapping_r = tep_db_query(
            "SELECT m.local_id, pov.products_options_values_id " .
            "FROM ep_holbi_soap_mapping m " .
            " INNER JOIN ".TABLE_PRODUCTS_OPTIONS_VALUES." pov ON pov.products_options_values_id=m.local_id ".
            "WHERE m.ep_directory_id='" . intval($this->config['directoryId']) . "' AND m.mapping_type='attr_option_value' " .
            " AND m.remote_id='" . $remoteValId . "' ".
            "LIMIT 1"
        );
        if ( tep_db_num_rows($getMapping_r)>0 ) {
            $getMapping = tep_db_fetch_array($getMapping_r);
            $localValId = $getMapping['products_options_values_id'];
        }
        return $localValId;
    }

    private function addProductToOrder(Messages $message, $product, $order)
    {
        $orderId = $order->getOrderId();

        $product = (array)$product;
        $localProductId = $this->lookupLocalProductId($product['id']);
        if ( $product['id'] && empty($localProductId) ){
            $message->info(' [!] Order '.(empty($orderId)?'create':$orderId).' Product #'.$product['id'].' "'.$product['model'].'" not found');
            $product['id'] = 0;
        }else{
            $product['id'] = $localProductId;
        }
        $ordered_attributes = [];
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
        }
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
            'packs' => (int)$product['packs'],
            'units'=> (int)$product['units'],
            'packagings' => (int)$product['packagings'],
            'packs_price' => $product['packs_price'],
            'units_price'=> $product['units_price'],
            'packagings_price' => $product['packagings_price'],
        ];

    }

    private function sendOrderStatusUpdateEmail($order, $status, $comments)
    {
        global $languages_id;

        $oID = $order->info['orders_id'];

        $platform_config = \Yii::$app->get('platform')->config();
        $link = $platform_config->getCatalogBaseUrl( true );

        $orders_status_array = array();
        $orders_status_query = tep_db_query("select orders_status_id, orders_status_name from " . TABLE_ORDERS_STATUS . " where language_id = '" . (int) $languages_id . "'");
        while ($orders_status = tep_db_fetch_array($orders_status_query)) {
            $orders_status_array[$orders_status['orders_status_id']] = $orders_status['orders_status_name'];
        }

        $notify_comments = '';
        if ($comments) {
            $notify_comments = trim(sprintf(EMAIL_TEXT_COMMENTS_UPDATE, $comments)) . "\n\n";
        }

        $eMail_store = $platform_config->const_value('STORE_NAME');
        $eMail_address = $platform_config->const_value('STORE_OWNER_EMAIL_ADDRESS');
        $eMail_store_owner = $platform_config->const_value('STORE_OWNER');

        // {{
        $email_params = array();
        $email_params['STORE_NAME'] = $eMail_store;
        $email_params['ORDER_NUMBER'] = $oID;
        //$email_params['ORDER_INVOICE_URL'] = \common\helpers\Output::get_clickable_link(tep_catalog_href_link('account/historyinfo', 'order_id=' . $oID, 'SSL'/* , $store['store_url'] */));
        $email_params['ORDER_INVOICE_URL'] = \common\helpers\Output::get_clickable_link($link.'account/historyinfo?order_id=' . $oID);
        $email_params['ORDER_DATE_LONG'] = \common\helpers\Date::date_long($order->info['date_purchased']);
        $email_params['ORDER_COMMENTS'] = $notify_comments;
        $email_params['NEW_ORDER_STATUS'] = $orders_status_array[$status];
        $email_params['PO_NUMBER'] = (empty($order->info['purchase_order'])?'':((defined('TEXT_PURCHASE_ORDER')?TEXT_PURCHASE_ORDER:'PO#').': '.$order->info['purchase_order']));

        $emailTemplate = '';
        $ostatus = tep_db_fetch_array(tep_db_query("select orders_status_template from " . TABLE_ORDERS_STATUS . " where language_id = '" . (int) $languages_id . "' and orders_status_id='" . (int) $status . "'"));
        if (!empty($ostatus['orders_status_template'])) {
            $get_template_r = tep_db_query("select * from " . TABLE_EMAIL_TEMPLATES . " where email_templates_key='" . tep_db_input($ostatus['orders_status_template']) . "'");
            if (tep_db_num_rows($get_template_r) > 0) {
                $emailTemplate = $ostatus['orders_status_template'];
            }
        }
        if(!empty($emailTemplate)) {
            list($email_subject, $email_text) = \common\helpers\Mail::get_parsed_email_template($emailTemplate, $email_params, -1, $order->info['platform_id']);
            $email_headers = '';
//                        if ($TrustpilotClass = Acl::checkExtension('Trustpilot', 'onOrderUpdateEmail')) {
//                            $email_headers = $TrustpilotClass::onOrderUpdateEmail((int)$oID, $email_headers);
//                        }

            \common\helpers\Mail::send($order->customer['name'], $order->customer['email_address'], $email_subject, $email_text, $eMail_store_owner, $eMail_address, [], $email_headers);
        }
    }

    protected function createLocalOrder(Messages $message, $orderData)
    {
        global $cart, $order_totals, $order_total_modules, $order;

        \Yii::$app->set('session', 'yii\web\Session');
        \Yii::setAlias('@webCatalogImages', DIR_WS_IMAGES);
        \Yii::$container->setSingleton('products', '\common\components\ProductsContainer');

        if ( !is_object($cart) ) {
            $cart = new \common\classes\shopping_cart();
        }
        $cart->reset(true);

        $orderData = json_decode(json_encode($orderData),true);

        $tools = new Tools();

        $order = new \common\classes\Order();

        $order->customer['customer_id'] = 0;

        // arrange countries
        foreach (['customer','billing','delivery'] as $abKey) {
            if (isset($orderData[$abKey]) && isset($orderData[$abKey]['country_iso2'])) {
                $countryId = $tools->getCountryId($orderData[$abKey]['country_iso2']);
                $orderData[$abKey]['country_id'] = $countryId;
                $country_info = \common\helpers\Country::get_country_info_by_id($countryId);
                $orderData[$abKey]['country'] = [
                    'id' => $countryId,
                    'title' => $country_info['countries_name'],
                    'iso_code_2' => $country_info['countries_iso_code_2'],
                    'iso_code_3' => $country_info['countries_iso_code_3'],
                ];
                $orderData[$abKey]['format_id'] = \common\helpers\Address::get_address_format_id($countryId);
            }
            if (isset($orderData[$abKey]) && !empty($orderData[$abKey]['country_id']) && !empty($orderData[$abKey]['state'])) {
                $orderData[$abKey]['zone_id'] = \common\helpers\Zones::get_zone_id($orderData[$abKey]['country_id'],$orderData[$abKey]['state']);
            }
        }

        $check_customer_exists_r = tep_db_query(
            "SELECT customers_id ".
            "FROM ".TABLE_CUSTOMERS." ".
            "WHERE customers_email_address='".tep_db_input($orderData['customer']['email_address'])."' ".
            "LIMIT 1"
        );

        if ( tep_db_num_rows($check_customer_exists_r)>0 )
        {
            $check_customer_exists = tep_db_fetch_array($check_customer_exists_r);
            $customer_id = $check_customer_exists['customers_id'];
        }
        else
        {
            $customer_data = [
                'customers_firstname' => strval($orderData['customer']['firstname']),
                'customers_lastname' => strval($orderData['customer']['lastname']),
                'customers_email_address' => strval($orderData['customer']['email_address']),
                'customers_password' => \common\helpers\Password::encrypt_password(\common\helpers\Password::create_random_value(12)),
                'platform_id' => \common\classes\platform::defaultId(),
                'customers_telephone' => strval($orderData['customer']['telephone']),
                //'groups_id' => '',
                'customers_company' => strval($orderData['customer']['company']),
            ];
            tep_db_perform(TABLE_CUSTOMERS, $customer_data);
            $customer_id = tep_db_insert_id();

            tep_db_perform(TABLE_CUSTOMERS_INFO, [
                'customers_info_id' => $customer_id,
                'customers_info_date_account_created' => 'now()',
            ]);

            $customer_address_book = [
                'customers_id' => $customer_id,
                'entry_company' => strval($orderData['customer']['company']),
                'entry_firstname' => strval($orderData['customer']['firstname']),
                'entry_lastname' => strval($orderData['customer']['lastname']),
                'entry_street_address' => strval($orderData['customer']['street_address']),
                'entry_suburb' => strval($orderData['customer']['suburb']),
                'entry_postcode' => strval($orderData['customer']['postcode']),
                'entry_city' => strval($orderData['customer']['city']),
                'entry_state' => strval($orderData['customer']['state']),
                'entry_country_id' => strval($orderData['customer']['country']['id']),
                'entry_zone_id' => strval($orderData['customer']['zone_id']),
            ];
            tep_db_perform(TABLE_ADDRESS_BOOK, $customer_address_book);
            $customers_default_address_id = tep_db_insert_id();

            tep_db_perform(TABLE_CUSTOMERS,[
                'customers_default_address_id' => $customers_default_address_id,
            ],'update',"customers_id='".(int)$customer_id."'");
            //?? linkage
        }
        $orderData['customer']['customer_id'] = $customer_id;
        $orderData['customer']['id'] = $customer_id;


        foreach (['customer','billing','delivery'] as $abKey) {
            if ( !isset($orderData[$abKey]) ) continue;
            $orderData[$abKey]['address_book_id'] = $tools->addressBookFind($customer_id,$orderData[$abKey]);
            foreach (array_keys($order->{$abKey}) as $key) {
                $order->{$abKey}[$key] = isset($orderData[$abKey][$key]) ? $orderData[$abKey][$key] : null;
            }
        }
        if ( !isset($orderData['products']) || empty($orderData['products']) || !isset($orderData['products']['product']) ){
            $orderData['products'] = [];
        }else{
            $orderData['products'] = ArrayHelper::isIndexed($orderData['products']['product'])?$orderData['products']['product']:[$orderData['products']['product']];
        }

        $order->products = [];
        foreach( $orderData['products']  as $product ) {
            $this->addProductToOrder($message, $product, $order);
        }

        $order->totals = [];
        if (isset($orderData['totals']) && isset($orderData['totals']['total'])) {
            $totals = (array)$orderData['totals']['total'];
            if (!ArrayHelper::isIndexed($totals)) $totals = [$totals];
            foreach ($totals as $total) {
                $total = (array)$total;
                $total['class'] = $total['code'];
                $order->totals[] = $total;
            }
        }


        $infoData = isset($orderData['info']) ? $orderData['info'] : [];
        if ( isset($infoData['language']) ) {
            $infoData['language_id'] = \common\classes\language::get_id($infoData['language']);
        }
        foreach (array_keys($order->info) as $key) {
            $order->info[$key] = isset($infoData[$key]) ? $infoData[$key] : null;
        }
        if ( empty($order->info['language_id']) ) {
            $order->info['language_id'] = \common\classes\language::defaultId();
        }
        global $order_delivery_date;
        if ( isset($infoData['delivery_date']) && $infoData['delivery_date']>0 ) {
            $order->info['delivery_date'] = date('Y-m-d', strtotime($infoData['delivery_date']));
            $order_delivery_date = $order->info['delivery_date'];
        }

        if ( isset($infoData['date_purchased']) && $infoData['date_purchased']>0 ) {
            $order->info['date_purchased'] = date('Y-m-d H:i:s', strtotime($infoData['date_purchased']));
        }

        $order->info['platform_id'] = \common\classes\platform::defaultId();

        $order->info['order_status'] = DEFAULT_ORDERS_STATUS_ID;

        $update_status_history = [];
        if ( isset($orderData['status_history_array']) && is_array($orderData['status_history_array']) && isset($orderData['status_history_array']['status_history']) ) {
            $status_history = ArrayHelper::isIndexed($orderData['status_history_array']['status_history'])?$orderData['status_history_array']['status_history']:[$orderData['status_history_array']['status_history']];
            foreach ( $status_history as $idx=>$history_row ) {
                if (!isset($history_row['orders_status_id']) || empty($history_row['orders_status_id']) || array_search($history_row['orders_status_id'],$this->config['status_map_local_to_server'])===false ) {
                    unset($status_history[$idx]);
                    continue;
                }
                $status_history[$idx]['orders_status_id'] = array_search($history_row['orders_status_id'], $this->config['status_map_local_to_server']);
                $status_history[$idx]['comments'] = isset($history_row['comments'])?trim($history_row['comments']):'';
                if ( isset($history_row['date_added']) && $history_row['date_added']>0 ) {
                    $status_history[$idx]['date_added'] = date('Y-m-d H:i:s', strtotime($history_row['date_added']));
                }
                $update_status_history[] = $status_history[$idx];
            }
        }

        $insert_id = $order->save_order();
        if ( $insert_id ) {
            $post_patch_order = [
                'tracking_number' => implode(';',!empty($order->info['tracking_number'])?$order->info['tracking_number']:[]),
                'date_purchased' => $order->info['date_purchased'],
            ];
            if ( $order->info['delivery_date'] && $order->info['delivery_date']>2000 ) {
                $post_patch_order['delivery_date'] = $order->info['delivery_date'];
            }
            if (!empty($order->info['sap_order_id'])){
                $post_patch_order['sap_order_id'] = $order->info['sap_order_id'];
            }
            if ($order->info['sap_export_date']>1000){
                $post_patch_order['sap_export_date'] = $order->info['sap_export_date'];
            }
            if ( isset($infoData['sap_export']) && is_numeric($infoData['sap_export']) ) {
                $post_patch_order['sap_export'] = $infoData['sap_export'];
            }
            if ( isset($orderData['order_id']) && is_numeric($orderData['order_id']) ) {
                $post_patch_order['external_orders_id'] = $orderData['order_id'];
            }

            tep_db_perform(
                TABLE_ORDERS, $post_patch_order, 'update', "orders_id='".(int)$insert_id."'"
            );

            if ( class_exists('\common\helpers\SapCommon') ) {
                tep_db_query("DELETE FROM ep_sap_order_issues WHERE orders_id='" . (int)$insert_id . "'");
                if (isset($infoData['sap_export_issues']) && is_array($infoData['sap_export_issues'])) {
                    foreach ($infoData['sap_export_issues'] as $line) {
                        list($issue_date, $issue_message) = explode(';', $line, 2);
                        $issue_date = date('Y-m-d H:i:s', strtotime($issue_date));
                        tep_db_perform('ep_sap_order_issues', [
                            'orders_id' => (int)$insert_id,
                            'date_added' => $issue_date,
                            'issue_text' => $issue_message,
                        ]);
                    }
                }
            }

            $order_totals = $order->totals;
            $order->save_details();

            $order->save_products(false);

            if ( count($update_status_history)>0 ) {
                tep_db_query("DELETE FROM ".TABLE_ORDERS_STATUS_HISTORY." WHERE orders_id='".(int)$insert_id."'");
                foreach ( $update_status_history as $income_history ) {
                    if (!isset($income_history['orders_status_id']) || empty($income_history['orders_status_id'])) continue;
                    if (!isset($income_history['date_added']) || empty($income_history['date_added'])) continue;

                    $notify_flag = 0;
                    tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, [
                        'orders_id' => $insert_id,
                        'orders_status_id' => $income_history['orders_status_id'],
                        'date_added' => $income_history['date_added'],
                        'customer_notified' => $notify_flag,
                        'comments' => $income_history['comments'],
                    ]);
                }
                $history_last_order_status = tep_db_fetch_array(tep_db_query(
                    "SELECT orders_status_id, date_added ".
                    "FROM ".TABLE_ORDERS_STATUS_HISTORY." ".
                    "WHERE orders_id = '".(int)$insert_id."' ".
                    "ORDER BY date_added DESC, orders_status_history_id DESC ".
                    "LIMIT 1"
                ));
                if ( is_array($history_last_order_status) && $history_last_order_status['orders_status_id'] ) {
                    $order->info['order_status'] = $history_last_order_status['orders_status_id'];
                    tep_db_query(
                        "UPDATE " . TABLE_ORDERS . " " .
                        "SET orders_status='" . (int)$order->info['order_status'] . "', " .
                        "  last_modified='".tep_db_input($history_last_order_status['date_added'])."' ".
                        "WHERE orders_id = '" . (int)$insert_id . "'"
                    );
                }
            }

//            $this->updateLocalOrderStatusHistory($message, $order, $orderData);

            tep_db_query(
                "UPDATE ".TABLE_ORDERS." ".
                "SET _api_order_time_modified=_api_order_time_modified, _api_order_time_processed=_api_order_time_modified ".
                "WHERE orders_id='".$insert_id."' "
            );
        }

        return $insert_id;
    }

    private function syncOrderStatuses()
    {
        $datasource = Directory::loadById($this->config['directoryId'])->getDatasource();
        if ( $datasource ) {
            Helper::syncOrderStatuses($this->client, $datasource);
        }
    }

    protected function updateOrder(Messages $message, $orderId, $checkHash='', $serverOrderId=0)
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
            'order_id' => $serverOrderId,
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
            $response = $this->client->updatePurchaseOrder(
                $orderData
            );
            \Yii::error("updatePurchaseOrder \n\n".$this->client->__getLastRequest()."\n\n".$this->client->__getLastResponse(), 'datasource');
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
            \Yii::error('updatePurchaseOrder Exception : '.$ex->getMessage()."\n\n".$this->client->__getLastRequest()."\n\n".$this->client->__getLastResponse(), 'datasource');
        }

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

}