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

require_once('lib/stripe.php');

use common\classes\modules\ModulePayment;
use common\classes\modules\ModuleStatus;
use common\classes\modules\ModuleSortOrder;
use lib\Stripe\Stripe;
use lib\Stripe\Source;
use lib\Stripe\Charge;
use lib\Stripe\Customer;
use lib\Stripe\Error\InvalidRequest;
use common\helpers\Output;
use common\helpers\Zones;

class stripe_ds extends ModulePayment {
    var $code, $title, $description, $enabled;

    protected $defaultTranslationArray = [
        'MODULE_PAYMENT_STRIPE_TEXT_TITLE' => 'Stripe',
        'MODULE_PAYMENT_STRIPE_TEXT_PUBLIC_TITLE' => 'Debit &amp; Credit Card',
        'MODULE_PAYMENT_STRIPE_TEXT_DESCRIPTION' => '<img src="images/icon_info.gif" border="0" />&nbsp;<a href="http://library.oscommerce.com/Package&en&stripe&oscom23&stripe_js" target="_blank" style="text-decoration: underline; font-weight: bold;">View Online Documentation</a><br /><br /><img src="images/icon_popup.gif" border="0">&nbsp;<a href="https://www.stripe.com" target="_blank" style="text-decoration: underline; font-weight: bold;">Visit Stripe Website</a>',

        'MODULE_PAYMENT_STRIPE_ERROR_ADMIN_CURL' => 'This module requires cURL to be enabled in PHP and will not load until it has been enabled on this webserver.',
        'MODULE_PAYMENT_STRIPE_ERROR_ADMIN_CONFIGURATION' => 'This module will not load until the Publishable Key and Secret Key parameters have been configured. Please edit and configure the settings of this module.',

        'MODULE_PAYMENT_STRIPE_CREDITCARD_NEW' => 'Enter a new Card',
        'MODULE_PAYMENT_STRIPE_CREDITCARD_OWNER' => 'Name on Card:',
        'MODULE_PAYMENT_STRIPE_CREDITCARD_NUMBER' => 'Card Number:',
        'MODULE_PAYMENT_STRIPE_CREDITCARD_EXPIRY' => 'Expiry Date:',
        'MODULE_PAYMENT_STRIPE_CREDITCARD_CVC' => 'Security Code:',
        'MODULE_PAYMENT_STRIPE_CREDITCARD_SAVE' => 'Save Card for next purchase?',

        'MODULE_PAYMENT_STRIPE_ERROR_TITLE' => 'There has been an error processing your credit card',
        'MODULE_PAYMENT_STRIPE_ERROR_GENERAL' => 'Please try again and if problems persist, please try another payment method.',
        'MODULE_PAYMENT_STRIPE_ERROR_CARDSTORED' => 'The stored card could not be found. Please try again and if problems persist, please try another payment method.',

        'MODULE_PAYMENT_STRIPE_DIALOG_CONNECTION_LINK_TITLE' => 'Test API Server Connection',
        'MODULE_PAYMENT_STRIPE_DIALOG_CONNECTION_TITLE' => 'API Server Connection Test',
        'MODULE_PAYMENT_STRIPE_DIALOG_CONNECTION_GENERAL_TEXT' => 'Testing connection to server..',
        'MODULE_PAYMENT_STRIPE_DIALOG_CONNECTION_BUTTON_CLOSE' => 'Close',
        'MODULE_PAYMENT_STRIPE_DIALOG_CONNECTION_TIME' => 'Connection Time:',
        'MODULE_PAYMENT_STRIPE_DIALOG_CONNECTION_SUCCESS' => 'Success!',
        'MODULE_PAYMENT_STRIPE_DIALOG_CONNECTION_FAILED' => 'Failed! Please review the Verify SSL Certificate settings and try again.',
        'MODULE_PAYMENT_STRIPE_DIALOG_CONNECTION_ERROR' => 'An error occurred. Please refresh the page, review your settings, and try again.'
    ];

    public function __construct() {
        parent::__construct();

        $this->signature = 'stripe|stripe|1.1|2.3';
        $this->api_version = '2017-06-05';

        $this->code = 'stripe_ds';
        $this->title = MODULE_PAYMENT_STRIPE_TEXT_TITLE;
        $this->public_title = MODULE_PAYMENT_STRIPE_TEXT_PUBLIC_TITLE;
        $this->description = MODULE_PAYMENT_STRIPE_TEXT_DESCRIPTION;
        $this->sort_order = defined('MODULE_PAYMENT_STRIPE_SORT_ORDER') ? MODULE_PAYMENT_STRIPE_SORT_ORDER : 0;
        $this->enabled = defined('MODULE_PAYMENT_STRIPE_STATUS') && (MODULE_PAYMENT_STRIPE_STATUS == 'True') ? true : false;
        $this->order_status = defined('MODULE_PAYMENT_STRIPE_ORDER_STATUS_ID') && ((int) MODULE_PAYMENT_STRIPE_ORDER_STATUS_ID > 0) ? (int) MODULE_PAYMENT_STRIPE_ORDER_STATUS_ID : 0;

        if (defined('MODULE_PAYMENT_STRIPE_STATUS')) {
            if (strpos(MODULE_PAYMENT_STRIPE_PUBLISHABLE_KEY, 'test') != false) {
                $this->title .= ' [Test]';
                $this->public_title .= ' (' . $this->title . ')';
            }
        }

        if ($this->enabled === true) {
            if (!tep_not_null(MODULE_PAYMENT_STRIPE_PUBLISHABLE_KEY) || !tep_not_null(MODULE_PAYMENT_STRIPE_SECRET_KEY)) {
                $this->description = '<div class="secWarning">' . MODULE_PAYMENT_STRIPE_ERROR_ADMIN_CONFIGURATION . '</div>' . $this->description;

                $this->enabled = false;
            }
        }

        if ($this->enabled === true) {
            $this->update_status();
        }
    }

    function update_status() {

        if (($this->enabled == true) && ((int) MODULE_PAYMENT_STRIPE_ZONE > 0)) {
            $check_flag = false;
            $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_STRIPE_ZONE . "' and zone_country_id = '" . $this->billing['country']['id'] . "' order by zone_id");
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

        if ((MODULE_PAYMENT_STRIPE_TOKENS == 'True') && !$this->manager->has('payment')) {
            $tokens_query = tep_db_query("select 1 from customers_stripe_tokens where customers_id = '" . (int) $this->manager->getCustomerAssigned() . "' limit 1");

            if (tep_db_num_rows($tokens_query)) {
                $this->manager->setPayment($this->code);
            }
        }
        
        return array('id' => $this->code,
            'module' => $this->public_title,
            'fields' => array($this->get_fields())
        );
    }

    function pre_confirmation_check() {
        if (isset($_POST['stripeSource'])){
            $this->manager->set('stripeData', [
                'stripeSource' => $_POST['stripeSource'],
                'way' => $_POST['way'],
                'three_d_secure' => $_POST['three_d_secure'],
            ]);
        }
    }

    function confirmation() {
        $confirmation = array(//'title' => MODULE_PAYMENT_SECURETRADING_WS_TEXT_CATALOG_TITLE, // Redundant
            'fields' => array(array('title' => $this->title,)
        ));
        return $confirmation;
    }

    function get_fields() {

        $order = $this->manager->getOrderInstance();
        
        $content = '<div class="form-row">
                      <div class="form-row">
                        <label for="card-element"></label>
                        <div id="card-element"></div>
                        <div id="card-errors" role="alert"></div>
                    </div>
                   </div>';
        
        \Yii::$app->getView()->registerJs($this->getSubmitCardDetailsJavascript());
        
        $params = [];
        if (MODULE_PAYMENT_STRIPE_3DSECURE == 'True') {
            $params['three_d_secure'] = 'authenticated';
        }
        $url = $this->getCheckoutUrl($params, self::PROCESS_PAGE);
        $address = array('address_line1' => $order->billing['street_address'],
            'address_city' => $order->billing['city'],
            'postalcode' => $order->billing['postcode'],
            'address_state' => Zones::get_zone_name($order->billing['country_id'], $order->billing['zone_id'], $order->billing['state']),
            'address_country' => $order->billing['country']['iso_code_2'],
            'amount' => $this->format_raw($order->info['total_inc_tax']),
            'currency' => strtolower($order->info['currency']),
            'redirect[return_url]' => $url,
        );

        foreach ($address as $k => $v) {
            $content .= '<input type="hidden" name="' . Output::output_string($k) . '" value="' . Output::output_string($v) . '" />';
        }
        
        $confirmation = array('title' => $content);

        return $confirmation;
    }

    function process_button() {
        //return \yii\helpers\Html::hiddenInput('skip', false);
    }
    
    public function popUpMode() {
        return true;
    }

    function before_process() {
        global $source;
        
        if (!$this->manager->has('stripeData')){
            tep_redirect($this->getCheckoutUrl(['error_message' => urlencode("Missing Stripe Data")], self::PAYMENT_PAGE));
        }
        
        $makeCharge = false;
        \Stripe\Stripe::setApiKey(MODULE_PAYMENT_STRIPE_SECRET_KEY);

        $order = $this->manager->getOrderInstance();
        $stripeData = $this->manager->get('stripeData');

        if (isset($stripeData['way']) && $stripeData['way'] == 'charge') {
            $three_d_secure = $stripeData['three_d_secure'];
            $stripeSource = $stripeData['stripeSource'];
            if ((MODULE_PAYMENT_STRIPE_3DSECURE == 'True' && $three_d_secure == 'optional') || $three_d_secure == 'required') {
                try {
                    $source = \Stripe\Source::create(array(
                                "amount" => $this->format_raw($order->info['total_inc_tax']),
                                "currency" => strtolower($order->info['currency']),
                                "type" => "three_d_secure",
                                "three_d_secure" => array(
                                    "card" => $stripeSource,
                                ),
                                "redirect" => array(
                                    "return_url" => $this->getCheckoutUrl(['way' => 'authenticated'], self::PROCESS_PAGE)
                                ),
                    ));
                } catch (\Stripe\Error\InvalidRequest $e) {
                    tep_redirect($this->getCheckoutUrl(['error_message' => urlencode($e->getMessage())], self::PAYMENT_PAGE));
                }

                if ($source->redirect->status == 'pending') {
                    tep_redirect($source->redirect->url);
                }
            }
            $makeCharge = true;
        }

        if (isset($_GET['way']) && $_GET['way'] == 'authenticated') {
            $makeCharge = true;
            $stripeSource = $_GET['source'];
            try {
                $source = \Stripe\Source::retrieve($stripeSource);
            } catch (\Stripe\Error\InvalidRequest $e) {
                tep_redirect($this->getCheckoutUrl(['error_message' => urlencode($e->getMessage())], self::PAYMENT_PAGE));
            }
            if ($source->client_secret != $_GET['client_secret']) {
                $makeCharge = false;
                $this->sendDebugEmail($e);
                tep_redirect($this->getCheckoutUrl(['error_message' => 'check+client+id'], self::PAYMENT_PAGE));
            }
        }

        if ($makeCharge) {
            try {
                $source = \Stripe\Source::retrieve($stripeSource);
            } catch (\Stripe\Error\InvalidRequest $e) {
                tep_redirect($this->getCheckoutUrl(['error_message' => urlencode($e->getMessage())], self::PAYMENT_PAGE));
            }
            if ($source->status == 'chargeable') {
                try {
                    $customer = \Stripe\Customer::create(array(
                                "email" => $order->customer['email_address'],
                                "source" => $source->source->id,
                    ));
                    if (MODULE_PAYMENT_STRIPE_TOKENS == 'True') {//save customers
                        $sql_data_array = [
                            'customers_id' => $this->manager->getCustomerAssigned(),
                            'stripe_token' => $customer->id,
                            'date_added' => 'now()'
                        ];
                        tep_db_perform('customers_stripe_tokens', $sql_data_array);
                    }
                } catch (\Stripe\Error\InvalidRequest $e) {
                    $this->sendDebugEmail($e);
                }

                try {
                    $source = \Stripe\Charge::create(array(
                                "amount" => $this->format_raw($order->info['total_inc_tax']),
                                "currency" => strtolower($order->info['currency']),
                                "source" => $stripeSource,
                    ));
                } catch (\Stripe\Error\InvalidRequest $e) {
                    tep_redirect($this->getCheckoutUrl(['error_message' => urlencode($e->getMessage())], self::PAYMENT_PAGE));
                } catch (\Stripe\Error\Card $e) {
                    tep_redirect($this->getCheckoutUrl(['error_message' => urlencode($e->getMessage())], self::PAYMENT_PAGE));
                }
                if ($source->status == 'succeeded') {
                    return true;
                }
            }
        }
        tep_redirect($this->getCheckoutUrl(['error_message' => 'undefined+error'], self::PAYMENT_PAGE));
    }

    function after_process() {
        global $source;

        $status_comment = [];
        if (is_object($source) && $source instanceof \Stripe\Charge) {
            $currencies = \Yii::$container->get('currencies');
            $order = $this->manager->getOrderInstance();
            $status_comment = array('Transaction ID: ' . $source->id);
            if ($source->outcome) {
                $status_comment[] = 'Seller Message: ' . $source->outcome->seller_message;
                $status_comment[] = 'Risk level: ' . $source->outcome->risk_level;
            }
            if ($source->source) {
                $status_comment[] = 'Type: ' . $source->source->type;
                $status_comment[] = 'Status: ' . $source->source->status;
                $status_comment[] = 'Transaction Amount: ' . $currencies->format(($source->amount/100), false, $order->info['currency'], $order->info['currency_value']);
            }

            $sql_data_array = array('orders_id' => $order->order_id,
                'orders_status_id' => MODULE_PAYMENT_STRIPE_TRANSACTION_ORDER_STATUS_ID,
                'date_added' => 'now()',
                'customer_notified' => '0',
                'comments' => implode("\n", $status_comment));

            tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
            $invoice_id = $this->manager->getOrderSplitter()->getInvoiceId();
            $this->manager->getTransactionManager($this)->addTransaction($source->id, $source->source->status, ($source->amount/100), $invoice_id, 'Customer\'s payment');
        }

        unset($_SESSION['cc_save']);
        $this->manager->remove('stripeData');

        if (tep_session_is_registered('stripe_error')) {
            tep_session_unregister('stripe_error');
        }
    }

    function get_error() {
        global $stripe_error;

        $message = MODULE_PAYMENT_STRIPE_ERROR_GENERAL;

        if (tep_session_is_registered('stripe_error')) {
            $message = $stripe_error . ' ' . $message;

            tep_session_unregister('stripe_error');
        }

        if (isset($_GET['error']) && !empty($_GET['error'])) {
            switch ($_GET['error']) {
                case 'cardstored':
                    $message = MODULE_PAYMENT_STRIPE_ERROR_CARDSTORED;
                    break;
            }
        }

        $error = array('title' => MODULE_PAYMENT_STRIPE_ERROR_TITLE,
            'error' => $message);

        return $error;
    }

    public function describe_status_key() {
        return new ModuleStatus('MODULE_PAYMENT_STRIPE_STATUS', 'True', 'False');
    }

    public function describe_sort_key() {
        return new ModuleSortOrder('MODULE_PAYMENT_STRIPE_SORT_ORDER');
    }

    public function configure_keys() {
        if (tep_db_num_rows(tep_db_query("show tables like 'customers_stripe_tokens'")) != 1) {
            $sql = <<<EOD
CREATE TABLE customers_stripe_tokens (
  id int NOT NULL auto_increment,
  customers_id int NOT NULL,
  stripe_token varchar(255) NOT NULL,
  date_added datetime NOT NULL,
  PRIMARY KEY (id),
  KEY idx_cstripet_customers_id (customers_id),
  KEY idx_cstripet_token (stripe_token)
);
EOD;

            tep_db_query($sql);
        }
        $status_id = defined('MODULE_PAYMENT_STRIPE_TRANSACTION_ORDER_STATUS_ID') ? MODULE_PAYMENT_STRIPE_TRANSACTION_ORDER_STATUS_ID : $this->getDefaultOrderStatusId();
        $status_id_p = defined('MODULE_PAYMENT_STRIPE_ORDER_STATUS_ID') ? MODULE_PAYMENT_STRIPE_ORDER_STATUS_ID : $this->getDefaultOrderStatusId();
        $params = array('MODULE_PAYMENT_STRIPE_STATUS' => array('title' => 'Enable Stripe Module',
                'desc' => 'Do you want to accept Stripe payments?',
                'value' => 'True',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '),
            'MODULE_PAYMENT_STRIPE_PUBLISHABLE_KEY' => array('title' => 'Publishable API Key',
                'desc' => 'The Stripe account publishable API key to use.',
                'value' => ''),
            'MODULE_PAYMENT_STRIPE_SECRET_KEY' => array('title' => 'Secret API Key',
                'desc' => 'The Stripe account secret API key to use with the publishable key.',
                'value' => ''),
            'MODULE_PAYMENT_STRIPE_TOKENS' => array('title' => 'Save Customers',
                'desc' => 'Save customers tokens to use on their next purchase?',
                'value' => 'False',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '),
            'MODULE_PAYMENT_STRIPE_3DSECURE' => array('title' => '3D Secure',
                'desc' => 'Use 3D secure?',
                'value' => 'False',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '),
            /* 'MODULE_PAYMENT_STRIPE_VERIFY_WITH_CVC' => array('title' => 'Verify With CVC',
              'desc' => 'Verify the credit card billing address with the Card Verification Code (CVC)?',
              'value' => 'True',
              'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '),
              'MODULE_PAYMENT_STRIPE_TRANSACTION_METHOD' => array('title' => 'Transaction Method',
              'desc' => 'The processing method to use for each transaction.',
              'value' => 'Authorize',
              'set_function' => 'tep_cfg_select_option(array(\'Authorize\', \'Capture\'), '), */
            'MODULE_PAYMENT_STRIPE_ORDER_STATUS_ID' => array('title' => 'Set Order Status',
                'desc' => 'Set the status of orders made with this payment module to this value',
                'value' => $status_id_p,
                'use_function' => '\\common\\helpers\\Order::get_order_status_name',
                'set_function' => 'tep_cfg_pull_down_order_statuses('),
            'MODULE_PAYMENT_STRIPE_TRANSACTION_ORDER_STATUS_ID' => array('title' => 'Transaction Order Status',
                'desc' => 'Include transaction information in this order status level',
                'value' => $status_id,
                'set_function' => 'tep_cfg_pull_down_order_statuses(',
                'use_function' => '\\common\\helpers\\Order::get_order_status_name'),
            'MODULE_PAYMENT_STRIPE_ZONE' => array('title' => 'Payment Zone',
                'desc' => 'If a zone is selected, only enable this payment method for that zone.',
                'value' => '0',
                'use_function' => '\\common\\helpers\\Zones::get_zone_class_title',
                'set_function' => 'tep_cfg_pull_down_zone_classes('),
            /* 'MODULE_PAYMENT_STRIPE_TRANSACTION_SERVER' => array('title' => 'Transaction Server',
              'desc' => 'Perform transactions on the production server or on the testing server.',
              'value' => 'Live',
              'set_function' => 'tep_cfg_select_option(array(\'Live\', \'Test\'), '), */
            /* 'MODULE_PAYMENT_STRIPE_VERIFY_SSL' => array('title' => 'Verify SSL Certificate',
              'desc' => 'Verify gateway server SSL certificate on connection?',
              'value' => 'True',
              'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '), */
            'MODULE_PAYMENT_STRIPE_DEBUG_EMAIL' => array('title' => 'Debug E-Mail Address',
                'desc' => 'All parameters of an invalid transaction will be sent to this email address.'),
            'MODULE_PAYMENT_STRIPE_SORT_ORDER' => array('title' => 'Sort order of display.',
                'desc' => 'Sort order of display. Lowest is displayed first.',
                'value' => '0'));

        return $params;
    }

    function format_raw($number, $currency_code = '', $currency_value = '') {
        $currencies = \Yii::$container->get('currencies');

        if (empty($currency_code) || !$currencies->is_set($currency_code)) {
            $currency_code = \Yii::$app->settings->get('currency');
        }

        if (empty($currency_value) || !is_numeric($currency_value)) {
            $currency_value = $currencies->currencies[$currency_code]['value'];
        }

        return number_format(self::round($number * $currency_value, $currencies->currencies[$currency_code]['decimal_places']), $currencies->currencies[$currency_code]['decimal_places'], '', '');
    }

    function getSubmitCardDetailsJavascript() {
        $order = $this->manager->getOrderInstance();
        $stripe_publishable_key = MODULE_PAYMENT_STRIPE_PUBLISHABLE_KEY;

        \Yii::$app->getView()->registerJsFile("https://js.stripe.com/v3/");
        $this->registerCallback("stripeCallback");
        
        $js = <<<EOD
function init(){
    if (!paymentCollection.hasOwnProperty('stripes')){
        var stripe = Stripe('{$stripe_publishable_key}');
        
        paymentCollection.stripes = {
            stripe: stripe,
            card: stripe.elements().create('card', { hidePostalCode:true })
        };
        paymentCollection.stripes.card.mount('#card-element');
    }
}
function stripeCallback(){
    init();
    paymentCollection.stripes.stripe.createSource(paymentCollection.stripes.card).then(function(result) {

        if (result.error) {
            // Inform the user if there was an error
            var errorElement = document.getElementById('card-errors');
            errorElement.textContent = result.error.message;
        } else {
            if (paymentCollection.form){
                var hiddenInput = document.createElement('input');
                hiddenInput.setAttribute('type', 'hidden');
                hiddenInput.setAttribute('name', 'stripeSource');
                hiddenInput.setAttribute('value', result.source.id);
                paymentCollection.form.append(hiddenInput);
                  //3d
                hiddenInput = document.createElement('input');
                hiddenInput.setAttribute('type', 'hidden');
                hiddenInput.setAttribute('name', 'three_d_secure');
                hiddenInput.setAttribute('value', result.source.card.three_d_secure);
                paymentCollection.form.append(hiddenInput);

                hiddenInput = document.createElement('input');
                hiddenInput.setAttribute('type', 'hidden');
                hiddenInput.setAttribute('name', 'way');
                hiddenInput.setAttribute('value', "charge");
                paymentCollection.form.append(hiddenInput);
                
                // Send the source to your server
                paymentCollection.finishCallback();
            }
        }
    });
}
EOD;
        return $js;
    }

    function sendDebugEmail($response = array()) {
        global $_POST, $_GET;

        if (tep_not_null(MODULE_PAYMENT_STRIPE_DEBUG_EMAIL)) {
            $email_body = '';

            if (!empty($response)) {
                $email_body .= 'RESPONSE:' . "\n\n" . print_r($response, true) . "\n\n";
            }

            if (!empty($_POST)) {
                $email_body .= '$_POST:' . "\n\n" . print_r($_POST, true) . "\n\n";
            }

            if (!empty($_GET)) {
                $email_body .= '$_GET:' . "\n\n" . print_r($_GET, true) . "\n\n";
            }

            if (!empty($email_body)) {
                \common\helpers\Mail::send('', MODULE_PAYMENT_STRIPE_DEBUG_EMAIL, 'Stripe Debug E-Mail', trim($email_body), STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
            }
        }
    }

    function isOnline() {
        return true;
    }

}