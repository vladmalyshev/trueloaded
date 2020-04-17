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

class IpAddress extends Widget
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
        \common\helpers\Translation::init('admin/design');
        $order = $this->params["order"];

        if ($order->parent_id) {
            $order_id = $order->parent_id;
        } elseif ( method_exists($order, 'getOrderNumber') ) {
            $order_id = $order->getOrderNumber();
        } else {
            $order_id = $order->order_id;
        }

        if (!$order_id) {
            return '';
        }

        $system = \common\helpers\System::get_ga_detection($order_id);

        return $system->ip_address;
    }
}