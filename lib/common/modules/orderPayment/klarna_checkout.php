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

use Yii;
use common\classes\modules\ModulePayment;
use common\classes\modules\ModuleStatus;
use common\classes\modules\ModuleSortOrder;
use common\classes\modules\TransactionalInterface;
use common\helpers\OrderPayment as OrderPaymentHelper;

require_once('lib/klarna.php');

//ini_set('display_errors', 1);
class klarna_checkout extends ModulePayment implements TransactionalInterface {

    var $code, $title, $description, $enabled, $test_mode;
    protected $defaultTranslationArray = [
        'MODULE_PAYMENT_KLARNA_CHECKOUT_TEXT_TITLE' => 'Klarna checkout',
        'MODULE_PAYMENT_KLARNA_CHECKOUT_TEXT_DESCRIPTION' => 'Klarna checkout'
    ];

    // class constructor
    function __construct() {
        parent::__construct();

        $this->code = 'klarna_checkout';
        $this->title = MODULE_PAYMENT_KLARNA_CHECKOUT_TEXT_TITLE;
        $this->description = MODULE_PAYMENT_KLARNA_CHECKOUT_TEXT_DESCRIPTION;
        if (!defined('MODULE_PAYMENT_KLARNA_CHECKOUT_STATUS')) {
            $this->enabled = false;
            return;
        }
        $this->sort_order = defined('MODULE_PAYMENT_KLARNA_CHECKOUT_SORT_ORDER') ? (int)MODULE_PAYMENT_KLARNA_CHECKOUT_SORT_ORDER : 0;
        $this->enabled = ((MODULE_PAYMENT_KLARNA_CHECKOUT_STATUS == 'True') ? true : false);
        $this->order_status = defined('MODULE_PAYMENT_KLARNA_CHECKOUT_ORDER_STATUS_ID') && ((int) MODULE_PAYMENT_KLARNA_CHECKOUT_ORDER_STATUS_ID > 0) ? (int) MODULE_PAYMENT_KLARNA_CHECKOUT_ORDER_STATUS_ID : 0;
        $this->update_status();
        $this->widget = defined('MODULE_PAYMENT_KLARNA_CHECKOUT_WIDGET') && MODULE_PAYMENT_KLARNA_CHECKOUT_WIDGET == 'js'? true: false;
    }

    // class methods
    function update_status() {
        if (
                (!defined('MODULE_PAYMENT_KLARNA_CHECKOUT_EID') || empty(MODULE_PAYMENT_KLARNA_CHECKOUT_EID)) ||
                !defined('MODULE_PAYMENT_KLARNA_CHECKOUT_SECRET') || empty(MODULE_PAYMENT_KLARNA_CHECKOUT_SECRET)
        ) {
            $this->enabled = false;
        }

        $currency = \Yii::$app->settings->get('currency');
        if (!$currency) $currency = DEFAULT_CURRENCY;

        $dependance = ['SEK' => ['SE'], 'EUR' => ['FI', 'DE', 'AT', 'NL', 'DK'], 'NOK' => ['NO'], 'USD' => ['US'], 'GBP' => ['GB']];

        if (!array_key_exists($currency, $dependance)) {
            $this->enabled = false;
        } else {
            if (!in_array($this->billing['country']['countries_iso_code_2'], $dependance[$currency])) {
                $this->enabled = false;
            }
        }
    }

    function javascript_validation() {
        return false;
    }

    function isTestMode() {
        return (defined('MODULE_PAYMENT_KLARNA_CHECKOUT_TEST_MODE') && MODULE_PAYMENT_KLARNA_CHECKOUT_TEST_MODE == 'True' ? true : false);
    }

    function getEndPoint() {
        if ($this->billing['coutry']['countries_iso_code_2'] == 'US') {
            if ($this->isTestMode()) {
                return \Klarna\Rest\Transport\ConnectorInterface::NA_TEST_BASE_URL;
            } else {
                return \Klarna\Rest\Transport\ConnectorInterface::NA_BASE_URL;
            }
        } else {
            if ($this->isTestMode()) {
                return \Klarna\Rest\Transport\ConnectorInterface::EU_TEST_BASE_URL;
            } else {
                return \Klarna\Rest\Transport\ConnectorInterface::EU_BASE_URL;
            }
        }
    }

    private function _getConnector() {
        return \Klarna\Rest\Transport\GuzzleConnector::create(
                        MODULE_PAYMENT_KLARNA_CHECKOUT_EID, MODULE_PAYMENT_KLARNA_CHECKOUT_SECRET, $this->getEndPoint()
        );
    }
    
    private function getTermsUrlPath(){
        return (defined('MODULE_PAYMENT_KLARNA_CHECKOUT_CONDITIONS') && !empty(MODULE_PAYMENT_KLARNA_CHECKOUT_CONDITIONS)? MODULE_PAYMENT_KLARNA_CHECKOUT_CONDITIONS : 'terms-conditions');
    }

    private function getMerchantUrls(){
        if($this->widget){
            return [
                'confirmation' => Yii::$app->urlManager->createAbsoluteUrl(['callback/webhooks', 'set' => 'payment', 'module' => $this->code, 'action' => 'confirm']) . "&klarna_order_id={session.id}",
                'push' => Yii::$app->urlManager->createAbsoluteUrl(['callback/webhooks', 'set' => 'payment', 'action' => 'push', 'module' => $this->code]) . "&klarna_order_id={session.id}",
                'notification' => Yii::$app->urlManager->createAbsoluteUrl(['callback/webhooks', 'set' => 'payment', 'module' => $this->code, 'action' => 'notification']) . "&klarna_order_id={session.id}",
                'terms' => Yii::$app->urlManager->createAbsoluteUrl([$this->getTermsUrlPath()], 'https'),
                'checkout' => $this->getCheckoutUrl([]),
            ];
        } else {
            return [
                'confirmation' => Yii::$app->urlManager->createAbsoluteUrl(['callback/webhooks', 'set' => 'payment', 'module' => $this->code, 'action' => 'confirm']) . "&klarna_order_id={checkout.order.id}",
                'push' => Yii::$app->urlManager->createAbsoluteUrl(['callback/webhooks', 'set' => 'payment', 'action' => 'push', 'module' => $this->code]) . "&klarna_order_id={checkout.order.id}",
                'notification' => Yii::$app->urlManager->createAbsoluteUrl(['callback/webhooks', 'set' => 'payment', 'module' => $this->code, 'action' => 'notification']) . "&klarna_order_id={checkout.order.id}",
                'terms' => Yii::$app->urlManager->createAbsoluteUrl([$this->getTermsUrlPath()], 'https'),
                'checkout' => $this->getCheckoutUrl([]),
            ];
        }
    }

    private function getOrderData() {
        $order = $this->manager->getOrderInstance();
        $cart = $this->manager->getCart();
        if (!$this->manager->isCustomerAssigned()) {
            $billing = $this->manager->getBillingForm(null, false);
            $country = \common\helpers\Country::get_country_info_by_id($billing->country);
            if (Yii::$app->request->isPost) {
                $contactForm = $this->manager->getCustomerContactForm(false);
                $contactForm->load(Yii::$app->request->post());
                $order->customer['email_address'] = $contactForm->email_address;
            }
            $bAddress = [
                'city' => $this->convert($billing->city),
                'country' => $country['countries_iso_code_2'],
                'given_name' => $this->convert($billing->firstname),
                'family_name' => $this->convert($billing->lastname),
                'postal_code' => $billing->postcode,
                'region' => $this->convert($billing->state),
                'street_address' => $this->convert($billing->street_address),
                'email' => $order->customer['email_address'],
                'phone' => $order->customer['telephone'],
            ];
        } else {
            //$billing = $this->billing;
            $billing = $order->billing ? $order->billing : $this->billing;
            $bAddress = [
                'city' => $this->convert($billing['city']),
                'country' => $billing['country']['iso_code_2'],
                'given_name' => $this->convert($billing['firstname']),
                'family_name' => $this->convert($billing['lastname']),
                'postal_code' => $this->convert($billing['postcode']),
                'region' => $billing['state'],
                'street_address' => $this->convert($billing['street_address']),
                'email' => $this->manager->getCustomersIdentity()->customers_email_address,
                'phone' => $order->customer['telephone'],
            ];
        }
        if (Yii::$app->settings->has('locale')){
            $locale = Yii::$app->settings->get('locale');
        } else {
            global $lng;
            $locale = $lng->language['locale'];
        }

        $rOrder = [
            'purchase_country' => $this->billing['country']['countries_iso_code_2'],
            'purchase_currency' => strtolower(Yii::$app->settings->get('currency')),
            'locale' => str_replace('_', '-', $locale),
            'merchant_urls' => $this->getMerchantUrls(),
            'billing_address' => $bAddress,
            'order_amount' => $this->format_raw($order->info['total_inc_tax']), // 10000,
            'order_tax_amount' => $this->format_raw($order->info['tax']), //2000,
            'order_lines' => [],
            'html_snippet' => "<div id='klarna-checkout-container'></div>",
            'customer' => [
                'date_of_birth' => '',
                'phone' => $order->customer['telephone'],
            ],
            'merchant_reference1' => ($order->parent_id ? $order->parent_id : $order->order_id),
        ];
        if (is_array($order->products)) {
            $oAmount = $otAmount = 0;
            foreach ($order->products as $product) {
                $taxRate = $product['tax'];
                $cTaxRate = abs(round($taxRate * 100, 0));
                $unitPrice = $this->format_raw(\common\helpers\Tax::add_tax($product['final_price'], $taxRate));
                $totalAmount = ($unitPrice * $product['qty']);
                $totlaTAxAmount = $this->getTaxAmount($totalAmount, $cTaxRate);
                $product = [
                    "reference" => (!empty($product['model']) ? $product['model'] : $product['id']),
                    "name" => $this->convert($product['name']),
                    "quantity" => $product['qty'], //10,
                    "unit_price" => $unitPrice, //600,
                    "total_amount" => $totalAmount, //6000,
                    "tax_rate" => $cTaxRate, //2500,
                    "total_tax_amount" => $totlaTAxAmount, //1200,
                ];
                $oAmount += $totalAmount;
                $otAmount += $totlaTAxAmount;
                $rOrder['order_lines'][] = $product;
            }
        }

        if ($this->manager->isShippingNeeded()) {
            $rOrder['shipping_countries'] = [
                strtolower($this->delivery['country']['countries_iso_code_2'])
            ];
            $_shipping = $this->manager->getSelectedShipping();
            if ($_shipping){
                list($sModule, $sMethod) = explode("_", $_shipping);
                $sPriceI = $this->format_raw($order->info['shipping_cost_inc_tax']);
                $sPriceE = $this->format_raw($order->info['shipping_cost_exc_tax']);
                $taxRate = $sPriceI ? abs(round((($sPriceI / (!$sPriceE ? 1 : $sPriceE)) - 1 ) * 10000)) : 0;
                $totlaTAxAmount = $this->getTaxAmount($sPriceI, $taxRate);

                $shippingOptions = [
                    'reference' => $_shipping,
                    'name' => $this->convert($this->manager->getShippingCollection()->get($sModule)->title??'Shipping'),
                    'unit_price' => $sPriceI,
                    'quantity' => 1,
                    'total_tax_amount' => $totlaTAxAmount,
                    'total_amount' => $sPriceI,
                    'tax_rate' => $taxRate,
                ];

                $oAmount += $sPriceI;
                $otAmount += $totlaTAxAmount;
                $rOrder['selected_shipping_option'] = $shippingOptions;
                $rOrder['order_lines'][] = $shippingOptions;
            }
        }

        if ($rOrder['order_amount'] != $oAmount) {
            $diffTotal = $rOrder['order_amount'] - $oAmount;
            $diffTaxTotal = $rOrder['order_tax_amount'] - $otAmount;
            $diffTT = $diffTotal - $diffTaxTotal;
            $taxRate = abs(round((($diffTotal / (!$diffTT ? 1 : $diffTT)) - 1 ) * 10000));
            $totlaTAxAmount = $this->getTaxAmount($diffTotal, $taxRate);

            $diffEx = $diffTotal - $diffTaxTotal;
            $product = [
                "reference" => 'reference-diff',
                "name" => 'Extra difinition',
                "quantity" => 1,
                "unit_price" => $diffTotal,
                "total_amount" => $diffTotal,
                "tax_rate" => $taxRate,
                "total_tax_amount" => $totlaTAxAmount,
            ];
            $rOrder['order_lines'][] = $product;
        }//echo'<pre>';print_r($rOrder);die;
        //echo '<pre>';print_r($order);die;
        return $rOrder;
    }

    /**
     *
     * @param int $total (25.30 - 2530)
     * @param int $rate (20% - 2000)
     * @return int round result
     */
    function getTaxAmount($total, $rate) {
        return round($total - $total * 10000 / (10000 + $rate), 0);
    }

    function convert($name) {
        return mb_convert_encoding($name, 'UTF-8', mb_detect_encoding($name));
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

    protected function udateSessionResponse(\ArrayObject $response ){
        $data = $response->fetch();
        if($data['session_id']){
            $array = [
                'session_id' => $data['session_id'],
                'client_token' => $data['client_token'],
                'payment_method_categories' => $data['payment_method_categories'],
            ];
            $this->manager->set('klarna_session', $array);
            return true;
        }
        return false;
    }

    protected function clearSession(){
        $session = $this->manager->get('klarna_session');
        unset($session['session_id']);
        unset($session['client_token']);
        unset($session['payment_method_categories']);
        $this->manager->set('klarna_session', $session);
    }

    protected function createKlarnaSession(){
        try{
            $session = new \Klarna\Rest\Payments\Sessions($this->_getConnector());
            $response = $session->create($this->getOrderData());
            if ($response){
                return $this->udateSessionResponse($response);
            }
        } catch( \Exception $ex){
            $this->sendDebugEmail([$ex->getMessage()]);
        }
        return false;
    }

    protected function updateKlarnaSession(){
        try{
            if ($this->manager->has('klarna_session')){
                $session = $this->manager->get('klarna_session');
                if ($session['session_id']){
                    $session = new \Klarna\Rest\Payments\Sessions($this->_getConnector(), $session['session_id']);
                    $response = $session->update($this->getOrderData());
                    if ($response){
                        return $this->udateSessionResponse($response);
                    }
                }
            }
            return $this->createKlarnaSession();
        } catch (\Exception $ex){
            $this->sendDebugEmail([$ex->getMessage()]);
        }
        return false;
    }

    protected function getKlarnaSession(){
        if ($this->manager->has('klarna_session')){
            $session = $this->manager->get('klarna_session');
            if ($session['session_id']){
                $session = new \Klarna\Rest\Payments\Sessions($this->_getConnector(), $session['session_id']);
                $response = $session->fetch($this->getOrderData());
                return $response;
            }
        } else return $this->createKlarnaSession();
    }

    function selection() {
        $selection = array('id' => $this->code,
            'module' => $this->title,
        );
        if (\frontend\design\Info::isTotallyAdmin()){
            return $selection;
        }
        if (is_object($this->manager)) {
            $cart = $this->manager->getCart();
            $orderMinAmount = (float)(defined('MODULE_PAYMENT_KLARNA_CHECKOUT_ORDER_MIN_AMOUNT') ? MODULE_PAYMENT_KLARNA_CHECKOUT_ORDER_MIN_AMOUNT : 0);
            if (!is_object($cart) OR (($orderMinAmount > 0) AND ((float)$cart->total < $orderMinAmount))) {
                return false;
            }
            if (defined('MODULE_PAYMENT_KLARNA_CHECKOUT_ACTIVE_SPECIAL') AND (MODULE_PAYMENT_KLARNA_CHECKOUT_ACTIVE_SPECIAL != 'True')) {
                foreach ($cart->get_products() as $product) {
                    if (($product['special_price'] !== false) AND ($product['standard_price'] > $product['final_price'])) {
                        return false;
                    }
                }
            }
            if (defined('MODULE_PAYMENT_KLARNA_CHECKOUT_ACTIVE_PROMOTION') AND (MODULE_PAYMENT_KLARNA_CHECKOUT_ACTIVE_PROMOTION != 'True')) {
                foreach ($cart->get_products() as $product) {
                    if (($product['special_price'] === false) AND ($product['standard_price'] !== false)
                        AND ($product['standard_price'] > $product['final_price'])
                    ) {
                        return false;
                    }
                }
            }
        }
        if ($this->widget){
            if (!$this->updateKlarnaSession()){
                return false;
            }

            $selection['fields'] = [$this->get_fields()];
        }

        return $selection;
    }

    public function get_fields() {
        $session = $this->manager->get('klarna_session');
        $methods = [];
        if (is_array($session['payment_method_categories'])){
            $methods = \yii\helpers\ArrayHelper::map($session['payment_method_categories'], 'identifier', 'name');
        }
        $content = '<div id="klarna-checkout-container"></div>';
        \Yii::$app->getView()->registerJs($this->getCardDetailsJavascript($session));
        
        $confirmation = array('title' => '', 'field' => \yii\helpers\Html::dropDownList('klarna_selection', '', $methods).$content);

        return $confirmation;
    }

    public function popUpMode() {
        return true;
    }

    public function getCardDetailsJavascript($session = []) {

        if ($this->widget){
            $client_token = $session['client_token'];

            \Yii::$app->getView()->registerJsFile("https://x.klarnacdn.net/kp/lib/v1/api.js");
            $this->registerCallback("loadKlarnaWidget");
            $confirmBtn = defined('TEXT_CONFIRM_AND_PAY') ? TEXT_CONFIRM_AND_PAY : 'Agree';
            $js = <<<EOD
function loadKlarnaWidget(){
    $('#klarna-checkout-container').show();
    var method = $('select[name=klarna_selection]').val();
    if ($('input[name="payment"][value="{$this->code}"]').attr('widget') != 'loaded'){
        klarnaState.reset()
    }
    if (klarnaState.state == 'loadWidget'){
        $.get('{$this->getMerchantUrls()['push']}', {}, function(data){
            $('#klarna-checkout-container').html('');
            Klarna.Payments.load({
                container: '#klarna-checkout-container',
                payment_method_category: method
            }, function (res) { 
                klarnaState.state = 'authorize';
                klarnaState.data = data;
                $('input[name="payment"][value="{$this->code}"]').attr('widget', 'loaded');
            });
        }, 'json');
    } else if(klarnaState.state == 'authorize'){
        Klarna.Payments.authorize({
                payment_method_category: method,
                auto_finalize: true,
            }, klarnaState.data, function(resp){
                if (resp.hasOwnProperty('authorization_token')){
                    if (!$(paymentCollection.form).find('[name=klarna_authorization_token]:hidden').length){
                        $(paymentCollection.form).append('<input type="hidden" name="klarna_authorization_token" value="'+resp.authorization_token+'">');
                    } else {
                        $('[name="klarna_authorization_token"]:hidden').val(resp.authorization_token);
                    }
                    paymentCollection.finishCallback();
                }
        });
    }
}

var klarnaState = new stateK();
function stateK(){
    return { 
        state:'loadWidget', data:{}, 
        reset:function(){ klarnaState.state = 'loadWidget'; klarnaState.data = {}; }
    }
}
$('body').on('change', 'select[name=klarna_selection]', function(){ 
    klarnaState.reset();
    loadKlarnaWidget();
})

klarnaAsyncCallback = function(){
    Klarna.Payments.init({
        client_token: '{$client_token}'
    })
}
klarnaAsyncCallback();
EOD;
        } else {
            $this->registerCallback("popUpIframe");
            $main = \frontend\design\Info::themeFile('/js/main.js');
            $js = <<<EOD
    function popUpIframe(){
        $.getScript('{$main}').then(function(){
            var link = document.createElement('a');
            $(link).attr('href', '#snippet-klarna');
            $(link).popUp({
                'opened':function(){
                    /*$(".pop-up-content:last").html($("#snippet-klarna").get());
                    $(".pop-up-content:last #snippet-klarna").show();*/
                    setTimeout(function(){ $('iframe[name=klarna-checkout-iframe]').attr('scrolling', 'yes'); }, 1000);
                    $('.box.w-checkout-confirm-btn').remove();
                }
            }).trigger('click');
        })
    }

EOD;
        }

        return $js;
    }

    function pre_confirmation_check() {

    }

    function confirmation() {
        $content = $this->title;
        $data = $this->getOrderData();
        if ($this->widget){
            if ($_POST['klarna_authorization_token']){
                $response = $this->confirmKlarnaPaymentOrder($_POST['klarna_authorization_token'], $data);
                if ($response) {

                    if ($response['order_id']){
                        $klarna_session = [
                            'order_id' => $response['order_id'],
                        ];
                        $this->manager->set('klarna_session', $klarna_session);
                    }

                    if ($response['redirect_url'] && $response['fraud_status'] != 'ACCEPTED'){ //may be fraud
                        tep_redirect($response['redirect_url']);
                    }

                    return array('title' => $content);
                }
            }
        } else {
            $session = $this->createKlarnaOrder($data);
            if ($session) {
                if ($session->offsetExists('order_id')) {
                    $klarna_session = [
                        'order_id' => $session->offsetGet('order_id'),
                    ];
                    $this->manager->set('klarna_session', $klarna_session);
                    $snippet = $session->offsetGet('html_snippet');
                    $content = "<div id='snippet-klarna' style='display:none;'>{$snippet}</div>";
                    \Yii::$app->getView()->registerJs($this->getCardDetailsJavascript($session));
                    return array('title' => $content);
                }
            }
        }
        $this->manager->remove('klarna_session');
        tep_redirect($this->getCheckoutUrl(['payment_error' => $this->code, 'error' => 'Please select another payment']));
    }
    
    function process_button() {
        if (!$this->widget){
            return \yii\helpers\Html::hiddenInput('skip', false);
        }
        return false;
    }

    function before_process() {

        $klarna = $this->manager->get('klarna_session');
        if (!$klarna['order_id']) {
            tep_redirect($this->getCheckoutUrl(['payment_error' => $this->code, 'error' => 'Invalid order Id']));
        }

        //capture
        $order = $this->manager->getOrderInstance();
        $amount = $this->format_raw($order->info['total_inc_tax']);
        if (!$this->captureKlarnaOrder($klarna['order_id'], $amount)) {
            tep_redirect($this->getCheckoutUrl(['payment_error' => $this->code, 'error' => 'Invalid total amount']));
        }
    }

    function after_process() {
        $order = $this->manager->getOrderInstance();
        if ($order) {
            $klarna = $this->manager->get('klarna_session');
            $klarnaOrder = $this->getTransactionDetails($klarna['order_id']);
            if ($klarnaOrder) {
                $currencies = \Yii::$container->get('currencies');
                //{{ history
                $transaction_details = [
                    'Transaction Id: ' . $klarna['order_id'],
                    'Transaction Amount: ' . $currencies->format($klarnaOrder->offsetGet('captured_amount')/100, false, $order->info['currency'], $order->info['currency_value']),
                    'Transaction Status: ' . $klarnaOrder->offsetGet('status'),
                    'Fraud Status: ' . $klarnaOrder->offsetGet('fraud_status'),
                ];
                $oModel = $order->getARModel()->select('orders_status')->where(['orders_id' => $order->order_id])->one();
                $sql_data_array = array('orders_id' => $order->order_id,
                    'orders_status_id' => $oModel->orders_status,
                    'date_added' => 'now()',
                    'customer_notified' => '0',
                    'comments' => implode("\n", $transaction_details),
                );
                tep_db_perform($order->table_prefix . TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
                //}} history
                //{{ transactions
                $tManager = $this->manager->getTransactionManager($this);
                $invoice_id = $this->manager->getOrderSplitter()->getInvoiceId();
                $tManager->addTransaction($klarna['order_id'], $klarnaOrder->offsetGet('status'), $klarnaOrder->offsetGet('captured_amount')/100, $invoice_id, 'Customer\'s payment');
                //{{
            }
            $this->manager->remove('klarna_session');
        }
    }

    public function getTransactionDetails($transaction_id, \common\services\PaymentTransactionManager $tManager = null) {
        try {
            $connector = $this->_getConnector();
            $klarnaOrder = new \Klarna\Rest\OrderManagement\Order($connector, $transaction_id);
            $response = $klarnaOrder->fetch();
            return $response;
        } catch (\Exception $ex) {
            $this->sendDebugEmail([$ex->getMessage()]);
        }
        return false;
    }

    public function canRefund($transaction_id){
        $response = $this->getTransactionDetails($transaction_id);
        if ($response){
            if ($response->offsetGet('refunded_amount') < $response->offsetGet('captured_amount')){
                return true;
            }
        }
        return false;
    }

    public function refund($transaction_id, $amount = 0){
        $response = $this->getTransactionDetails($transaction_id);
        if ($response){
            try{
                if (!$amount) {
                    $amount = $response->offsetGet('captured_amount') / 100;
                }
                $connector = $this->_getConnector();
                $klarnaOrder = new \Klarna\Rest\OrderManagement\Order($connector, $transaction_id);
                $klarnaRefund = new \Klarna\Rest\OrderManagement\Refund($connector, $klarnaOrder->getLocation());
                $params = [
                    'refunded_amount' => round($amount * 100, 0),
                ];
                $response = $klarnaRefund->create($params);
                if ($response){
                    $this->manager->getTransactionManager($this)
                        ->addTransactionChild($transaction_id, $response->offsetGet('refund_id'), 'Success', $response->offsetGet('refunded_amount')/100, ($amount? 'Partial Refund':'Full Refund'));
                    return true;
                }
            } catch (\Exception $ex) {
                $this->sendDebugEmail([$ex->getMessage()]);
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

    public function createKlarnaOrder($data) {
        try {
            $connector = $this->_getConnector();
            $klarnaOrder = new \Klarna\Rest\Checkout\Order($connector);
            $response = $klarnaOrder->create($data);
            return $response;
        } catch (\Exception $ex) {
            $this->sendDebugEmail([$ex->getMessage()]);
        }
        return false;
    }

    public function confirmKlarnaPaymentOrder($token, $data) {
        try {
            $connector = $this->_getConnector();
            $klarnaOrder = new \Klarna\Rest\Payments\Orders($connector, $token);
            $response = $klarnaOrder->create($data);
            return $response;
        } catch (\Exception $ex) {
            $this->sendDebugEmail([$ex->getMessage()]);
        }
        return false;
    }

    public function removeKlarnaOrder($klarnaOrderId) {
        try {
            $connector = $this->_getConnector();
            $klarnaOrder = new \Klarna\Rest\OrderManagement\Order($connector, $klarnaOrderId);
            $response = $klarnaOrder->cancel();
            return $response;
        } catch (\Exception $ex) {
            $this->sendDebugEmail([$ex->getMessage()]);
        }
        return false;
    }

    public function clearOldKlarnaOrder(){
        if ($this->manager->has('klarna_session')){
            $klarna_session = $this->manager->get('klarna_session');
            if ($klarna_session['order_id']){
                $this->removeKlarnaOrder($klarna_session['order_id']);
            }
        }
    }

    public function acknowledgeKlarnaOrder($klarnaOrderId) {
        try {
            $connector = $this->_getConnector();
            $klarnaOrder = new \Klarna\Rest\OrderManagement\Order($connector, $klarnaOrderId);
            $response = $klarnaOrder->acknowledge();
            return $response;
        } catch (\Exception $ex) {
            $this->sendDebugEmail([$ex->getMessage()]);
        }
        return false;
    }

    public function captureKlarnaOrder($klarnaOrderId, $amount) {
        try {
            $connector = $this->_getConnector();
            $klarnaOrder = new \Klarna\Rest\OrderManagement\Order($connector, $klarnaOrderId);
            $klarnaCapture = new \Klarna\Rest\OrderManagement\Capture($connector, $klarnaOrder->getLocation());
            $data = [
                'captured_amount' => $amount,
            ];
            $response = $klarnaCapture->create($data);
            return $response;
        } catch (\Exception $ex) {
            $this->sendDebugEmail([$ex->getMessage()]);
        }
        return false;
    }

    function call_webhooks() {
        $klarnaOrderId = Yii::$app->request->get('klarna_order_id');
        $action = Yii::$app->request->get('action');
        switch ($action) {
            case 'confirm':
                $klarna = $this->manager->get('klarna_session');
                if (!$this->widget && strcmp($klarna['order_id'], $klarnaOrderId)) {
                    tep_redirect($this->getCheckoutUrl(['payment_error' => $this->code, 'error' => 'Invalid order Id']));
                }
                //acknowledge
                $this->acknowledgeKlarnaOrder($klarna['order_id']);

                tep_redirect($this->getCheckoutUrl([], self::PROCESS_PAGE));
                break;
            case 'push':
                if ($this->widget){
                    if (!$this->manager->isInstance()){
                        global $cart;
                        $this->manager->loadCart($cart);
                        $order = $this->manager->createOrderInstance('\common\classes\Order');
                        $this->manager->checkoutOrderWithAddresses();
                        $this->manager->totalProcess();
                    }
                    $data = $this->getOrderData();
                    unset($data['merchant_urls']);
                    /*foreach($data as $key=>$value){
                        if (!in_array($key, ['billing_address', 'purchase_country', 'purchase_currency', 'locale', 'customer'])){
                            //unset($data[$key]);
                        }
                    }*/
                    echo json_encode($data);
                }
                break;
        }

    }

    function get_error() {
        $this->clearSession();
        if (isset($_GET['message']) && strlen($_GET['message']) > 0) {
            $error = stripslashes(urldecode($_GET['message']));
        } else {
            $error = $_GET['error'];
        }
        return array('title' => html_entity_decode('Error'),
            'error' => $error);
    }

    function sendDebugEmail($response = array()) {

        if (tep_not_null(MODULE_PAYMENT_KLARNA_DEBUG_EMAIL)) {
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
                \common\helpers\Mail::send('', MODULE_PAYMENT_KLARNA_DEBUG_EMAIL, 'Klarna Debug E-Mail', trim($email_body), STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
            }
        }
    }

    public function describe_status_key() {
        return new ModuleStatus('MODULE_PAYMENT_KLARNA_CHECKOUT_STATUS', 'True', 'False');
    }

    public function describe_sort_key() {
        return new ModuleSortOrder('MODULE_PAYMENT_KLARNA_CHECKOUT_SORT_ORDER');
    }

    function isOnline() {
        return true;
    }

    public function configure_keys() {

        $status_id = defined('MODULE_PAYMENT_KLARNA_CHECKOUT_ORDER_STATUS_ID') ? MODULE_PAYMENT_KLARNA_CHECKOUT_ORDER_STATUS_ID : $this->getDefaultOrderStatusId();
        tep_db_query("CREATE TABLE IF NOT EXISTS `klarna_order_reference` (`klarna_id` varchar(255) NOT NULL, `order_id` int(11) NOT NULL, PRIMARY KEY (`klarna_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

        $params = array('MODULE_PAYMENT_KLARNA_CHECKOUT_STATUS' => array('title' => 'Enable Klarna Module',
                'description' => 'Do you want to accept Klarna payments?',
                'value' => 'True',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '),
            'MODULE_PAYMENT_KLARNA_CHECKOUT_EID' => array('title' => 'Username',
                'description' => 'Username to use for the Klarna service (provided by Klarna)'),
            'MODULE_PAYMENT_KLARNA_CHECKOUT_SECRET' => array('title' => 'Password',
                'description' => 'Password to use with the Klarna service (provided by Klarna).'),
            'MODULE_PAYMENT_KLARNA_CHECKOUT_WIDGET' => array('title' => 'Use js or snippet',
                'description' => 'Use js or snippet.',
                'value' => 'js',
                'set_function' => 'tep_cfg_select_option(array(\'js\', \'snippet\'), '),
            'MODULE_PAYMENT_KLARNA_CHECKOUT_ORDER_STATUS_ID' => array('title' => 'Set Order Status',
                'description' => 'Set the status of orders made with this payment module to this value',
                'value' => $status_id,
                'set_function' => 'tep_cfg_pull_down_order_statuses(',
                'use_function' => '\\common\helpers\\Order::get_order_status_name'),
            'MODULE_PAYMENT_KLARNA_CHECKOUT_TEST_MODE' => array('title' => 'Test Mode',
                'description' => 'Do you want to activate the Testmode?',
                'value' => 'False',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '),
            'MODULE_PAYMENT_KLARNA_DEBUG_EMAIL' => array('title' => 'Debug email',
                'description' => 'Debug email send to',),
            'MODULE_PAYMENT_KLARNA_CHECKOUT_CONDITIONS' => array('title' => 'Conditions',
                'description' => 'URL with condition details',),
            'MODULE_PAYMENT_KLARNA_CHECKOUT_SORT_ORDER' => array('title' => 'Klarna Sort order of display.',
                'description' => 'Sort order of display. Lowest is displayed first.',
                'sort_order' => '0',),
            'MODULE_PAYMENT_KLARNA_CHECKOUT_ORDER_MIN_AMOUNT' => array('title' => 'Minimal order amount',
                'description' => 'Minimal order amount to use this payment (set to 0 to skip this rule).',
                'value' => '0.00',),
            'MODULE_PAYMENT_KLARNA_CHECKOUT_ACTIVE_SPECIAL' => array('title' => 'Active on specials',
                'description' => 'Is payment available if special price is applied at least at one product?',
                'value' => 'True',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '),
            'MODULE_PAYMENT_KLARNA_CHECKOUT_ACTIVE_PROMOTION' => array('title' => 'Active on promotions',
                'description' => 'Is payment available if promotion price is applied at least at one product?',
                'value' => 'True',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '),
        );

        return $params;
    }

}
