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

use Yii;
use common\classes\modules\ModulePayment;
use common\classes\modules\ModuleStatus;
use common\classes\modules\ModuleSortOrder;


class secpay_out extends ModulePayment {

    var $code, $title, $description, $enabled;

    /** @var array */
    protected $defaultTranslationArray = [
        'MODULE_PAYMENT_SECPAY_OUT_TEXT_TITLE' => 'SECPay',
        'MODULE_PAYMENT_SECPAY_OUT_TEXT_PUBLIC_TITLE' => 'SecPay',
        'MODULE_PAYMENT_SECPAY_OUT_TEXT_DESCRIPTION' => 'SecPay (old version)',
        'MODULE_PAYMENT_SECPAY_OUT_SORT_ORDER' => '120',
    ];
// class constructor
    function __construct() {
        parent::__construct();
        $this->code = 'secpay_out';
        $this->title = MODULE_PAYMENT_SECPAY_OUT_TEXT_TITLE;
        $this->description = MODULE_PAYMENT_SECPAY_OUT_TEXT_DESCRIPTION;
        $this->sort_order = MODULE_PAYMENT_SECPAY_OUT_SORT_ORDER;
        $this->enabled = ((defined('MODULE_PAYMENT_SECPAY_OUT_STATUS') && MODULE_PAYMENT_SECPAY_OUT_STATUS == 'True') ? true : false);

        if (defined('MODULE_PAYMENT_SECPAY_OUT_ORDER_STATUS_ID') && (int) MODULE_PAYMENT_SECPAY_OUT_ORDER_STATUS_ID > 0) {
            $this->order_status = MODULE_PAYMENT_SECPAY_OUT_ORDER_STATUS_ID;
        }

        $this->update_status();

        $this->form_action_url = 'https://www.secpay.com/java-bin/ValCard';
    }

// class methods
    function update_status() {

        if (($this->enabled == true) && ( defined('MODULE_PAYMENT_SECPAY_OUT_ZONE') &&  (int) MODULE_PAYMENT_SECPAY_OUT_ZONE > 0)) {
            $check_flag = false;
            $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_SECPAY_OUT_ZONE . "' and zone_country_id = '" . $this->billing['country']['id'] . "' order by zone_id");
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

    function javascript_validation() {
        return false;
    }

    function selection() {
        return array('id' => $this->code,
            'module' => $this->title);
    }

    function pre_confirmation_check() {
        return false;
    }

    function confirmation() {
        return false;
    }

    function process_button() {
        $order = $this->manager->getOrderInstance();
        $currencies = Yii::$container->get('currencies');
        $currency = Yii::$app->settings->get('currency');

        switch (MODULE_PAYMENT_SECPAY_OUT_CURRENCY) {
            case 'Default Currency':
                $sec_currency = DEFAULT_CURRENCY;
                break;
            case 'Any Currency':
            default:
                $sec_currency = $currency;
                break;
        }

        switch (MODULE_PAYMENT_SECPAY_OUT_TEST_STATUS) {
            case 'Always Fail':
                $test_status = 'false';
                break;
            case 'Production':
                $test_status = 'live';
                break;
            case 'Always Successful':
            default:
                $test_status = 'true';
                break;
        }

        $process_button_string = tep_draw_hidden_field('merchant', MODULE_PAYMENT_SECPAY_OUT_MERCHANT_ID) .
                tep_draw_hidden_field('trans_id', STORE_NAME . date('Ymdhis')) .
                tep_draw_hidden_field('amount', number_format($order->info['total'] * $currencies->get_value($sec_currency), $currencies->currencies[$sec_currency]['decimal_places'], '.', '')) .
                tep_draw_hidden_field('bill_name', $order->billing['firstname'] . ' ' . $order->billing['lastname']) .
                tep_draw_hidden_field('bill_addr_1', $order->billing['street_address']) .
                tep_draw_hidden_field('bill_addr_2', $order->billing['suburb']) .
                tep_draw_hidden_field('bill_city', $order->billing['city']) .
                tep_draw_hidden_field('bill_state', $order->billing['state']) .
                tep_draw_hidden_field('bill_post_code', $order->billing['postcode']) .
                tep_draw_hidden_field('bill_country', $order->billing['country']['title']) .
                tep_draw_hidden_field('bill_tel', $order->customer['telephone']) .
                tep_draw_hidden_field('bill_email', $order->customer['email_address']) .
                tep_draw_hidden_field('ship_name', $order->delivery['firstname'] . ' ' . $order->delivery['lastname']) .
                tep_draw_hidden_field('ship_addr_1', $order->delivery['street_address']) .
                tep_draw_hidden_field('ship_addr_2', $order->delivery['suburb']) .
                tep_draw_hidden_field('ship_city', $order->delivery['city']) .
                tep_draw_hidden_field('ship_state', $order->delivery['state']) .
                tep_draw_hidden_field('ship_post_code', $order->delivery['postcode']) .
                tep_draw_hidden_field('ship_country', $order->delivery['country']['title']) .
                tep_draw_hidden_field('currency', $sec_currency) .
                tep_draw_hidden_field('callback', tep_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL', false) . ';' . tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code, 'SSL', false)) .
                tep_draw_hidden_field(tep_session_name(), tep_session_id()) .
                //tep_draw_hidden_field('options', 'test_status=' . $test_status . ',dups=false,cb_post=true,cb_flds=' . tep_session_name());
                tep_draw_hidden_field('options', 'test_status=' . $test_status . ',cb_post=false,cb_flds=' . tep_session_name());//template=https://www.secpay.com/users/peterh02/newpaymentpage.html, -phdesign page

        return $process_button_string;
    }

    function before_process() {

        if ($_POST['valid'] == 'true') {
            if ($remote_host = getenv('REMOTE_HOST')) {
                if ($remote_host != 'secpay.com') {
                    $remote_host = gethostbyaddr($remote_host);
                }
                if ($remote_host != 'secpay.com') {
                    tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, tep_session_name() . '=' . $_POST[tep_session_name()] . '&payment_error=' . $this->code, 'SSL', false, false));
                }
            } else {
                tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, tep_session_name() . '=' . $_POST[tep_session_name()] . '&payment_error=' . $this->code, 'SSL', false, false));
            }
        }
    }

    function after_process() {
        return false;
    }

    function get_error() {

        if (isset($_GET['message']) && (strlen($_GET['message']) > 0)) {
            $error = stripslashes(urldecode($_GET['message']));
        } else {
            $error = MODULE_PAYMENT_SECPAY_OUT_TEXT_ERROR_MESSAGE;
        }

        return array('title' => MODULE_PAYMENT_SECPAY_OUT_TEXT_ERROR,
            'error' => $error);
    }
    
    public function configure_keys(): array
    {
        $status_id = defined('MODULE_PAYMENT_SECPAY_ORDER_STATUS_ID') ? MODULE_PAYMENT_SECPAY_ORDER_STATUS_ID : $this->getDefaultOrderStatusId();

        $params = ['MODULE_PAYMENT_SECPAY_OUT_STATUS' => ['title' => 'Enable SecPay Module (Old)',
            'desc' => 'Do you want to use SecPay Payment (Old)?',
            'value' => 'True',
            'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '],
            'MODULE_PAYMENT_SECPAY_OUT_MERCHANT_ID' => ['title' => 'SECpay Merchant ID',
                'value' => 'secpay',
                'desc' => 'Merchant ID to use for the SECPay service'],
            'MODULE_PAYMENT_SECPAY_OUT_CURRENCY' => ['title' => 'SECpay Transaction Currency',
                'value' => 'Any Currency',
                'desc' => 'The currency to use for credit card transactions',
                'set_function' => 'tep_cfg_select_option(array(\'Any Currency\', \'Default Currency\'), '],
            'MODULE_PAYMENT_SECPAY_OUT_TEST_STATUS' => ['title' => 'SECpay Transaction Mode',
                'value' => 'Always Successful',
                'set_function' => 'tep_cfg_select_option(array(\'Always Successful\', \'Always Fail\', \'Production\'), ',
                'desc' => 'Transaction mode to use for the SECPay service'],
            'MODULE_PAYMENT_SECPAY_OUT_ORDER_STATUS_ID' => ['title' => 'Set Order Status',
                'desc' => 'Set the status of prepared orders made with this payment module to this value',
                'value' => $status_id,
                'use_function' => '\common\helpers\Order::get_order_status_name',
                'set_function' => 'tep_cfg_pull_down_order_statuses('],
            'MODULE_PAYMENT_SECPAY_OUT_ZONE' => ['title' => 'Payment Zone',
                'desc' => 'If a zone is selected, only enable this payment method for that zone.',
                'value' => '0',
                'set_function' => 'tep_cfg_pull_down_zone_classes(',
                'use_function' => '\common\helpers\Zones::get_zone_class_title'],
            'MODULE_PAYMENT_SECPAY_OUT_DEBUG_EMAIL' => [
                'title' => 'Debug E-Mail Address',
                'desc' => 'All parameters of an invalid transaction will be sent to this email address.'
            ],
            'MODULE_PAYMENT_SECPAY_OUT_SORT_ORDER' => ['title' => 'Sort order of display.',
                'desc' => 'Sort order of display. Lowest is displayed first.',
                'value' => '0']];

        return $params;
    }

    public function describe_status_key(): ModuleStatus
    {
        return new ModuleStatus('MODULE_PAYMENT_SECPAY_OUT_STATUS', 'True', 'False');
    }

    public function describe_sort_key(): ModuleSortOrder
    {
        return new ModuleSortOrder('MODULE_PAYMENT_SECPAY_OUT_SORT_ORDER');
    }

    public function isOnline(): bool
    {
        return true;
    }

}
