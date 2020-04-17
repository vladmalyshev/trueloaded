<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\product;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;
use frontend\design\Info;

class Buttons extends Widget
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
    $params = Yii::$app->request->get();
    
    if (Yii::$app->user->isGuest && \common\helpers\PlatformConfig::getFieldValue('platform_please_login')) {
        return '';
    }

    if ($params['products_id'] && !GROUPS_DISABLE_CHECKOUT) {

      $payment_modules = \common\services\OrderManager::loadManager($cart)->getPaymentCollection();
      $arr = $payment_modules->showPaynowButton();
      if (count($arr)) {
        $externalPayments = implode("\n", $arr);
      }

      $compare_link = '';
      $wishlist_link = '';
      $products = Yii::$container->get('products');
      $product = $products->getProduct($params['products_id']);
      if (!$product->checkAttachedDetails($products::TYPE_STOCK)){
        $product_qty = \common\helpers\Product::get_products_stock($params['products_id']);
        $stock_info = \common\classes\StockIndication::product_info(array(
          'products_id' => $params['products_id'],
          'products_quantity' => $product_qty,
        ));
        $stock_info['quantity_max'] = \common\helpers\Product::filter_product_order_quantity($params['products_id'], $stock_info['max_qty'], true);
        $product->attachDetails([$products::TYPE_STOCK => $stock_info]);
      } else {
          $stock_info = $product[$products::TYPE_STOCK];
      }
      $cart_button = isset(\common\models\Products::findOne($params['products_id'])->cart_button) ? \common\models\Products::findOne($params['products_id'])->cart_button : 1;
      return IncludeTpl::widget(['file' => 'boxes/product/buttons.tpl', 'params' => [
        'compare_link' => $compare_link,
        'wishlist_link' => $wishlist_link,
        'product_qty' => $stock_info['products_quantity'],//\common\helpers\Product::get_products_stock($params['products_id']),
        'product_has_attributes' => \common\helpers\Attributes::has_product_attributes($params['products_id']),
        'stock_info' => $stock_info,
        'product_in_cart' => $cart->in_cart($params['products_id']),//Info::checkProductInCart($params['products_id']),
        'customer_is_logged' => !Yii::$app->user->isGuest,
        'paypal_block' => $externalPayments,
        'cart_button' => $cart_button,
        'settings' => $this->settings[0],
        'products_carousel' => Info::themeSetting('products_carousel'),
        'show_in_cart_button' => Info::themeSetting('show_in_cart_button'),
        'products_id' => (int)$params['products_id'],
      ]]);
    } else {
      return '';
    }
  }
}