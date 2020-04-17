<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\controllers;

use common\models\Customers;
use frontend\design\Info;
use Yii;
use yii\web\Session;
use common\classes\opc;
use common\classes\payment;
use common\components\Customer;
use common\components\Socials;

/**
 * Sample Checkout controller
 */
class SampleCheckoutController extends AbstractCheckoutController {

    public function actionIndex() {

        $extSampleFree = (false === \common\helpers\Acl::checkExtension('Samples', 'isFree'));
        if (!$extSampleFree) {
            $extSampleFree = \common\extensions\Samples\Samples::isFree();
        }

        global $breadcrumb;
        global $session_started, $sample;

        $currencies = \Yii::$container->get('currencies');
        $messageStack = \Yii::$container->get('message_stack');

        if (GROUPS_DISABLE_CHECKOUT) {
            tep_redirect(tep_href_link(FILENAME_DEFAULT));
        }

// redirect the customer to a friendly cookie-must-be-enabled page if cookies are disabled (or the session has not started)
        if ($session_started == false) {
            tep_redirect(tep_href_link(FILENAME_COOKIE_USAGE));
        }
        if ($sample->count_contents() < 1) {
            tep_redirect(tep_href_link('sample-cart'));
        }

        $breadcrumb->add(NAVBAR_TITLE_CHECKOUT);

        $this->manager->remove("credit_covers");

        $create_temp_account = false;

        $this->manager->loadCart($sample);

        if (Yii::$app->user->isGuest) {
            //tep_redirect(tep_href_link('sample-checkout/login', '', 'SSL'));
        }

        if (!Yii::$app->user->isGuest) {
            if (!$this->manager->isCustomerAssigned()) {
                $this->manager->assignCustomer(Yii::$app->user->getId());
            }
        }

        $error = false;
        $order = $this->manager->createOrderInstance('\common\extensions\Samples\Sample');

        if (Yii::$app->request->isPost) {
            if (isset($_POST['xwidth']) && isset($_POST['xheight'])) {
                $_SESSION['resolution'] = (int) $_POST['xwidth'] . 'x' . (int) $_POST['xheight'];
            }

            if ($this->manager->isShippingNeeded()) {
                if ($ext = \common\helpers\Acl::checkExtension('DelayedDespatch', 'prepareDeliveryDate')) {
                    $response = $ext::prepareDeliveryDate(false, $this->manager);
                    if ($response) {
                        $error = true;
                    }
                }
            }

            if (tep_not_null($_POST['comments'])) {
                $this->manager->set('comments', tep_db_prepare_input($_POST['comments']));
            }

            if (!$this->manager->validateContactForm(Yii::$app->request->post())) {
                $error = true;
            }
            if (!$this->manager->validateAddressForms(Yii::$app->request->post())) {
                $error = true;
            }

            if (!$error) {
                if (Yii::$app->user->isGuest) {
                    $this->manager->registerCustomerAccount();
                }
            }

            if ($this->manager->isShippingNeeded() && !is_array($this->manager->getShipping()) && count($this->manager->getShippingCollection()->getEnabledModules()) > 0) {
                $messageStack->add(TEXT_CHOOSE_SHIPPING_METHOD, 'one_page_checkout');
                $error = true;
                $this->manager->remove('shipping');
            }

            if (!$error) {
                $this->manager->set('sampleID', $sample->cartID);

                tep_redirect(tep_href_link('sample-checkout/confirmation', '', 'SSL'));
            }
        }

        $this->manager->totalCollectPosts($_POST);

        $payment_error = '';
        if (isset($_GET['payment_error'])) {
            $currentPayment = $this->manager->getPaymentCollection()->get($_GET['payment_error']);
            if (is_object($currentPayment) && method_exists($currentPayment, 'get_error')) {
                $payment_error = $currentPayment->get_error();
                $this->manager->setPayment($_GET['payment_error']);
            }
        }

        $order->prepareOrderInfo();
        $order->prepareOrderInfoTotals();

        $this->manager->totalProcess();

        $this->manager->totalPreConfirmationCheck();

        if (isset($_GET['error_message']) && tep_not_null($_GET['error_message'])) {
            $messageStack->add(tep_db_prepare_input($_GET['error_message']), 'one_page_checkout');
        }

        $message = '';
        if ($messageStack->size('one_page_checkout') > 0) {
            $message = $messageStack->output('one_page_checkout');
        }

        $render_data = [
            'manager' => $this->manager,
            'worker' => Yii::$app->getUrlManager()->createUrl('sample-checkout/worker'),
            'message' => $message,
            'payment_javascript_validation' => (!defined('ONE_PAGE_POST_PAYMENT') ? $this->manager->getPaymentJSValidation() : ''),
            'payment_error' => $payment_error,
        ];

        if (
                Info::themeSetting('checkout_view') == 1 &&
                $error == true && isset($_GET['action']) &&
                $_GET['action'] == 'one_page_checkout'
        ) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            $data = [
                'payment_error' => $payment_error,
                'message' => $message,
                'error_box' => $this->manager->errorForm,
            ];
            return json_encode($data);
        } else {
            $tpl = 'index.tpl';
        }
        $sample_free = Yii::$app->request->get('sample_free');

        $render_data['extSampleFree'] = ($extSampleFree && !Info::isAdmin()) || $sample_free ? true : false;

        $this->manager->setTemplate($render_data['extSampleFree'] ? 'checkout_free_s' : 'checkout_s');

        $no_shipping = Yii::$app->request->get('no_shipping');
        if (Info::isAdmin() && $no_shipping) {
            $render_data['is_shipping'] = false;
        } elseif (Info::isAdmin()) {
            $render_data['is_shipping'] = true;
        }

        $render_data = array_merge($render_data, [
            'params' => $render_data,
        ]);
        return $this->render($tpl, $render_data);
    }

    public $loginPage = 'sample-checkout/login';
    public $indexPage = 'sample-checkout/';
    public $cartPage = 'sample-cart';

    public function actionLogin() {
        global $sample;

        if ($sample->count_contents() == 0) {
            tep_redirect(tep_href_link('sample-cart'));
        }

        return $this->_actionLogin();
    }

    public function actionPayment() {

        return $this->render('payment.tpl', ['products' => '']);
    }

    public function actionPaymentAddress() {

        return $this->render('payment-address.tpl', ['products' => '']);
    }

    public function actionShipping() {

        return $this->render('shipping.tpl', ['products' => '']);
    }

    public function actionShippingAddress() {

        return $this->render('shipping-address.tpl', ['products' => '']);
    }

    public function actionConfirmation() {

        \common\helpers\Translation::init('checkout/confirmation');
        $extSampleFree = (false === \common\helpers\Acl::checkExtension('Samples', 'isFree'));
        if (!$extSampleFree) {
            $extSampleFree = \common\extensions\Samples\Samples::isFree();
        }

        global $navigation, $sample, $sampleID;
        $customer_groups_id = (int) \Yii::$app->storage->get('customer_groups_id');

        if (Yii::$app->user->isGuest) {
            $navigation->set_snapshot(array('mode' => 'SSL', 'page' => 'sample-checkout/'));
            tep_redirect(tep_href_link(FILENAME_LOGIN, '', 'SSL'));
        }

        if ($ext = \common\helpers\Acl::checkExtension('BusinessToBusiness', 'checkDisableCheckout')) {
            $ext::checkDisableCheckout($customer_groups_id);
        }
        if (\common\helpers\Customer::check_customer_groups($customer_groups_id, 'groups_disable_checkout')) {
            tep_redirect(tep_href_link('sample-checkout/', '', 'SSL'));
        }
// if there is nothing in the customers cart, redirect them to the shopping cart page
        if ($sample->count_contents() < 1) {
            tep_redirect(tep_href_link('sample-cart/'));
        }

        $this->manager->loadCart($sample);

// avoid hack attempts during the checkout procedure by checking the internal cartID
        if (isset($sample->cartID) && $this->manager->has('sampleID')) {
            if ($sample->cartID != $this->manager->get('sampleID')) {
                tep_redirect(tep_href_link('sample-checkout/', '', 'SSL'));
            }
        }

        if ($this->manager->isChargedOrder()) {
            if ($this->manager->get('shipping_choice') && !$this->manager->has('sendto')) {
                tep_redirect(tep_href_link('sample-checkout/', '', 'SSL'));
            }

            if (!$this->manager->has('billto')) {
                tep_redirect(tep_href_link('sample-checkout/', '', 'SSL'));
            }

            // if no shipping method has been selected, redirect the customer to the shipping method selection page
            if ($this->manager->isShippingNeeded() && $this->manager->get('shipping_choice') && !$this->manager->has('shipping')) {
                tep_redirect(tep_href_link('sample-checkout/', 'error_message=' . urlencode(ERROR_NO_SHIPPING_METHOD), 'SSL'));
            }
        }

        if (defined('GERMAN_SITE') && GERMAN_SITE == 'True') {
            if (!$this->manager->has('conditions')) {
                tep_redirect(tep_href_link('sample-checkout/', 'error_message=' . urlencode(ERROR_CONDITIONS_NOT_ACCEPTED), 'SSL', true, false));
            }
        }

        if (tep_not_null($_POST['comments'])) {
            $this->manager->set('comments', tep_db_prepare_input($_POST['comments']));
        }


        if ($this->manager->isChargedOrder()) {
            $this->manager->setSelectedPaymentModule($this->manager->getPayment());
            $this->manager->getShippingCollection($this->manager->getShipping());
        }

        $order = $this->manager->createOrderInstance('\common\extensions\Samples\Sample');
        $this->manager->checkoutOrderWithAddresses();

        $this->manager->totalCollectPosts();

        $this->manager->totalProcess();
        $this->manager->totalPreConfirmationCheck();

        if ($this->manager->isChargedOrder()) {
            $paymentCollection = $this->manager->getPaymentCollection();

            if (!$paymentCollection->isPaymentSelected() && !$this->manager->get('credit_covers')) {
                $this->manager->remove('payment');
                tep_redirect(tep_href_link('sample-checkout/', 'error_message=' . urlencode(ERROR_NO_PAYMENT_MODULE_SELECTED), 'SSL'));
            }

            if (!defined('ONE_PAGE_POST_PAYMENT')) {
                $this->manager->paymentPreConfirmationCheck();
            }
        }

// Stock Check
        if (!$order->stockAllowCheckout()) {
            tep_redirect(tep_href_link('sample-cart'));
        }

        if ($this->manager->isChargedOrder()) {
            $form_action_url = $this->manager->getPaymentUrl() ?? tep_href_link('sample-checkout/process', '', 'SSL');
        } else {
            $form_action_url = tep_href_link('sample-checkout/process', '', 'SSL');
        }

        if ($this->manager->isChargedOrder()) {
            $payment_confirmation = $this->manager->getPaymentConfirmation();

            $payment_process_button_hidden = $this->manager->getPaymentButton();
        } else {
            $payment_confirmation = [];
            $payment_process_button_hidden = '';
        }

        global $breadcrumb;
        $breadcrumb->add(NAVBAR_TITLE_CHECKOUT);
        $breadcrumb->add(NAVBAR_TITLE);

        $tpl = 'confirmation.tpl';

        $deliveryAddress = '';
        if ($this->manager->isDeliveryUsed()) {
            $deliveryAddress = \common\helpers\Address::address_format($order->delivery['format_id'], $order->delivery, 1, ' ', '<br>');
        }
        $deliveryAddress = (!empty($deliveryAddress) ? $deliveryAddress : 'Without shipping addreess');
        $billingAddress = \common\helpers\Address::address_format($order->billing['format_id'], $order->billing, 1, ' ', '<br>');
        $billingAddress = (!empty($billingAddress) ? $billingAddress : 'Without billing addreess');

        $render_data = [
            'shipping_address_link' => tep_href_link('sample-checkout/index#shipping_address'),
            'billing_address_link' => tep_href_link('sample-checkout/index#billing_address'),
            'shipping_method_link' => tep_href_link('sample-checkout/index#shipping_method'),
            'payment_method_link' => tep_href_link('sample-checkout/index#payment_method'),
            'cart_link' => tep_href_link('sample-cart/'),
            'address_label_delivery' => $deliveryAddress,
            'address_label_billing' => $billingAddress,
            'order' => $order,
            'is_shipable_order' => $this->manager->isShippingNeeded(),
            'form_action_url' => $form_action_url,
            'payment_process_button_hidden' => $payment_process_button_hidden,
            'payment_confirmation' => $payment_confirmation,
            'manager' => $this->manager,
        ];

        $sample_free = Yii::$app->request->get('sample_free');

        $render_data['extSampleFree'] = ($extSampleFree && !Info::isAdmin()) || $sample_free ? true : false;

        $no_shipping = Yii::$app->request->get('no_shipping');
        if (Info::isAdmin() && $no_shipping) {
            $render_data['is_shipping'] = false;
        } elseif (Info::isAdmin()) {
            $render_data['is_shipping'] = true;
        }

        $render_data = array_merge($render_data, [
            'params' => $render_data,
        ]);

        return $this->render($tpl, $render_data);
    }

    public function actionSuccess() {

        return $this->_actionSuccess();
    }

    public function actionProcess() {
        if ($ext = \common\helpers\Acl::checkExtension('Samples', 'isFree')) {
            $isFree = $ext::isFree();
        } else {
            tep_redirect(tep_href_link('sample-checkout/', '', 'SSL'));
        }
        global $navigation, $sample;
        // if the customer is not logged on, redirect them to the login page
        if (Yii::$app->user->isGuest) {
            $navigation->set_snapshot(array('mode' => 'SSL', 'page' => 'sample-checkout/'));
            tep_redirect(tep_href_link(FILENAME_LOGIN, '', 'SSL'));
        }

        $this->manager->loadCart($sample);

        if ($this->manager->isChargedOrder()) {
            if ($this->manager->getShippingChoice() && !$this->manager->get('sendto')) {
                tep_redirect(tep_href_link('sample-checkout/', '', 'SSL'));
            }

            if ((tep_not_null(MODULE_PAYMENT_INSTALLED)) && (!$this->manager->has('payment'))) {
                tep_redirect(tep_href_link('sample-checkout/', '', 'SSL'));
            }

            if (!$this->manager->has('billto')) {
                tep_redirect(tep_href_link('sample-checkout/', '', 'SSL'));
            }

            if (isset($sample->cartID) && $this->manager->has('sampleID')) {
                if ($sample->cartID != $this->manager->get('sampleID')) {
                    tep_redirect(tep_href_link('sample-checkout/', '', 'SSL'));
                }
            }

            $payment = $this->manager->getPayment();

            if ($this->manager->get('credit_covers')) {
                $payment = ''; //ICW added for CREDIT CLASS
                $this->manager->remove('payment');
            }
            $this->manager->setSelectedPaymentModule($payment);

            $payment_modules = $this->manager->getPaymentCollection();

            if (defined('ONE_PAGE_POST_PAYMENT') && preg_match("/" . preg_quote('sample-checkout/confirmation', "/") . "/", $_SERVER['HTTP_REFERER'])) {
                if (count($payment_modules->getEnabledModules())) {
                    $this->manager->paymentPreConfirmationCheck();
                }
            }

            $this->manager->getShippingCollection($this->manager->getShipping());
        }

        $order = $this->manager->createOrderInstance('\common\extensions\Samples\Sample');
        $this->manager->checkoutOrderWithAddresses();
        $order->info['order_status'] = \common\helpers\Sample::getStatus('Active');

        $this->manager->totalProcess();

        if ($this->manager->isChargedOrder()) {
            $payment_modules->before_process();
            $order->update_piad_information();
        }

        $order->save_order();

        $order->save_details();

        $order->save_products();

        if ($this->manager->isChargedOrder()) {
            $this->manager->getTotalCollection()->apply_credit();
        }

        if ($this->manager->isChargedOrder()) {
            $payment_modules->after_process();
        }

        $this->manager->clearAfterProcess();

        if ($ext = \common\helpers\Acl::checkExtension('ReferFriend', 'rf_after_order_placed')) {
            $ext::rf_after_order_placed($order->order_id);
        }

        tep_redirect(tep_href_link('sample-checkout/success', 'order_id=' . $order->order_id, 'SSL'));
    }

    public function actionReorder() {
        global $navigation;
        global $sample;

        if (Yii::$app->user->isGuest) {
            $navigation->set_snapshot(array('mode' => 'SSL', 'page' => 'checkout/reorder', 'get' => 'order_id=' . (int) (isset($_GET['order_id']) ? $_GET['order_id'] : 0)));
            tep_redirect(tep_href_link(FILENAME_LOGIN, '', 'SSL'));
        }

        $oID = (int) $_GET['order_id'];

        $get_order_info_r = tep_db_query(
                "SELECT orders_id, shipping_class, payment_class " .
                "FROM " . TABLE_ORDERS . " " .
                "WHERE orders_id='" . (int) $oID . "' AND customers_id='" . (int) Yii::$app->user->getId() . "' "
        );

        if (tep_db_num_rows($get_order_info_r) == 0) {
            tep_redirect(tep_href_link(FILENAME_ACCOUNT, '', 'SSL'));
        }

        $messageStack = \Yii::$container->get('message_stack');

        $_order_info = tep_db_fetch_array($get_order_info_r);

        $get_products_r = tep_db_query(
                "SELECT * " .
                "FROM " . TABLE_ORDERS_PRODUCTS . " " .
                "WHERE orders_id='{$_order_info['orders_id']}' " .
                "ORDER BY is_giveaway, orders_products_id"
        );
        while ($get_product = tep_db_fetch_array($get_products_r)) {
            if (!$get_product['is_giveaway'] && !\common\helpers\Product::check_product((int) $get_product['uprid'])) {
                $messageStack->add(sprintf(ERROR_REORDER_PRODUCT_DISABLED_S, $get_product['products_name']), 'shopping_cart', 'warning');
                continue;
            }
            if ($get_product['is_giveaway'] && !\common\helpers\Product::is_giveaway((int) $get_product['uprid'])) {
                $messageStack->add(sprintf(ERROR_REORDER_PRODUCT_DISABLED_S, $get_product['products_name']), 'shopping_cart', 'warning');
                continue;
            }

            $attr = '';
            if (strpos($get_product['uprid'], '{') !== false && preg_match_all('/{(\d+)}(\d+)/', $get_product['uprid'], $attr_parts)) {
                $attr = array();
                foreach ($attr_parts[1] as $_idx => $opt) {
                    $attr[$opt] = $attr_parts[2][$_idx];
                }
            }
            if (!$sample->is_valid_product_data((int) $get_product['uprid'], $attr)) {
                $messageStack->add(sprintf(ERROR_REORDER_PRODUCT_VARIATION_MISSING_S, $get_product['products_name']), 'shopping_cart', 'warning');
                continue;
            }
            if ($get_product['is_giveaway']) {
                $sample->add_cart((int) $get_product['uprid'], /* $sample->get_quantity(\common\helpers\Inventory::normalize_id(\common\helpers\Inventory::get_uprid((int)$get_product['uprid'], $attr)),1)+ */ $get_product['products_quantity'], $attr, true, 1);
            } else {
                $sample->add_cart((int) $get_product['uprid'], $sample->get_quantity(\common\helpers\Inventory::normalize_id(\common\helpers\Inventory::get_uprid((int) $get_product['uprid'], $attr))) + $get_product['products_quantity'], $attr, false, 0, !!$get_product['gift_wrapped']);
            }
        }

        $sample->setReference($oID);

        $this->manager->set('sampleID', $sample->cartID);

        $order_sendto = \common\helpers\Address::find_order_ab($_order_info['orders_id'], 'delivery');
        $order_billto = \common\helpers\Address::find_order_ab($_order_info['orders_id'], 'billing');
        if (is_numeric($order_billto) || is_array($order_billto)) {
            $this->manager->set('billto', $order_billto);
        }
        if (is_numeric($order_sendto) || is_array($order_sendto)) {
            $this->manager->set('sendto', $order_sendto);
        }

        if ($messageStack->size('shopping_cart') > 0) {
            $messageStack->convert_to_session('shopping_cart');
            tep_redirect(tep_href_link('sample-cart', '', 'SSL'));
        } elseif (is_array($order_sendto) || is_array($order_billto)) {
            tep_redirect(tep_href_link('sample-checkout/', '', 'SSL'));
        }

        $this->manager->loadCart($sample);
        if ($this->manager->isChargedOrder()) {
            $payment = $_order_info['payment_class'];
            $this->manager->set('payment', $payment);
        }

        $order = $this->manager->createOrderInstance('\common\extensions\Samples\Sample');

        $this->manager->setSelectedShipping($_order_info['shipping_class']);
        if (!$this->manager->getShipping()) {
            tep_redirect(tep_href_link('sample-checkout/', '', 'SSL'));
        }

        tep_redirect(tep_href_link('sample-checkout/confirmation', '', 'SSL'));
    }

    public function __construct($id, $module) {
        \common\helpers\Translation::init('checkout');
        \common\helpers\Translation::init('checkout/login');
        \common\helpers\Translation::init('main');

        $this->manager = new \common\services\OrderManager(Yii::$app->get('storage'));

        parent::__construct($id, $module);

        parent::checkoutInit();

        $extSampleFree = (false === \common\helpers\Acl::checkExtension('Samples', 'isFree'));
        if (!$extSampleFree) {
            $extSampleFree = \common\extensions\Samples\Samples::isFree();
            $this->manager->setChargeOrder(!$extSampleFree);
        }

        $this->manager->setModulesVisibility(['shop_sample']);
        Yii::configure($this->manager, [
            'combineShippings' => ((!defined('SHIPPING_SEPARATELY') || defined('SHIPPING_SEPARATELY') && SHIPPING_SEPARATELY == 'false') ? true : false),
        ]);
    }

    /**
     * Save fast order
     * @return $this
     */
    public function saveFastOrder($customer) {
        global $sample;

        $this->manager->loadCart($sample);
        $this->manager->createOrderInstance('\common\extensions\Samples\Sample');
        $this->manager->checkoutOrder();

        $order = $this->manager->getOrderInstance();
        $order->info['order_status'] = \common\helpers\Sample::getStatus('Active');

        if ($customer instanceof \common\components\Customer) {
            $order->customer['customer_id'] = $customer->customers_id;
            $order->customer['email_address'] = $customer->customers_email_address;
            $order->customer['firstname'] = $customer->customers_firstname;
        }

        if (isset($_POST['comments']) && !empty($_POST['comments'])) {
            $order->info['comments'] = tep_db_prepare_input($_POST['comments']);
        }
        // setup payment
        if ($this->manager->isChargedOrder()) {
            $payment_modules = $this->manager->setSelectedPaymentModule('offline');
            $aPaymentMethods = $payment_modules->getSelectedPayment();

            if ($aPaymentMethods) {
                $selection = $aPaymentMethods->selection();
                if ($selection) {
                    $order->info['payment_method'] = $selection['methods'][0]['module'] ?? '';
                    $order->info['payment_info'] = '';
                    $order->info['payment_class'] = $selection['methods'][0]['id'] ?? '';
                }
            }
        }

        $this->manager->totalProcess();

        $order->save_order();
        $order->save_details();
        $order->save_products();

        $this->manager->clearAfterprocess();

        if ($ext = \common\helpers\Acl::checkExtension('ReferFriend', 'rf_after_order_placed')) {
            $ext::rf_after_order_placed($order->order_id);
        }

        // show success page
        tep_redirect(tep_href_link('sample-checkout/success', 'order_id=' . $order->order_id, 'SSL'));
    }

    public function actionWorker($subaction) {
        global $sample;

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if (!Yii::$app->user->isGuest) {
            if (!$this->manager->isCustomerAssigned()) {
                $this->manager->assignCustomer(Yii::$app->user->getId());
            }
        }

        $this->manager->loadCart($sample);
        $this->manager->createOrderInstance('\common\extensions\Samples\Sample');

        $extSampleFree = (false === \common\helpers\Acl::checkExtension('Samples', 'isFree'));
        if (!$extSampleFree) {
            $extSampleFree = \common\extensions\Samples\Samples::isFree();
            $this->manager->setTemplate($extSampleFree ? 'checkout_free_s' : 'checkout_s');
        }

        return parent::actionWorker($subaction);
    }

}
