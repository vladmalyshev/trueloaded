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
use common\classes\platform_config;
use \SimpleXMLElement;

class authorizenet_accept_hosted extends ModulePayment {

    var $code, $title, $description, $enabled;

    protected $defaultTranslationArray = [
        'MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_TEXT_TITLE' => 'Authorize.net Accept Hosted',
        'MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_TEXT_PUBLIC_TITLE' => 'Credit Card',
        'MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_TEXT_DESCRIPTION' => '',

        'MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_ERROR_ADMIN_CONFIGURATION' => 'This module will not load until the API Login ID and API Transaction Key parameters have been configured. Please edit and configure the settings of this module.',

        'MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_TEXT_RETURN_BUTTON' => 'Back to %s',

        'MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_ERROR_TITLE' => 'There has been an error processing your credit card',
        'MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_ERROR_VERIFICATION' => 'The credit card transaction could not be verified with this order. Please try again and if problems persist, please try another payment method.',
        'MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_ERROR_DECLINED' => 'This credit card transaction has been declined. Please try again and if problems persist, please try another payment method.',
        'MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_ERROR_GENERAL' => 'Please try again and if problems persist, please try another payment method.'
    ];

    function __construct() {
        parent::__construct();

        $this->code = 'authorizenet_accept_hosted';
        $this->title = MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_TEXT_TITLE;
        $this->public_title = MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_TEXT_PUBLIC_TITLE;
        $this->description = MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_TEXT_DESCRIPTION;
        $this->sort_order = defined('MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_SORT_ORDER') ? MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_SORT_ORDER : null;
        $this->enabled = defined('MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_STATUS') && (MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_STATUS == 'True') ? true : false;
        $this->order_status = defined('MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_ORDER_STATUS_ID') && ((int) MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_ORDER_STATUS_ID > 0) ? (int) MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_ORDER_STATUS_ID : 0;

        if (defined('MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_STATUS')) {
            if (MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_TRANSACTION_SERVER == 'Test') {
                $this->title .= ' [Test]';
                $this->public_title .= ' (' . $this->code . '; Test)';
            }

            if (MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_TRANSACTION_SERVER == 'Live') {
                $this->form_action_url = 'https://accept.authorize.net/payment/payment';
            } else {
                $this->form_action_url = 'https://test.authorize.net/payment/payment';
            }
        }

        if ($this->enabled === true) {
            if (!tep_not_null(MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_LOGIN_ID) || !tep_not_null(MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_TRANSACTION_KEY)) {
                $this->description = '<div class="secWarning">' . MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_ERROR_ADMIN_CONFIGURATION . '</div>' . $this->description;

                $this->enabled = false;
            }
        }

        if ($this->enabled === true) {
            $this->update_status();
        }
    }

    function update_status() {

        if (($this->enabled == true) && ((int) MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_ZONE > 0)) {
            $check_flag = false;
            $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_ZONE . "' and zone_country_id = '" . $this->billing['country']['id'] . "' order by zone_id");
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
        return array('title' => 'Upon clicked to Confirm this order, you will be directed to secure Credit Card Processing Gateway to enter your credit card information');
    }

    function thisPageURL() {
        $pageURL = 'http';
        if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
            $pageURL .= "s";
        }
        $pageURL .= "://";
        if ($_SERVER["SERVER_PORT"] != "80") {
            $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
        } else {
            $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
        }

        $pageLocation = str_replace('index.php', '', $pageURL);

        return $pageLocation;
    }

    function process_button() {
        global $languages_id, $anh_card_id;

        if (tep_session_is_registered('anh_card_id') && $anh_card_id) {
            $tmpOrder = $this->manager->getParentToInstanceWithId('\common\classes\TmpOrder', $anh_card_id);
        } else {
            $tmpOrder = $this->manager->getParentToInstance('\common\classes\TmpOrder');
            $tmpOrder->info['order_status'] = MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_ORDER_STATUS_ID_BEFORE;

            $insert_id = $tmpOrder->save_order();
            $tmpOrder->save_details();
            $tmpOrder->save_products(false);
            $anh_card_id = $insert_id;
        }

        if (!tep_session_is_registered('anh_card_id'))
            tep_session_register('anh_card_id');

        $tmpOrder_info_total = $this->format_raw($tmpOrder->info['total_inc_tax']);

        $description = "Order from " . STORE_NAME;
        $lineItems = $lineItem = '';
        for ($i = 0, $n = sizeof($tmpOrder->products); $i < $n; $i++) {
            $lineItem = "<lineItem>" . "\n";
            $lineItem .= "<itemId><![CDATA[" . $tmpOrder->products[$i]['id'] . "]]></itemId>" . "\n";
            $lineItem .= "<name><![CDATA[" . substr($tmpOrder->products[$i]['name'], 0, 31);
            if (isset($tmpOrder->products[$i]['attributes'])) {
                for ($j = 0, $n2 = sizeof($tmpOrder->products[$i]['attributes']); $j < $n2; $j++) {
                    $attributes = tep_db_query("select pa.products_attributes_id, popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa where pa.products_id = '" . $tmpOrder->products[$i]['id'] . "' and pa.options_id = '" . $tmpOrder->products[$i]['attributes'][$j]['option_id'] . "' and pa.options_id = popt.products_options_id and pa.options_values_id = '" . $tmpOrder->products[$i]['attributes'][$j]['value_id'] . "' and pa.options_values_id = poval.products_options_values_id and popt.language_id = '" . $languages_id . "' and poval.language_id = '" . $languages_id . "'");
                    $attributes_values = tep_db_fetch_array($attributes);
                    if ($lineItemAttributes != "") {
                        $lineItemAttributes .= " | ";
                    }
                    $lineItemAttributes .= $attributes_values['products_options_name'] . " : " . $attributes_values['products_options_values_name'];
                }
            }
            $lineItem .= "]]></name>" . "\n";
            $lineItem .= "<quantity><![CDATA[" . $tmpOrder->products[$i]['qty'] . "]]></quantity>" . "\n";
            $lineItem .= "<unitPrice><![CDATA[" . $this->format_raw($tmpOrder->products[$i]['final_price']) . "]]></unitPrice>" . "\n";
            $lineItem .= "</lineItem>" . "\n";
        }
        $lineItems = $lineItem;
        $hash = md5($tmpOrder_info_total . $tmpOrder->customer['email_address'] . $tmpOrder->billing['postcode']);



        $Module_Payment_Authorizenet_Accept_Hosted_Login_ID = MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_LOGIN_ID;
        $Module_Payment_Authorizenet_Accept_Hosted_Transaction_Key = MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_TRANSACTION_KEY;

        $xmlStr = '<?xml version="1.0" encoding="utf-8"?>
      <getHostedPaymentPageRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
          <merchantAuthentication>
            <name>' . $Module_Payment_Authorizenet_Accept_Hosted_Login_ID . '</name>
            <transactionKey>' . $Module_Payment_Authorizenet_Accept_Hosted_Transaction_Key . '</transactionKey>
          </merchantAuthentication>
          <transactionRequest>
              <transactionType>' . MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_TRANSACTION_TYPE . '</transactionType>
              <amount><![CDATA[' . $tmpOrder_info_total . ']]></amount>
              <order>
                <invoiceNumber><![CDATA[' . $anh_card_id . ']]></invoiceNumber>
                <description><![CDATA[' . substr($description, 0, 255) . ']]></description>
              </order>
              <lineItems>' . $lineItems . '</lineItems>
              <customer>
                <email><![CDATA[' . $tmpOrder->customer['email_address'] . ']]></email>
              </customer>
              <billTo>
                <firstName><![CDATA[' . substr($tmpOrder->billing['firstname'],0,50) . ']]></firstName>
                <lastName><![CDATA[' . substr($tmpOrder->billing['lastname'],0,50) . ']]></lastName>
                <address><![CDATA[' . substr($tmpOrder->billing['street_address'],0,60) . ']]></address>
                <city><![CDATA[' . substr($tmpOrder->billing['city'],0,40) . ']]></city>
                <state><![CDATA[' . substr($tmpOrder->billing['state'],0,40) . ']]></state>
                <zip><![CDATA[' . substr($tmpOrder->billing['postcode'],0,20) . ']]></zip>
                <country><![CDATA[' . substr($tmpOrder->billing['country']['iso_code_3'],0,60) . ']]></country>
                <phoneNumber><![CDATA[' . substr($tmpOrder->customer['telephone'],0,25) . ']]></phoneNumber>
              </billTo>' .
                ($this->manager->isShippingNeeded() ?
                '<shipTo>
                <firstName><![CDATA[' . substr($tmpOrder->delivery['firstname'],0,50) . ']]></firstName>
                <lastName><![CDATA[' . substr($tmpOrder->delivery['lastname'],0,50) . ']]></lastName>
                <address><![CDATA[' . substr($tmpOrder->delivery['street_address'],0,60) . ']]></address>
                <city><![CDATA[' . substr($tmpOrder->delivery['city'],0,40) . ']]></city>
                <state><![CDATA[' . substr($tmpOrder->delivery['state'],0,40) . ']]></state>
                <zip><![CDATA[' . substr($tmpOrder->delivery['postcode'],0,20) . ']]></zip>
                <country><![CDATA[' . substr($tmpOrder->delivery['country']['iso_code_3'],0,60) . ']]></country>
              </shipTo>' : '') .
                '<userFields>
                <userField>
                <name>hash</name>
                <value>' . $hash . '</value>
                </userField>
                <userField>
                <name>tmpOrderID</name>
                <value>' . $anh_card_id . '</value>
                </userField>
              </userFields>
            </transactionRequest>
          <hostedPaymentSettings>
              <setting>
                  <settingName>hostedPaymentButtonOptions</settingName>
                  <settingValue>{"text": "Pay"}</settingValue>
              </setting>
              <setting>
                  <settingName>hostedPaymentReturnOptions</settingName>
              </setting>
              <setting>
                  <settingName>hostedPaymentOrderOptions</settingName>
                  <settingValue>{"show": true}</settingValue>
              </setting>
              <setting>
                  <settingName>hostedPaymentPaymentOptions</settingName>
                  <settingValue>{"cardCodeRequired": true, "showBankAccount": false }</settingValue>
              </setting>
              <setting>
                  <settingName>hostedPaymentBillingAddressOptions</settingName>
                  <settingValue>{"show": true, "required":true}</settingValue>
              </setting>
              <setting>
                  <settingName>hostedPaymentShippingAddressOptions</settingName>
                  <settingValue>{"show": true, "required":true}</settingValue>
              </setting>
              <setting>
                  <settingName>hostedPaymentSecurityOptions</settingName>
                  <settingValue>{"captcha": false}</settingValue>
              </setting>
              <setting>
                  <settingName>hostedPaymentStyleOptions</settingName>
                  <settingValue>{"bgColor": "' . MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_BACKGROUND_COLOR . '"}</settingValue>
              </setting>
              <setting>
                  <settingName>hostedPaymentCustomerOptions</settingName>
                  <settingValue>{"showEmail": true, "requiredEmail":true}</settingValue>
              </setting>
          </hostedPaymentSettings>
      </getHostedPaymentPageRequest>';

        $xml = simplexml_load_string($xmlStr, 'SimpleXMLElement', LIBXML_NOWARNING);

        $url = tep_href_link(FILENAME_CHECKOUT_PROCESS, 'hash=' . $hash . "-" . $anh_card_id, 'SSL', false);
        $retUrl = json_encode(
                array(
            "showReceipt" => false,
            "url" => htmlspecialchars($url),
            "urlText" => "Continue to site",
            "cancelUrl" => tep_href_link(FILENAME_CHECKOUT_CONFIRMATION, '', 'SSL', false),
            "cancelUrlText" => "Cancel"
                ), JSON_UNESCAPED_SLASHES
        );
        $xml->hostedPaymentSettings->setting[1]->addChild('settingValue', $retUrl);

        if (MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_TRANSACTION_SERVER == 'Live') {
            $url = "https://api.authorize.net/xml/v1/request.api";
        } else {
            $url = "https://apitest.authorize.net/xml/v1/request.api";
        }

        try {   //setting the curl parameters.
            $ch = curl_init();
            if (false === $ch) {
                throw new Exception('failed to initialize');
            }
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml'));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml->asXML());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);    //for production, set value to true or 1
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);    //for production, set value to 2
            curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
            $content = curl_exec($ch);
            $content = str_replace('xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd"', '', $content);

            $hostedPaymentResponse = new SimpleXMLElement($content);

            if (false === $content) {
                throw new Exception(curl_error($ch), curl_errno($ch));
            }
            curl_close($ch);
        } catch (Exception $e) {
            trigger_error(sprintf('Curl failed with error #%d: %s', $e->getCode(), $e->getMessage()), E_USER_ERROR);
        }

        $process_button_string = '';

        $process_button_string .= tep_draw_hidden_field('token', $hostedPaymentResponse->token);

        return $process_button_string;
    }

    function before_process() {
        $hash_array = explode("-", $_GET['hash']);

        $tmpOrderID = $hash_array[1];

        if ($tmpOrderID) {
            $tmpOrder = $this->manager->getParentToInstanceWithId('\common\classes\TmpOrder', $tmpOrderID);

            $tmpOrder_info_total = $this->format_raw($tmpOrder->info['total_inc_tax']);

            $hash_after = md5($tmpOrder_info_total . $tmpOrder->customer['email_address'] . $tmpOrder->billing['postcode']);

            if ($hash_array[0] != $hash_after) {
                tep_redirect(tep_href_link(FILENAME_CHECKOUT_CONFIRMATION, '', 'SSL'));
            }

            $this->after_process();
        }
        tep_redirect(tep_href_link(FILENAME_CHECKOUT_CONFIRMATION, '', 'SSL'));
    }

    function after_process() {

        $tmpOrder = $this->manager->getParentToInstance('\common\classes\TmpOrder');

        $tmpOrder->info['order_status'] = $this->order_status;

        $tmpOrderID = $tmpOrder->order_id;
        //{{
        global $db_link;
        if (!@mysqli_query($db_link, "insert into orders_check (tmp_orders_id, date_time_added) values ('" . tep_db_input($tmpOrderID) . "', now())")) {
          tep_db_query("update orders_check set debug_info = concat(debug_info, now(), ' - ', '" . tep_db_input(@mysqli_error($db_link)) . "', '\n') where tmp_orders_id = '" . tep_db_input($tmpOrderID) . "'");
          $i=0;
          do {
            sleep(1);
            $i++;
            $query_check = "select orders_id from orders_check where tmp_orders_id='".$tmpOrderID."'";
            $result_check = tep_db_query($query_check);
            $array_check =tep_db_fetch_array($result_check);
            $orders_id = $array_check['orders_id'];
            
          } while ($i<3 && (int)$orders_id==0);
        }
        //}}
        $query_check = "select orders_id from orders where invoice_id='".$tmpOrderID."'";
        $result_check = tep_db_query($query_check);
        if(tep_db_num_rows($result_check)>0) {
          $array_check = tep_db_fetch_array($result_check);
          $orders_id = $array_check['orders_id'];
        } else {
        $tmpOrder->save_order($tmpOrderID);
        $orders_id = $tmpOrder->createOrder();
        $this->manager->getTotalCollection()->apply_credit();
          //{{
          $query_u = "update orders_check set orders_id='".$orders_id."' where tmp_orders_id='".$tmpOrderID."'";
          $result_check = tep_db_query($query_u);
          //}}
        }
        if (tep_session_is_registered('anh_card_id'))
            tep_session_unregister('anh_card_id');

        $this->manager->clearAfterProcess();

        tep_redirect(tep_href_link(FILENAME_CHECKOUT_SUCCESS, 'order_id=' . $orders_id, 'SSL'));
    }

    function get_error() {

    }

    function isAffiliateSupported() {
        return true;
    }

    public function describe_status_key() {
        return new ModuleStatus('MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_STATUS', 'True', 'False');
    }

    public function describe_sort_key() {
        return new ModuleSortOrder('MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_SORT_ORDER');
    }

    public function configure_keys() {

        $status_id = defined('MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_ORDER_STATUS_ID') ? MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_ORDER_STATUS_ID : $this->getDefaultOrderStatusId();
        $status_id_b = defined('MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_ORDER_STATUS_ID_BEFORE') ? MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_ORDER_STATUS_ID_BEFORE : $this->getDefaultOrderStatusId();

        return array(
            'MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_STATUS' => array(
                'title' => 'Enable Authorize.net Server Integration Method',
                'description' => 'Do you want to accept Authorize.net Server Integration Method payments?',
                'value' => 'True',
                'sort_order' => '1',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
            ),
            'MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_LOGIN_ID' => array(
                'title' => 'API Login ID',
                'description' => 'The API Login ID used for the Authorize.net service',
                'sort_order' => '2',
            ),
            'MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_TRANSACTION_KEY' => array(
                'title' => 'API Transaction Key',
                'description' => 'The API Transaction Key used for the Authorize.net service',
                'sort_order' => '3',
            ),
            'MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_TRANSACTION_TYPE' => array(
                'title' => 'Transaction Type',
                'description' => 'The processing method to use for each transaction.',
                'value' => 'authCaptureTransaction',
                'sort_order' => '4',
                'set_function' => 'tep_cfg_select_option(array(\'authCaptureTransaction\', \'authOnlyTransaction\'), ',
            ),
            'MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_ORDER_STATUS_ID_BEFORE' => array(
                'title' => 'Set Order Status Before Payment',
                'description' => 'Set the status of orders made with this payment module to this value before payment',
                'value' => $status_id_b,
                'sort_order' => '6',
                'set_function' => 'tep_cfg_pull_down_order_statuses(',
                'use_function' => '\\common\\helpers\\Order::get_order_status_name',
            ),
            'MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_ORDER_STATUS_ID' => array(
                'title' => 'Set Order Status',
                'description' => 'Set the status of orders made with this payment module to this value',
                'value' => $status_id,
                'sort_order' => '6',
                'set_function' => 'tep_cfg_pull_down_order_statuses(',
                'use_function' => '\\common\\helpers\\Order::get_order_status_name',
            ),
            'MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_ZONE' => array(
                'title' => 'Payment Zone',
                'description' => 'If a zone is selected, only enable this payment method for that zone.',
                'value' => '0',
                'sort_order' => '8',
                'use_function' => '\\common\\helpers\\Zones::get_zone_class_title',
                'set_function' => 'tep_cfg_pull_down_zone_classes(',
            ),
            'MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_TRANSACTION_SERVER' => array(
                'title' => 'Transaction Server',
                'description' => 'Perform transactions on the live or test server. The test server should only be used by developers with Authorize.net test accounts.',
                'value' => 'Test',
                'sort_order' => '9',
                'set_function' => 'tep_cfg_select_option(array(\'Live\', \'Test\'), '
            ),
            'MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_BACKGROUND_COLOR' => array(
                'title' => 'Payment Page - Background color',
                'value' => 'Green',
                'description' => '',
                'sort_order' => '11',
            ),
            'MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_SORT_ORDER' => array(
                'title' => 'Sort order of display.',
                'description' => 'Sort order of display. Lowest is displayed first.',
                'value' => '0',
                'sort_order' => '16',
            )
        );
    }

    // format prices without currency formatting
    function format_raw($number, $currency_code = '', $currency_value = '') {
        $currencies = \Yii::$container->get('currencies');

        if (empty($currency_code) || !$currencies->is_set($currency_code)) {
            $currency_code = \Yii::$app->settings->get('currency');
        }

        if (empty($currency_value) || !is_numeric($currency_value)) {
            $currency_value = $currencies->currencies[$currency_code]['value'];
        }

        return number_format(round($number * $currency_value, $currencies->currencies[$currency_code]['decimal_places']), $currencies->currencies[$currency_code]['decimal_places'], '.', '');
    }

    function sendDebugEmail($response = array()) {

    }

    function tep_get_ip_address($single_ip = false) {
        $ip = \common\helpers\System::get_ip_address();
        if ($single_ip) {
            preg_match_all("/\d+\.\d+\.\d+\.\d+/i", $ip, $regs);
            if (tep_not_null($regs[0][count($regs[0]) - 1])) {
                $ip = $regs[0][count($regs[0]) - 1];
            } elseif (tep_not_null($regs[0][0])) {
                $ip = $regs[0][0];
            }
        }

        return $ip;
    }

    public function isOnline() {
        return true;
    }

    public function call_webhooks() {
        \common\helpers\Translation::init('payment');
        if ( !defined('MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_STATUS') || (MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_STATUS  != 'True') ) {
            exit;
        }
        $payload = file_get_contents("php://input");
        if($payload=="") {
          exit;
        }
        $data = json_decode($payload,true);
        $transId = $data['payload']['id'];
        if($transId!="") {

          $Module_Payment_Authorizenet_Accept_Hosted_Login_ID = MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_LOGIN_ID;
          $Module_Payment_Authorizenet_Accept_Hosted_Transaction_Key = MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_TRANSACTION_KEY;

          $transId = $data['payload']['id'];

          $xmlStr = '<?xml version="1.0" encoding="utf-8"?>
                      <getTransactionDetailsRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
                          <merchantAuthentication>
                            <name>'.$Module_Payment_Authorizenet_Accept_Hosted_Login_ID.'</name>
                            <transactionKey>'.$Module_Payment_Authorizenet_Accept_Hosted_Transaction_Key.'</transactionKey>
                          </merchantAuthentication>
                          <transId>'.$transId.'</transId>
                      </getTransactionDetailsRequest>';
          $xml = simplexml_load_string($xmlStr, 'SimpleXMLElement', LIBXML_NOWARNING);

          if (MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_TRANSACTION_SERVER == 'Live') {
              $url = "https://api.authorize.net/xml/v1/request.api";
          } else {
              $url = "https://apitest.authorize.net/xml/v1/request.api";
          }
          
          try {   //setting the curl parameters.
              $ch = curl_init();
              if (false === $ch) {
                  throw new Exception('failed to initialize');
              }
              curl_setopt($ch, CURLOPT_URL, $url);
              curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml'));
              curl_setopt($ch, CURLOPT_POSTFIELDS, $xml->asXML());
              curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
              curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
              curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);    //for production, set value to true or 1
              curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);    //for production, set value to 2
              curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
              $content = curl_exec($ch);
              $content = str_replace('xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd"', '', $content);
              if (false === $content) {
                      throw new Exception(curl_error($ch), curl_errno($ch));
              }
              curl_close($ch);
          } catch (Exception $e) {
                  //mail("egerasev@holbi.co.uk","daa_authorize_net_call_webhooks exception ",\common\classes\platform::currentId()."\n".sprintf('Curl failed with error #%d: %s', $e->getCode(), $e->getMessage()));
                  trigger_error(sprintf('Curl failed with error #%d: %s', $e->getCode(), $e->getMessage()), E_USER_ERROR);
                  Header("HTTP/1.0 200 OK");
                  die();
          }
          $doc = new DOMDocument(/*'1.0', 'UTF-8'*/);
          
          $reader = new XMLReader();
          $reader->xml($content);
          $current_node = 0;
          $reader->read();
          $transaction_node = simplexml_import_dom($doc->importNode($reader->expand(), true));
          $transaction_array = $this->parseSimpleXML($transaction_node);
          unset($transaction_node);
          $response = array();
          $resultCode = $transaction_array['messages']['resultCode'];
          $message_code = $transaction_array['messages']['message']['code'];
          $message_text = $transaction_array['messages']['message']['text'];
          $transId = $transaction_array['transaction']['transId'];
          $query_i = "insert into authorize_net_webhooks_logs(platform_id,payload,date_time_added,resultCode,message_code,TransactionDetailsResponse)values('".(int)\common\classes\platform::currentId()."','".serialize($data)."',now(),'".tep_db_input($resultCode)."','".tep_db_input($message_code)."','".serialize($transaction_array)."')";
          tep_db_query($query_i);
          
            
          if($resultCode == "Error") {
          
          } elseif($transId!="") {
          
            
            $response[] = "Response: ".$message_text . "(".$message_code.")";
            $response[] = "Transaction ID: ".$transId;

            $AVSResponse = $transaction_array['transaction']['AVSResponse'];
            $avs_response = '?';
            if($AVSResponse!="") {
              if ( defined('MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_TEXT_AVS_' . $AVSResponse) ) {
                $avs_response = constant('MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_TEXT_AVS_' . $AVSResponse) . ' (' . $AVSResponse . ')';
              } else {
                $avs_response = $AVSResponse;
              }
            }
            $response[] = 'AVS: ' . tep_db_prepare_input($avs_response);

            $cardCodeResponse = $transaction_array['transaction']['cardCodeResponse'];
            $cvv2_response = '?';
            if($cardCodeResponse!="") {
              if ( defined('MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_TEXT_CVV2_' . $cardCodeResponse) ) {
                $cvv2_response = constant('MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_TEXT_CVV2_' . $cardCodeResponse) . ' (' . $cardCodeResponse . ')';
              } else {
                $cvv2_response = $cardCodeResponse;
              }
            }
            $response[] = 'Card Code: ' . tep_db_prepare_input($cvv2_response);

            $CAVVResponse = $transaction_array['transaction']['CAVVResponse'];
            $cavv_response = '?';
            if($CAVVResponse!="") {
              if ( defined('MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_TEXT_CAVV_' . $CAVVResponse) ) {
                $cavv_response = constant('MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_TEXT_CAVV_' . $CAVVResponse) . ' (' . $CAVVResponse . ')';
              } else {
                $cavv_response = $CAVVResponse;
              }
            }
            $response[] = 'Card Holder: ' . tep_db_prepare_input($cavv_response);

            $invoice_id = $transaction_array['transaction']['order']['invoiceNumber'];
            if($invoice_id!="") {
              $status = MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_ORDER_STATUS_ID;
              $comments = "";
              $comments = implode("\n", $response);
              //{{
              $query_o = "select orders_id from tmp_orders where invoice_id='".$invoice_id."'";
              $result_o = tep_db_query($query_o);
              if(tep_db_num_rows($result_o)==1) {
                $array_o = tep_db_fetch_array($result_o);
                $orders_id = $array_o['orders_id'];
                $query_i = "insert into tmp_orders_status_history (orders_id,orders_status_id,date_added,comments) values('".(int)$orders_id."','".(int)$status."',now(),'".tep_db_input($comments)."')";
                tep_db_query($query_i);
              } elseif(tep_db_num_rows($result_o)>1) {
                //mail("egerasev@holbi.co.uk","daa_authorize_net_call_webhooks too many records found",$query_o);
              } else {
                exit;
              }
              //}}
              $query_o = "select orders_id from ".TABLE_ORDERS."  where invoice_id='".$invoice_id."'";
              $result_o = tep_db_query($query_o);
              if(tep_db_num_rows($result_o)==1) {
                $array_o = tep_db_fetch_array($result_o);
                $orders_id = $array_o['orders_id'];
                $query_i = "insert into ".TABLE_ORDERS_STATUS_HISTORY." (orders_id,orders_status_id,date_added,comments) values('".(int)$orders_id."','".(int)$status."',now(),'".tep_db_input($comments)."')";
                tep_db_query($query_i);
                exit;
              } elseif(tep_db_num_rows($result_o)>1) {
                //mail("egerasev@holbi.co.uk","daa_authorize_net_call_webhooks too many records found",$query_o);
              } else {
              //{{
                global $db_link;
                if (!@mysqli_query($db_link, "insert into orders_check (tmp_orders_id, date_time_added) values ('" . tep_db_input($invoice_id) . "', now())")) {
                  tep_db_query("update orders_check set debug_info = concat(debug_info, now(), ' - ', '" . tep_db_input(@mysqli_error($db_link)) . "', '\n') where tmp_orders_id = '" . tep_db_input($invoice_id) . "'");
                  $i=0;
                  do {
                    sleep(1);
                    $i++;
                    $query_check = "select orders_id from orders_check where tmp_orders_id='".$invoice_id."'";
                    $result_check = tep_db_query($query_check);
                    $array_check =tep_db_fetch_array($result_check);
                    $orders_id = $array_check['orders_id'];
                  } while ($i<3 && (int)$orders_id==0);
                }
                //}}
                tep_db_query("update tmp_orders set orders_status = '" . tep_db_input($status) . "', last_modified = now() where invoice_id='".$invoice_id."'");
                            
                Yii::$app->get('platform')->config($orders['platform_id'])->constant_up();
        
                $manager = \common\services\OrderManager::loadManager(new \common\classes\shopping_cart);
                $tmpOrder = new \common\classes\TmpOrder($invoice_id);
                $tmpOrder->manager = $manager;
                $manager->createOrderInstance('\common\classes\Order');
                $orders_id = $tmpOrder->createOrder(true,false);
                //{{
                $query_u = "update orders_check set orders_id='".$orders_id."' where tmp_orders_id='".$invoice_id."'";
                $result_check = tep_db_query($query_u);
                //}}
                $query_i = "insert into ".TABLE_ORDERS_STATUS_HISTORY." (orders_id,orders_status_id,date_added,comments) values('".(int)$orders_id."','".(int)$status."',now(),'".tep_db_input($comments)."')";
                tep_db_query($query_i);
                exit;
              }
            }
          }
        }
        exit;
    }

    function parseSimpleXML($xmldata) {
      $childNames = array();
      $children = array();

      if( count($xmldata) !== 0 ) {
        foreach( $xmldata->children() AS $child ) {
          $name = $child->getName();
          if( !isset($childNames[$name]) ) {
            $childNames[$name] = 0;
          }
          $childNames[$name]++;
          $children[$name][] = $this->parseSimpleXML($child);
        }
      }
      $returndata = array();
      if( count($childNames) > 0 ) {
        foreach( $childNames AS $name => $count ) {
          if( $count === 1 ) {
            $returndata[$name] = $children[$name][0];
          } else {
            $returndata[$name] = array();
            $counter = 0;
            foreach( $children[$name] AS $data ) {
              $returndata[$name][$counter] = $data;
              $counter++;
            }
          }
        }
      } else {
        $returndata = (string)$xmldata;
      }
      return $returndata;
    }
}
