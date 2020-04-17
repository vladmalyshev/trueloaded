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
  use common\classes\modules\TransactionalInterface;
  use common\classes\modules\PaymentTokensInterface;
  use common\helpers\OrderPayment as OrderPaymentHelper;

  use common\helpers\Html;

  class sage_pay_server extends ModulePayment implements TransactionalInterface, PaymentTokensInterface, \common\classes\modules\TransactionSearchInterface {
    var $code, $title, $description, $enabled;
    private $debug = false;
    private $referrer = 'E57C3C9C-DB7F-4EA1-9AE7-252EEBE28626';

    protected $defaultTranslationArray = [
        'MODULE_PAYMENT_SAGE_PAY_SERVER_TEXT_TITLE' => 'Sage Pay Server',
        'MODULE_PAYMENT_SAGE_PAY_SERVER_TEXT_PUBLIC_TITLE' => 'Credit Card or Bank Card (Processed by Sage Pay)',
        'MODULE_PAYMENT_SAGE_PAY_SERVER_TEXT_DESCRIPTION' => '<img src="images/icon_popup.gif" border="0">&nbsp;<a href="https://support.sagepay.com/apply/default.aspx?PartnerID=E57C3C9C-DB7F-4EA1-9AE7-252EEBE28626" target="_blank" style="text-decoration: underline; font-weight: bold;">Visit Sage Pay Website</a>&nbsp;<a href="javascript:toggleDivBlock(\'sagePayInfo\');">(info)</a><span id="sagePayInfo" style="display: none;"><br><i>Using the above link to signup at Sage Pay grants osCommerce a small financial bonus for referring a customer.</i></span>',
        'MODULE_PAYMENT_SAGE_PAY_SERVER_ERROR_TITLE' => 'There has been an error processing your credit card',
        'MODULE_PAYMENT_SAGE_PAY_SERVER_ERROR_GENERAL' => 'Please try again and if problems persist, please try another payment method.'
    ];

// class constructor
    function __construct() {
        parent::__construct();

        $this->signature = 'sage_pay|sage_pay_server|2.0|2.3';
        $this->api_version = '3.00';

        $this->code = 'sage_pay_server';
        $this->title = MODULE_PAYMENT_SAGE_PAY_SERVER_TEXT_TITLE;
        $this->public_title = MODULE_PAYMENT_SAGE_PAY_SERVER_TEXT_PUBLIC_TITLE;
        $this->description = MODULE_PAYMENT_SAGE_PAY_SERVER_TEXT_DESCRIPTION;
        if (!defined('MODULE_PAYMENT_SAGE_PAY_SERVER_STATUS')) {
            $this->enabled = false;
            return;
        }
        $this->sort_order = MODULE_PAYMENT_SAGE_PAY_SERVER_SORT_ORDER;
        $this->enabled = ((MODULE_PAYMENT_SAGE_PAY_SERVER_STATUS == 'True') ? true : false);
        $this->online = true;

// {{
//      if (IS_TRADE_SITE == 'True') $this->enabled = false;
// }}

        if ((int)MODULE_PAYMENT_SAGE_PAY_SERVER_ORDER_STATUS_ID > 0) {
          $this->order_status = MODULE_PAYMENT_SAGE_PAY_SERVER_ORDER_STATUS_ID;
        }

        $this->update_status();
    }

// class methods
    function update_status() {

      if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_SAGE_PAY_SERVER_ZONE > 0) ) {
        $check_flag = false;
        $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_SAGE_PAY_SERVER_ZONE . "' and zone_country_id = '" . $this->billing['country']['id'] . "' order by zone_id");
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
      $this->manager->remove('ptoken');
      $this->manager->remove('use_token');
      $selection = array('id' => $this->code,
                   'module' => $this->public_title);

      $fields = $this->renderTokenSelection((int)\Yii::$app->user->getId());
      if (!empty($fields)) {
        $selection ['fields'] = $fields;
      }
      if ($this->popUpMode()) {
        if (isset($selection ['fields'][0]['title'])) {
          $selection ['fields'][0]['title'] .=  $this->tlPopupJS();
        } else {
          $selection ['fields'][] = ['title' => $this->tlPopupJS()];
        }
      }
      return $selection;

    }
    
    protected function tlPopupJS() :string {
        $this->registerCallback("popUpIframe");
        \Yii::$app->getView()->registerJs(parent::tlPopupJS());
        return '';
    }

    function pre_confirmation_check() {
      $order = $this->manager->getOrderInstance();
      //\Yii::$app->request->post()
      if (!empty($_POST['ptoken']) && $this->checkToken((int)\Yii::$app->user->getId(), $_POST['ptoken'])) {
        //$order->info['use_token'] = isset($_POST['use_token'])?$_POST['use_token']:false;
        //$_SESSION['use_token'] = $_POST['ptoken'];
        $this->manager->set('ptoken', $_POST['ptoken']);
      } elseif (!empty($_POST['use_token'])) {
        //$order->info['use_token'] = isset($_POST['use_token'])?$_POST['use_token']:false;
        //$_SESSION['use_token'] = isset($_POST['use_token'])?$_POST['use_token']:false;
        $this->manager->set('use_token', $_POST['use_token']);
      }
      if (!empty($_POST['set_default_token'])) {
        $this->manager->set('update_default_token', $_POST['set_default_token']);
        $this->saveToken((int)\Yii::$app->user->getId(),
                  [
                    'old_payment_token' => $this->manager->get('ptoken'),
                    'token' => $this->manager->get('ptoken')
                   ]);
      }

      return true;
    }

    function confirmation() {
      if ($this->isWithoutConfirmation()) {
        return false;
      }
      return ['title' => $this->tlPopupJS()];
    }

    function process_button() {
      return false;
    }

    /**
     * Validate vpsSignature in POST against saved in session
     */
    public function validateResponse() {
      $ret = false;
      $sig_string = '';
      $post = \Yii::$app->request->post();
      foreach([
          'VPSTxId',
          'VendorTxCode',
          'Status',
          'TxAuthNo',
          'VendorName',
          'AVSCV2',
          'SecurityKey',
          'AddressResult',
          'PostCodeResult',
          'CV2Result',
          'GiftAid',
          '3DSecureStatus',
          'CAVV',
          'AddressStatus',
          'PayerStatus',
          'CardType',
          'Last4Digits',
          'DeclineCode',
          'ExpiryDate',
          'FraudResponse',
          'BankAuthCode',
        ] as $key) {

          if ( $key == 'VendorName' ) {
            // Please ensure the VendorName is lower case prior to hashing.
            $sig_string .= strtolower(substr(MODULE_PAYMENT_SAGE_PAY_SERVER_VENDOR_LOGIN_NAME, 0, 15));
          }elseif ( $key == 'SecurityKey' ) {
            $sig_string .= $this->manager->get('sage_pay_server_securitykey');
          }elseif ( isset($post[$key]) ) {
            // If a field is returned without a value this should not be included in the string.
            $sig_string .= $post[$key];
          }
      }

      if (isset($post['VPSSignature']) && ($post['VPSSignature'] == strtoupper(md5($sig_string)))) {
        //MD5 value is returned in UPPER CASE.
        $ret = true;
      }
      
      return $ret;
    }

    public function safeServer() {

        $post = \Yii::$app->request->post();
        if ($this->validateResponse()) {
          $paymentStatus = \Yii::$app->request->post('Status', false);

          if ( !in_array($paymentStatus, ['OK', 'AUTHENTICATED', 'REGISTERED']) ) {
            //payment error
            $this->manager->remove('sage_pay_server_securitykey');
            $this->manager->remove('sage_pay_server_nexturl');

            $error = \Yii::$app->request->post('StatusDetail', false);

            $error_url = tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code . (tep_not_null($error) ? '&error=' . $error : '') . '&' . tep_session_name() . '=' . tep_session_id(), 'SSL', false);
            if ($this->popUpMode()) {
              $error_url = \Yii::$app->urlManager->createAbsoluteUrl(['callback/webhooks', 'set' => 'payment', 'action' => 'error', 'module' => $this->code, 'redirect' => $error_url]);
            }
            
              $result = 'Status=OK' . chr(13) . chr(10) .
                        'RedirectURL=' . $error_url;
          } else {
            //payment OK
            if ($this->debug ) {
              \Yii::warning(print_r(\Yii::$app->request->post(),1), 'SAGEPAY_SERVER_RESPONSE');
            }

            
            $sage_pay_server_additional_info = $post;
            $sage_pay_server_additional_info['SecurityKey'] = $this->manager->get('sage_pay_server_securitykey');

            $token = \Yii::$app->request->post('Token', false);
            if (!empty($token)) {
              $expDate = \Yii::$app->request->post('ExpiryDate');
              if (strlen($expDate)==4) {
                $expYear = '20' . substr($expDate, 2);
                $expMonth = substr($expDate, 0, 2);
              }
              $this->saveToken((int)\Yii::$app->user->getId(),
                  [
                    'token' => $token,
                    'cardType' => \Yii::$app->request->post('CardType', ''),
                    'lastDigits' => \Yii::$app->request->post('Last4Digits', ''),
                    'expDate' => (!empty($expMonth)? date('Y-m-t', mktime(23, 59,  59, intval($expMonth), 1, $expYear)) :'')
                   ]);
            }

            $this->manager->set('sage_pay_server_additional_info', $sage_pay_server_additional_info);
/*
            $controller = FILENAME_CHECKOUT_PROCESS;
            if (isset($_GET['partlypaid']) && $_GET['partlypaid']){
                //$controller = 'account/order-process';
            }*/
            $params = [
              'check' => 'PROCESS',
              'key' => md5($this->manager->get('sage_pay_server_securitykey')),
              tep_session_name() => tep_session_id()
            ];
            if ($this->manager->has('pay_order_id') && is_numeric($this->manager->get('pay_order_id'))) {
              $params['order_id'] = $this->manager->get('pay_order_id');
            }

            $result = 'Status=OK' . chr(13) . chr(10) .
                      'RedirectURL=' . $this->getCheckoutUrl($params, self::PROCESS_PAGE);
                //tep_href_link($controller, 'check=PROCESS&key=' . md5($this->manager->get('sage_pay_server_securitykey')) . '&' . tep_session_name() . '=' . tep_session_id(), 'SSL', false);
          }
        } else {
          $this->manager->remove('sage_pay_server_securitykey');
          $this->manager->remove('sage_pay_server_nexturl');

          $error = $post['StatusDetail'];

          $error_url = tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code . (tep_not_null($error) ? '&error=' . $error : '') . '&' . tep_session_name() . '=' . tep_session_id(), 'SSL', false);

          if ($this->popUpMode()) {
            $error_url = \Yii::$app->urlManager->createAbsoluteUrl(['callback/webhooks', 'set' => 'payment', 'action' => 'error', 'module' => $this->code, 'redirect' => $error_url]);
          }

          $result = 'Status=INVALID' . chr(13) . chr(10) .
                    'RedirectURL=' . $error_url;
        }

        echo $result;
        exit;
    }

    public function isPartlyPaid() {
      $ret = parent::isPartlyPaid();
      if ($this->manager->has('pay_order_id') && is_numeric($this->manager->get('pay_order_id'))) {
        $ret = true;
        $this->manager->getOrderInstanceWithId('\common\classes\Order', $this->manager->get('pay_order_id'));
      }
      return $ret;
    }

    function before_process() {
      
      if ($this->debug) {
        \Yii::warning(print_r(\Yii::$app->request->post(), 1), 'SAGEPAY_RESPONSE POST');
      }

      $error = null;
      $order = $this->manager->getOrderInstance();
      $customer_id = $this->manager->getCustomerAssigned();
      $cartID = (string)$this->manager->get('cartID');

      $mode = \Yii::$app->request->get('check', false);

      if ($mode == 'SERVER') {
        //step2 confirm payment notification with redirect URL
          $this->safeServer();

      } elseif ($mode == 'PROCESS') {
        //step3 redirect URL OK
        if (\Yii::$app->request->get('key', false) == md5($this->manager->get('sage_pay_server_securitykey'))) {
          $this->manager->remove('sage_pay_server_securitykey');
          $this->manager->remove('sage_pay_server_nexturl');
          return true;
        }
      } else {
        //step1 init payment
        $partlyPaid = '';
        $_amount = $this->formatRaw(($order->info['total_inc_tax']?$order->info['total_inc_tax']:$order->info['total']));
        $_cur = \Yii::$app->settings->get('currency');
        if ($this->isPartlyPaid()){
          $partlyPaid = '&partlypaid=1';
          if ($order->info['currency'] != $_cur) {
            $_cur = $order->info['currency'];
            $_amount = $this->formatRaw(($order->info['total_inc_tax']?$order->info['total_inc_tax']:$order->info['total']), $_cur, $order->info['currency_value']);
          }
        }
        $params = array('VPSProtocol' => $this->api_version,
                        'ReferrerID' => $this->referrer,
                        'Vendor' => substr(MODULE_PAYMENT_SAGE_PAY_SERVER_VENDOR_LOGIN_NAME, 0, 15),
                        'VendorTxCode' => substr(date('YmdHis') . '-' . $customer_id . '-' . $cartID, 0, 40),
                        'Amount' => $_amount,
                        'Currency' => $_cur,
                        'Description' => substr(STORE_NAME, 0, 100),
                        'NotificationURL' => tep_href_link('callback/sage-server', 'check=SERVER&' . tep_session_name() . '=' . tep_session_id().$partlyPaid, 'SSL', false),
                        'BillingSurname' => substr($order->billing['lastname'], 0, 20),
                        'BillingFirstnames' => substr($order->billing['firstname'], 0, 20),
                        'BillingAddress1' => substr($order->billing['street_address'], 0, 100),
                        'BillingCity' => substr($order->billing['city'], 0, 40),
                        'BillingPostCode' => substr($order->billing['postcode'], 0, 10),
                        'BillingCountry' => $order->billing['country']['iso_code_2'],
                        'BillingPhone' => substr($order->customer['telephone'], 0, 20),
                        'DeliverySurname' => substr($order->delivery['lastname'], 0, 20),
                        'DeliveryFirstnames' => substr($order->delivery['firstname'], 0, 20),
                        'DeliveryAddress1' => substr($order->delivery['street_address'], 0, 100),
                        'DeliveryCity' => substr($order->delivery['city'], 0, 40),
                        'DeliveryPostCode' => substr($order->delivery['postcode'], 0, 10),
                        'DeliveryCountry' => @$order->delivery['country']['iso_code_2'],
                        'DeliveryPhone' => substr($order->customer['telephone'], 0, 20),
                        'CustomerEMail' => substr($order->customer['email_address'], 0, 255),
                        //'ApplyAVSCV2' => '2',
                        'Apply3DSecure' => '0');

        $ip_address = \common\helpers\System::get_ip_address();
        if ($this->manager->has('ptoken') && !empty($this->manager->get('ptoken'))) {
          $params['Token'] = $this->manager->get('ptoken');
          $params['StoreToken'] = 1;
          if (defined('MODULE_PAYMENT_SAGE_PAY_3DS_SKIP') && (float)MODULE_PAYMENT_SAGE_PAY_3DS_SKIP >= $params['Amount']) {
            $params['Apply3DSecure'] = '2';
          }
        }

        if ($this->onBehalf()) {
          $params['Apply3DSecure'] = '2';
          $params['AccountType'] = 'M'; // by default 'E' so not set.
        }

        if ($this->manager->has('use_token') && !empty($this->manager->get('use_token'))) {
          $params['CreateToken'] = 1;
        }

        if ( (ip2long($ip_address) != -1) && (ip2long($ip_address) != false) ) {
          $params['ClientIPAddress']= $ip_address;
        }

        if ( MODULE_PAYMENT_SAGE_PAY_SERVER_TRANSACTION_METHOD == 'Payment' ) {
          $params['TxType'] = 'PAYMENT';
        } elseif ( MODULE_PAYMENT_SAGE_PAY_SERVER_TRANSACTION_METHOD == 'Deferred' ) {
          $params['TxType'] = 'DEFERRED';
        } else {
          $params['TxType'] = 'AUTHENTICATE';
        }

        if ($params['BillingCountry'] == 'US') {
          $params['BillingState'] = \common\helpers\Zones::get_zone_code($order->billing['country']['id'], $order->billing['zone_id'], '');
        }

        if ($params['DeliveryCountry'] == 'US') {
          $params['DeliveryState'] = \common\helpers\Zones::get_zone_code($order->delivery['country']['id'], $order->delivery['zone_id'], '');
        }

        /* doesn't work now as no separate page/popup mode*/
         if ( MODULE_PAYMENT_SAGE_PAY_SERVER_PROFILE_PAGE != 'Normal' ) {
          $params['Profile'] = 'LOW';
        }
/*         */

        $contents = array();

        foreach ($order->products as $product) {
          $product_name = $product['name'];

          if (isset($product['attributes'])) {
            foreach ($product['attributes'] as $att) {
              $product_name .= '; ' . $att['option'] . '=' . $att['value'];
            }
          }

          $contents[] = str_replace(array(':', "\n", "\r", '&'), '', $product_name) . ':' . $product['qty'] . ':' . $this->formatRaw($product['final_price']) . ':' . $this->formatRaw(($product['tax'] / 100) * $product['final_price']) . ':' . $this->formatRaw((($product['tax'] / 100) * $product['final_price']) + $product['final_price']) . ':' . $this->formatRaw(((($product['tax'] / 100) * $product['final_price']) + $product['final_price']) * $product['qty']);
        }

        $order_totals = ($order->totals && $order->order_id? $order->totals : $this->manager->getTotalOutput(true, 'TEXT_CHECKOUT'));
        foreach ($order_totals as $ot) {
          $contents[] = str_replace(array(':', "\n", "\r", '&'), '', strip_tags($ot['title'])) . ':---:---:---:---:' . $this->formatRaw($ot['value']);
        }

        $params['Basket'] = substr(sizeof($contents) . ':' . implode(':', $contents), 0, 7500);

        $post_string = '';

        foreach ($params as $key => $value) {
          $post_string .= $key . '=' . urlencode(trim($value)) . '&';
        }

        $gateway_url = $this->getApiUrl('transaction', MODULE_PAYMENT_SAGE_PAY_SERVER_TRANSACTION_SERVER);
        if ($this->debug) {
          \Yii::warning($gateway_url . 'post  => ' . $post_string, 'SAGEPAY_SERVER_REQUEST');
        }

        $transaction_response = $this->sendRequest($gateway_url, ['post' => $post_string, 'headerOut' =>1]);
        $return = $this->parseResponce($transaction_response['headers']);

        if ($this->debug) {
          \Yii::warning(print_r($return, 1), 'SAGEPAY_SERVER_RESPONCE');
        }
        if (!empty($return['Status']) && $return['Status'] == 'OK') {
          $this->manager->set('sage_pay_server_securitykey', $return['SecurityKey']);
          $this->manager->set('sage_pay_server_nexturl', $return['NextURL']);

          tep_redirect($return['NextURL']);

          /*if ( MODULE_PAYMENT_SAGE_PAY_SERVER_PROFILE_PAGE == 'Normal' ) {
            tep_redirect($return['NextURL']);
          } else {
            tep_redirect(tep_href_link('checkout_sage_pay.php', '', 'SSL'));
          }*/
        } else {
          $error = $return['StatusDetail']['description'];
        }
      }

      $this->manager->remove('sage_pay_server_securitykey');
      $this->manager->remove('sage_pay_server_nexturl');
      
      $error_url = tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code . (tep_not_null($error) ? '&error=' . $error : ''), 'SSL');
      if ($this->popUpMode()) {
            $result = <<<EOD
                <html><body style="text-align:center">
                    <script>window.top.location.href = '{$error_url}';</script>
                    <a href="{$error_url}" target="_top">Click here if form is not redirected automatically.</a>
                    </body></html>
EOD;
                    echo $result;
                    exit;
          } else
      tep_redirect($error_url);
    }

    function after_process() {

      $response = $this->manager->get('sage_pay_server_additional_info');
/*
    [VPSProtocol] => 3.00
    [TxType] => PAYMENT
    [VendorTxCode] => 20190812140709-532-70277
    [VPSTxId] => {015B3E87-0692-4DE9-36B8-711CD784B749}
    [Status] => OK
    [StatusDetail] => 0000 : The Authorisation was Successful.
    [TxAuthNo] => 2147027
    [AVSCV2] => SECURITY CODE MATCH ONLY
    [AddressResult] => NOTMATCHED
    [PostCodeResult] => NOTMATCHED
    [CV2Result] => MATCHED
    [GiftAid] => 0
    [3DSecureStatus] => NOTCHECKED
    [CardType] => AMEX
    [Last4Digits] => 0004
    [VPSSignature] => 1C36F8E702B99C28CB16AFEF1AE389DD
    [DeclineCode] => 00
    [ExpiryDate] => 1222
    [Token] => {3C533492-2F60-6956-F1C7-53BFBA401945}
    [BankAuthCode] => 99972
 */
      $this->manager->remove('sage_pay_server_additional_info');
      $this->manager->remove('ptoken');
      $this->manager->remove('use_token');
      
      $this->manager->clearAfterProcess();

      $order = $this->manager->getOrderInstance();

      $response['orderId'] = $response['VPSTxId'];
      if ($response['TxType'] == 'PAYMENT' && $response['Status'] == 'OK') {
        $statusCode = OrderPaymentHelper::OPYS_SUCCESSFUL;
      } else {
        if (in_array($response['Status'], ['AUTHENTICATED', 'REGISTERED'])) {
          $statusCode = OrderPaymentHelper::OPYS_PROCESSING;
        } else {
          $statusCode = OrderPaymentHelper::OPYS_PENDING;
        }
      }
      $trans = $this->getTransactionDetails($response['VPSTxId'], null, true);
      if ($trans) {
        $response = array_merge($response, $trans);
      } else {
        $response['amount'] = $order->info['total']; // suppose pay in full at once
      }

      //{{ transactions
      /** @var \common\services\PaymentTransactionManager $tManager */
      $tManager = $this->manager->getTransactionManager($this);
      $invoice_id = $this->manager->getOrderSplitter()->getInvoiceId();
      $tManager->addTransaction($response['orderId'], 'Success', $response['amount'], $invoice_id, 'Customer\'s payment');
      //{{

      $orderPayment = $this->searchRecord($response['orderId']);
      $orderPayment->orders_payment_order_id = $order->getOrderId();
      $orderPayment->orders_payment_snapshot = json_encode(OrderPaymentHelper::getOrderPaymentSnapshot($order));
      $orderPayment->orders_payment_status = $statusCode;
      $orderPayment->orders_payment_amount = (float)$response['amount'];
      $orderPayment->orders_payment_currency = trim($order->info['currency']);
      $orderPayment->orders_payment_currency_rate = (float)$order->info['currency_value'];
      $orderPayment->orders_payment_transaction_date = new \yii\db\Expression('now()');
      $orderPayment->orders_payment_transaction_id = $response['orderId'];
      $orderPayment->orders_payment_transaction_status = $response['Status'];
      $orderPayment->orders_payment_transaction_commentary = print_r($response, 1);
      $orderPayment->save(false);
      //}} transactions

      if ($this->onBehalf()) {
        \Yii::$app->settings->set('from_admin', false);
        if (!\Yii::$app->user->isGuest){
          \Yii::$app->user->getIdentity()->logoffCustomer();
        }
        echo TEXT_ON_BEHALF_PAYMENT_SUCCESSFUL;
        die;
      }

      tep_redirect(tep_href_link('callback/redirect-by-js', '', 'SSL'));// JS redirect
    }

    function get_error() {

      $error = \Yii::$app->request->get('error', '');
      $message = \Yii::$app->request->get('message', '');

      if (!empty($message)) {
          $error = stripslashes(urldecode($message));
      } else {
          $error = stripslashes(urldecode($error));
      }

      $error = array('title' => MODULE_PAYMENT_SAGE_PAY_SERVER_ERROR_TITLE,
                     'error' => MODULE_PAYMENT_SAGE_PAY_SERVER_ERROR_GENERAL . ' ' . strip_tags($error));

      return $error;
    }

    public function describe_status_key()
    {
      return new ModuleStatus('MODULE_PAYMENT_SAGE_PAY_SERVER_STATUS','True','False');
    }

    public function describe_sort_key()
    {
      return new ModuleSortOrder('MODULE_PAYMENT_SAGE_PAY_SERVER_SORT_ORDER');
    }

    public function configure_keys()
    {
        $status_id = defined('MODULE_PAYMENT_SAGE_PAY_SERVER_ORDER_STATUS_ID') ? MODULE_PAYMENT_SAGE_PAY_SERVER_ORDER_STATUS_ID : $this->getDefaultOrderStatusId();

      return array(
        'MODULE_PAYMENT_SAGE_PAY_SERVER_STATUS' => array(
          'title' => 'Enable Sage Pay Server Module',
          'value' => 'False',
          'description' => 'Do you want to accept Sage Pay Server payments?',
          'sort_order' => '0',
          'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
        ),
        'MODULE_PAYMENT_SAGE_PAY_SERVER_VENDOR_LOGIN_NAME' => array(
          'title' => 'Vendor Login Name',
          'value' => '',
          'description' => 'The vendor login name to connect to the gateway with.',
          'sort_order' => '0',
        ),
        'MODULE_PAYMENT_SAGE_PAY_SERVER_ACCOUNT' => array(
          'title' => 'Account login',
          'value' => '',
          'description' => 'Account login to get transaction details',
          'sort_order' => '0',
        ),
        'MODULE_PAYMENT_SAGE_PAY_SERVER_ACCOUNT_PASSWORD' => array(
          'title' => 'Account Password',
          'value' => '',
          'description' => 'Account password to get transaction details',
          'sort_order' => '0',
        ),
        'MODULE_PAYMENT_SAGE_PAY_SERVER_PROFILE_PAGE' => array(
          'title' => 'Profile Payment Page',
          'value' => 'Normal',
          'description' => 'Profile page to use for the payment page.',
          'sort_order' => '0',
          'set_function' => 'tep_cfg_select_option(array(\'Normal\', \'Low\'), ',
        ),
        'MODULE_PAYMENT_SAGE_PAY_SERVER_TRANSACTION_METHOD' => array(
          'title' => 'Transaction Method',
          'value' => 'Authenticate',
          'description' => 'The processing method to use for each transaction.',
          'sort_order' => '0',
          'set_function' => 'tep_cfg_select_option(array(\'Authenticate\', \'Deferred\', \'Payment\'), ',
        ),
        'MODULE_PAYMENT_SAGE_PAY_SERVER_TRANSACTION_SERVER' => array(
          'title' => 'Transaction Server',
          'value' => 'Simulator',
          'description' => 'Perform transactions on the production server or on the testing server.',
          'sort_order' => '0',
          'set_function' => 'tep_cfg_select_option(array(\'Live\', \'Test\', \'Simulator\'), ',
        ),
        'MODULE_PAYMENT_SAGE_PAY_SERVER_USE_TOKENS' => array(
          'title' => 'Allow tokens',
          'value' => 'False',
          'description' => 'Allow to save tokens.',
          'sort_order' => '0',
          'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
        ),
        'MODULE_PAYMENT_SAGE_PAY_SERVER_ZONE' => array(
          'title' => 'Payment Zone',
          'value' => '0',
          'description' => 'If a zone is selected, only enable this payment method for that zone.',
          'sort_order' => '2',
          'use_function' => '\\common\\helpers\\Zones::get_zone_class_title',
          'set_function' => 'tep_cfg_pull_down_zone_classes(',
        ),
        'MODULE_PAYMENT_SAGE_PAY_SERVER_ORDER_STATUS_ID' => array(
          'title' => 'Set Order Status',
          'value' => $status_id,
          'description' => 'Set the status of orders made with this payment module to this value',
          'sort_order' => '0',
          'set_function' => 'tep_cfg_pull_down_order_statuses(',
          'use_function' => '\\common\\helpers\\Order::get_order_status_name',
        ),
        'MODULE_PAYMENT_SAGE_PAY_3DS_SKIP' => array(
          'title' => 'Skip 3D secure amount',
          'value' => '',
          'description' => 'Skip 3D secure verification when paid by token on orders below',
          'sort_order' => '100',
        ),
        /*'MODULE_PAYMENT_SAGE_PAY_SERVER_CURL' => array(
          'title' => 'cURL Program Location',
          'value' => '/usr/bin/curl',
          'description' => 'The location to the cURL program application.',
          'sort_order' => '0',
        ),*/
        'MODULE_PAYMENT_SAGE_PAY_SERVER_SORT_ORDER' => array(
          'title' => 'Sort order of display.',
          'value' => '0',
          'description' => 'Sort order of display. Lowest is displayed first.',
          'sort_order' => '0',
        ),
      );
    }

    function isOnline() {
        return true;
    }

/**
 * checks whether the module supports token system and tokens allowed on the site.
 * @return bool
 */
    public function hasToken(): bool {
       return true && parent::tokenAllowed();
    }

/**
 * checks whether the module hasToken and its enabled on the module.
 * @return bool
 */
    public function useToken(): bool {
       return ($this->hasToken() && defined('MODULE_PAYMENT_SAGE_PAY_SERVER_USE_TOKENS') && MODULE_PAYMENT_SAGE_PAY_SERVER_USE_TOKENS  == 'True');
    }

/**
 * parse array of strings [ 0 => 'k1=v1', 1=>''] to associative array [k1=>v1, k2=>v2]
 * @param array $res
 * @return array associative array  [k1=>v1, k2=>v2]
 */
    private function parseResponce($res) {
      $ret = [];
      if (!is_array($res)) {
        $res = [$res];
      }
      foreach ($res as $string) {
        if (strpos($string, '=') != false) {
         $parts = explode('=', $string, 2);
         $key = trim($parts[0]);
         if ($key=='StatusDetail') {
           $val = [
             'code' => trim(substr($parts[1], 0, strpos($parts[1], ':'))),
             'description' => trim($parts[1])
             ];
         } else {
           $val = trim($parts[1]);
         }
         $ret[$key] = $val;
       }

      }
      return $ret;
    }

/**
 *
 * @param string $action transaction | token | remove-token |token-remove | refund |void
 * @param string $mode Live | Test else Simulator
 * @return string gateway URL
 */
    private function getApiUrl($action, $mode) {
      $gateway_url = '';
      switch ($mode) {
        case 'Live':
          $gateway_url = 'https://live.sagepay.com';
          break;

        case 'Test':
          $gateway_url = 'https://test.sagepay.com';
          break;

        default:
          $gateway_url = 'https://test.sagepay.com/Simulator/VSPServerGateway.asp?Service=VendorRegisterTx';
          $action = '';
          break;
      }

      $action = strtolower($action);

      switch ($action) {
        case 'transaction':
          $gateway_url .= '/gateway/service/vspserver-register.vsp';
          break;
        case 'token':
          $gateway_url .= '/gateway/service/token.vsp';
          break;
        case 'refund':
          $gateway_url .= '/gateway/service/refund.vsp';
          break;
        case 'release':
          $gateway_url .= '/gateway/service/release.vsp';
          break;
        case 'void':
          $gateway_url .= '/gateway/service/void.vsp';
          break;
        case 'status':
          $gateway_url .= '/access/access.htm';
          break;
        case 'token-remove':
        case 'remove-token':
          $gateway_url .= '/gateway/service/removetoken.vsp';
          break;
      }

      return $gateway_url;
    }

    /**
     * Note it doesn't try to delete token at gateway again in case of any error
     * @param int $customersId
     * @param string  $token
     * @return int number of token deleted in DB
     */
    public function deleteToken($customersId, $token) {
      if ($ret = parent::deleteToken($customersId, $token)) {
        $params = ['VPSProtocol' => $this->api_version,
                   'Token' => $token,
                   'TxType' => 'REMOVETOKEN',
                   'Vendor' => substr(MODULE_PAYMENT_SAGE_PAY_SERVER_VENDOR_LOGIN_NAME, 0, 15)
          ];

        $post_string = '';
        foreach ($params as $key => $value) {
          $post_string .= $key . '=' . urlencode(trim($value)) . '&';
        }

        $gateway_url = $this->getApiUrl('remove-token', MODULE_PAYMENT_SAGE_PAY_SERVER_TRANSACTION_SERVER);

        $transaction_response = $this->sendRequest($gateway_url, ['post' => $post_string, 'headerOut' =>1]);
        $return = $this->parseResponce($transaction_response['headers']);

        if (!empty($return['Status']) && $return['Status'] == 'OK') {

        } else {
          \Yii::warning('token wasn\'t removed ' . print_r($return,1), 'SAGEPAY_SERVER_TOKEN');
        }
      }

      return $ret;
    }

    public function canRefund($transaction_id) {

      $ret = false;
      $orderPayment = $this->getTransactionDetails($transaction_id);

      if ($orderPayment && OrderPaymentHelper::getAmountAvailable($orderPayment)){
        $ret = true;
      }
      return $ret;
    }

    public function canVoid($transaction_id) {
      //vl2do
      return false;

      $ret = false;
      $orderPayment = $this->getTransactionDetails($transaction_id);

      if ($orderPayment && $orderPayment->orders_payment_status==OrderPaymentHelper::OPYS_PROCESSING){
        $ret = true;
      }
      return $ret;

    }

    public function refund($transaction_id, $amount = 0) {
      $ret = false;

      $transaction = $this->getTransactionDetails($transaction_id, null, true);

      /**
       *     [vpstxid] => B90FFFC3-DA13-BC45-FE1A-7FA6FE2F10C8
    [vendortxcode] => PAYMENT-1565361506-811596439
    [transactiontype] => Payment
    [txstateid] => 8
    [status] => Transaction CANCELLED by Sage Pay after 15 minutes of inactivity.  This is normally because the customer closed their browser.
    [description] => Barcall shopping
    [amount] => 412.27
    [currency] => GBP
    [started] => 09/08/2019 15:38:26.167
    [completed] => 09/08/2019 15:59:29.140
    [securitykey] => TLQKBDDAMW
       */

        $params = array('VPSProtocol' => $this->api_version,
                      'Vendor' => substr(MODULE_PAYMENT_SAGE_PAY_SERVER_VENDOR_LOGIN_NAME, 0, 15),
                      'VendorTxCode' => substr(date('YmdHis') . '-' . $transaction['vpstxid'], 0, 40),
                      'Amount' => $amount,
                      'Currency' => $transaction['currency'],
                      'Description' => substr(STORE_NAME, 0, 100),
                      'RelatedVPSTxId' => $transaction_id,
                      'RelatedVendorTxCode' => $transaction['vendortxcode'],
                      'RelatedSecurityKey' => $transaction['securitykey'],
                      'RelatedTxAuthNo' => (is_array($transaction['vpsauthcode'])?$transaction['vpsauthcode'][0]:$transaction['vpsauthcode']),
                      'TxType' => 'REFUND');


        $post_string = '';

        foreach ($params as $key => $value) {
          $post_string .= $key . '=' . urlencode(trim($value)) . '&';
    }

        $gateway_url = $this->getApiUrl('refund', MODULE_PAYMENT_SAGE_PAY_SERVER_TRANSACTION_SERVER);
        if ($this->debug) {
          \Yii::warning($gateway_url . 'post  => ' . $post_string, 'SAGEPAY_SERVER_REQUEST');
        }

        $transaction_response = $this->sendRequest($gateway_url, ['post' => $post_string, 'headerOut' =>1]);
        $response = $this->parseResponce($transaction_response['headers']);

        if ($this->debug) {
          \Yii::warning(print_r($response, 1), 'SAGEPAY_SERVER_RESPONCE');
          /*
  [VPSProtocol] => 3.00
    [Status] => OK
    [StatusDetail] => Array
        (
            [code] => 0000
            [description] => 0000 : The Authorisation was Successful.
        )

    [SecurityKey] => EK2PHXBULY
    [TxAuthNo] => 2602459
    [VPSTxId] => {2D5AACEE-C6CD-0AD5-FA51-1CC429A64F03}
           */
        }
        if (!empty($response['Status']) && $response['Status'] == 'OK') {
          $this->manager->getTransactionManager($this)
                        ->addTransactionChild($transaction_id, $response['VPSTxId'], $response['Status'], $amount, ($amount? 'Refund':'Refund'));
                $currencies = \Yii::$container->get('currencies');
                $order = $this->manager->getOrderInstance();
                $order->info['comments'] = "Refund State: " . $response['Status'] . "\n" .
                        "Refund Amount: " . $currencies->format($amount, true, $order->info['currency'], $order->info['currency_value']);
          $this->_savePaymentTransactionRefund(['transaction_id'=>$response['VPSTxId'], 'status'=>$response['Status'], 'amount'=>$amount] , $transaction_id);

        }
      return $ret;
    }
    
    private function _savePaymentTransactionRefund($response, $transaction_id){
        $orderPaymentParentRecord = $this->searchRecord($transaction_id);
        if ($orderPaymentParentRecord) {
            $orderPaymentRecord = $this->searchRecord($response['transaction_id']);
            if ($orderPaymentRecord) {
                $order = $this->manager->getOrderInstance();
                $orderPaymentRecord->orders_payment_id_parent = (int)$orderPaymentParentRecord->orders_payment_id;
                $orderPaymentRecord->orders_payment_order_id = (int)$order->order_id;
                $orderPaymentRecord->orders_payment_is_credit = 1;
                $orderPaymentRecord->orders_payment_status = \common\helpers\OrderPayment::OPYS_REFUNDED;
                $orderPaymentRecord->orders_payment_amount = (float)$response['amount'];
                $orderPaymentRecord->orders_payment_currency = trim($order->info['currency']);
                $orderPaymentRecord->orders_payment_currency_rate = (float)$order->info['currency_value'];
                $orderPaymentRecord->orders_payment_snapshot = json_encode(\common\helpers\OrderPayment::getOrderPaymentSnapshot($order));
                $orderPaymentRecord->orders_payment_transaction_status = trim($response['status']);
                $orderPaymentRecord->orders_payment_transaction_date = date('Y-m-d H:i:s');
                $orderPaymentRecord->orders_payment_date_create = date('Y-m-d H:i:s');
                $orderPaymentRecord->save();
            }
        }
    }

    public function void($transaction_id) {
      //vl2do
      return false;
    }
    
    /**
     * get transaction details from DB or from server (
     * @param string  $transaction_id
     * @param \common\services\PaymentTransactionManager $tManager
     * @param bool $force
     */
    public function getTransactionDetails($transaction_id, \common\services\PaymentTransactionManager $tManager = null, $force = false) {
      $ret = false;
      $orderPayment = $this->searchRecord($transaction_id);
      if (!$force && $orderPayment) {
        $ret = $orderPayment;

      } else {
      
      $vendor = strtolower(substr(MODULE_PAYMENT_SAGE_PAY_SERVER_VENDOR_LOGIN_NAME, 0, 15));
        $user = defined('MODULE_PAYMENT_SAGE_PAY_SERVER_ACCOUNT')?MODULE_PAYMENT_SAGE_PAY_SERVER_ACCOUNT:false;
        $xml = '<command>getTransactionDetail</command><vendor>' . $vendor . '</vendor><user>' . $user . '</user><vpstxid>' . $transaction_id . '</vpstxid>';
      $signature = $this->md5hash($xml);
      $xml = '<vspaccess>' . $xml . '<signature>'. $signature . '</signature></vspaccess>';
        if ($vendor && $signature) {
      $url = $this->getApiUrl('status', 'Test');
      $client = new \yii\httpclient\Client([
            'baseUrl' => $url,
            'parsers' => [
                'xml' => '\yii\httpclient\XmlParser',
            ]
        ]);
      $response = $client->post('',  'XML=' . $xml)->send();
          $ret = $response->getData();
          if (is_array($ret) && is_array($orderPayment)) {
            $ret = array_merge($orderPayment, $ret);
          }
        }
      }
      return $ret;

/*
  [errorcode] => 0000
    [timestamp] => 12/08/2019 20:55:27
    [vpstxid] => B90FFFC3-DA13-BC45-FE1A-7FA6FE2F10C8
    [vendortxcode] => PAYMENT-1565361506-811596439
    [transactiontype] => Payment
    [txstateid] => 8
    [status] => Transaction CANCELLED by Sage Pay after 15 minutes of inactivity.  This is normally because the customer closed their browser.
    [description] => Barcall shopping
    [amount] => 412.27
    [currency] => GBP
    [started] => 09/08/2019 15:38:26.167
    [completed] => 09/08/2019 15:59:29.140
    [securitykey] => TLQKBDDAMW
    [clientip] => 92.27.209.139
    [giftaid] => NO
    [paymentsystemdetails] => Debit Card
    [accounttype] => E
    [vpsauthcode] => Array
        (
        )

    [bankauthcode] => Array
        (
        )

    [billingfirstnames] => John
    [billingsurname] => Doe
    [billingaddress] => 44 Main street
    [billingaddress2] => test
    [billingcity] => Boston
    [billingstate] => MA
    [billingpostcode] => 02134
    [billingcountry] => US
    [billingphone] => Array
        (
        )

    [deliveryfirstnames] => John
    [deliverysurname] => Doe
    [deliveryaddress] => 45 Main street
    [deliveryaddress2] => test 2
    [deliverycity] => Boston 2
    [deliverystate] => MA
    [deliverypostcode] => 02135
    [deliverycountry] => US
    [deliveryphone] => Array
        (
        )

    [customername] => John Doe
    [customeremail] => alavrentyev@simtechdev.us
    [systemused] => S
    [vpsprotocol] => 3.00
    [callbackurl] => https://alavrentyev.simtechdev.us/barcall/index.php?dispatch=payment_notification.sagepay_iframe..250
    [refunded] => NO
    [repeated] => NO
    [basketxml] => Array
        (
            [basket] => Array
                (
                    [item] => Array
                        (
                            [description] => X-Box One
                            [productSku] => L2571ODER9
                            [quantity] => 1
                            [unitNetAmount] => 338.43
                            [unitTaxAmount] => 33.84
                            [unitGrossAmount] => 372.27
                            [totalGrossAmount] => 372.27
                        )

                    [deliveryTaxAmount] => 40.00
                    [deliveryGrossAmount] => 40.00
                )

        )

    [applyavscv2] => 0
    [apply3dsecure] => 0
    [authattempt] => 0
    [cv2result] => NOTPROVIDED
    [addressresult] => NOTPROVIDED
    [postcoderesult] => NOTPROVIDED
    [threedresult] => NOTCHECKED
    [t3maction] => NORESULT
    [locale] => en
    [surcharge] => 0.00
  */


    }

    private function md5hash($xml) {
      $password = defined('MODULE_PAYMENT_SAGE_PAY_SERVER_ACCOUNT_PASSWORD')?MODULE_PAYMENT_SAGE_PAY_SERVER_ACCOUNT_PASSWORD:false;
      $signature = false;
      if ($password) {
      $signature = md5($xml . '<password>' . $password . '</password>');
      }
      return $signature;
    }

    public function popUpMode() {
      $ret = false;
      if ($this->isWithoutConfirmation() && MODULE_PAYMENT_SAGE_PAY_SERVER_PROFILE_PAGE != 'Normal' && !(bool)\Yii::$app->settings->get('from_admin')) {
        $ret = true;
      }
      return $ret;
    }

    public function call_webhooks() {
      $error_url = \Yii::$app->request->get('redirect', '');
      $result = false;
      if (!empty($error_url)) {
                    $result = <<<EOD
                <html><body style="text-align:center">
                    <script>window.top.location.href = '{$error_url}';</script>
                    <a href="{$error_url}">Click here if form is not redirected automatically.</a>
                    </body></html>
EOD;
      }
      echo $result;
      return $result;


    }
    
     public function search($queryParams) {
       $found = [];
        $vendor = strtolower(substr(MODULE_PAYMENT_SAGE_PAY_SERVER_VENDOR_LOGIN_NAME, 0, 15));
        $user = defined('MODULE_PAYMENT_SAGE_PAY_SERVER_ACCOUNT')?MODULE_PAYMENT_SAGE_PAY_SERVER_ACCOUNT:false;
        $xml = '<command>getTransactionList</command><vendor>' . $vendor . '</vendor><user>' . $user . '</user><sorttype>ByDate</sorttype><sortorder>DESC</sortorder>';
        if ($queryParams['STARTDATE']) {
          $xml .= '<startdate>' . gmdate("d/m/Y H:i:s", strtotime($queryParams['STARTDATE'])) . '</startdate>';
        }
        if ($queryParams['ENDDATE']) {
          $xml .= '<enddate>' . gmdate("d/m/Y H:i:s", strtotime($queryParams['ENDDATE'])) . '</enddate>';
        }
        if ($queryParams['TRANSACTIONID']) {
          $xml .= '<relatedtransactionid>' . $queryParams['TRANSACTIONID'] . '</relatedtransactionid>';
        }
        if ($queryParams['SEARCHPHRASE']) {
          $xml .= '<searchphrase>' . $queryParams['SEARCHPHRASE'] . '</searchphrase>';
        }

    
        $signature = $this->md5hash($xml);
        $xml = '<vspaccess>' . $xml . '<signature>'. $signature . '</signature></vspaccess>';
        if ($vendor && $signature) {
          $url = $this->getApiUrl('status', 'Test');
          $client = new \yii\httpclient\Client([
                'baseUrl' => $url,
                'parsers' => [
                    'xml' => '\yii\httpclient\XmlParser',
                ]
            ]);
          $response = $client->post('',  'XML=' . $xml)->send();
          $response = $response->getData();
        }
        if ($response['errorcode'] == '0000') {
          $ret = $response['transactions'];
          $found[] = [
              'id' => 0,
              'date' => '',
              'amount' => '',
              'negative' => 0,
              'name' => 'Shown ' . $ret['endrow'] . ' of ' . $ret['totalrows'],
              'status' => 0,
          ];
          $currencies = \Yii::$container->get('currencies');
          if (is_array($ret['transaction'])) {
            foreach ($ret['transaction'] as $t) {
              $t = (array)$t;
              $found[] = [
                  'id' => $t['vpstxid'],
                  //'date' => \common\helpers\Date::formatDateTimeJS($t['started']),
                  'date' => $t['started'],
                  'amount' => $currencies->format($t['amount'], true, $t['currency']),
                  'negative' => $t['transactiontype'] != 'Payment',
                  'name' =>   $t['cardholder'],
                  'status' => $t['result'],
              ];
            }
          }
        } else  {
          $found[] = ['name' => $response['error']];
        }

        return $found;
    }

    public function getFields(){
        return [
            [['STARTDATE'], 'datetime', 'format' => 'yyyy-MM-dd HH:mm:ss'],
            [['ENDDATE'], 'datetime', 'format' => 'yyyy-MM-dd HH:mm:ss'],
            ['TRANSACTIONID', 'string'],
            ['SEARCHPHRASE', 'string'],
        ];
    }
    /////////
    
}