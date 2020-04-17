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

use frontend\design\boxes\cart\OrderTotal;
use frontend\design\boxes\cart\ShippingEstimator;
use frontend\design\Info;
use Yii;

/**
 * quote-cart controller
 */
class QuoteCartController extends Sceleton {

    public function actionIndex() {
        global $quote, $breadcrumb;

        $messageStack = \Yii::$container->get('message_stack');
        $currencies = \Yii::$container->get('currencies');

        if (GROUPS_DISABLE_CHECKOUT) {
            tep_redirect(tep_href_link(FILENAME_DEFAULT));
        }

        $breadcrumb->add(NAVBAR_TITLE, tep_href_link('quote-cart'));


        /* if (Yii::$app->request->isPost && isset($_POST['ajax_estimate'])){
          return $this->actionEstimate();
          } */

        $this->manager->loadCart($quote);
        $this->manager->createOrderInstance('\common\extensions\Quotations\Quotation');

        $render_data = array(
            'action' => tep_href_link('quote-cart', 'action=update_quote'),
            'manager' => $this->manager,
            'cart_link' => tep_href_link('quote-cart'),
        );

        $render_data = array_merge($render_data, $this->manager->prepareEstimateData());

        $message_discount_coupon = '';
        if ($messageStack->size('cart_discount_coupon') > 0) {
            $message_discount_coupon = $messageStack->output('cart_discount_coupon');
        }
        $message_discount_gv = '';
        if ($messageStack->size('cart_discount_gv') > 0) {
            $message_discount_gv = $messageStack->output('cart_discount_gv');
        }
        $ot_gv_data = array(
            'can_apply_gv_credit' => false,
            'message_discount_gv' => $message_discount_gv,
            'credit_amount' => '',
            'credit_gv_in_use' => $this->manager->has('cot_gv'),
            'message_discount_coupon' => $message_discount_coupon,
            'message_shopping_cart' => ( $messageStack->size('shopping_cart') > 0 ? $messageStack->output('shopping_cart') : '' ),
        );
        
        $this->manager->remove('shipping_choice');

        $render_data = array_merge($render_data, $ot_gv_data);

        if (Yii::$app->request->isAjax && $_GET['popup'] && Info::themeSetting('after_add') == 'popup') {
            return $this->render('popup.tpl', $render_data);
        } else {
            return $this->render('index.tpl', $render_data);
        }
    }

    public function actionEstimate() {
        $this->layout = false;
        global $quote;
        //but not used
        $this->manager->loadCart($quote);

        return json_encode(array('estimate' => ShippingEstimator::widget(['params' => ['manager' => $this->manager]]), 'total' => OrderTotal::widget(['params' => ['manager' => $this->manager]])));
    }

    public $manager;

    public function __construct($id, $module, $config = []) {
        parent::__construct($id, $module, $config);
        $this->manager = new \common\services\OrderManager(Yii::$app->get('storage'));
        $this->manager->setModulesVisibility(['shop_quote']);
        Yii::configure($this->manager, [
            'combineShippings' => true,
        ]);
    }

}
