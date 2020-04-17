<?php

namespace common\modules\orderPayment;

use backend\services\OrdersService;
use common\classes\modules\ModulePayment;
use common\classes\modules\ModuleStatus;
use common\classes\modules\ModuleSortOrder;
use common\classes\modules\TransactionalInterface;
use common\classes\Order;
use common\modules\orderPayment\GlobalPayments\services\GlobalPaymentsService;
use common\modules\orderPayment\GlobalPayments\VO\TransactionDTO;
use common\services\CustomersService;
use common\services\PaymentTransactionManager;
use common\services\storages\StorageInterface;
use common\services\ZonesService;
use GlobalPayments\Api\Entities\Exceptions\ApiException;
use GlobalPayments\Api\Entities\Transaction;
use GlobalPayments\Api\ServicesContainer;

/**
 * Class globalpaymentshpp
 * @property \common\services\OrderManager $manager
 */
class globalpaymentshpp extends ModulePayment implements TransactionalInterface
{
    const REQUEST_URL_LIVE = 'https://pay.realexpayments.com/pay';
    const REQUEST_URL_SANDBOX = 'https://pay.sandbox.realexpayments.com/pay';
    const TRANSACTION_URL_LIVE = 'https://api.realexpayments.com/epage-remote.cgi';
    const TRANSACTION_URL_SANDBOX = 'https://api.sandbox.realexpayments.com/epage-remote.cgi';

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
    /** @var string */
    public $public_title;
    /** @var string */
    public $requestUrl;
    /** @var string */
    public $transactionUrl;
    /** @var GlobalPaymentsService */
    private $globalPaymentsService;
    /** @var OrdersService */
    private $ordersService;
    /**@var CustomersService */
    private $customersService;
    /** @var ZonesService */
    private $zonesService;
    /** @var StorageInterface */
    private $storage;

    /** @var array */
    protected $defaultTranslationArray = [
        'MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_TEXT_TITLE' => 'Global Payments',
        'MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_TEXT_DESCRIPTION' => 'Global Payments Inc. (NYSE: GPN) is a leading worldwide provider of payment technology and software solutions delivering innovative services to our customers globally.',
        'MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_SORT_ORDER' => '100',
    ];

    public function __construct()
    {
        try {
            parent::__construct();
            $this->code = 'globalpaymentshpp';
            $this->title = MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_TEXT_TITLE;
            $this->description = MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_TEXT_DESCRIPTION;
            $this->sort_order = (int)MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_SORT_ORDER;
            if (defined('MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_STATUS')) {
                $this->requestUrl = self::getRequestUrl();
                $this->transactionUrl = self::getTransactionUrl();
                if (MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_TRANSACTION_SERVER === 'Test') {
                    $this->title .= ' [Test]';
                    $this->public_title .= ' (Test)';
                }
            }
            $this->updateStatus();
            if (!$this->enabled) {
                return;
            }
            $this->globalPaymentsService = \Yii::createObject(GlobalPaymentsService::class);
            $this->ordersService = \Yii::createObject(OrdersService::class);
            $this->customersService = \Yii::createObject(CustomersService::class);
            $this->zonesService = \Yii::createObject(ZonesService::class);
            $this->storage = \Yii::$app->get('storage');
        } catch (\Exception $e) {
            $this->enabled = false;
        }
    }

    public static function getRequestUrl(): string
    {
        $requestUrl = self::REQUEST_URL_LIVE;
        if (defined('MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_TRANSACTION_SERVER') && MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_TRANSACTION_SERVER === 'Test') {
            $requestUrl = self::REQUEST_URL_SANDBOX;
        }
        return $requestUrl;
    }

    public static function getTransactionUrl(): string
    {
        $requestUrl = self::TRANSACTION_URL_LIVE;
        if (defined('MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_TRANSACTION_SERVER') && MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_TRANSACTION_SERVER === 'Test') {
            $requestUrl = self::TRANSACTION_URL_SANDBOX;
        }
        return $requestUrl;
    }

    public function selection(): array
    {
        $selection = [
            'id' => $this->code,
            'icon' => '<img src="' . tep_href_link('images/payment.png') . '">',
            'module' => $this->title,
            'fields' => []
        ];
        if ($this->isWithoutConfirmation()) {
            $selection ['fields'][] = [
                'title' => $this->getTemplate(),
                'field' => '',
            ];
        } else {
            $selection ['fields'][] = [
                'title' => MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_WARNING_TEXT,
                'field' => '',
            ];
        }
        return $selection;
    }

    public function process_button(): bool
    {
        return false;
    }


    public function before_process()
    {
        $responseCode = '';
        try {
            $order = $this->manager->getOrderInstance();
            $paymentResponse = \Yii::$app->request->post('hppResponse');
            if (!$paymentResponse) {
                throw new \DomainException('Payment data empty');
            }
            $merchantId = MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_MERCHANT_ID;
            $secret = MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_SHARED_SECRET;
            $account = MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_ACCOUNT;
            $hostedService = $this->globalPaymentsService->getGPService($merchantId, $account, $secret, $this->requestUrl);
            $parsedResponse = $hostedService->parseResponse($paymentResponse, true);
            $responseCode = $parsedResponse->responseCode;
            if ($responseCode !== GlobalPaymentsService::PAYMENT_SUCCESS_CODE) {
                throw new \DomainException('Payment failed');
            }
            if (defined('MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_TEXT_TITLE') && $order->info['payment_method'] === $order->info['payment_class']) {
                $order->info['payment_method'] = MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_TEXT_TITLE;
            }
            $this->storage->set('gpResponseValues', json_encode($parsedResponse));
        } catch (ApiException $e) {
            tep_redirect($this->getCheckoutUrl(['payment_error' => $responseCode, 'error' => $e->getMessage()], self::PAYMENT_PAGE), 'SSL', false);
        } catch (\Exception $e) {
            tep_redirect($this->getCheckoutUrl(['payment_error' => $responseCode, 'error' => $e->getMessage()], self::PAYMENT_PAGE), 'SSL', false);
        }
    }


    public function after_process()
    {
        /** @var Order $order */
        $order = $this->manager->getOrderInstance();
        $orderId = (int)$order->order_id;
        if ($orderId > 0) {
            $comment = [MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_ORDER_STATUS_TEXT];
            if ($this->storage->has('gpResponseValues')) {
                $parsedResponse = json_decode($this->storage->get('gpResponseValues'), true);
                $responseValues = $parsedResponse['responseValues'];
                $storeName = defined('STORE_NAME') ? STORE_NAME : '';
                $comment = array_merge($comment, [
                    "Transaction Id: {$responseValues['PASREF']}",
                    "Schema Reference Data: {$responseValues['SRD']}",
                    "Internal Transaction Id: {$responseValues['ORDER_ID']}",
                    "Message: {$responseValues['MESSAGE']}",
                    "Amount: {$responseValues['AMOUNT']}",
                ]);
                $customer = $this->customersService->getById($order->customer['id']);
                if (isset($responseValues['PAYER_SETUP']) && $responseValues['PAYER_SETUP'] === GlobalPaymentsService::PAYMENT_SUCCESS_CODE) {
                    $this->customersService->savePayerReference($customer, $responseValues['SAVED_PAYER_REF']);
                }
                $this->globalPaymentsService->addGlobalPaymentsTransaction($responseValues['PASREF'], $orderId, $storeName, $customer, $responseValues);
                $transactionData = TransactionDTO::create(
                    $responseValues['PASREF'],
                    $responseValues['PASREF'],
                    'Success',
                    (float)$responseValues['AMOUNT'] / 100,
                    "Customer's payment {$responseValues['MESSAGE']}",
                    $orderId,
                    \common\helpers\OrderPayment::OPYS_SUCCESSFUL,
                    json_encode(\common\helpers\OrderPayment::getOrderPaymentSnapshot($order)),
                    trim($order->info['currency']),
                    (float)$order->info['currency_value']
                );
                $this->savePaymentTransaction($transactionData);
                $this->storage->remove('gpResponseValues');
            }
            $orderAR = $this->ordersService->getById($orderId);
            $this->ordersService->changeStatus($orderAR, MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_ORDER_STATUS_ID, implode("\n", $comment));
            unset($orderAR);
            $this->storage->remove('gpOrderId');
        }
    }

    public function get_error()
    {
        global $HTTP_GET_VARS;

        $msg = '';
        if (stripslashes(urldecode($HTTP_GET_VARS['response_text'])) !== '') {
            $msg = stripslashes(urldecode($HTTP_GET_VARS['response_text']));
        } elseif (stripslashes(urldecode($HTTP_GET_VARS['error'])) !== '') {
            $msg = stripslashes(urldecode($HTTP_GET_VARS['error']));
        }
        $error = ['title' => MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_TEXT_ERROR,
            'error' => $msg];
        return $error;
    }

    public function confirmation(): array
    {
        $output = $this->getTemplate();
        return ['title' => $output];
    }

    public function getTransactionDetails($transaction_id, PaymentTransactionManager $tManager = null)
    {
        return true;
    }

    public function canRefund($transaction_id)
    {
        return $this->canTransaction($transaction_id);
    }

    public function refund($transaction_id, $amount = 0)
    {
        try {
            $order = $this->manager->getOrderInstance();
            $transaction = $this->globalPaymentsService->findTransaction($transaction_id, true);
            $gpTransaction = $this->getGPTransaction($transaction);
            $message = 'Partial Refund';
            if (!$amount) {
                $message = 'Full Refund';
                $amount = (float)$transaction['amount'] / 100;
            }
            $response = $gpTransaction
                ->refund($amount)
                ->withCurrency($this->globalPaymentsService->getPaymentCurrency($order->info['currency']))
                ->execute();

            $transactionData = TransactionDTO::create(
                (string)$response->transactionId,
                $transaction['transaction_id'],
                $response->responseCode,
                (float)$amount,
                "{$message} {$response->responseMessage}",
                (int)$order->order_id,
                \common\helpers\OrderPayment::OPYS_REFUNDED,
                json_encode(\common\helpers\OrderPayment::getOrderPaymentSnapshot($order)),
                trim($order->info['currency']),
                (float)$order->info['currency_value'],
                1
            );
            $this->savePaymentTransaction($transactionData);
            if ($response->responseCode === GlobalPaymentsService::PAYMENT_SUCCESS_CODE) {
                $order->info['comments'] = 'VOID State: ' . $response->responseCode . "\n" . 'Void Date: ' . date('d-m-Y H:i:s') . "\n" . 'Message: ' . $response->responseMessage;
            }
        } catch (ApiException $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    public function canVoid($transaction_id)
    {
        return !$this->canTransaction($transaction_id);
    }

    public function void($transaction_id)
    {
        try {
            $order = $this->manager->getOrderInstance();
            $transaction = $this->globalPaymentsService->findTransaction($transaction_id, true);
            $gpTransaction = $this->getGPTransaction($transaction);
            $response = $gpTransaction->void()->execute();
            $transactionData = TransactionDTO::create(
                (string)$response->transactionId,
                $transaction['transaction_id'],
                $response->responseCode,
                (float)$transaction['amount'] / 100,
                "Fully Voided payment {$response->responseMessage}",
                (int)$order->order_id,
                \common\helpers\OrderPayment::OPYS_CANCELLED,
                json_encode(\common\helpers\OrderPayment::getOrderPaymentSnapshot($order)),
                trim($order->info['currency']),
                (float)$order->info['currency_value']
            );
            $this->savePaymentTransaction($transactionData);
            if ($response->responseCode === GlobalPaymentsService::PAYMENT_SUCCESS_CODE) {
                $order->info['comments'] = 'VOID State: ' . $response->responseCode . "\n" . 'Void Date: ' . date('d-m-Y H:i:s') . "\n" . 'Message: ' . $response->responseMessage;
            }
        } catch (ApiException $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
        return false;
    }

    private function savePaymentTransaction(TransactionDTO $transaction)
    {
        $parentTransaction = $transaction->getParentTransactionId() ?? $transaction->getTransactionId();
        $transactionManager = $this->manager->getTransactionManager($this);
        $orderPaymentRecord = null;
        if ($parentTransaction === $transaction->getTransactionId()) {
            $invoice_id = $this->manager->getOrderSplitter()->getInvoiceId();
            $transactionManager->addTransaction($transaction->getTransactionId(), $transaction->getTransactionCode(), $transaction->getAmount(), $invoice_id, $transaction->getMessage());
            $orderPaymentRecord = $this->searchRecord($transaction->getTransactionId());
        } else {
            $transactionManager->addTransactionChild($parentTransaction, $transaction->getTransactionId(), $transaction->getTransactionCode(), $transaction->getAmount(), $transaction->getMessage());
            $orderPaymentParentRecord = $this->searchRecord($parentTransaction);
            if ($orderPaymentParentRecord) {
                $orderPaymentRecord = $this->searchRecord($transaction->getTransactionId());
            }
        }
        if ($orderPaymentRecord) {
            $orderPaymentRecord->orders_payment_id_parent = isset($orderPaymentParentRecord) ?  (int)$orderPaymentParentRecord->orders_payment_id : 0;
            $orderPaymentRecord->orders_payment_order_id = $transaction->getOrderId();
            $orderPaymentRecord->orders_payment_is_credit = $transaction->getIsCredit();
            $orderPaymentRecord->orders_payment_status = $transaction->getPaymentStatus();
            $orderPaymentRecord->orders_payment_amount = $transaction->getAmount();
            $orderPaymentRecord->orders_payment_currency = $transaction->getCurrency();
            $orderPaymentRecord->orders_payment_currency_rate = $transaction->getCurrencyRate();
            $orderPaymentRecord->orders_payment_snapshot = $transaction->getOrderSnapshot();
            $orderPaymentRecord->orders_payment_transaction_status = $transaction->getTransactionCode();
            $orderPaymentRecord->orders_payment_transaction_date = $transaction->getDate()->format('Y-m-d H:i:s');
            $orderPaymentRecord->orders_payment_date_create = $transaction->getDate()->format('Y-m-d H:i:s');
            $orderPaymentRecord->save();
        }
    }

    private function getGPTransaction(array $transaction)
    {
        $merchantId = MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_MERCHANT_ID;
        $secret = MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_SHARED_SECRET;
        $account = MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_ACCOUNT;
        $rebatePasswords = MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_REBATE_PASSWORD;
        $refundPasswords = MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_REFUND_PASSWORD;

        $config = $this->globalPaymentsService->getConfig($merchantId, $account, $secret, $this->transactionUrl, $rebatePasswords, $refundPasswords);
        ServicesContainer::configure($config);
        $gpTransaction = Transaction::fromId($transaction['transaction_id'], $transaction['gp_order_id']);
        $raw = json_decode($transaction['raw'], true);
        $gpTransaction->authorizationCode = $raw['AUTHCODE'];
        return $gpTransaction;

    }

    private function getTemplate(): string
    {
        \Yii::$app->getView()->registerJsFile(\frontend\design\Info::themeFile('/js/rxp-js-new.min.js'));
        \Yii::$app->getView()->registerJs($this->getJS());
        $frame = '';
        if (MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_MODE === 'Embedded') {
            $frame = '<iframe id="targetIframe" style="display:none;"></iframe>';
        }
        $output = <<<EOD
        {$frame}
        <div id="pay_but_wrap">
            <button type="button" id="payButtonId" class="btn" style="display:none">Checkout Now</button>
        </div>
EOD;
        return $output;
    }
    
    public function getJS(){
        $params = [];
        if ($this->isPartlyPaid() && $this->manager->isInstance()) {
            $order = $this->manager->getOrderInstance();
            $orderId = $order->parent_id ?? $order->parent_id ?? null;
            if ($orderId) {
                $params = ['order_id' => $orderId];
            }
        }
        $return_url = $this->getCheckoutUrl($params, self::PROCESS_PAGE);
        if (MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_MODE === 'Embedded') {            
            $init = "RealexHpp.embedded.init('payButtonId', 'targetIframe', '{$return_url}', jsonFromServerSdk);";
        } else {
            $init = "RealexHpp.lightbox.init('payButtonId', '{$return_url}', jsonFromServerSdk);";
        }
        $testServer = "RealexHpp.setHppUrl('https://pay.realexpayments.com/pay');";
        if (MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_TRANSACTION_SERVER == 'Test') {
            $testServer = "RealexHpp.setHppUrl('https://pay.sandbox.realexpayments.com/pay');";
        }

        $this->registerCallback('initGPPay');
        
        $output = <<<EOD
        {$testServer}
        function initGPPay() {
         $.getJSON("global-payments/authorization-request",{id:$('input[name="order_id"]').val()}, function (jsonFromServerSdk) {
             {$init}
             $("#payButtonId").click();
         });
         return false;
        }
EOD;
        return $output;
    }

    /**
     * TODO WARNING NOW API DID NOT ALLOWED CHECK TRANSACTION iS SETTLED
     * THIS TEMP SOLUTION CHECK 6H
     * @param $transaction_id
     * @return bool
     */
    private function canTransaction($transaction_id)
    {
        try {
            $transaction = $this->globalPaymentsService->findTransaction($transaction_id, true);
            $raw = json_decode($transaction['raw'], true);
            $date = new \DateTimeImmutable($raw['TIMESTAMP']);
            $currentDate = new \DateTimeImmutable();
            return $date->add(new \DateInterval('PT6H')) < $currentDate;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function configure_keys(): array
    {
        $status_id = defined('MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_ORDER_STATUS_ID') ? MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_ORDER_STATUS_ID : $this->getDefaultOrderStatusId();

        $params = ['MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_STATUS' => ['title' => 'Enable Global Payments Hosted Payment Page Module',
            'desc' => 'Do you want to use Global Payments Hosted Payment Page Payments?',
            'value' => 'True',
            'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '],
            'MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_ACCOUNT' => ['title' => 'Account',
                'value' => 'internet',
                'desc' => 'The Account (or sub-Account) is provided by Global Payments. Leave it set to \'internet\' unless instructed by Realex.'],
            'MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_MERCHANT_ID' => ['title' => 'Merchant ID',
                'value' => 'MerchantID',
                'desc' => 'The merchant ID provided by Global Payments'],
            'MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_SHARED_SECRET' => ['title' => 'Shared Secret',
                'value' => 'secret',
                'desc' => 'The Shared Secret provided by Global Payments'],
            'MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_REFUND_PASSWORD' => ['title' => 'Password For Refund',
                'value' => '',
                'desc' => 'Password For Refund transactions  provided by Global Payments'],
            'MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_REBATE_PASSWORD' => ['title' => 'Password For Rebate',
                'value' => '',
                'desc' => 'Password For Rebate transactions provided by Global Payments'],
            'MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_CURRENCY' => ['title' => 'Transaction Currency',
                'value' => 'EUR',
                'set_function' => 'tep_cfg_select_option(array(\'USD\',\'CHF\',\'EUR\',\'GBP\',\'JPY\', \'HKD\', \'SEK\'), ',
                'desc' => 'The currency to use for credit card transactions'],
            'MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_TRANSACTION_SERVER' => ['title' => 'Transaction Server',
                'value' => 'Test',
                'set_function' => 'tep_cfg_select_option(array(\'Test\', \'Live\'), ',
                'desc' => 'Perform transactions on the production server or on the testing server.'],
            'MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_MODE' => [
                'title' => 'Payment Mode',
                'value' => 'Lightbox',
                'set_function' => 'tep_cfg_select_option(array(\'Lightbox\', \'Embedded\'), ',
                'desc' => 'Lightbox - pay in modal window, Embedded - pay in iframe window'],
            'MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_ORDER_STATUS_ID' => ['title' => 'Set Order Status',
                'desc' => 'Set the status of prepared orders made with this payment module to this value',
                'value' => $status_id,
                'use_function' => '\common\helpers\Order::get_order_status_name',
                'set_function' => 'tep_cfg_pull_down_order_statuses('],
            'MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_ORDER_STATUS_TEXT' => ['title' => 'Status Comment',
                'value' => 'Successfully paid Global Payments',
                'desc' => 'Text status order status'],
            'MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_WARNING_TEXT' => ['title' => 'Comment Payment',
                'value' => 'You will be redirected to a secure payment page at the end of the process.',
                'desc' => 'Text warning in checkout'],
            'MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_ZONE' => ['title' => 'Payment Zone',
                'desc' => 'If a zone is selected, only enable this payment method for that zone.',
                'value' => '0',
                'set_function' => 'tep_cfg_pull_down_zone_classes(',
                'use_function' => '\common\helpers\Zones::get_zone_class_title'],
            'MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_SORT_ORDER' => ['title' => 'Sort order of display.',
                'desc' => 'Sort order of display. Lowest is displayed first.',
                'value' => '0']];

        return $params;
    }

    public function describe_status_key(): ModuleStatus
    {
        return new ModuleStatus('MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_STATUS', 'True', 'False');
    }

    public function describe_sort_key(): ModuleSortOrder
    {
        return new ModuleSortOrder('MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_SORT_ORDER');
    }

    public function isOnline(): bool
    {
        return true;
    }

    private function updateStatus()
    {
        $this->enabled = defined('MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_STATUS') && MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_STATUS === 'True';
        if (
            $this->enabled === true &&
            defined('MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_ZONE') &&
            ((int)MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_ZONE > 0)) {
            $this->enabled = false;
            $zones = $this->zonesService->getAllByGeoZoneIdAndCountryId((int)MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_ZONE, $this->billing['country']['id'], true);
            if ($zones) {
                foreach ($zones as $zone) {
                    if ($zone['zone_id'] < 1) {
                        $this->enabled = true;
                        break;
                    }
                    if ((int)$zone['zone_id'] === (int)$this->billing['zone_id']) {
                        $this->enabled = true;
                        break;
                    }
                }
            }
        }
    }

    public function popUpMode() {
        return $this->isWithoutConfirmation();
    }
}
