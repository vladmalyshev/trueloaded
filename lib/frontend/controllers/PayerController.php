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

use Yii;
use frontend\design\Info;
use yii\web\NotFoundHttpException;
use common\components\Customer;
use common\services\SplitterManager;
/**
 * Site controller
 */
class PayerController extends Sceleton
{
    private $manager;
    private $use_splinters;

    public function beforeAction($action)
    {
        if (in_array($action->id, ['process', 'order-process'], true)) {
            $this->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }

    public function __construct($id, $module)
    {
        parent::__construct($id, $module);
        
        $this->manager = \common\services\OrderManager::loadManager();
        $this->manager->setModulesVisibility(['shop_order']);
        \common\helpers\Translation::init('checkout');
        \common\helpers\Translation::init('account/history-info');
        $this->use_splinters = true;
    }
    
    public function actionOrderPay() {
        global $navigation;
        
        if (!$this->manager->isCustomerAssigned() && !Info::isAdmin()){
            tep_redirect(tep_href_link('index/index', '', 'SSL'));
        }
        
        $customer_id = $this->manager->getCustomerAssigned();

        if ( ($this->manager->has('pay_order_id') && !isset($_GET['order_id'])) || (isset($_GET['order_id']) && !is_numeric($_GET['order_id']))){
            $_GET['order_id'] = $this->manager->get('pay_order_id');
        }

        if ((!isset($_GET['order_id']) || (isset($_GET['order_id']) && !is_numeric($_GET['order_id']))) && !Info::isAdmin()) {
            tep_redirect(tep_href_link('index/index', '', 'SSL'));
        }

        $payOrderId = (int)$_GET['order_id'];
                
        $splitter = $this->manager->getOrderSplitter();
        $splinters = $splitter->getInstancesFromSplinters($payOrderId, SplitterManager::STATUS_PENDING);

        if ($this->use_splinters && count($splinters)){
            $this->manager->replaceOrderInstance(array_shift($splinters));
        } else {
            $this->manager->getOrderInstanceWithId('\common\classes\Order', $payOrderId);
        }
        
        $order = $this->manager->getOrderInstance();

        if ($order->customer['customer_id'] != $customer_id) {
          if (!Info::isAdmin()) {
            tep_redirect(tep_href_link('index/index', '', 'SSL'));
          } else {
            $this->manager->set('customer_id', $order->customer['customer_id']);
          }
        }

        /** @var \common\extensions\UpdateAndPay\UpdateAndPay $ext */
        if ($ext = \common\helpers\Acl::checkExtension('UpdateAndPay', 'orderPay')) {
            return $ext::orderPay($this->manager, $payOrderId);
        }
        tep_redirect(tep_href_link('index/index', '', 'SSL'));
    }

    /**
     * either check (ajax) or render confirmation page
     * @return html|json
     */
    public function actionOrderConfirmation() {
      if (\Yii::$app->request->isAjax) {
        $ret = [
          'formCheck' => 'error',
          'payment_error' => '',
          'message' => TEXT_GENERAL_ERROR,
        ];

        if ($this->manager->isCustomerAssigned() || Info::isAdmin() || !empty(\Yii::$app->settings->get('from_admin')) ) {
          $order_id = Yii::$app->request->get('order_id', false);

          if ($order_id && (int)$order_id>0 && !empty($_POST['payment'])) {
            $this->manager->setPayment($_POST['payment']);
            if ($this->manager->getPayment()) {
              //save post to storage
              foreach ($_POST as $key => $value) {
                  if (is_scalar($value)){
                      $this->manager->set('payer_' . $key, $value);
                  }
              }
              $ret = [
                  'payment_error' => '',
                  'formCheck' => 'OK',
              ];

            }
          }
        }
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return \Yii::$app->response->data = $ret;
        
      } else {


        if (!$this->manager->isCustomerAssigned() && !(Info::isAdmin() || !empty(\Yii::$app->settings->get('from_admin')) ) ){
            tep_redirect(tep_href_link('index/index', '', 'SSL'));
        }

        if ($this->manager->getPayment() ) {
          $module = $this->manager->getPaymentCollection($this->manager->getPayment())->getSelectedPayment();

          if ($module->isWithoutConfirmation()) {
            foreach ($this->manager->getAll() as $key => $value) {
              if (is_scalar($value) && strpos($key, 'payer_') === 0){
                  $_POST[str_replace('payer_', '', $key)] = $value;
              }
            }
          }
        }

        if ((!isset($_GET['order_id']) || (isset($_GET['order_id']) && !is_numeric($_GET['order_id']))) && !Info::isAdmin()) {
            tep_redirect(tep_href_link('index/index', '', 'SSL'));
        }

        $order_id = Yii::$app->request->get('order_id');

        if ($order_id){
            $this->manager->set('pay_order_id', $order_id);
            /** @var \common\extensions\UpdateAndPay\UpdateAndPay $ext */
            if ($ext = \common\helpers\Acl::checkExtension('UpdateAndPay', 'orderConfirmation')) {
                return $ext::orderConfirmation($this->manager, $order_id);
            }
        }
      }
    }
    
    public function actionOrderProcess() {
        global $navigation;
/*** admin 
"POST /payer/order-confirmation?order_id=86836 HTTP/1.1" 302 717 "https://drinksdirect.holbidev.co.uk/payer/order-pay?order_id=86836"
"GET /payer/order-process?skip=1 HTTP/1.1" 302 618 "https://drinksdirect.holbidev.co.uk/payer/order-pay?order_id=86836"
"POST /callback/sage-server?check=SERVER&tlSID=0fv8h4isvo6ra59rnlgpc20n20&partlypaid=1 HTTP/1.1" 200 4109 "-" "SagePay-Notifier/1.0"
"GET /payer/order-process?check=PROCESS&key=979f03234b5b18c517e184c1450885a7&tlSID=0fv8h4isvo6ra59rnlgpc20n20&order_id=86836 HTTP/1.1" 200 767 "https://test.sagepay.com/gateway/service/authorisation" "GET /account/logoff
         */
        $skip = (int)\Yii::$app->request->get('skip');
        if (defined('SKIP_CHECKOUT') && SKIP_CHECKOUT == 'True' && $skip == 1) {
            $checkout_post = \Yii::$app->storage->get('checkout_post');
            if (is_array($checkout_post)) {
                foreach ($checkout_post as $key => $value) {
                    $_POST[$key] = $value;
                }
            }
        }
        
        $order_id = (int)$_POST['order_id'];
        if (!$order_id) {
            $order_id = (int)$_GET['order_id'];
        }
        if (!$order_id){
            tep_redirect(tep_href_link('index/index', '', 'SSL'));
        }
        
        $redirectURL = tep_href_link('payer/order-pay', 'order_id=' . $order_id, 'SSL');
        
        if (!$this->manager->isCustomerAssigned()){
            return $this->redirect($redirectURL);
        }
        
        $payment = $this->manager->getPayment();
        $payment_modules = $this->manager->getPaymentCollection($payment);
        $payment_module = $payment_modules->getSelectedPayment();

        if ( (tep_not_null(MODULE_PAYMENT_INSTALLED)) && (!$payment_module) ) {
            tep_redirect(tep_href_link($redirectURL, '', 'SSL'));
        }
        
        $splitter = $this->manager->getOrderSplitter();
        $splinters = $splitter->getInstancesFromSplinters($order_id, SplitterManager::STATUS_PENDING);

        if ($this->use_splinters && count($splinters)){
            $splinter = array_shift($splinters);
            $this->manager->replaceOrderInstance($splinter);
            $splitter->setInvoiceInstance($splinter);
        } else {
            //{{old compatibility
            $order = $this->manager->getOrderInstanceWithId('\common\classes\Order', $order_id);
            $order->info['total_inc_tax'] -= floatval($order->info['total_paid_inc_tax']);
            $order->info['total_exc_tax'] -= floatval($order->info['total_paid_exc_tax']);
            //}}
        }

        //$invoice = $this->manager->getOrderInstance();
        if (defined('ONE_PAGE_POST_PAYMENT') && preg_match("/".preg_quote('payer/order-confirmation', "/")."/",$_SERVER['HTTP_REFERER'])){
            $this->manager->paymentPreConfirmationCheck();
        }
        
        $order_total_modules = $this->manager->getTotalCollection();
        
        \common\models\Orders::updateAll(['payment_class' => $payment_module->code, 'payment_method' => $payment_module->title], ['orders_id' => $order_id]);

        $payment_modules->before_process();
        
        $order = $this->manager->getOrderInstanceWithId('\common\classes\Order', $order_id);

        $order->update_piad_information();

        $order->setPaymentStatus($payment);
        \common\helpers\Order::setStatus($order->order_id, $order->info['order_status']);

        $order->save_totals();

        $payment_modules->after_process();
        
        $this->manager->remove('payment');
        $this->manager->remove('pay_order_id');
        $order_total_modules->clear_posts();//ICW ADDED FOR CREDIT CLASS SYSTEM
        
        //admin basket should be overloaded
        \common\models\AdminShoppingCarts::deleteAll(['cart_type' => 'cart', 'order_id' => $order_id, 'customers_id' => $this->manager->getCustomerAssigned()]);
        //need to refresh admin data in edit order???

        tep_redirect(tep_href_link(FILENAME_CHECKOUT_SUCCESS, 'order_id='. $order_id, 'SSL'));
        
    }
    
}
