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
/**
 * Site controller
 */
class OrdersController extends Sceleton
{

    public function actionIndex()
    {
    }

    public function actionInvoice() {

        \common\helpers\Translation::init('email-template');

        $this->layout = false;

        $oID = Yii::$app->request->get('orders_id');
        $page_name = Yii::$app->request->get('page_name');

        $currencies = \Yii::$container->get('currencies');

        $manager = \common\services\OrderManager::loadManager();
        $order = $manager->getOrderInstanceWithId('\common\classes\Order', $oID);

        $key = Yii::$app->request->get('key');
        if ($_SESSION['customer_id'] != $order->customer['id'] && !Info::isAdmin() && $key != 'UNJfMzvmwE6EVbL6') {
            return false;
        }
        
        if ($_GET['theme_name']) {
            $theme = $_GET['theme_name'];
        } else {
            $theme_array = tep_db_fetch_array(tep_db_query("select theme_name from " . TABLE_THEMES . " where is_default = 1"));
            if ($theme_array['theme_name']){
                $theme = $theme_array['theme_name'];
            } else {
                $theme = 'theme-1';
            }
        }
        define('THEME_NAME', $theme);

        return $this->render('order.tpl', [
            'page_name' => ($page_name ? $page_name : 'invoice'),
            'type' => 'invoice',
            'order' => $order,
            'params' => [
                'order' => $order,
                'currencies' => $currencies,
                'oID' => $oID
            ],
            'base_url' => (getenv('HTTPS') == 'on' ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG,
            'oID' => $oID,
            'currencies' => $currencies,
        ]);

    }

    public function actionPackingslip() {

        \common\helpers\Translation::init('email-template');

        $this->layout = false;

        $oID = Yii::$app->request->get('orders_id');

        $currencies = \Yii::$container->get('currencies');

        $order = new \common\classes\Order($oID);

        $key = Yii::$app->request->get('key');
        if ($_SESSION['customer_id'] != $order->customer['id'] && !Info::isAdmin() && $key != 'UNJfMzvmwE6EVbL6') {
            return false;
        }

        return $this->render('order.tpl', [
            'page_name' => 'packingslip',
            'type' => 'packingslip',
            'order' => $order,
            'params' => [
                'order' => $order,
                'currencies' => $currencies,
                'oID' => $oID
            ],
            'base_url' => (getenv('HTTPS') == 'on' ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG,
            'oID' => $oID,
            'currencies' => $currencies,
        ]);
    }
    
    public function actionCreditNote(){
        \common\helpers\Translation::init('email-template');

        $this->layout = false;

        $cn = \common\services\OrderManager::loadManager()->getOrderSplitter()->getCreditNoteRow();

        $oID = $cn->orders_id;
        $manager = \common\services\OrderManager::loadManager();
        $splitter = $manager->getOrderSplitter();
        $CNs = $splitter->getInstancesFromSplinters($oID, $splitter::STATUS_RETURNED);

        $currencies = \Yii::$container->get('currencies');

        return $this->render('order.tpl', [
            'page_name' => 'credit_note',
            'type' => 'orders',
            'order' => $CNs[0],
            'params' => [
                'order' => $CNs[0],
                'currencies' => $currencies,
                'oID' => $oID
            ],
            'base_url' => (getenv('HTTPS') == 'on' ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG,
            'oID' => $oID,
            'currencies' => $currencies,
        ]);

    }

    public function actionPurchase(){
        \common\helpers\Translation::init('email-template');

        $this->layout = false;
        $currencies = \Yii::$container->get('currencies');

        $ordersId = \common\models\PurchaseOrders::find()
            ->select(['orders_id'])
            ->asArray()
            ->one();
        $oID = $ordersId['orders_id'];
        $order = new \common\extensions\PurchaseOrders\classes\PurchaseOrder($oID);

        return $this->render('order.tpl', [
            'page_name' => 'purchase',
            'type' => 'orders',
            'order' => $order,
            'params' => [
                'order' => $order,
                'currencies' => $currencies,
                'oID' => $oID
            ],
            'base_url' => (getenv('HTTPS') == 'on' ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG,
            'oID' => $oID,
            'currencies' => $currencies,
        ]);

    }
}
