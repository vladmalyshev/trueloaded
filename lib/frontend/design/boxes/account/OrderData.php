<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\account;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;

class OrderData extends Widget
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
        $order_id = (int)Yii::$app->request->get('order_id');
        $manager = \common\services\OrderManager::loadManager();
        $order = $manager->getOrderInstanceWithId('\common\classes\Order', $order_id);

        $order_delivery_address = '';
        $order_shipping_method = '';
        if ($order->delivery != false) {
            $order_delivery_address = \common\helpers\Address::address_format($order->delivery['format_id'], $order->delivery, 1, ' ', '<br>');
            if (tep_not_null($order->info['shipping_method'])) {
                $order_shipping_method = $order->info['shipping_method'];
            }
        }
        $order_billing = \common\helpers\Address::address_format($order->billing['format_id'], $order->billing, 1, ' ', '<br>');
        $payment_method = $order->info['payment_method'] . \common\helpers\Order::getPurchaseOrderId($order);

        return IncludeTpl::widget(['file' => 'boxes/account/order-data.tpl', 'params' => [
            'settings' => $this->settings,
            'id' => $this->id,
            'order_customer' => $order->customer,
            'order_delivery_address' => $order_delivery_address,
            'order_shipping_method' => $order_shipping_method,
            'order_billing' => $order_billing,
            'payment_method' => $payment_method,
        ]]);
    }
}