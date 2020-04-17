<?php
/*
  $Id: paypal_pro_hs.php 10231 2017-12-05 17:16:37Z dbalagov $

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
use backend\services\OrdersService;
use common\classes\modules\TransactionalInterface;
use common\services\PaymentTransactionManager;

class paypal_pro_hs extends ModulePayment implements TransactionalInterface{

    var $code, $title, $description, $enabled, $ordersService;
    protected $defaultTranslationArray = [
        'MODULE_PAYMENT_PAYPAL_PRO_HS_TEXT_TITLE' => 'PayPal Payments Pro (Hosted Solution)',
        'MODULE_PAYMENT_PAYPAL_PRO_HS_TEXT_DESCRIPTION' => 'PayPal Payments Pro (Hosted Solution)',
        'MODULE_PAYMENT_PAYPAL_PRO_HS_DIALOG_CONNECTION_TITLE' => 'Test connection',
        'MODULE_PAYMENT_PAYPAL_PRO_HS_DIALOG_CONNECTION_BUTTON_CLOSE' => '<button type="button">Close</button>',
        'MODULE_PAYMENT_PAYPAL_PRO_HS_DIALOG_CONNECTION_SUCCESS' => 'success',
        'MODULE_PAYMENT_PAYPAL_PRO_HS_DIALOG_CONNECTION_FAILED' => 'failed',
        'MODULE_PAYMENT_PAYPAL_PRO_HS_DIALOG_CONNECTION_ERROR' => 'error',
        'MODULE_PAYMENT_PAYPAL_PRO_HS_DIALOG_CONNECTION_TIME' => 'Connection time:',
        'MODULE_PAYMENT_PAYPAL_PRO_HS_DIALOG_CONNECTION_LINK_TITLE' => 'Link',
        'MODULE_PAYMENT_PAYPAL_PRO_HS_DIALOG_CONNECTION_GENERAL_TEXT' => ''
    ];

    function __construct() {
        parent::__construct();

        global $PHP_SELF;
        $dev_ip = [
            '94.153.254.230',
        ];
        $this->signature = 'paypal|paypal_pro_hs|1.1|2.3';
        $this->api_version = '112';
        $this->dont_send_email = false;
        $this->enable_test_mode = false;
        $this->code = 'paypal_pro_hs';
        $this->title = MODULE_PAYMENT_PAYPAL_PRO_HS_TEXT_TITLE;
        $this->public_title = MODULE_PAYMENT_PAYPAL_PRO_HS_TEXT_TITLE;
        $this->description = MODULE_PAYMENT_PAYPAL_PRO_HS_TEXT_DESCRIPTION;
        $this->sort_order = defined('MODULE_PAYMENT_PAYPAL_PRO_HS_SORT_ORDER') ? MODULE_PAYMENT_PAYPAL_PRO_HS_SORT_ORDER : 0;
        $this->enabled = defined('MODULE_PAYMENT_PAYPAL_PRO_HS_STATUS') && (MODULE_PAYMENT_PAYPAL_PRO_HS_STATUS == 'True') ? true : false;
        $this->order_status = defined('MODULE_PAYMENT_PAYPAL_PRO_HS_ORDER_STATUS_ID') && ((int) MODULE_PAYMENT_PAYPAL_PRO_HS_ORDER_STATUS_ID > 0) ? (int) MODULE_PAYMENT_PAYPAL_PRO_HS_ORDER_STATUS_ID : 0;

        if (defined('MODULE_PAYMENT_PAYPAL_PRO_HS_STATUS')) {
            if (MODULE_PAYMENT_PAYPAL_PRO_HS_GATEWAY_SERVER == 'Sandbox') {
                $this->title .= ' [Sandbox]';
                $this->public_title .= ' (Sandbox)';
            }

            if (MODULE_PAYMENT_PAYPAL_PRO_HS_GATEWAY_SERVER == 'Live') {
                $this->api_url = 'https://api-3t.paypal.com/nvp';
            } else {
                $this->api_url = 'https://api-3t.sandbox.paypal.com/nvp';
            }

            $this->description .= $this->getTestLinkInfo();
        }

        if (!function_exists('curl_init')) {
            $this->description = '<div class="secWarning">' . MODULE_PAYMENT_PAYPAL_PRO_HS_ERROR_ADMIN_CURL . '</div>' . $this->description;

            $this->enabled = false;
        }

        if ($this->enabled === true) {
            if (!tep_not_null(MODULE_PAYMENT_PAYPAL_PRO_HS_ID) || !tep_not_null(MODULE_PAYMENT_PAYPAL_PRO_HS_API_USERNAME) || !tep_not_null(MODULE_PAYMENT_PAYPAL_PRO_HS_API_PASSWORD) || !tep_not_null(MODULE_PAYMENT_PAYPAL_PRO_HS_API_SIGNATURE)) {
                $this->description = '<div class="secWarning">' . MODULE_PAYMENT_PAYPAL_PRO_HS_ERROR_ADMIN_CONFIGURATION . '</div>' . $this->description;

                $this->enabled = false;
            }
        }
        
        if ($this->enabled === true) {
            $this->update_status();
        }

        if (defined('FILENAME_MODULES') && (basename($PHP_SELF) == FILENAME_MODULES) && isset($_GET['action']) && ($_GET['action'] == 'install') && isset($_GET['subaction']) && ($_GET['subaction'] == 'conntest')) {
            echo $this->getTestConnectionResult();
            exit;
        }
        
    }

    function update_status() {

        if (($this->enabled == true) && ((int) MODULE_PAYMENT_PAYPAL_PRO_HS_ZONE > 0)) {
            $check_flag = false;
            $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_PAYPAL_PRO_HS_ZONE . "' and zone_country_id = '" . $this->billing['country']['id'] . "' order by zone_id");
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

    function selection() {
        global $cart_PayPal_Pro_HS_ID;

        if (tep_session_is_registered('cart_PayPal_Pro_HS_ID')) {
            tep_session_unregister('cart_PayPal_Pro_HS_ID');
        }
        
        $selection = array('id' => $this->code,
                    'module' => $this->public_title,
                );
        if ($this->isWithoutConfirmation()){
            $selection['fields'] = [
                [
                    'title' => $this->tlPopupJS()
                ]
            ];
        }
        
        return $selection;
    }

    function pre_confirmation_check() {
        global $cart;

        if (empty($cart->cartID)) {
            $cart->cartID = $cart->generate_cart_id();
        }

        $this->manager->set('cartID', $cart->cartID);
    }

    function confirmation() {
        
        return ['title' => $this->createButton()->renderFrame()];
    }
    
    public function createButton(){
        global $cart_PayPal_Pro_HS_ID, $pphs_result, $cart;

        $pphs_result = array();
        $order = $this->manager->getOrderInstance();

        if ($this->manager->has('cartID')) {
            $cartID = $this->manager->get('cartID');
            $insert_order = false;

            if (tep_session_is_registered('cart_PayPal_Pro_HS_ID')) {
                $order_id = substr($cart_PayPal_Pro_HS_ID, strpos($cart_PayPal_Pro_HS_ID, '-') + 1);
            } else {
                $order_id = $this->saveOrder( 'TmpOrder');
                $cart_PayPal_Pro_HS_ID = $cartID . '-' . $order_id;
                tep_session_register('cart_PayPal_Pro_HS_ID');
            }

            $_shipping = $order->info['shipping_cost_exc_tax'];
            $_tax = $order->info['tax'];
            $_subtotal = $order->info['total_inc_tax'] - $_shipping - $_tax; //$order->info['subtotal_exc_tax'];
            
            $params = array('business' => MODULE_PAYMENT_PAYPAL_PRO_HS_ID,
                'bn' => 'HOLBIGROUPLTD_Cart_HSS',
                'buyer_email' => $order->customer['email_address'],
                'cancel_return' => tep_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'),
                'currency_code' => \Yii::$app->settings->get('currency'),
                'invoice' => $order_id,
                'custom' => $this->manager->getCustomerAssigned(),
                'paymentaction' => MODULE_PAYMENT_PAYPAL_PRO_HS_TRANSACTION_METHOD == 'Sale' ? 'sale' : 'authorization',
                'return' => $this->getCheckoutUrl([], self::PROCESS_PAGE),
                'notify_url' => Yii::$app->urlManager->createAbsoluteUrl(['callback/webhooks', 'set' => 'payment', 'module' => $this->code]),
                'shipping' => $this->format_raw($_shipping),
                'tax' => $this->format_raw($_tax),
                'subtotal' => $this->format_raw($_subtotal),
                'billing_first_name' => $order->billing['firstname'],
                'billing_last_name' => $order->billing['lastname'],
                'billing_address1' => $order->billing['street_address'],
                'billing_city' => $order->billing['city'],
                'billing_state' => $this->tep_get_zone_code($order->billing['country']['id'], $order->billing['zone_id'], $order->billing['state']),
                'billing_zip' => $order->billing['postcode'],
                'billing_country' => $order->billing['country']['iso_code_2'],
                'night_phone_b' => $order->customer['telephone'],
                'template' => 'templateD',
                'item_name' => STORE_NAME,
                'showBillingAddress' => 'false',
                'showShippingAddress' => 'false',
                'showHostedThankyouPage' => 'false');

            if ($this->manager->has('sendto') && is_numeric($this->manager->get('sendto')) && ($this->manager->get('sendto') > 0)) {
                $params['address_override'] = 'true';
                $params['first_name'] = $order->delivery['firstname'];
                $params['last_name'] = $order->delivery['lastname'];
                $params['address1'] = $order->delivery['street_address'];
                $params['city'] = $order->delivery['city'];
                $params['state'] = $this->tep_get_zone_code($order->delivery['country']['id'], $order->delivery['zone_id'], $order->delivery['state']);
                $params['zip'] = $order->delivery['postcode'];
                $params['country'] = $order->delivery['country']['iso_code_2'];
                /*        $params['lc'] = 'IT';
                  $params['locale'] = 'it_IT'; */
            }

            if (tep_not_null(MODULE_PAYMENT_PAYPAL_PRO_HS_TEXT_PAYPAL_RETURN_BUTTON) && (strlen(MODULE_PAYMENT_PAYPAL_PRO_HS_TEXT_PAYPAL_RETURN_BUTTON) <= 60)) {
                $params['cbt'] = MODULE_PAYMENT_PAYPAL_PRO_HS_TEXT_PAYPAL_RETURN_BUTTON;
            }

            $counter = 0;
            $additionalParams = ['BUTTONCODE' => 'TOKEN', 'BUTTONTYPE' => 'PAYMENT'];
            foreach ($params as $key => $value) {
                $additionalParams['L_BUTTONVAR' . $counter] =  $key . '=' . $value;
                $counter++;
            }
            
            $pphs_result = $this->setTransaction('BMCreateButton', $additionalParams);

            if (($pphs_result['ACK'] != 'Success') && ($pphs_result['ACK'] != 'SuccessWithWarning')) {
                $this->sendDebugEmail($pphs_result);
            }

            if (!tep_session_is_registered('pphs_result')) {
                tep_session_register('pphs_result');
            }
        }

        if (($pphs_result['ACK'] == 'Success' || $pphs_result['ACK'] == 'SuccessWithWarning') && isset($pphs_result['EMAILLINK'])) {
            if ($this->isWithoutConfirmation()){
                tep_redirect($pphs_result['EMAILLINK']);
            }
        } else {
            //fail
            if ($this->popUpMode()){
                echo $this->renderFrame();
                exit();
            } else {
                tep_redirect($this->getCheckoutUrl(['payment_error' => $this->code, 'error_mesage' => stripslashes($pphs_result['L_LONGMESSAGE0'])], self::PAYMENT_PAGE));
            }
        }
        return $this;
    }
    
    protected function renderFrame(){
        global $pphs_result;
        
        $error = false;
        
        if (($pphs_result['ACK'] != 'Success') && ($pphs_result['ACK'] != 'SuccessWithWarning')) {
            $error = true;
            $pphs_error_msg = $pphs_result['L_LONGMESSAGE0'];
        }
        if (!$error){
            $output = <<<EOD
            {$pphs_result['WEBSITECODE']}
            <script>
                tl(function(){
                    $('input[name=hosted_button_id]').next().hide();
                })
            </script>
EOD;
            if (!$this->isWithoutConfirmation()){
                $this->registerCallback("popUpIframe");
                \Yii::$app->getView()->registerJs($this->openPopupJS($pphs_result['EMAILLINK']));
            }
        } else {
            $output = <<<EOD
            {$pphs_result['L_LONGMESSAGE0']}
EOD;
        }
        return $output;
    }
    
    public function popUpMode() {
        return $this->isWithoutConfirmation() ? true : false;
    }
    
    protected function tlPopupJS() :string {
        $this->registerCallback("popUpIframe");
        \Yii::$app->getView()->registerJs($this->openPopupJS($this->getCheckoutUrl([], self::CONFIRMATION_PAGE)));
        return '';
    }
    
    function process_button() {
        return false;
    }
    
    public function call_webhooks(){
        if (isset($_POST['txn_id']) && !empty($_POST['txn_id'])) {
            try{
                $result = $this->getTransactionDetails($_POST['txn_id']);
                if (is_array($result) && isset($result['ACK']) && (($result['ACK'] == 'Success') || ($result['ACK'] == 'SuccessWithWarning'))) {
                    global $pphs_result;
                    $pphs_result = $result;

                    if ($this->verifyTransaction(true)){
                        echo 'ok';
                        exit();
                    }
                }
            } catch (\Exception $ex) {
                $this->sendDebugEmail($ex);
            }
        }
    }

    function before_process() {
        global $cart_PayPal_Pro_HS_ID, $pphs_result, $cart;

        $result = false;

        if (isset($_GET['tx']) && !empty($_GET['tx'])) { // direct payment (eg, credit card)
            $result = $this->getTransactionDetails($_GET['tx']);
        } elseif (isset($_POST['txn_id']) && !empty($_POST['txn_id'])) { // paypal payment
            $result = $this->getTransactionDetails($_POST['txn_id']);
        }

        if (!is_array($result) || !isset($result['ACK']) || (($result['ACK'] != 'Success') && ($result['ACK'] != 'SuccessWithWarning'))) {
            tep_redirect($this->getCheckoutUrl(['payment_error' => $this->code, 'error_mesage' => stripslashes($result['L_LONGMESSAGE0'])], self::PAYMENT_PAGE));
        }

        $order_id = substr($cart_PayPal_Pro_HS_ID, strpos($cart_PayPal_Pro_HS_ID, '-') + 1);
        
        $seller_accounts = array(MODULE_PAYMENT_PAYPAL_PRO_HS_ID);

        if (tep_not_null(MODULE_PAYMENT_PAYPAL_PRO_HS_PRIMARY_ID)) {
            $seller_accounts[] = MODULE_PAYMENT_PAYPAL_PRO_HS_PRIMARY_ID;
        }

        if (!isset($result['RECEIVERBUSINESS']) || !in_array($result['RECEIVERBUSINESS'], $seller_accounts) || ($result['INVNUM'] != $order_id) || ($result['CUSTOM'] != $this->manager->getCustomerAssigned())) {
            tep_redirect(tep_href_link(FILENAME_SHOPPING_CART));
        }

        $pphs_result = $result;
        
        $tx_order_id = $pphs_result['INVNUM'];
        $tx_customer_id = $pphs_result['CUSTOM'];
        
        $tmpOrder = $this->manager->getParentToInstanceWithId('\common\classes\TmpOrder', $pphs_result['INVNUM']);
        
        if (!is_object($tmpOrder) || ($tmpOrder->customer['id'] != $this->manager->getCustomerAssigned())){
             tep_redirect(tep_href_link(FILENAME_SHOPPING_CART));
        }
        parent::before_process();
        
        $tmpArModel = $tmpOrder->getARModel()->where(['orders_id' => $tx_order_id])->one();
        if ($tmpArModel->child_id){ //real order has been created
            $this->after_process(true);
        }
    }

    function after_process($skip = false) {
        global $pphs_result;
        if (!$skip){
            $tmpOrder = $this->manager->getParentToInstanceWithId('\common\classes\TmpOrder', $pphs_result['INVNUM']);
            $order = $this->manager->getOrderInstance();
            $tmpArModel = $tmpOrder->getARModel()->where(['orders_id' => $pphs_result['INVNUM']])->one();
            $tmpArModel->child_id = $order->order_id;
            $tmpArModel->save(false);
            $tmpOrder->setParent($order->order_id);
        }        
        
        $this->verifyTransaction();
        tep_session_unregister('cart_PayPal_Pro_HS_ID');
        tep_session_unregister('pphs_result');
        
        if ($skip){
            tep_redirect(tep_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL'));
        }
    }

    function get_error() {
        global $pphs_error_msg;

        $error = array('title' => MODULE_PAYMENT_PAYPAL_PRO_HS_ERROR_TITLE,
            'error' => MODULE_PAYMENT_PAYPAL_PRO_HS_ERROR_GENERAL);

        if (tep_session_is_registered('pphs_error_msg')) {
            $error['error'] = $pphs_error_msg;

            tep_session_unregister('pphs_error_msg');
        }

        return $error;
    }

    function sendTransactionToGateway($url, $parameters) {
        $server = parse_url($url);

        if (!isset($server['port'])) {
            $server['port'] = ($server['scheme'] == 'https') ? 443 : 80;
        }

        if (!isset($server['path'])) {
            $server['path'] = '/';
        }

        $curl = curl_init($server['scheme'] . '://' . $server['host'] . $server['path'] . (isset($server['query']) ? '?' . $server['query'] : ''));
        curl_setopt($curl, CURLOPT_PORT, $server['port']);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
        curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters);

        if (MODULE_PAYMENT_PAYPAL_PRO_HS_VERIFY_SSL == 'True') {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);

            if (file_exists(DIR_FS_CATALOG . 'ext/modules/payment/paypal/paypal.com.crt')) {
                curl_setopt($curl, CURLOPT_CAINFO, DIR_FS_CATALOG . 'ext/modules/payment/paypal/paypal.com.crt');
            } elseif (file_exists(DIR_FS_CATALOG . 'includes/cacert.pem')) {
                curl_setopt($curl, CURLOPT_CAINFO, DIR_FS_CATALOG . 'includes/cacert.pem');
            }
        } else {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }

        if (tep_not_null(MODULE_PAYMENT_PAYPAL_PRO_HS_PROXY)) {
            curl_setopt($curl, CURLOPT_HTTPPROXYTUNNEL, true);
            curl_setopt($curl, CURLOPT_PROXY, MODULE_PAYMENT_PAYPAL_PRO_HS_PROXY);
        }

        $result = curl_exec($curl);

        curl_close($curl);

        return $result;
    }

    // format prices without currency formatting
    function format_raw($number, $currency_code = '', $currency_value = '') {
        $currencies = \Yii::$container->get('currencies');

        if (empty($currency_code) || !$this->is_set($currency_code)) {
            $currency_code = \Yii::$app->settings->get('currency');
        }

        if (empty($currency_value) || !is_numeric($currency_value)) {
            $currency_value = $currencies->currencies[$currency_code]['value'];
        }

        return number_format(self::round($number * $currency_value, $currencies->currencies[$currency_code]['decimal_places']), $currencies->currencies[$currency_code]['decimal_places'], '.', '');
    }

    function getTestLinkInfo() {
        $dialog_title = MODULE_PAYMENT_PAYPAL_PRO_HS_DIALOG_CONNECTION_TITLE;
        $dialog_button_close = MODULE_PAYMENT_PAYPAL_PRO_HS_DIALOG_CONNECTION_BUTTON_CLOSE;
        $dialog_success = MODULE_PAYMENT_PAYPAL_PRO_HS_DIALOG_CONNECTION_SUCCESS;
        $dialog_failed = MODULE_PAYMENT_PAYPAL_PRO_HS_DIALOG_CONNECTION_FAILED;
        $dialog_error = MODULE_PAYMENT_PAYPAL_PRO_HS_DIALOG_CONNECTION_ERROR;
        $dialog_connection_time = MODULE_PAYMENT_PAYPAL_PRO_HS_DIALOG_CONNECTION_TIME;

        if (defined('FILENAME_MODULES')) {
            $test_url = tep_href_link(FILENAME_MODULES, 'set=payment&module=' . $this->code . '&action=install&subaction=conntest');
        }

// include jquery if it doesn't exist in the template
        $js = <<<EOD
<script>
if ( typeof jQuery == 'undefined' ) {
//console.log('jquery underfined');
  document.write('<scr' + 'ipt src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></scr' + 'ipt>');
  document.write('<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/themes/redmond/jquery-ui.css" />');
  document.write('<scr' + 'ipt src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/jquery-ui.min.js"></scr' + 'ipt>');
}
</script>

<script>
$(function() {
  $('#tcdprogressbar').progressbar({
    value: false
  });
});

function openTestConnectionDialog() {
  var d = $('<div>').html($('#testConnectionDialog').html()).dialog({
    modal: true,
    title: '{$dialog_title}',
    buttons: {
      '{$dialog_button_close}': function () {
        $(this).dialog('destroy');
      }
    }
  });

  var timeStart = new Date().getTime();

  $.ajax({
    url: '{$test_url}'
  }).done(function(data) {
    if ( data == '1' ) {
      d.find('#testConnectionDialogProgress').html('<p style="font-weight: bold; color: green;">{$dialog_success}</p>');
    } else {
      d.find('#testConnectionDialogProgress').html('<p style="font-weight: bold; color: red;">{$dialog_failed}</p>');
    }
  }).fail(function() {
    d.find('#testConnectionDialogProgress').html('<p style="font-weight: bold; color: red;">{$dialog_error}</p>');
  }).always(function() {
    var timeEnd = new Date().getTime();
    var timeTook = new Date(0, 0, 0, 0, 0, 0, timeEnd-timeStart);

    d.find('#testConnectionDialogProgress').append('<p>{$dialog_connection_time} ' + timeTook.getSeconds() + '.' + timeTook.getMilliseconds() + 's</p>');
  });
}
</script>
EOD;

        $info = '<p><img src="images/icons/locked.gif" border="0">&nbsp;<a href="javascript:openTestConnectionDialog();" style="text-decoration: underline; font-weight: bold;">' . MODULE_PAYMENT_PAYPAL_PRO_HS_DIALOG_CONNECTION_LINK_TITLE . '</a></p>' .
                '<div id="testConnectionDialog" style="display: none;"><p>';

        if (MODULE_PAYMENT_PAYPAL_PRO_HS_GATEWAY_SERVER == 'Live') {
            $info .= 'Live Server:<br />' . $this->api_url;
        } else {
            $info .= 'Sandbox Server:<br />' . $this->api_url;
        }

        $info .= '</p><div id="testConnectionDialogProgress"><p>' . MODULE_PAYMENT_PAYPAL_PRO_HS_DIALOG_CONNECTION_GENERAL_TEXT . '</p><div id="tcdprogressbar"></div></div></div>' .
                $js;

        return $info;
    }

    function getTestConnectionResult() {
        $params = array('USER' => MODULE_PAYMENT_PAYPAL_PRO_HS_API_USERNAME,
            'PWD' => MODULE_PAYMENT_PAYPAL_PRO_HS_API_PASSWORD,
            'SIGNATURE' => MODULE_PAYMENT_PAYPAL_PRO_HS_API_SIGNATURE,
            'VERSION' => $this->api_version,
            'METHOD' => 'BMCreateButton');

        $post_string = '';

        foreach ($params as $key => $value) {
            $post_string .= $key . '=' . urlencode(utf8_encode(trim($value))) . '&';
        }

        $post_string = substr($post_string, 0, -1);

        $response = $this->sendTransactionToGateway($this->api_url, $post_string);

        $response_array = array();
        parse_str($response, $response_array);

        if (is_array($response_array) && isset($response_array['ACK'])) {
            return 1;
        }

        return -1;
    }
    
    public function getTransactionDetails($transaction_id, PaymentTransactionManager $tManager = null){
        $response_array = $this->setTransaction('GetTransactionDetails', [ 'TRANSACTIONID' => $transaction_id]);

        if (($response_array['ACK'] != 'Success') && ($response_array['ACK'] != 'SuccessWithWarning')) {
            $this->sendDebugEmail($response_array);
        } else if ($tManager) {
            $tManager->updateTransactionFromPayment($transaction_id, $response_array['PAYMENTSTATUS'], $response_array['AMT']);
        }

        return $response_array;
    }
    
    function setTransaction($method, array $additionalParams) {

        $params = array_merge(array('USER' => MODULE_PAYMENT_PAYPAL_PRO_HS_API_USERNAME,
            'PWD' => MODULE_PAYMENT_PAYPAL_PRO_HS_API_PASSWORD,
            'SIGNATURE' => MODULE_PAYMENT_PAYPAL_PRO_HS_API_SIGNATURE,
            'VERSION' => $this->api_version,
            'METHOD' => $method,
        ), $additionalParams);
        
        $post_string = '';

        foreach ($params as $key => $value) {
            $post_string .= $key . '=' . urlencode(utf8_encode(trim($value))) . '&';
        }

        $post_string = substr($post_string, 0, -1);

        $response = $this->sendTransactionToGateway($this->api_url, $post_string);

        $response_array = array();
        parse_str($response, $response_array);

        return $response_array;
    }

    public function canRefund($transaction_id){
        $response = $this->getTransactionDetails($transaction_id);
        if ($response['ACK'] == 'Success' || $response['ACK'] == 'SuccessWithWarning') {
            return !in_array($response['PAYMENTSTATUS'], ['Refunded']);
        }
        return false;
    }
    
    public function refund($transaction_id, $amount = 0){
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
                return true;
            } catch (\Exception $ex) {
                $this->sendDebugEmail($ex);
            }
        }
        return false;
    }
    
    public function canVoid($transaction_id){
        return false;
    }
    
    public function void($transaction_id){
        return false;
    }

    function verifyTransaction($is_ipn = false) {
        global $pphs_result, $languages_id;
        \common\helpers\Translation::init('payment');
        \common\helpers\Translation::init('main');

        $currencies = \Yii::$container->get('currencies');

        $payment_title = false;
        $tx_order_id = $pphs_result['INVNUM'];//only tmpOrder ID here
        $tx_customer_id = $pphs_result['CUSTOM'];
        $tx_transaction_id = $pphs_result['TRANSACTIONID'];
        $tx_payment_status = $pphs_result['PAYMENTSTATUS'];
        $tx_payer_status = $pphs_result['PAYERSTATUS'];
        $tx_amount = $pphs_result['AMT'];
        $tx_currency = $pphs_result['CURRENCYCODE'];
        $tx_pending_reason = (isset($pphs_result['PENDINGREASON'])) ? $pphs_result['PENDINGREASON'] : null;
        $tx_reason_code = (isset($pphs_result['REASONCODE'])) ? $pphs_result['REASONCODE'] : null;

        if (is_numeric($tx_order_id) && ($tx_order_id > 0) && is_numeric($tx_customer_id) && ($tx_customer_id > 0)) {
            if ($this->manager->isInstance()){//customer returned by default
                //
            } else { //probably IPN
                $tmpOrder = $this->manager->getParentToInstanceWithId('\common\classes\TmpOrder', $tx_order_id);
                if ($tmpOrder){
                    $tmpArModel = $tmpOrder->getARModel()->where(['orders_id' => $tx_order_id])->one();
                    if (!$tmpArModel->child_id){ //real order hasn't been created
                        $order_id = $tmpOrder->createOrder();
                    }
                }
            }
            $order = $this->manager->getOrderInstance();
            if ($order){
                try{

                    $invoice_id = $this->manager->getOrderSplitter()->getInvoiceId();
                    $this->manager->getTransactionManager($this)
                                ->addTransaction($tx_transaction_id, $tx_payment_status, $tx_amount, $invoice_id, 'Customer\'s payment');

                    
                    $this->ordersService = \Yii::createObject(OrdersService::class);
                    $comment_status = 'Transaction ID: ' . $tx_transaction_id . '; ' .
                    $tx_payment_status . ' (' . ucfirst($tx_payer_status) . '; ' . $currencies->format($tx_amount, false, $tx_currency) . ')';

                    if ($tx_payment_status == 'Pending') {
                        $comment_status .= '; ' . $tx_pending_reason;
                    } elseif (($tx_payment_status == 'Reversed') || ($tx_payment_status == 'Refunded')) {
                        $comment_status .= '; ' . $tx_reason_code;
                    }
                    
                    if ($tx_amount != number_format($order->info['total_inc_tax'] * $order->info['currency_value'], $currencies->get_decimal_places($order->info['currency']))) {
                        $comment_status .= '; PayPal transaction value (' . $tx_amount . ') does not match order value (' . number_format($order->info['total_inc_tax'] * $order->info['currency_value'], $currencies->get_decimal_places($order->info['currency'])) . ')';
                    } elseif ($tx_payment_status == 'Completed') {
                        $new_order_status = (MODULE_PAYMENT_PAYPAL_PRO_HS_ORDER_STATUS_ID > 0 ? MODULE_PAYMENT_PAYPAL_PRO_HS_ORDER_STATUS_ID : $order->info['orders_status']);
                    }
                    $orderStatus = 0;
                    if ($is_ipn === true) {
                        $source = 'PayPal IPN Verified';
                        $orderStatus = (int) $new_order_status;
                    } else {
                        $source = 'PayPal Verified';
                        $orderStatus = MODULE_PAYMENT_PAYPAL_PRO_HS_TRANSACTIONS_ORDER_STATUS_ID;
                    }
                    $orARModel = $this->ordersService->getById($order->order_id);
                    $this->ordersService->changeStatus($orARModel, $orderStatus, $comment_status);
                    unset($orARModel);
                    return true;
                } catch (\Exception $ex) {
                    return false;
                }
            }
        }
        return false;
    }

    function sendDebugEmail($response = array()) {
        if (tep_not_null(MODULE_PAYMENT_PAYPAL_PRO_HS_DEBUG_EMAIL)) {
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
                \common\helpers\Mail::send('Debug Letter', MODULE_PAYMENT_PAYPAL_PRO_HS_DEBUG_EMAIL, 'PayPal Payments Pro (Hosted Solution) Debug E-Mail', $email_body, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS
                );
            }
        }
    }

    function tep_output_string_protected($string) {
        return \common\helpers\Output::output_string_protected($string);
    }

    public function describe_status_key() {
        return new ModuleStatus('MODULE_PAYMENT_PAYPAL_PRO_HS_STATUS', 'True', 'False');
    }

    public function describe_sort_key() {
        return new ModuleSortOrder('MODULE_PAYMENT_PAYPAL_PRO_HS_SORT_ORDER');
    }

    public function configure_keys() {
        $tx_status_id = defined('MODULE_PAYMENT_PAYPAL_PRO_HS_TRANSACTIONS_ORDER_STATUS_ID') ? MODULE_PAYMENT_PAYPAL_PRO_HS_TRANSACTIONS_ORDER_STATUS_ID : $this->getDefaultOrderStatusId();
        $status_id_an = defined('MODULE_PAYMENT_PAYPAL_PRO_HS_ORDER_STATUS_ID') ? MODULE_PAYMENT_PAYPAL_PRO_HS_ORDER_STATUS_ID : $this->getDefaultOrderStatusId();
        return array(
            'MODULE_PAYMENT_PAYPAL_PRO_HS_STATUS' => array('title' => 'Enable PayPal Payments Pro (Hosted Solution)',
                'desc' => 'Do you want to accept PayPal Payments Pro (Hosted Solution) payments?',
                'value' => 'True',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '),
            'MODULE_PAYMENT_PAYPAL_PRO_HS_API_USERNAME' => array('title' => 'API Username',
                'desc' => 'The username to use for the PayPal API service.'),
            'MODULE_PAYMENT_PAYPAL_PRO_HS_API_PASSWORD' => array('title' => 'API Password',
                'desc' => 'The password to use for the PayPal API service.'),
            'MODULE_PAYMENT_PAYPAL_PRO_HS_API_SIGNATURE' => array('title' => 'API Signature',
                'desc' => 'The signature to use for the PayPal API service.'),
            'MODULE_PAYMENT_PAYPAL_PRO_HS_ID' => array('title' => 'Seller E-Mail Address',
                'desc' => 'The PayPal seller e-mail address to accept payments for'),
            'MODULE_PAYMENT_PAYPAL_PRO_HS_PRIMARY_ID' => array('title' => 'Primary E-Mail Address',
                'desc' => 'The primary PayPal seller e-mail address to validate transactions with (leave empty if it is the same as the Seller E-Mail Address)'),
            'MODULE_PAYMENT_PAYPAL_PRO_HS_TRANSACTION_METHOD' => array('title' => 'Transaction Method',
                'desc' => 'The processing method to use for each transaction.',
                'value' => 'Sale',
                'set_function' => 'tep_cfg_select_option(array(\'Authorization\', \'Sale\'), '),
            'MODULE_PAYMENT_PAYPAL_PRO_HS_ORDER_STATUS_ID' => array('title' => 'Set PayPal Acknowledged Order Status',
                'desc' => 'Set the status of orders made with this payment module to this value',
                'value' => $status_id_an,
                'set_function' => 'tep_cfg_pull_down_order_statuses(',
                'use_function' => '\common\helpers\Order::get_order_status_name'),
            'MODULE_PAYMENT_PAYPAL_PRO_HS_TRANSACTIONS_ORDER_STATUS_ID' => array('title' => 'PayPal Transactions Order Status Level',
                'desc' => 'Include PayPal transaction information in this order status level.',
                'value' => $tx_status_id,
                'use_function' => '\common\helpers\Order::get_order_status_name',
                'set_function' => 'tep_cfg_pull_down_order_statuses('),
            'MODULE_PAYMENT_PAYPAL_PRO_HS_ZONE' => array('title' => 'Payment Zone',
                'desc' => 'If a zone is selected, only enable this payment method for that zone.',
                'value' => '0',
                'set_function' => 'tep_cfg_pull_down_zone_classes(',
                'use_function' => '\common\helpers\Zones::get_zone_class_title'),
            'MODULE_PAYMENT_PAYPAL_PRO_HS_GATEWAY_SERVER' => array('title' => 'Gateway Server',
                'desc' => 'Use the testing (sandbox) or live gateway server for transactions?',
                'value' => 'Live',
                'set_function' => 'tep_cfg_select_option(array(\'Live\', \'Sandbox\'), '),
            'MODULE_PAYMENT_PAYPAL_PRO_HS_VERIFY_SSL' => array('title' => 'Verify SSL Certificate',
                'desc' => 'Verify gateway server SSL certificate on connection?',
                'value' => 'True',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '),
            'MODULE_PAYMENT_PAYPAL_PRO_HS_PROXY' => array('title' => 'Proxy Server',
                'desc' => 'Send API requests through this proxy server. (host:port, eg: 123.45.67.89:8080 or proxy.example.com:8080)'),
            'MODULE_PAYMENT_PAYPAL_PRO_HS_DEBUG_EMAIL' => array('title' => 'Debug E-Mail Address',
                'desc' => 'All parameters of an invalid transaction will be sent to this email address.'),
            'MODULE_PAYMENT_PAYPAL_PRO_HS_SORT_ORDER' => array('title' => 'Sort order of display.',
                'desc' => 'Sort order of display. Lowest is displayed first.',
                'value' => '0')
        );
    }

    function tep_create_random_value($length, $type = 'mixed') {
        return \common\helpers\Password::create_random_value($length, $type);
    }

    function tep_get_zone_code($country_id, $zone_id, $default_zone) {
        return \common\helpers\Zones::get_zone_code($country_id, $zone_id, $default_zone);
    }

    function isOnline() {
        return true;
    }

}
