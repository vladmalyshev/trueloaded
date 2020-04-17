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

  use common\classes\modules\ModulePayment;
  use common\classes\modules\ModuleStatus;
  use common\classes\modules\ModuleSortOrder;
  use common\classes\platform_config;
  use common\classes\modules\TransactionalInterface;
  use common\classes\modules\TransactionSearchInterface;

  class paypalipn extends lib\PaypalMidleWare implements TransactionalInterface, TransactionSearchInterface {
    var $code, $title, $description, $enabled, $notify_url, $curl, $add_shipping_to_amount, $add_tax_to_amount, $update_stock_before_payment, $allowed_currencies, $default_currency, $test_mode;

    protected $defaultTranslationArray = [
        'MODULE_PAYMENT_PAYPALIPN_TEXT_TITLE' => 'PayPal (including Credit and Debit Cards)',
        'MODULE_PAYMENT_PAYPALIPN_TEXT_DESCRIPTION' => 'PayPal IPN',
        'MODULE_PAYMENT_PAYPALIPN_TEXT_CURL' => 'cURL Enabled'
    ];
    
    public static $callback = 'callback/paypal-notify';

// class constructor
    function __construct() {
        parent::__construct();

        $this->code = 'paypalipn';
        $this->title = MODULE_PAYMENT_PAYPALIPN_TEXT_TITLE;
        $this->description = MODULE_PAYMENT_PAYPALIPN_TEXT_DESCRIPTION;
        if (!defined('MODULE_PAYMENT_PAYPALIPN_STATUS')) {
            $this->enabled = false;
            return;
        }
        $this->sort_order = MODULE_PAYMENT_PAYPALIPN_SORT_ORDER;
        $this->enabled = ((MODULE_PAYMENT_PAYPALIPN_STATUS == 'True') ? true : false);
        $this->notify_url = MODULE_PAYMENT_PAYPALIPN_NOTIFY_URL;
        $this->curl = ((MODULE_PAYMENT_PAYPALIPN_CURL == 'True') ? true : false);
        $this->add_shipping_to_amount = ((MODULE_PAYMENT_PAYPALIPN_ADD_SHIPPING_TO_AMOUNT == 'True') ? true : false);
        $this->add_tax_to_amount = ((MODULE_PAYMENT_PAYPALIPN_ADD_TAX_TO_AMOUNT == 'True') ? true : false);
        $this->update_stock_before_payment = ((MODULE_PAYMENT_PAYPALIPN_UPDATE_STOCK_BEFORE_PAYMENT == 'True') ? true : false);
        $this->allowed_currencies = MODULE_PAYMENT_PAYPALIPN_ALLOWED_CURRENCIES;
        $this->default_currency = MODULE_PAYMENT_PAYPALIPN_DEFAULT_CURRENCY;
        $this->test_mode = ((MODULE_PAYMENT_PAYPALIPN_TEST_MODE == 'True') ? true : false);
        $this->online = true;

        if ((int)MODULE_PAYMENT_PAYPALIPN_ORDER_STATUS_ID > 0) {
          $this->order_status = MODULE_PAYMENT_PAYPALIPN_ORDER_STATUS_ID;
        }

        $this->update_status();

        $this->dont_update_stock = !$this->update_stock_before_payment;
        $this->dont_send_email = true;
        
        $this->api = null;
        if (defined('MODULE_PAYMENT_PAYPALIPN_SIGNATURE') && !empty(MODULE_PAYMENT_PAYPALIPN_SIGNATURE)){
            $this->privateKey = MODULE_PAYMENT_PAYPALIPN_SIGNATURE;
            $this->api = 'Signature';
        } else if (defined('MODULE_PAYMENT_PAYPALIPN_CERTIFICATE') && !empty(MODULE_PAYMENT_PAYPALIPN_CERTIFICATE)){
            if (file_exists(\Yii::$aliases['@common'] . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'certificates' . DIRECTORY_SEPARATOR. MODULE_PAYMENT_PAYPALIPN_CERTIFICATE)){
                $this->privateKey = \Yii::$aliases['@common'] . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'certificates' . DIRECTORY_SEPARATOR. MODULE_PAYMENT_PAYPALIPN_CERTIFICATE;
                $this->api = 'Certificate';
            }
        }
    }

// class methods
    function update_status() {

      if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_PAYPALIPN_ZONE > 0) ) {
        $check_flag = false;
        $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_PAYPALIPN_ZONE . "' and zone_country_id = '" . $this->billing['country']['id'] . "' order by zone_id");
        while ($check = tep_db_fetch_array($check_query)) {
          if ($check['zone_id'] < 1) {
            $check_flag = true;
            break;
          } elseif ($check['zone_id'] == $this->delivery['zone_id']) {
            $check_flag = true;
            break;
          }
        }

        if ($check_flag == false) {
          $this->enabled = false;
        }
      }
    }

    function selection() {
      return array('id' => $this->code,
                   'module' => $this->title);
    }

    function before_process() {
        $order = $this->manager->getOrderInstance();
        if ((int)MODULE_PAYMENT_PAYPALIPN_ORDER_BEFORE_PAYMENT_ID > 0){
            $order->info['order_status'] = (int)MODULE_PAYMENT_PAYPALIPN_ORDER_BEFORE_PAYMENT_ID;
        } else {
            $order->info['order_status'] = (int)DEFAULT_ORDERS_STATUS_ID;
        }
        $order->isPaidUpdated = true;
        if ($this->isPartlyPaid()) {
            $this->after_process();
        }
    }

    function after_process() {
      $currencies = \Yii::$container->get('currencies');
      $order = $this->manager->getOrderInstance();

      $this->manager->clearAfterProcess();

      if (preg_match("/".preg_quote($order->info['currency'],"/")."/", MODULE_PAYMENT_PAYPALIPN_ALLOWED_CURRENCIES)) {
        $paypal_ipn_currency = $order->info['currency'];
      } else {
        $paypal_ipn_currency = MODULE_PAYMENT_PAYPALIPN_DEFAULT_CURRENCY;
      };

      
      if ($this->isPartlyPaid()) {
          $invoice = $this->manager->getOrderSplitter()->getInvoiceInstance();
          $paypal_ipn_order_amount = $invoice->info['total_inc_tax'];
          $tax = $invoice->info['tax'];
          $shipping_cost = $order->info['shipping_cost'];
          $order_id = $invoice->parent_id;
      } else {
          $paypal_ipn_order_amount = $order->info['total_inc_tax'];
          $tax = $order->info['tax'];
          $shipping_cost = $order->info['shipping_cost'];
          $order_id = $order->order_id;
      }
      $paypal_ipn_order_amount = number_format($paypal_ipn_order_amount * $currencies->get_value($paypal_ipn_currency), 2, '.', '');

      if (!$this->isPartlyPaid()) {
          $paypal_ipn_shipping_amount = number_format($shipping_cost * $currencies->get_value($paypal_ipn_currency), 2, '.', '');
          $paypal_ipn_tax_amount = number_format($tax * $currencies->get_value($paypal_ipn_currency), 2, '.', '');

          // is it possible to subtract:
          if (($paypal_ipn_order_amount - $paypal_ipn_shipping_amount - $paypal_ipn_tax_amount) > 0) {
              $force_add_shipping = $force_add_tax = false;
          } elseif (($paypal_ipn_order_amount - $paypal_ipn_tax_amount) > 0) {
              $force_add_shipping = true;
              $force_add_tax = false;
          } else {
              $force_add_shipping = $force_add_tax = true;
          }

          if (MODULE_PAYMENT_PAYPALIPN_ADD_SHIPPING_TO_AMOUNT=='True' || $force_add_shipping) {
              $paypal_ipn_shipping_amount = 0.00;
          } else {
              $paypal_ipn_order_amount -= $paypal_ipn_shipping_amount;
          }
          if (MODULE_PAYMENT_PAYPALIPN_ADD_TAX_TO_AMOUNT=='True' || $force_add_tax) {
              $paypal_ipn_tax_amount = 0.00;
          } else {
              $paypal_ipn_order_amount -= $paypal_ipn_tax_amount;
          }
      }

      $siteURL = 'https://www.paypal.com/';
      if ($this->test_mode){
          $siteURL = 'https://www.sandbox.paypal.com/';
      }

      $exists_subscription_data = false;
      foreach ($order->products as $i => $product) {
          if ($order->products[$i]['subscription'] == 1) {
            $exists_subscription_data = [
                'name' => $order->products[$i]['name'],
                'billingFrequency' => 12,
                'billingPeriod' => 'Month',
                'totalBillingCycles' => 12,
            ];
            break;
          }
      }

      \common\helpers\OrderPayment::createDebitFromOrder($order, $paypal_ipn_order_amount+$paypal_ipn_shipping_amount+$paypal_ipn_tax_amount, false, ['id' => md5($order_id."_".urlencode($order->customer['firstname']))]);

      if (is_array($exists_subscription_data)){

          tep_redirect($siteURL . "cgi-bin/webscr?cmd=_xclick-subscriptions&redirect_cmd=_xclick-subscriptions&business=".MODULE_PAYMENT_PAYPALIPN_ID.
                      "&item_name=".urlencode($exists_subscription_data['name']).(defined("TEXT_ITEM_SUBSCRIPTION")?TEXT_ITEM_SUBSCRIPTION:" - Subsribe for Products").
                      "&item_number=recurr_".$order_id.
                      "&currency_code=".$paypal_ipn_currency.
                      "&a3=".($paypal_ipn_order_amount+$paypal_ipn_shipping_amount+$paypal_ipn_tax_amount). //regular subscription amount without trial period
                      "&p3=".$exists_subscription_data['billingFrequency']. //Subscription duration.
                      "&t3=".$exists_subscription_data['billingPeriod']. //Regular subscription units of duration. Allowable values:
                      "&src=1".//subscription payments recur
                      "&srt=".((int)$exists_subscription_data['totalBillingCycles']>52?52:$exists_subscription_data['totalBillingCycles']).//Recurring times. Number of times that subscription payments recur. Specify an integer above 1. Valid only if you specify src="1". 52 is maximum allowed
                      "&shipping=".$paypal_ipn_shipping_amount.
                      "&tax=".$paypal_ipn_tax_amount.
                      "&first_name=".urlencode($order->customer['firstname']).
                      "&last_name=".urlencode($order->customer['lastname']).
                      "&address1=".urlencode(trim($order->customer['street_address'])).
                      "&city=".urlencode($order->customer['city']).
                      "&state=".urlencode($order->customer['state']).
                      "&zip=".urlencode($order->customer['postcode']).
                      "&email=".$order->customer['email_address']."&bn=HOLBIGROUPLTD_Cart_WPS&return=".tep_href_link(FILENAME_CHECKOUT_SUCCESS, 'orders_id='.$order_id, 'SSL').
                      "&cancel_return=".tep_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL').
                      "&notify_url=".urlencode(MODULE_PAYMENT_PAYPALIPN_NOTIFY_URL));


        //tep_redirect($siteURL . "cgi-bin/webscr?cmd=_ext-enter&redirect_cmd=_xclick&business=".MODULE_PAYMENT_PAYPALIPN_ID."&item_name=".urlencode(STORE_NAME)."&item_number=".$insert_id."&currency_code=".$paypal_ipn_currency."&amount=".$paypal_ipn_order_amount."&shipping=".$paypal_ipn_shipping_amount."&tax=".$paypal_ipn_tax_amount."&first_name=".urlencode($order->customer['firstname'])."&last_name=".urlencode($order->customer['lastname'])."&address1=".urlencode($order->customer['street_address'])."&city=".urlencode($order->customer['city'])."&state=".urlencode($order->customer['state'])."&zip=".urlencode($order->customer['postcode'])."&country=".urlencode($order->customer['country']['iso_code_2'])."&email=".$order->customer['email_address']."&bn=HOLBIGROUPLTD_Cart_WPS&return=".tep_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL')."&cancel_return=".tep_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL')."&notify_url=".MODULE_PAYMENT_PAYPALIPN_NOTIFY_URL);
      } else {
        $platform_id = $this->manager->getPlatformId();
        $base_url = \Yii::$app->get('platform')->getConfig($platform_id)->getCatalogBaseUrl(true) . self::$callback;
        $notify_url = MODULE_PAYMENT_PAYPALIPN_NOTIFY_URL;
        if (strcmp($base_url, $notify_url) !== 0){
            $notify_url = $base_url;
            $aN = new \backend\models\AdminNotifier;
            $aN->addNotification(null, 'Paypal IPN Notify Url on ' . \common\classes\platform::name($platform_id) . ' differs to platform Url, ' . MODULE_PAYMENT_PAYPALIPN_NOTIFY_URL . ' - ' . $base_url, 'warning');
        }
        if ($this->isPartlyPaid()) {
            $cancel_return = $this->getCheckoutUrl(['order_id' => $order_id]);
        } else {
            $cancel_return = tep_href_link('checkout/restart', 'order_id='.$order_id, 'SSL');
        }
        tep_redirect($siteURL . "cgi-bin/webscr?cmd=_ext-enter&redirect_cmd=_xclick&business=".MODULE_PAYMENT_PAYPALIPN_ID."&item_name=".urlencode(STORE_NAME)."&item_number=".$order_id."&currency_code=".$paypal_ipn_currency."&amount=".$paypal_ipn_order_amount."&shipping=".$paypal_ipn_shipping_amount."&tax=".$paypal_ipn_tax_amount."&first_name=".urlencode($order->customer['firstname'])."&last_name=".urlencode($order->customer['lastname'])."&address1=".urlencode($order->customer['street_address'])."&city=".urlencode($order->customer['city'])."&state=".urlencode($order->customer['state'])."&zip=".urlencode($order->customer['postcode'])."&country=".urlencode($order->customer['country']['iso_code_2'])."&email=".$order->customer['email_address']."&bn=HOLBIGROUPLTD_Cart_WPS&return=".tep_href_link(FILENAME_CHECKOUT_SUCCESS, 'orders_id='.$order_id, 'SSL')."&cancel_return=".$cancel_return."&notify_url=".$notify_url);
      }

      exit;
    }

    function output_error() {
      return false;
    }

    public function getTransactionDetails($transaction_id, \common\services\PaymentTransactionManager $tManager = null){        
        try{
            $response = $this->setTransaction('GetTransactionDetails', array('TransactionID' => $transaction_id));
            if ($tManager && $response['ACK'] == 'Success') {
                $tManager->updateTransactionFromPayment($transaction_id, $response['PAYMENTSTATUS'], $response['AMT'], date("Y-m-d H:i:s", strtotime($response['ORDERTIME'])));
            }
            if (!$response && $this->_isReady()){
                    try{
                        $response = parent::getTransactionDetails($transaction_id, $tManager);
                    } catch (\Error $ex) {
                    } catch (\Exception $ex) {}
                }
            return $response;
        } catch (\Exception $ex) {
            $this->sendDebugEmail($ex);
        }
    }

    function canVoid($transaction_id){
        return false;
    }

    function void($transaction_id){
        return false;
    }

    function canRefund($transaction_id){
        $response = $this->getTransactionDetails($transaction_id);
        if ($response['ACK'] == 'Success' || $response['ACK'] == 'SuccessWithWarning') {
            return !in_array($response['PAYMENTSTATUS'], ['Refunded']);
        } else if ($this->_isReady()){
            return parent::canRefund($transaction_id);
        }
        return false;
    }

    function refund($transaction_id, $amount = 0){
        
        $order = $this->manager->getOrderInstance();

        $parameters = [
                    'TRANSACTIONID' => $transaction_id,
                    'REFUNDTYPE' => ($amount ? urlencode('Partial') : urlencode('Full')),
                    'CURRENCYCODE' => $order->info['currency'],
                ];
        if ($amount){
            $parameters['AMT'] = $amount;
        }
        
        $response = $this->setTransaction('RefundTransaction', $parameters);

        if ($response['ACK'] == 'Success' || $response['ACK'] == 'SuccessWithWarning') {
            try {
                $tManager = $this->manager->getTransactionManager($this);
                $tManager->addTransactionChild($transaction_id, $response['REFUNDTRANSACTIONID'], $response['REFUNDSTATUS'], $response['GROSSREFUNDAMT'], ($amount? 'Partial Refund':'Full Refund'));                
            } catch (\Exception $exc) {}
            try {
                $orderPaymentParentRecord = \common\helpers\OrderPayment::searchRecord($this->code, $transaction_id);
                if ($orderPaymentParentRecord) {
                    $orderPaymentRecord = \common\helpers\OrderPayment::searchRecord($this->code, $response['REFUNDTRANSACTIONID']);
                    if ($orderPaymentRecord) {
                        $orderPaymentRecord->orders_payment_id_parent = (int)$orderPaymentParentRecord->orders_payment_id;
                        $orderPaymentRecord->orders_payment_order_id = (int)$order->order_id;
                        $orderPaymentRecord->orders_payment_module_name = trim($order->info['payment_method']);
                        $orderPaymentRecord->orders_payment_is_credit = 1;
                        $orderPaymentRecord->orders_payment_status = \common\helpers\OrderPayment::OPYS_REFUNDED;
                        $orderPaymentRecord->orders_payment_amount = (float)$response['GROSSREFUNDAMT'];
                        $orderPaymentRecord->orders_payment_currency = trim($order->info['currency']);
                        $orderPaymentRecord->orders_payment_currency_rate = (float)$order->info['currency_value'];
                        $orderPaymentRecord->orders_payment_snapshot = json_encode(\common\helpers\OrderPayment::getOrderPaymentSnapshot($order));
                        $orderPaymentRecord->orders_payment_transaction_status = trim($response['REFUNDSTATUS']);
                        $orderPaymentRecord->orders_payment_transaction_date = date('Y-m-d H:i:s');
                        $orderPaymentRecord->orders_payment_date_create = date('Y-m-d H:i:s');
                        $orderPaymentRecord->save();
                    }                    
                }
            } catch (\Exception $exc) {}
            return true;
        } else if ($this->_isReady()){
            return parent::refund($transaction_id, $amount);
        }

        return false;
    }

    public $api_version = '2.0';//'112';

    function setTransaction($method, $parameters) {
        
        foreach($this->getPaypalConfig() as $key => $value){
            $this->{$key} = $value;
        }
        
        $api_url = $this->getServiceLocation('nvp');
        
        $params = array('VERSION' => $this->api_version,
                      'METHOD' => $method,
                      'PAYMENTREQUEST_0_PAYMENTACTION' => 'Sale',
                      'BRANDNAME' => STORE_NAME,
                      );

        $params['USER'] = MODULE_PAYMENT_PAYPALIPN_USERNAME;
        $params['PWD'] = MODULE_PAYMENT_PAYPALIPN_PASSWORD;
        if (is_null($this->api)){
            $this->sendDebugEmail(['error' => 'Undefined signature or certificate']);
            return false;
        }
        
        if ($this->api == 'Signature'){
            $params['SIGNATURE'] = $this->privateKey;
        }

        if (is_array($parameters) && !empty($parameters)) {
            $params = array_merge($params, $parameters);
        }

        $post_string = '';

        foreach ($params as $key => $value) {
            $post_string .= $key . '=' . urlencode(utf8_encode(trim($value))) . '&';
        }

        $post_string = substr($post_string, 0, -1);

        $response = $this->sendTransactionToGateway($api_url, $post_string);

        $response_array = array();
        parse_str($response, $response_array);

        if (($response_array['ACK'] != 'Success') && ($response_array['ACK'] != 'SuccessWithWarning')) {
            $this->sendDebugEmail($response_array);
        }

        return $response_array;
    }
    
    private function getPaypalConfig(){
        $config = array(
            'mode' => $this->test_mode ? 'Sandbox':'Live',
            'paypal_username' => MODULE_PAYMENT_PAYPALIPN_USERNAME,
            'paypal_password' => MODULE_PAYMENT_PAYPALIPN_PASSWORD,
          );
        if ($this->api == 'Certificate'){
            $config['certificate_path'] = $this->privateKey;
        } else if ($this->api == 'Signature'){
            $config['signature_value'] = $this->privateKey;
        }
        return $config;
    }
    
    function getServiceLocation($type = 'nvp'){
        $ret = '';
        $mode_api = $this->mode.$this->api;
        if ($type == 'soap'){
            switch ( $mode_api ) {
                case 'SandboxCertificate':
                  $ret = 'https://api-3t.sandbox.paypal.com/2.0/';
                  break;
                case 'SandboxSignature':
                  $ret = 'https://api-3t.paypal.com/2.0/';
                  break;
                case 'LiveCertificate':
                  $ret = 'https://api.paypal.com/2.0/';
                  break;
                case 'LiveSignature':
                  $ret = 'https://api-3t.sandbox.paypal.com/2.0/';
                  break;
                default:
                  die( 'What is '.$mode_api );
            }
        } else if ($type == 'nvp'){
            switch ( $mode_api ) {
                case 'SandboxCertificate':
                  $ret = 'https://api.sandbox.paypal.com/nvp';
                  break;
                case 'SandboxSignature':
                  $ret = 'https://api-3t.sandbox.paypal.com/nvp';
                  break;
                case 'LiveCertificate':
                  $ret = 'https://api.paypal.com/nvp';
                  break;
                case 'LiveSignature':
                  $ret = 'https://api-3t.paypal.com/nvp';
                  break;
                default:
                  die( 'What is '.$mode_api );
            }
        }
        
        return $ret;
    }

    function _SoapHeader(){
        $sig_addon = '';
        if ( $this->api=='Signature' ) {
          $sig_addon = '<Signature>'.$this->signature_value.'</Signature>';
        }
        return '<soap:Header>' .
                 '<RequesterCredentials xmlns="urn:ebay:api:PayPalAPI">' .
                   '<Credentials xmlns="urn:ebay:apis:eBLBaseComponents">' .
                     '<Username>'.$this->paypal_username.'</Username>'.
                     '<ebl:Password xmlns:ebl="urn:ebay:apis:eBLBaseComponents">'.$this->paypal_password.'</ebl:Password>'.
                     $sig_addon .
                   '</Credentials>'.
                  '</RequesterCredentials>'.
               '</soap:Header>';
  }

    function _GetTransactionDetailsRequest( $params ){
        return '<GetTransactionDetailsReq xmlns="urn:ebay:api:PayPalAPI">'.
               '<GetTransactionDetailsRequest>'.
                 '<Version xmlns="urn:ebay:apis:eBLBaseComponents">'.$this->api_version.'</Version>'.
                 '<TransactionID>'.$params['TransactionID'].'</TransactionID>'.
               '</GetTransactionDetailsRequest>'.
             '</GetTransactionDetailsReq>';
    }

    function call( $method, $params=false ){
        $request = '<?xml version="1.0" encoding="utf-8"?'.'>'."\n";
        $request .= '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">';
        $request .= $this->_SoapHeader();
        $request .= '<soap:Body>';
        switch ($method){
          case 'GetTransactionDetails':
            $request .= $this->_GetTransactionDetailsRequest( $params );
          break;
          default:
          die( 'Unknown method ['.$method.']' );
        }
        $request .= '</soap:Body>';
        $request .= '</soap:Envelope>';
        if ( $this->http_request( $request ) ) {
          $parser = new paypal_xmlParser( $this->_response );
          $root = $parser->GetRoot();
          $data = $parser->GetData();
          $data = $data[$root]['SOAP-ENV:Body'][$method.'Response'];
          switch ($method){
            case 'GetTransactionDetails':
              if (isset($data['PaymentTransactionDetails']['PaymentItemInfo']['PaymentItem']['Name']) ){
                $data['PaymentTransactionDetails']['PaymentItemInfo']['PaymentItem'] = array($data['PaymentTransactionDetails']['PaymentItemInfo']['PaymentItem']);
              }
            break;
            default:
          }
          return $data;
        }else{
          return array('TIMESTAMP' => date('Y-m-d').'T'.date('H-i-s').'Z',
                       'ACK' => 'Failure',
                       'L_LONGMESSAGE0' => $this->_error,
                       'L_SHORTMESSAGE0' => $this->_error,
          );
        };
    }

  function http_request( $xml_contents ){
    $this->_request = $xml_contents;
    $this->_response = '';
    $this->_error = '';

    //Initialize curl
    $ch = curl_init();
    if ( !$ch ) {
      $this->_error = 'Curl Init error';
      return false;
    }

    //For the poor souls on GoDaddy and the like, set the connection to go through their proxy
    if ( !empty($this->http_proxy) ) {
      curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
      curl_setopt($ch, CURLOPT_PROXY, $this->http_proxy);
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    if ( $this->api=='Certificate' ) {
      curl_setopt($ch, CURLOPT_SSLCERTTYPE, "PEM");
      curl_setopt($ch, CURLOPT_SSLCERT, $this->certificate_path);
    }
    curl_setopt($ch, CURLOPT_URL, $this->getServiceLocation('soap'));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_contents);

    $this->_response = curl_exec($ch);

    if ( !empty($this->_response) ) {
      curl_close($ch);
      //Simple check to make sure that this is a valid response
//      if (strpos($response, 'SOAP-ENV') === false) {
//        $response = false;
//      }
      return true;
    } else {
      $this->_error = curl_error($ch) . ' (Error No. ' . curl_errno($ch) . ')';
      curl_close($ch);
    }
    return false;
  }

    function sendTransactionToGateway($url, $parameters) {
        $server = parse_url($url);

        if ( !isset($server['port']) ) {
            $server['port'] = ($server['scheme'] == 'https') ? 443 : 80;
        }

        if ( !isset($server['path']) ) {
            $server['path'] = '/';
        }
        
        $curl = curl_init($server['scheme'] . '://' . $server['host'] . $server['path'] . (isset($server['query']) ? '?' . $server['query'] : ''));
        curl_setopt($curl, CURLOPT_PORT, $server['port']);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters);

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT, 3);//30
        
        if ($this->api == 'Certificate') {
            curl_setopt($curl, CURLOPT_SSLCERTTYPE, "PEM");
            curl_setopt($curl, CURLOPT_SSLCERT, $this->privateKey);
        }
        try{
            $result = curl_exec($curl);
        } catch(\Exception $ex){
            $this->sendDebugEmail($ex);
        }

        curl_close($curl);

        return $result;
    }

    public function describe_status_key()
    {
      return new ModuleStatus('MODULE_PAYMENT_PAYPALIPN_STATUS','True','False');
    }

    public function describe_sort_key()
    {
      return new ModuleSortOrder('MODULE_PAYMENT_PAYPALIPN_SORT_ORDER');
    }

    protected function get_install_keys($platform_id)
    {
      $keys = $this->configure_keys();

      $platform_config = new platform_config($platform_id);

      if (isset($keys['MODULE_PAYMENT_PAYPALIPN_CURL'])) {
        if (function_exists('curl_exec')) {
          $curl_message = '<br>cURL has been <b>DETECTED</b> in your system';
        } else {
          $curl_message = '<br>cURL has <b>NOT</b> been <b>DETECTED</b> in your system';
        };
        $keys['MODULE_PAYMENT_PAYPALIPN_CURL']['description'] = str_replace('<curl_message>', $curl_message, $keys['MODULE_PAYMENT_PAYPALIPN_CURL']['description']);
      }
      if ( isset($keys['MODULE_PAYMENT_PAYPALIPN_NOTIFY_URL']) ) {
        $keys['MODULE_PAYMENT_PAYPALIPN_NOTIFY_URL']['value'] = $platform_config->getCatalogBaseUrl().$keys['MODULE_PAYMENT_PAYPALIPN_NOTIFY_URL']['value'];
      }

      $paypal_supported_currencies = "'USD','EUR','GBP','CAD','JPY'";
      $pCurrencies = $platform_config->getAllowedCurrencies();
      if (is_array($pCurrencies)){
          $osc_allowed_currencies = implode(',', $pCurrencies);
      }
      if (empty($osc_allowed_currencies)) {
        $osc_allowed_currencies = 'USD';
      };

      $replace_currencies = array(
        '<paypal_supported_currencies>' => str_replace('\'','',$paypal_supported_currencies),
        '<osc_allowed_currencies>' => $osc_allowed_currencies,
        '<osc_set_allowed_currencies>' => var_export(explode(',',$osc_allowed_currencies),true),
      );

      foreach( $keys as $key=>$key_data ) {
        $keys[$key]['value'] = str_replace(array_keys($replace_currencies),array_values($replace_currencies),$keys[$key]['value']);
        $keys[$key]['description'] = str_replace(array_keys($replace_currencies),array_values($replace_currencies),$keys[$key]['description']);
        $keys[$key]['set_function'] = str_replace(array_keys($replace_currencies),array_values($replace_currencies),$keys[$key]['set_function']);
      }
      return $keys;
    }
    
    public function configure_keys()
    {
        $status_id = defined('MODULE_PAYMENT_PAYPALIPN_ORDER_STATUS_ID') ? MODULE_PAYMENT_PAYPALIPN_ORDER_STATUS_ID : $this->getDefaultOrderStatusId();
      return array (
        'MODULE_PAYMENT_PAYPALIPN_STATUS' => array(
          'title' => 'Allow PayPal IPN',
          'value' => 'True',
          'description' => 'Do you want to accept PayPal IPN payments and notifications?',
          'sort_order' => '1',
          'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
        ),
        'MODULE_PAYMENT_PAYPALIPN_ID' => array(
          'title' => 'PayPal IPN ID',
          'value' => 'you@yourbusiness.com',
          'description' => 'Your business ID at PayPal.  Usually the email address you signed up with.  You can create a free PayPal account at <a href="http://www.paypal.com/" target="_blank">http://www.paypal.com</a>.',
          'sort_order' => '2',
        ),
        'MODULE_PAYMENT_PAYPALIPN_NOTIFY_URL' => array(
          'title' => 'PayPal IPN Notify URL',
          'value' => self::$callback,
          'description' => sprintf('Exact location in which your %s resides.', self::$callback),
          'sort_order' => '3',
          'set_function' => '\\common\\modules\\orderPayment\\urlchecker('
        ),
        'MODULE_PAYMENT_PAYPALIPN_USERNAME' => array('title' => 'API Username',
                                                            'description' => 'The username to use for the PayPal API service.'),
        'MODULE_PAYMENT_PAYPALIPN_PASSWORD' => array('title' => 'API Password',
                                                            'description' => 'The password to use for the PayPal API service.'),
        'MODULE_PAYMENT_PAYPALIPN_SIGNATURE' => array('title' => 'Use API Signature',
                                                            'description' => 'The signature to use for the PayPal API service.'),
        'MODULE_PAYMENT_PAYPALIPN_CERTIFICATE' => array('title' => 'or API Certificate',
                                                            'description' => 'The certificate to use for the PayPal API service.',
                                                            'value' => '',
                                                            'set_function' => 'cfg_upload_file(',
                                                            ),
        'MODULE_PAYMENT_PAYPALIPN_CLIENT_ID' => array('title' => 'API Application Client ID',
                                                                             'description' => 'The Client ID of app'),
        'MODULE_PAYMENT_PPAYPALIPN_CLIENT_SECRET' => array('title' => 'API Application Client Secret Key',
                                                                             'description' => 'The Client Secret Key of app'),
        'MODULE_PAYMENT_PAYPALIPN_CURL' => array (
          'title' => 'PayPal IPN Use cURL',
          'value' => 'False',
          'description' => 'Use cURL to communicate with PayPal?<curl_message>',
          'sort_order' => '4',
          'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
        ),
        'MODULE_PAYMENT_PAYPALIPN_ADD_SHIPPING_TO_AMOUNT' => array(
          'title' => 'PayPal IPN Add Shipping to Amount',
          'value' => 'False',
          'description' => 'Add shipping amount to order amount? (will set shipping amount to $0 in PayPal)',
          'sort_order' => '5',
          'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
        ),
        'MODULE_PAYMENT_PAYPALIPN_ADD_TAX_TO_AMOUNT' => array(
          'title' => 'PayPal IPN Add Tax to Amount',
          'value' => 'False',
          'description' => 'Add tax amount to order amount? (will set tax amount to $0 in PayPal)',
          'sort_order' => '5',
          'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
        ),
        'MODULE_PAYMENT_PAYPALIPN_UPDATE_STOCK_BEFORE_PAYMENT' => array (
          'title' => 'PayPal IPN Update Stock Before Payment',
          'value' => 'False',
          'description' => 'Should Products Stock be updated even when the payment is not yet COMPLETED?',
          'sort_order' => '6',
          'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
        ),
        'MODULE_PAYMENT_PAYPALIPN_ALLOWED_CURRENCIES' => array(
          'title' => 'PayPal IPN Allowed Currencies',
          'value' => '<osc_allowed_currencies>',
          'description' => 'Allowed currencies in which customers can pay.<br>Allowed by PayPal: <paypal_supported_currencies><br>Allowed in your shop: <osc_allowed_currencies><br>To add more currencies to your shop go to Localization->Currencies.',
          'sort_order' => '9',
        ),
        'MODULE_PAYMENT_PAYPALIPN_DEFAULT_CURRENCY' => array (
          'title' => 'PayPal IPN Default Currency',
          'value' => 'USD',
          'description' => 'Default currency to use when customer try to pay in a NON allowed (because of PayPal or you) currency',
          'sort_order' => '10',
          'set_function' => 'tep_cfg_select_option(<osc_set_allowed_currencies>, ',
        ),
        'MODULE_PAYMENT_PAYPALIPN_TEST_MODE' => array(
          'title' => 'PayPal IPN Test Mode',
          'value' => 'False',
          'description' => 'Run in TEST MODE? If so, you will be able to send TEST IPN from Admin->PayPal_IPN->Test_IPN, BUT you will not be able to receive real IPN\'s from PayPal.',
          'sort_order' => '11',
          'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
        ),
        'MODULE_PAYMENT_PAYPALIPN_ZONE' => array(
          'title' => 'PayPal IPN Payment Zone',
          'value' => '0',
          'description' => 'If a zone is selected, only enable this payment method for that zone.',
          'sort_order' => '13',
          'use_function' => '\\common\\helpers\\Zones::get_zone_class_title',
          'set_function' => 'tep_cfg_pull_down_zone_classes(',
        ),
        'MODULE_PAYMENT_PAYPALIPN_ORDER_BEFORE_PAYMENT_ID' => array(
          'title' => 'PayPal IPN Set Status Before Payment',
          'value' => $status_id,
          'description' => 'Set the status of ordersbefore redirect to Gateway',
          'sort_order' => '14',
          'set_function' => 'tep_cfg_pull_down_order_statuses(',
          'use_function' => '\\common\\helpers\\Order::get_order_status_name',
        ),
        'MODULE_PAYMENT_PAYPALIPN_ORDER_STATUS_ID' => array(
          'title' => 'PayPal IPN Set Order Status',
          'value' => $status_id,
          'description' => 'Set the status of orders made with this payment module to this value',
          'sort_order' => '14',
          'set_function' => 'tep_cfg_pull_down_order_statuses(',
          'use_function' => '\\common\\helpers\\Order::get_order_status_name',
        ),
        'MODULE_PAYMENT_PAYPALIPN_CANCEL_ORDER_STATUS_ID' => array(
            'title' => 'PayPal Transactions Cancel Order Status',
            'desc' => 'Set Order Status to cancel doubled order',
            'value' => '0',
            'use_func' => '\\common\\helpers\\Order::get_order_status_name',
            'set_function' => 'tep_cfg_pull_down_order_statuses('
        ),
        'MODULE_PAYMENT_PAYPALIPN_DEBUG_EMAIL' => array(
          'title' => 'PayPal IPN Debug Email',
          'value' => '',
          'description' => 'PayPal IPN Debug Email',
          'sort_order' => '15',
        ),
        'MODULE_PAYMENT_PAYPALIPN_SORT_ORDER' => array(
          'title' => 'PayPal IPN Sort order of display.',
          'value' => '0',
          'description' => 'Sort order of display. Lowest is displayed first.',
          'sort_order' => '12',
        ),
      );
    }

    function isOnline() {
        return true;
        return $this->isPartlyPaid();
    }

    function haveSubscription() {
        return true;
    }
    function forPOS() {
        return true;
    }

    function sendDebugEmail($response = array()) {
      if (tep_not_null(MODULE_PAYMENT_PAYPALIPN_DEBUG_EMAIL)) {
        $email_body = '';

        if (!empty($response)) {
          $email_body .= 'RESPONSE:' . "\n\n" . print_r($response, true) . "\n\n";
        }

        if (!empty($_POST)) {
          $email_body .= '$_POST:' . "\n\n" . print_r($_POST, true) . "\n\n";
        }

        if (!empty($_GET)) {
          $email_body .= '$_GET:' . "\n\n" . print_r($_GET, true) . "\n\n";
        }

        if (!empty($email_body)) {
          \common\helpers\Mail::send('', MODULE_PAYMENT_PAYPALIPN_DEBUG_EMAIL, 'PayPal IPN Debug E-Mail', trim($email_body), STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
        }
      }
    }
    
    protected function _isReady(){
        return (defined('MODULE_PAYMENT_PAYPALIPN_CLIENT_ID') && !empty(MODULE_PAYMENT_PAYPALIPN_CLIENT_ID) &&
                defined('MODULE_PAYMENT_PAYPALIPN_CLIENT_SECRET') && !empty('MODULE_PAYMENT_PAYPALIPN_CLIENT_SECRET'));
    }
    
    protected function _getClientId(){
        return MODULE_PAYMENT_PAYPALIPN_CLIENT_ID;
    }
    protected function _getClientSecret(){
        return MODULE_PAYMENT_PAYPALIPN_CLIENT_SECRET;
    }
    protected function _getIntent(){
        return 'sale';
    }
    protected function _getMode(){
        return defined('MODULE_PAYMENT_PAYPALIPN_TEST_MODE') && MODULE_PAYMENT_PAYPALIPN_TEST_MODE == 'False'?'Live':'Sandbox';
    }
    
    public function getFields(){
        if ($this->_isReady()){
            return parent::getFields();
        }
        return [
            //[['STARTDATE', 'TRANSACTIONID'], 'required'],
            [['STARTDATE'], 'datetime', 'format' => 'yyyy-MM-dd HH:mm:ss'],
            [['ENDDATE'], 'datetime', 'format' => 'yyyy-MM-dd HH:mm:ss'],
            ['TRANSACTIONID', 'string'],
        ];
    }
    
    public function search($queryParams) {
        
        $queryParams['start_date'] = $queryParams['STARTDATE'];
        $queryParams['end_date'] = $queryParams['ENDDATE'];
        $queryParams['transaction_id'] = $queryParams['TRANSACTIONID'];
        
        if ($found = $this->getIpnTransactions($queryParams)){
            return $found;
        } else if ($this->_isReady()){
            $queryParams['skipIpn'] = true;
            return parent::search($queryParams);
        }
        
        foreach($queryParams as $key => $value){
            if (empty($value)){
                unset($queryParams[$key]);
            }
        }
        foreach($queryParams as $key => $value){
            if ($key == 'STARTDATE'){
                $queryParams['STARTDATE'] = gmdate("Y-m-d\TH:i:s\Z", strtotime($queryParams['STARTDATE']));
            }
            if ($key == 'ENDDATE'){
                $queryParams['ENDDATE'] = gmdate("Y-m-d\TH:i:s\Z", strtotime($queryParams['ENDDATE']));
            }
        }
        
        if (!$queryParams['STARTDATE']){
            $queryParams['STARTDATE'] = gmdate("Y-m-d\TH:i:s\Z", strtotime("-31 days"));
        }
        
        if (!$queryParams['ENDDATE']){
            $queryParams['ENDDATE'] = gmdate("Y-m-d\TH:i:s\Z");
        }

        $found = [];
        $reposne = $this->setTransaction('TransactionSearch', $queryParams);
        if ($reposne && $reposne['ACK'] == 'Success'){
            $currencies = \Yii::$container->get('currencies');
            $iter = 0;
            do{
                $type = $reposne['L_TYPE' . $iter];
                if (in_array($type, ['Payment','Purchase'])){
                    $found[] = [
                        'id' => $reposne['L_TRANSACTIONID' . $iter],
                        'date' => \common\helpers\Date::formatDateTimeJS($reposne['L_TIMESTAMP' . $iter]),
                        'amount' => $currencies->format( $reposne['L_AMT' . $iter], true, $reposne['L_CURRENCYCODE' . $iter]),
                        'negative' => $reposne['L_AMT' . $iter] < 0,
                        'name' =>   $reposne['L_NAME' . $iter] . ($reposne['L_EMAIL' . $iter] ? ", " . $reposne['L_EMAIL' . $iter]: ""),
                        'status' => $reposne['L_STATUS' . $iter],
                    ];
                }
                $iter++;
                if ($iter > 100) break;
            } while(isset($reposne['L_STATUS'.$iter]));
        }
        return $found;
    }
}

class paypal_xmlParser {

    var $params = array(); //Stores the object representation of XML data
    var $root = NULL;
    var $global_index = -1;
    var $fold = false;

   /* Constructor for the class
    * Takes in XML data as input( do not include the <xml> tag
    */
    function paypal_xmlParser($input, $xmlParams=array(XML_OPTION_CASE_FOLDING => 0)) {
      $xmlp = xml_parser_create('utf-8');
      foreach($xmlParams as $opt => $optVal) {
        switch( $opt ) {
          case XML_OPTION_CASE_FOLDING:
            $this->fold = $optVal;
           break;
          default:
           break;
        }
        xml_parser_set_option($xmlp, $opt, $optVal);
      }

      if(xml_parse_into_struct($xmlp, $input, $vals, $index)) {
        $this->root = $this->_foldCase($vals[0]['tag']);
        $this->params = $this->xml2ary($vals);
      }
      xml_parser_free($xmlp);
    }

    function _foldCase($arg) {
      return( $this->fold ? strtoupper($arg) : $arg);
    }

/*
 * Credits for the structure of this function
 * http://mysrc.blogspot.com/2007/02/php-xml-to-array-and-backwards.html
 *
 * Adapted by Ropu - 05/23/2007
 *
 */
    function xml2ary($vals) {
        $mnary=array();
        $ary=&$mnary;
        foreach ($vals as $r) {
            $t=$r['tag'];
            if ($r['type']=='open') {
                if (isset($ary[$t]) && !empty($ary[$t])) {
                    if (isset($ary[$t][0])){
                      $ary[$t][]=array();
                    }
                    else {
                      $ary[$t]=array($ary[$t], array());
                    }
                    $cv=&$ary[$t][count($ary[$t])-1];
                }
                else {
                  $cv=&$ary[$t];
                }
                $cv=array();
                if (isset($r['attributes'])) {
                  foreach ($r['attributes'] as $k=>$v) {
                    $cv[$k]=$v;
                  }
                }

                $cv['_p']=&$ary;
                $ary=&$cv;

            } else if ($r['type']=='complete') {
                if (isset($ary[$t]) && !empty($ary[$t])) { // same as open
                    if (isset($ary[$t][0])) {
                      $ary[$t][]=array();
                    }
                    else {
                      $ary[$t]=array($ary[$t], array());
                    }
                    $cv=&$ary[$t][count($ary[$t])-1];
                }
                else {
                  $cv=&$ary[$t];
                }
                if (isset($r['attributes'])) {
                  foreach ($r['attributes'] as $k=>$v) {
                    $cv[$k]=$v;
                  }
                }
                $cv['VALUE'] = (isset($r['value']) ? $r['value'] : '');

            } elseif ($r['type']=='close') {
                $ary=&$ary['_p'];
            }
        }

        $this->_del_p($mnary);
        return $mnary;
    }

    // _Internal: Remove recursion in result array
    function _del_p(&$ary) {
        foreach ($ary as $k=>$v) {
            if ($k==='_p') {
              unset($ary[$k]);
            }
            else if(is_array($ary[$k])) {
              $this->_del_p($ary[$k]);
            }
        }
    }

    /* Returns the root of the XML data */
    function GetRoot() {
      return $this->root;
    }

    /* Returns the array representing the XML data */
    function GetData() {
      return $this->params;
    }
  }


function urlchecker(){
    $vars = func_get_args();
    $alert = '';
    if (!empty($vars[0])){
        $platform_id = (int)\Yii::$app->request->get('platform_id');
        if ($platform_id){
            $callback = \common\modules\orderPayment\paypalipn::$callback; 
            $url = \Yii::$app->get('platform')->getConfig($platform_id)->getCatalogBaseUrl(true);
            if ($url . $callback != $vars[0]){
                $alert = ' <span style="color:#ff0000">Current Notify Url differs from platform Url</span> - ' . $url . $callback;
            }
        }
    }
    return \yii\helpers\Html::textInput('configuration['.$vars[1].']', $vars[0]) . $alert;
}