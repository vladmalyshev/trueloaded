<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\modules\orderPayment;

use common\classes\modules\ModulePayment;
use common\classes\modules\ModuleStatus;
use common\classes\modules\ModuleSortOrder;

class paypal extends ModulePayment{
    var $code, $title, $description, $enabled;

    protected $defaultTranslationArray = [
        'MODULE_PAYMENT_PAYPAL_TEXT_TITLE' => 'PayPal',
        'MODULE_PAYMENT_PAYPAL_TEXT_DESCRIPTION' => 'PayPal'
    ];

// class constructor
    function __construct() {
        parent::__construct();

        $this->code = 'paypal';
        $this->title = MODULE_PAYMENT_PAYPAL_TEXT_TITLE;
        $this->description = MODULE_PAYMENT_PAYPAL_TEXT_DESCRIPTION;
        if (!defined('MODULE_PAYMENT_PAYPAL_STATUS')) {
            $this->enabled = false;
            return;
        }
        $this->sort_order = MODULE_PAYMENT_PAYPAL_SORT_ORDER;
        $this->enabled = ((MODULE_PAYMENT_PAYPAL_STATUS == 'True') ? true : false);
        $this->online = true;

        if ((int)MODULE_PAYMENT_PAYPAL_ORDER_STATUS_ID > 0) {
          $this->order_status = MODULE_PAYMENT_PAYPAL_ORDER_STATUS_ID;
        }

        $this->update_status();

        $this->form_action_url = 'https://secure.paypal.com/cgi-bin/webscr';
    }

// class methods
    function update_status() {

      if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_PAYPAL_ZONE > 0) ) {
        $check_flag = false;
        $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_PAYPAL_ZONE . "' and zone_country_id = '" . $this->billing['country']['id'] . "' order by zone_id");
        while ($check = tep_db_fetch_array($check_query)) {
          if ($check['zone_id'] < 1) {
            $check_flag = true;
            break;
          } elseif ($check['zone_id'] == $this->billing['zone_id']) {
            $check_flag = true;
            break;
          }
        }

        if ($check_flag == false) {
          $this->enabled = false;
        }
      }
    }

    function selection() {
      return array('id' => $this->code,
                   'module' => $this->title);
    }

    function process_button() {
      $order = $this->manager->getOrderInstance();
      $currencies = \Yii::$container->get('currencies');
      if (MODULE_PAYMENT_PAYPAL_CURRENCY == 'Selected Currency') {
        $my_currency = \Yii::$app->settings->get('currency');
      } else {
        $my_currency = substr(MODULE_PAYMENT_PAYPAL_CURRENCY, 5);
      }
      if (!in_array($my_currency, array('CAD', 'EUR', 'CHF', 'JPY', 'USD'))) {
        $my_currency = 'USD';
      }
      $process_button_string = tep_draw_hidden_field('cmd', '_xclick') .
                               tep_draw_hidden_field('business', MODULE_PAYMENT_PAYPAL_ID) .
                               tep_draw_hidden_field('item_name', STORE_NAME) .
                               tep_draw_hidden_field('amount', number_format(($order->info['total'] - $order->info['shipping_cost']) * $currencies->get_value($my_currency), $currencies->get_decimal_places($my_currency))) .
                               tep_draw_hidden_field('shipping', number_format($order->info['shipping_cost'] * $currencies->get_value($my_currency), $currencies->get_decimal_places($my_currency))) .
                               tep_draw_hidden_field('currency_code', $my_currency) .
                               tep_draw_hidden_field('return', tep_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL')) .
                               tep_draw_hidden_field('cancel_return', tep_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));

      return $process_button_string;
    }


  public function configure_keys()
  {
      $status_id = defined('MODULE_PAYMENT_PAYPAL_ORDER_STATUS_ID') ? MODULE_PAYMENT_PAYPAL_ORDER_STATUS_ID : $this->getDefaultOrderStatusId();
      return array(
      'MODULE_PAYMENT_PAYPAL_STATUS' => array (
        'title' => 'Enable PayPal Module',
        'value' => 'True',
        'description' => 'Do you want to accept PayPal payments?',
        'sort_order' => '3',
        'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
      ),
      'MODULE_PAYMENT_PAYPAL_ID' => array (
        'title' => 'E-Mail Address',
        'value' => 'you@yourbusiness.com',
        'description' => 'The e-mail address to use for the PayPal service',
        'sort_order' => '4',
      ),
      'MODULE_PAYMENT_PAYPAL_CURRENCY' => array(
        'title' => 'Transaction Currency',
        'value' => 'Selected Currency',
        'description' => 'The currency to use for credit card transactions',
        'sort_order' => '6',
        'set_function' => 'tep_cfg_select_option(array(\'Selected Currency\',\'Only USD\',\'Only CAD\',\'Only EUR\',\'Only CHF\',\'Only JPY\'), ',
      ),
      'MODULE_PAYMENT_PAYPAL_ZONE' => array(
        'title' => 'Payment Zone',
        'value' => '0',
        'description' => 'If a zone is selected, only enable this payment method for that zone.',
        'sort_order' => '2',
        'use_function' => '\\common\\helpers\\Zones::get_zone_class_title',
        'set_function' => 'tep_cfg_pull_down_zone_classes(',
      ),
      'MODULE_PAYMENT_PAYPAL_ORDER_STATUS_ID' => array(
        'title' => 'Set Order Status',
        'value' => $status_id,
        'description' => 'Set the status of orders made with this payment module to this value',
        'sort_order' => '0',
        'set_function' => 'tep_cfg_pull_down_order_statuses(',
        'use_function' => '\\common\\helpers\\Order::get_order_status_name',
      ),
      'MODULE_PAYMENT_PAYPAL_SORT_ORDER' => array(
        'title' => 'Sort order of display.',
        'value' => '0',
        'description' => 'Sort order of display. Lowest is displayed first.',
        'sort_order' => '0',
      ),
    );
  }

  public function describe_status_key()
  {
    return new ModuleStatus('MODULE_PAYMENT_PAYPAL_STATUS', 'True', 'False');
  }

  public function describe_sort_key()
  {
    return new ModuleSortOrder('MODULE_PAYMENT_PAYPAL_SORT_ORDER');
  }

  public function isOnline() {
      return true;
  }
}