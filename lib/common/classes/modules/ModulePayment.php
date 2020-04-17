<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\classes\modules;

use common\helpers\Html;
use \yii\helpers\ArrayHelper;
use common\helpers\OrderPayment as OrderPaymentHelper;

abstract class ModulePayment extends Module{
  protected $transactionInfo = [];
  
  function javascript_validation() {
    return false;
  }

  function selection() {
    return false;
  }

  function pre_confirmation_check() {
    return false;
  }

  function confirmation() {
    return false;
  }

  function confirmationCurlAllowed() {
    return false;
  }

  function confirmationAutosubmit() {
    return false;
  }

  function process_button() {
    $order = $this->manager->getOrderInstance();

    if (!self::isPartlyPaid()) return false;

    if (isset($order->info['total_paid_inc_tax'])){
        $order->info['total'] = $order->info['total_inc_tax'] - $order->info['total_paid_inc_tax'];
        if ($order->info['total'] < 0 ) $order->info['total'] = 0;
    }
    $this->paid = 'partlypaid';

    $this->manager->set('pay_order_id', $order->order_id);
  }

  function before_process() {
    //global $sendto, $billto, $order;
    if (self::isPartlyPaid()){
        $order = $this->manager->getOrderInstance();
        if (!$this->manager->has('sendto') && (int)$order->delivery['address_book_id'] > 0) $this->manager->set('sendto', (int)$order->delivery['address_book_id']);
        if (!$this->manager->has('billto') && (int)$order->billing['address_book_id'] > 0) $this->manager->set('billto', (int)$order->billing['address_book_id']);
        return true;
    }
    return false;
  }

  function after_process() {
      $order = $this->manager->getOrderInstance();
      if (is_object($order)) {
          return \common\helpers\OrderPayment::createDebitFromOrder($order);
      }
      return false;
  }

  function get_error() {
    return false;
  }

  function output_error() {
    return false;
  }

  function before_subscription($id = 0) {
    return false;
  }

  function haveSubscription() {
    return false;
  }

  function get_subscription_info($id = '') {
    return '';
  }

  function get_subscription_full_info($id = '') {
    return [];
  }

  function cancel_subscription($id = '') {
    return false;
  }

  function terminate_subscription($id = '', $type = 'none') {
    return false;
  }

  function postpone_subscription($id = '', $date = '') {
    return false;
  }

  function reactivate_subscription($id = '') {
    return false;
  }

  function isOnline() {
      return false;
  }

  function isPartlyPaid(){
    if (
            strpos($_SERVER['REQUEST_URI'], 'order-confirmation') !== false ||
            strpos($_SERVER['REQUEST_URI'], 'order-process') !== false ||
            strpos($_SERVER['REQUEST_URI'], 'order-pay') !== false ||
            (isset(\Yii::$app->request->queryParams['page_name']) && in_array(\Yii::$app->request->queryParams['page_name'], ['order_pay']))
            ){
            return true;
            }
    return false;
  }
  const PAYMENT_PAGE = 1;
  const CONFIRMATION_PAGE = 2;
  const PROCESS_PAGE = 3;

  function getCheckoutUrl(array $params, int $checkoutPage = 0){
    if ($this->isPartlyPaid() && $this->manager->isInstance()){
        if (!isset($params['order_id'])) {$params['order_id'] = $this->manager->getOrderInstance()->order_id; }
        switch ($checkoutPage){
            case self::CONFIRMATION_PAGE :
                $url = 'payer/order-confirmation';
                break;
            case self::PROCESS_PAGE :
                $url = 'payer/order-process';
                break;
            case self::PAYMENT_PAGE :
            default:
                $url = 'payer/order-pay';
                break;
        }
        return \Yii::$app->urlManager->createAbsoluteUrl(array_merge([$url], $params), ((ENABLE_SSL == true) ? 'https' : 'http'));
    } else {
        switch ($checkoutPage){
            case self::CONFIRMATION_PAGE :
                $url = defined('FILENAME_CHECKOUT_CONFIRMATION')? FILENAME_CHECKOUT_CONFIRMATION : '';
                break;
            case self::PROCESS_PAGE :
                $url = defined('FILENAME_CHECKOUT_PROCESS')? FILENAME_CHECKOUT_PROCESS : '';
                break;
            case self::PAYMENT_PAGE :
            default:
                $url = defined('FILENAME_CHECKOUT_PAYMENT')? FILENAME_CHECKOUT_PAYMENT : '';
                break;
        }
        return \Yii::$app->urlManager->createAbsoluteUrl(array_merge([$url], $params), ((ENABLE_SSL == true) ? 'https' : 'http'));
    }
  }

  function forShop() {
    return true;
  }

  function forPOS() {
    return false;
  }

  function forAdmin() {
      return false;
  }

  function forCollect() {
      return false;
  }

/**
 * save order before redirect to payment gateway
 * @param bool|string $asType orderClass (for now TmpOrder only) or false (
 * @param bool  $updateStock default false
 * @param array $params extra order params default []
 * @return integer|null - orderId
 */
  protected function saveOrder( $asType = false, $updateStock = false, $params = [] ) {

    $ret = null;

    switch ($asType) {
      case 'TmpOrder':
        $tmpOrder = $this->manager->getParentToInstance('\common\classes\TmpOrder');
        if (is_object($tmpOrder)) {
          if (is_array($params) && count($params)) {
            foreach ($params as $k => $v) {
              if (property_exists($tmpOrder, $k)) {
                if (is_array($v) && is_array($tmpOrder->$k)) {
                  $tmpOrder->$k = array_merge_recursive($tmpOrder->$k, $v);
                } elseif (is_scalar($v) && is_scalar($tmpOrder->$k)) {
                  $tmpOrder->$k = $v;
                }
              }
            }
          }
          $ret = $tmpOrder->save_order();
          $tmpOrder->save_details();
          $tmpOrder->save_products(false);
        }
        break;

      case 'Order':
      default:
        $order = $this->manager->getOrderInstance();
        $order->save_order();
        $order->save_details();

        $order->save_products(false);

        $ret = $order->order_id;
        break;
    }

    return $ret;
  }


  /**
   * submit request using Curl and returns header/data
   * @param string $url
   * @param array $params post | ['post'=>'', 'header' =>'', 'headerOut' => 1]
   * @return array ['code'=> int, response =>'', headers =>[], header =>'' ]
   */
  protected function sendRequest($url, $params) {

    if (isset($params['post'])) {
      $post = $params['post'];
    } else {
      $post = $params;
    }

		$curl = curl_init($url);
    if ($post) {
      curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
    } else {
      curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
      curl_setopt( $curl, CURLOPT_POST, 0);
    }

    curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
    curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    if (!empty($params['header'])) {
		  curl_setopt($curl, CURLOPT_HEADER, 1);
      curl_setopt($curl, CURLOPT_HTTPHEADER, $params['header']);
    }

		$response = curl_exec($curl);

		$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    $http_code = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);

		$header = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
		$headers = array_map('trim', explode(PHP_EOL, $header));

		curl_close($curl);

    if (!empty($params['headerOut'])) {
      $ret = [
        'code' => $http_code,
        'response' => $body,
        'headers' => $headers,
        'header' => $header
      ];
    } else {
      $ret = [
        'code' => $http_code,
        'response' => $body
      ];
    }

    return $ret;

  }

/**
 * it should be overridden & called in payment module to populate $this->transactionInfo
 * @param bool $notify customer default false
 * @return nothing | ['checkout/success']
 */
  protected function processPaymentNotification($notify = false) {

    if ($this->transactionInfo && !empty($this->transactionInfo['order_id'])) {

      $order_id = $this->transactionInfo['order_id'];
      $transaction_id = $this->transactionInfo['transaction_id'];
      $transaction_details = $this->transactionInfo['transaction_details'];
      $silent = $this->transactionInfo['silent'];
      if (isset($this->transactionInfo['status'])) {
        $status = $this->transactionInfo['status'];
      } elseif($this->paid_status>0) {
        $status = $this->paid_status;
      } else {
        $status = false;
      }

      /* @var $order \common\classes\Order */
      $order = $this->manager->getOrderInstanceWithId('\common\classes\Order', $order_id);
      /* @var $oModel \common\models\Orders */
      $oModel = $order->getARModel()->where(['orders_id' => $order_id])->one();

      $otl = \common\models\OrdersTransactions::findOne(['orders_id' => $order_id, 'transaction_id' => $transaction_id]);
      if ($otl) {
        $tList[] = $transaction_id;
      } else {
        $tList = [];
      }

        /* 2do not fully paid && additional paid amount
        if (abs($order->info['total_inc_tax'] - $order->info['total_paid_inc_tax'] - floatval($response->getAmountSettlement())) > 0.01) {
          $order->info['total_paid_inc_tax'] = $order->info['total_inc_tax'] - floatval($response->getAmountSettlement());
        }
        */

      if (empty($tList) || !in_array(trim($transaction_id), $tList) ){
        $order->info['comments'] = str_replace(["\n\n", "\r"], ["\n", ''], $transaction_details);
        if ($status) {
          $oModel->orders_status = $order->info['order_status'] = $status;
          $oModel->update(false);
        }

        //{{ transactions
        /** @var \common\services\PaymentTransactionManager $tManager */
        $tManager = $this->manager->getTransactionManager($this);
        $invoice_id = $this->manager->getOrderSplitter()->getInvoiceId();
        $tManager->addTransaction($transaction_id, 'Success', $this->transactionInfo['amountPaid'], $invoice_id, $transaction_details);
        //{{

        $orderPayment = $this->searchRecord($transaction_id);
        $orderPayment->orders_payment_order_id = $order_id;
        $orderPayment->orders_payment_snapshot = json_encode(OrderPaymentHelper::getOrderPaymentSnapshot($order));
        $orderPayment->orders_payment_status = OrderPaymentHelper::OPYS_SUCCESSFUL;
        $orderPayment->orders_payment_amount = (float)$this->transactionInfo['amountPaid'];
        $orderPayment->orders_payment_currency = trim($order->info['currency']);
        $orderPayment->orders_payment_currency_rate = (float)$order->info['currency_value'];
        $orderPayment->orders_payment_transaction_date = new \yii\db\Expression('now()');
        $orderPayment->orders_payment_transaction_id = $transaction_id;
        $orderPayment->save(false);
        //}} transactions

        /**  2do (to replace when special method exists in order class */
        if (isset($order->products) && is_array($order->products)) {
          foreach ($order->products as $p) {
            if (!empty($p['orders_products_id'])) {
              \common\helpers\OrderProduct::doAllocateAutomatic($p['orders_products_id'], true);
            } else {
              \Yii::warning('Product stock allocation failed - no orders_products_id Order# ' . $order_id, 'stock allocation');
            }
          }
        }
        /** 2do eof */

        $order->update_piad_information(true);
        $order->save_details($notify);

        if ($notify) {
          $order->notify_customer($order->getProductsHtmlForEmail(),[]);
        }

        if ($ext = \common\helpers\Acl::checkExtension('ReferFriend', 'rf_after_order_placed')) {
            $ext::rf_after_order_placed($order_id);
        }

        try {
            $ess = new \common\components\google\GoogleEcommerceSS($order_id);
            if (!$ess->isOrderPlacedToAnalytics()) {
                $result = $ess->pushDataToAnalytics($order);
            }
        } catch (\Exception $e) {
          \Yii::warning($e->getMessage(), 'CHECKOUT_GOOGLE_ECOMMERCE');
        }
      }

      $this->after_process();
      if (empty($silent)) {
        return ['checkout/success'];
      }
    }
  }

/**
 * it should be overridden in payment module if the module creates order (to delete it)
 * @param bool $notify customer default false
 */
  protected function processPaymentCancellation($notify = false) {
    if ($this->transactionInfo && !empty($this->transactionInfo['order_id'])) {

      $order_id = $this->transactionInfo['order_id'];
      if (isset($this->transactionInfo['status'])) {
        $status = $this->transactionInfo['status'];
      } else {
        $status = false;
      }
      $transaction_id = $this->transactionInfo['transaction_id'];
      $transaction_details = $this->transactionInfo['transaction_details'];

      /* @var $order \common\classes\Order */
      $order = $this->manager->getOrderInstanceWithId('\common\classes\Order', $order_id);
      /* @var $oModel \common\models\Orders */
      $oModel = $order->getARModel()->where(['orders_id' => $order_id])->one();

      //2do - transactions table instead of feild.
      if (!empty($oModel->transaction_id) ) {
        $tList = preg_split('/\|/', $oModel->transaction_id, -1, PREG_SPLIT_NO_EMPTY);
      } else {
        $tList = [];
      }

      if (empty($tList) || !in_array(trim($transaction_id), $tList) ){
        $order->info['comments'] = str_replace(["\n\n", "\r"], ["\n", ''], $transaction_details);
        if ($status) {
          $oModel->orders_status = $order->info['order_status'] = $status;
        }

        $oModel->transaction_id = implode('|', array_merge([trim($transaction_id)], $tList));
        $oModel->update(false);

        /**  2do (to replace when special method exists in order class */
        if (isset($order->products) && is_array($order->products)) {
          foreach ($order->products as $p) {
            if (!empty($p['orders_products_id'])) {
              \common\helpers\OrderProduct::doAllocateAutomatic($p['orders_products_id'], true);
            } else {
              \Yii::warning('Product stock allocation failed - no orders_products_id Order# ' . $order_id, 'stock allocation');
            }
          }
        }
        /** 2do eof */

        $order->save_details($notify);

        if ($notify) {
          $order->notify_customer($order->getProductsHtmlForEmail(),[]);
        }

      }

    }

  }

/**
 *
 * @param array $data (associative) of passed data
 * @param string  $api_key use in hash function
 * @param bool $incEmpty default true
 * @param bool $sort default true
 * @param string $algo default sha256
 * @return string
 */
  protected function generateSignature($data, $api_key, $incEmpty = true, $sort = true, $algo = 'sha256') {
    $ret = '';

    $algos = hash_algos();

    if (is_array($data) && in_array($algo, $algos)) {

      $clear_text = '';
      if ($sort) {
        ksort($data);
      }
      foreach ($data as $key => $value) {
        if ($incEmpty || !empty(value)) {
          $clear_text .= $key . $value;
        }
      }

      $ret = hash_hmac($algo, $clear_text, $api_key);

    }

    return $ret;
  }

  public function getDefaultOrderStatusId(): int
  {
      static $status_id = null;
      if ($status_id === null) {
          $status_id = 0;
          $defaultPaymentOS = \Yii::$app->get('db')->createCommand("SELECT configuration_value FROM configuration WHERE configuration_key = 'DEFAULT_ONLINE_PAYMENT_ORDERS_STATUS_ID'")->queryOne();
          if ($defaultPaymentOS) {
              $status_id = (int) $defaultPaymentOS['configuration_value'];
          } else {
              $defaultOS = \Yii::$app->get('db')->createCommand("SELECT configuration_value FROM configuration WHERE configuration_key = 'DEFAULT_ORDERS_STATUS_ID'")->queryOne();
              if ($defaultOS) {
                  $status_id = (int) $defaultOS['configuration_value'];
              }
          }
      }
      return $status_id;
  }

    public function searchRecord($orderPaymentTransactionId = '')
    {
        $orderPaymentRecord = \common\helpers\OrderPayment::searchRecord($this->code, $orderPaymentTransactionId);
        if ($orderPaymentRecord instanceof \common\models\OrdersPayment) {
            if ($orderPaymentRecord->orders_payment_module_name == '') {
                $orderPaymentRecord->orders_payment_module_name = $this->title;
            }
            return $orderPaymentRecord;
        }
        return false;
    }

    public function tokenAllowed(): bool
    {
      return defined('USE_TOKENS_IN_PAYMENT_METHODS')?USE_TOKENS_IN_PAYMENT_METHODS=='True':false;
    }

/**
 * true if module supports tokens
 * @return bool
 */
    public function hasToken(): bool {
      /* override in your module if it's support tokens
       return true && parent::tokenAllowed();
       */
      return false;
    }

/**
 * true if tokens is allowed in the module settings
 * @return bool
 */
    public function useToken(): bool {
      /* override in your module if tokens is enabled in the method settings
       return $this->hasToken() && defined('MODULE_PAYMENT_SAGE_PAY_SERVER_USE_TOKENS') && MODULE_PAYMENT_SAGE_PAY_SERVER_USE_TOKENS  == 'True';
       */
      return false;
    }

/**
 * returns [all] customer token(s)
 * @param int $customersId
 * @param int $tokenId
 * @return array|null
 */
    public function getTokens($customersId, $tokenId = false) {
      $ret = null;
      if ($this->useToken()) {
        $q = \common\models\PaymentTokens::find()->andWhere([
          'customers_id' => (int)$customersId,
          'payment_class' => $this->code,
        ]);
        if ($tokenId) {
          $q->andWhere(['payment_tokens_id'  => $tokenId]);
        }
        $ret = ArrayHelper::toArray( $q->all()); //do not use asArray() as there are afterFind method
      }
      return $ret;
    }

/**
 * check customers token
 * @param int $customersId
 * @param string $token
 * @return bool
 */
    public function checkToken($customersId, $token) {
      $ret = false;
      if ($this->useToken()) {

        $tokens = $this->getTokens($customersId);
        if (is_array($tokens)) {
          $arr = ArrayHelper::getColumn($tokens, 'token');
          $ret = in_array($token, $arr);
        }
      }
      return $ret;
    }

/**
 * checks customer/token and try to delete it from DB. Override this method in your module to delete the token at gateway.
 * @param int $customersId
 * @param string $token
 * @return bool (deleted - true, not found - false)
 */
    public function deleteToken($customersId, $token) {
      $res = false;
      if ($this->checkToken($customersId, $token)) {
        $res = \common\models\PaymentTokens::deleteToken($customersId, $this->code, $token);
      }
      return $res;
    }

    /**
     * save token in DB, update only default flag
     * @param int $customersId
     * @param array $tokenData [token => '', cardType => '', lastDigits =>'', fistDigits =>'', maskedCC =>'', expDate =>]
     * @return type
     */
    public function saveToken($customersId, $tokenData) {
      if ($this->useToken() && (int)$customersId>0 && !empty($tokenData['token'])) {
        try {
          $m = false;

          if (!empty($tokenData['old_payment_token'])) {
           $tokens = $this->getTokens($customersId);

           if (is_array($tokens)) {
             $tokens = array_values(array_filter($tokens, function ($el) use($tokenData) {return $el['token']==$tokenData['old_payment_token']; }) );
           }
           if (!empty($tokens[0]['payment_tokens_id'])) {
             $m = \common\models\PaymentTokens::findOne($tokens[0]['payment_tokens_id']);
           }

          } else {

          $m = new \common\models\PaymentTokens();
          $m->customers_id = $customersId;
          $m->payment_class = $this->code;

          $m->token = $tokenData['token'];

          if (!empty($tokenData['cardType'])) {
            $m->card_type = $tokenData['cardType'];
          }

          if (!empty($tokenData['expDate'])) {
            $m->exp_date = $tokenData['expDate'];
          } else {
            $m->exp_date = date('Y-m-01', strtotime("+20 years"));//FUI Visa
          }

          if (!empty($tokenData['maskedCC'])) {
            $m->last_digits = $tokenData['maskedCC'];
          } elseif (!empty($tokenData['lastDigits']) &&  !empty($tokenData['lastDigits']) ) {
            $m->last_digits = $tokenData['fistDigits'] . str_repeat('x', 20-strlen($tokenData['fistDigits'] . $tokenData['lastDigits'])) . $tokenData['lastDigits'];
          } elseif (!empty($tokenData['lastDigits'])) {
            $m->last_digits = str_repeat('x', 16-strlen($tokenData['lastDigits'])) . $tokenData['lastDigits'];
          } elseif (!empty($tokenData['fistDigits'])) {
            $m->last_digits = $tokenData['fistDigits'] . str_repeat('x', 16-strlen($tokenData['fistDigits']));
          }
          }

          if ($m) {
            $m->is_default = empty($this->manager->get('update_default_token'))?0:1;
          $m->save(false);
          }
          
        } catch (\Exception $e) {
          \Yii::warning($e->getMessage() . $e->getTraceAsString(), 'TOKEN_SAVE');
        }

      }
    }

/**
 * checkout - module's selection method
 * @param int $customersId
 * @return array
 */
    public function renderTokenSelection($customersId = false) {
      $ret = null;
      if ($this->useToken()) {
        $ret = [];
        if ((int)$customersId>0) {
          $tokens = $this->getTokens($customersId);
        }

        $ret[] = [
          'title' => '<label for="data_' . $this->code . '_use_token">' . sprintf(PAYMENT_USE_TOKEN_TEXT, ($this->public_title?$this->public_title:$this->title)) .'</label>',
          'field' => Html::checkbox('use_token', !empty($tokens), ['id' => 'data_' . $this->code . '_use_token', 'class' => $this->code]) . $this->getJS()
          ];
        if (!empty($tokens) && is_array($tokens)) {
          $ret[] = [
            'title' => '<label for="data_' . $this->code . '_use_token_0">' .PAYMENT_USE_DIFFERENT_CARD .'</label>',
            'field' => Html::radio('ptoken', empty($tokens), ['id' => 'data_' . $this->code . '_use_token_0', 'class' => $this->code, 'value' => 0])
            ];
          foreach ($tokens as $token) {
            $ret[] = [
              'title' => '<i class="cc-icon cc-' . (!empty($token['card_type'])?strtolower($token['card_type']):'unknown') . '"></i><label for="data_' . $this->code . '_use_token_' . $token['payment_tokens_id'] .'">' . (!empty($token['card_name'])?$token['card_name']:$token['last_digits']) .'</label>',
              'field' => Html::radio('ptoken', !empty($token['is_default']), ['value' => $token['token'], 'id' => 'data_' . $this->code . '_use_token_' . $token['payment_tokens_id'], 'class' => $this->code])
              ];
          }

        }
      }
      return $ret;

    }

    public function getJS(){
return <<<EOD
<script>
function toggleSubFields_{$this->code}(){
    if ($('input[name=payment][value="{$this->code}"]').is(':checked')){
        $('.payment_class_{$this->code} .sub-item').show();
    } else {
        $('.payment_class_{$this->code} .sub-item').hide();
    }
    $('.payment_class_{$this->code} .sub-item label, .payment_class_{$this->code} .sub-item input').css('display', 'inline-block');
    
}
if (typeof tl == 'function'){
    tl(function(){ toggleSubFields_{$this->code}();
        $('input[name="payment"]').change(function(){toggleSubFields_{$this->code}(); })
    })
}
</script>
EOD;
    }

    
/**
 *  format prices without currency formatting Replace format_raw with formatRaw in most modules and delete it
 * @param decimal $number
 * @param string $currency_code
 * @param float $currency_value
 * @return decimal
 */
function formatRaw($number, $currency_code = '', $currency_value = '') {
  $currencies = \Yii::$container->get('currencies');

  if (empty($currency_code) || !$currencies->is_set($currency_code)) {
    $currency_code = \Yii::$app->settings->get('currency');
  }

  if (empty($currency_value) || !is_numeric($currency_value)) {
    $currency_value = $currencies->currencies[$currency_code]['value'];
  }

  return number_format(round($number * $currency_value, $currencies->currencies[$currency_code]['decimal_places']), $currencies->currencies[$currency_code]['decimal_places'], '.', '');
}

  /**
   * @return bool
   */
  public function isWithoutConfirmation(): bool
  {
      return defined('SKIP_CHECKOUT') && SKIP_CHECKOUT === 'True';
  }
  
  public function popUpMode() {
    return false;
  }

/**
 * @return string
 */
  protected function tlPopupJS() : string
  {
    if (!$this->isWithoutConfirmation()){
      $url = $this->getCheckoutUrl([], self::PROCESS_PAGE);
    } else {
      $url = $this->getCheckoutUrl(['order_id' => \Yii::$app->request->get('order_id')], self::CONFIRMATION_PAGE);
    }
    return $this->openPopupJS($url);
  }
  
/**
 * @param string $url
 * @return JS string
 */
  public function openPopupJS(string $url) : string
  {
    $ret = <<<EOD
        function popUpIframe() {
          var divId = 'tl-payment-popup-checkout';
          var frameId = 'tl-payment-popup-checkout-frm';
          var paymentPopup = $('#'+divId);
          if (paymentPopup.length>0) {
            paymentPopup.remove();
          }
          $('body').append('<div class="tl-payment-popup" id="' + divId + '" style = "display: none;"></div>');
          paymentPopup = $('#' + divId);

          //useless not aligned paymentPopup.html('<div style="width:' + Math.round(screen.width/2) +'px;height:' + Math.round(screen.height*0.65) +'px"></div>');
          paymentPopup.popUp({ 'event': 'show' });

          var w = Math.max(300, Math.round(screen.width/2));
          var h = Math.max(300, Math.round(screen.height*0.65));

          $(".popup-box").css("width", w +'px').css("height", h +'px');
          var d = ($(window).height() - $('.popup-box').height()) / 2;
          if (d < 0) d = 0;
          $('.popup-box-wrap').css('top', $(window).scrollTop() + d);
          //paymentPopup.position($('.popup-box:last'));
          $(".pop-up-content").html('<iframe src="{$url}" frameborder="0" style="width:100%;height:' + (h-15) +'px" class="payment-iframe"></iframe>');
        }
EOD;

    return $ret;
  }
  
    /**
     * MOTO order - most payment gateways require to mark transaction as Moto (card not present)
     */
    public function onBehalf() {
      $ret = false;
      if (!empty(\Yii::$app->settings->get('from_admin'))) {
        $ret = true;
      }
      return $ret;
    }
    
    /**
     * Register payment jsCallback
     * @param type $callback
     */
    public function registerCallback($callback){
        $colection = $this->manager->getPaymentCollection();
        if (!$colection->hasCallback($this->code)){
            $colection->registerCallback($this->code, $callback);
        }
    }
}