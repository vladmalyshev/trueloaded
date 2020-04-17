<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

namespace common\modules\orderPayment;

use common\classes\modules\ModulePayment;
use common\classes\modules\ModuleStatus;
use common\classes\modules\ModuleSortOrder;

  class rbsworldpay_hosted extends ModulePayment {
    var $code, $title, $description, $enabled;

    protected $defaultTranslationArray = [
        'MODULE_PAYMENT_RBSWORLDPAY_HOSTED_TEXT_TITLE' => 'WorldPay Hosted Payment Pages',
        'MODULE_PAYMENT_RBSWORLDPAY_HOSTED_TEXT_PUBLIC_TITLE' => 'Credit Card',
        'MODULE_PAYMENT_RBSWORLDPAY_HOSTED_TEXT_DESCRIPTION' => '<img src="images/icon_info.gif" border="0" />&nbsp;<a href="http://library.oscommerce.com/Package&en&worldpay&oscom23&hosted" target="_blank" style="text-decoration: underline; font-weight: bold;">View Online Documentation</a><br /><br /><img src="images/icon_popup.gif" border="0">&nbsp;<a href="http://www.worldpay.com" target="_blank" style="text-decoration: underline; font-weight: bold;">Visit WorldPay Website</a>',

        'MODULE_PAYMENT_RBSWORLDPAY_HOSTED_ERROR_ADMIN_CONFIGURATION' => 'This module will not load until the Installation ID parameter has been configured. Please edit and configure the settings of this module.',

        'MODULE_PAYMENT_RBSWORLDPAY_HOSTED_TEXT_WARNING_DEMO_MODE' => 'Transaction performed in test mode.',

        'MODULE_PAYMENT_RBSWORLDPAY_HOSTED_TEXT_SUCCESSFUL_TRANSACTION' => 'The payment has been successfully performed! You will be automatically redirected back to our website in 3 seconds.',
        'MODULE_PAYMENT_RBSWORLDPAY_HOSTED_TEXT_CONTINUE_BUTTON' => 'Continue to %s'
    ];

    function __construct() {
        parent::__construct();

        $this->signature = 'rbs|worldpay_hosted|2.2|2.3';
        $this->api_version = '4.6';

        $this->code = 'rbsworldpay_hosted';
        $this->title = MODULE_PAYMENT_RBSWORLDPAY_HOSTED_TEXT_TITLE;
        $this->public_title = MODULE_PAYMENT_RBSWORLDPAY_HOSTED_TEXT_PUBLIC_TITLE;
        $this->description = MODULE_PAYMENT_RBSWORLDPAY_HOSTED_TEXT_DESCRIPTION;
        if (!defined('MODULE_PAYMENT_RBSWORLDPAY_HOSTED_STATUS')) {
            $this->enabled = false;
            return;
        }
        $this->sort_order = defined('MODULE_PAYMENT_RBSWORLDPAY_HOSTED_SORT_ORDER') ? MODULE_PAYMENT_RBSWORLDPAY_HOSTED_SORT_ORDER : 0;
        $this->enabled = defined('MODULE_PAYMENT_RBSWORLDPAY_HOSTED_STATUS') && (MODULE_PAYMENT_RBSWORLDPAY_HOSTED_STATUS == 'True') ? true : false;
        $this->order_status = defined('MODULE_PAYMENT_RBSWORLDPAY_HOSTED_PREPARE_ORDER_STATUS_ID') && ((int)MODULE_PAYMENT_RBSWORLDPAY_HOSTED_PREPARE_ORDER_STATUS_ID > 0) ? (int)MODULE_PAYMENT_RBSWORLDPAY_HOSTED_PREPARE_ORDER_STATUS_ID : 0;

      if ( defined('MODULE_PAYMENT_RBSWORLDPAY_HOSTED_STATUS') ) {
        if ( MODULE_PAYMENT_RBSWORLDPAY_HOSTED_TESTMODE == 'True' ) {
          $this->title .= ' [Test]';
          $this->public_title .= ' (' . $this->code . '; Test)';
        }

        if ( MODULE_PAYMENT_RBSWORLDPAY_HOSTED_TESTMODE == 'True' ) {
          $this->form_action_url = 'https://secure-test.worldpay.com/wcc/purchase';
        } else {
          $this->form_action_url = 'https://secure.worldpay.com/wcc/purchase';
        }
      }

      if ( $this->enabled === true ) {
        if ( !tep_not_null(MODULE_PAYMENT_RBSWORLDPAY_HOSTED_INSTALLATION_ID) ) {
          $this->description = '<div class="secWarning">' . MODULE_PAYMENT_RBSWORLDPAY_HOSTED_ERROR_ADMIN_CONFIGURATION . '</div>' . $this->description;

          $this->enabled = false;
        }
      }

      if ( $this->enabled === true ) {
          $this->update_status();
      }
    }

    function update_status() {
      global $order;

      if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_RBSWORLDPAY_HOSTED_ZONE > 0) ) {
        $check_flag = false;
        $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_RBSWORLDPAY_HOSTED_ZONE . "' and zone_country_id = '" . $this->billing['country']['id'] . "' order by zone_id");
        while ($check = tep_db_fetch_array($check_query)) {
          if ($check['zone_id'] < 1) {
            $check_flag = true;
            break;
          } elseif ($check['zone_id'] == $this->delivery['zone_id']) {
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
      global $cart_RBS_Worldpay_Hosted_ID;

      if (tep_session_is_registered('cart_RBS_Worldpay_Hosted_ID')) {
        $order_id = substr($cart_RBS_Worldpay_Hosted_ID, strpos($cart_RBS_Worldpay_Hosted_ID, '-')+1);

        $check_query = tep_db_query('select orders_id from ' . TABLE_ORDERS_STATUS_HISTORY . ' where orders_id = "' . (int)$order_id . '" limit 1');

        if (tep_db_num_rows($check_query) < 1) {
          tep_db_query('delete from ' . TABLE_ORDERS . ' where orders_id = "' . (int)$order_id . '"');
          tep_db_query('delete from ' . TABLE_ORDERS_TOTAL . ' where orders_id = "' . (int)$order_id . '"');
          tep_db_query('delete from ' . TABLE_ORDERS_STATUS_HISTORY . ' where orders_id = "' . (int)$order_id . '"');
          tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS . ' where orders_id = "' . (int)$order_id . '"');
          tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . ' where orders_id = "' . (int)$order_id . '"');
          tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS_DOWNLOAD . ' where orders_id = "' . (int)$order_id . '"');

          tep_session_unregister('cart_RBS_Worldpay_Hosted_ID');
        }
      }

      return array('id' => $this->code,
                   'module' => $this->public_title);
    }

    function pre_confirmation_check() {
      global $cart;

      if (empty($cart->cartID)) {
        $cart->cartID = $cart->generate_cart_id();
      }

      $this->manager->set('cartID', $cart->cartID);
    }

    function confirmation() {
      global $cart_RBS_Worldpay_Hosted_ID, $languages_id;

      $insert_order = false;
      $cartID =  $this->manager->get('cartID');
      $order = $this->manager->getOrderInstance();

      if (tep_session_is_registered('cart_RBS_Worldpay_Hosted_ID')) {
        $order_id = substr($cart_RBS_Worldpay_Hosted_ID, strpos($cart_RBS_Worldpay_Hosted_ID, '-')+1);

        $curr_check = tep_db_query("select currency from " . TABLE_ORDERS . " where orders_id = '" . (int)$order_id . "'");
        $curr = tep_db_fetch_array($curr_check);

        if ( ($curr['currency'] != $order->info['currency']) || ($cartID != substr($cart_RBS_Worldpay_Hosted_ID, 0, strlen($cartID))) ) {
          $check_query = tep_db_query('select orders_id from ' . TABLE_ORDERS_STATUS_HISTORY . ' where orders_id = "' . (int)$order_id . '" limit 1');

          if (tep_db_num_rows($check_query) < 1) {
            tep_db_query('delete from ' . TABLE_ORDERS . ' where orders_id = "' . (int)$order_id . '"');
            tep_db_query('delete from ' . TABLE_ORDERS_TOTAL . ' where orders_id = "' . (int)$order_id . '"');
            tep_db_query('delete from ' . TABLE_ORDERS_STATUS_HISTORY . ' where orders_id = "' . (int)$order_id . '"');
            tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS . ' where orders_id = "' . (int)$order_id . '"');
            tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . ' where orders_id = "' . (int)$order_id . '"');
            tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS_DOWNLOAD . ' where orders_id = "' . (int)$order_id . '"');
          }

          $insert_order = true;
        }
      } else {
        $insert_order = true;
      }

      if (parent::isPartlyPaid()){
        $cart_RBS_Worldpay_Hosted_ID = $cartID . '-' . ($order->parent_id? $order->parent_id : $order->order_id);
        tep_session_register('cart_RBS_Worldpay_Hosted_ID');
        return false;
      }

      if ($insert_order == true) {

        /*$order_totals = array();
        if (is_array($order_total_modules->modules)) {
          foreach ($order_total_modules->modules as $value) {
            $class = substr($value, 0, strrpos($value, '.'));
            if ($GLOBALS[$class]->enabled) {
              for ($i=0, $n=sizeof($GLOBALS[$class]->output); $i<$n; $i++) {
                if (tep_not_null($GLOBALS[$class]->output[$i]['title']) && tep_not_null($GLOBALS[$class]->output[$i]['text'])) {
                  $order_totals[] = array('code' => $GLOBALS[$class]->code,
                                          'title' => $GLOBALS[$class]->output[$i]['title'],
                                          'text' => $GLOBALS[$class]->output[$i]['text'],
                                          'value' => $GLOBALS[$class]->output[$i]['value'],
                                          'sort_order' => $GLOBALS[$class]->sort_order,
										  'text_exc_tax' => $GLOBALS[$class]->output[$i]['text_exc_tax'],
										  'text_inc_tax' => $GLOBALS[$class]->output[$i]['text_inc_tax'],
										  'value_exc_vat' => $GLOBALS[$class]->output[$i]['value_exc_vat'],
										  'value_inc_tax' => $GLOBALS[$class]->output[$i]['value_inc_tax'],
										  'is_removed' => 0,
										  'currency' => $order->info['currency'],
										  'currency_value' => $order->info['currency_value'],
										  );
                }
              }
            }
          }
        }*/

        $order->save_order();
        /*
        $sql_data_array = array('customers_id' => $customer_id,
                                'basket_id' => $order->info['basket_id'],
                                'customers_name' => $order->customer['firstname'] . ' ' . $order->customer['lastname'],
                                //{{ BEGIN FISTNAME
                                'customers_firstname' => $order->customer['firstname'],
                                'customers_lastname' => $order->customer['lastname'],
                                //}} END FIRSTNAME
                                'customers_company' => $order->customer['company'],
                                'customers_company_vat' => $order->customer['company_vat'],
                                'customers_company_vat_status' => $order->customer['company_vat_status'],
                                'customers_street_address' => $order->customer['street_address'],
                                'customers_suburb' => $order->customer['suburb'],
                                'customers_city' => $order->customer['city'],
                                'customers_postcode' => $order->customer['postcode'],
                                'customers_state' => $order->customer['state'],
                                'customers_country' => $order->customer['country']['title'],
                                'customers_telephone' => $order->customer['telephone'],
                                'customers_landline' => $order->customer['landline'],
                                'customers_email_address' => $order->customer['email_address'],
                                'customers_address_format_id' => $order->customer['format_id'],
                                'delivery_address_book_id'=> isset($order->delivery['address_book_id'])?$order->delivery['address_book_id']:0,
                                'delivery_gender' => $order->delivery['gender'],
                                'delivery_name' => $order->delivery['firstname'] . ' ' . $order->delivery['lastname'],
                                //{{ BEGIN FISTNAME
                                'delivery_firstname' => $order->delivery['firstname'],
                                'delivery_lastname' => $order->delivery['lastname'],
                                //}} END FIRSTNAME
                                //'delivery_company' => $order->delivery['company'],
                                'delivery_street_address' => $order->delivery['street_address'],
                                'delivery_suburb' => $order->delivery['suburb'],
                                'delivery_city' => $order->delivery['city'],
                                'delivery_postcode' => $order->delivery['postcode'],
                                'delivery_state' => $order->delivery['state'],
                                'delivery_country' => $order->delivery['country']['title'],
                                'delivery_address_format_id' => $order->delivery['format_id'],
                                'billing_address_book_id'=> isset($order->billing['address_book_id'])?$order->billing['address_book_id']:0,
                                'billing_gender' => $order->billing['gender'],
                                'billing_name' => $order->billing['firstname'] . ' ' . $order->billing['lastname'],
                                //{{ BEGIN FISTNAME
                                'billing_firstname' => $order->billing['firstname'],
                                'billing_lastname' => $order->billing['lastname'],
                                //}} END FIRSTNAME
                                //'billing_company' => $order->billing['company'],
                                'billing_street_address' => $order->billing['street_address'],
                                'billing_suburb' => $order->billing['suburb'],
                                'billing_city' => $order->billing['city'],
                                'billing_postcode' => $order->billing['postcode'],
                                'billing_state' => $order->billing['state'],
                                'billing_country' => $order->billing['country']['title'],
                                'billing_address_format_id' => $order->billing['format_id'],
                                'platform_id' => $order->info['platform_id'],
                                'payment_method' => $order->info['payment_method'],
                                'payment_info' => $GLOBALS['payment_info'],
                                'cc_type' => $order->info['cc_type'],
                                'cc_owner' => $order->info['cc_owner'],
                                'cc_number' => $order->info['cc_number'],
                                'cc_expires' => $order->info['cc_expires'],
                                'language_id' => (int)$languages_id,
                                'payment_class' => $order->info['payment_class'],
                                'shipping_class' => $order->info['shipping_class'],
                                'date_purchased' => 'now()',
                                'last_modified' => 'now()',
                                'search_engines_id' => isset($_SESSION['search_engines_id'])?(int)$_SESSION['search_engines_id']:0,
                                'search_words_id' => isset($_SESSION['search_words_id'])?(int)$_SESSION['search_words_id']:0,
                                'orders_status' => $order->info['order_status'],
                                'currency' => $order->info['currency'],
                                'currency_value' => $order->info['currency_value'],
                                'shipping_weight' => $order->info['shipping_weight'],
        );*/
        /*
        if (tep_session_is_registered('platform_code')) {
            global $platform_code;
            if (!empty($platform_code)) {
                $sql_data_array['platform_id'] = \Yii::$app->get('platform')->config()->getSattelitePlatformId($platform_code);
            }
        }

        tep_db_perform(TABLE_ORDERS, $sql_data_array);

        $insert_id = tep_db_insert_id();*/
        /*
        for ($i=0, $n=sizeof($order_totals); $i<$n; $i++) {
          $sql_data_array = array('orders_id' => $insert_id,
                                  'title' => $order_totals[$i]['title'],
                                  'text' => $order_totals[$i]['text'],
                                  'value' => $order_totals[$i]['value'],
                                  'class' => $order_totals[$i]['code'],
                                  'sort_order' => $order_totals[$i]['sort_order'],
                                  'text_exc_tax' => $order_totals[$i]['text_exc_tax'],
                                  'text_inc_tax' => $order_totals[$i]['text_inc_tax'],
								  'value_exc_vat' => $order_totals[$i]['value_exc_vat'],
								  'value_inc_tax' => $order_totals[$i]['value_inc_tax'],
								  'is_removed' => 0,
								  'currency' => $order->info['currency'],
								  'currency_value' => $order->info['currency_value'],
              );

          tep_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);
        }*/

        $order->save_details();

        $order->save_products();

        /*
        for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {
          $sql_data_array = array('orders_id' => $insert_id,
                                  'products_id' => \common\helpers\Inventory::get_prid($order->products[$i]['id']),
                                  'products_model' => $order->products[$i]['model'],
                                  'products_name' => $order->products[$i]['name'],
                                  'products_price' => $order->products[$i]['price'],
                                  'final_price' => $order->products[$i]['final_price'],
                                  'products_tax' => $order->products[$i]['tax'],
                                  'products_quantity' => $order->products[$i]['qty'],
                                  'is_giveaway' => $order->products[$i]['ga'],
                                  'is_virtual' => $order->products[$i]['is_virtual'],
                                  'gift_wrap_price' => $order->products[$i]['gift_wrap_price'],
                                  'gift_wrapped' => $order->products[$i]['gift_wrapped']?1:0,
                                  'gv_state' => $order->products[$i]['gv_state'],
                                  'uprid' => \common\helpers\Inventory::normalize_id($order->products[$i]['id'])
              );

          tep_db_perform(TABLE_ORDERS_PRODUCTS, $sql_data_array);

          $order_products_id = tep_db_insert_id();

          $attributes_exist = '0';
          if (isset($order->products[$i]['attributes'])) {
            $attributes_exist = '1';
            for ($j=0, $n2=sizeof($order->products[$i]['attributes']); $j<$n2; $j++) {
              if (DOWNLOAD_ENABLED == 'true') {
                $attributes_query = "select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix, pad.products_attributes_maxdays, pad.products_attributes_maxcount , pad.products_attributes_filename
                                     from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                     left join " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                     on pa.products_attributes_id=pad.products_attributes_id
                                     where pa.products_id = '" . $order->products[$i]['id'] . "'
                                     and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "'
                                     and pa.options_id = popt.products_options_id
                                     and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "'
                                     and pa.options_values_id = poval.products_options_values_id
                                     and popt.language_id = '" . $languages_id . "'
                                     and poval.language_id = '" . $languages_id . "'";
                $attributes = tep_db_query($attributes_query);
              } else {
                $attributes = tep_db_query("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa where pa.products_id = '" . $order->products[$i]['id'] . "' and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "' and pa.options_id = popt.products_options_id and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "' and pa.options_values_id = poval.products_options_values_id and popt.language_id = '" . $languages_id . "' and poval.language_id = '" . $languages_id . "'");
              }
              $attributes_values = tep_db_fetch_array($attributes);

              $sql_data_array = array('orders_id' => $insert_id,
                                      'orders_products_id' => $order_products_id,
                                      'products_options' => $attributes_values['products_options_name'],
                                      'products_options_values' => $attributes_values['products_options_values_name'],
                                      'options_values_price' => $attributes_values['options_values_price'],
                                      'price_prefix' => $attributes_values['price_prefix'],
                                      'products_options_id' => $order->products[$i]['attributes'][$j]['option_id'],
                                      'products_options_values_id' => $order->products[$i]['attributes'][$j]['value_id']);

              tep_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $sql_data_array);

              if ((DOWNLOAD_ENABLED == 'true') && isset($attributes_values['products_attributes_filename']) && tep_not_null($attributes_values['products_attributes_filename'])) {
                $sql_data_array = array('orders_id' => $insert_id,
                                        'orders_products_id' => $order_products_id,
                                        'orders_products_filename' => $attributes_values['products_attributes_filename'],
                                        'download_maxdays' => $attributes_values['products_attributes_maxdays'],
                                        'download_count' => $attributes_values['products_attributes_maxcount']);

                tep_db_perform(TABLE_ORDERS_PRODUCTS_DOWNLOAD, $sql_data_array);
              }
            }
          }
        }

        $sql_data_array = array('orders_id' => $insert_id,
                                'orders_status_id' => $order->info['order_status'],
                                'date_added' => 'now()',
                                'customer_notified' => '0',
                                'comments' => '');

        tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
        */
        $cart_RBS_Worldpay_Hosted_ID = $cartID . '-' . $order->order_id;
        tep_session_register('cart_RBS_Worldpay_Hosted_ID');
      }

      return false;
    }

    function process_button() {
      global /*$order,*/ $languages_id, $language, /*$customer_id, */$cart_RBS_Worldpay_Hosted_ID;

      $order_id = substr($cart_RBS_Worldpay_Hosted_ID, strpos($cart_RBS_Worldpay_Hosted_ID, '-')+1);

      $lang_query = tep_db_query("select code from " . TABLE_LANGUAGES . " where languages_id = '" . (int)$languages_id . "'");
      $lang = tep_db_fetch_array($lang_query);

      $this->paid = '';
      parent::process_button();
      $order = $this->manager->getOrderInstance();
      $currency = \Yii::$app->settings->get('currency');
      $customer_id = $this->manager->getCustomerAssigned();
      $process_button_string = tep_draw_hidden_field('instId', MODULE_PAYMENT_RBSWORLDPAY_HOSTED_INSTALLATION_ID) .
                               tep_draw_hidden_field('cartId', $order_id) .
                               tep_draw_hidden_field('amount', $this->format_raw($order->info['total'])) .
                               tep_draw_hidden_field('currency', $currency) .
                               tep_draw_hidden_field('desc', STORE_NAME) .
                               tep_draw_hidden_field('name', $order->billing['firstname'] . ' ' . $order->billing['lastname']) .
                               tep_draw_hidden_field('address1', $order->billing['street_address']) .
                               tep_draw_hidden_field('town', $order->billing['city']) .
                               tep_draw_hidden_field('region', $order->billing['state']) .
                               tep_draw_hidden_field('postcode', $order->billing['postcode']) .
                               tep_draw_hidden_field('country', $order->billing['country']['iso_code_2']) .
                               tep_draw_hidden_field('tel', $order->customer['telephone']) .
                               tep_draw_hidden_field('email', $order->customer['email_address']) .
                               tep_draw_hidden_field('fixContact', 'Y') .
                               tep_draw_hidden_field('hideCurrency', 'true') .
                               tep_draw_hidden_field('lang', strtoupper($lang['code'])) .
                               tep_draw_hidden_field('signatureFields', 'amount:currency:cartId') .
                               tep_draw_hidden_field('signature', md5(MODULE_PAYMENT_RBSWORLDPAY_HOSTED_MD5_PASSWORD . ':' . $this->format_raw($order->info['total']) . ':' . $currency . ':' . $order_id)) .
                               tep_draw_hidden_field('MC_callback', tep_href_link('callback/rbs-worldpay', '', 'SSL', false)) .
                               tep_draw_hidden_field('M_sid', tep_session_id()) .
                               tep_draw_hidden_field('M_cid', $customer_id) .
                               tep_draw_hidden_field('M_lang', $language) .
                               tep_draw_hidden_field('M_hash', md5(tep_session_id() . $customer_id . $order_id . $language . number_format($order->info['total'], 2) . MODULE_PAYMENT_RBSWORLDPAY_HOSTED_MD5_PASSWORD)).
                               tep_draw_hidden_field('M_paid', $this->paid);

      if (MODULE_PAYMENT_RBSWORLDPAY_HOSTED_TRANSACTION_METHOD == 'Pre-Authorization') {
        $process_button_string .= tep_draw_hidden_field('authMode', 'E');
      }

      if (MODULE_PAYMENT_RBSWORLDPAY_HOSTED_TESTMODE == 'True') {
        $process_button_string .= tep_draw_hidden_field('testMode', '100');
      }

      return $process_button_string;
    }

    function before_process() {
      //global $customer_id, $language, $order, $order_totals, $sendto, $billto, $languages_id, $payment, $cart, $cart_RBS_Worldpay_Hosted_ID;
      //global $$payment;
      global $language, $languages_id, $cart, $cart_RBS_Worldpay_Hosted_ID;

      $currencies = \Yii::$container->get('currencies');

      $order_id = substr($cart_RBS_Worldpay_Hosted_ID, strpos($cart_RBS_Worldpay_Hosted_ID, '-')+1);
      $customer_id = $this->manager->getCustomerAssigned();
      $order = $this->manager->getOrderInstance();

      if (!isset($_GET['hash']) || ($_GET['hash'] != md5(tep_session_id() . $customer_id . $order_id . $language . number_format($order->info['total'], 2) . MODULE_PAYMENT_RBSWORLDPAY_HOSTED_MD5_PASSWORD))) {
        $this->sendDebugEmail();

        tep_redirect(tep_href_link(FILENAME_SHOPPING_CART));
      }

      $check_query = tep_db_query("select orders_status from " . TABLE_ORDERS . " where orders_id = '" . (int)$order_id . "' and customers_id = '" . (int)$customer_id . "'");

      if (!tep_db_num_rows($check_query)) {
        $this->sendDebugEmail();

        tep_redirect(tep_href_link(FILENAME_SHOPPING_CART));
      }

      $check = tep_db_fetch_array($check_query);

      // {{ transaction id
      if ( isset($_GET['transId']) && !empty($_GET['transId']) ){
          $transaction_id = tep_db_prepare_input($_GET['transId']);
          tep_db_query("update " . TABLE_ORDERS . " set transaction_id = '" . tep_db_input($transaction_id) . "' where orders_id = '" . (int)$order_id . "'");
      }
      // }} transaction id
      $order_status_id = (MODULE_PAYMENT_RBSWORLDPAY_HOSTED_ORDER_STATUS_ID > 0 ? (int)MODULE_PAYMENT_RBSWORLDPAY_HOSTED_ORDER_STATUS_ID : (int)DEFAULT_ORDERS_STATUS_ID);

      if ($order_status_id){
        tep_db_query("update " . TABLE_ORDERS . " set orders_status = '" . $order_status_id . "', last_modified = now() where orders_id = '" . (int)$order_id . "'");
      }

      if ($check['orders_status'] == MODULE_PAYMENT_RBSWORLDPAY_HOSTED_PREPARE_ORDER_STATUS_ID) {

        $sql_data_array = array('orders_id' => $order_id,
                                'orders_status_id' => $order_status_id,
                                'date_added' => 'now()',
                                'customer_notified' => (SEND_EMAILS == 'true') ? '1' : '0',
                                'comments' => $order->info['comments']);

        tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
      } else {
        $order_status_query = tep_db_query("select orders_status_history_id from " . TABLE_ORDERS_STATUS_HISTORY . " where orders_id = '" . (int)$order_id . "' and orders_status_id = '" . (int)$order_status_id . "' and comments = '' order by date_added desc limit 1");

        if (tep_db_num_rows($order_status_query)) {
          $order_status = tep_db_fetch_array($order_status_query);

          $sql_data_array = array('customer_notified' => (SEND_EMAILS == 'true') ? '1' : '0',
                                  'comments' => $order->info['comments']);

          tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array, 'update', "orders_status_history_id = '" . (int)$order_status['orders_status_history_id'] . "'");
        }
      }
      $order->order_id = $order_id;
      $order->update_piad_information();

      $order->info['order_status'] = $order_status_id;

      $trans_result = 'WorldPay: Transaction Verified';

      if (MODULE_PAYMENT_RBSWORLDPAY_HOSTED_TESTMODE == 'True') {
        $trans_result .= "\n" . MODULE_PAYMENT_RBSWORLDPAY_HOSTED_TEXT_WARNING_DEMO_MODE;
      }

      $order->info['comments'] = $trans_result;
      $order->status = 'new';
      $order->save_details();

      /*
      $sql_data_array = array('orders_id' => $order_id,
                              'orders_status_id' => MODULE_PAYMENT_RBSWORLDPAY_HOSTED_TRANSACTIONS_ORDER_STATUS_ID,
                              'date_added' => 'now()',
                              'customer_notified' => '0',
                              'comments' => $trans_result);

      tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
      */
// initialized for the email confirmation
      $products_ordered = '';
      for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {
// Stock Update - Joao Correia
        if (STOCK_LIMITED == 'true') {
          if (DOWNLOAD_ENABLED == 'true') {
            $stock_query_raw = "SELECT products_quantity, pad.products_attributes_filename
                                FROM " . TABLE_PRODUCTS . " p
                                LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                ON p.products_id=pa.products_id
                                LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                ON pa.products_attributes_id=pad.products_attributes_id
                                WHERE p.products_id = '" . \common\helpers\Inventory::get_prid($order->products[$i]['id']) . "'";
// Will work with only one option for downloadable products
// otherwise, we have to build the query dynamically with a loop
            $products_attributes = $order->products[$i]['attributes'];
            if (is_array($products_attributes)) {
              $stock_query_raw .= " AND pa.options_id = '" . $products_attributes[0]['option_id'] . "' AND pa.options_values_id = '" . $products_attributes[0]['value_id'] . "'";
            }
            $stock_query = tep_db_query($stock_query_raw);
          } else {
            $stock_query = tep_db_query("select products_quantity from " . TABLE_PRODUCTS . " where products_id = '" . \common\helpers\Inventory::get_prid($order->products[$i]['id']) . "'");
          }
          if (tep_db_num_rows($stock_query) > 0) {
            $stock_values = tep_db_fetch_array($stock_query);
// do not decrement quantities if products_attributes_filename exists
            if ((DOWNLOAD_ENABLED != 'true') || (!$stock_values['products_attributes_filename'])) {
              $stock_left = $stock_values['products_quantity'] - $order->products[$i]['qty'];
            } else {
              $stock_left = $stock_values['products_quantity'];
            }
            tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity = '" . $stock_left . "' where products_id = '" . \common\helpers\Inventory::get_prid($order->products[$i]['id']) . "'");
            if ( ($stock_left < 1) && (STOCK_ALLOW_CHECKOUT == 'false') ) {
              tep_db_query("update " . TABLE_PRODUCTS . " set products_status = '0' where products_id = '" . \common\helpers\Inventory::get_prid($order->products[$i]['id']) . "'");
            }
          }
        }

// Update products_ordered (for bestsellers list)
        tep_db_query("update " . TABLE_PRODUCTS . " set products_ordered = products_ordered + " . sprintf('%d', $order->products[$i]['qty']) . " where products_id = '" . \common\helpers\Inventory::get_prid($order->products[$i]['id']) . "'");

//------insert customer choosen option to order--------
        $attributes_exist = '0';
        $products_ordered_attributes = '';
        if (isset($order->products[$i]['attributes'])) {
          $attributes_exist = '1';
          for ($j=0, $n2=sizeof($order->products[$i]['attributes']); $j<$n2; $j++) {
            if (DOWNLOAD_ENABLED == 'true') {
              $attributes_query = "select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix, pad.products_attributes_maxdays, pad.products_attributes_maxcount , pad.products_attributes_filename
                                   from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                   left join " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                   on pa.products_attributes_id=pad.products_attributes_id
                                   where pa.products_id = '" . $order->products[$i]['id'] . "'
                                   and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "'
                                   and pa.options_id = popt.products_options_id
                                   and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "'
                                   and pa.options_values_id = poval.products_options_values_id
                                   and popt.language_id = '" . $languages_id . "'
                                   and poval.language_id = '" . $languages_id . "'";
              $attributes = tep_db_query($attributes_query);
            } else {
              $attributes = tep_db_query("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa where pa.products_id = '" . $order->products[$i]['id'] . "' and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "' and pa.options_id = popt.products_options_id and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "' and pa.options_values_id = poval.products_options_values_id and popt.language_id = '" . $languages_id . "' and poval.language_id = '" . $languages_id . "'");
            }
            $attributes_values = tep_db_fetch_array($attributes);

            $products_ordered_attributes .= "\n\t" . $attributes_values['products_options_name'] . ' ' . $attributes_values['products_options_values_name'];
          }
        }
        $order->products[$i]['tpl_attributes'] = $products_ordered_attributes;
        $order->products[$i]['tpl_price'] = $currencies->display_price($order->products[$i]['final_price'], $order->products[$i]['tax'], $order->products[$i]['qty']);
      }

        //global $order_total_modules, $insert_id;
      $order_total_modules = $this->manager->getTotalCollection();
      if (is_object($order_total_modules)){
            $order_total_modules->apply_credit();
        }

// lets start with the email confirmation
      // build the message content
        /*
        $email_params = array();
        $email_params['STORE_NAME'] = STORE_NAME;
        $email_params['ORDER_NUMBER'] = $order_id;
        $email_params['ORDER_DATE_SHORT'] = strftime(DATE_FORMAT_SHORT);
        $email_params['ORDER_INVOICE_URL'] = \common\helpers\Output::get_clickable_link(tep_href_link(FILENAME_ACCOUNT_HISTORY_INFO, 'order_id=' . $order_id, 'SSL', false));
        $email_params['ORDER_DATE_LONG'] = strftime(DATE_FORMAT_LONG);
        if ($ext = \common\helpers\Acl::checkExtension('DelayedDespatch', 'mailInfo')){
            $email_params['ORDER_DATE_LONG'] .= $ext::mailInfo($order->info['delivery_date']);
        }
        $email_params['PRODUCTS_ORDERED'] = substr($products_ordered, 0 , -1);

        $email_params['ORDER_TOTALS'] = '';

        $order_total_output = [];
        foreach ($order_totals as $total) {
            if (class_exists($total['code'])) {
                if (method_exists($GLOBALS[$total['code']], 'visibility')) {
                    if (true == $GLOBALS[$total['code']]->visibility(PLATFORM_ID, 'TEXT_EMAIL') ) {
                        if (method_exists($GLOBALS[$total['code']], 'visibility')) {
                            $order_total_output[]  = $GLOBALS[$total['code']]->displayText(PLATFORM_ID, 'TEXT_EMAIL', $total);
                        } else {
                            $order_total_output[] = $total;
                        }
                    }
                }
            }
        }

        parent::before_process();



        for ($i=0, $n=sizeof($order_total_output); $i<$n; $i++) {
            $email_params['ORDER_TOTALS'] .= strip_tags($order_total_output[$i]['title']) . ' ' . strip_tags($order_total_output[$i]['text']) . "\n";
        }
        $email_params['ORDER_TOTALS'] = substr($email_params['ORDER_TOTALS'], 0 , -1);
        $email_params['BILLING_ADDRESS'] = \common\helpers\Address::address_label($customer_id, $billto, 0, '', "\n");
        $email_params['DELIVERY_ADDRESS'] = ($order->content_type != 'virtual' ? \common\helpers\Address::address_label($customer_id, $sendto, 0, '', "\n") : '');
        $payment_method = '';
        if ( !empty($payment)  && is_object($GLOBALS[$payment]) ) {
            $payment_method = $GLOBALS[$payment]->title;
            if ($GLOBALS[$payment]->email_footer) {
                $payment_method .= "\n\n" . $GLOBALS[$payment]->email_footer;
            }
        }
        $email_params['PAYMENT_METHOD'] = $payment_method;

        $email_params['ORDER_COMMENTS'] = tep_db_output($order->info['comments']);

        $emailTemplate = '';
        $ostatus = tep_db_fetch_array(tep_db_query("select orders_status_template_confirm from " . TABLE_ORDERS_STATUS . " where language_id = '" . (int) $this->info['language_id'] . "' and orders_status_id='" . (int) $this->info['order_status'] . "' LIMIT 1 "));
        if (!empty($ostatus['orders_status_template_confirm'])) {
            $get_template_r = tep_db_query("select * from " . TABLE_EMAIL_TEMPLATES . " where email_templates_key='" . tep_db_input($ostatus['orders_status_template_confirm']) . "'");
            if (tep_db_num_rows($get_template_r) > 0) {
                $emailTemplate = $ostatus['orders_status_template_confirm'];
            }
        }
        if (!empty($emailTemplate)) {
            list($email_subject, $email_text) = \common\helpers\Mail::get_parsed_email_template($emailTemplate, $email_params);

            \common\helpers\Mail::send(
                $order->customer['firstname'] . ' ' . $order->customer['lastname'], $order->customer['email_address'],
                $email_subject, $email_text,
                STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS
            );
            if (SEND_EXTRA_ORDER_EMAILS_TO == '') {
                \common\helpers\Mail::send(STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, $email_subject, $email_text, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
            } else {
                \common\helpers\Mail::send(STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, $email_subject, $email_text, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, array(), 'CC: ' . SEND_EXTRA_ORDER_EMAILS_TO);
            }
        }
         *
         */
        $products_ordered = \frontend\design\boxes\email\OrderProducts::widget(['params' => ['products' => $order->products, 'platform_id' => $order->info['platform_id']]]);
        $order->notify_customer($products_ordered);

// load the after_process function from the payment modules
        $this->after_process();

        $this->manager->clearAfterProcess();

        tep_session_unregister('cart_RBS_Worldpay_Hosted_ID');

        if($ext = \common\helpers\Acl::checkExtension('ReferFriend', 'rf_after_order_placed')){
            $ext::rf_after_order_placed($order_id);
        }

        tep_redirect(tep_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL'));
    }

    function after_process() {
      return false;
    }

    function get_error() {
      return false;
    }

    public function describe_status_key()
    {
      return new ModuleStatus('MODULE_PAYMENT_RBSWORLDPAY_HOSTED_STATUS','True','False');
    }

    public function describe_sort_key()
    {
      return new ModuleSortOrder('MODULE_PAYMENT_RBSWORLDPAY_HOSTED_SORT_ORDER');
    }

    function configure_keys() {
      $status_id = defined('MODULE_PAYMENT_RBSWORLDPAY_HOSTED_PREPARE_ORDER_STATUS_ID') ? MODULE_PAYMENT_RBSWORLDPAY_HOSTED_PREPARE_ORDER_STATUS_ID : $this->getDefaultOrderStatusId();
      $tx_status_id = defined('MODULE_PAYMENT_RBSWORLDPAY_HOSTED_TRANSACTIONS_ORDER_STATUS_ID') ? MODULE_PAYMENT_RBSWORLDPAY_HOSTED_TRANSACTIONS_ORDER_STATUS_ID : $this->getDefaultOrderStatusId();
      $status_id_res  = defined('MODULE_PAYMENT_RBSWORLDPAY_HOSTED_ORDER_STATUS_ID') ? MODULE_PAYMENT_RBSWORLDPAY_HOSTED_ORDER_STATUS_ID : $this->getDefaultOrderStatusId();
      $params = array('MODULE_PAYMENT_RBSWORLDPAY_HOSTED_STATUS' => array('title' => 'Enable WorldPay Hosted Payment Pages',
                                                                          'desc' => 'Do you want to accept WorldPay Hosted Payment Pages payments?',
                                                                          'value' => 'True',
                                                                          'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '),
                      'MODULE_PAYMENT_RBSWORLDPAY_HOSTED_INSTALLATION_ID' => array('title' => 'Installation ID',
                                                                                   'desc' => 'The WorldPay Account Installation ID to accept payments for'),
                      'MODULE_PAYMENT_RBSWORLDPAY_HOSTED_CALLBACK_PASSWORD' => array('title' => 'Callback Password',
                                                                                     'desc' => 'The password sent to the callback processing script. This must be the same value defined in the WorldPay Merchant Interface.'),
                      'MODULE_PAYMENT_RBSWORLDPAY_HOSTED_MD5_PASSWORD' => array('title' => 'MD5 Password',
                                                                                'desc' => 'The MD5 password to verify transactions with. This must be the same value defined in the WorldPay Merchant Interface.'),
                      'MODULE_PAYMENT_RBSWORLDPAY_HOSTED_TRANSACTION_METHOD' => array('title' => 'Transaction Method',
                                                                                      'desc' => 'The processing method to use for each transaction.',
                                                                                      'value' => 'Capture',
                                                                                      'set_function' => 'tep_cfg_select_option(array(\'Pre-Authorization\', \'Capture\'), '),
                      'MODULE_PAYMENT_RBSWORLDPAY_HOSTED_PREPARE_ORDER_STATUS_ID' => array('title' => 'Set Preparing Order Status',
                                                                                           'desc' => 'Set the status of prepared orders made with this payment module to this value',
                                                                                           'value' => $status_id,
                                                                                           'set_function' => 'tep_cfg_pull_down_order_statuses(',
                                                                                           'use_function' => '\\common\\helpers\\Order::get_order_status_name'),
                      'MODULE_PAYMENT_RBSWORLDPAY_HOSTED_ORDER_STATUS_ID' => array('title' => 'Set Order Status',
                                                                                   'desc' => 'Set the status of orders made with this payment module to this value',
                                                                                   'value' => $status_id_res,
                                                                                   'set_function' => 'tep_cfg_pull_down_order_statuses(',
                                                                                   'use_function' => '\\common\\helpers\\Order::get_order_status_name'),
                      'MODULE_PAYMENT_RBSWORLDPAY_HOSTED_TRANSACTIONS_ORDER_STATUS_ID' => array('title' => 'Transactions Order Status Level',
                                                                                                'desc' => 'Include WorldPay transaction information in this order status level.',
                                                                                                'value' => $tx_status_id,
                                                                                                'use_function' => '\\common\\helpers\\Order::get_order_status_name',
                                                                                                'set_function' => 'tep_cfg_pull_down_order_statuses('),
                      'MODULE_PAYMENT_RBSWORLDPAY_HOSTED_ZONE' => array('title' => 'Payment Zone',
                                                                        'desc' => 'If a zone is selected, only enable this payment method for that zone.',
                                                                        'value' => '0',
                                                                        'use_function' => '\\common\\helpers\\Zones::get_zone_class_title',
                                                                        'set_function' => 'tep_cfg_pull_down_zone_classes('),
                      'MODULE_PAYMENT_RBSWORLDPAY_HOSTED_TESTMODE' => array('title' => 'Test Mode',
                                                                            'desc' => 'Should transactions be processed in test mode?',
                                                                            'value' => 'False',
                                                                            'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '),
                      'MODULE_PAYMENT_RBSWORLDPAY_HOSTED_DEBUG_EMAIL' => array('title' => 'Debug E-Mail Address',
                                                                               'desc' => 'All parameters of an invalid transaction will be sent to this email address if one is entered.'),
                      'MODULE_PAYMENT_RBSWORLDPAY_HOSTED_SORT_ORDER' => array('title' => 'Sort order of display.',
                                                                              'desc' => 'Sort order of display. Lowest is displayed first.',
                                                                              'value' => '0'));

      return $params;
    }

// format prices without currency formatting
    function format_raw($number, $currency_code = '', $currency_value = '') {
      $currencies = \Yii::$container->get('currencies');

      if (empty($currency_code) || !$this->is_set($currency_code)) {
        $currency_code = \Yii::$app->settings->get('currency');
      }

      if (empty($currency_value) || !is_numeric($currency_value)) {
        $currency_value = $currencies->currencies[$currency_code]['value'];
      }

      return number_format(self::round($number * $currency_value, $currencies->currencies[$currency_code]['decimal_places']), $currencies->currencies[$currency_code]['decimal_places'], '.', '');
    }

    function sendDebugEmail($response = array()) {
      if (tep_not_null(MODULE_PAYMENT_RBSWORLDPAY_HOSTED_DEBUG_EMAIL)) {
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
          \common\helpers\Mail::send('', MODULE_PAYMENT_RBSWORLDPAY_HOSTED_DEBUG_EMAIL, 'WorldPay Hosted Debug E-Mail', trim($email_body), STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
        }
      }
    }

    function isOnline() {
        return true;
    }

}