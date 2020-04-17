<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\extensions\UpdateAndPay;

use Yii;
use yii\base\Widget;
use common\services\OrderManager;
use common\services\SplitterManager;
use common\helpers\Date as DateHelper;
use common\classes\Images;
use frontend\design\Info;

// Order Update and Pay
class UpdateAndPay extends Widget
{

    public static function allowed() {
        return true;
    }
    
    public static function getActions($old_ot_total, $new_ot_total, OrderManager $manager) {
        $currencies = \Yii::$container->get('currencies');
        
        $difference_ot_total = $old_ot_total - $new_ot_total;
        $difference = ($difference_ot_total >= 0 ? true : false);
        $actions = [];
        if (number_format($old_ot_total, 2) > number_format($new_ot_total, 2) && $manager->isCustomerAssigned()) {
          // new is cheaper
            $order = $manager->getOrderInstance();
            $checkRefund = false;
            if (!empty($order->info['payment_class'])){
                $checkRefund = 'to_credit';
            }
            $actions = [
                [
                    'value' => 'to_credit',
                    'name' => TEXT_PAY_DIFFERENCE_ON,
                ],
                [
                    'value' => 'create_rma',
                    'name' => TEXT_CREATE_RMA,
                    //'disabled' => 'disabled',
                    'checked' => 'checked',
                ],
                [
                    'value' => 'just_save',
                    'name' => TEXT_JUST_SAVE,
                ]
            ];
        } elseif (number_format($new_ot_total, 2) > number_format($old_ot_total, 2) && $manager->isCustomerAssigned()) {
          // new is more expensive
            /*$order_total_modules = new \common\classes\order_total(array(
                'ONE_PAGE_CHECKOUT' => 'True',
                'ONE_PAGE_SHOW_TOTALS' => 'false',
            ));*/
            $actions = [];
            $paid = 'checked';
            if ($manager->getTotalCollection()->get('ot_paid')){
            //if (isset($GLOBALS['ot_paid']) && $GLOBALS['ot_paid']->enabled) {
                $actions[] = [
                    'value' => 'send_request',
                    'name' => TEXT_SEND_CUSTOMER_REQUEST,
                    'checked' => $paid,
                ];
                $paid = '';
            }
            $actions[] = [
                'value' => 'from_credit',
                'name' => TEXT_PAY_DIFFERENCE_FROM,
                'checked' => $paid,
            ];
            $actions[] = [
                'value' => 'just_save',
                'name' => TEXT_JUST_SAVE,
            ];
            $onBehalfUrl = '';
            if ( extension_loaded('openssl') ) {
              $actions[] = [
                  'value' => 'on_behalf',
                  'name' => TEXT_PAY_ON_BEHALF,
              ];
              $order = $manager->getOrderInstance();

              $sc = new \yii\base\Security();
              $aup = base64_encode($sc->encryptByKey($order->customer['id']."\t".$order->customer['email_address'], date('\s\me\c\rYkd\ey')));
              $_activePlatformId = $order->info['platform_id'];
              \Yii::$app->get('platform')->config($_activePlatformId);
              if (!empty($order->orders_id)) {
                $orders_id = $order->orders_id;
              } else {
                $cart = $manager->getCart();
                $orders_id = $cart->order_id;
              }
              $onBehalfUrl = tep_catalog_href_link('account/login-me', 'payer=1&aup='.$aup);

            }

        } else {
            $actions = [
                [
                    'value' => 'just_save',
                    'name' => TEXT_JUST_SAVE,
                    'checked' => 'checked',
                ]
            ];
        }
        
        $cart = $manager->getCart();
        $currency_value = $currencies->currencies[$cart->currency]['value'];
        return self::begin()->render('updatepay.tpl', [
                    'new_ot_total' => $currencies->format($new_ot_total, true, $cart->currency, $currency_value),
                    'old_ot_total' => $currencies->format($old_ot_total, true, $cart->currency, $currency_value),
                    'difference_ot_total' => $currencies->format($difference_ot_total, true, $cart->currency, $currency_value),
                    'pay_difference' => $difference_ot_total,
                    'difference' => $difference,
                    'difference_desc' => $difference ? CREDIT_AMOUNT : TEXT_AMOUNT_DUE,
                    'actions' => $actions,
                    'manager' => $manager,
                    'onBehalfUrl' => $onBehalfUrl,
                    'checkRefund' => $checkRefund,
        ]);
    }
    /*backend*/
    public static function checkStatus(OrderManager $manager) {
        $update_and_pay = Yii::$app->request->post('type');
        switch ($update_and_pay) {
            case 'to_credit':
            case 'from_credit':
                if (defined('ORDER_STATUS_FULL_AMOUNT') && (int) ORDER_STATUS_FULL_AMOUNT > 0)
                    $manager->getCart()->setOrderStatus(ORDER_STATUS_FULL_AMOUNT);
                break;
            case 'send_request':
                if (defined('ORDER_STATUS_AFTER_PAYMENT_REQUEST') && (int) ORDER_STATUS_AFTER_PAYMENT_REQUEST > 0) {
                    $manager->getOrderInstance()->info['order_status'] = (int) ORDER_STATUS_AFTER_PAYMENT_REQUEST;
                }
                break;
        }
    }
    
    /*make refund/void in admin area*/
    public static function checkRefund(OrderManager $manager, $difference = 0) {
        $update_and_pay = Yii::$app->request->post('type');
        if ($update_and_pay == 'refund'){
            $order = $manager->getOrderInstance();
            if ($order->order_id && $order->hasTransactions()){
                $tm = $manager->getTransactionManager();
                if ($tm->getTransactionsCount() == 1){
                    $transaction = $tm->getTransactions()[0];
                    $payment = $manager->getPaymentCollection()->get($transaction->payment_class);
                    if ($payment){
                        $tm->usePayment($payment);
                        if ($tm->canPaymentRefund($transaction->transaction_id)){
                            if ($tm->paymentRefund($transaction->transaction_id, $difference)){
                                $manager->getCart()->clearTotalKey('ot_paid');
                                $order->update_piad_information(true);
                            }
                        }
                    }
                }
            }
        } else if ($update_and_pay == 'void') {
            $order = $manager->getOrderInstance();
            if ($order->order_id && $order->hasTransactions()){
                if ($tm->getTransactionsCount() == 1){
                    $transaction = $tm->getTransactions()[0];
                    $payment = $manager->getPaymentCollection()->get($transaction->payment_class);
                    if ($payment){
                        $tm->usePayment($payment);
                        if ($tm->canPaymentVoid($transaction->transaction_id)){
                            if ($tm->paymentVoid($transaction->transaction_id)){
                                $manager->getCart()->clearTotalKey('ot_paid');
                                $order->update_piad_information(true);
                            }
                        }
                    }
                }
                
            }
        }
    }
    /*backend*/
    public static function saveOrder(OrderManager $manager, $type, $difference) {
        //global $customer_id, $cart, $order;
        
        $currencies = \Yii::$container->get('currencies');
        $currency = \Yii::$app->settings->get('currency');
        $order = $manager->getOrderInstance();
        $credit_prefix = '';
        switch ($type) {
            case 'to_credit':
                $credit_prefix = '+';
                $credit_amount = $difference;// * $currencies->get_market_price_rate($currency, DEFAULT_CURRENCY);
                //$order->update_piad_information(true);
                break;
            case 'from_credit':
                $credit_prefix = '-';
                $credit_amount = $difference;// * $currencies->get_market_price_rate($currency, DEFAULT_CURRENCY);
                $credit_amount = $credit_amount * -1;
                //$order->update_piad_information(true);
                break;
            case 'create_rma':
                if ($manager->isCustomerAssigned()){
                    $splitter = $manager->getOrderSplitter();
                    $splitter->prepareSplinterToCN();
                }
                break;
            case 'send_request':
                if ($manager->isCustomerAssigned()){
                    $customer = $manager->getCustomersIdentity();
                    $customer->updateUserToken();
                    $token = $customer->getCustomersInfo()->getToken();
                    $products_ordered = \frontend\design\boxes\email\OrderProducts::widget(['params' => ['products' => $order->products, 'platform_id' => $order->info['platform_id']]]);
                    $url = (new \common\classes\platform_config($order->info['platform_id']))->getCatalogBaseUrl(true) . $order->info['currency'] . '/';
                    $params = [
                        'REQUEST_MESSAGE' => $currencies->format(abs($difference)),
                        'REQUEST_URL' => $url . '?action=payment_request&order_id=' . $order->order_id . '&email_address=' . $order->customer['email_address'] . '&token=' . $token,
                        'STORE_NAME' => \common\classes\platform::name($manager->getPlatformId()),
                    ];
                    $order->notify_customer($products_ordered, $params, 'Request for payment');
                }
                break;
            case 'on_behalf':
            default:
                break;
        }

        if (!empty($credit_prefix)) {
            if ($manager->isCustomerAssigned()){
                $customer = $manager->getCustomersIdentity();
                $customer->saveCreditHistory( null, $credit_amount, $credit_prefix, $currency, $currencies->currencies[$currency]['value'], EMAIL_TEXT_ORDER_NUMBER . ' #' . $order->order_id);
                if ($credit_prefix == '+'){
                    $customer->credit_amount += $credit_amount;
                } else {
                    $customer->credit_amount -= $credit_amount;
                }
                $customer->save(false);
            }
        }
    }
    
    public static function payLink($orderId) {
        $pay_link = false;
        $due_query = tep_db_query("select value from " . TABLE_ORDERS_TOTAL . " where orders_id = '" . (int)$orderId . "' and class='ot_due' limit 1");
        while ($due = tep_db_fetch_array($due_query)) {
            if (number_format($due['value'],2) > 0) {
                $pay_link = tep_href_link('payer/order-pay', 'order_id=' . (int)$orderId, 'SSL');
            }
        }
        return $pay_link;
    }

    private static function preparingOrder($manager, $payOrderId){
        $order_info = array();
        $order_product = array();
        $currencies = Yii::$container->get('currencies');
        $order = $manager->getOrderInstance();
        for ($i = 0, $n = sizeof($order->products); $i < $n; $i++) {
            $order_info['products_image'] = Images::getImageUrl($order->products[$i]['id'], 'Small');
            $order_info['order_product_qty'] = $order->products[$i]['qty'];
            $order_info['order_product_name'] = $order->products[$i]['name'];
            $order_info['product_info_link'] = '';
            $order_info['id'] = $order->products[$i]['id'];
            if (\common\helpers\Product::check_product($order->products[$i]['id'])) {
                $order_info['product_info_link'] = tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . \common\helpers\Inventory::get_prid($order->products[$i]['id']));
            }
            $order_info_attr = array();
            if ((isset($order->products[$i]['attributes'])) && (sizeof($order->products[$i]['attributes']) > 0)) {
                for ($j = 0, $n2 = sizeof($order->products[$i]['attributes']); $j < $n2; $j++) {
                    $order_info_attr[$j]['order_pr_option'] = str_replace(array('&amp;nbsp;', '&lt;b&gt;', '&lt;/b&gt;', '&lt;br&gt;'), array('&nbsp;', '<b>', '</b>', '<br>'), htmlspecialchars($order->products[$i]['attributes'][$j]['option']));
                    $order_info_attr[$j]['order_pr_value'] = ($order->products[$i]['attributes'][$j]['value'] ? htmlspecialchars($order->products[$i]['attributes'][$j]['value']) : '');
                }
            }
            $order_info['attr_array'] = $order_info_attr;
            if (sizeof($order->info['tax_groups']) > 1) {
                $order_info['order_products_tax'] = \common\helpers\Tax::display_tax_value($order->products[$i]['tax']) . '%';
            }
            $order_info['final_price'] = $currencies->format($currencies->calculate_price_in_order($order->info, $order->products[$i]['final_price'], (DISPLAY_PRICE_WITH_TAX == 'true' ? $order->products[$i]['tax'] : 0), $order->products[$i]['qty']), true, $order->info['currency'], $order->info['currency_value']);
            $order_product[] = $order_info;
        }        

        $payment_method = $order->info['payment_class'];
        $payment_error = '';
        if (isset($_GET['payment_error'])){
            $currentPayment = $manager->getPaymentCollection()->get($_GET['payment_error']);
            if (is_object($currentPayment) && method_exists($currentPayment, 'get_error')) {
                $payment_error = $currentPayment->get_error();
                $payment_method = $_GET['payment_error'];
            }
        }
        $payment_modules = $manager->getPaymentCollection();
        $manager->setPayment($payment_method);
        
        $payment_modules->update_status();
        
        $order->update_piad_information();
        
        $order_totals = $manager->wrapTotals($order->totals, 'TEXT_ACCOUNT');
        
        $selection = $manager->getPaymentSelection(false, true);
        
        $pModule = $manager->getPaymentCollection()->get($payment_method);
        if (method_exists($pModule, 'getTitle')){
            $payment_method_title = $pModule->getTitle($payment_method);
        } else {
            $payment_method_title = $pModule->title;
        }
        return [
            'order' => $order,
            'order_id' => $payOrderId,
            'order_product' => $order_product,
            'order_totals' => $order_totals,
            'payment_method' => $payment_method_title,
            'selection' => $selection,
            'checkout_process_link' => ['payer/order-confirmation', 'order_id' => $payOrderId],
            'payment_error' => $payment_error,
            'payment_javascript_validation' => (!defined('ONE_PAGE_POST_PAYMENT') ? $manager->getPaymentJSValidation() : '')
        ];
    }

    public static function orderPay(OrderManager $manager, $payOrderId) {
        $params = self::preparingOrder($manager, $payOrderId);
        $params = array_merge($params, [
            'params' => $params
        ]);
        $html = self::begin()->render('order-pay.tpl', $params);
        
        if ( \Yii::$app->settings->get('from_admin') ) {
          \Yii::$app->controller->view->no_header_footer = true;
        }

        return Yii::$app->controller->renderContent($html);
    }
    
    public static function orderConfirmation(OrderManager $manager, $order_id) {
        
        if (!$manager->isCustomerAssigned()) {
            die();
        }
        
        $splitter = $manager->getOrderSplitter();
        
        $splinters = $splitter->getInstancesFromSplinters($order_id, SplitterManager::STATUS_PENDING);

        if (count($splinters)){
            $manager->replaceOrderInstance(array_shift($splinters));
        } else{
            //{{old compatibility
            $manager->getOrderInstanceWithId('\common\classes\Order', $order_id);
            //}}
        }
        
        $order = $manager->getOrderInstance();
        
        if ($order->customer['customer_id'] != $manager->getCustomerAssigned()) {
            die();
        }
        
        if (isset($_POST['payment'])) {
            $manager->setPayment($_POST['payment']);
            $payment = $_POST['payment'];
        }
        else {
          $payment = $manager->getPayment();
        }

        $paymentCollection = $manager->setSelectedPaymentModule($payment);

        $paymentCollection->update_status();

        if ( !$paymentCollection->isPaymentSelected() && !$manager->get('credit_covers') && !Info::isAdmin()){
            $manager->remove('payment');
            echo ERROR_NO_PAYMENT_MODULE_SELECTED;
            exit();
        }

        if (!defined('ONE_PAGE_POST_PAYMENT')) {
            $manager->paymentPreConfirmationCheck();
        }

        $form_action_url = $manager->getPaymentUrl() ?? tep_href_link('payer/order-process', '', 'SSL');
        
        $payment_confirmation = $manager->getPaymentConfirmation();
        
        $payment_process_button_hidden = $manager->getPaymentButton();
        
        if (defined('SKIP_CHECKOUT') && SKIP_CHECKOUT == 'True') {
            $checkout_post = array_merge($_POST, ['order_id' => $order_id]);
            \Yii::$app->storage->set('checkout_post', $checkout_post);
            tep_redirect(tep_href_link('payer/order-process', 'skip=1', 'SSL'));
        }
        
        $params = array_merge(self::preparingOrder($manager, $order_id), [
            'order_id' => (int) $order_id,
            'payment_process_button_hidden' => $payment_process_button_hidden,
            'form_action_url' => $form_action_url,
            'payment_confirmation' => $payment_confirmation,
        ]);
        $params = array_merge($params, [
            'params' => $params
        ]);
        $html =  self::begin()->render('order-confirmation.tpl', $params);
        
        return Yii::$app->controller->renderContent($html);
        
    }

    public static function getWidgets($type = 'general') {
        \common\helpers\Translation::init('admin/design');
        if ($type == 'payer') {
            return [
                ['name' => 'UpdateAndPay\PayForm', 'title' => TEXT_PAY_FORM, 'description' => '', 'type' => 'payer'],
                ['name' => 'UpdateAndPay\PayConfirm', 'title' => TEXT_PAY_CONFIRM, 'description' => '', 'type' => 'payer'],
                ['name' => 'order\Name', 'title' => TEXT_CUSTOMER_NAME, 'description' => '', 'type' => 'payer'],
                ['name' => 'order\Telephone', 'title' => CUSTOMER_PHONE, 'description' => '', 'type' => 'payer'],
                ['name' => 'order\Email', 'title' => IMAGE_EMAIL, 'description' => '', 'type' => 'payer'],
                ['name' => 'order\DeliveryAddress', 'title' => DELIVERY_ADDRESS, 'description' => '', 'type' => 'payer'],
                ['name' => 'order\ShippingMethod', 'title' => TEXT_CHOOSE_SHIPPING_METHOD, 'description' => '', 'type' => 'payer'],
                ['name' => 'order\BillingAddress', 'title' => TEXT_BILLING_ADDRESS, 'description' => '', 'type' => 'payer'],
                ['name' => 'order\PaymentMethod', 'title' => TEXT_INFO_PAYMENT_METHOD, 'description' => '', 'type' => 'payer'],
                ['name' => 'order\Products', 'title' => TABLE_HEADING_PRODUCTS, 'description' => '', 'type' => 'payer'],
                ['name' => 'account\OrderHeading', 'title' => TEXT_ORDER_HEADING, 'description' => '', 'type' => 'payer'],
                ['name' => 'checkout\ContinueBtn', 'title' => CONTINUE_BUTTON, 'description' => '', 'type' => 'payer'],
            ];
        }
        return [];
    }

    public static function getPages() {
        return [
            [
                'type' => 'payer',
                'group' => 'checkout',
                'action' => 'payer/order-pay',
                'params' => 'order_id=1',
                'name' => 'update-and-pay-order_pay',
                'title' => TEXT_ORDER_PAY,
            ],
            [
                'type' => 'payer',
                'group' => 'checkout',
                'action' => 'payer/order-confirmation',
                'params' => 'order_id=1',
                'name' => 'update-and-pay-order_confirmation',
                'title' => TEXT_ORDER_CONFIRMATION,
            ],
        ];
    }

}
