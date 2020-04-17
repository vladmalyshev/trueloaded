<?php

namespace common\modules\orderPayment;

use Yii;
use common\classes\modules\ModulePayment;
use common\classes\modules\ModuleStatus;
use common\classes\modules\ModuleSortOrder;
use common\classes\modules\TransactionalInterface;
use common\services\PaymentTransactionManager;
use backend\services\OrdersService;
use common\modules\orderPayment\lib\pay360\Pay360 as Pay360;

/**
 * Class secpay
 */
class secpay extends ModulePayment implements TransactionalInterface {

    const URL_LIVE = 'https://api.pay360.com';
    const URL_SANDBOX = 'https://api.mite.pay360.com';

    /** @var string */
    public $code;

    /** @var string */
    public $title;

    /** @var string */
    public $description;

    /** @var bool */
    public $enabled;

    /** @var int */
    public $sort_order;

    /** @var int */
    public $order_status;

    /** @var string */
    public $public_title;

    /** @var string */
    private $hostedService;

    /** @var array */
    private $alternative = [];
    private $isHosted = true;

    /** @var array */
    protected $defaultTranslationArray = [
        'MODULE_PAYMENT_SECPAY_TEXT_TITLE' => 'SecPay (pay360)',
        'MODULE_PAYMENT_SECPAY_TEXT_PUBLIC_TITLE' => 'SecPay',
        'MODULE_PAYMENT_SECPAY_TEXT_DESCRIPTION' => 'SecPay',
        'MODULE_PAYMENT_SECPAY_SORT_ORDER' => '110',
    ];

    public function __construct() {
        try {
            parent::__construct();
            $this->code = 'secpay';
            $this->title = MODULE_PAYMENT_SECPAY_TEXT_TITLE;
            $this->public_title = MODULE_PAYMENT_SECPAY_TEXT_PUBLIC_TITLE;
            $this->description = MODULE_PAYMENT_SECPAY_TEXT_DESCRIPTION;
            $this->sort_order = (int) MODULE_PAYMENT_SECPAY_SORT_ORDER;
            if (defined('MODULE_PAYMENT_SECPAY_STATUS')) {
                $this->requestUrl = self::getRequestUrl();
                if (MODULE_PAYMENT_SECPAY_TRANSACTION_SERVER === 'Test') {
                    $this->title .= ' [Test]';
                    $this->public_title .= ' (Test)';
                }
            }
            $this->updateStatus();
            if (!$this->enabled) {
                return;
            }

            if (defined('MODULE_PAYMENT_SECPAY_ALTERNATIVE') && !empty(MODULE_PAYMENT_SECPAY_ALTERNATIVE)) {
                $this->alternative = array_map('strtolower', array_map('trim', explode(",", MODULE_PAYMENT_SECPAY_ALTERNATIVE)));
            }

            $this->hostedService = \Yii::createObject([
                        'class' => Pay360::class,
                        'username' => MODULE_PAYMENT_SECPAY_USERNAME,
                        'password' => MODULE_PAYMENT_SECPAY_PASSWORD
            ]);

            $this->ordersService = \Yii::createObject(OrdersService::class);
        } catch (\Exception $e) {
            $this->enabled = false;
        }
    }

    public function getTitle($method = '') {
        return $this->public_title;
    }

    public static function getRequestUrl(): string {
        if (MODULE_PAYMENT_SECPAY_TRANSACTION_SERVER === 'Test') {
            return self::URL_SANDBOX;
        } else {
            return self::URL_LIVE;
        }
    }

    protected function getAlternatives() {
        $allowed = [];
        if (!empty($this->alternative) && is_array($this->alternative)) {
            $currency = Yii::$app->settings->get('currency');
            $countryIso3 = $this->billing['country']['countries_iso_code_3'] ??  $this->delivery['country']['countries_iso_code_3'];
            $order = $this->manager->getOrderInstance();
            $currencies = Yii::$container->get('currencies');
            $totalAmount = floatval($currencies->display_price_clear($order->info['total_inc_tax'],0));
            foreach ($this->alternative as $method) {
                $acceptor = $this->hostedService->createAcceptor($method);
                if ($acceptor->isAllowed($currency, $countryIso3, $totalAmount)) {
                    $allowed[] = $method;
                }
            }
        }
        return $allowed;
    }

    public function selection(): array {
        $this->manager->remove('secPaySessionId');
        $this->manager->remove('secPayTransactionId');

        $selection = [
            'id' => $this->code,
            'icon' => '<img src="' . tep_href_link('images/payment.png') . '">',
            'module' => $this->public_title,
            'fields' => []
        ];
        if (!empty($this->alternative) && is_array($this->alternative)) {
            $methods = [];
            $methods[] = [
                'id' => $this->code,
                'module' => $this->public_title
            ];
            foreach ($this->getAlternatives() as $method) {
                $methods[] = [
                    'id' => $this->code . "_" . $method,
                    'module' => $this->public_title . " " . ucfirst($method)
                ];
            }
            $selection['methods'] = $methods;
        }
        return $selection;
    }

    public function process_button(): bool {
        return false;
    }

    public function prepareOrderData($secPayRefId = null) {

        $order = $this->manager->getOrderInstance();
        if ($order) {
            $session = $this->hostedService->session();
            $session->setReturnUrl(Yii::$app->urlManager->createAbsoluteUrl('checkout/process'));
            $session->setCancelUrl($this->getCheckoutUrl([], self::PAYMENT_PAGE));
            $session->setTransactionNotification(Yii::$app->urlManager->createAbsoluteUrl(['callback/webhooks', 'set' => 'payment', 'module' => $this->code]));

            if ($order->order_id) {
                $merchaneReference = $order->order_id;
            } else if (!is_null($secPayRefId)) {
                $merchaneReference = $secPayRefId;
            } else {
                $merchaneReference = md5(date("Y-m-d H:i:s"));
                $this->manager->set('secPayRefId', $merchaneReference);
            }

            if ($this->isHosted) {
                $transaction = $this->hostedService->transaction($merchaneReference);
            } else {
                $transaction = $this->hostedService->transaction();
                $transaction->setMerchantRef($merchaneReference);
            }
            
            $transaction->setChannel('WEB');
            $transaction->setDescription("Trueloaded Commerce 3.2");
            $transaction->setDeferred(false);

            if ($this->isHosted) {
                $money = $transaction->money();
                $money->setCurrency(Yii::$app->settings->get('currency'));
                $money->setFixedAmount($this->format_raw($order->info['total_inc_tax'])); //change to real amount
            } else {
                $transaction->setCurrency(Yii::$app->settings->get('currency'));
                $transaction->setAmount($this->format_raw($order->info['total_inc_tax']));
                $transaction->setCommerceType('ECOM');
            }

            $customer = $this->hostedService->customer();
            if ($this->isHosted) {
                if ($order->customer['name']) {
                    $customer->setName($order->customer['name']);
                }
                if ($order->customer['telephone']) {
                    $customer->setTelephone($order->customer['telephone']);
                }
                if ($order->customer['email_address']) {
                    $customer->setEmailAddress($order->customer['email_address']);
                }
            } else {
                $customer->setDisplayName($order->customer['name']);
            }

            if ($this->isHosted) {
                $address = $customer->address();
            } else {
                $address = $customer->BillingAddress();
            }
            if ($order->billing['street_address']) {
                $address->setLine1($order->billing['street_address']);
            }
            if ($order->billing['suburb']) {
                $address->setLine2($order->billing['suburb']);
            }
            if ($order->billing['city']) {
                $address->setCity($order->billing['city']);
            }
            if ($order->billing['postcode']) {
                $address->setPostcode($order->billing['postcode']);
            }
            if ($order->billing['state'] || $order->billing['zone_id']) {
                if ($order->billing['state']) {
                    $address->setRegion($order->billing['state']);
                } else {
                    $address->setRegion(\common\helpers\Zones::get_zone_name($order->billing['country_id'], $order->billing['zone_id']));
                }
            }
            if (is_array($order->billing['country']) && $order->billing['country']['iso_code_3']) {
                $address->setCountryCode($order->billing['country']['iso_code_3']);
            }
        }
    }

    private function _before_hosted_process() {
        try {
            if (!$this->manager->has('secPaySessionId')) {
                $this->prepareOrderData();
                $response = $this->hostedService->makePayment(MODULE_PAYMENT_SECPAY_INSTALLATION_ID, $this->getRequestUrl());
                if ($response['status'] == 'SUCCESS') {
                    $this->manager->set('secPaySessionId', $response['sessionId']);
                    if (isset($response['redirectUrl'])) {
                        tep_redirect($response['redirectUrl']);
                    }
                } else {
                    tep_redirect($this->getCheckoutUrl(['payment_error' => $this->code, 'error' => ''], self::PAYMENT_PAGE), 'SSL', false);
                }
            } else if ($this->manager->has('secPaySessionId')) {
                $secPaySessionId = $this->manager->get('secPaySessionId');
                if ($secPaySessionId == Yii::$app->request->get('sessionId')) {
                    $response = $this->hostedService->getPaymentStatus(MODULE_PAYMENT_SECPAY_INSTALLATION_ID, $this->getRequestUrl(), $secPaySessionId);
                    if ($response['status'] == 'SUCCESS' && ( is_array($response['hostedSessionStatus']['transactionState']) && isset($response['hostedSessionStatus']['transactionState']['transactionState']) && $response['hostedSessionStatus']['transactionState']['transactionState'] != 'FAILED' )) {
                        $order = $this->manager->getOrderInstance();
                        $order->info['order_status'] = defined('MODULE_PAYMENT_SECPAY_ORDER_STATUS_ID') && ((int) MODULE_PAYMENT_SECPAY_ORDER_STATUS_ID > 0) ? (int) MODULE_PAYMENT_SECPAY_ORDER_STATUS_ID : 0;
                    } else {
                        tep_redirect($this->getCheckoutUrl(['payment_error' => $this->code, 'error' => urlencode('Transaction failed')], self::PAYMENT_PAGE), 'SSL', false);
                    }
                } else {
                    $this->manager->remove('secPaySessionId');
                    tep_redirect($this->getCheckoutUrl(['payment_error' => $this->code, 'error' => urlencode('Incorrect Secpay Session ID')], self::PAYMENT_PAGE), 'SSL', false);
                }
            }
        } catch (\Exception $e) {
            tep_redirect($this->getCheckoutUrl(['payment_error' => $this->code, 'error' => urlencode($e->getMessage())], self::PAYMENT_PAGE), 'SSL', false);
        }
    }

    private function _before_alternative_process($method) {
        try {
            if (!in_array($method, $this->alternative)) {
                throw new \Exception('Invalid Acceptor Payment');
            } else {
                if (!$this->manager->has('secPayTransactionId')){
                    $this->isHosted = false;
                    $acceptor = $this->hostedService->createAcceptor($method);
                    if ($acceptor) {
                        $name = defined('MODULE_PAYMENT_SECPAY_ALTERNATIVE_ACCOUNT_NAME_'.strtoupper($method)) ? constant('MODULE_PAYMENT_SECPAY_ALTERNATIVE_ACCOUNT_NAME_'.strtoupper($method)):'';
                        $acceptor->setAccountHolder($name);
                        $acceptor->setReturnUrl(Yii::$app->urlManager->createAbsoluteUrl('checkout/process'));
                        $acceptor->setErrorUrl($this->getCheckoutUrl(['payment_error' => $this->code, 'error' => urlencode("Invalid payment processing")], self::PAYMENT_PAGE));
                        $iso3 = $this->billing['country']['countries_iso_code_3']??$this->delivery['country']['countries_iso_code_3'];
                        $acceptor->setBillingCountry($iso3);
                        $this->prepareOrderData();
                        $response = $this->hostedService->makeAcceptorPayment($acceptor, MODULE_PAYMENT_SECPAY_INSTALLATION_ALT_ID, $this->getRequestUrl());
                        if (!$response){
                            throw new \Exception('Incorrect Alternative Payment');
                        }
                        if ($response['outcome']['status'] == 'SUCCESS' && in_array($response['transaction']['status'], ['SUCCESS', 'PENDING'])){
                            $this->manager->set('secPayTransactionId', $response['transaction']['transactionId']);
                            tep_redirect($response['clientRedirect']['url'], 'SSL', false);
                        } else {
                            throw new \Exception('Incorrect Alternative Payment');
                        }
                    } else {
                        throw new \Exception('Invalid Acceptor Payment');
                    }
                } else {
                    $transaction = $this->getTransactionDetails($this->manager->get('secPayTransactionId'));
                    if ($transaction && $transaction['transaction']['status'] == 'SUCCESS'){
                        $order = $this->manager->getOrderInstance();
                        $order->info['order_status'] = defined('MODULE_PAYMENT_SECPAY_ORDER_STATUS_ID') && ((int) MODULE_PAYMENT_SECPAY_ORDER_STATUS_ID > 0) ? (int) MODULE_PAYMENT_SECPAY_ORDER_STATUS_ID : 0;
                    }
                }
            }
        } catch (\Exception $e) {
            tep_redirect($this->getCheckoutUrl(['payment_error' => $this->code, 'error' => urlencode($e->getMessage())], self::PAYMENT_PAGE), 'SSL', false);
        }
    }

    public function before_process() {
        $_payment = $this->manager->getSelectedPayment();
        if (strpos($_payment, '_') !== false) { //alternative
            list(, $method) = explode("_", $_payment);
            $this->_before_alternative_process($method);
        } else { //hosted
            $this->_before_hosted_process();
        }
    }
    
    public function _after_hosted_process(){
        if ($this->manager->has('secPaySessionId')) {
            try {
                $secPaySessionId = $this->manager->get('secPaySessionId');
                if ($secPaySessionId == Yii::$app->request->get('sessionId')) {
                    $order = $this->manager->getOrderInstance();
                    $orderId = (int) $order->order_id;
                    if ($orderId > 0) {
                        $response = $this->hostedService->getPaymentStatus(MODULE_PAYMENT_SECPAY_INSTALLATION_ID, $this->getRequestUrl(), $secPaySessionId);
                        $comment = [];
                        if (is_array($response)) {
                            $comment[] = "Status: {$response['status']}";
                        }
                        if (isset($response['hostedSessionStatus']) && isset($response['hostedSessionStatus']['transactionState'])) {
                            $comment[] = "Session Id: {$response['hostedSessionStatus']['sessionId']}";
                            $comment[] = "State: {$response['hostedSessionStatus']['sessionState']}";
                        }
                        if ($response['status'] == 'SUCCESS') {
                            if (isset($response['hostedSessionStatus']) && isset($response['hostedSessionStatus']['transactionState'])) {
                                $comment[] = "Transaction State: {$response['hostedSessionStatus']['transactionState']['transactionState']}";
                                $comment[] = "Transaction Id: {$response['hostedSessionStatus']['transactionState']['id']}";
                                if ($response['hostedSessionStatus']['transactionState']['transactionState'] == 'SUCCESS') { //confirmed transaction
                                    $transactionId = $response['hostedSessionStatus']['transactionState']['id']; //retrieve real transaction Id
                                    $this->finalizeTransaction($transactionId/*, $response['hostedSessionStatus']['transactionState']['transactionState']*/);
                                } else {
                                    //need webhook to retrieve 
                                }
                            }
                        } else {
                            $this->sendDebugEmail();
                        }
                        $orderAR = $this->ordersService->getById($orderId);
                        if ($orderAR)
                            $orderStatusHistory = $this->ordersService->addHistory($orderAR, $orderAR->orders_status, implode("\n", $comment));
                    }
                } else {
                    $this->sendDebugEmail();
                }
            } catch (\Exception $ex) {
                $this->sendDebugEmail($ex);
            }
            $this->manager->remove('secPaySessionId');
        } else {
            if (!$this->isHosted){
                $this->sendDebugEmail();
            }
        }
    }
    
    public function _after_alternative_process($method){
        try {
            if (!in_array($method, $this->alternative)) {
                throw new \Exception('Invalid Acceptor Payment');
            } else {
                if ($this->manager->has('secPayTransactionId')){
                    $comment = $this->finalizeTransaction($this->manager->get('secPayTransactionId'), true);
                    if (is_array($comment)){
                        $comment[] = "Payment Method: " . ucfirst($method);
                        $order = $this->manager->getOrderInstance();
                        $orderAR = $this->ordersService->getById(intval($order->order_id));
                        if ($orderAR)
                            $orderStatusHistory = $this->ordersService->addHistory($orderAR, $orderAR->orders_status, implode("\n", $comment));
                    }
                    $this->manager->remove('secPayTransactionId');
                }
            }
        } catch (\Exception $e) {
            $this->sendDebugEmail($e);
        }
    }
    
    protected function finalizeTransaction($transactionId, $returnAsComment = false){
        if ($transactionId) {
            $transaction = $this->getTransactionDetails($transactionId);
            if ($transaction) {
                $amount = (float) $transaction['transaction']['amount'];
                $state = $transaction['transaction']['status'];
                $currency = $transaction['transaction']['currency'];
                $invoice_id = $this->manager->getOrderSplitter()->getInvoiceId();
                $transaction = $this->manager->getTransactionManager($this)
                        ->addTransaction($transactionId, $state, $amount, $invoice_id, 'Customer\'s payment'); //??total inc tax
                if ($returnAsComment){
                    return  [
                        "Transaction State: {$transactionId}",
                        "Transaction Id: {$state}",
                        "Transaction Amount: {$amount}",
                        "Transaction Currency: {$currency}",
                    ];
                }
                return true;
            }
        }
        return false;
    }

    public function after_process() {
        $_payment = $this->manager->getSelectedPayment();
        if (strpos($_payment, '_') !== false) { //alternative
            list(, $method) = explode("_", $_payment);
            $this->_after_alternative_process($method);
        } else { //hosted
            $this->_after_hosted_process();
        }
    }

    public function call_webhooks() {
        $action = Yii::$app->request->get('action');
        switch($action){
            case 'error':
            default:
                $this->sendDebugEmail();
                break;
        }
    }

    public function get_error() {
        $error = ['title' => $this->code,
            'error' => (isset($_GET['error']) ? urldecode($_GET['error']) : 'Undefined error')];
        return $error;
    }

    public function pre_confirmation_check() {
        
    }

    public function confirmation() {
        
    }

    public function getTransactionDetails($transaction_id, PaymentTransactionManager $tManager = null) {
        try {
            $response = $this->hostedService->findTransaction(MODULE_PAYMENT_SECPAY_INSTALLATION_ID, $this->getRequestUrl(), $transaction_id);
            if ($tManager && $response) {
                $transaction = $response['transaction'];
                if (is_array($transaction)) {
                    $tManager->updateTransactionFromPayment($transaction_id, $transaction['status'], $transaction['amount'], date('Y-m-d H:i:s', strtotime($transaction['transactionTime'])));
                }
            }
            return $response;
        } catch (\Exception $ex) {
            $this->sendDebugEmail($ex);
        }
    }

    public function canRefund($transaction_id) {
        try {
            $response = $this->getTransactionDetails($transaction_id);
            $can = false;
            if (is_array($response['transaction'])) {
                $can = (in_array($response['transaction']['stage'], ['COMPLETE']) && $response['transaction']['type'] == 'PAYMENT');
            }

            if ($response['followUpStatus'] && isset($response['followUpStatus']['status'])) {
                $rAmount = 0;
                foreach ($response['followUpStatus']['status'] as $record) {
                    if (in_array($record['name'], ['REFUNDED', 'PARTIALLY_REFUNDED']) && is_array($record['followUpTransaction'])) {
                        foreach ($record['followUpTransaction'] as $refunded) {
                            $fResponse = $this->getTransactionDetails($refunded['transactionId']);
                            if (isset($fResponse['transaction'])) {
                                $rAmount += (float) $fResponse['transaction']['amount'];
                            }
                        }
                    }
                }

                if ($rAmount) {
                    $can = ($rAmount < $response['transaction']['amount']) && $can;
                }
            }
        } catch (\Exception $ex) {
            $this->sendDebugEmail($ex);
        }
        return $can;
    }

    public function refund($transaction_id, $amount = 0) {
        try {
            $order = $this->manager->getOrderInstance();
            $transaction = $this->hostedService->transaction();
            if (!$amount) {
                $response = $this->getTransactionDetails($transaction_id);
                if ($response) {
                    $amount = (float) $response['transaction']['amount'];
                }
            }
            $transaction->setAmount($amount);
            $transaction->setCurrency($order->info['currency']);

            $response = $this->hostedService->makeRefund(MODULE_PAYMENT_SECPAY_INSTALLATION_ID, $this->getRequestUrl(), $transaction_id);
            if (is_array($response['transaction'])) {
                $this->manager->getTransactionManager($this)
                        ->addTransactionChild($transaction_id, $response['transaction']['transactionId'], $response['transaction']['status'], $response['transaction']['amount'], ($amount ? 'Partial Refund' : 'Full Refund'));
            }
        } catch (\Exception $e) {
            $this->sendDebugEmail($e);
        }
        return false;
    }

    public function canVoid($transaction_id) {
        return false;
    }

    public function void($transaction_id) {
        return false;
    }

    public function configure_keys(): array {
        $status_id = defined('MODULE_PAYMENT_SECPAY_ORDER_STATUS_ID') ? MODULE_PAYMENT_SECPAY_ORDER_STATUS_ID : $this->getDefaultOrderStatusId();

        $params = ['MODULE_PAYMENT_SECPAY_STATUS' => ['title' => 'Enable SecPay Module',
                'desc' => 'Do you want to use SecPay Payment?',
                'value' => 'True',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '],
            'MODULE_PAYMENT_SECPAY_INSTALLATION_ID' => ['title' => 'Installation ID',
                'value' => '',
                'desc' => 'The Hosted Installation ID'],
            'MODULE_PAYMENT_SECPAY_USERNAME' => ['title' => 'Username',
                'value' => '',
                'desc' => 'The Username provided by Pay360 Payment'],
            'MODULE_PAYMENT_SECPAY_PASSWORD' => ['title' => 'Password',
                'value' => '',
                'desc' => 'Password provided by Pay360 Payment'],
            'MODULE_PAYMENT_SECPAY_TRANSACTION_SERVER' => ['title' => 'Transaction Server',
                'value' => 'Test',
                'set_function' => 'tep_cfg_select_option(array(\'Test\', \'Live\'), ',
                'desc' => 'Perform transactions on the production server or on the testing server.'],
            /* 'MODULE_PAYMENT_SECPAY_MODE' => [
              'title' => 'Payment Mode',
              'value' => 'Hosted',
              'set_function' => 'tep_cfg_select_option(array(\'Hosted\'), ',
              'desc' => 'Hosted'], */
            'MODULE_PAYMENT_SECPAY_INSTALLATION_ALT_ID' => ['title' => 'Installation ID (for alternative payments)',
                'value' => '',
                'desc' => 'The Cashier Installation ID'],
            'MODULE_PAYMENT_SECPAY_ALTERNATIVE' => ['title' => 'Alternative Payments (additionaly)',
                'value' => '',
                'desc' => 'Select alternative Payments',
                'set_function' => 'tep_cfg_select_multioption(array(\'Eps\', \'Giropay\', \'iDeal\', \'Sofort\'), '
            ],
            'MODULE_PAYMENT_SECPAY_ORDER_STATUS_ID' => ['title' => 'Set Order Status',
                'desc' => 'Set the status of prepared orders made with this payment module to this value',
                'value' => $status_id,
                'use_function' => '\common\helpers\Order::get_order_status_name',
                'set_function' => 'tep_cfg_pull_down_order_statuses('],
            'MODULE_PAYMENT_SECPAY_ZONE' => ['title' => 'Payment Zone',
                'desc' => 'If a zone is selected, only enable this payment method for that zone.',
                'value' => '0',
                'set_function' => 'tep_cfg_pull_down_zone_classes(',
                'use_function' => '\common\helpers\Zones::get_zone_class_title'],
            'MODULE_PAYMENT_SECPAY_DEBUG_EMAIL' => [
                'title' => 'Debug E-Mail Address',
                'desc' => 'All parameters of an invalid transaction will be sent to this email address.'
            ],
            'MODULE_PAYMENT_SECPAY_SORT_ORDER' => ['title' => 'Sort order of display.',
                'desc' => 'Sort order of display. Lowest is displayed first.',
                'value' => '0']];

        self::setAlternativeNames($params);        
        return $params;
    }

    public function describe_status_key(): ModuleStatus {
        return new ModuleStatus('MODULE_PAYMENT_SECPAY_STATUS', 'True', 'False');
    }

    public function describe_sort_key(): ModuleSortOrder {
        return new ModuleSortOrder('MODULE_PAYMENT_SECPAY_SORT_ORDER');
    }

    public function isOnline(): bool {
        return true;
    }

    private function updateStatus() {
        $this->enabled = defined('MODULE_PAYMENT_SECPAY_STATUS') && MODULE_PAYMENT_SECPAY_STATUS === 'True';
        if (
            $this->enabled === true &&
            defined('MODULE_PAYMENT_SECPAY_ZONE') &&
            ((int) MODULE_PAYMENT_SECPAY_ZONE > 0)) {
            $this->enabled = false;
            $zones = $this->zonesService->getAllByGeoZoneIdAndCountryId((int) MODULE_PAYMENT_SECPAY_ZONE, $this->billing['country']['id'], true);
            if ($zones) {
                foreach ($zones as $zone) {
                    if ($zone['zone_id'] < 1) {
                        $this->enabled = true;
                        break;
                    }
                    if ((int) $zone['zone_id'] === (int) $this->billing['zone_id']) {
                        $this->enabled = true;
                        break;
                    }
                }
            }
        }
    }

    function format_raw($number, $currency_code = '', $currency_value = '') {
        $currencies = \Yii::$container->get('currencies');

        if (empty($currency_code) || !$currencies->is_set($currency_code)) {
            $currency_code = \Yii::$app->settings->get('currency');
        }

        if (empty($currency_value) || !is_numeric($currency_value)) {
            $currency_value = $currencies->currencies[$currency_code]['value'];
        }

        return number_format(round($number * $currency_value, $currencies->currencies[$currency_code]['decimal_places'], 2), $currencies->currencies[$currency_code]['decimal_places'], '.', '');
    }

    function sendDebugEmail($response = array()) {

        if (tep_not_null(MODULE_PAYMENT_SECPAY_DEBUG_EMAIL)) {
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
                \common\helpers\Mail::send('', MODULE_PAYMENT_SECPAY_DEBUG_EMAIL, 'SecPay Debug E-Mail', trim($email_body), STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
            }
        }
    }
        
    public static function setAlternativeNames(&$configArray){
        $begining = array_slice($configArray, 0, 7, true);
        $config = [];
        if (defined('MODULE_PAYMENT_SECPAY_ALTERNATIVE') && !empty(MODULE_PAYMENT_SECPAY_ALTERNATIVE)){
            $alternative = array_map('strtolower', array_map('trim', explode(",", MODULE_PAYMENT_SECPAY_ALTERNATIVE)));
            if (is_array($alternative)){
                
                foreach($alternative as $key => $method){
                    $config['MODULE_PAYMENT_SECPAY_ALTERNATIVE_ACCOUNT_NAME_'.strtoupper($method)] = [
                        'title' => 'Account Name for ' . $method,
                        'value' => '',
                        'desc' => 'Account Name for ' . $method,
                    ];
                }
            }
        }
        $configArray = $begining + $config + array_slice($configArray, 7, sizeof($configArray), true);
    }

}
