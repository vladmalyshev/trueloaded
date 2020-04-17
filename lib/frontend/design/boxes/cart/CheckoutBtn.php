<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\cart;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;
use common\classes\payment;

class CheckoutBtn extends Widget
{

  public $file;
  public $params;
  public $settings;

  public function init()
  {
    parent::init();
  }

  public function run()
  {
    global $cart;

    if (!Yii::$app->user->isGuest){
      $checkout_link = tep_href_link('checkout', '', 'SSL');
    } else {
      $checkout_link = tep_href_link('checkout/login', '', 'SSL');
    }
    
    $payment_modules = \common\services\OrderManager::loadManager($cart)->getPaymentCollection($payment);
    $initialize_checkout_methods = $payment_modules->checkout_initialization_method();

    if ($cart->count_contents() > 0) {
      return IncludeTpl::widget(['file' => 'boxes/cart/checkout-btn.tpl', 'params' => ['link' => $checkout_link, 'inline' => $initialize_checkout_methods]]);
    } else {
      return '';
    }
  }
}