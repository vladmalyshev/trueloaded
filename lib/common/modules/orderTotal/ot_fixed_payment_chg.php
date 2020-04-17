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

class ot_fixed_payment_chg extends ModuleTotal {

    var $title, $output;

    protected $defaultTranslationArray = [
        'MODULE_FIXED_PAYMENT_CHG_TITLE' => 'The cost of payment methods',
        'MODULE_FIXED_PAYMENT_CHG_DESCRIPTION' => 'The cost of payment methods'
    ];

    function __construct() {
        parent::__construct();

        $this->code = 'ot_fixed_payment_chg';
        $this->title = MODULE_FIXED_PAYMENT_CHG_TITLE;
        $this->description = MODULE_FIXED_PAYMENT_CHG_DESCRIPTION;
        if (!defined('MODULE_FIXED_PAYMENT_CHG_STATUS')) {
            $this->enabled = false;
            return false;
        }
        $this->enabled = (defined('MODULE_FIXED_PAYMENT_CHG_STATUS') && MODULE_FIXED_PAYMENT_CHG_STATUS == 'true') ? true : false;
        $this->sort_order = MODULE_FIXED_PAYMENT_CHG_SORT_ORDER;
        $this->type = MODULE_FIXED_PAYMENT_CHG_TYPE;
        $this->tax_class = MODULE_FIXED_PAYMENT_CHG_TAX_CLASS;
        $this->output = array();
    }

    function getIncVATTitle() {
        return '';
    }

    function getIncVAT($visibility_id = 0, $checked = false) {
        return '';
    }

    function getExcVATTitle() {
        return '';
    }

    function getExcVAT($visibility_id = 0, $checked = false) {
        return '';
    }

    function process($replacing_value = -1, $visible = false) {
        $currencies = \Yii::$container->get('currencies');
        $od_amount = $this->calculate_credit();
        $order = $this->manager->getOrderInstance();
        if ($replacing_value != -1) {
            $cart = $this->manager->getCart();
            if (is_array($replacing_value)) {
                $od_amount = $replacing_value['ex'];
                $od_amount = $replacing_value['in'];
            } else {
                $replacing_value = [];
                $replacing_value['ex'] = $od_amount;
                $replacing_value['in'] = $od_amount;
            }
            $cart->setTotalKey($this->code, $replacing_value);
        }
        if ($od_amount != 0 || $visible) {
            $this->deduction = $od_amount;

            parent::$adjusting += $currencies->format_clear($od_amount, true, $order->info['currency'], $order->info['currency_value']);

            $this->output[] = array('title' => $this->title . ':',
                'text' => $currencies->format($od_amount, true, $order->info['currency'], $order->info['currency_value']),
                'value' => $od_amount,
                'text_exc_tax' => $currencies->format($od_amount, true, $order->info['currency'], $order->info['currency_value']),
                'text_inc_tax' => $currencies->format($od_amount, true, $order->info['currency'], $order->info['currency_value']),
// {{
                'tax_class_id' => MODULE_FIXED_PAYMENT_CHG_TAX_CLASS,
                'value_exc_vat' => $od_amount,
                'value_inc_tax' => $od_amount,
// }}
            );
            $order->info['total'] = $order->info['total'] + $od_amount;
            $order->info['total_inc_tax'] = $order->info['total_inc_tax'] + $od_amount;
            $order->info['total_exc_tax'] = $order->info['total_exc_tax'] + $od_amount;
            $order->info['msp_fee_inc_tax'] = $od_amount;
        }
    }

    function calculate_credit() {

        $od_amount = 0;
        $table = preg_split("/[:,]/", MODULE_FIXED_PAYMENT_CHG_TYPE);
        $order = $this->manager->getOrderInstance();
        $payment = $this->manager->getPayment();
        for ($i = 0; $i < count($table); $i += 3) {
            if ($payment == $table[$i]) {

                $od_min_fee = $table[$i + 1];
                $od_fee = $table[$i + 2] * $order->info['subtotal'];

                if ($od_min_fee < $od_fee) {
                    $od_am = $od_fee;
                } else {
                    $od_am = $od_min_fee;
                }

                // If tax class is defined, get the tax rate according to delivery country and zone
                // $tod_rate = \common\helpers\Tax::get_tax_rate(MODULE_FIXED_PAYMENT_CHG_TAX_CLASS); // Amended for tax calculation fix
                //$tod_rate = \common\helpers\Tax::get_tax_rate(MODULE_FIXED_PAYMENT_CHG_TAX_CLASS, $order->delivery['country']['id'], $order->delivery['zone_id']); // Added for tax fix
                //$tax_description = \common\helpers\Tax::get_tax_description(MODULE_FIXED_PAYMENT_CHG_TAX_CLASS, $order->delivery['country']['id'], $order->delivery['zone_id']);

                $taxation = $this->getTaxValues(MODULE_FIXED_PAYMENT_CHG_TAX_CLASS, $order);

                $tax_class_id = $taxation['tax_class_id'];
                $tod_rate = $taxation['tax'];
                $tax_description = $taxation['tax_description'];

                if ($od_min_fee < $od_fee) {



                    if (DISPLAY_PRICE_WITH_TAX == "true") {
                        $tod_amount = \common\helpers\Tax::roundTax(\common\helpers\Tax::calculate_tax($od_am / (1 + ($tod_rate / 100)), $tod_rate));
                        $order->info['tax_groups']["$tax_description"] += $tod_amount;
                    } else {
                        $tod_amount = \common\helpers\Tax::roundTax(\common\helpers\Tax::calculate_tax($od_am, $tod_rate));
                        $order->info['tax_groups']["$tax_description"] += $tod_amount;
                        $order->info['total'] += $tod_amount;
                    }


                    $od_amount = $od_am;
                } else {
                    $tod_amount = \common\helpers\Tax::roundTax(\common\helpers\Tax::calculate_tax($od_am, $tod_rate));
                    $order->info['tax_groups']["$tax_description"] += $tod_amount;
                    if (DISPLAY_PRICE_WITH_TAX == "true") {
                        $od_amount = $od_am + $tod_amount;
                    } else {
                        $od_amount = $od_am;
                        $order->info['total'] += $tod_amount;
                    }




                    $order->info['tax'] += $tod_amount;
                }
            }
        }
        return $od_amount;
    }

    function get_payment_cost($pay_type) {
        $order = $this->manager->getOrderInstance();

        $od_amount = 0;
        $table = preg_split("/[:,]/", MODULE_FIXED_PAYMENT_CHG_TYPE);


        for ($i = 0; $i < count($table); $i += 3) {
            if ($pay_type == $table[$i]) {
                $od_min_fee = $table[$i + 1];
                $od_fee = $table[$i + 2] * $order->info['subtotal'];

                if ($od_min_fee < $od_fee) {
                    $od_am = $od_fee;
                } else {
                    $od_am = $od_min_fee;
                }
                if (MODULE_FIXED_PAYMENT_CHG_TAX_CLASS > 0) {
                    $tod_rate = \common\helpers\Tax::get_tax_rate(MODULE_FIXED_PAYMENT_CHG_TAX_CLASS, $order->delivery['country']['id'], $order->delivery['zone_id']);
                    if ($od_min_fee < $od_fee) {
                        if (DISPLAY_PRICE_WITH_TAX == "true") {
                            $tod_amount = \common\helpers\Tax::calculate_tax($od_am / (1 + ($tod_rate / 100)), $tod_rate);
                        } else {
                            $tod_amount = \common\helpers\Tax::calculate_tax($od_am, $tod_rate);
                        }
                        $od_amount = $od_am;
                    } else {
                        $tod_amount = \common\helpers\Tax::calculate_tax($od_am, $tod_rate);
                        if (DISPLAY_PRICE_WITH_TAX == "true") {
                            $od_amount = $od_am + $tod_amount;
                        } else {
                            $od_amount = $od_am;
                        }
                    }
                }
            }
        }
        return $od_amount;
    }

    public function describe_status_key() {
        return new ModuleStatus('MODULE_FIXED_PAYMENT_CHG_STATUS', 'true', 'false');
    }

    public function describe_sort_key() {
        return new ModuleSortOrder('MODULE_FIXED_PAYMENT_CHG_SORT_ORDER');
    }

    public function configure_keys() {
        return array(
            'MODULE_FIXED_PAYMENT_CHG_STATUS' =>
            array(
                'title' => 'Display fee',
                'value' => 'true',
                'description' => 'Display fee related to the payment type',
                'sort_order' => '1',
                'set_function' => 'tep_cfg_select_option(array(\'true\', \'false\'), ',
            ),
            'MODULE_FIXED_PAYMENT_CHG_SORT_ORDER' =>
            array(
                'title' => 'Sort Order',
                'value' => '3',
                'description' => 'Display sort order.',
                'sort_order' => '2',
            ),
            'MODULE_FIXED_PAYMENT_CHG_TYPE' =>
            array(
                'title' => 'Fee for payment type',
                'value' => 'cod:2.70:0.035,paypal_ipn:0:0.03',
                'description' => 'Payment methods with minimal fee (any) and normal fee (0 to 1, 1 is 100%) all splitted by colons, enter like this: [cod:xx:0.yy,paypal_ipn:xx:0.yy] ',
                'sort_order' => '2',
            ),
            'MODULE_FIXED_PAYMENT_CHG_TAX_CLASS' =>
            array(
                'title' => 'Tax',
                'value' => '0',
                'description' => 'Use the following tax class:',
                'sort_order' => '6',
                'use_function' => '\\common\\helpers\\Tax::get_tax_class_title',
                'set_function' => 'tep_cfg_pull_down_tax_classes(',
            ),
        );
    }

}