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
  use \PayPal;

  class paypal_express_app extends lib\PaypalMidleWare {
    var $code, $title, $description, $enabled;

    protected $defaultTranslationArray = [
        'MODULE_PAYMENT_PAYPAL_EXPRESS_APP_TEXT_TITLE' => 'PayPal Express Checkout (oAuth)',
        'MODULE_PAYMENT_PAYPAL_EXPRESS_APP_TEXT_PUBLIC_TITLE' => 'PayPal (including Credit and Debit Cards)',
        'MODULE_PAYMENT_PAYPAL_EXPRESS_APP_TEXT_DESCRIPTION' => '<img src="images/icon_info.gif" border="0" />&nbsp;<a href="http://library.oscommerce.com/Package&en&paypal&oscom23&express_checkout" target="_blank" style="text-decoration: underline; font-weight: bold;">View Online Documentation</a><br /><br /><img src="images/icon_popup.gif" border="0" />&nbsp;<a href="https://www.paypal.com" target="_blank" style="text-decoration: underline; font-weight: bold;">Visit PayPal Website</a>',
        'MODULE_PAYMENT_PAYPAL_EXPRESS_APP_TEXT_BUTTON' => 'Check Out with PayPal',
        'MODULE_PAYMENT_PAYPAL_EXPRESS_APP_TEXT_COMMENTS' => 'Comments:',
        'MODULE_PAYMENT_PAYPAL_EXPRESS_APP_EMAIL_PASSWORD' => 'An account has automatically been created for you with the following e-mail address and password:' . "\n\n" . 'Store Account E-Mail Address: %s' . "\n" . 'Store Account Password: %s' . "\n\n",
        'MODULE_PAYMENT_PAYPAL_EXPRESS_APP_ERROR_ADMIN_CONFIGURATION' => 'This module will not load until the API Credential parameters have been configured. Please edit and configure the settings of this module.',
        'MODULE_PAYMENT_PAYPAL_EXPRESS_APP_BUTTON_STATIC' => 'https://www.paypalobjects.com/webstatic/en_US/i/buttons/checkout-logo-medium.png',
        'MODULE_PAYMENT_PAYPAL_EXPRESS_APP_LANGUAGE_LOCALE' => 'en_US',
        'MODULE_PAYMENT_PAYPAL_EXPRESS_APP_ERROR_NO_SHIPPING_AVAILABLE_TO_SHIPPING_ADDRESS' => 'Shipping is currently not available for the selected shipping address. Please select or create a new shipping address to use with your purchase.',
        'MODULE_PAYMENT_PAYPAL_EXPRESS_APP_WARNING_LOCAL_LOGIN_REQUIRED' => 'Please log into your account to verify the order.',
        'MODULE_PAYMENT_PAYPAL_EXPRESS_APP_NOTICE_CHECKOUT_CONFIRMATION' => 'Please review and confirm your order below. Your order will not be processed until it has been confirmed.',
    ];

    function __construct() {
      parent::__construct();

      $this->code = 'paypal_express_app';
      $this->title = MODULE_PAYMENT_PAYPAL_EXPRESS_APP_TEXT_TITLE;
      $this->public_title = MODULE_PAYMENT_PAYPAL_EXPRESS_APP_TEXT_PUBLIC_TITLE;
      $this->description = MODULE_PAYMENT_PAYPAL_EXPRESS_APP_TEXT_DESCRIPTION;
      $this->sort_order = defined('MODULE_PAYMENT_PAYPAL_EXPRESS_APP_SORT_ORDER') ? MODULE_PAYMENT_PAYPAL_EXPRESS_APP_SORT_ORDER : 0;
      $this->enabled = defined('MODULE_PAYMENT_PAYPAL_EXPRESS_APP_STATUS') && (MODULE_PAYMENT_PAYPAL_EXPRESS_APP_STATUS == 'True') ? true : false;
      $this->order_status = defined('MODULE_PAYMENT_PAYPAL_EXPRESS_APP_ORDER_STATUS_ID') && ((int)MODULE_PAYMENT_PAYPAL_EXPRESS_APP_ORDER_STATUS_ID > 0) ? (int)MODULE_PAYMENT_PAYPAL_EXPRESS_APP_ORDER_STATUS_ID : 0;
      $this->online = true;

      if ( defined('MODULE_PAYMENT_PAYPAL_EXPRESS_APP_STATUS') ) {
        if ( MODULE_PAYMENT_PAYPAL_EXPRESS_APP_TRANSACTION_SERVER == 'Sandbox' ) {
          $this->title .= ' [Sandbox]';
          $this->public_title .= ' (' . $this->code . '; Sandbox)';
        }

      }

      if ( $this->enabled === true ) {
        if ( !tep_not_null(MODULE_PAYMENT_PAYPAL_EXPRESS_APP_API_APP_CLIENT_ID) && !tep_not_null(MODULE_PAYMENT_PAYPAL_EXPRESS_APP_API_APP_CLIENT_ID) ) {
          $this->description = '<div class="secWarning">' . MODULE_PAYMENT_PAYPAL_EXPRESS_APP_ERROR_ADMIN_CONFIGURATION . '</div>' . $this->description;

          $this->enabled = false;
        }
      }

      if ( $this->enabled === true ) {
        $this->update_status();
      }
    }

    function update_status() {

      if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_PAYPAL_EXPRESS_APP_ZONE > 0) ) {
        $check_flag = false;
        $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_PAYPAL_EXPRESS_APP_ZONE . "' and zone_country_id = '" . $this->delivery['country']['id'] . "' order by zone_id");
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
        return tep_href_link('callback/paypal-express-app', '', 'SSL');
    }

    public function checkButtonOnProduct(){
        return defined('MODULE_PAYMENT_PAYPAL_EXPRESS_APP_BUY_IMMEDIATELLY') && MODULE_PAYMENT_PAYPAL_EXPRESS_APP_BUY_IMMEDIATELLY == 'True';
    }

    function checkout_initialization_method($index = 0) {
      global $cart;
      static $cnt = 0; //for buttons on listings
      $cnt++;

      $button_title = \common\helpers\Output::output_string_protected(MODULE_PAYMENT_PAYPAL_EXPRESS_APP_TEXT_BUTTON);

      if ( MODULE_PAYMENT_PAYPAL_EXPRESS_APP_TRANSACTION_SERVER == 'Sandbox' ) {
        $button_title .= ' (' . $this->code . '; Sandbox)';
      }
      
      if (MODULE_PAYMENT_PAYPAL_EXPRESS_APP_CHECKOUT_IMAGE == 'Dynamic' && !$index) {
        $image_button = '<div id="paypal-button-container"></div>';
      } else {
        $image_button = '<img src="' . MODULE_PAYMENT_PAYPAL_EXPRESS_APP_BUTTON_STATIC . '" border="0" alt="" title="' . $button_title . '" />';
      }

      $string = '<a rel="nofollow" href="' . $this->getObserveUrl() . '" class="paypal_buy btn-to-checkout" >'.$image_button.'</a>';
      if ($cnt==1) {
        $string .= $this->checkout_initialization_method_js($index);
      }

      return $string;
    }

    function checkout_initialization_method_js($index = 0) {
        if (MODULE_PAYMENT_PAYPAL_EXPRESS_APP_CHECKOUT_IMAGE == 'Dynamic' && !$index) {
            $clid = MODULE_PAYMENT_PAYPAL_EXPRESS_APP_API_APP_CLIENT_ID;
            $locale = \Yii::$app->settings->get('locale');
            $size = defined('MODULE_PAYMENT_PAYPAL_EXPRESS_APP_BUTTON_SIZE') ? MODULE_PAYMENT_PAYPAL_EXPRESS_APP_BUTTON_SIZE : 'small';
            $color = defined('MODULE_PAYMENT_PAYPAL_EXPRESS_APP_BUTTON_COLOR') ? MODULE_PAYMENT_PAYPAL_EXPRESS_APP_BUTTON_COLOR : 'gold';
            $shape = defined('MODULE_PAYMENT_PAYPAL_EXPRESS_APP_BUTTON_SHAPE') ? MODULE_PAYMENT_PAYPAL_EXPRESS_APP_BUTTON_SHAPE : 'pill';
            $label = defined('MODULE_PAYMENT_PAYPAL_EXPRESS_APP_BUTTON_LABEL') ? MODULE_PAYMENT_PAYPAL_EXPRESS_APP_BUTTON_LABEL : 'checkout';
            return <<<EOD
    <style>
    a.paypal_buy{ position:relative;display:block; }
    a.paypal_buy:before{ position:absolute;width:100%;height:100%;content:'';left: 0;top: 0;z-index: 1000; }
    </style>
   <script>
    var ppButton;
    var paypal_script;
   tl(function(){
        paypal_script = document.createElement('script');
        paypal_script.setAttribute('src', 'https://www.paypal.com/sdk/js?client-id={$clid}');        
        paypal_script.onload = handlePPLoadClient;
        function handlePPLoadClient(e) {
            stateClient = e.returnValue;
            if (stateClient) {
                PPSetup();
            }
        }
        document.head.appendChild(paypal_script);
        PPSetup = function(){
            if ($('#paypal-button-container').is('div')){
                ppButton = paypal.Buttons({
                  locale: '{$locale}',
                  style: {
                     size: '{$size}',
                     color: '{$color}',
                     shape: '{$shape}',
                     label: '{$label}',
                    },
                  });
                ppButton.render('#paypal-button-container');
            }
        }
       $('.paypal_buy').on('click', function(e){
           var that = this;
           if ($(that).parents('form').attr('name') == 'cart_quantity' || $(that).parents('form').hasClass('form-buy')){
               e.preventDefault();
               $(that).parents('form').append('<input type="hidden" name="purchase" value="{$this->code}">').submit();
           }
       })
   });
   </script>
EOD;
        } else {
            return <<<EOD
   <script>
   tl(function(){
       $('.paypal_buy').on('click', function(e){
           var that = this;
           if ($(that).parents('form').attr('name') == 'cart_quantity' || $(that).parents('form').hasClass('form-buy')){
               e.preventDefault();
               $(that).parents('form').append('<input type="hidden" name="purchase" value="{$this->code}">').submit();
           }
       })
   });
   </script>
EOD;
        }
    }

    function javascript_validation() {
      return false;
    }

    function selection() {
        if ($this->manager->has('ppe_order_total_check') || in_array('admin', $this->manager->getModulesVisibility())/*|| $this->isPartlyPaid()*/){
            return array('id' => $this->code,
                   'module' => $this->public_title);
        }
        return false;
    }

    function pre_confirmation_check() {

      if (!$this->manager->has('ppe_paymentId')) {
        tep_redirect(tep_href_link('callback/paypal-express-app', '', 'SSL'));
      }

      $payment = $this->getPaymentDetails($this->manager->get('ppe_paymentId'));

      if ($payment->getFailureReason()){
        tep_redirect(tep_href_link(FILENAME_SHOPPING_CART, 'error_message=' . stripslashes($payment->getFailureReason()), 'SSL'));
      } else {
          $transaction = $payment->getTransactions();
          $transaction = $transaction[0];//simple alone transaction
          if ($transaction){
            if ( !$this->manager->has('ppe_secret') || ($transaction->getCustom() != $this->manager->get('ppe_secret')) ) {
                tep_redirect(tep_href_link(FILENAME_SHOPPING_CART, '', 'SSL'));
            }
          }
      }

      if ( $this->manager->has('ppe_order_total_check') ) {
        \Yii::$container->get('message_stack')->add('<span id="PayPalNotice">' . MODULE_PAYMENT_PAYPAL_EXPRESS_APP_NOTICE_CHECKOUT_CONFIRMATION . '</span><script>$("#PayPalNotice").parent().css({backgroundColor: "#fcf8e3", border: "1px #faedd0 solid", color: "#a67d57", padding: "5px" });</script>', 'checkout_confirmation', 'paypal');
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
        $confirmation = array('fields' => array(array('title' => MODULE_PAYMENT_PAYPAL_EXPRESS_APP_TEXT_COMMENTS,
                                                      'field' => tep_draw_textarea_field('ppecomments', 'soft', '60', '5', $comments))));
      }

      return $confirmation;
    }

    function process_button() {
      return false;
    }

    private function _getPatch(){
        $patch = new PayPal\Api\Patch();
        $patch->setOp('replace');
        $patch->setPath('/transactions/0/amount');
        $patch->setValue($this->_fillTransactionDetails());
        $patches = [$patch];
        $patch = new PayPal\Api\Patch();
        $patch->setOp('replace');
        $patch->setPath('/transactions/0/item_list');
        $patch->setValue($this->getItemList());
        $patches[] = $patch;
        $patchRequest = new PayPal\Api\PatchRequest();
        return $patchRequest->setPatches($patches);
    }

    function before_process() {

      if (!$this->manager->has('ppe_paymentId')) {
        tep_redirect(tep_href_link('callback/paypal-express-app', '', 'SSL'));
      }

      $payment = $this->getPaymentDetails($this->manager->get('ppe_paymentId'));
      $transaction = $payment->getTransactions();
      $transaction = $transaction[0];//simple alone transaction
      if ($transaction){
          $order = $this->manager->getOrderInstance();
          if ( !$this->manager->has('ppe_secret') || ($transaction->getCustom() != $this->manager->get('ppe_secret')) ) {
            tep_redirect(tep_href_link(FILENAME_SHOPPING_CART, '', 'SSL'));
          } elseif ( ($transaction->getAmount()->getTotal() != $this->format_raw($order->info['total_inc_tax'])) && !$this->manager->has('ppe_order_total_check') ) {
            $this->manager->set('ppe_order_total_check', true);

            tep_redirect(tep_href_link(FILENAME_CHECKOUT_CONFIRMATION, '', 'SSL'));
          }

          if ( $this->manager->has('ppe_order_total_check') ) {
              try{
                  $response = $payment->update($this->_getPatch(), $this->getApiContext());
              } catch (\Exception $ex) {
                  $this->sendDebugEmail($ex);
              }
          }

          $this->manager->remove('ppe_order_total_check');

      } else {
          tep_redirect(tep_href_link(FILENAME_SHOPPING_CART, 'error_message=transaction+erorr', 'SSL'));
      }

      $paymentExecution = new PayPal\Api\PaymentExecution;
      try{
          $paymentExecution->setPayerId($this->manager->get('ppe_payerid'));
          $response = $payment->execute($paymentExecution, $this->getApiContext());
          if ($response->getState() == 'failed'){
              tep_redirect(tep_href_link(FILENAME_SHOPPING_CART, 'error_message=' . stripslashes($response->getFailureReason()), 'SSL'));
          }

          $comments = $this->manager->get('comments');
          if (empty($comments)) {
            if (isset($_POST['ppecomments']) && tep_not_null($_POST['ppecomments'])) {
                $comments = tep_db_prepare_input($_POST['ppecomments']);
                $this->manager->set('comments', $comments);

                $order->info['comments'] = $comments;
            }
          }
          $order->update_piad_information(true);
      } catch (\Exception $ex) {
          $this->sendDebugEmail($ex);
      }
    }

    function after_process() {

      $payment = $this->getPaymentDetails($this->manager->get('ppe_paymentId'));
      $order = $this->manager->getOrderInstance();

      if ((int)MODULE_PAYMENT_PAYPAL_EXPRESS_APP_CANCEL_ORDER_STATUS_ID){
            $cancelStatus = (int)MODULE_PAYMENT_PAYPAL_EXPRESS_APP_CANCEL_ORDER_STATUS_ID;
            $check_transaction = (new \yii\db\Query)->select('orders_id')->from('paypal_checkout')
                    ->where(['txn_signature' => $payment->getId()])->one();
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
                        ':oId' => $order->order_id, ':sig' => $payment->getId()])->execute();
      }

      $transaction = $payment->getTransactions();
      $transaction = $transaction[0];

      if ($transaction){
          $sale = $transaction->getRelatedResources();
          $sale = $sale[0]->getSale();
          if ($sale){
              $currencies = \Yii::$container->get('currencies');
              $pp_result = [
                  'Transaction ID: ' . \common\helpers\Output::output_string_protected($payment->getId()),
                  'Transactin Amount: ' . $currencies->format($sale->getAmount()->getTotal(), true, $order->info['currency'], $order->info['currency_value']),
                  'Payer Status: ' . \common\helpers\Output::output_string_protected($payment->getPayer()->getStatus()),
                  'Payment Status: ' . \common\helpers\Output::output_string_protected($sale->getState()),
                  'Payment Type: ' . \common\helpers\Output::output_string_protected($sale->getPaymentMode()),
                  'Pending Reason: ' . \common\helpers\Output::output_string_protected($sale->getReasonCode()),
                  'Protection Eligibility: ' . \common\helpers\Output::output_string_protected($sale->getProtectionEligibility()),
              ];
              $fraud = $sale->getFmfDetails();
              if ($fraud){
                  $pp_result[] = 'Fraud Filter Id: ' . \common\helpers\Output::output_string_protected($fraud->getFilterId());
                  $pp_result[] = 'Fraud Filter Type: ' .\common\helpers\Output::output_string_protected($fraud->getFilterType());
                  $pp_result[] = 'Fraud Name: ' .\common\helpers\Output::output_string_protected($fraud->getName());
                  $pp_result[] = 'Fraud Description: ' . \common\helpers\Output::output_string_protected($fraud->getDescription());
              }
              $tx_transaction_id = $sale->getId();
              $invoice_id = $this->manager->getOrderSplitter()->getInvoiceId();
              $this->manager->getTransactionManager($this)->addTransaction($tx_transaction_id, $sale->getState(), $sale->getAmount()->getTotal(), $invoice_id, 'Customer\'s payment');

              $set_new_order_status_transaction = (int)MODULE_PAYMENT_PAYPAL_EXPRESS_APP_TRANSACTIONS_ORDER_STATUS_ID;
              $set_new_order_status = (int)MODULE_PAYMENT_PAYPAL_EXPRESS_APP_ORDER_STATUS_ID;
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
                                        'orders_status_id' => $set_new_order_status_transaction,
                                        'date_added' => 'now()',
                                        'customer_notified' => '0',
                                        'comments' => implode("\n", $pp_result));

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

              $orderPaymentAmount = $sale->getAmount()->getTotal();
              $ordersPaymentStatus = \common\helpers\OrderPayment::OPYS_SUCCESSFUL;
              $transactionInformationArray = [
                  'id' => trim($sale->getId()),
                  'status' => ucfirst(trim($sale->getState())),
                  'commentary' => implode("\n", $pp_result),
                  'date' => date('Y-m-d H:i:s', strtotime($sale->getCreateTime()))
              ];
              \common\helpers\OrderPayment::createDebitFromOrder($order, $orderPaymentAmount, $ordersPaymentStatus, $transactionInformationArray);
          }
          $this->manager->remove('ppe_paymentId');
          $this->manager->remove('ppe_payerid');
          $this->manager->remove('ppe_secret');
          $this->manager->remove('ppe_order_total_check');
      }
    }

    function get_error() {
      return false;
    }
    public function describe_status_key()
    {
      return new ModuleStatus('MODULE_PAYMENT_PAYPAL_EXPRESS_APP_STATUS','True','False');
    }

    public function describe_sort_key()
    {
      return new ModuleSortOrder('MODULE_PAYMENT_PAYPAL_EXPRESS_APP_SORT_ORDER');
    }

    public function configure_keys() {
        $status_id = defined('MODULE_PAYMENT_PAYPAL_EXPRESS_APP_TRANSACTIONS_ORDER_STATUS_ID') ? MODULE_PAYMENT_PAYPAL_EXPRESS_APP_TRANSACTIONS_ORDER_STATUS_ID : $this->getDefaultOrderStatusId();
        $status_id_ch = defined('MODULE_PAYMENT_PAYPAL_EXPRESS_APP_CANCEL_ORDER_STATUS_ID') ? MODULE_PAYMENT_PAYPAL_EXPRESS_APP_CANCEL_ORDER_STATUS_ID : $this->getDefaultOrderStatusId();
        $status_id_o = defined('MODULE_PAYMENT_PAYPAL_EXPRESS_APP_ORDER_STATUS_ID') ? MODULE_PAYMENT_PAYPAL_EXPRESS_APP_ORDER_STATUS_ID : $this->getDefaultOrderStatusId();


        $params = array('MODULE_PAYMENT_PAYPAL_EXPRESS_APP_STATUS' => array('title' => 'Enable PayPal Express Checkout (OAuth)',
                                                                      'description' => 'Do you want to accept PayPal Express Checkout payments?',
                                                                      'value' => 'True',
                                                                      'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '),
                      'MODULE_PAYMENT_PAYPAL_EXPRESS_APP_API_APP_CLIENT_ID' => array('title' => 'API Application Client ID',
                                                                             'description' => 'The Client ID of app'),
                      'MODULE_PAYMENT_PAYPAL_EXPRESS_APP_API_APP_CLIENT_SECRET' => array('title' => 'API Application Client Secret Key',
                                                                             'description' => 'The Client Secret Key of app'),
                      /*'MODULE_PAYMENT_PAYPAL_EXPRESS_APP_ACCOUNT_OPTIONAL' => array('title' => 'PayPal Account Optional',
                                                                                'desc' => 'This must also be enabled in your PayPal account, in Profile > Website Payment Preferences.',
                                                                                'value' => 'False',
                                                                                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '),
                      'MODULE_PAYMENT_PAYPAL_EXPRESS_APP_INSTANT_UPDATE' => array('title' => 'PayPal Instant Update',
                                                                              'desc' => 'Allow PayPal to retrieve shipping rates and taxes for the order.',
                                                                              'value' => 'True',
                                                                              'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '),*/
                      'MODULE_PAYMENT_PAYPAL_EXPRESS_APP_BUY_IMMEDIATELLY' => array('title' => 'Show Paypal button on product',
                                                                              'description' => 'Allow to make PayPal purchase from product page',
                                                                              'value' => 'False',
                                                                              'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '),
                      /*'MODULE_PAYMENT_PAYPAL_EXPRESS_APP_PAGE_STYLE' => array('title' => 'Page Style',
                                                                          'desc' => 'The page style to use for the checkout flow (defined at your PayPal Profile page)'),*/
                      'MODULE_PAYMENT_PAYPAL_EXPRESS_APP_TRANSACTION_METHOD' => array('title' => 'Transaction Method',
                                                                                  'description' => 'The processing method to use for each transaction.',
                                                                                  'value' => 'sale',
                                                                                  'set_function' => 'tep_cfg_select_option(array(\'authorize\', \'sale\'), '),
                      'MODULE_PAYMENT_PAYPAL_EXPRESS_APP_ORDER_STATUS_ID' => array('title' => 'Set Order Status',
                                                                               'description' => 'Set the status of orders made with this payment module to this value',
                                                                               'value' => $status_id_o,
                                                                               'use_func' => '\\common\\helpers\\Order::get_order_status_name',
                                                                               'set_function' => 'tep_cfg_pull_down_order_statuses('),
                      'MODULE_PAYMENT_PAYPAL_EXPRESS_APP_TRANSACTIONS_ORDER_STATUS_ID' => array('title' => 'PayPal Transactions Order Status Level',
                                                                                            'description' => 'Include PayPal transaction information in this order status level.',
                                                                                            'value' => $status_id,
                                                                                            'use_func' => '\\common\\helpers\\Order::get_order_status_name',
                                                                                            'set_function' => 'tep_cfg_pull_down_order_statuses('),
                       'MODULE_PAYMENT_PAYPAL_EXPRESS_APP_CANCEL_ORDER_STATUS_ID' => array('title' => 'PayPal Transactions Cancel Order Status',
                                                                                            'description' => 'Set Order Status to cancel doubled order',
                                                                                            'value' => $status_id_ch,
                                                                                            'use_func' => '\\common\\helpers\\Order::get_order_status_name',
                                                                                            'set_function' => 'tep_cfg_pull_down_order_statuses('),
                      'MODULE_PAYMENT_PAYPAL_EXPRESS_APP_ZONE' => array('title' => 'Payment Zone',
                                                                    'description' => 'If a zone is selected, only enable this payment method for that zone.',
                                                                    'value' => '0',
                                                                    'use_func' => '\\common\\helpers\\Zones::get_zone_class_title',
                                                                    'set_function' => 'tep_cfg_pull_down_zone_classes('),
                      'MODULE_PAYMENT_PAYPAL_EXPRESS_APP_TRANSACTION_SERVER' => array('title' => 'Transaction Server',
                                                                                  'description' => 'Use the live or testing (sandbox) gateway server to process transactions?',
                                                                                  'value' => 'Live',
                                                                                  'set_function' => 'tep_cfg_select_option(array(\'Live\', \'Sandbox\'), '),
                      'MODULE_PAYMENT_PAYPAL_EXPRESS_APP_CHECKOUT_IMAGE' => array('title' => 'PayPal Checkout Image',
                                                                              'description' => 'Use static or dynamic Express Checkout image buttons. Dynamic images are used with PayPal campaigns.',
                                                                              'value' => 'Static',
                                                                              'set_function' => 'tep_cfg_select_option(array(\'Static\', \'Dynamic\'), '),
                      'MODULE_PAYMENT_PAYPAL_EXPRESS_APP_BUTTON_COLOR' => array('title' => 'Dynamic Button Color',
                                                                                  'description' => 'Color for Dynamic Button',
                                                                                  'value' => 'gold',
                                                                                  'set_function' => 'multiOption(\'dropdown\', array(\'gold\', \'blue\', \'silver\', \'white\', \'black\'), '),
                      'MODULE_PAYMENT_PAYPAL_EXPRESS_APP_BUTTON_SHAPE' => array('title' => 'Dynamic Button Shape',
                                                                                  'description' => 'Shape for Dynamic Button',
                                                                                  'value' => 'pill',
                                                                                  'set_function' => 'multiOption(\'dropdown\', array(\'pill\', \'rect\'), '),
                      'MODULE_PAYMENT_PAYPAL_EXPRESS_APP_BUTTON_SIZE' => array('title' => 'Dynamic Button Size',
                                                                                  'description' => 'Size for Dynamic Button',
                                                                                  'value' => 'small',
                                                                                  'set_function' => 'multiOption(\'dropdown\', array(\'small\', \'medium\', \'large\', \'responsive\'), '),
                      'MODULE_PAYMENT_PAYPAL_EXPRESS_APP_BUTTON_LABEL' => array('title' => 'Dynamic Button Label',
                                                                                  'description' => 'Label for Dynamic Button',
                                                                                  'value' => 'checkout',
                                                                                  'set_function' => 'multiOption(\'dropdown\', array(\'checkout\', \'pay\', \'buynow\', \'paypal\'), '),
                      /*'MODULE_PAYMENT_PAYPAL_EXPRESS_APP_VERIFY_SSL' => array('title' => 'Verify SSL Certificate',
                                                                          'desc' => 'Verify gateway server SSL certificate on connection?',
                                                                          'value' => 'True',
                                                                          'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '),
                      'MODULE_PAYMENT_PAYPAL_EXPRESS_APP_PROXY' => array('title' => 'Proxy Server',
                                                                     'desc' => 'Send API requests through this proxy server. (host:port, eg: 123.45.67.89:8080 or proxy.example.com:8080)'),*/
                      'MODULE_PAYMENT_PAYPAL_EXPRESS_APP_DEBUG_EMAIL' => array('title' => 'Debug E-Mail Address',
                                                                           'description' => 'All parameters of an invalid transaction will be sent to this email address.'),
                      'MODULE_PAYMENT_PAYPAL_EXPRESS_APP_SORT_ORDER' => array('title' => 'Sort order of display',
                                                                          'description' => 'Sort order of display. Lowest is displayed first.',
                                                                          'value' => '0'));

      return $params;
    }

    protected function _fillTransactionDetails(){
        $totals = $this->manager->getTotalOutput(false);
        $totalCollection = $this->manager->getTotalCollection();
        $filled = [];
        $_total = 0;
        if (is_array($totals)){
            foreach($totals as $total){
                $_class = (isset($total['class']) ? $total['class'] : $total['code']);
                if (in_array($_class, ['ot_paid', 'ot_due', 'ot_total', 'ot_subtax'])) continue;
                $mod = $totalCollection->get($_class);
                if ($mod && $mod->credit_class){
                    $filled['shipping_discount'] -= $total['value_exc_vat'];//no any discount fields
                } else {
                    switch($_class){
                        case 'ot_subtotal':
                            $filled['subtotal'] = $total['value_exc_vat'];
                            break;
                        case 'ot_shipping':
                            $filled['shipping'] = $total['value_exc_vat'];
                            break;
                        case 'ot_tax':
                            $filled['tax'] = $total['value_inc_tax'];
                            break;
                        default:
                            $filled['handling_fee'] += $total['value_exc_vat'];
                            break;
                    }
                }
            }
        }
        
        $order = $this->manager->getOrderInstance();
        
        $totalAmount = (float)$this->format_raw($order->info['total_inc_tax']);
        foreach ($filled as $key => $value){
            $filled[$key] = $this->format_raw($value);
            $_total += (float)$filled[$key];
        }
        
        if ($totalAmount != $_total){
            if ($totalAmount > $_total){
                $filled['insurance'] =  round($totalAmount - $_total,2);
            } else {
                $filled['shipping_discount'] = (float)$filled['shipping_discount'] + round($totalAmount - $_total,2);
            }
        }
        
        return [
            'total' => $totalAmount,
            'currency' => $order->info['currency'],
            'details' => $filled,
        ];
    }

    protected function getItemList(){
        $order = $this->manager->getOrderInstance();
        $items = new \PayPal\Api\ItemList();
        foreach($order->products as $product){
            $items->addItem([
                'name' => $product['name'],
                'quantity' => $product['qty'],
                'price' => $this->format_raw($product['final_price']),
                'tax' => $this->format_raw(\common\helpers\Tax::calculate_tax($product['final_price'], $product['tax'])),
                'sku' => $product['model'],
                'currency' => $order->info['currency'],
            ]);
        }
        if ($this->manager->isCustomerAssigned() && $order->delivery['country'] && $order->delivery['street_address'] && ($order->delivery['zone_id'] ||$order->delivery['zone_id'])){
            $data = [];
            $data['line1'] = $order->delivery['street_address'];
            $data['country_code'] = $order->delivery['country']['iso_code_2'];
            if ($order->delivery['firstname'] || $order->delivery['lastname']){
                $data['recipient_name'] = $order->delivery['firstname'] . ' ' . $order->delivery['lastname'];
            }
            if ($order->delivery['suburb']){
                $data['line2'] = $order->delivery['suburb'];
            }
            if ($order->delivery['city']){
                $data['city'] = $order->delivery['city'];
            }
            if ($order->delivery['postcode']){
                $data['postal_code'] = $order->delivery['postcode'];
            }
            if ($order->delivery['zone_id']){
                $data['state'] = \common\helpers\Zones::get_zone_code($order->delivery['country']['id'], $order->delivery['zone_id'], $order->delivery['state']);
            } else if ($order->delivery['state']){
                $data['state'] = $order->delivery['state'];
            }
            if ($order->delivery['telephone']){
                $data['phone'] = (substr($order->delivery['telephone'], 0, 1) != '+' ? '+':'') .$order->delivery['telephone'];
            }
            $items->setShippingAddress($data);
        }

        return $items;
    }

    protected function _addTransactionDetail($payment){
        $order = $this->manager->getOrderInstance();

        $amount = $this->_fillTransactionDetails();

        $tranasction = new PayPal\Api\Transaction;
        $tranasction->setAmount($amount);
        $tranasction->setDescription(STORE_NAME . " transaction");
        $ppe_secret = \common\helpers\Password::create_random_value(16, 'digits');

        $tranasction->setCustom($ppe_secret);
        $this->manager->set('ppe_secret', $ppe_secret);

        $tranasction->setItemList($this->getItemList());

        $payment->addTransaction($tranasction);

        return $payment;
    }
    
    public function createPayment(){

        $payment = $this->_getPayment();

        $this->_addTransactionDetail($payment);

        $payment->setRedirectUrls([ //when payment is paypal
                'return_url' => tep_href_link('callback/paypal-express-app', 'osC_Action=retrieve', 'SSL', true, false),
                'cancel_url' => tep_href_link('callback/paypal-express-app', 'osC_Action=cancel', 'SSL', true, false),
            ]);

        try{
            $response = $payment->create($this->getApiContext());
            if ($response->getState() == 'created'){
                tep_redirect($response->getApprovalLink());
            } else{
                tep_redirect(tep_href_link(FILENAME_SHOPPING_CART, 'error_message=' . stripslashes($response->getFailureReason()), 'SSL'));
            }
        } catch (\Exception $ex) {
            $this->sendDebugEmail($ex);
        }
        return false;
    }

    function sendDebugEmail($response = array()) {
      if (tep_not_null(MODULE_PAYMENT_PAYPAL_EXPRESS_APP_DEBUG_EMAIL)) {
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
          \common\helpers\Mail::send('', MODULE_PAYMENT_PAYPAL_EXPRESS_APP_DEBUG_EMAIL, 'PayPal Express Checkout Debug E-Mail', trim($email_body), STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
        }
      }
    }

    function isOnline() {
        return true;
    }   
    
    protected function _getIntent(){
        return strtolower(MODULE_PAYMENT_PAYPAL_EXPRESS_APP_TRANSACTION_METHOD);
    }
    
    protected function _isReady(){
        return (defined('MODULE_PAYMENT_PAYPAL_EXPRESS_APP_API_APP_CLIENT_ID') && !empty(MODULE_PAYMENT_PAYPAL_EXPRESS_APP_API_APP_CLIENT_ID) &&
                defined('MODULE_PAYMENT_PAYPAL_EXPRESS_APP_API_APP_CLIENT_SECRET') && !empty('MODULE_PAYMENT_PAYPAL_EXPRESS_APP_API_APP_CLIENT_SECRET'));
    }
    
    protected function _getClientId(){
        return MODULE_PAYMENT_PAYPAL_EXPRESS_APP_API_APP_CLIENT_ID;
    }
    
    protected function _getClientSecret(){
        return MODULE_PAYMENT_PAYPAL_EXPRESS_APP_API_APP_CLIENT_SECRET;
    }
    
    protected function _getMode(){
        return MODULE_PAYMENT_PAYPAL_EXPRESS_APP_TRANSACTION_SERVER;
    }

}
