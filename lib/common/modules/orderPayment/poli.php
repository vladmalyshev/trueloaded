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

use common\classes\modules\ModuleStatus;
use common\classes\modules\ModuleSortOrder;
use common\classes\modules\TransactionalInterface;

/**
 * Class poli
 */
class poli extends \common\classes\modules\ModulePayment implements TransactionalInterface {

  const AS_TEMP_ORDER = false; //2do transform to real order on callback

  private $debug = false;
  private $_poliUrl = 'https://poliapi.apac.paywithpoli.com';
  private $_APIActions = [
    'init' => '/api/v2/Transaction/Initiate',
    'get' => '/api/v2/Transaction/GetTransaction', //?token={transactionToken}
    'getDaily' => '/api/v2/Transaction/GetDailyTransactions', //?date=2018-01-01&statuscodes=Completed,ReceiptUnverified
  ];
  private $_poliUserId;
  private $_poliKey;
  public $paid_status;
  public $processing_status;
  public $fail_paid_status;
  protected $defaultTranslationArray = [
    'MODULE_PAYMENT_POLI_TEXT_TITLE' => 'Poli',
    'MODULE_PAYMENT_POLI_TEXT_DESCRIPTION' => 'Poli',
    'MODULE_PAYMENT_POLI_TEXT_NOTES' => ''
  ];

  public function __construct() {
    parent::__construct();

    $this->code = 'poli';
    $this->title = defined('MODULE_PAYMENT_POLI_TEXT_TITLE') ? MODULE_PAYMENT_POLI_TEXT_TITLE : 'Poli';
    $this->description = defined('MODULE_PAYMENT_POLI_TEXT_DESCRIPTION') ? MODULE_PAYMENT_POLI_TEXT_DESCRIPTION : 'Poli';
    $this->enabled = true;

    if (!defined('MODULE_PAYMENT_POLI_STATUS')) {
      $this->enabled = false;
      return;
    }
    $this->_poliKey = defined('MODULE_PAYMENT_POLI_KEY') ? MODULE_PAYMENT_POLI_KEY : '';
    $this->_poliUserId = defined('MODULE_PAYMENT_POLI_USER_ID') ? MODULE_PAYMENT_POLI_USER_ID : '';
    $this->paid_status = MODULE_PAYMENT_POLI_ORDER_PAID_STATUS_ID;
    $this->processing_status = MODULE_PAYMENT_POLI_ORDER_PROCESS_STATUS_ID;
    $this->fail_paid_status = MODULE_PAYMENT_POLI_FAIL_PAID_STATUS_ID;

    $this->update();
  }

  private function update() {
    if (!$this->_poliUserId || !$this->_poliKey) {
      $this->enabled = false;
    }
  }

  function before_process() {
    $order = $this->manager->getOrderInstance();
    $order->info['order_status'] = $this->processing_status;

    $poli_order_id = \Yii::$app->request->get('returned_order', '');

    if (empty($poli_order_id)) {
      $order->order_id = $poli_order_id = $this->saveOrder(self::AS_TEMP_ORDER);
    }

    if (!$this->manager->has('poli_order_id')) {
      $this->manager->set('poli_order_id', $poli_order_id);
    }

    return $this->redirectForm();
  }

  function after_process() {

    if (tep_session_is_registered('poli_order_id')) {
      tep_session_unregister('poli_order_id');
    }
    $this->manager->clearAfterProcess();
  }

  private function redirectForm() {
    $order = $this->manager->getOrderInstance();
    $currencies = \Yii::$container->get('currencies');

    $recalculate = ( USE_MARKET_PRICES == 'True' ? false : true );
    $total = $currencies->format_clear($currencies->calculate_price_in_order($order->info, $order->info['total_inc_tax']), $recalculate, $order->info['currency']);

    $data = [
      'MerchantReference' => $order->order_id,
      'MerchantHomepageURL' => \Yii::$app->urlManager->createAbsoluteUrl(['/']),
      'SuccessURL' => \Yii::$app->urlManager->createAbsoluteUrl(['callback/webhooks', 'set' => 'payment', 'action' => 'complete', 'module' => $this->code]),
      'NotificationURL' => \Yii::$app->urlManager->createAbsoluteUrl(['callback/webhooks', 'set' => 'payment', 'module' => $this->code]),
      'FailureURL' => \Yii::$app->urlManager->createAbsoluteUrl(['callback/webhooks', 'set' => 'payment', 'module' => $this->code, 'order_id' => $order->order_id, 'action' => 'fail']),
      'CancellationURL' => \Yii::$app->urlManager->createAbsoluteUrl(['callback/webhooks', 'set' => 'payment', 'module' => $this->code, 'order_id' => $order->order_id, 'action' => 'cancel']),
      'Amount' => $total,
      'CurrencyCode' => $order->info['currency'],
        //'MerchantData'  => custom transactionId??
        /* $order->customer['email_address'] . ' ' .
          $order->billing['telephone'] . ' ' .
          $order->billing['firstname'] . ' ' .
          $order->billing['lastname'], */
    ];
    //Timeout 	The timeout for the transaction in seconds, which defaults to 900 (15 minutes) 	Number of seconds before transaction times out 	Number 	No 	900
    //SelectedFICode 	Used for pre-selecting banks in order to skip the POLi Landing page

    if ($this->debug) {
      \Yii::warning(print_r($data, 1), 'POLIDATA');
    }

    $response = $this->sendRequest($this->_poliUrl . $this->_APIActions['init'], $data);

    if ($this->debug) {
      \Yii::warning(print_r($response, 1), 'POLIRESULT');
    }

    if (tep_session_is_registered('poli_order_id')) {
      tep_session_unregister('poli_order_id');
    }
    if ($this->manager->has('poli_order_id')) {
      $this->manager->remove('poli_order_id');
    }

    $res = $response['response'];

    $url = $res['NavigateURL'];
    $valid = $res['Success'];
    $msg = $res['ErrorMessage'];
    $code = $res['ErrorCode'];
    if (!$valid || empty($url) || $code != 0) {
      tep_redirect(\Yii::$app->urlManager->createAbsoluteUrl(['checkout', 'returned_order' => $order->order_id, 'error_message' => (!empty($msg) ? $msg : (defined('TEXT_GENERAL_PAYMENT_ERROR') ? TEXT_GENERAL_PAYMENT_ERROR : 'Please select different payment method'))]));
    } else {
      header("Location: " . $url);
    }

    die;
  }

  protected function sendRequest($url, $post) {
    $auth = base64_encode($this->_poliUserId . ':' . $this->_poliKey);
    $header = [];
    $header[] = 'Content-Type: application/json';
    $header[] = 'Authorization: Basic ' . $auth;

    $params = [
      'post' => ($post ? json_encode($post) : $post),
      'header' => $header,
      'headerOut' => 0,
    ];

    $response = parent::sendRequest($url, $params);
    $response['response'] = json_decode($response['response'], true);
    return $response;
  }

  function get_error() {
    return (defined('TEXT_GENERAL_PAYMENT_ERROR') ? TEXT_GENERAL_PAYMENT_ERROR : 'Please select different payment method');
  }

  public function describe_status_key() {
    return new ModuleStatus('MODULE_PAYMENT_POLI_STATUS', 'True', 'False');
  }

  public function describe_sort_key() {
    return new ModuleSortOrder('MODULE_PAYMENT_POLI_SORT_ORDER');
  }

  public function configure_keys() {
    $status_id = defined('MODULE_PAYMENT_POLI_ORDER_PAID_STATUS_ID') ? MODULE_PAYMENT_POLI_ORDER_PAID_STATUS_ID : $this->getDefaultOrderStatusId();
    $status_id_p = defined('MODULE_PAYMENT_POLI_ORDER_PROCESS_STATUS_ID') ? MODULE_PAYMENT_POLI_ORDER_PROCESS_STATUS_ID : $this->getDefaultOrderStatusId();
    $status_id_f = defined('MODULE_PAYMENT_POLI_FAIL_PAID_STATUS_ID') ? MODULE_PAYMENT_POLI_FAIL_PAID_STATUS_ID : $this->getDefaultOrderStatusId();

    return array(
      'MODULE_PAYMENT_POLI_STATUS' => array(
        'title' => 'Poli Enable Module',
        'value' => 'True',
        'description' => 'Do you want to accept Poli payments?',
        'sort_order' => '1',
        'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
      ),
      'MODULE_PAYMENT_POLI_KEY' => array(
        'title' => 'Poli Authentication Code',
        'value' => '',
        'description' => '',
        'sort_order' => '2',
      //'use_function' => '\\common\\helpers\\Zones::get_zone_class_title',
      //'set_function' => 'tep_cfg_pull_down_zone_classes(',
      ),
      'MODULE_PAYMENT_POLI_USER_ID' => array(
        'title' => 'Poli Merchant Code',
        'value' => '',
        'description' => '',
        'sort_order' => '3',
      //'use_function' => '\\common\\helpers\\Zones::get_zone_class_title',
      //'set_function' => 'tep_cfg_pull_down_zone_classes(',
      ),
      'MODULE_PAYMENT_POLI_SORT_ORDER' => array(
        'title' => 'Sort order of display.',
        'value' => '0',
        'description' => 'Sort order of display. Lowest is displayed first.',
        'sort_order' => '5',
      ),
      'MODULE_PAYMENT_POLI_ORDER_PROCESS_STATUS_ID' => array(
        'title' => 'Poli Set Order Processing Status',
        'value' => $status_id_p,
        'description' => 'Set the process status of orders made with this payment module to this value',
        'sort_order' => '14',
        'set_function' => 'tep_cfg_pull_down_order_statuses(',
        'use_function' => '\\common\\helpers\\Order::get_order_status_name',
      ),
      'MODULE_PAYMENT_POLI_ORDER_PAID_STATUS_ID' => array(
        'title' => 'Poli Set Order Paid Status',
        'value' => $status_id,
        'description' => 'Set the paid status of orders made with this payment module to this value',
        'sort_order' => '15',
        'set_function' => 'tep_cfg_pull_down_order_statuses(',
        'use_function' => '\\common\\helpers\\Order::get_order_status_name',
      ),
      'MODULE_PAYMENT_POLI_FAIL_PAID_STATUS_ID' => array(
        'title' => 'Poli Set Order Fail Paid Status',
        'value' => $status_id_f,
        'description' => 'Set the fail paid status of orders made with this payment module to this value',
        'sort_order' => '15',
        'set_function' => 'tep_cfg_pull_down_order_statuses(',
        'use_function' => '\\common\\helpers\\Order::get_order_status_name',
      ),
    );
  }

  public function install($platform_id) {

    return parent::install($platform_id);
  }

  function isOnline() {
    return true;
  }

  function selection() {
    $selection = array(
      'id' => $this->code,
      'module' => $this->title. $this->getJS()
    );

    if (defined('MODULE_PAYMENT_POLI_TEXT_NOTES') && !empty(MODULE_PAYMENT_POLI_TEXT_NOTES)) {
      $selection['notes'][] = MODULE_PAYMENT_POLI_TEXT_NOTES;
    }
    return $selection;
  }

  public function call_webhooks() {

    $action = \Yii::$app->request->get('action', null);
    $token = \Yii::$app->request->post('Token', null);
    if (is_null($token)) {
      $token = \Yii::$app->request->get('token', null);
    }

    if ($token) {
      $tmp = $this->sendRequest($this->_poliUrl . $this->_APIActions['get'] . '?token=' . $token, false);
      $transactionDetails = $tmp['response'];
      $tData = ['CountryName',
        'FinancialInstitutionCountryCode',
        'TransactionID',
        //'MerchantEstablishedDateTime',
        //hidden 'PayerAccountNumber',
        //'PayerAccountSortCode',
        //'MerchantAccountSortCode',
        'MerchantAccountName',
        //'MerchantData',
        //'CurrencyName',
        'TransactionStatus',
        'IsExpired',
        //'MerchantEntityID',
        'UserIPAddress',
        //'POLiVersionCode',
        'MerchantName',
        'TransactionRefNo',
        'CurrencyCode',
        //'CountryCode',
        'PaymentAmount',
        'AmountPaid',
        //'EstablishedDateTime',
        'StartDateTime',
        'EndDateTime',
        'BankReceipt',
        'BankReceiptDateTime',
        'TransactionStatusCode',
        'ErrorCode',
        'ErrorMessage',
        'FinancialInstitutionCode',
        'FinancialInstitutionName',
        //'MerchantReference',
        'MerchantAccountSuffix',
        'MerchantAccountNumber',
        'PayerFirstName',
        'PayerFamilyName',
        'PayerAccountSuffix'];

      if ($transactionDetails && is_array($transactionDetails)) {
        $tData = array_filter(array_intersect_key($transactionDetails, array_flip($tData)));
      }
    }

    $orderId = $transactionDetails['MerchantReference'];

    $this->transactionInfo['order_id'] = $orderId;
    $this->transactionInfo['transaction_id'] = $token;//  API search by token, Id's useless $transactionDetails['TransactionID'];
    $this->transactionInfo['transaction_details'] = trim(str_replace('Array', ' ', print_r($tData, 1)), ' )(');
    $this->transactionInfo['amountPaid'] = $transactionDetails['AmountPaid'];
    $this->transactionInfo['currencyCode'] = $transactionDetails['CurrencyCode'];
    $this->transactionInfo['transactionStatus'] = $transactionDetails['TransactionStatus'];

    $this->transactionInfo['silent'] = true;

    if (!empty($transactionDetails['TransactionStatus']) && in_array($transactionDetails['TransactionStatus'], ['Completed'])) {
      // ok to processPaymentNotification
    } elseif (!empty($transactionDetails['TransactionStatus']) &&
        in_array($transactionDetails['TransactionStatus'], ['Initiated', 'FinancialInstitution Selected', 'EulaAccepted', 'InProcess', 'Unknown'])) {
      $this->transactionInfo['status'] = $this->processing_status;
    } else {
      if ($action != 'cancel') {
        $action = 'fail';
      }
    }


    if ($this->debug) {
      \Yii::warning($token . ' (token) ' . print_r($transactionDetails, 1), 'POLIWEBHOOK');
      \Yii::warning(' transactionInfo ' . print_r($this->transactionInfo, 1), 'POLIWEBHOOK');
    }

    switch ($action) {
      case 'fail':
        $this->transactionInfo['status'] = $this->fail_paid_status;
        parent::processPaymentCancellation();
        $msg = $transactionDetails['ErrorMessage'];
        tep_redirect(\Yii::$app->urlManager->createAbsoluteUrl(['checkout', 'returned_order' => $orderId, 'error_message' => (!empty($msg) ? $msg : (defined('TEXT_GENERAL_PAYMENT_ERROR') ? TEXT_GENERAL_PAYMENT_ERROR : 'Please select different payment method'))]));
        break;
      case 'cancel':
        $this->transactionInfo['status'] = $this->fail_paid_status;
        parent::processPaymentCancellation();
        $msg = $transactionDetails['ErrorMessage'];
        tep_redirect(\Yii::$app->urlManager->createAbsoluteUrl(['checkout', 'returned_order' => $orderId, 'error_message' => (!empty($msg) ? $msg : (defined('TEXT_GENERAL_PAYMENT_ERROR') ? TEXT_GENERAL_PAYMENT_ERROR : 'Please select different payment method'))]));
        break;
      case 'complete':
        parent::processPaymentNotification(true);
        tep_redirect(\Yii::$app->urlManager->createAbsoluteUrl(['checkout/success']));
        break;
      default: //NotificationURL - IPN
        parent::processPaymentNotification(true);
        break;
    }


    return;
  }

  public function getTransactionDetails($token, \common\services\PaymentTransactionManager $tManager = null) {

    $response = $this->sendRequest($this->_poliUrl . $this->_APIActions['get'] . '?token=' . $token, false);

    if (!empty($response['response'])) {
      return $response['response'];
    }
    return false;
  }

  public function canRefund($transaction_id) {
    return false;
  }

  public function refund($transaction_id, $amount = 0) {
    return false;
  }

  public function canVoid($transaction_id) {
    return false;
  }

  public function void($transaction_id) {
    return false;
  }

}
