<?php

/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\modules\orderPayment;

use Yii;
use common\classes\modules\ModulePayment;
use common\classes\modules\ModuleStatus;
use common\classes\modules\ModuleSortOrder;
use common\classes\modules\TransactionalInterface;
use common\helpers\OrderPayment as OrderPaymentHelper;

class laybuy extends ModulePayment implements TransactionalInterface{

    var $code, $title, $description, $enabled, $test_mode;
    protected $defaultTranslationArray = [
        'MODULE_PAYMENT_LAYBUY_TEXT_TITLE' => 'Laybuy',
        'MODULE_PAYMENT_LAYBUY_TEXT_DESCRIPTION' => 'Laybuy'
    ];

    // class constructor
    function __construct() {
        parent::__construct();

        $this->code = 'laybuy';
        $this->title = MODULE_PAYMENT_LAYBUY_TEXT_TITLE;
        $this->description = MODULE_PAYMENT_LAYBUY_TEXT_DESCRIPTION;
        if (!defined('MODULE_PAYMENT_LAYBUY_STATUS')) {
            $this->enabled = false;
            return;
        }
        $this->enabled = ((MODULE_PAYMENT_LAYBUY_STATUS == 'True') ? true : false);
        $this->order_status = defined('MODULE_PAYMENT_LAYBUY_ORDER_STATUS_ID') && ((int) MODULE_PAYMENT_LAYBUY_ORDER_STATUS_ID > 0) ? (int) MODULE_PAYMENT_LAYBUY_ORDER_STATUS_ID : 0;
        $this->update_status();
    }

    // class methods
    function update_status() {
        if ( 
            (!defined('MODULE_PAYMENT_LAYBUY_MERCHANTID') || empty(MODULE_PAYMENT_LAYBUY_MERCHANTID))
            ||
            !defined('MODULE_PAYMENT_LAYBUY_APIKEY') || empty(MODULE_PAYMENT_LAYBUY_APIKEY)
            ){
                $this->enabled = false;
        }
        if ($this->enabled && ((int) MODULE_PAYMENT_LAYBUY_ZONE > 0)) {
            $check_flag = false;
            $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_LAYBUY_ZONE . "' and zone_country_id = '" . $this->billing['country']['id'] . "' order by zone_id");
            while ($check = tep_db_fetch_array($check_query)) {
                if ($check['zone_id'] < 1) {
                    $check_flag = true;
                    break;
                } elseif ($check['zone_id'] == $this->billing['zone_id']) {
                    $check_flag = true;
                    break;
                }
            }

            if ($check_flag == false) {
                $this->enabled = false;
            }
        }
    }

    function javascript_validation() {
        return false;
    }
    
    function isTestMode(){
        return (defined('MODULE_PAYMENT_LAYBUY_TEST_MODE') && MODULE_PAYMENT_LAYBUY_TEST_MODE == 'True' ? true: false);
    }
    
    function getEndPoint(){
        if ($this->isTestMode()){
            return "https://sandbox-api.laybuy.com/";
        } else {
            return "https://api.laybuy.com/";
        }
    }
        
    function format_raw($number, $currency_code = '', $currency_value = '') {
        return parent::formatRaw($number, $currency_code, $currency_value);
    }
    
    private function _getConnector(){
        $config = [
            'base_uri' => $this->getEndPoint(),
        ];
        if (defined('MODULE_PAYMENT_LAYBUY_PROXY') && !empty(MODULE_PAYMENT_LAYBUY_PROXY)){
            $config['proxy'] = trim(MODULE_PAYMENT_LAYBUY_PROXY);
        }
        return new \GuzzleHttp\Client($config);
    }
    
    private function _getHeaders(){
        return [
            'headers' =>
                [
                    'Authorization' => 'Basic ' . base64_encode(MODULE_PAYMENT_LAYBUY_MERCHANTID . ':' . MODULE_PAYMENT_LAYBUY_APIKEY),
                    'Content-Type' => 'application/json',
                ]
            ];
    }
    
    function selection() {
        return array('id' => $this->code,
            'module' => $this->title,
        );
    }
    
    public function getOrderParams(){
        $order = $this->manager->getOrderInstance();
        $orderAmount = (float)$this->format_raw($order->info['total_inc_tax']);
        $laybuy_transaction_id = md5(date("Y-m-d H:i:s"));
        $params = [
            'amount' => $orderAmount,
            'currency' => $order->info['currency'],
            'returnUrl' => $this->getCheckoutUrl(['check_order' => 1], self::PROCESS_PAGE),
            'merchantReference' => $laybuy_transaction_id,
            'tax' => $this->format_raw($order->info['tax']),
            'customer' => [
                'firstName' => $order->customer['firstname'],
                'lastName' => $order->customer['lastname'],
                'email' => $order->customer['email_address'],
                'phone' => $order->customer['telephone'],
            ],
            'billingAddress' => [
                'name' => $order->billing['firstname'] . ' ' . $order->billing['lastname'],
                'address1' => $order->billing['street_address'],
                'suburb' => $order->billing['suburb'],
                'city' => $order->billing['city'],
                'state' => $order->billing['state'],
                'postcode' => $order->billing['postcode'],
                'country' => $order->billing['country']['title'],
            ],
        ];
        if ($this->manager->isDeliveryUsed() && false){
            $params['shippingAddress'] = [
                'name' => $order->delivery['firstname'] . ' ' . $order->delivery['lastname'],
                'address1' => $order->delivery['street_address'],
                'suburb' => $order->delivery['suburb'],
                'city' => $order->delivery['city'],
                'state' => $order->delivery['state'],
                'postcode' => $order->delivery['postcode'],
                'country' => $order->delivery['country']['title'],                
            ];
        }
        $params['items'] = [];
        $total = 0;
        foreach($order->products as $product){
            $price = (float)$this->format_raw($product['final_price']);
            $item = [
                'id' => $product['model'],
                'description' => $product['name'],
                'quantity' => $product['qty'],
                'price' => $price,
            ];
            $total += $price * (int)$product['qty'];
            $params['items'][] = $item;
        }
        if ($this->manager->isDeliveryUsed()){
            $price = $this->format_raw($order->info['shipping_cost_inc_tax']);
            $params['items'][] = [
                'id' => "SHIPPING",
                'description' => 'Shipping',
                'quantity' => 1,
                'price' => $price,
            ];
            $total += $price;
        }
        
        if ($orderAmount != $total){
            $diff = $orderAmount - $total;
            $qty = $params['items'][sizeof($params['items']) - 1]['quantity'];
            if (!$qty) $qty = 1;
            $params['items'][sizeof($params['items']) - 1]['price'] += ($diff / $qty);
        }
        $this->manager->set('laybuy_transaction_id', $laybuy_transaction_id);
        return $params;
    }
    
    function pre_confirmation_check() {
        
        $connector = $this->_getConnector();
        $options = array_merge($this->_getHeaders(), ['body' => json_encode($this->getOrderParams())]);
        $response = $connector->post('order/create', $options);
        $body = $response->getBody();
        $response = json_decode($body->getContents(), true);
        if ($response['result'] == 'SUCCESS'){
            $this->manager->set('laybay',[
                'token' => $response['token'],
                'paymentUrl' => $response['paymentUrl']
            ]);
        } else {
            tep_redirect($this->getCheckoutUrl(['payment_error' => $this->code, 'error' => $response['error']], self::PAYMENT_PAGE), 'SSL', false);
        }
    }

    function confirmation() {
        return false;
    }

    function process_button() {
        
    }

    function before_process() {
        if (!$this->manager->has('laybay')){
            tep_redirect($this->getCheckoutUrl(['payment_error' => $this->code, 'error' => 'Laybay Error'], self::PAYMENT_PAGE), 'SSL', false);
        }
        
        if (!Yii::$app->request->get('check_order')){
            $data = $this->manager->get('laybay');
            $data['tmp_order_id'] = $this->saveOrder('TmpOrder');
            $this->manager->set('laybay', $data);
            tep_redirect($data['paymentUrl']);
        } else { //return, need confirmation
            if (Yii::$app->request->get('status') != 'SUCCESS'){
                $this->manager->remove('laybay');
                tep_redirect($this->getCheckoutUrl([], self::PAYMENT_PAGE), 'SSL', false);
            }            
        }
    }

    function after_process() {
        $connector = $this->_getConnector();
        $data = $this->manager->get('laybay');
        $params = [
            'token' => $data['token'],                
        ];
        $options = array_merge($this->_getHeaders(), ['body' => json_encode($params)]);
        $response = $connector->post('order/confirm', $options);
        $body = $response->getBody();
        $response = json_decode($body->getContents(), true);
        if ($response['result'] == 'SUCCESS'){            
            $options = array_merge($this->_getHeaders(), []);
            $response = $connector->get("order/{$response['orderId']}", $options);
            $body = $response->getBody();
            $response = json_decode($body->getContents(), true);
            if ($response['result'] == 'SUCCESS'){
                $order = $this->manager->getOrderInstance();
                $currencies = \Yii::$container->get('currencies');
                //{{ history
                $transaction_details = [
                    'Transaction Id: ' . $response['orderId'],
                    'Transaction Amount: '. $currencies->format($response['amount'], false, $order->info['currency'], $order->info['currency_value']),
                    'Response Text: ' . $response['result'],
                ];
                $oModel = $order->getARModel()->select('orders_status')->where(['orders_id' => $order->order_id])->one();
                $sql_data_array = array('orders_id' => $order->order_id,
                    'orders_status_id' => $oModel->orders_status,
                    'date_added' => 'now()',
                    'customer_notified' => '0',
                    'comments' => implode("\n", $transaction_details),
                );
                tep_db_perform($order->table_prefix . TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
                //}} history
                
                //{{ transactions
                $tManager = $this->manager->getTransactionManager($this);
                $invoice_id = $this->manager->getOrderSplitter()->getInvoiceId();
                $tManager->addTransaction($response['orderId'], 'Success', $response['amount'], $invoice_id, 'Customer\'s payment');                
                //{{
                
                $orderPayment = $this->searchRecord($this->manager->get('laybuy_transaction_id'));
                $orderPayment->orders_payment_order_id = $order->order_id;
                $orderPayment->orders_payment_snapshot = json_encode(OrderPaymentHelper::getOrderPaymentSnapshot($order));
                $orderPayment->orders_payment_status = OrderPaymentHelper::OPYS_SUCCESSFUL;
                $orderPayment->orders_payment_amount = (float)$response['amount'];
                $orderPayment->orders_payment_currency = trim($order->info['currency']);
                $orderPayment->orders_payment_currency_rate = (float)$order->info['currency_value'];
                $orderPayment->orders_payment_transaction_date = new \yii\db\Expression('now()');
                $orderPayment->orders_payment_transaction_id = $response['orderId'];
                $orderPayment->save(false);
                //}} transactions
            }
            $this->manager->remove('laybay');
            $this->manager->remove('laybuy_transaction_id');
        } else {
            tep_redirect($this->getCheckoutUrl(['payment_error' => $this->code, 'error' => $response['error']], self::PAYMENT_PAGE), 'SSL', false);
        }
    }
    
    public function getTransactionDetails($transaction_id, \common\services\PaymentTransactionManager $tManager = null){
        $connector = $this->_getConnector();
        $response = $connector->post("order/{$transaction_id}", $this->_getHeaders());
        $body = $response->getBody();
        $response = json_decode($body->getContents(), true);
        if ($response['result'] == 'SUCCESS'){
            return $response;
        }
        return false;
    }

    public function canRefund($transaction_id){
        $response = $this->getTransactionDetails($transaction_id);
        if ($response){
            if (isset($response['refunds'])){
                $refunded = 0;
                foreach($response['refunds'] as $_refund){
                    $refunded += (float)$_refund['amount'];
                }
                return $refunded < $response['amount'];
            }
            return true;
        }
        return false;
    }
    
    public function refund($transaction_id, $amount = 0){
        $message = 'Partial Refund';
        if (!$amount){
            $message = 'Full Refund';
            $details = $this->getTransactionDetails($transaction_id);
            if ($details){
                $amount = $details['amount'];
            }
        }
        $connector = $this->_getConnector();
        $params = [
            'orderId' => $transaction_id,
            'amount' => $amount,
        ];
        $options = array_merge($this->_getHeaders(), ['body' => json_encode($params)]);
        $response = $connector->post("order/refund", $options);
        $body = $response->getBody();
        $response = json_decode($body->getContents(), true);
        if ($response['result'] == 'SUCCESS'){
            $this->manager->getTransactionManager($this)
                        ->addTransactionChild($transaction_id, $response['refundId'], $response['result'], $amount, $message);
            $currencies = \Yii::$container->get('currencies');
            $order = $this->manager->getOrderInstance();
            $order->info['comments'] = "Refund State: " . $response['result'] . "\n" .
                    "Refund Date: " . date('d-m-Y H:i:s') . "\n" .
                    "Refund Amount: " . $currencies->format($amount, false, $order->info['currency'], $order->info['currency_value']);
            return true;
        }
        return false;
    }
    
    public function canVoid($transaction_id){
        return false;
    }
    
    public function void($transaction_id){
        return false;
    }

    function get_error() {

        if (isset($_GET['message']) && strlen($_GET['message']) > 0) {
            $error = stripslashes(urldecode($_GET['message']));
        } else {
            $error = $_GET['error'];
        }
        return array('title' => 'Laybay Error',
            'error' => $error);
    }

    public function describe_status_key() {
        return new ModuleStatus('MODULE_PAYMENT_LAYBUY_STATUS', 'True', 'False');
    }

    public function describe_sort_key() {
        return new ModuleSortOrder('MODULE_PAYMENT_LAYBUY_SORT_ORDER');
    }

    function isOnline() {
        return true;
    }

    public function configure_keys() {

        $status_id = defined('MODULE_PAYMENT_LAYBUY_ORDER_STATUS_ID') ? MODULE_PAYMENT_LAYBUY_ORDER_STATUS_ID : $this->getDefaultOrderStatusId();        

        $params = array('MODULE_PAYMENT_LAYBUY_STATUS' => array('title' => 'Enable Klarna Module',
                'description' => 'Do you want to accept Klarna payments?',
                'value' => 'True',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '),
            'MODULE_PAYMENT_LAYBUY_MERCHANTID' => array('title' => 'Username',
                'description' => 'Username to use for the Laybuy service'),
            'MODULE_PAYMENT_LAYBUY_APIKEY' => array('title' => 'Password',
                'description' => 'Password to use with the Laybuy service.'),
            'MODULE_PAYMENT_LAYBUY_PROXY' => array('title' => 'Proxy',
                'description' => 'Proxy (proxy address:port)'),
            'MODULE_PAYMENT_LAYBUY_TEST_MODE' => array('title' => 'Test Mode',
                'description' => 'Do you want to activate the Testmode?',
                'value' => 'False',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '),
            'MODULE_PAYMENT_LAYBUY_ORDER_STATUS_ID' => array('title' => 'Set Order Status',
                'description' => 'Set the status of orders made with this payment module to this value',
                'value' => $status_id,
                'set_function' => 'tep_cfg_pull_down_order_statuses(',
                'use_function' => '\\common\helpers\\Order::get_order_status_name'),
            'MODULE_PAYMENT_LAYBUY_ZONE' => array('title' => 'Payment Zone',
                'desc' => 'If a zone is selected, only enable this payment method for that zone.',
                'value' => '0',
                'use_function' => '\\common\\helpers\\Zones::get_zone_class_title',
                'set_function' => 'tep_cfg_pull_down_zone_classes('),
        );

        return $params;
    }
}
