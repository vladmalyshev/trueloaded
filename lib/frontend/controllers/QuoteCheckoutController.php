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
 * Quote Checkout controller
 */
class QuoteCheckoutController extends AbstractCheckoutController {

    public function actionIndex() {
        global $wish_list, $breadcrumb;
        global $session_started, $quote;

        $messageStack = \Yii::$container->get('message_stack');
        $currencies = \Yii::$container->get('currencies');

        if (GROUPS_DISABLE_CHECKOUT) {
            tep_redirect(tep_href_link(FILENAME_DEFAULT));
        }

// redirect the customer to a friendly cookie-must-be-enabled page if cookies are disabled (or the session has not started)
        if ($session_started == false) {
            tep_redirect(tep_href_link(FILENAME_COOKIE_USAGE));
        }
        if ($quote->count_contents() < 1) {
            tep_redirect(tep_href_link('quote-cart'));
        }

        $breadcrumb->add(NAVBAR_TITLE_CHECKOUT);

        $this->manager->remove("credit_covers");

        $create_temp_account = false;

        $this->manager->loadCart($quote);

        if (Yii::$app->user->isGuest) {
            //tep_redirect(tep_href_link('quote-checkout/login', '', 'SSL'));
        }

        if (!Yii::$app->user->isGuest) {
            if (!$this->manager->isCustomerAssigned()) {
                $this->manager->assignCustomer(Yii::$app->user->getId());
            }
        }

        $error = false;
        $order = $this->manager->createOrderInstance('\common\extensions\Quotations\Quotation');

        if (Yii::$app->request->isPost) {
            if (isset($_POST['xwidth']) && isset($_POST['xheight'])) {
                $_SESSION['resolution'] = (int) $_POST['xwidth'] . 'x' . (int) $_POST['xheight'];
            }

            if (defined('QUOTE_SKIP_SHIPPING') && QUOTE_SKIP_SHIPPING == 'True') {
              $this->manager->skipShipping = true;
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
                $this->manager->set('quoteID', $quote->cartID);

                tep_redirect(tep_href_link('quote-checkout/confirmation', '', 'SSL'));
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
            'worker' => Yii::$app->getUrlManager()->createUrl('quote-checkout/worker'),
            'message' => $message,
            'payment_javascript_validation' => (!defined('ONE_PAGE_POST_PAYMENT') ? $this->manager->getPaymentJSValidation() : ''),
            'payment_error' => $payment_error,
        ];

        $noShipping = Yii::$app->request->get('no_shipping', 0);
        if (!$this->manager->isShippingNeeded() || (Info::isAdmin() && $noShipping)) {
            $render_data['noShipping'] = true;
            $this->manager->setTemplate('checkout_no_shipping_q');
        } else {
            $render_data['noShipping'] = false;
            $this->manager->setTemplate('checkout_q');
        }

        $page_name = Yii::$app->request->get('page_name');

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
        } elseif (Info::themeSetting('checkout_view') == 1 || $page_name == 'index_2') {
            $tpl = 'index_2.tpl';
        } else {
            $tpl = 'index.tpl';
        }

        $render_data = array_merge($render_data, [
            'params' => $render_data
        ]);

        \frontend\design\Info::addBoxToCss('select-suggest');

        return $this->render($tpl, $render_data);
    }

    public $loginPage = 'quote-checkout/login';
    public $indexPage = 'quote-checkout/';
    public $cartPage = 'quote-cart';

    public function actionLogin() {
        global $quote;
        global $wish_list;

        $this->manager->remove('guest_email_address');

        if ($quote->count_contents() == 0) {
            tep_redirect(tep_href_link($this->cartPage));
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

        global $navigation, $quote;
        $customer_groups_id = (int) \Yii::$app->storage->get('customer_groups_id');

        if (Yii::$app->user->isGuest) {
            $navigation->set_snapshot(array('mode' => 'SSL', 'page' => 'quote-checkout/'));
            tep_redirect(tep_href_link(FILENAME_LOGIN, '', 'SSL'));
        }

        if ($ext = \common\helpers\Acl::checkExtension('BusinessToBusiness', 'checkDisableCheckout')) {
            $ext::checkDisableCheckout($customer_groups_id);
        }
        if (\common\helpers\Customer::check_customer_groups($customer_groups_id, 'groups_disable_checkout')) {
            tep_redirect(tep_href_link('quote-checkout/', '', 'SSL'));
        }
// if there is nothing in the customers cart, redirect them to the shopping cart page
        if ($quote->count_contents() < 1) {
            tep_redirect(tep_href_link('quote-cart/'));
        }

        $this->manager->loadCart($quote);

// avoid hack attempts during the checkout procedure by checking the internal cartID
        //if (isset($quote->cartID) && tep_session_is_registered('quoteID')) {
        if ($quote->cartID != $this->manager->get('quoteID')) {
            tep_redirect(tep_href_link('quote-checkout/', '', 'SSL'));
        }
        //}

        if (defined('QUOTE_SKIP_SHIPPING') && QUOTE_SKIP_SHIPPING == 'True') {
          $this->manager->skipShipping = true;
        }
        
        if ($this->manager->get('shipping_choice') && !$this->manager->has('sendto')) {
            tep_redirect(tep_href_link('quote-checkout/', '', 'SSL'));
        }

        if (!$this->manager->has('billto')) {
            tep_redirect(tep_href_link('quote-checkout/', '', 'SSL'));
        }

// if no shipping method has been selected, redirect the customer to the shipping method selection page
        if ($this->manager->isShippingNeeded() && $this->manager->get('shipping_choice') && !$this->manager->has('shipping')) {
            tep_redirect(tep_href_link('quote-checkout/', 'error_message=' . urlencode(ERROR_NO_SHIPPING_METHOD), 'SSL'));
        }

        if (defined('GERMAN_SITE') && GERMAN_SITE == 'True') {
            if (!$this->manager->has('conditions')) {
                tep_redirect(tep_href_link('quote-checkout/', 'error_message=' . urlencode(ERROR_CONDITIONS_NOT_ACCEPTED), 'SSL', true, false));
            }
        }

        if (tep_not_null($_POST['comments'])) {
            $this->manager->set('comments', tep_db_prepare_input($_POST['comments']));
        }

        $this->manager->setSelectedPaymentModule($this->manager->getPayment());

        // load the selected payment module
        //if ($credit_covers) $payment=''; //ICW added for CREDIT CLASS
        $this->manager->getShippingCollection($this->manager->getShipping());

        $order = $this->manager->createOrderInstance('\common\extensions\Quotations\Quotation');
        $this->manager->checkoutOrderWithAddresses();

        $this->manager->totalCollectPosts();

        $this->manager->totalProcess();
        $this->manager->totalPreConfirmationCheck();

        $paymentCollection = $this->manager->getPaymentCollection();

        if (!$paymentCollection->isPaymentSelected() && !$this->manager->get('credit_covers')) {
            $this->manager->remove('payment');
            tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'error_message=' . urlencode(ERROR_NO_PAYMENT_MODULE_SELECTED), 'SSL'));
        }

        if (!defined('ONE_PAGE_POST_PAYMENT')) {
            $this->manager->paymentPreConfirmationCheck();
        }

        if (!$order->stockAllowCheckout()) {
            // Out of Stock
            tep_redirect(tep_href_link('quote-cart'));
        }

        $form_action_url = tep_href_link('quote-checkout/process', '', 'SSL'); //do not process any billing  //$this->manager->getPaymentUrl() ?? tep_href_link('quote-checkout/process', '', 'SSL');

        $payment_confirmation = $this->manager->getPaymentConfirmation();

        $payment_process_button_hidden = $this->manager->getPaymentButton();


        global $breadcrumb;
        $breadcrumb->add(NAVBAR_TITLE_CHECKOUT);
        $breadcrumb->add(NAVBAR_TITLE);

        $page_name = Yii::$app->request->get('page_name');

        if (Info::themeSetting('checkout_view') == 1 || $page_name == 'confirmation_2') {
            $tpl = 'confirmation_2.tpl';
        } else {
            $tpl = 'confirmation.tpl';
        }
        $deliveryAddress = '';
        if ($this->manager->isDeliveryUsed()) {
            $deliveryAddress = \common\helpers\Address::address_format($order->delivery['format_id'], $order->delivery, 1, ' ', '<br>');
        }
        $deliveryAddress = (!empty($deliveryAddress) ? $deliveryAddress : 'Without shipping addreess');
        $billingAddress = \common\helpers\Address::address_format($order->billing['format_id'], $order->billing, 1, ' ', '<br>');
        $billingAddress = (!empty($billingAddress) ? $billingAddress : 'Without billing addreess');

        $render_data = [
            'shipping_address_link' => tep_href_link('quote-checkout/index#shipping_address'),
            'billing_address_link' => tep_href_link('quote-checkout/index#billing_address'),
            'shipping_method_link' => tep_href_link('quote-checkout/index#shipping_method'),
            'payment_method_link' => tep_href_link('quote-checkout/index#payment_method'),
            'cart_link' => tep_href_link('quote-cart/'),
            'address_label_delivery' => $deliveryAddress,
            'address_label_billing' => $billingAddress,
            'order' => $order,
            'is_shipable_order' => $this->manager->isShippingNeeded(),
            'form_action_url' => $form_action_url,
            'payment_process_button_hidden' => $payment_process_button_hidden,
            'payment_confirmation' => $payment_confirmation,
            'manager' => $this->manager,
        ];

        $noShipping = Yii::$app->request->get('no_shipping', 0);
        if (!$render_data['is_shipable_order'] || (Info::isAdmin() && $noShipping)) {
            $render_data['noShipping'] = true;
        } else {
            $render_data['noShipping'] = false;
        }

        $render_data = array_merge($render_data, [
            'params' => $render_data
        ]);
        return $this->render($tpl, $render_data);
    }

    public function actionSuccess() {

        return $this->_actionSuccess();
    }

    public function actionProcess() {
        global $navigation, $quote;
        // if the customer is not logged on, redirect them to the login page
        if (Yii::$app->user->isGuest) {
            $navigation->set_snapshot(array('mode' => 'SSL', 'page' => 'quote-checkout/'));
            tep_redirect(tep_href_link(FILENAME_LOGIN, '', 'SSL'));
        }

        $this->manager->loadCart($quote);

        if ($this->manager->getShippingChoice() && !$this->manager->get('sendto')) {
            tep_redirect(tep_href_link('quote-checkout/', '', 'SSL'));
        }

        if ((tep_not_null(MODULE_PAYMENT_INSTALLED)) && (!$this->manager->has('payment'))) {
            tep_redirect(tep_href_link('quote-checkout/', '', 'SSL'));
        }

        if (!$this->manager->has('billto')) {
            tep_redirect(tep_href_link('quote-checkout/', '', 'SSL'));
        }

// avoid hack attempts during the checkout procedure by checking the internal cartID
        if (isset($quote->cartID) && $this->manager->has('quoteID')) {
            if ($quote->cartID != (string) $this->manager->get('quoteID')) {
                tep_redirect(tep_href_link('quote-checkout/', '', 'SSL'));
            }
        }
// load selected payment module

        $payment = $this->manager->getPayment();

        if ($this->manager->get('credit_covers')) {
            $payment = ''; //ICW added for CREDIT CLASS
            $this->manager->remove('payment');
        }
        $this->manager->setSelectedPaymentModule($payment);

        $payment_modules = $this->manager->getPaymentCollection();

        if (defined('ONE_PAGE_POST_PAYMENT') && preg_match("/" . preg_quote('quote-checkout/confirmation', "/") . "/", $_SERVER['HTTP_REFERER'])) {
            if (count($payment_modules->getEnabledModules())) {
                $this->manager->paymentPreConfirmationCheck();
            }
        }

// load the selected shipping module
        $this->manager->getShippingCollection($this->manager->getShipping());

        $order = $this->manager->createOrderInstance('\common\extensions\Quotations\Quotation');
        $this->manager->checkoutOrderWithAddresses();
        $order->info['order_status'] = \common\helpers\Quote::getStatus('Active');

        $this->manager->totalProcess();

        $order->save_order();

        $order->save_details();

        $order->save_products();

        $this->manager->clearAfterProcess();

        tep_redirect(tep_href_link('quote-checkout/success', 'order_id=' . $order->order_id, 'SSL'));
    }

    public function actionReorder() {
        global $navigation;
        global $quote;

        if (Yii::$app->user->isGuest) {
            $navigation->set_snapshot(array('mode' => 'SSL', 'page' => 'checkout/reorder', 'get' => 'order_id=' . (int) (isset($_GET['order_id']) ? $_GET['order_id'] : 0)));
            tep_redirect(tep_href_link(FILENAME_LOGIN, '', 'SSL'));
        }

        $messageStack = \Yii::$container->get('message_stack');
        $currencies = \Yii::$container->get('currencies');

        $oID = (int) $_GET['order_id'];

        $get_order_info_r = tep_db_query(
                "SELECT orders_id, shipping_class, payment_class " .
                "FROM " . TABLE_ORDERS . " " .
                "WHERE orders_id='" . (int) $oID . "' AND customers_id='" . (int) Yii::$app->user->getId() . "' "
        );

        if (tep_db_num_rows($get_order_info_r) == 0) {
            tep_redirect(tep_href_link(FILENAME_ACCOUNT, '', 'SSL'));
        }

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
            if (!$quote->is_valid_product_data((int) $get_product['uprid'], $attr)) {
                $messageStack->add(sprintf(ERROR_REORDER_PRODUCT_VARIATION_MISSING_S, $get_product['products_name']), 'shopping_cart', 'warning');
                continue;
            }
            if ($get_product['is_giveaway']) {
                $quote->add_cart((int) $get_product['uprid'], /* $quote->get_quantity(\common\helpers\Inventory::normalize_id(\common\helpers\Inventory::get_uprid((int)$get_product['uprid'], $attr)),1)+ */ $get_product['products_quantity'], $attr, true, 1);
            } else {
                $quote->add_cart((int) $get_product['uprid'], $quote->get_quantity(\common\helpers\Inventory::normalize_id(\common\helpers\Inventory::get_uprid((int) $get_product['uprid'], $attr))) + $get_product['products_quantity'], $attr, false, 0, !!$get_product['gift_wrapped']);
            }
        }

        $quote->setReference($oID);

        $this->manager->set('quoteID', $quote->cartID);

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
            tep_redirect(tep_href_link('quote-cart', '', 'SSL'));
        } elseif (is_array($order_sendto) || is_array($order_billto)) {
            tep_redirect(tep_href_link('quote-checkout/', '', 'SSL'));
        }

        $this->manager->loadCart($quote);
        $payment = $_order_info['payment_class'];
        $this->manager->set('payment', $payment);

        $order = $this->manager->createOrderInstance('\common\extensions\Quotations\Quotation');

        $this->manager->setSelectedShipping($_order_info['shipping_class']);
        if (!$this->manager->getShipping()) {
            tep_redirect(tep_href_link('quote-checkout/', '', 'SSL'));
        }

        tep_redirect(tep_href_link('quote-checkout/confirmation', '', 'SSL'));
    }

    public function __construct($id, $module) {

        \common\helpers\Translation::init('checkout');
        \common\helpers\Translation::init('checkout/login');

        $this->manager = new \common\services\OrderManager(Yii::$app->get('storage'));

        parent::__construct($id, $module);

        parent::checkoutInit();

        $this->manager->setModulesVisibility(['shop_quote']);
        Yii::configure($this->manager, [
            'combineShippings' => ((!defined('SHIPPING_SEPARATELY') || defined('SHIPPING_SEPARATELY') && SHIPPING_SEPARATELY == 'false') ? true : false),
        ]);
    }

    /**
     * Save fast order
     * @return $this
     */
    public function saveFastOrder($customer) {
        global $quote;

        $this->manager->loadCart($quote);
        $this->manager->createOrderInstance('\common\extensions\Quotations\Quotation');
        $this->manager->checkoutOrder();
        $order = $this->manager->getOrderInstance();
        $order->info['order_status'] = \common\helpers\Quote::getStatus('Active');
        if ($customer instanceof \common\components\Customer) {
            $order->customer['customer_id'] = $customer->customers_id;
            $order->customer['email_address'] = $customer->customers_email_address;
            $order->customer['firstname'] = $customer->customers_firstname;
        }

        if (isset($_POST['comments']) && !empty($_POST['comments'])) {
            $order->info['comments'] = tep_db_prepare_input($_POST['comments']);
        }

        // setup payment
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

        $this->manager->totalProcess();
        $order->save_order();
        $order->save_details();
        $order->save_products();

        $this->manager->clearAfterprocess();

        tep_redirect(tep_href_link('quote-checkout/success', 'order_id=' . $order->order_id, 'SSL'));
    }

    public function actionWorker($subaction) {
        global $quote;

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if (!Yii::$app->user->isGuest) {
            if (!$this->manager->isCustomerAssigned()) {
                $this->manager->assignCustomer(Yii::$app->user->getId());
            }
        }

        $this->manager->loadCart($quote);
        $this->manager->createOrderInstance('\common\extensions\Quotations\Quotation');

        if ($this->manager->isShippingNeeded()) {
            $this->manager->setTemplate('checkout_q');
        } else {
            $this->manager->setTemplate('checkout_no_shipping_q');
        }

        return parent::actionWorker($subaction);
    }

}
