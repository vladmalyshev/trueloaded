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

use frontend\design\Info;
use Yii;
use yii\web\Session;
use common\components\Socials;

/**
 * Abstract Checkout controller
 */
abstract class AbstractCheckoutController extends Sceleton {
  
/** @prop \common\services\OrderManager $manager */
    public $manager;
    public $loginPage = 'checkout/login';
    public $indexPage = 'checkout/';
    public $cartPage = 'shopping-cart';
    protected $use_social = false;
    private $initialized = false;

    public function checkoutInit() {
        if (!$this->initialized) {
            $platform_config = new \common\classes\platform_config(PLATFORM_ID);

            $this->use_social = $platform_config->checkNeedSocials();
            if ($this->use_social) {
                \common\components\Socials::loadComponents(PLATFORM_ID);
            }
            $this->initialized = true;
        }
    }

    protected function _actionLogin() {
        if (!Yii::$app->user->isGuest) {
            tep_redirect(tep_href_link($this->indexPage, '', 'SSL'));
        }

        \common\helpers\Translation::init('js');
        \common\helpers\Translation::init('checkout');
        \common\helpers\Translation::init('checkout/login');

        $messageStack = \Yii::$container->get('message_stack');
        global $breadcrumb;
        $breadcrumb->add(NAVBAR_TITLE_CHECKOUT);
        $breadcrumb->add(NAVBAR_TITLE);

        $params = [
            'action' => tep_href_link($this->loginPage, '', 'SSL'),
            'show_socials' => $this->use_social,
        ];

        $authContainer = new \frontend\forms\registration\AuthContainer();
        $params['enterModels'] = $authContainer->getForms('quote/sample');
        $params['showAddress'] = $authContainer->isShowAddress();

        if (Yii::$app->request->isPost) {
            $scenario = Yii::$app->request->post('scenario');
            $response = $authContainer->loadScenario($scenario);
            if (!$authContainer->hasErrors()) {
                if ($scenario == 'fast_order' && method_exists($this, 'saveFastOrder')) {
                    $this->saveFastOrder($response);
                }
                tep_redirect(tep_href_link($this->indexPage, '', 'SSL'));
            } else {
                foreach ($authContainer->getErrors($scenario) as $error) {
                    if (Yii::$app->request->isAjax) {
                        $messageStack->add_session((is_array($error) ? implode("<br>", $error) : $error), $scenario);
                    } else {
                        $messageStack->add((is_array($error) ? implode("<br>", $error) : $error), $scenario);
                    }
                }
                $messages = '';
                if ($messageStack->size($scenario) > 0) {
                    $messages = $messageStack->output($scenario);
                }
                $params['messages_' . $scenario] = $messages;
                $params['active'] = $scenario;
            }
        }

        $page_name = Yii::$app->request->get('page_name');
        if (Info::themeSetting('checkout_view') || $page_name == 'login_2') {
            $tpl = 'login_2.tpl';
        } else {
            $tpl = 'login.tpl';
        }

        return $this->render($tpl, ['params' => $params, 'settings' => ['tabsManually' => true]]);
    }

    protected function _actionSuccess() {
        \common\helpers\Translation::init('checkout/success');
        \common\helpers\Translation::init('checkout');

        global $breadcrumb, $order, $platform_code;

        if (Yii::$app->user->isGuest && !\frontend\design\Info::isAdmin()) {
            tep_redirect(tep_href_link($this->cartPage));
        }

        $customer_id = Yii::$app->user->getId();

        if (tep_session_is_registered('platform_code')) {
            $platform_code = '';
        }

        $breadcrumb->add(NAVBAR_TITLE_CHECKOUT);
        $breadcrumb->add(NAVBAR_TITLE);
        $order_info_data = array(
            'order_id' => 0,
            'print_order_href' => (Info::isAdmin() ? '1111' : ''),
            'order' => false,
        );

        return $this->render('success.tpl', array_merge([
                    'products' => '',
                    'continue_href' => tep_href_link(FILENAME_DEFAULT, '', 'NONSSL'),
                    'params' => $order_info_data
                                ], $order_info_data));
    }

    public function actionNotifyAdmin() {
        $type = Yii::$app->request->post('type', null);
        if (!is_null($type)) {
            if ($type == 'need_analytics') {
                if (class_exists('\common\components\google\ModuleProvider')) {
                    \common\components\google\ModuleProvider::notify();
                }
            }
        }
        exit();
    }

    public function actions() {
        return [
            'auth' => [
                'class' => 'yii\authclient\AuthAction',
                'successCallback' => [$this, 'onAuthSuccess'],
            ],
        ];
    }

    public function beforeAction($action)
    {
        if (in_array($action->id, ['process', 'order-process', 'success'], true)) {
            $this->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }

    public function onAuthSuccess($client) {
        \common\helpers\Translation::init('account/login');
        (new Socials($client))->handle();
    }

    public function actionWorker($subAction) {
        $data = [];
        $messageStack = \Yii::$container->get('message_stack');
        switch ($subAction) {
            case 'shipping_choice':
                $_choice = Yii::$app->request->get('choice');
                $this->manager->remove('estimate_ship');
                $this->manager->setCustomerShippingChoice($_choice);
                $this->manager->checkoutOrder();
                $data = $this->manager->render('ShippingByChoice', ['manager' => $this->manager], 'json');
                break;
            case 'get_address_list':
                $type = Yii::$app->request->get('type');
                $data = $this->manager->render('AddressesList', ['manager' => $this->manager, 'mode' => 'select', 'type' => $type], 'json');
                break;
            case 'set_bill_as_ship':
                $_sendto = $this->manager->get('sendto');
                if ($_sendto) {
                    $this->manager->changeCustomerAddressSelection('billing', $_sendto);
                }
                $this->manager->checkoutOrder();
                $data['address'] = $this->manager->render('AddressesList', ['manager' => $this->manager, 'mode' => 'single', 'type' => 'billing'], 'json');
                $data['payments'] = $this->manager->render('PaymentMethod', ['manager' => $this->manager], 'json');
                $data['order_totals'] = $this->manager->render('Totals', ['manager' => $this->manager], 'json');
                break;
            case 'set_ship_as_bill':
                $_billto = $this->manager->get('billto');
                if ($_billto) {
                    $this->manager->changeCustomerAddressSelection('shipping', $_billto);
                }
                $this->manager->checkoutOrder();
                $data['address'] = $this->manager->render('AddressesList', ['manager' => $this->manager, 'mode' => 'single', 'type' => 'shipping'], 'json');
                $data['payments'] = $this->manager->render('PaymentMethod', ['manager' => $this->manager], 'json');
                $data['shipping'] = $this->manager->render('Shipping', ['manager' => $this->manager], 'json');
                $data['order_totals'] = $this->manager->render('Totals', ['manager' => $this->manager], 'json');
                break;
            case 'change_address_list':
                $type = Yii::$app->request->get('type');
                $value = Yii::$app->request->get('value');
                $this->manager->remove('estimate_ship');
                $this->manager->remove('estimate_bill');
                if ($value) {
                    $this->manager->changeCustomerAddressSelection($type, $value);
                    if ($type == 'shipping') {
                        $this->manager->set('shipping', false);
                    }
                }
                $this->manager->getShippingQuotesByChoice();
                $this->manager->checkoutOrder();
                $data = $this->manager->render('ShippingByChoice', ['manager' => $this->manager], 'json');
                break;
            case 'edit_address':
                $type = Yii::$app->request->get('type');
                $ab_id = Yii::$app->request->get('ab_id', 0);
                $data['address'] = $this->manager->render('AddressesList', ['manager' => $this->manager, 'mode' => 'edit', 'type' => $type, 'ab_id' => $ab_id], 'json');
                break;
            case 'save_address':
                $type = Yii::$app->request->get('type');
                $shipAsBill = Yii::$app->request->post('ship_as_bill', false) && true;
                $shipAsBill = $shipAsBill || (Yii::$app->request->post('bill_as_ship', false) && true);
                $valid = $this->manager->validateAddressForms(Yii::$app->request->post(), '', $shipAsBill);
                $data = [];
                if ($valid) {
                    $this->manager->checkoutOrder();
                    $data = $this->manager->render('ShippingByChoice', ['manager' => $this->manager], 'json');
                } else {
                    if ($messageStack->size('one_page_checkout')) {
                        $data['error'] = $messageStack->output('one_page_checkout');
                    }
                }
                break;
            case 'shipping_changed':
                $shipping = Yii::$app->request->post('shipping');
                if ($shipping) {
                    $this->manager->setSelectedShipping($shipping);
                }
                $this->manager->checkoutOrder();
                $_shipping = $this->manager->getShipping();
                if ($_shipping) {
                    $module = $this->manager->getShippingCollection()->get($_shipping['module']);
                    if (is_object($module) && method_exists($module, 'setAdditionalParams')) {
                        $module->setAdditionalParams(Yii::$app->request->post());
                    } else {
                        $this->manager->remove('shippingparam');
                    }
                }
                $data['order_totals'] = $this->manager->render('Totals', ['manager' => $this->manager], 'json');
                $data['payments'] = $this->manager->render('PaymentMethod', ['manager' => $this->manager], 'json');
                break;
            case 'payment_changed':
                $payment = Yii::$app->request->post('payment');
                if ($payment) {
                    $this->manager->setSelectedPayment($payment);
                }
                $this->manager->checkoutOrder();
                $data['order_totals'] = $this->manager->render('Totals', ['manager' => $this->manager], 'json');
                break;
            case 'credit_class':
                $this->manager->remove('cot_gv');
                $this->manager->remove('cc_id');
                $this->manager->checkoutOrder();
                $data['credit_modules'] = $this->manager->totalCollectPosts($_POST);
                $this->manager->totalPreConfirmationCheck();
                $data['order_totals'] = $this->manager->render('Totals', ['manager' => $this->manager], 'json');
                $data['payments'] = $this->manager->render('PaymentMethod', ['manager' => $this->manager], 'json');
                $this->manager->setRenderPath('\\frontend\\design\\boxes\\cart\\');
                $data['products'] = $this->manager->render('Products', ['params' => ['manager' => $this->manager, 'sender' => 'worker']], 'json');
                break;
            case 'check_vat':
                $modelName = Yii::$app->request->post('checked_model');
                if ($modelName == 'Shipping_address') {
                    $bAddress = $this->manager->getShippingForm();
                    $_which = 'estimate_ship';
                } else {
                    $bAddress = $this->manager->getBillingForm();
                    $_which = 'estimate_bill';
                }
              /** @var common\forms\AddressForm $address */
                $bAddress->preload(Yii::$app->request->post($modelName));
                if ($bAddress->notEmpty(true)) {
                  $this->manager->set($_which, ['country_id' => $bAddress->country, 'postcode' => $bAddress->postcode, 'zone' => $bAddress->state, 'company_vat' => $bAddress->company_vat, 'company_vat_date' => $bAddress->company_vat_date, 'company_vat_status' => 0]);
                }
                $company_vat_status = 0;
                $customer_company_vat_status = '&nbsp;';
                /** @var \common\extensions\VatOnOrder\VatOnOrder $ext */
                if ($ext = \common\helpers\Acl::checkExtension('VatOnOrder', 'update_vat_status')) {
                    list($company_vat_status, $customer_company_vat_status) = $ext::update_vat_status($bAddress);
                }
                $data = ['vat_status' => $customer_company_vat_status, 'field' => \yii\helpers\Html::getInputId($bAddress, 'company_vat')];
                $this->manager->checkoutOrderWithAddresses();
                $data['payments'] = $this->manager->render('PaymentMethod', ['manager' => $this->manager], 'json');
                $data['order_totals'] = $this->manager->render('Totals', ['manager' => $this->manager], 'json');
                break;
            case 'recalculation':
              /** @var \common\forms\AddressForm $sAddress */
                $sAddress = $this->manager->getShippingForm(null, false);
                $sAddress->load(Yii::$app->request->post());
                if ($sAddress->notEmpty(true) && intval($sAddress->country)>0) {
                    $this->manager->set('estimate_ship', ['country_id' => $sAddress->country, 'postcode' => $sAddress->postcode, 'zone' => $sAddress->state, 'company_vat' => $sAddress->company_vat, 'company_vat_date' => $sAddress->company_vat_date, 'company_vat_status' => $sAddress->company_vat_status]);
                }

                /** @var \common\forms\AddressForm $bAddress */
                $bAddress = $this->manager->getBillingForm(null, false);
                $bAddress->load(Yii::$app->request->post());
                if ($bAddress->notEmpty(true)  && intval($bAddress->country)>0) {
                    $this->manager->set('estimate_bill', ['country_id' => $bAddress->country, 'postcode' => $bAddress->postcode, 'zone' => $bAddress->state, 'company_vat' => $bAddress->company_vat, 'company_vat_date' => $bAddress->company_vat_date, 'company_vat_status' => $bAddress->company_vat_status]);
                }
                $this->manager->getShippingQuotesByChoice();

                $this->manager->checkoutOrderWithAddresses();
                if ($sAddress->notEmpty(true)) {
                    $data['shipping'] = $this->manager->render('Shipping', ['manager' => $this->manager], 'json');
                }

                $data['payments'] = $this->manager->render('PaymentMethod', ['manager' => $this->manager], 'json');
                $data['order_totals'] = $this->manager->render('Totals', ['manager' => $this->manager], 'json');
                //$this->manager->remove('estimate_ship');
                //$this->manager->remove('estimate_bill');
                break;
        }
        return Yii::$app->response->data = $data;
    }

}
