<?php

/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */
namespace common\modules\orderTotal;

use common\classes\modules\ModuleTotal;
use common\classes\modules\ModuleStatus;
use common\classes\modules\ModuleSortOrder;

class ot_custom extends ModuleTotal {

    var $title, $output;

    protected $defaultTranslationArray = [
        'MODULE_ORDER_TOTAL_CUSTOM_TITLE' => 'Custom',
        'MODULE_ORDER_TOTAL_CUSTOM_DESCRIPTION' => 'Custom'
    ];

    function __construct() {
        parent::__construct();

        $this->code = 'ot_custom';
        $this->title = MODULE_ORDER_TOTAL_CUSTOM_TITLE;
        $this->description = MODULE_ORDER_TOTAL_CUSTOM_DESCRIPTION;
        if (!defined('MODULE_ORDER_TOTAL_CUSTOM_STATUS')) {
            $this->enabled = false;
            return false;
        }
        $this->enabled = ((MODULE_ORDER_TOTAL_CUSTOM_STATUS == 'true') ? true : false);
        $this->sort_order = MODULE_ORDER_TOTAL_CUSTOM_SORT_ORDER;

        if (!\frontend\design\Info::isTotallyAdmin())
            $this->enabled = false;

        $this->output = array();
    }

    function process($replacing_value = -1, $visible = false) {
        global $update_totals_custom;
        $currencies = \Yii::$container->get('currencies');
        $order = $this->manager->getOrderInstance();
        $cart = $this->manager->getCart();
        if ($replacing_value != -1) {
            if (is_array($replacing_value)) {
                $replacing_value['in'] = $replacing_value['ex'];
            }
            $cart->setTotalKey($this->code, $replacing_value);
        }

        if (($_t = $cart->getTotalKey('ot_custom')) != false)
            $replacing_value = $_t;

        if ($replacing_value == -1 && !$visible)
            return;

        if (Yii::$app->request->isPost) {
            $cart->setTotalTitle('ot_custom', $update_totals_custom['desc'] . ':' . $update_totals_custom['prefix']);
        }

        if ($cart->getTotalKey('ot_custom') != false /* && is_array($update_totals_custom) && !count($update_totals_custom) */) {
            $_title = $cart->getTotalTitle('ot_custom');
            if (!is_null($_title)) {
                $ex = explode(':', $_title);
                $update_totals_custom['desc'] = $ex[0];
                $update_totals_custom['prefix'] = $ex[1];
            }
        }


        if ($update_totals_custom['prefix'] == 'plus') {

            $order->info['total'] += $replacing_value['in'];
            $order->info['total_inc_tax'] += $replacing_value['in'];
            $order->info['total_exc_tax'] += $replacing_value['ex'];
            parent::$adjusting += $currencies->format_clear($replacing_value['ex'], true, $order->info['currency'], $order->info['currency_value']);
        } else {
            $order->info['total'] -= $replacing_value['in'];
            $order->info['total_inc_tax'] -= $replacing_value['in'];
            $order->info['total_exc_tax'] -= $replacing_value['ex'];
            if ($order->info['total'] < 0)
                $order->info['total'] = 0;
            if ($order->info['total_inc_tax'] < 0)
                $order->info['total_inc_tax'] = 0;
            if ($order->info['total_exc_tax'] < 0)
                $order->info['total_exc_tax'] = 0;
            parent::$adjusting -= $currencies->format_clear($replacing_value['ex'], true, $order->info['currency'], $order->info['currency_value']);
        }



        $this->output[] = array('code' => 'ot_custom',
            'title' => $update_totals_custom['desc'] . ':' . $update_totals_custom['prefix'],
            'text' => $currencies->format($replacing_value['in'], true, $order->info['currency'], $order->info['currency_value']),
            'value' => $replacing_value['in'],
            'sort_order' => 1000,
            'text_exc_tax' => $currencies->format($replacing_value['ex'], true, $order->info['currency'], $order->info['currency_value']),
            'text_inc_tax' => $currencies->format($replacing_value['in'], true, $order->info['currency'], $order->info['currency_value']),
// {{
            'tax_class_id' => 0,
            'value_exc_vat' => $replacing_value['ex'],
            'value_inc_tax' => $replacing_value['in'], // }}
        );

// }}
    }

    public function describe_status_key() {
        return new ModuleStatus('MODULE_ORDER_TOTAL_CUSTOM_STATUS', 'true', 'false');
    }

    public function describe_sort_key() {
        return new ModuleSortOrder('MODULE_ORDER_TOTAL_CUSTOM_SORT_ORDER');
    }

    public function configure_keys() {
        return array(
            'MODULE_ORDER_TOTAL_CUSTOM_STATUS' =>
            array(
                'title' => 'Display Custom',
                'value' => 'true',
                'description' => 'Do you want to display the Custom value?',
                'sort_order' => '1',
                'set_function' => 'tep_cfg_select_option(array(\'true\', \'false\'), ',
            ),
            'MODULE_ORDER_TOTAL_CUSTOM_SORT_ORDER' =>
            array(
                'title' => 'Sort Order',
                'value' => '4',
                'description' => 'Sort order of display.',
                'sort_order' => '2',
            ),
        );
    }

}