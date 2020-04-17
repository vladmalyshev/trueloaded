<?php

namespace common\modules\orderPayment;

use Yii;
use common\classes\modules\ModulePayment;
use common\classes\modules\ModuleStatus;
use common\classes\modules\ModuleSortOrder;
use common\classes\modules\TransactionalInterface;
use common\services\PaymentTransactionManager;
use backend\services\OrdersService;
use common\services\ZonesService;
use common\modules\orderPayment\lib\SecurePay\ClientApi;

/**
 * Class securepay
 */
class securepay extends ModulePayment implements TransactionalInterface {

    const URL_LIVE_JS = 'https://payments.auspost.net.au/v3/ui/client/securepay-ui.min.js';
    const URL_SANDBOX_JS = 'https://payments-stest.npe.auspost.zone/v3/ui/client/securepay-ui.min.js';

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
    private $apiService;
    private $zonesService;
    private $ordersService;
    private $merchantCode;
    private $clientId;

    /** @var array */
    protected $defaultTranslationArray = [
        'MODULE_PAYMENT_SECUREPAY_TEXT_TITLE' => 'SecurePay',
        'MODULE_PAYMENT_SECUREPAY_TEXT_PUBLIC_TITLE' => 'SecurePay',
        'MODULE_PAYMENT_SECUREPAY_TEXT_DESCRIPTION' => 'SecurePay',
        'MODULE_PAYMENT_SECUREPAY_SORT_ORDER' => '120',
    ];

    public function __construct() {

        try {
            parent::__construct();
            $this->code = 'securepay';
            $this->title = MODULE_PAYMENT_SECUREPAY_TEXT_TITLE;
            $this->public_title = MODULE_PAYMENT_SECUREPAY_TEXT_PUBLIC_TITLE;
            $this->description = MODULE_PAYMENT_SECUREPAY_TEXT_DESCRIPTION;
            $this->sort_order = (int) MODULE_PAYMENT_SECUREPAY_SORT_ORDER;
            $this->order_status = defined('MODULE_PAYMENT_SECUREPAY_ORDER_STATUS_ID') && ((int) MODULE_PAYMENT_SECUREPAY_ORDER_STATUS_ID > 0) ? (int) MODULE_PAYMENT_SECUREPAY_ORDER_STATUS_ID : 0;
            if (defined('MODULE_PAYMENT_SECUREPAY_STATUS')) {
                if (MODULE_PAYMENT_SECUREPAY_TRANSACTION_SERVER === 'Test') {
                    $this->title .= ' [Test]';
                    $this->public_title .= ' (Test)';
                }
            }

            $this->zonesService = \Yii::createObject(ZonesService::class);

            $this->updateStatus();
            if (!$this->enabled) {
                return;
            }

            $this->merchantCode = MODULE_PAYMENT_SECUREPAY_MERCHANT_CODE;
            $this->clientId = MODULE_PAYMENT_SECUREPAY_CLIENT_ID;

            $this->apiService = new ClientApi($this->merchantCode, $this->clientId, MODULE_PAYMENT_SECUREPAY_CLIENT_SECRET);

            if (MODULE_PAYMENT_SECUREPAY_TRANSACTION_SERVER === 'Test') {
                $this->apiService->initTestMode();
            }
        } catch (\Exception $e) {
            $this->enabled = false;
        }
    }

    public function getTitle($method = '') {
        return $this->public_title;
    }

    public static function getRequestJsUrl(): string {
        if (MODULE_PAYMENT_SECUREPAY_TRANSACTION_SERVER === 'Test') {
            return self::URL_SANDBOX_JS;
        } else {
            return self::URL_LIVE_JS;
        }
    }

    public function selection(): array {

        $selection = [
            'id' => $this->code,
            'module' => $this->public_title,
            'fields' => []
        ];

        if ($this->isWithoutConfirmation()) {
            Yii::$app->getView()->registerJs($this->getJS());
            $selection['fields'] = [
                ['title' => $this->getContainer(),]
            ];
        }

        return $selection;
    }

    public function getContainer() {

        return '<script id="securepay-ui-js" src="' . $this->getRequestJsUrl() . '"></script>'
                . '<div id="securepay-ui-container" style="display:block"><div class="inside-box"></div></div>';
    }

    private function registerCss() {
        Yii::$app->getView()->registerCss(".sec-btn-div{text-align: center;}.securepay-box{width:auto!important;min-height:330px;}.securepay-box iframe{height: 280px!important;padding: 40px 40px 0px 40px;}");
    }

    public function getJS() {
        $confirm = defined('TEXT_CONFIRM_AND_PAY') ? TEXT_CONFIRM_AND_PAY : 'Confirm';
        $this->registerCallback("startSecurePay");
        $this->registerCss();
        $js = <<<EOD
        var _securePayObj = new getSecurePayObj();    
        function startSecurePay(){
            $('#securepay-ui-container').removeClass('set-popup');
            _popupSecure(function(){
                _securePayObj.init();
            })
        }

        function getSecurePayObj(){
            var obj = {
                mySecurePayUI:false,
                init:function(){
                    mySecurePayUI = new securePayUI.init({
                        containerId: 'securepay-ui-container-v',
                        scriptId: 'securepay-ui-js',
                        clientId: "{$this->clientId}",
                        merchantCode: "{$this->merchantCode}",
                        card: {
                            showCardIcons:true,
                            onTokeniseSuccess: function(tokenisedCard) {
                                if (paymentCollection.form){
                                    var hiddenInput = document.createElement('input');
                                    hiddenInput.setAttribute('type', 'hidden');
                                    hiddenInput.setAttribute('name', 'securePayToken');
                                    hiddenInput.setAttribute('value', tokenisedCard.token);
                                    paymentCollection.form.append(hiddenInput);
                                    // Send the source to server
                                    paymentCollection.finishCallback();
                                }
                            },
                            onTokeniseError: function(errors) {
                                console.error(errors);
                            },
                            onCardTypeChange:function(cardType){
                                //
                            },
                            onCardBINChange:function(cardBIN){
                                //
                            },
                            onFormValidityChange:function(valid){
                                //
                            }
                        },
                        onLoadComplete: function() {
                            //
                        }
                    });
                }
            }
            return obj;
        }
        function _popupSecure(callback){
            $('#securepay-ui-container').popUp({
                'event':'show',
                'box_class': 'securepay-box',
                'opened': function(){
                    $('.inside-box:last').attr('id', 'securepay-ui-container-v');
                    callback();
                    $('.securepay-box .pop-up-content').append("<div class='sec-btn-div'><button class='btn'>{$confirm}</button></div>");
                    $('.sec-btn-div button').click(function(e){ e.preventDefault(); mySecurePayUI.tokenise(); })
                }
            }).trigger('click');
        }
EOD;
        return $js;
    }

    public function popUpMode() {
        return true;
    }

    public function pre_confirmation_check() {
        $this->manager->remove('securePayToken');
        if (isset($_POST['securePayToken'])) {
            $this->manager->set('securePayToken', $_POST['securePayToken']);
        }
    }

    public function process_button(): bool {
        return false;
    }

    public function getOrderDetails($paymentToken) {
        $order = $this->manager->getOrderInstance();
        return [
            'merchantCode' => $this->merchantCode,
            'token' => $paymentToken,
            'ip' => \common\helpers\System::get_ip_address(),
            'amount' => $this->format_raw($order->info['total_inc_tax']),
        ];
    }

    public function before_process() {
        if (!$this->manager->has('securePayToken')) {
            tep_redirect($this->getCheckoutUrl(['error_message' => urlencode("SecurePay: Ivalid tokanization")], self::PAYMENT_PAGE));
        }

        try {
            $response = $this->apiService->createPayment($this->getOrderDetails($this->manager->get('securePayToken')));
            if ($response['status'] == 'paid') {
                $this->manager->set("securePayTransaction", [
                    'bankTransactionId' => $response['bankTransactionId'],
                    'orderId' => $response['orderId'],
                    'createdAt' => $response['createdAt'],
                    'amount' => $response['amount'],
                    'status' => $response['status'],
                    'gatewayResponseMessage' => $response['gatewayResponseMessage'],
                ]);
            } else {
                tep_redirect($this->getCheckoutUrl(['error_message' => urlencode("SecurePay: " . $response['errorCode'] . ", " . $response['gatewayResponseMessage'])], self::PAYMENT_PAGE));
            }
        } catch (\Exception $ex) {
            tep_redirect($this->getCheckoutUrl(['error_message' => urlencode($ex->getMessage())], self::PAYMENT_PAGE));
        }
    }

    public function after_process() {
        if ($this->manager->has('securePayTransaction')) {
            $order = $this->manager->getOrderInstance();
            $orderId = (int) $order->order_id;
            $response = $this->manager->get('securePayTransaction');
            if ($orderId > 0) {
                $currencies = Yii::$container->get('currencies');
                $this->ordersService = \Yii::createObject(OrdersService::class);
                $comment = [
                    'Bank Transaction Id: ' . $response['bankTransactionId'],
                    'Order Id: ' . $response['orderId'],
                    'Status: ' . $response['status'],
                    'Amount: ' . $currencies->format($response['amount'] / 100),
                    'Message: ' . $response['gatewayResponseMessage'],
                ];
                $invoice_id = $this->manager->getOrderSplitter()->getInvoiceId();
                $transaction = $this->manager->getTransactionManager($this)
                        ->addTransaction($response['orderId'], $response['status'], $response['amount'] / 100, $invoice_id, 'Customer\'s payment');
                $orderAR = $this->ordersService->getById($orderId);
                if ($orderAR)
                    $orderStatusHistory = $this->ordersService->addHistory($orderAR, $orderAR->orders_status, implode("\n", $comment));
            }
            $this->manager->remove('securePayTransaction');
        } else {
            $this->sendDebugEmail();
        }
        $this->manager->remove('securePayToken');
    }

    function format_raw($number, $currency_code = '', $currency_value = '') {
        $currencies = \Yii::$container->get('currencies');

        if (empty($currency_code) || !$currencies->is_set($currency_code)) {
            $currency_code = \Yii::$app->settings->get('currency');
        }

        if (empty($currency_value) || !is_numeric($currency_value)) {
            $currency_value = $currencies->currencies[$currency_code]['value'];
        }

        return (int) number_format(round($number * $currency_value, $currencies->currencies[$currency_code]['decimal_places'], 0), $currencies->currencies[$currency_code]['decimal_places'], '', '');
    }

    public function call_webhooks() {
        
    }

    public function get_error() {
        $error = ['title' => $this->code,
            'error' => (isset($_GET['error']) ? urldecode($_GET['error']) : 'Undefined error')];
        return $error;
    }

    public function confirmation() {
        
    }

    public function getTransactionDetails($transaction_id, PaymentTransactionManager $tManager = null) {
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

    public function configure_keys(): array {
        $status_id = defined('MODULE_PAYMENT_SECUREPAY_ORDER_STATUS_ID') ? MODULE_PAYMENT_SECUREPAY_ORDER_STATUS_ID : $this->getDefaultOrderStatusId();

        $params = ['MODULE_PAYMENT_SECUREPAY_STATUS' => ['title' => 'Enable SecurePay Module',
                'desc' => 'Do you want to use SecurePay Payment?',
                'value' => 'True',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '],
            'MODULE_PAYMENT_SECUREPAY_MERCHANT_CODE' => ['title' => 'Merchant Code',
                'value' => '',
                'desc' => 'The SecurePay Merchant Code'],
            'MODULE_PAYMENT_SECUREPAY_CLIENT_ID' => ['title' => 'Client Id',
                'value' => '',
                'desc' => 'The Client Id provided by SecurePay Payment'],
            'MODULE_PAYMENT_SECUREPAY_CLIENT_SECRET' => ['title' => 'Client Secret',
                'value' => '',
                'desc' => 'The Client Secret provided by SecurePay Payment'],
            'MODULE_PAYMENT_SECUREPAY_TRANSACTION_SERVER' => ['title' => 'Transaction Server',
                'value' => 'Test',
                'set_function' => 'tep_cfg_select_option(array(\'Test\', \'Live\'), ',
                'desc' => 'Perform transactions on the production server or on the testing server.'],
            'MODULE_PAYMENT_SECUREPAY_ORDER_STATUS_ID' => ['title' => 'Set Order Status',
                'desc' => 'Set the status of prepared orders made with this payment module to this value',
                'value' => $status_id,
                'use_function' => '\common\helpers\Order::get_order_status_name',
                'set_function' => 'tep_cfg_pull_down_order_statuses('],
            'MODULE_PAYMENT_SECUREPAY_ZONE' => ['title' => 'Payment Zone',
                'desc' => 'If a zone is selected, only enable this payment method for that zone.',
                'value' => '0',
                'set_function' => 'tep_cfg_pull_down_zone_classes(',
                'use_function' => '\common\helpers\Zones::get_zone_class_title'],
            'MODULE_PAYMENT_SECUREPAY_DEBUG_EMAIL' => [
                'title' => 'Debug E-Mail Address',
                'desc' => 'All parameters of an invalid transaction will be sent to this email address.'
            ],
            'MODULE_PAYMENT_SECUREPAY_SORT_ORDER' => ['title' => 'Sort order of display.',
                'desc' => 'Sort order of display. Lowest is displayed first.',
                'value' => '0']];

        return $params;
    }

    public function describe_status_key(): ModuleStatus {
        return new ModuleStatus('MODULE_PAYMENT_SECUREPAY_STATUS', 'True', 'False');
    }

    public function describe_sort_key(): ModuleSortOrder {
        return new ModuleSortOrder('MODULE_PAYMENT_SECUREPAY_SORT_ORDER');
    }

    public function isOnline(): bool {
        return true;
    }

    private function updateStatus() {
        $this->enabled = defined('MODULE_PAYMENT_SECUREPAY_STATUS') && MODULE_PAYMENT_SECUREPAY_STATUS === 'True';
        $currency = Yii::$app->settings->get('currency');
        if ($currency != 'AUD')
            $this->enabled = false;
        if (
                $this->enabled === true &&
                defined('MODULE_PAYMENT_SECUREPAY_ZONE') &&
                ((int) MODULE_PAYMENT_SECUREPAY_ZONE > 0)) {
            $this->enabled = false;
            $zones = $this->zonesService->getAllByGeoZoneIdAndCountryId((int) MODULE_PAYMENT_SECUREPAY_ZONE, $this->billing['country']['id'], true);
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

    function sendDebugEmail($response = array()) {

        if (tep_not_null(MODULE_PAYMENT_SECUREPAY_DEBUG_EMAIL)) {
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
                \common\helpers\Mail::send('', MODULE_PAYMENT_SECUREPAY_DEBUG_EMAIL, 'SecPay Debug E-Mail', trim($email_body), STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
            }
        }
    }

}
