<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\invoice;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;

class ShippingAddress extends Widget
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
        $address = \common\helpers\Address::address_format($this->params['order']->delivery['format_id'], $this->params['order']->delivery, 1, '', '<br>');
        [$class, $method] = explode('_', $this->params['order']->info['shipping_class']);
        try {
            $shipping = $this->params['order']->manager->getShippingCollection()->get($class);
            if (is_object($shipping)) {
                $collect = $shipping->toCollect($method);
                if ($collect && method_exists($shipping, 'getAdditionalOrderParams')) {
                    $address = $shipping->getAdditionalOrderParams([], $this->params['order']->order_id, $this->params['order']->table_prefix);
                }
            }
        } catch (\Error $e) {
            restore_error_handler();
        }
        return (!empty($address) ? $address : TEXT_WITHOUT_SHIPPING_ADDRESS);
    }
}
