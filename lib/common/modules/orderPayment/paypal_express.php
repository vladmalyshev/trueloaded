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

  class paypal_express extends ModulePayment{
    var $code, $title, $description, $enabled;

    protected $defaultTranslationArray = [
        'MODULE_PAYMENT_PAYPAL_EXPRESS_TEXT_TITLE' => 'PayPal Express Checkout',
        'MODULE_PAYMENT_PAYPAL_EXPRESS_TEXT_PUBLIC_TITLE' => 'PayPal (including Credit and Debit Cards)',
        'MODULE_PAYMENT_PAYPAL_EXPRESS_TEXT_DESCRIPTION' => '<img src="images/icon_info.gif" border="0" />&nbsp;<a href="http://library.oscommerce.com/Package&en&paypal&oscom23&express_checkout" target="_blank" style="text-decoration: underline; font-weight: bold;">View Online Documentation</a><br /><br /><img src="images/icon_popup.gif" border="0" />&nbsp;<a href="https://www.paypal.com" target="_blank" style="text-decoration: underline; font-weight: bold;">Visit PayPal Website</a>',
        'MODULE_PAYMENT_PAYPAL_EXPRESS_ERROR_ADMIN_CURL' => 'This module requires cURL to be enabled in PHP and will not load until it has been enabled on this webserver.',
        'MODULE_PAYMENT_PAYPAL_EXPRESS_ERROR_ADMIN_CONFIGURATION' => 'This module will not load until the Seller Account or API Credential parameters have been configured. Please edit and configure the settings of this module.',
        'MODULE_PAYMENT_PAYPAL_EXPRESS_TEXT_BUTTON' => 'Check Out with PayPal',
        'MODULE_PAYMENT_PAYPAL_EXPRESS_TEXT_COMMENTS' => 'Comments:',
        'MODULE_PAYMENT_PAYPAL_EXPRESS_EMAIL_PASSWORD' => 'An account has automatically been created for you with the following e-mail address and password:' . "\n\n" . 'Store Account E-Mail Address: %s' . "\n" . 'Store Account Password: %s' . "\n\n",
        'MODULE_PAYMENT_PAYPAL_EXPRESS_BUTTON' => 'https://www.paypalobjects.com/webstatic/en_US/btn/btn_checkout_pp_142x27.png',
        'MODULE_PAYMENT_PAYPAL_EXPRESS_LANGUAGE_LOCALE' => 'en_US',
        'MODULE_PAYMENT_PAYPAL_EXPRESS_DIALOG_CONNECTION_LINK_TITLE' => 'Test API Server Connection',
        'MODULE_PAYMENT_PAYPAL_EXPRESS_DIALOG_CONNECTION_TITLE' => 'API Server Connection Test',
        'MODULE_PAYMENT_PAYPAL_EXPRESS_DIALOG_CONNECTION_GENERAL_TEXT' => 'Testing connection to server..',
        'MODULE_PAYMENT_PAYPAL_EXPRESS_DIALOG_CONNECTION_BUTTON_CLOSE' => 'Close',
        'MODULE_PAYMENT_PAYPAL_EXPRESS_DIALOG_CONNECTION_TIME' => 'Connection Time:',
        'MODULE_PAYMENT_PAYPAL_EXPRESS_DIALOG_CONNECTION_SUCCESS' => 'Success!',
        'MODULE_PAYMENT_PAYPAL_EXPRESS_DIALOG_CONNECTION_FAILED' => 'Failed! Please review the Verify SSL Certificate settings and try again.',
        'MODULE_PAYMENT_PAYPAL_EXPRESS_DIALOG_CONNECTION_ERROR' => 'An error occurred. Please refresh the page, review your settings, and try again.',
        'MODULE_PAYMENT_PAYPAL_EXPRESS_ERROR_NO_SHIPPING_AVAILABLE_TO_SHIPPING_ADDRESS' => 'Shipping is currently not available for the selected shipping address. Please select or create a new shipping address to use with your purchase.',
        'MODULE_PAYMENT_PAYPAL_EXPRESS_WARNING_LOCAL_LOGIN_REQUIRED' => 'Please log into your account to verify the order.',
        'MODULE_PAYMENT_PAYPAL_EXPRESS_NOTICE_CHECKOUT_CONFIRMATION' => 'Please review and confirm your order below. Your order will not be processed until it has been confirmed.'
    ];

    function __construct() {
      parent::__construct();

      global $PHP_SELF, $payment;

      $this->signature = 'paypal|paypal_express|3.1|2.3';
      $this->api_version = '112';

      $this->code = 'paypal_express';
      $this->title = MODULE_PAYMENT_PAYPAL_EXPRESS_TEXT_TITLE;
      $this->public_title = MODULE_PAYMENT_PAYPAL_EXPRESS_TEXT_PUBLIC_TITLE;
      $this->description = MODULE_PAYMENT_PAYPAL_EXPRESS_TEXT_DESCRIPTION;
      $this->sort_order = defined('MODULE_PAYMENT_PAYPAL_EXPRESS_SORT_ORDER') ? MODULE_PAYMENT_PAYPAL_EXPRESS_SORT_ORDER : 0;
      $this->enabled = defined('MODULE_PAYMENT_PAYPAL_EXPRESS_STATUS') && (MODULE_PAYMENT_PAYPAL_EXPRESS_STATUS == 'True') ? true : false;
      $this->order_status = defined('MODULE_PAYMENT_PAYPAL_EXPRESS_ORDER_STATUS_ID') && ((int)MODULE_PAYMENT_PAYPAL_EXPRESS_ORDER_STATUS_ID > 0) ? (int)MODULE_PAYMENT_PAYPAL_EXPRESS_ORDER_STATUS_ID : 0;
      $this->online = true;

      if ( defined('MODULE_PAYMENT_PAYPAL_EXPRESS_STATUS') ) {
        if ( MODULE_PAYMENT_PAYPAL_EXPRESS_TRANSACTION_SERVER == 'Sandbox' ) {
          $this->title .= ' [Sandbox]';
          $this->public_title .= ' (' . $this->code . '; Sandbox)';
        }

        $this->description .= $this->getTestLinkInfo();
      }

      if ( !function_exists('curl_init') ) {
        $this->description = '<div class="secWarning">' . MODULE_PAYMENT_PAYPAL_EXPRESS_ERROR_ADMIN_CURL . '</div>' . $this->description;

        $this->enabled = false;
      }

      if ( $this->enabled === true ) {
        if ( !tep_not_null(MODULE_PAYMENT_PAYPAL_EXPRESS_SELLER_ACCOUNT) && !tep_not_null(MODULE_PAYMENT_PAYPAL_EXPRESS_API_USERNAME) ) {
          $this->description = '<div class="secWarning">' . MODULE_PAYMENT_PAYPAL_EXPRESS_ERROR_ADMIN_CONFIGURATION . '</div>' . $this->description;

          $this->enabled = false;
        }
      }

      if ( $this->enabled === true ) {
        $this->update_status();
      }

// When changing the shipping address due to no shipping rates being available, head straight to the checkout confirmation page
      if ( defined('FILENAME_CHECKOUT_PAYMENT') && (basename($PHP_SELF) == FILENAME_CHECKOUT_PAYMENT) && tep_session_is_registered('ppec_right_turn') ) {
        tep_session_unregister('ppec_right_turn');

        if ( tep_session_is_registered('payment') && ($payment == $this->code) ) {
          tep_redirect(tep_href_link(FILENAME_CHECKOUT_CONFIRMATION, '', 'SSL'));
        }
      }

      if ( defined('FILENAME_MODULES') && (basename($PHP_SELF) == FILENAME_MODULES) && isset($_GET['action']) && ($_GET['action'] == 'install') && isset($_GET['subaction']) && ($_GET['subaction'] == 'conntest') ) {
        echo $this->getTestConnectionResult();
        exit;
      }
    }

    function update_status() {

      if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_PAYPAL_EXPRESS_ZONE > 0) ) {
        $check_flag = false;
        $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_PAYPAL_EXPRESS_ZONE . "' and zone_country_id = '" . $this->delivery['country']['id'] . "' order by zone_id");
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

    public function toObserve($script = true){
        if ($script){
            echo '<div style="padding:25px;text-align:center;">Now you will be redirected to '.$this->title.'...</div><script>window.location.href="' . $this->getObserveUrl() . '"</script>';
        }
    }

    public function getObserveUrl(){
        return tep_href_link('callback/paypal-express', '', 'SSL');
    }

    public function checkButtonOnProduct(){
        return defined('MODULE_PAYMENT_PAYPAL_EXPRESS_BUY_IMMEDIATELLY') && MODULE_PAYMENT_PAYPAL_EXPRESS_BUY_IMMEDIATELLY == 'True';
    }

    function checkout_initialization_method() {
      global $cart, $order;
      static $cnt = 0; //for buttons on listings
      $cnt++;

      if (MODULE_PAYMENT_PAYPAL_EXPRESS_CHECKOUT_IMAGE == 'Dynamic') {
        if (MODULE_PAYMENT_PAYPAL_EXPRESS_TRANSACTION_SERVER == 'Live') {
          $image_button = 'https://fpdbs.paypal.com/dynamicimageweb?cmd=_dynamic-image';
        } else {
          $image_button = 'https://fpdbs.sandbox.paypal.com/dynamicimageweb?cmd=_dynamic-image';
        }

        $params = array('locale=' . MODULE_PAYMENT_PAYPAL_EXPRESS_LANGUAGE_LOCALE);

        if (tep_not_null(MODULE_PAYMENT_PAYPAL_EXPRESS_API_USERNAME)) {
          $response_array = $this->getPalDetails();

          if (isset($response_array['PAL'])) {
            $params[] = 'pal=' . $response_array['PAL'];
            $params[] = 'ordertotal=' . $this->format_raw($cart->show_total());
          }
        }

        if (!empty($params)) {
          $image_button .= '&' . implode('&', $params);
        }
      } else {
        $image_button = MODULE_PAYMENT_PAYPAL_EXPRESS_BUTTON;
      }

      $button_title = \common\helpers\Output::output_string_protected(MODULE_PAYMENT_PAYPAL_EXPRESS_TEXT_BUTTON);

      if ( MODULE_PAYMENT_PAYPAL_EXPRESS_TRANSACTION_SERVER == 'Sandbox' ) {
        $button_title .= ' (' . $this->code . '; Sandbox)';
      }

      $string = '<a rel="nofollow" href="' . $this->getObserveUrl() . '" class="paypal_buy btn-to-checkout"><img src="' . $image_button . '" border="0" alt="" title="' . $button_title . '" /></a>';
      if ($cnt==1) {
        $string .= $this->checkout_initialization_method_js();
      }

      return $string;
    }

    function checkout_initialization_method_js() {
        return <<<EOD
   <script>
   tl(function(){
       $('.paypal_buy').on('click', function(e){
           var that = this;
           if ($(that).parents('form').attr('name') == 'cart_quantity' || $(that).parents('form').hasClass('form-buy')){
               e.preventDefault();
               $(that).parents('form').append('<input type="hidden" name="purchase" value="paypal_express">').submit();
           }
       })
   });
   </script>
EOD;
    }

    function javascript_validation() {
      return false;
    }

    function selection() {
        return false;
        return array('id' => $this->code,
                   'module' => $this->public_title);
    }

    function pre_confirmation_check() {
      global $ppe_token, $ppe_secret;

      if (!tep_session_is_registered('ppe_token')) {
        tep_redirect(tep_href_link('callback/paypal-express', '', 'SSL'));
      }

      $response_array = $this->getExpressCheckoutDetails($ppe_token);

      if ( ($response_array['ACK'] != 'Success') && ($response_array['ACK'] != 'SuccessWithWarning') ) {
        tep_redirect(tep_href_link(FILENAME_SHOPPING_CART, 'error_message=' . stripslashes($response_array['L_LONGMESSAGE0']), 'SSL'));
      } elseif ( !tep_session_is_registered('ppe_secret') || ($response_array['PAYMENTREQUEST_0_CUSTOM'] != $ppe_secret) ) {
        tep_redirect(tep_href_link(FILENAME_SHOPPING_CART, '', 'SSL'));
      }

      if ( tep_session_is_registered('ppe_order_total_check') ) {
        \Yii::$container->get('message_stack')->add('<span id="PayPalNotice">' . MODULE_PAYMENT_PAYPAL_EXPRESS_NOTICE_CHECKOUT_CONFIRMATION . '</span><script>$("#PayPalNotice").parent().css({backgroundColor: "#fcf8e3", border: "1px #faedd0 solid", color: "#a67d57", padding: "5px" });</script>', 'checkout_confirmation', 'paypal');
      }

      $this->manager->getOrderInstance()->info['payment_method'] = '<img src="https://www.paypalobjects.com/webstatic/mktg/Logo/pp-logo-100px.png" border="0" alt="PayPal Logo" style="padding: 3px;" />';
    }

    function confirmation() {

      $comments = $this->manager->get('comments');

      if (!isset($comments)) {
        $comments = null;
      }

      $confirmation = false;

      if (empty($comments)) {
        $confirmation = array('fields' => array(array('title' => MODULE_PAYMENT_PAYPAL_EXPRESS_TEXT_COMMENTS,
                                                      'field' => tep_draw_textarea_field('ppecomments', 'soft', '60', '5', $comments))));
      }

      return $confirmation;
    }

    function process_button() {
      return false;
    }

    function before_process() {
      global $ppe_token, $ppe_payerid, $ppe_secret, $ppe_order_total_check, $response_array;
      //$order, $sendto,  $comments,

      if (!tep_session_is_registered('ppe_token')) {
        tep_redirect(tep_href_link('callback/paypal-express', '', 'SSL'));
      }

      $response_array = $this->getExpressCheckoutDetails($ppe_token);
      $order =  $this->manager->getOrderInstance();
      $comments = $this->manager->get('comments');

      if (($response_array['ACK'] == 'Success') || ($response_array['ACK'] == 'SuccessWithWarning')) {
        if ( !tep_session_is_registered('ppe_secret') || ($response_array['PAYMENTREQUEST_0_CUSTOM'] != $ppe_secret) ) {
          tep_redirect(tep_href_link(FILENAME_SHOPPING_CART, '', 'SSL'));
        } elseif ( ($response_array['PAYMENTREQUEST_0_AMT'] != $this->format_raw($order->info['total'])) && !tep_session_is_registered('ppe_order_total_check') ) {
          tep_session_register('ppe_order_total_check');
          $ppe_order_total_check = true;

          tep_redirect(tep_href_link(FILENAME_CHECKOUT_CONFIRMATION, '', 'SSL'));
        }
      } else {
        tep_redirect(tep_href_link(FILENAME_SHOPPING_CART, 'error_message=' . stripslashes($response_array['L_LONGMESSAGE0']), 'SSL'));
      }

      if ( tep_session_is_registered('ppe_order_total_check') ) {
        tep_session_unregister('ppe_order_total_check');
      }

      if (empty($comments)) {
        if (isset($_POST['ppecomments']) && tep_not_null($_POST['ppecomments'])) {
          $comments = tep_db_prepare_input($_POST['ppecomments']);
          $this->manager->set('comments', $comments);

          $order->info['comments'] = $comments;
        }
      }

      $params = array('TOKEN' => $ppe_token,
                      'PAYERID' => $ppe_payerid,
                      'PAYMENTREQUEST_0_AMT' => $this->format_raw($order->info['total']),
                      'PAYMENTREQUEST_0_CURRENCYCODE' => $order->info['currency']);

      if ($this->manager->get('sendto')) {
        $params['PAYMENTREQUEST_0_SHIPTONAME'] = $order->delivery['firstname'] . ' ' . $order->delivery['lastname'];
        $params['PAYMENTREQUEST_0_SHIPTOSTREET'] = $order->delivery['street_address'];
        $params['PAYMENTREQUEST_0_SHIPTOCITY'] = $order->delivery['city'];
        $params['PAYMENTREQUEST_0_SHIPTOSTATE'] = \common\helpers\Zones::get_zone_code($order->delivery['country']['id'], $order->delivery['zone_id'], $order->delivery['state']);
        $params['PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE'] = $order->delivery['country']['iso_code_2'];
        $params['PAYMENTREQUEST_0_SHIPTOZIP'] = $order->delivery['postcode'];
      }

      $response_array = $this->doExpressCheckoutPayment($params);

      if (($response_array['ACK'] != 'Success') && ($response_array['ACK'] != 'SuccessWithWarning')) {
        if ( $response_array['L_ERRORCODE0'] == '10486' ) {

            $paypal_url = $this->getUrl() . 'token=' . $ppe_token . '&useraction=commit';

            tep_redirect($paypal_url);
        }

        tep_redirect(tep_href_link(FILENAME_SHOPPING_CART, 'error_message=' . stripslashes($response_array['L_LONGMESSAGE0']), 'SSL'));
      }
      $order->update_piad_information(true);
    }

    public function getUrl(){
        if (MODULE_PAYMENT_PAYPAL_EXPRESS_TRANSACTION_SERVER == 'Live') {
            $paypal_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&';
        } else {
            $paypal_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout&';
        }
        return $paypal_url;
    }

    function after_process() {
      global $response_array, $ppe_payerstatus, $ppe_addressstatus;

      $order = $this->manager->getOrderInstance();

      if ((int)MODULE_PAYMENT_PAYPAL_EXPRESS_CANCEL_ORDER_STATUS_ID){
            $cancelStatus = (int)MODULE_PAYMENT_PAYPAL_EXPRESS_CANCEL_ORDER_STATUS_ID;
            $check_transaction = (new \yii\db\Query)->select('orders_id')->from('paypal_checkout')
                    ->where(['txn_signature' => $response_array['PAYMENTINFO_0_TRANSACTIONID']])->one();
            if ($check_transaction){
                $prevOrder = \common\models\Orders::findOne(['orders_id' => $check_transaction['orders_id']]);
                if ($prevOrder){
                    $prevOrder->changeStatus($cancelStatus);
                    $sHistory = \common\models\OrdersStatusHistory::create($prevOrder->orders_id, $cancelStatus, false, "Doubled Order for {$order->order_id}");
                    $sHistory->save(false);
                }
            }
            \Yii::$app->getDb()
                    ->createCommand("insert ignore into paypal_checkout (orders_id, txn_signature) values( :oId, :sig) ", [
                        ':oId' => $order->order_id, ':sig' => $response_array['PAYMENTINFO_0_TRANSACTIONID']])->execute();
      }

      $tx_transaction_id = $response_array['PAYMENTINFO_0_TRANSACTIONID'];
      $pp_result = 'Transaction ID: ' . \common\helpers\Output::output_string_protected($response_array['PAYMENTINFO_0_TRANSACTIONID']) . "\n" .
                   'Payer Status: ' . \common\helpers\Output::output_string_protected($ppe_payerstatus) . "\n" .
                   'Address Status: ' . \common\helpers\Output::output_string_protected($ppe_addressstatus) . "\n\n" .
                   'Payment Status: ' . \common\helpers\Output::output_string_protected($response_array['PAYMENTINFO_0_PAYMENTSTATUS']) . "\n" .
                   'Payment Type: ' . \common\helpers\Output::output_string_protected($response_array['PAYMENTINFO_0_PAYMENTTYPE']) . "\n" .
                   'Pending Reason: ' . \common\helpers\Output::output_string_protected($response_array['PAYMENTINFO_0_PENDINGREASON']) . "\n" .
                   'Reversal Code: ' . \common\helpers\Output::output_string_protected($response_array['PAYMENTINFO_0_REASONCODE']);

      $set_new_order_status = (int)MODULE_PAYMENT_PAYPAL_EXPRESS_TRANSACTIONS_ORDER_STATUS_ID;
      if ( $set_new_order_status==0 ) {
          $get_current_status_r = tep_db_query(
              "SELECT orders_status FROM ".TABLE_ORDERS." WHERE orders_id='".(int)$order->order_id."'"
          );
          if ( tep_db_num_rows($get_current_status_r)>0 ) {
              $_current_status = tep_db_fetch_array($get_current_status_r);
              $set_new_order_status = $_current_status['orders_status'];
          }
      }
      $sql_data_array = array('orders_id' => $order->order_id,
                              'orders_status_id' => $set_new_order_status,
                              'date_added' => 'now()',
                              'customer_notified' => '0',
                              'comments' => $pp_result);

      tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
      if ( $set_new_order_status) {
          tep_db_query(
              "UPDATE ".TABLE_ORDERS." ".
              "SET transaction_id='".strval($tx_transaction_id)."', orders_status='".(int)$set_new_order_status."', last_modified=NOW() ".
              "WHERE orders_id='".$order->order_id."'"
          );
      } else {
          tep_db_query(
              "UPDATE ".TABLE_ORDERS." ".
              "SET transaction_id='".strval($tx_transaction_id)."' ".
              "WHERE orders_id='".$order->order_id."'"
          );
      }

      tep_session_unregister('ppe_token');
      tep_session_unregister('ppe_payerid');
      tep_session_unregister('ppe_payerstatus');
      tep_session_unregister('ppe_addressstatus');
      tep_session_unregister('ppe_secret');
    }

    function get_error() {
      return false;
    }
    public function describe_status_key()
    {
      return new ModuleStatus('MODULE_PAYMENT_PAYPAL_EXPRESS_STATUS','True','False');
    }

    public function describe_sort_key()
    {
      return new ModuleSortOrder('MODULE_PAYMENT_PAYPAL_EXPRESS_SORT_ORDER');
    }

    public function configure_keys() {

        $status_id = defined('MODULE_PAYMENT_PAYPAL_EXPRESS_TRANSACTIONS_ORDER_STATUS_ID') ? MODULE_PAYMENT_PAYPAL_EXPRESS_TRANSACTIONS_ORDER_STATUS_ID : $this->getDefaultOrderStatusId();
        $status_id_ch = defined('MODULE_PAYMENT_PAYPAL_EXPRESS_CANCEL_ORDER_STATUS_ID') ? MODULE_PAYMENT_PAYPAL_EXPRESS_CANCEL_ORDER_STATUS_ID : $this->getDefaultOrderStatusId();
        $status_id_o = defined('MODULE_PAYMENT_PAYPAL_EXPRESS_ORDER_STATUS_ID') ? MODULE_PAYMENT_PAYPAL_EXPRESS_ORDER_STATUS_ID : $this->getDefaultOrderStatusId();

      $params = array('MODULE_PAYMENT_PAYPAL_EXPRESS_STATUS' => array('title' => 'Enable PayPal Express Checkout',
                                                                      'desc' => 'Do you want to accept PayPal Express Checkout payments?',
                                                                      'value' => 'True',
                                                                      'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '),
                      'MODULE_PAYMENT_PAYPAL_EXPRESS_SELLER_ACCOUNT' => array('title' => 'Seller Account',
                                                                              'desc' => 'The email address of the seller account if no API credentials has been setup.',
                                                                              'value' => STORE_OWNER_EMAIL_ADDRESS),
                      'MODULE_PAYMENT_PAYPAL_EXPRESS_API_USERNAME' => array('title' => 'API Username',
                                                                            'desc' => 'The username to use for the PayPal API service.'),
                      'MODULE_PAYMENT_PAYPAL_EXPRESS_API_PASSWORD' => array('title' => 'API Password',
                                                                            'desc' => 'The password to use for the PayPal API service.'),
                      'MODULE_PAYMENT_PAYPAL_EXPRESS_API_SIGNATURE' => array('title' => 'API Signature',
                                                                             'desc' => 'The signature to use for the PayPal API service.'),
                      'MODULE_PAYMENT_PAYPAL_EXPRESS_ACCOUNT_OPTIONAL' => array('title' => 'PayPal Account Optional',
                                                                                'desc' => 'This must also be enabled in your PayPal account, in Profile > Website Payment Preferences.',
                                                                                'value' => 'False',
                                                                                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '),
                      'MODULE_PAYMENT_PAYPAL_EXPRESS_INSTANT_UPDATE' => array('title' => 'PayPal Instant Update',
                                                                              'desc' => 'Allow PayPal to retrieve shipping rates and taxes for the order.',
                                                                              'value' => 'True',
                                                                              'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '),
                      'MODULE_PAYMENT_PAYPAL_EXPRESS_BUY_IMMEDIATELLY' => array('title' => 'Show Paypal button on product',
                                                                              'desc' => 'Allow to make PayPal purchase from product page',
                                                                              'value' => 'False',
                                                                              'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '),
                      'MODULE_PAYMENT_PAYPAL_EXPRESS_CHECKOUT_IMAGE' => array('title' => 'PayPal Checkout Image',
                                                                              'desc' => 'Use static or dynamic Express Checkout image buttons. Dynamic images are used with PayPal campaigns.',
                                                                              'value' => 'Static',
                                                                              'set_function' => 'tep_cfg_select_option(array(\'Static\', \'Dynamic\'), '),
                      'MODULE_PAYMENT_PAYPAL_EXPRESS_PAGE_STYLE' => array('title' => 'Page Style',
                                                                          'desc' => 'The page style to use for the checkout flow (defined at your PayPal Profile page)'),
                      'MODULE_PAYMENT_PAYPAL_EXPRESS_TRANSACTION_METHOD' => array('title' => 'Transaction Method',
                                                                                  'desc' => 'The processing method to use for each transaction.',
                                                                                  'value' => 'Sale',
                                                                                  'set_function' => 'tep_cfg_select_option(array(\'Authorization\', \'Sale\'), '),
                      'MODULE_PAYMENT_PAYPAL_EXPRESS_ORDER_STATUS_ID' => array('title' => 'Set Order Status',
                                                                               'desc' => 'Set the status of orders made with this payment module to this value',
                                                                               'value' => $status_id_o,
                                                                               'use_func' => '\\common\\helpers\\Order::get_order_status_name',
                                                                               'set_function' => 'tep_cfg_pull_down_order_statuses('),
                      'MODULE_PAYMENT_PAYPAL_EXPRESS_TRANSACTIONS_ORDER_STATUS_ID' => array('title' => 'PayPal Transactions Order Status Level',
                                                                                            'desc' => 'Include PayPal transaction information in this order status level.',
                                                                                            'value' => $status_id,
                                                                                            'use_func' => '\\common\\helpers\\Order::get_order_status_name',
                                                                                            'set_function' => 'tep_cfg_pull_down_order_statuses('),
                       'MODULE_PAYMENT_PAYPAL_EXPRESS_CANCEL_ORDER_STATUS_ID' => array('title' => 'PayPal Transactions Cancel Order Status',
                                                                                            'desc' => 'Set Order Status to cancel doubled order',
                                                                                            'value' => $status_id_ch,
                                                                                            'use_func' => '\\common\\helpers\\Order::get_order_status_name',
                                                                                            'set_function' => 'tep_cfg_pull_down_order_statuses('),
                      'MODULE_PAYMENT_PAYPAL_EXPRESS_ZONE' => array('title' => 'Payment Zone',
                                                                    'desc' => 'If a zone is selected, only enable this payment method for that zone.',
                                                                    'value' => '0',
                                                                    'use_func' => '\\common\\helpers\\Zones::get_zone_class_title',
                                                                    'set_function' => 'tep_cfg_pull_down_zone_classes('),
                      'MODULE_PAYMENT_PAYPAL_EXPRESS_TRANSACTION_SERVER' => array('title' => 'Transaction Server',
                                                                                  'desc' => 'Use the live or testing (sandbox) gateway server to process transactions?',
                                                                                  'value' => 'Live',
                                                                                  'set_function' => 'tep_cfg_select_option(array(\'Live\', \'Sandbox\'), '),
                      'MODULE_PAYMENT_PAYPAL_EXPRESS_VERIFY_SSL' => array('title' => 'Verify SSL Certificate',
                                                                          'desc' => 'Verify gateway server SSL certificate on connection?',
                                                                          'value' => 'True',
                                                                          'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '),
                      'MODULE_PAYMENT_PAYPAL_EXPRESS_PROXY' => array('title' => 'Proxy Server',
                                                                     'desc' => 'Send API requests through this proxy server. (host:port, eg: 123.45.67.89:8080 or proxy.example.com:8080)'),
                      'MODULE_PAYMENT_PAYPAL_EXPRESS_DEBUG_EMAIL' => array('title' => 'Debug E-Mail Address',
                                                                           'desc' => 'All parameters of an invalid transaction will be sent to this email address.'),
                      'MODULE_PAYMENT_PAYPAL_EXPRESS_SORT_ORDER' => array('title' => 'Sort order of display',
                                                                          'desc' => 'Sort order of display. Lowest is displayed first.',
                                                                          'value' => '0'));

      return $params;
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
      curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
      curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
      curl_setopt($curl, CURLOPT_POST, true);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters);

      if ( MODULE_PAYMENT_PAYPAL_EXPRESS_VERIFY_SSL == 'True' ) {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);

        if ( file_exists(DIR_FS_CATALOG . 'ext/modules/payment/paypal/paypal.com.crt') ) {
          curl_setopt($curl, CURLOPT_CAINFO, DIR_FS_CATALOG . 'ext/modules/payment/paypal/paypal.com.crt');
        } elseif ( file_exists(DIR_FS_CATALOG . 'includes/cacert.pem') ) {
          curl_setopt($curl, CURLOPT_CAINFO, DIR_FS_CATALOG . 'includes/cacert.pem');
        }
      } else {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
      }

      if ( tep_not_null(MODULE_PAYMENT_PAYPAL_EXPRESS_PROXY) ) {
        curl_setopt($curl, CURLOPT_HTTPPROXYTUNNEL, true);
        curl_setopt($curl, CURLOPT_PROXY, MODULE_PAYMENT_PAYPAL_EXPRESS_PROXY);
      }

      $result = curl_exec($curl);

      curl_close($curl);

      return $result;
    }

// format prices without currency formatting
    function format_raw($number, $currency_code = '', $currency_value = '') {
      $currencies = \Yii::$container->get('currencies');

      if (empty($currency_code) || !$currencies->is_set($currency_code)) {
        $currency_code = \Yii::$app->settings->get('currency');
      }

      if (empty($currency_value) || !is_numeric($currency_value)) {
        $currency_value = $currencies->currencies[$currency_code]['value'];
      }

      return number_format(round($number * $currency_value, $currencies->currencies[$currency_code]['decimal_places']), $currencies->currencies[$currency_code]['decimal_places'], '.', '');
    }

    function getPalDetails() {
      static $cache = false;
      if (!$cache ) {
        if (MODULE_PAYMENT_PAYPAL_EXPRESS_TRANSACTION_SERVER == 'Live') {
          $api_url = 'https://api-3t.paypal.com/nvp';
        } else {
          $api_url = 'https://api-3t.sandbox.paypal.com/nvp';
        }

        $params = array('VERSION' => $this->api_version,
                        'METHOD' => 'GetPalDetails',
                        'USER' => MODULE_PAYMENT_PAYPAL_EXPRESS_API_USERNAME,
                        'PWD' => MODULE_PAYMENT_PAYPAL_EXPRESS_API_PASSWORD,
                        'SIGNATURE' => MODULE_PAYMENT_PAYPAL_EXPRESS_API_SIGNATURE);

        $post_string = '';

        foreach ($params as $key => $value) {
          $post_string .= $key . '=' . urlencode(utf8_encode(trim($value))) . '&';
        }

        $post_string = substr($post_string, 0, -1);

        $response = $this->sendTransactionToGateway($api_url, $post_string);
        $response_array = array();
        parse_str($response, $response_array);

        if (!isset($response_array['PAL'])) {
          $this->sendDebugEmail($response_array);
        }
        $cache = $response_array;
      }

      return $cache;
    }

    function setExpressCheckout($parameters) {
      if (MODULE_PAYMENT_PAYPAL_EXPRESS_TRANSACTION_SERVER == 'Live') {
        $api_url = 'https://api-3t.paypal.com/nvp';
      } else {
        $api_url = 'https://api-3t.sandbox.paypal.com/nvp';
      }

      $params = array('VERSION' => $this->api_version,
                      'METHOD' => 'SetExpressCheckout',
                      'PAYMENTREQUEST_0_PAYMENTACTION' => ((MODULE_PAYMENT_PAYPAL_EXPRESS_TRANSACTION_METHOD == 'Sale') || (!tep_not_null(MODULE_PAYMENT_PAYPAL_EXPRESS_API_USERNAME)) ? 'Sale' : 'Authorization'),
                      'RETURNURL' => tep_href_link('callback/paypal-express', 'osC_Action=retrieve', 'SSL', true, false),
                      'CANCELURL' => tep_href_link('callback/paypal-express', 'osC_Action=cancel', 'SSL', true, false),
                      'BRANDNAME' => STORE_NAME,
                      'SOLUTIONTYPE' => (MODULE_PAYMENT_PAYPAL_EXPRESS_ACCOUNT_OPTIONAL == 'True') ? 'Sole' : 'Mark');

      if (tep_not_null(MODULE_PAYMENT_PAYPAL_EXPRESS_API_USERNAME)) {
        $params['USER'] = MODULE_PAYMENT_PAYPAL_EXPRESS_API_USERNAME;
        $params['PWD'] = MODULE_PAYMENT_PAYPAL_EXPRESS_API_PASSWORD;
        $params['SIGNATURE'] = MODULE_PAYMENT_PAYPAL_EXPRESS_API_SIGNATURE;
      } else {
        $params['SUBJECT'] = MODULE_PAYMENT_PAYPAL_EXPRESS_SELLER_ACCOUNT;
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

    function getExpressCheckoutDetails($token) {
      if (MODULE_PAYMENT_PAYPAL_EXPRESS_TRANSACTION_SERVER == 'Live') {
        $api_url = 'https://api-3t.paypal.com/nvp';
      } else {
        $api_url = 'https://api-3t.sandbox.paypal.com/nvp';
      }

      $params = array('VERSION' => $this->api_version,
                      'METHOD' => 'GetExpressCheckoutDetails',
                      'TOKEN' => $token);

      if (tep_not_null(MODULE_PAYMENT_PAYPAL_EXPRESS_API_USERNAME)) {
        $params['USER'] = MODULE_PAYMENT_PAYPAL_EXPRESS_API_USERNAME;
        $params['PWD'] = MODULE_PAYMENT_PAYPAL_EXPRESS_API_PASSWORD;
        $params['SIGNATURE'] = MODULE_PAYMENT_PAYPAL_EXPRESS_API_SIGNATURE;
      } else {
        $params['SUBJECT'] = MODULE_PAYMENT_PAYPAL_EXPRESS_SELLER_ACCOUNT;
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

    function doExpressCheckoutPayment($parameters) {
      if (MODULE_PAYMENT_PAYPAL_EXPRESS_TRANSACTION_SERVER == 'Live') {
        $api_url = 'https://api-3t.paypal.com/nvp';
      } else {
        $api_url = 'https://api-3t.sandbox.paypal.com/nvp';
      }

      $params = array('VERSION' => $this->api_version,
                      'METHOD' => 'DoExpressCheckoutPayment',
                      'PAYMENTREQUEST_0_PAYMENTACTION' => ((MODULE_PAYMENT_PAYPAL_EXPRESS_TRANSACTION_METHOD == 'Sale') || (!tep_not_null(MODULE_PAYMENT_PAYPAL_EXPRESS_API_USERNAME)) ? 'Sale' : 'Authorization'),
                      'BUTTONSOURCE' => 'HOLBIGROUPLTD_Cart_EC');

      if (tep_not_null(MODULE_PAYMENT_PAYPAL_EXPRESS_API_USERNAME)) {
        $params['USER'] = MODULE_PAYMENT_PAYPAL_EXPRESS_API_USERNAME;
        $params['PWD'] = MODULE_PAYMENT_PAYPAL_EXPRESS_API_PASSWORD;
        $params['SIGNATURE'] = MODULE_PAYMENT_PAYPAL_EXPRESS_API_SIGNATURE;
      } else {
        $params['SUBJECT'] = MODULE_PAYMENT_PAYPAL_EXPRESS_SELLER_ACCOUNT;
      }

      if (is_array($parameters) && !empty($parameters)) {
        $params = array_merge($params, $parameters);
      }

      $post_string = '';

      foreach ($params as $key => $value) {
        $post_string .= $key . '=' . urlencode(trim($value)) . '&';
      }

      $post_string = mb_substr($post_string, 0, -1);

      $response = $this->sendTransactionToGateway($api_url, $post_string);
      $response_array = array();
      parse_str($response, $response_array);

      if (($response_array['ACK'] != 'Success') && ($response_array['ACK'] != 'SuccessWithWarning')) {
        $this->sendDebugEmail($response_array);
      }

      return $response_array;
    }

    function getProductType($id, $attributes) {
      foreach ( $attributes as $a ) {
        $virtual_check_query = tep_db_query("select pad.products_attributes_id from " . TABLE_PRODUCTS_ATTRIBUTES . " pa, " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad where pa.products_id = '" . (int)$id . "' and pa.options_values_id = '" . (int)$a['value_id'] . "' and pa.products_attributes_id = pad.products_attributes_id limit 1");

        if ( tep_db_num_rows($virtual_check_query) == 1 ) {
          return 'Digital';
        }
      }

      return 'Physical';
    }

    function sendDebugEmail($response = array()) {
      if (tep_not_null(MODULE_PAYMENT_PAYPAL_EXPRESS_DEBUG_EMAIL)) {
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
          \common\helpers\Mail::send('', MODULE_PAYMENT_PAYPAL_EXPRESS_DEBUG_EMAIL, 'PayPal Express Checkout Debug E-Mail', trim($email_body), STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
        }
      }
    }

    function getTestLinkInfo() {
      $dialog_title = MODULE_PAYMENT_PAYPAL_EXPRESS_DIALOG_CONNECTION_TITLE;
      $dialog_button_close = MODULE_PAYMENT_PAYPAL_EXPRESS_DIALOG_CONNECTION_BUTTON_CLOSE;
      $dialog_success = MODULE_PAYMENT_PAYPAL_EXPRESS_DIALOG_CONNECTION_SUCCESS;
      $dialog_failed = MODULE_PAYMENT_PAYPAL_EXPRESS_DIALOG_CONNECTION_FAILED;
      $dialog_error = MODULE_PAYMENT_PAYPAL_EXPRESS_DIALOG_CONNECTION_ERROR;
      $dialog_connection_time = MODULE_PAYMENT_PAYPAL_EXPRESS_DIALOG_CONNECTION_TIME;

      if (defined('FILENAME_MODULES')) {
        $test_url = tep_href_link(FILENAME_MODULES, 'set=payment&module=' . $this->code . '&action=install&subaction=conntest');
      } else {
          $test_url = '';
      }

      $js = <<<EOD
<script>
if ( typeof jQuery == 'undefined' ) {
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

      $info = '<p><img src="images/icons/locked.gif" border="0">&nbsp;<a href="javascript:openTestConnectionDialog();" style="text-decoration: underline; font-weight: bold;">' . MODULE_PAYMENT_PAYPAL_EXPRESS_DIALOG_CONNECTION_LINK_TITLE . '</a></p>' .
              '<div id="testConnectionDialog" style="display: none;"><p>';

      if ( MODULE_PAYMENT_PAYPAL_EXPRESS_TRANSACTION_SERVER == 'Live' ) {
        $info .= 'Live Server:<br />https://api-3t.paypal.com/nvp';
      } else {
        $info .= 'Sandbox Server:<br />https://api-3t.sandbox.paypal.com/nvp';
      }

      $info .= '</p><div id="testConnectionDialogProgress"><p>' . MODULE_PAYMENT_PAYPAL_EXPRESS_DIALOG_CONNECTION_GENERAL_TEXT . '</p><div id="tcdprogressbar"></div></div></div>' .
               $js;

      return $info;
    }

    function getTestConnectionResult() {
      $params = array('PAYMENTREQUEST_0_CURRENCYCODE' => DEFAULT_CURRENCY,
                      'PAYMENTREQUEST_0_AMT' => '1.00');

      $response_array = $this->setExpressCheckout($params);

      if ( is_array($response_array) && isset($response_array['ACK']) ) {
        return 1;
      }

      return -1;
    }

    function isOnline() {
      return true;
  }
}
