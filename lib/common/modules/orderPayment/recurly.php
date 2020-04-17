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

require_once('lib/recurly.php');

use common\classes\modules\ModulePayment;
use common\classes\modules\ModuleStatus;
use common\classes\modules\ModuleSortOrder;

class recurly extends ModulePayment {

    var $code, $title, $description, $enabled;

    protected $defaultTranslationArray = [
        'MODULE_PAYMENT_RECURLY_SERVER_TEXT_TITLE' => 'Recurly',
        'MODULE_PAYMENT_RECURLY_SERVER_TEXT_DESCRIPTION' => 'Recurly'
    ];

    function __construct() {
        parent::__construct();

        $this->code = 'recurly';
        $this->title = MODULE_PAYMENT_RECURLY_SERVER_TEXT_TITLE;
        $this->description = MODULE_PAYMENT_RECURLY_SERVER_TEXT_DESCRIPTION;
        if (!defined('MODULE_PAYMENT_RECURLY_SERVER_STATUS')) {
            $this->enabled = false;
            return;
        }
        $this->public_title = MODULE_PAYMENT_RECURLY_SERVER_TEXT_PUBLIC_TITLE;
        $this->sort_order = MODULE_PAYMENT_RECURLY_SERVER_SORT_ORDER;
        $this->enabled = ((MODULE_PAYMENT_RECURLY_SERVER_STATUS == 'True') ? true : false);

        $this->update_status();
    }

    function update_status() {

        if (($this->enabled == true) && ((int) MODULE_PAYMENT_RECURLY_SERVER_ZONE > 0)) {
            $check_flag = false;
            $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_RECURLY_SERVER_ZONE . "' and zone_country_id = '" . $this->billing['country']['id'] . "' order by zone_id");
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
            'module' => $this->public_title);
    }

    function pre_confirmation_check() {
      return false;
    }

    function confirmation() {

        for ($i = 1; $i < 13; $i++) {
            $expires_month[] = array('id' => sprintf('%02d', $i), 'text' => sprintf('%02d', $i));
        }

        $today = getdate();
        for ($i = $today['year']; $i < $today['year'] + 10; $i++) {
            $expires_year[] = array('id' => strftime('%y', mktime(0, 0, 0, 1, 1, $i)), 'text' => strftime('%Y', mktime(0, 0, 0, 1, 1, $i)));
        }

        $confirmation = array('fields' => array(array('title' => MODULE_PAYMENT_RECURLY_CREDIT_CARD_OWNER_FIRSTNAME,
                    'field' => tep_draw_input_field('cc_owner_firstname', $this->billing['firstname'])),
                array('title' => MODULE_PAYMENT_RECURLY_CREDIT_CARD_OWNER_LASTNAME,
                    'field' => tep_draw_input_field('cc_owner_lastname', $this->billing['lastname'])),
                array('title' => MODULE_PAYMENT_RECURLY_CREDIT_CARD_NUMBER,
                    'field' => tep_draw_input_field('cc_number_nh')),
                array('title' => MODULE_PAYMENT_RECURLY_CREDIT_CARD_EXPIRES,
                    'field' => tep_draw_pull_down_menu('cc_expires_month', $expires_month) . '&nbsp;' . tep_draw_pull_down_menu('cc_expires_year', $expires_year)),
                array('title' => MODULE_PAYMENT_RECURLY_CREDIT_CARD_CCV,
                    'field' => tep_draw_input_field('cc_ccv_nh', '', 'size="5" maxlength="4"'))));

        return $confirmation;
    }

    function process_button() {
      return false;
    }

    function before_process() {

        $order = $this->manager->getOrderInstance();

        \Recurly_Client::$subdomain = MODULE_PAYMENT_RECURLY_SERVER_SUBDOMAIN;
        \Recurly_Client::$apiKey = MODULE_PAYMENT_RECURLY_SERVER_KEY;

        try {
            $billing_info = new \Recurly_BillingInfo();
            $billing_info->number = $_POST['cc_number_nh'];
            $billing_info->month = (int)$_POST['cc_expires_month'];
            $billing_info->year = (int)$_POST['cc_expires_year'];
            $billing_info->verification_value = $_POST['cc_ccv_nh'];
            $billing_info->address1 = $order->billing['street_address'];
            $billing_info->city = $order->billing['city'];
            $billing_info->state = $order->billing['state'];
            $billing_info->country = $order->billing['country']['iso_code_2'];
            $billing_info->zip = $order->billing['postcode'];

            $account = new \Recurly_Account();
            $account->account_code = $this->manager->getCustomerAssigned();
            $account->email = $order->customer['email_address'];
            $account->first_name = $_POST['cc_owner_firstname'];
            $account->last_name = $_POST['cc_owner_lastname'];
            $account->billing_info = $billing_info;

            $transaction = new \Recurly_Transaction();
            $transaction->amount_in_cents = (int)($order->info['total'] * 100); // $10.00.
            $transaction->currency = \Yii::$app->settings->get('currency');
            $transaction->account = $account;
            $transaction->create();
        } catch (\Recurly_ValidationError $e) {
            $error = $e->getMessage();
            tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code . (tep_not_null($error) ? '&error=' . $error : ''), 'SSL'));
        }

        return false;
    }

    function after_process() {
        return false;
    }

    function get_error() {
        $error = array(
            'title' => MODULE_PAYMENT_RECURLY_TEXT_ERROR,
            'error' => $_GET['error']
        );

        return $error;
    }

    public function describe_status_key()
    {
      return new ModuleStatus('MODULE_PAYMENT_RECURLY_SERVER_STATUS','True','False');
    }

    public function describe_sort_key()
    {
      return new ModuleSortOrder('MODULE_PAYMENT_RECURLY_SERVER_SORT_ORDER');
    }

    public function configure_keys()
    {
        $status_id = defined('MODULE_PAYMENT_RECURLY_SERVER_ORDER_STATUS_ID') ? MODULE_PAYMENT_RECURLY_SERVER_ORDER_STATUS_ID : $this->getDefaultOrderStatusId();
        return array(
        'MODULE_PAYMENT_RECURLY_SERVER_STATUS' => array(
          'title' => 'Enable Recurly Module',
          'value' => 'False',
          'description' => 'Do you want to accept Recurly payments?',
          'sort_order' => '0',
          'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
        ),
        'MODULE_PAYMENT_RECURLY_SERVER_SUBDOMAIN' => array(
          'title' => 'Your subdomain',
          'value' => '',
          'description' => 'https:\/\/<your-subdomain>.recurly.com',
          'sort_order' => '0',
        ),
        'MODULE_PAYMENT_RECURLY_SERVER_KEY' => array(
          'title' => 'API key',
          'value' => '',
          'description' => 'Your private API key',
          'sort_order' => '0',
        ),
        'MODULE_PAYMENT_RECURLY_SERVER_ZONE' => array(
          'title' => 'Payment Zone',
          'value' => '0',
          'description' => 'If a zone is selected, only enable this payment method for that zone.',
          'sort_order' => '2',
          'use_function' => '\\common\\helpers\\Zones::get_zone_class_title',
          'set_function' => 'tep_cfg_pull_down_zone_classes(',
        ),
        'MODULE_PAYMENT_RECURLY_SERVER_ORDER_STATUS_ID' => array(
          'title' => 'Set Order Status',
          'value' => $status_id,
          'description' => 'Set the status of orders made with this payment module to this value',
          'sort_order' => '0',
          'set_function' => 'tep_cfg_pull_down_order_statuses(',
          'use_function' => '\\common\\helpers\\Order::get_order_status_name',
        ),
        'MODULE_PAYMENT_RECURLY_SERVER_SORT_ORDER' => array(
          'title' => 'Sort order of display.',
          'value' => '0',
          'description' => 'Sort order of display. Lowest is displayed first.',
          'sort_order' => '0',
        ),
      );
    }

    public function install($platform_id) {
        $languages = \common\helpers\Language::get_languages(true);
        foreach ($languages as $language) {
            tep_db_query("INSERT IGNORE INTO `translation` (`language_id`, `translation_key`, `translation_entity`, `translation_value`, `hash`, `not_used`, `translated`) VALUES (" . (int) $language['id'] . ", 'MODULE_PAYMENT_RECURLY_SERVER_TEXT_PUBLIC_TITLE', 'payment', 'Recurly Subscription', '27304bf18b70183aa964fd50301dc76d', 0, 0);");
        }
        return parent::install($platform_id);
    }

    function before_subscription($id = 0) {
        $order = $this->manager->getOrderInstance();

        if ($order->products[$id]['subscription'] != 1) {
            return false;
        }

        \Recurly_Client::$subdomain = MODULE_PAYMENT_RECURLY_SERVER_SUBDOMAIN;
        \Recurly_Client::$apiKey = MODULE_PAYMENT_RECURLY_SERVER_KEY;

        try {

            $billing_info = new \Recurly_BillingInfo();
            $billing_info->number = $_POST['cc_number_nh'];
            $billing_info->month = (int)$_POST['cc_expires_month'];
            $billing_info->year = (int)$_POST['cc_expires_year'];
            $billing_info->verification_value = $_POST['cc_ccv_nh'];
            $billing_info->address1 = $order->billing['street_address'];
            $billing_info->city = $order->billing['city'];
            $billing_info->state = $order->billing['state'];
            $billing_info->country = $order->billing['country']['iso_code_2'];
            $billing_info->zip = $order->billing['postcode'];

            $account = new \Recurly_Account();
            $account->account_code = $this->manager->getCustomerAssigned();
            $account->email = $order->customer['email_address'];
            $account->first_name = $_POST['cc_owner_firstname'];
            $account->last_name = $_POST['cc_owner_lastname'];
            $account->billing_info = $billing_info;

            $subscription = new \Recurly_Subscription();
            $subscription->plan_code = $order->products[$id]['subscription_code'];
            $subscription->currency = \Yii::$app->settings->get('currency');
            $subscription->account = $account;
            $subscription->create();

            $uuid = $subscription->__get('uuid');
            if (!empty($uuid)) {
                return $uuid;
            }
        } catch (\Recurly_ValidationError $e) {
            $error = $e->getMessage();
            tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code . (tep_not_null($error) ? '&error=' . $error : ''), 'SSL'));
        }

        return false;
    }

    function haveSubscription() {
        return true;
    }

    function get_subscription_info($uuid = '') {

        try {
            \Recurly_Client::$subdomain = MODULE_PAYMENT_RECURLY_SERVER_SUBDOMAIN;
            \Recurly_Client::$apiKey = MODULE_PAYMENT_RECURLY_SERVER_KEY;

            $subscription = \Recurly_Subscription::get($uuid);

            return $subscription->__get('plan')->__get('name');

        } catch (\Recurly_NotFoundError $e) {
            return '';
        }
        return '';
    }

    function download_invoice($id = '', $currentByUUID = false) {

        if ($currentByUUID) {
            $id = $this->get_subscription_invoice_id($id);
        }
        try {
            \Recurly_Client::$subdomain = MODULE_PAYMENT_RECURLY_SERVER_SUBDOMAIN;
            \Recurly_Client::$apiKey = MODULE_PAYMENT_RECURLY_SERVER_KEY;

            $pdf = \Recurly_Invoice::getInvoicePdf($id);

            header( 'Content-Type: application/pdf' );
            header( 'Expires: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
            header( 'Content-Disposition: inline; filename="invoice_' . $id . '.pdf"' );

            echo $pdf;
        } catch (\Recurly_NotFoundError $e) {
            print "Invoice not found: $e";
        }

        exit();
    }

    function get_subscription_invoice_id($uuid = '') {
        try {
            \Recurly_Client::$subdomain = MODULE_PAYMENT_RECURLY_SERVER_SUBDOMAIN;
            \Recurly_Client::$apiKey = MODULE_PAYMENT_RECURLY_SERVER_KEY;

            $subscription = \Recurly_Subscription::get($uuid);

            return  $subscription->__get('invoice')->get()->invoice_number;
        } catch (\Recurly_NotFoundError $e) {
            return false;
        }
    }

    function get_subscription_full_info($uuid = '') {
        try {
            \Recurly_Client::$subdomain = MODULE_PAYMENT_RECURLY_SERVER_SUBDOMAIN;
            \Recurly_Client::$apiKey = MODULE_PAYMENT_RECURLY_SERVER_KEY;

            $subscription = \Recurly_Subscription::get($uuid);

            $response = '<div class="widget box box-no-shadow widget-subscription">';

            $response .= '<div class="widget-header"><h4>' . $subscription->__get('plan')->__get('name') . '</h4></div>';
            $response .= '<div class="widget-content">';
            $response .= '<div class="after widget-subs-box">';
            $response .= '<div class="widget-subs-box-left">';
            $response .= '<div class="edp-line"><label>Status:</label>' . $subscription->__get('state') . '</div>';
            $response .= '<div class="edp-line"><label>Start date:</label>' . $subscription->__get('activated_at')->format('M d,Y') . '</div>';

            switch ($subscription->__get('state')) {
                case 'active':
                    $response .= '<div class="edp-line"><label>Current Period:</label>' . $subscription->__get('current_period_started_at')->format('M d,Y') . ' - ' . $subscription->__get('current_period_ends_at')->format('M d,Y'). '</div>';
                    break;
                case 'canceled':
                    $response .= '<div class="edp-line"><label>Canceled on:</label>' . $subscription->__get('canceled_at')->format('Y-m-d'). '</div>';//canceled_at
                    break;
                case 'future':
                    break;
                case 'expired':
                    $response .= '<div class="edp-line"><label>Expired on:</label>' . $subscription->__get('expires_at')->format('Y-m-d'). '</div>';//expires_at
                    break;
                default:
                    break;
            }
            $response .= '<div class="edp-line"><label>Collection:</label>' . $subscription->__get('collection_method') . '</div>';//collection_method
            //$response[] = 'Updated at: ' . $subscription->__get('updated_at')->format('Y-m-d');//updated_at
            //$response[] = $subscription->__get('total_billing_cycles');//total_billing_cycles
            //$response[] = $subscription->__get('remaining_billing_cycles');//remaining_billing_cycles


            //invoice
            //plan

            //$response[] = 'Plan code: ' . $subscription->__get('plan')->__get('plan_code');
            //$response[] = 'Amount: ' . ($subscription->__get('unit_amount_in_cents') / 100 );//currency

            if (is_object($subscription->__get('invoice'))) {
                $invoice_number = $subscription->__get('invoice')->get()->invoice_number;
                $response .= '<div class="edp-line"><label>Invoice:</label><a href="' . \Yii::$app->urlManager->createUrl(['subscription/download-invoice', 'set' => 'payment', 'module' => 'recurly', 'id' => $invoice_number]) . '">#' . $invoice_number . '</a></div>';
            }
            $response .= '</div><div class="widget-subs-box-right">';
            $response .= '<div class="after table-sub-pr"><div>' . $subscription->__get('quantity') . ' x ' . $subscription->__get('plan')->__get('name') . '</div><div>' . ($subscription->__get('unit_amount_in_cents') / 100 ) . ' ' . $subscription->__get('currency') . '</div></div>';
            $response .= '</div></div>';
            $response .= '</div></div>';
            return $response;
        } catch (\Recurly_NotFoundError $e) {
            return '';
        }
        return [];
    }

    function cancel_subscription($uuid = '') {
        try {
            \Recurly_Client::$subdomain = MODULE_PAYMENT_RECURLY_SERVER_SUBDOMAIN;
            \Recurly_Client::$apiKey = MODULE_PAYMENT_RECURLY_SERVER_KEY;

            $subscription = \Recurly_Subscription::get($uuid);
            $subscription->cancel();

        } catch (\Recurly_Error $e) {
            return $e->getMessage();
        }
        return false;
    }

    function terminate_subscription($uuid = '', $type = 'none') {
        try {
            \Recurly_Client::$subdomain = MODULE_PAYMENT_RECURLY_SERVER_SUBDOMAIN;
            \Recurly_Client::$apiKey = MODULE_PAYMENT_RECURLY_SERVER_KEY;

            $subscription = \Recurly_Subscription::get($uuid);
            switch ($type) {
                case 'full':
                    $subscription->terminateAndRefund();
                    break;
                case 'partial':
                    $subscription->terminateAndPartialRefund();
                    break;
                case 'none':
                default:
                    $subscription->terminateWithoutRefund();
                    break;
            }

        } catch (\Recurly_Error $e) {
            return $e->getMessage();
        }
        return false;
    }

    function postpone_subscription($uuid = '', $date = '') {
        try {
            \Recurly_Client::$subdomain = MODULE_PAYMENT_RECURLY_SERVER_SUBDOMAIN;
            \Recurly_Client::$apiKey = MODULE_PAYMENT_RECURLY_SERVER_KEY;

            $subscription = \Recurly_Subscription::get($uuid);
            $subscription->postpone(date('c', strtotime($date)));

        } catch (\Recurly_Error $e) {
            return $e->getMessage();
        }
        return false;
    }

    function reactivate_subscription($uuid = '') {
        try {
            \Recurly_Client::$subdomain = MODULE_PAYMENT_RECURLY_SERVER_SUBDOMAIN;
            \Recurly_Client::$apiKey = MODULE_PAYMENT_RECURLY_SERVER_KEY;

            $subscription = \Recurly_Subscription::get($uuid);
            $subscription->reactivate();

        } catch (\Recurly_Error $e) {
            return $e->getMessage();
        }
        return false;
    }

    function call_webhooks() {
        \Recurly_Client::$subdomain = MODULE_PAYMENT_RECURLY_SERVER_SUBDOMAIN;
        \Recurly_Client::$apiKey = MODULE_PAYMENT_RECURLY_SERVER_KEY;

        $post_xml = file_get_contents ("php://input");

        $notification = new \Recurly_PushNotification($post_xml);

        $uuid = (string)$notification->subscription->uuid;

        $check_query = tep_db_query("select * from " . TABLE_SUBSCRIPTION . " where transaction_id = '" . tep_db_input($uuid) . "'");
        if (tep_db_num_rows($check_query) == 0) {
            return false;
        }
        $check = tep_db_fetch_array($check_query);
        $subscription_id = $check['subscription_id'];
        $current_status = $check['subscription_status'];

        switch ($notification->type) {
//            case 'new_account_notification'://New Account
//                break;
//            case 'canceled_account_notification'://Closed Account
//                break;
//            case 'billing_info_updated_notification'://Updated Billing Information
//                break;
//            case 'billing_info_update_failed_notification'://Failed Billing Information Update
//                break;
//            case 'reactivated_account_notification'://Reactivated Account
//                break;

//            case 'new_shipping_address_notification'://A new shipping address is created
//                break;
//            case 'updated_shipping_address_notification'://An existing shipping address is edited
//                break;
//            case 'deleted_shipping_address_notification'://An existing shipping address is deleted
//                break;

//            case 'new_subscription_notification'://New Subscription
            case 'updated_subscription_notification'://Updated Subscription
            case 'canceled_subscription_notification'://Canceled Subscription
            case 'expired_subscription_notification'://Expired Subscription
            case 'renewed_subscription_notification'://Renewed Subscription
                $subscription = \Recurly_Subscription::get($uuid);
                switch ($subscription->__get('state')) {
                    case 'active':
                        $current_status = \common\helpers\Subscription::getStatus('Active');
                        break;
                    case 'canceled':
                        $current_status = \common\helpers\Subscription::getStatus('Canceled');
                        break;
                    case 'future':
                        $current_status = \common\helpers\Subscription::getStatus('Future');
                        break;
                    case '100005':
                        $current_status = \common\helpers\Subscription::getStatus('Expired');
                        break;
                    default:
                        break;
                }
                break;

//            case 'scheduled_payment_notification':
//                break;
//            case 'processing_payment_notification':
//                break;
//            case 'successful_payment_notification':
//                break;
//            case 'failed_payment_notification':
//                break;
//            case 'successful_refund_notification':
//                break;
//            case 'void_payment_notification':
//                break;

//            case 'new_usage_notification':
//                break;

//            case 'purchased_gift_card_notification':
//                break;
//            case 'canceled_gift_card_notification':
//                break;
//            case 'updated_gift_card_notification':
//                break;
//            case 'regenerated_gift_card_notification':
//                break;
//            case 'redeemed_gift_card_notification':
//                break;
//            case 'updated_balance_gift_card_notification':
//                break;

//            case 'new_invoice_notification':
//                break;
//            case 'processing_invoice_notification':
//                break;
//            case 'closed_invoice_notification':
//                break;
//            case 'past_due_invoice_notification':
//                break;

//            case 'new_dunning_event_notification':
//                break;
        }
        if ($current_status != $check['subscription_status']) {
            $sql_data_array = [];
            $sql_data_array['subscription_status_id'] = $current_status;
            $sql_data_array['last_modified'] = 'now()';
            tep_db_perform(TABLE_SUBSCRIPTION, $sql_data_array, 'update', 'subscription_id=' . (int)$subscription_id);

            $sql_data_array = [
                'subscription_id' => (int)$subscription_id,
                'subscription_status_id' => $current_status,
                'date_added' => 'now()',
                'customer_notified' => 0,
                'comments' => '',
                'admin_id' => 0,
            ];
            tep_db_perform(TABLE_SUBSCRIPTION_STATUS_HISTORY, $sql_data_array);
        }

        $post_xml = file_get_contents ("php://input");
        $sql_data_array = [
            'xml' => $post_xml,
        ];
        tep_db_perform('recurly', $sql_data_array);
        return false;
    }

    function isOnline() {
        return true;
    }
}