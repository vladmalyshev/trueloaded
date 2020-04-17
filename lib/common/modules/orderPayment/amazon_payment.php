<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2018 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

  namespace common\modules\orderPayment;

  use common\classes\modules\ModulePayment;
  use common\classes\modules\ModuleStatus;
  use common\classes\modules\ModuleSortOrder;

  class amazon_payment extends ModulePayment{
    var $code, $title, $description, $enabled;

    protected $defaultTranslationArray = [
        'MODULE_PAYMENT_AMAZON_PAYMENT_TEXT_TITLE' => 'Amazon',
        'MODULE_PAYMENT_AMAZON_PAYMENT_TEXT_PUBLIC_TITLE' => 'Amazon',
        'MODULE_PAYMENT_AMAZON_PAYMENT_TEXT_DESCRIPTION' => 'Amazon'
    ];

    function __construct() {
      parent::__construct();

      $this->code = 'amazon_payment';
      $this->signature = 'amazon|amazon_payment|0.1|TL.3.2';

      $this->title = MODULE_PAYMENT_AMAZON_PAYMENT_TEXT_TITLE;
      $this->public_title = MODULE_PAYMENT_AMAZON_PAYMENT_TEXT_PUBLIC_TITLE;
      $this->description = MODULE_PAYMENT_AMAZON_PAYMENT_TEXT_DESCRIPTION;
      $this->sort_order = defined('MODULE_PAYMENT_AMAZON_PAYMENT_SORT_ORDER') ? MODULE_PAYMENT_AMAZON_PAYMENT_SORT_ORDER : 0;
      $this->enabled = defined('MODULE_PAYMENT_AMAZON_PAYMENT_STATUS') && (MODULE_PAYMENT_AMAZON_PAYMENT_STATUS == 'True') ? true : false;
      $this->order_status = self::getDefaultStatus();
      $this->online = true;

      if ( defined('MODULE_PAYMENT_AMAZON_PAYMENT_STATUS') ) {
        if ( MODULE_PAYMENT_AMAZON_PAYMENT_MODE == 'Sandbox' ) {
          $this->title .= ' [Sandbox]';
          $this->public_title .= ' (' . $this->code . '; Sandbox)';
        }

      }

      if ( !function_exists('curl_init') ) {
        $this->description = '<div class="secWarning">' . MODULE_PAYMENT_AMAZON_PAYMENT_ERROR_ADMIN_CURL . '</div>' . $this->description;

        $this->enabled = false;
      }

      if ( $this->enabled === true ) {
        if ( !tep_not_null(self::getMerchantId()) &&
             !tep_not_null(self::getSecretAccessKey()) &&
             !tep_not_null(self::getAccessKeyId()) &&
             !tep_not_null(self::getClientId()) ) {
          $this->description = '<div class="secWarning">' . MODULE_PAYMENT_AMAZON_PAYMENT_ERROR_ADMIN_CONFIGURATION . '</div>' . $this->description;

          $this->enabled = false;
        }
      }

      if ( $this->enabled === true ) {
        $this->update_status();
      }
    }

    function update_status() {

      if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_AMAZON_PAYMENT_ZONE > 0) ) {
        $check_flag = false;
        $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_AMAZON_PAYMENT_ZONE . "' and zone_country_id = '" . $this->delivery['country']['id'] . "' order by zone_id");
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

    public function checkButtonOnProduct(){
        return defined('MODULE_PAYMENT_AMAZON_PAYMENT_BUY_IMMEDIATELLY') && MODULE_PAYMENT_AMAZON_PAYMENT_BUY_IMMEDIATELLY == 'True';
    }
    /**
     *
     * @global type $cart
     * @global type $order
     * @staticvar int $id - button number on page (part of HTML id)
     * @param int $buy_now - 0, 1, 2 (equal to PwA, Pay, A)
     * @return string
     */
    function checkout_initialization_method($buy_now = false, $type = 0) {
      static $id = 0;
      $id++;
//      $button_title = \common\helpers\Output::output_string_protected(MODULE_PAYMENT_AMAZON_PAYMENT_TEXT_BUTTON);

      if ( MODULE_PAYMENT_AMAZON_PAYMENT_MODE == 'Sandbox' ) {
        $button_title .= ' (' . $this->code . '; Sandbox)';
      }

      if ($id ==1 ) {
        $string = '<script>
                      if (typeof window.onAmazonLoginReady != "function") {
                        window.onAmazonLoginReady = init_amazon_pay_client;
                      } else {
                      }
                      function init_amazon_pay_client() {
                        amazon.Login.setClientId("' . self::getClientId() . '");
                      };
                    </script>';
      }

      $string .= '<div id="PayWithAmazon' . $id .'" class="login-with-amazon"></div>';

      if ($id ==1 ) {
        $string .= '
<script>
  tl(function(){
       $(document).ready(function () {
          if ($("#apwjs").length==0) {
            var script = document.createElement( "script" );
            script.type = "text/javascript";
            script.src  = "' . self::getWidgetUrl() .'";
            script.id  = "apwjs";
            script.async = "async";
            $("body").prepend( script );
          }
       });

       if (typeof window.onAmazonPaymentsReady != "function") {
         window.onAmazonPaymentsReady = init_amazon_pay_buttons;
       } else {
         init_amazon_pay_buttons();
       }


     function init_amazon_pay_buttons(){
       // render the button here
       var authRequest;

       $("div.login-with-amazon").each(function (a) {

         var el = this.id;//$(this).prop("id");//
         var state;
          OffAmazonPayments.Button(el, "' . self::getMerchantId() . '", {
              type:  "' . ($type?($type==1?'Pay':'A'):'PwA') . '",//| Pay | A
              color: "' . self::getButtonColor() . '",
              size:  "' . self::getButtonSize() . '",
              language: "' . str_replace('_', '-', Yii::$app->language) . '",
              useAmazonAddressBook: true,
              authorization: function() {
                var loginOptions = {scope: "profile payments:widget", popup: true}; /*Example: scope: profile payments:widget payments:shipping_address payments:billing_address*/
                ' . ($buy_now?
                '

                    if ($("#"+el).parents("form[name=\'cart_quantity\']").length>0) {
                      var p = $("#"+el).parents("form[name=\'cart_quantity\']");
                    } else {
                      var p = $("#"+el).parents("form.form-buy");
                    }
                    var    u = p.attr("action"),
                        m = p.attr("method"),
                        d = "purchase=amazon_payment&" + p.serialize()
                        ;
                        if (state != "send") {
                          state = "send";
                          $("body").css("cursor", "progress");

                          $.ajax({
                              url: u,
                              data: d,
                              dataType: "json",
                              type: m,
                              crossDomain: !1,
                              success: function(data) {
                                $("body").css("cursor", "default");
                                state = "ready";
                                if (data.added) {
                                  /*popup blocker this way
                                   authRequest = amazon.Login.authorize(loginOptions, "' . Yii::$app->urlManager->createAbsoluteUrl( ['checkout/amazonlogin'] ) . '");
                                     */
                                } else {
                                /* display popup error*/
                                //alert(data);
                                }
                              }
                          });
                        }
                        var apiid = setInterval(function(){
                            if (state == "ready") {
                              clearInterval(apiid );
                              authRequest = amazon.Login.authorize(loginOptions, "' . Yii::$app->urlManager->createAbsoluteUrl( ['checkout/amazonlogin'] ) . '");
                            }
                          }, 300);
'
                :' authRequest = amazon.Login.authorize(loginOptions, "' . Yii::$app->urlManager->createAbsoluteUrl( ['checkout/amazonlogin'] ) . '");') .
                '
                  ;

              },
              onError: function(error) {
                // Write your custom error handling
              }
          });
       });

     }
  });
</script>';
        }
      return $string;
    }

    function javascript_validation() {
      return false;
    }

    function selection() {
      return false;
      /*return array('id' => $this->code,
                   'module' => $this->public_title);*/
    }

    function pre_confirmation_check() {
      if (!tep_session_is_registered('amazon_eu_login')) {
        tep_redirect(tep_href_link(FILENAME_SHOPPING_CART, '', 'SSL'));
      }

    }

    function confirmation() {

/*
      if (!isset($comments)) {
        $comments = null;
      }

      $confirmation = false;

      if (empty($comments)) {
        $confirmation = array('fields' => array(array('title' => MODULE_PAYMENT_AMAZON_PAYMENT_TEXT_COMMENTS,
                                                      'field' => tep_draw_textarea_field('apcomments', 'soft', '60', '5', $comments))));
      }

      return $confirmation;
*/
      return false;
    }

    function process_button() {
      global $amazon_eu_login;

      $process_button_string = ''
          . ''
          . '<style type="text/css">
                  #readOnlyAddressBookWidgetDiv {width: 100%; height: 185px; padding-bottom:20px}
                  #readOnlyWalletWidgetDiv {width: 100%; height: 185px; padding-bottom:20px}
              </style>
              <script>
          tl(function(){
            var script = document.createElement( "script" );
            script.type = "text/javascript";
            script.src  = "' . self::getWidgetUrl() .'";
            script.async = "async";
            $("body").append( script );

            window.onAmazonLoginReady = function() { amazon.Login.setClientId("' . self::getClientId() . '"); };

            window.onAmazonPaymentsReady = function(){

              //replace standard elements with readonly widgets. edit jquery if required by design.

              var cnt = Math.min(2, $("#frmCheckoutConfirm .col-left").length);
              $("#frmCheckoutConfirm .col-left").each(function () {
                if (cnt==1) {
                  $(this).html(\'<div class="heading-4">&nbsp;<a class="edit" href="' . Yii::$app->urlManager->createUrl( ['checkout/amazonlogin']) . '">' . (defined('EDIT')?EDIT:'') . '</a></div><div id="readOnlyAddressBookWidgetDiv"></div>\');
                } else {
                  $(this).remove();
                }
                cnt--;
              });
              var cnt = Math.min(2, $("#frmCheckoutConfirm .col-right").length);
              $("#frmCheckoutConfirm .col-right").each(function () {
                if (cnt==1) {
                  $(this).html(\'<div class="heading-4">&nbsp;<a class="edit" href="' . Yii::$app->urlManager->createUrl( ['checkout/amazonlogin']) . '">' . (defined('EDIT')?EDIT:'') . '</a></div><div id="readOnlyWalletWidgetDiv"></div>\');
                } else {
                  $(this).remove();
                }
                cnt--;
              });

                new OffAmazonPayments.Widgets.AddressBook({
                  sellerId: "' . self::getMerchantId() . '",
                  // amazonOrderReferenceId obtained from Address widget
                  amazonOrderReferenceId: "' . $_SESSION['amazon_eu_login']['orderRef'] . '",
                  displayMode: "Read",
                  design: {
                     designMode: "responsive"
                  },
                  onError: function(error) {
                   // your error handling code
                  }
                }).bind("readOnlyAddressBookWidgetDiv");

                new OffAmazonPayments.Widgets.Wallet({
                  sellerId: "' . self::getMerchantId() . '",
                  // amazonOrderReferenceId obtained from Address widget
                  amazonOrderReferenceId: "' . $_SESSION['amazon_eu_login']['orderRef'] . '",
                  displayMode: "Read",
                  design: {
                     designMode: "responsive"
                  },
                  onError: function(error) {
                   // your error handling code
                  }
                }).bind("readOnlyWalletWidgetDiv");
              }
            });

              </script>'
          . '';
      $process_button_string .= tep_draw_hidden_field('orderRef', $amazon_eu_login['orderRef']);

      return $process_button_string;
    }

    function before_process() {
      global $amazon_eu_login;

      $order = $this->manager->getOrderInstance();
     /// set order reference details (totals)
      $orderRef = $amazon_eu_login['orderRef'];
      $params = array(
          'AWSAccessKeyId' => self::getAccessKeyId(),
          'Action' => 'SetOrderReferenceDetails',
          'AmazonOrderReferenceId' => $orderRef,
          'OrderReferenceAttributes.OrderTotal.Amount' => $order->info['total'],
          'OrderReferenceAttributes.OrderTotal.CurrencyCode' => $order->info['currency'],
          'OrderReferenceAttributes.SellerNote' => isset($order->info['comments'])?$order->info['comments']:'',
          'OrderReferenceAttributes.SellerOrderAttributes.StoreName' => STORE_NAME,
          /*'MWSAuthToken' => self::getMWSToken(),*/
          'SellerId' => self::getMerchantId(),
          'SignatureMethod' => 'HmacSHA256',
          'SignatureVersion' => '2',
          //'Timestamp' => date('Y-m-d').'T'.date('H:i:s').'Z',
          'Timestamp' => gmdate("Y-m-d\TH:i:s\\Z", time()),
          'Version' => '2013-01-01'
        );

      $xml = $this->sendSignedRequest(self::getMWSPayUrl(), $params);
      $xml_arr = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
      $result = json_decode(json_encode((array)$xml_arr), true);
      //if (isset($result['Error']) && is_array($result['Error']) && isset($result['Error']['Message'])) {
      $err = self::hasError($result);
      if (is_array($err)) {
        tep_redirect(Yii::$app->urlManager->createUrl( ['checkout/amazonlogin',
            'error_message' => urlencode($err['Message'] . ' (' . $err['Code'] .')' ),
            'payment_error' => ($this->code),
            'errcode' => urlencode($err['Code'])
                ]));
      }
      self::updateDBLog(
       (['amazon_order_id' => $orderRef,
        'amazon_status' => $result['SetOrderReferenceDetailsResult']['OrderReferenceDetails']['OrderReferenceStatus']['State'],
        'custom_data' => serialize($result)
      ]), " amazon_order_id = '" . tep_db_input($orderRef) . "'");

    }

    function after_process() {
      global $amazon_eu_login;

      $order = $this->manager->getOrderInstance();
      $orderRef = $amazon_eu_login['orderRef'];
      $params = array(
          'AWSAccessKeyId' => self::getAccessKeyId(),
          'Action' => 'ConfirmOrderReference',
          'AmazonOrderReferenceId' => $orderRef,
          'OrderReferenceAttributes.OrderTotal.Amount' => $order->info['total'],
          'OrderReferenceAttributes.OrderTotal.CurrencyCode' => $order->info['currency'],
          'OrderReferenceAttributes.SellerNote' => isset($order->info['comments'])?$order->info['comments']:'',
          'OrderReferenceAttributes.SellerOrderAttributes.SellerOrderId' => $order->order_id,
          'OrderReferenceAttributes.SellerOrderAttributes.StoreName' => STORE_NAME,
          /*'MWSAuthToken' => self::getMWSToken(),*/
          'SellerId' => self::getMerchantId(),
          'SignatureMethod' => 'HmacSHA256',
          'SignatureVersion' => '2',
          //'Timestamp' => date('Y-m-d').'T'.date('H:i:s').'Z',
          'Timestamp' => gmdate("Y-m-d\TH:i:s\\Z", time()),
          'Version' => '2013-01-01'
        );

      $xml = $this->sendSignedRequest(self::getMWSPayUrl(), $params);
      $xml_arr = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
      $result = json_decode(json_encode((array)$xml_arr), true);
      self::updateDBLog(
       (['amazon_order_id' => $orderRef,
        'orders_id' => $order->order_id,
        'custom_data' => serialize($result)
      ]), " amazon_order_id = '" . tep_db_input($orderRef) . "'");

      if (self::hasError($result)) {
        $this->sendDebugEmail(array_merge($params, $result));
      } else {
        //Authorise
        $param = [];
        $param['AmazonOrderReferenceId'] = $orderRef;
        $param['total'] = $order->info['total'];
        $param['currency'] = $order->info['currency'];
        $param['order_id'] = $order->order_id;
        $param['order_status'] = $order->info['order_status'];
        $param['note'] = STORE_NAME . ' #' . $order->order_id . ' - ' . $order->info['total'] . $order->info['currency'];
        $result = $this->auth($param);

        if (self::hasError($result)) {
          $this->sendDebugEmail(array_merge($params, $result));
        }
      }

      //Check Authorization status/exception & cancel
      $AuthorizationStatus = isset($result['AuthorizeResult']['AuthorizationDetails']['AuthorizationStatus']['State'])?$result['AuthorizeResult']['AuthorizationDetails']['AuthorizationStatus']['State']:'';
      $AuthorizationRightStatus = (bool)($AuthorizationStatus == 'Open' || $AuthorizationStatus == 'Pending');
      if (!self::hasError($result) && !$AuthorizationRightStatus)  { // || !empty($InvalidPaymentMethod) - from simulation
        $order_status = self::getCancelStatus();

        $AuthorizationReasonCode = isset($result['AuthorizeResult']['AuthorizationDetails']['AuthorizationStatus']['ReasonCode'])?' ReasonCode: '.$result['AuthorizeResult']['AuthorizationDetails']['AuthorizationStatus']['ReasonCode']:'';

        self::addOrderStatusHistory($order->order_id, $order_status, $AuthorizationReasonCode);

        tep_redirect(
            Yii::$app->urlManager->createAbsoluteUrl([
              'account/history-info',
              'order_id' => $order->order_id,
              'error_message' => urlencode($AuthorizationReasonCode),
              'payment_error' => ($this->code)
            ])

            );

      }


      if (!self::hasError($result)) {
        self::updateOrderCustomer($result, $order->order_id);
        $this->getOrderReferenceDetails($orderRef);// update full shipping address
      }

      //capture if required.
      if (!self::hasError($result) && self::instCapture() && isset($result['AuthorizeResult']['AuthorizationDetails']['AmazonAuthorizationId']) ) {
        $param = [];
        $param['AmazonAuthorizationId'] = $result['AuthorizeResult']['AuthorizationDetails']['AmazonAuthorizationId'];
        $param['total'] = $order->info['total'];
        $param['currency'] = $order->info['currency'];
        $param['order_id'] = $order->order_id;
        $param['order_status'] = $order->info['order_status'];

        $result = $this->capture($param);
         //most probably useless
        /*if (!self::hasError($result)) {
          self::updateOrderCustomer($result, $insert_id);
        }*/

      }

      //most probably useless
        /*if (!self::hasError($result)){
        self::updateOrderCustomer($result, $insert_id);
      }*/

    }

    public static function updateOrderCustomer($result, $orders_id) {
      $sql_data_array = $sql_data_array_b = $sql_data_array_s = [];
      $dif_biling_address = false;
      if (isset($result['GetOrderReferenceDetailsResult']['OrderReferenceDetails'])) {
        $OrderReferenceDetails = $result['GetOrderReferenceDetailsResult']['OrderReferenceDetails'];
      } elseif (isset($result['AuthorizeResult']['AuthorizationDetails']['AuthorizationBillingAddress'])) { //auth
        $OrderReferenceDetails = $result['AuthorizeResult'];
      } elseif (isset($result['GetAuthorizationDetailsResult']['AuthorizationDetails']['AuthorizationBillingAddress'])) { //get aut details
        $OrderReferenceDetails = $result['GetAuthorizationDetailsResult'];
      }

      if (isset($OrderReferenceDetails['Destination']['PhysicalDestination'])) {

        if (isset($OrderReferenceDetails['Destination']['PhysicalDestination']['Phone'])) {
          $sql_data_array['customers_telephone'] =
              $OrderReferenceDetails['Destination']['PhysicalDestination']['Phone'];
        }
        if (!empty($OrderReferenceDetails['Destination']['PhysicalDestination']['Name'])) {
          $name = tep_db_prepare_input($OrderReferenceDetails['Destination']['PhysicalDestination']['Name']);
          $parts = explode(' ', $name, 2);
          $firstname = tep_db_prepare_input(trim(isset($parts[0])?$parts[0]:''));
          $lastname = tep_db_prepare_input(trim(isset($parts[1])?$parts[1]:''));
          $sql_data_array['customers_name'] = $sql_data_array_b['billing_name'] = $sql_data_array_s['delivery_name'] = $name;
          $sql_data_array['customers_firstname'] = $sql_data_array_b['billing_firstname'] = $sql_data_array_s['delivery_firstname'] = $firstname;
          $sql_data_array['customers_lastname'] = $sql_data_array_b['billing_lastname'] = $sql_data_array_s['delivery_lastname'] = $lastname;
        }
        if (!empty($OrderReferenceDetails['Destination']['PhysicalDestination']['AddressLine1'])) {
          $sql_data_array['customers_street_address'] = $sql_data_array_s['billing_street_address'] =$sql_data_array_b['delivery_street_address'] =
              $OrderReferenceDetails['Destination']['PhysicalDestination']['AddressLine1'];
        }

        if (!empty($OrderReferenceDetails['Destination']['PhysicalDestination']['AddressLine2'])) {
          $sql_data_array['customers_suburb'] = $sql_data_array_b['billing_suburb'] = $sql_data_array_s['delivery_suburb'] =
              $OrderReferenceDetails['Destination']['PhysicalDestination']['AddressLine2'];
        }
        if (!empty($OrderReferenceDetails['Destination']['PhysicalDestination']['PostalCode'])) {
          $sql_data_array['customers_postcode'] = $sql_data_array_b['billing_postcode'] = $sql_data_array_s['delivery_postcode'] =
              $OrderReferenceDetails['Destination']['PhysicalDestination']['PostalCode'];
        }
        if (!empty($OrderReferenceDetails['Destination']['PhysicalDestination']['City'])) {
          $sql_data_array['customers_city'] = $sql_data_array_b['billing_city'] = $sql_data_array_s['delivery_city'] =
              $OrderReferenceDetails['Destination']['PhysicalDestination']['City'];
        }
        if (!empty($OrderReferenceDetails['Destination']['PhysicalDestination']['StateOrRegion'])) {
          $sql_data_array['customers_state'] = $sql_data_array_b['billing_state'] = $sql_data_array_s['delivery_state'] =
              $OrderReferenceDetails['Destination']['PhysicalDestination']['StateOrRegion'];
        }
        if (!empty($OrderReferenceDetails['Destination']['PhysicalDestination']['CountryCode'])) {
          $tmp = \common\helpers\Country::get_country_info_by_iso($OrderReferenceDetails['Destination']['PhysicalDestination']['CountryCode']);
          $sql_data_array['customers_country'] = $sql_data_array_b['billing_country'] = $sql_data_array_s['delivery_country'] = isset($tmp['title'])?$tmp['title']:$tmp;
          if (isset($tmp['address_format_id'])) {
            $sql_data_array['customers_address_format_id'] = $sql_data_array_b['billing_address_format_id'] = $sql_data_array_s['delivery_address_format_id'] = $tmp['address_format_id'];
          }
        }

      }
      //billing address is filled on auth. seems full delivery address is available after auth (so billing never should be overwritten)
      $sql_data_array_b = [];

      if (isset($OrderReferenceDetails['AuthorizationDetails']['AuthorizationBillingAddress'])) {
        $ba = $OrderReferenceDetails['AuthorizationDetails']['AuthorizationBillingAddress'];
        if (!empty($ba['Name'])) {
          $name = tep_db_prepare_input($ba['Name']);
          $parts = explode(' ', $name, 2);
          $firstname = tep_db_prepare_input(trim(isset($parts[0])?$parts[0]:''));
          $lastname = tep_db_prepare_input(trim(isset($parts[1])?$parts[1]:''));
          $sql_data_array_b['billing_name'] = $name;
          $sql_data_array_b['billing_firstname'] = $firstname;
          $sql_data_array_b['billing_lastname'] = $lastname;
        }
        if (!empty($ba['AddressLine1'])) {
          $sql_data_array_b['billing_street_address'] = $ba['AddressLine1'];
        }
        if (!empty($ba['AddressLine2'])) {
          $sql_data_array_b['billing_suburb'] = $ba['AddressLine2'];
        }
        if (!empty($ba['PostalCode'])) {
          $sql_data_array_b['billing_postcode'] = $ba['PostalCode'];
        }
        if (!empty($ba['City'])) {
          $sql_data_array_b['billing_city'] = $ba['City'];
        }
        if (!empty($ba['StateOrRegion'])) {
          $sql_data_array_b['billing_state'] = $ba['StateOrRegion'];
        }
        if (!empty($ba['CountryCode'])) {
          $tmp = \common\helpers\Country::get_country_info_by_iso($ba['CountryCode']);
          $sql_data_array_b['billing_country'] = isset($tmp['title'])?$tmp['title']:$tmp;
          if (isset($tmp['address_format_id'])) {
            $sql_data_array_b['billing_address_format_id'] = $tmp['address_format_id'];
          }
        }
        $dif_biling_address = ( implode('', $sql_data_array_b) != implode('', $sql_data_array_s) );
      }
        $sql_data_array = array_merge($sql_data_array, $sql_data_array_b, $sql_data_array_s);
      if (count($sql_data_array) >0) {
        $sql_data_array = array_map('tep_db_prepare_input', $sql_data_array);

        tep_db_perform(TABLE_ORDERS, $sql_data_array, 'update', "orders_id='" . (int)$orders_id . "'");

        if (!empty($sql_data_array_s['delivery_name'] . $sql_data_array_s['customers_street_address'])) {
          tep_db_query("update amazon_payment_orders set address_full=1 where orders_id='" . (int)$orders_id . "'");
        }
        tep_db_query("update " . TABLE_ORDERS. " o, ". TABLE_ADDRESS_BOOK . " a "
            . " set a.entry_firstname=o.delivery_firstname, a.entry_lastname=o.delivery_lastname, a.entry_street_address=o.delivery_street_address, "
            . "     a.entry_suburb=o.delivery_suburb, a.entry_postcode=o.delivery_postcode, a.entry_city=o.delivery_city, a.entry_state=o.delivery_state, "
            . "     a.entry_telephone=o.customers_telephone  "
            . " where o.customers_id=a.customers_id and a.address_book_id=o.delivery_address_book_id "
            . "   and a.entry_street_address='' and orders_id='" . (int)$orders_id . "'");
      }
    }

    public static function hasError ($result) {
      $ret = [];
      if (isset($result['Error']['Message'])) {
        $ret['Message'] = (isset($ret['Message'])?' ':'') . $result['Error']['Message'];
      }
      if (isset($result['Error']['Code'])) {
        $ret['Code'] = (isset($ret['Code'])?' ':'') . $result['Error']['Code'];
      }
      $constraints = [];
      if (isset($result['SetOrderReferenceDetailsResult']['OrderReferenceDetails']['Constraints'])) {
        $constraints = $result['SetOrderReferenceDetailsResult']['OrderReferenceDetails']['Constraints'];
      } elseif (isset($result['GetOrderReferenceDetailsResult']['OrderReferenceDetails']['Constraints'])) {
        $constraints = $result['GetOrderReferenceDetailsResult']['OrderReferenceDetails']['Constraints'];
      }

      if (count($constraints)>0) {
        foreach ($constraints as $constraint) {
          if (isset($constraint['ConstraintID'])) {
            $ret['Code'] = (isset($ret['Code'])?' ':'') . $constraint['ConstraintID'];
          }
          if (isset($constraint['Description'])) {
            $ret['Message'] = (isset($ret['Message'])?' ':'') . $constraint['Description'];
          }
        }
      }

      if ($ret) {
        Yii::warning($ret, 'amazon_payment');
      }

      return (count($ret)>0?$ret:false);
    }

    public static function addOrderStatusHistory($orders_id, $status_id, $status_text) {
      if ($status_id) {
        tep_db_query("UPDATE " . TABLE_ORDERS . " SET orders_status = '" . (int)$status_id . "' WHERE orders_id = '" . (int)$orders_id . "'");
      }
      $sql_data_array = array(
              'orders_id' => (int)$orders_id,
              'orders_status_id' => (int)$status_id,
              'date_added' => 'now()',
              'customer_notified' => 0,
              'comments' => '' . $status_text
      );
      tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
    }

    public function auth($param) {
      $params = array(
        'AWSAccessKeyId' => self::getAccessKeyId(),
        'Action' => 'Authorize',
        'AmazonOrderReferenceId' => $param['AmazonOrderReferenceId'],
        'AuthorizationAmount.Amount' => $param['total'],
        'AuthorizationAmount.CurrencyCode' => $param['currency'],
        'AuthorizationReferenceId' => 'A_'.abs(crc32(STORE_NAME)).'_'.time().'_'.$param['order_id'],
        'SellerAuthorizationNote' => $param['note'],
        /*'MWSAuthToken' => self::getMWSToken(),*/
        'SellerId' => self::getMerchantId(),
        'SignatureMethod' => 'HmacSHA256',
        'SignatureVersion' => '2',
        //'Timestamp' => date('Y-m-d').'T'.date('H:i:s').'Z',
        'Timestamp' => gmdate("Y-m-d\TH:i:s\\Z", time()),
        'TransactionTimeout' => '0',
        'Version' => '2013-01-01'
      );

      $xml = $this->sendSignedRequest(self::getMWSPayUrl(), $params);
      $xml_arr = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
      $result = json_decode(json_encode((array)$xml_arr), true);

      $order_status = 0;
      if (isset($result['AuthorizeResult']['AuthorizationDetails']['AuthorizationStatus']['State'])
          && $result['AuthorizeResult']['AuthorizationDetails']['AuthorizationStatus']['State'] == 'Open') {
        $order_status = self::getAuthStatus();
      }
      if ($order_status == 0) {
        $order_status = $param['order_status'];
      }
      $auth = [];

      if (!self::hasError($result) && isset($result['AuthorizeResult']['AuthorizationDetails']['AmazonAuthorizationId']) && (int)$param['order_id']) {
        $auth['amazon_auth_id'] = $result['AuthorizeResult']['AuthorizationDetails']['AmazonAuthorizationId'];
        $auth['amazon_auth_status'] = $result['AuthorizeResult']['AuthorizationDetails']['AuthorizationStatus']['State'];
        $status_text = "Authorization: ";
        $status_text .= "\nAuthorizationId: ".$result['AuthorizeResult']['AuthorizationDetails']['AmazonAuthorizationId'];
        if (isset($result['AuthorizeResult']['AuthorizationDetails']['AuthorizationStatus']['State'])) {
          $status_text .= "\nAuthorizationStatus: ".$result['AuthorizeResult']['AuthorizationDetails']['AuthorizationStatus']['State'];
        }
        if (isset($result['AuthorizeResult']['AuthorizationDetails']['AuthorizationStatus']['ReasonCode'])) {
          $status_text .= "\nReasonCode: ".$result['AuthorizeResult']['AuthorizationDetails']['AuthorizationStatus']['ReasonCode'];
        }
        self::addOrderStatusHistory($param['order_id'], $order_status, $status_text);
      } else {
        Yii::warning($result, 'amazon_payment_auth');
      }
      self::updateDBLog(
      array_merge ($auth, ['amazon_order_id' => $param['AmazonOrderReferenceId'],
        'orders_id' => $param['order_id'],
        'custom_data' => serialize($result)
      ]), " orders_id = '" . tep_db_input($param['order_id']) . "'");

      return $result;
    }

    public function capture($param) {
      $params = array(
        'AWSAccessKeyId' => self::getAccessKeyId(),
        'Action' => 'Capture',
        'AmazonAuthorizationId' => $param['AmazonAuthorizationId'],
        'CaptureAmount.Amount' => $param['total'],
        'CaptureAmount.CurrencyCode' => $param['currency'],
        'CaptureReferenceId' => 'c_' . abs(crc32(STORE_NAME)) . '_' . time() . '_' . $param['order_id'],
        /*'MWSAuthToken' => self::getMWSToken(),*/
        'SellerId' => self::getMerchantId(),
        'SignatureMethod' => 'HmacSHA256',
        'SignatureVersion' => '2',
        //'Timestamp' => date('Y-m-d').'T'.date('H:i:s').'Z',
        'Timestamp' => gmdate("Y-m-d\TH:i:s\\Z", time()),
        'Version' => '2013-01-01'
      );

      $xml = $this->sendSignedRequest(self::getMWSPayUrl(), $params);
      $xml_arr = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
      $result = json_decode(json_encode((array)$xml_arr), true);

      $order_status = 0;
      if (!isset($result['Error']) && isset($result['CaptureResult']['CaptureDetails']['AmazonCaptureId'])) {
        $order_status = self::getCaptureStatus();
      }
      if ($order_status == 0) {
        $order_status = $param['order_status'];
      }

      $auth = [];
      if (!self::hasError($result) && isset($result['CaptureResult']['CaptureDetails']['AmazonCaptureId']) && (int)$param['order_id']) {
        $status_text = "Capture:";
        $status_text .= "\nCaptureId: ".$result['CaptureResult']['CaptureDetails']['AmazonCaptureId'];
        $status_text .= "\nAmount: " . $param['total'] . $param['currency'];
        self::addOrderStatusHistory($param['order_id'], $order_status, $status_text);
        $auth['amazon_capture_id'] = $result['CaptureResult']['CaptureDetails']['AmazonCaptureId'];
        $auth['amazon_capture_status'] = $result['CaptureResult']['CaptureDetails']['CaptureStatus']['State'];

        if($this->manager->isInstance()){
            $order = $this->manager->getOrderInstance();
        } else {
            $order = $this->manager->getOrderInstanceWithId('\common\classes\Order', $param['order_id']);
        }
        $order_total_modules = $this->manager->getTotalCollection();

        if ($param['total'] != $order->info['total_inc_tax']) {
          $ex = round($order->info['total_exc_tax']*$param['total']/$order->info['total_inc_tax'], 2);//something, could be incorrect
        } else {
          $ex = $order->info['total_exc_tax'];
        }
        $inc = $param['total'];

        if ($order->totals ) {
          $order_totals = $order->totals;
          foreach( $order->totals as $key => $total ) {
            $order_totals[ $key ]['sort_order'] = $order_total_modules->get($total['class'])->sort_order;
            if( $total['class'] == 'ot_paid' ) {
              $paid_key = $key;
            }
            if( $total['class'] == 'ot_due' ) {
              //2check $order->info['total'] = $total['value_inc_tax'];

              if( $paid_key != - 1 ) {
                $order->info['total_inc_tax'] = $order_totals[ $paid_key ]['value_inc_tax'] + $inc;
                $order->info['total_exc_tax'] = $order_totals[ $paid_key ]['value_exc_vat'] + $ex;
              }
              break;
            }
          }
        }
        $order->update_piad_information(true);
        $order->save_details();

      } else {
        Yii::warning($result, 'amazon_payment');
      }

      self::updateDBLog(
      array_merge ($auth, [
        'orders_id' => $param['order_id'],
        'custom_data' => serialize($result)
      ]), " orders_id = '" . tep_db_input($param['order_id']) . "'");

      return $result;
    }

    public function refund($param) {
      $params = array(
        'AWSAccessKeyId' => self::getAccessKeyId(),
        'Action' => 'Refund',
        'AmazonCaptureId' => $param['AmazonCaptureId'],
        'RefundAmount.Amount' => $param['total'],
        'RefundAmount.CurrencyCode' => $param['currency'],
        'RefundReferenceId' => 'r_' . abs(crc32(STORE_NAME)) . '_' . time() . '_' . $param['order_id'],
        /*'MWSAuthToken' => self::getMWSToken(),*/
        'SellerId' => self::getMerchantId(),
        'SignatureMethod' => 'HmacSHA256',
        'SignatureVersion' => '2',
        //'Timestamp' => date('Y-m-d').'T'.date('H:i:s').'Z',
        'Timestamp' => gmdate("Y-m-d\TH:i:s\\Z", time()),
        'Version' => '2013-01-01'
      );

      $xml = $this->sendSignedRequest(self::getMWSPayUrl(), $params);
      $xml_arr = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
      $result = json_decode(json_encode((array)$xml_arr), true);

      $order_status = $param['order_status'];
      $auth = [];

      if (!self::hasError($result) && isset($result['RefundResult']['RefundDetails']['AmazonRefundId']) && (int)$param['order_id']) {
        $status_text = "Refund:";
        $status_text .= "\nRefundId: " . $result['RefundResult']['RefundDetails']['AmazonRefundId'];
         $status_text .= "\nAmount: " . $param['total'] . $param['currency'];
        self::addOrderStatusHistory($param['order_id'], $order_status, $status_text);
        $auth['amazon_refund_id'] = $result['RefundResult']['RefundDetails']['AmazonRefundId'];
        $auth['amazon_refund_status'] = $result['RefundResult']['RefundDetails']['RefundStatus']['State'];
      } else {
        Yii::warning($result, 'amazon_payment_refund');
      }
      self::updateDBLog(
      array_merge ($auth, [
        'orders_id' => $param['order_id'],
        'custom_data' => serialize($result)
      ]), " orders_id = '" . tep_db_input($param['order_id']) . "'");

      return $result;
    }

    public function closeOrder($ref) {
      $params = array(
        'AWSAccessKeyId' => self::getAccessKeyId(),
        'Action' => 'CloseOrderReference',
        'AmazonOrderReferenceId' => $ref,
        'ClosureReason' => 'by admin',
        /*'MWSAuthToken' => self::getMWSToken(),*/
        'SellerId' => self::getMerchantId(),
        'SignatureMethod' => 'HmacSHA256',
        'SignatureVersion' => '2',
        //'Timestamp' => date('Y-m-d').'T'.date('H:i:s').'Z',
        'Timestamp' => gmdate("Y-m-d\TH:i:s\\Z", time()),
        'Version' => '2013-01-01'
      );

      $xml = $this->sendSignedRequest(self::getMWSPayUrl(), $params);
      $xml_arr = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
      $result = json_decode(json_encode((array)$xml_arr), true);


      if (!self::hasError($result) ) {
        $result = $this->getOrderReferenceDetails($ref);
      } else {
        Yii::warning($result, 'amazon_payment_refund');
      }

      return $result;
    }
    /**
     * updates $data in rows $where
     * $data should contain $where also
     */
    public static function updateDBLog ($data, $where) {
      $ret = false;
      if (strpos($where, '_id') !== false) {
        $check = tep_db_fetch_array(tep_db_query("select count(*) as total from amazon_payment_orders where " . $where));
        if ($check['total']==0) {
          tep_db_perform('amazon_payment_orders', $data);
        } else {
          if (isset($data['custom_data'])) {
            $_add_text = $data['custom_data'];
            unset($data['custom_data']);
          } else {
            $_add_text = false;
          }
          tep_db_perform('amazon_payment_orders', $data, 'update', $where);
          if ($_add_text ) {
            tep_db_query("update amazon_payment_orders set custom_data=concat('" . tep_db_input($_add_text) . "#\n\n#', custom_data)");
          }
        }
        $ret = tep_db_fetch_array(tep_db_query("select orders_id from amazon_payment_orders where " . $where));
      }
      return $ret;
    }

    function get_error() {
      return false;
    }
    public function describe_status_key()
    {
      return new ModuleStatus('MODULE_PAYMENT_AMAZON_PAYMENT_STATUS','True','False');
    }

    public function describe_sort_key()
    {
      return new ModuleSortOrder('MODULE_PAYMENT_AMAZON_PAYMENT_SORT_ORDER');
    }

    public function configure_keys() {
        $status_id = defined('MODULE_PAYMENT_AMAZON_PAYMENT_ORDER_STATUS_ID') ? MODULE_PAYMENT_AMAZON_PAYMENT_ORDER_STATUS_ID : $this->getDefaultOrderStatusId();
        $status_id_n = defined('MODULE_PAYMENT_AMAZON_PAYMENT_NEW_ORDER_NOTIFY_ORDER_STATUS_ID') ? MODULE_PAYMENT_AMAZON_PAYMENT_NEW_ORDER_NOTIFY_ORDER_STATUS_ID : $this->getDefaultOrderStatusId();
        $status_id_n_r = defined('MODULE_PAYMENT_AMAZON_PAYMENT_RTS_NOTIFY_ORDER_STATUS_ID') ? MODULE_PAYMENT_AMAZON_PAYMENT_RTS_NOTIFY_ORDER_STATUS_ID : $this->getDefaultOrderStatusId();
        $status_id_c = defined('MODULE_PAYMENT_AMAZON_PAYMENT_CANCEL_NOTIFY_ORDER_STATUS_ID') ? MODULE_PAYMENT_AMAZON_PAYMENT_CANCEL_NOTIFY_ORDER_STATUS_ID : $this->getDefaultOrderStatusId();


        tep_db_query(
        "
        CREATE TABLE IF NOT EXISTS `amazon_payment_orders` (
          `amazon_order_id` varchar(64) NOT NULL DEFAULT '',
          `amazon_status` varchar(64) NOT NULL DEFAULT '',
          `amazon_auth_id` varchar(64) NOT NULL DEFAULT '',
          `amazon_auth_status` varchar(64) NOT NULL DEFAULT '',
          `amazon_capture_id` varchar(64) NOT NULL DEFAULT '',
          `amazon_capture_status` varchar(64) NOT NULL DEFAULT '',
          `amazon_refund_id` varchar(64) NOT NULL DEFAULT '',
          `amazon_refund_status` varchar(64) NOT NULL DEFAULT '',
          `orders_id` int(11) NOT NULL DEFAULT '0',
          `cart` text,
          `custom_data` text,
          `address_full` tinyint(1) NOT NULL DEFAULT '0',
          `amazon_ack` tinyint(1) NOT NULL DEFAULT '0',
          `amazon_ack_sendtime` datetime DEFAULT NULL,
          `date_created` datetime DEFAULT NULL,
          PRIMARY KEY (`amazon_order_id`),
          KEY `orders_id` (`orders_id`)
        )
        ");

      $params = array('MODULE_PAYMENT_AMAZON_PAYMENT_STATUS' => array('title' => 'Enable Amazon Payment Checkout',
                          'desc' => 'Do you want to accept Amazon payments?',
                          'value' => 'False',
                          'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
                          ),
                        'MODULE_PAYMENT_AMAZON_PAYMENT_AMAZON_SITE' => array('title' => 'Transaction Server',
                          'desc' => 'Gateway server to process transactions?',
                          'value' => 'DE',
                          'set_function' => 'tep_cfg_select_option(array(\'UK\', \'DE\'), ',
                          ),
                        'MODULE_PAYMENT_AMAZON_PAYMENT_MODE' => array('title' => 'Transaction Server Mode',
                          'desc' => 'Use the live or testing (sandbox) gateway server to process transactions?',
                          'value' => 'Sandbox',
                          'set_function' => 'tep_cfg_select_option(array(\'Production\', \'Sandbox\'), ',
                          ),
                        'MODULE_PAYMENT_AMAZON_PAYMENT_MERCHANT_ID' => array('title' => 'MERCHANT_ID',
                          'value' => 'AQGJZL2WICFPV',
                          ),
                        'MODULE_PAYMENT_AMAZON_PAYMENT_ACCESS_KEY_ID' => array('title' => 'ACCESS_KEY_ID',
                          'value' => 'AKIAJ467QCPOWR4U3UVA',
                          ),
                        'MODULE_PAYMENT_AMAZON_PAYMENT_SECRET_ACCESS_KEY' => array('title' => 'SECRET_ACCESS_KEY',
                          'value' => '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',
                          ),
                        'MODULE_PAYMENT_AMAZON_PAYMENT_CLIENT_ID' => array('title' => 'CLIENT_ID',
                          'value' => '',
                          ),
                        'MODULE_PAYMENT_AMAZON_PAYMENT_CAPTURE' => array('title' => 'CAPTURE',
                          'value' => 'Authorize',
                          'set_function' => 'tep_cfg_select_option(array(\'Authorize\', \'Capture\'), ',
                          ),
                        /*'MODULE_PAYMENT_AMAZON_PAYMENT_MARKETPLACE_ID' => array('title' => 'MARKETPLACE_ID',
                          'value' => 'AVWY87JS6QRCI',
                          ),
                        'MODULE_PAYMENT_AMAZON_PAYMENT_MERCHANT_TOKEN' => array('title' => 'MERCHANT_TOKEN',
                          'value' => 'M_ALEXDETEST_1197002',
                          ),
                        'MODULE_PAYMENT_AMAZON_PAYMENT_CURRENCY' => array('title' => 'TRANSACTION CURRENCY',
                          'value' => 'GBP',
                          'set_function' => 'amazon_inline::cfg_choose_currency(',
                          ),*/
                        'MODULE_PAYMENT_AMAZON_PAYMENT_BUTTON_SIZE' => array('title' => 'Button Size',
                          'value' => 'small',
                          'set_function' => 'tep_cfg_select_option(array(\'small\', \'medium\', \'large\', \'x-large\'), ',
                          ),
                        'MODULE_PAYMENT_AMAZON_PAYMENT_BUTTON_COLOR' => array('title' => 'Button Color',
                          'value' => 'Gold',
                          'set_function' => 'tep_cfg_select_option(array(\'Gold\', \'LightGray\', \'DarkGray\'), ',
                          ),
                        'MODULE_PAYMENT_AMAZON_PAYMENT_BUTTON_SITEBG' => array('title' => 'Button Style - Site Background',
                          'value' => 'white',
                          'set_function' => 'tep_cfg_select_option(array(\'white\', \'light\', \'dark\'), ',
                          ),
                        'MODULE_PAYMENT_AMAZON_PAYMENT_BUY_IMMEDIATELLY' => array('title' => 'Show buy now button at product page',
                          'desc' => 'Do you want to display the button on the product info page',
                          'value' => 'True',
                          'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
                          ),


/*                        'MODULE_PAYMENT_AMAZON_PAYMENT_NOTIFICATION_URL' => array('title' => 'Notify URL',
                          'desc' => 'Relative from site root or full path (need https)',
                          'value' => 'ext/modules/payment/amazon_inline/notify_cbai.php',
                          ),*/
                        'MODULE_PAYMENT_AMAZON_PAYMENT_ORDER_STATUS_ID' => array('title' => 'Set Order Status',
                          'desc' => 'Set the status of orders made with this payment module to this value',
                          'value' => $status_id,
                          'set_function' => 'tep_cfg_pull_down_order_statuses(',
                          'use_function' => '\\common\\helpers\\Order::get_order_status_name'),
                        'MODULE_PAYMENT_AMAZON_PAYMENT_NEW_ORDER_NOTIFY_ORDER_STATUS_ID' => array('title' => 'Set Order Status on Authorize',
                          'desc' => 'Set the status of orders made with this payment module to this value',
                          'value' => $status_id_n,
                          'set_function' => 'tep_cfg_pull_down_order_statuses(',
                          'use_function' => '\\common\\helpers\\Order::get_order_status_name'),
                        'MODULE_PAYMENT_AMAZON_PAYMENT_RTS_NOTIFY_ORDER_STATUS_ID' => array('title' => 'Set Order Status on Capture',
                          'desc' => 'Set the status of orders made with this payment module to this value',
                          'value' => $status_id_n_r,
                          'set_function' => 'tep_cfg_pull_down_order_statuses(',
                          'use_function' => '\\common\\helpers\\Order::get_order_status_name'),
                        'MODULE_PAYMENT_AMAZON_PAYMENT_CANCEL_NOTIFY_ORDER_STATUS_ID' => array('title' => 'Set Order Status on Cancel',
                          'desc' => 'Set the status of orders made with this payment module to this value',
                          'value' => $status_id_c,
                          'set_function' => 'tep_cfg_pull_down_order_statuses(',
                          'use_function' => '\\common\\helpers\\Order::get_order_status_name'),
                        'MODULE_PAYMENT_AMAZON_PAYMENT_ZONE' => array('title' => 'Payment Zone',
                                                             'desc' => 'If a zone is selected, only enable this payment method for that zone.',
                                                             'value' => '0',
                                                             'set_function' => 'tep_cfg_pull_down_zone_classes(',
                                                             'use_function' => '\common\helpers\Zones::get_zone_class_title'),
                        'MODULE_PAYMENT_AMAZON_PAYMENT_DEBUG_EMAIL' => array('title' => 'Debug E-Mail Address',
                          'desc' => 'All parameters of an invalid transaction will be sent to this email address.',
                          'value' => 'vkoshelev@holbi.co.uk',
                          ),
                        'MODULE_PAYMENT_AMAZON_PAYMENT_SORT_ORDER' => array('title' => 'Sort order of display.',
                          'desc' => 'Sort order of display. Lowest is displayed first.',
                          'value' => '0',
                          ),
                      );

      return $params;
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


    function getProductType($id, $attributes) {
      foreach ( $attributes as $a ) {
        $virtual_check_query = tep_db_query("select pad.products_attributes_id from " . TABLE_PRODUCTS_ATTRIBUTES . " pa, " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad where pa.products_id = '" . (int)$id . "' and pa.options_values_id = '" . (int)$a['value_id'] . "' and pa.products_attributes_id = pad.products_attributes_id limit 1");

        if ( tep_db_num_rows($virtual_check_query) == 1 ) {
          return 'Digital';
        }
      }

      return 'Physical';
    }

    function sendDebugEmail($response = array()) {
      if (tep_not_null(MODULE_PAYMENT_AMAZON_PAYMENT_DEBUG_EMAIL)) {
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
          \common\helpers\Mail::send('', MODULE_PAYMENT_AMAZON_PAYMENT_DEBUG_EMAIL, 'Amazon Pay Debug E-Mail', trim($email_body), STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
        }
      }
    }

    public static function getMerchantId() { return (defined('MODULE_PAYMENT_AMAZON_PAYMENT_MERCHANT_ID')?MODULE_PAYMENT_AMAZON_PAYMENT_MERCHANT_ID:''); }
    public static function getAccessKeyId() { return (defined('MODULE_PAYMENT_AMAZON_PAYMENT_ACCESS_KEY_ID')?MODULE_PAYMENT_AMAZON_PAYMENT_ACCESS_KEY_ID:''); }
    public static function getSecretAccessKey() { return (defined('MODULE_PAYMENT_AMAZON_PAYMENT_SECRET_ACCESS_KEY')?MODULE_PAYMENT_AMAZON_PAYMENT_SECRET_ACCESS_KEY:''); }
    public static function getClientId() { return (defined('MODULE_PAYMENT_AMAZON_PAYMENT_CLIENT_ID')?MODULE_PAYMENT_AMAZON_PAYMENT_CLIENT_ID:''); }
    public static function getButtonSize() { return (defined('MODULE_PAYMENT_AMAZON_PAYMENT_BUTTON_SIZE')?MODULE_PAYMENT_AMAZON_PAYMENT_BUTTON_SIZE:'medium'); }
    public static function getButtonColor() { return (defined('MODULE_PAYMENT_AMAZON_PAYMENT_BUTTON_COLOR')?MODULE_PAYMENT_AMAZON_PAYMENT_BUTTON_COLOR:'Gold'); }
    public static function getButtonSiteBackground() { return (defined('MODULE_PAYMENT_AMAZON_PAYMENT_BUTTON_SITEBG')?MODULE_PAYMENT_AMAZON_PAYMENT_BUTTON_SITEBG:'white'); }
    public static function instCapture() { return (defined('MODULE_PAYMENT_AMAZON_PAYMENT_CAPTURE') && MODULE_PAYMENT_AMAZON_PAYMENT_CAPTURE=='Capture'?true:false); }
    public static function getDefaultStatus() { return (defined('MODULE_PAYMENT_AMAZON_PAYMENT_ORDER_STATUS_ID') && MODULE_PAYMENT_AMAZON_PAYMENT_ORDER_STATUS_ID>0?MODULE_PAYMENT_AMAZON_PAYMENT_ORDER_STATUS_ID:0); }
    public static function getAuthStatus() { return (defined('MODULE_PAYMENT_AMAZON_PAYMENT_NEW_ORDER_NOTIFY_ORDER_STATUS_ID') && MODULE_PAYMENT_AMAZON_PAYMENT_NEW_ORDER_NOTIFY_ORDER_STATUS_ID>0?MODULE_PAYMENT_AMAZON_PAYMENT_NEW_ORDER_NOTIFY_ORDER_STATUS_ID:0); }
    public static function getCancelStatus() { return (defined('MODULE_PAYMENT_AMAZON_PAYMENT_CANCEL_NOTIFY_ORDER_STATUS_ID') && MODULE_PAYMENT_AMAZON_PAYMENT_CANCEL_NOTIFY_ORDER_STATUS_ID>0?MODULE_PAYMENT_AMAZON_PAYMENT_CANCEL_NOTIFY_ORDER_STATUS_ID:0); }
    public static function getCaptureStatus() { return (defined('MODULE_PAYMENT_AMAZON_PAYMENT_RTS_NOTIFY_ORDER_STATUS_ID') && MODULE_PAYMENT_AMAZON_PAYMENT_RTS_NOTIFY_ORDER_STATUS_ID>0?MODULE_PAYMENT_AMAZON_PAYMENT_RTS_NOTIFY_ORDER_STATUS_ID:0); }

    public static function getWidgetUrl() {
      $currency = \Yii::$app->settings->get('currency');

      if (MODULE_PAYMENT_AMAZON_PAYMENT_MODE == 'Sandbox') {
        if ($currency == 'GBP') {
          $ret = 'https://static-eu.payments-amazon.com/OffAmazonPayments/gbp/sandbox/lpa/js/Widgets.js';
        } else {
          $ret = 'https://static-eu.payments-amazon.com/OffAmazonPayments/de/sandbox/lpa/js/Widgets.js';
        }
      } else {
        if ($currency == 'GBP') {
          $ret = 'https://static-eu.payments-amazon.com/OffAmazonPayments/uk/lpa/js/Widgets.js';
        } else {
          $ret = 'https://static-eu.payments-amazon.com/OffAmazonPayments/de/lpa/js/Widgets.js';
        }
      }
      return $ret;
    }

    public static function getTokenUrl() {
      $currency = \Yii::$app->settings->get('currency');

      if (MODULE_PAYMENT_AMAZON_PAYMENT_MODE == 'Sandbox') {
        if ($currency == 'GBP') {
          $ret = 'https://api.sandbox.amazon.co.uk/auth/o2/tokeninfo';
        } else {
          $ret = 'https://api.sandbox.amazon.de/auth/o2/tokeninfo';
        }
      } else {
        if ($currency == 'GBP') {
          $ret = 'https://api.amazon.co.uk/auth/o2/tokeninfo';
        } else {
          $ret = 'https://api.amazon.de/auth/o2/tokeninfo';
        }
      }
      return $ret;
    }

    public static function getProfileUrl() {
      $currency = \Yii::$app->settings->get('currency');

      if (MODULE_PAYMENT_AMAZON_PAYMENT_MODE == 'Sandbox') {
        if ($currency == 'GBP') {
          $ret = 'https://api.sandbox.amazon.co.uk/user/profile';
        } else {
          $ret = 'https://api.sandbox.amazon.de/user/profile';
        }
      } else {
        if ($currency == 'GBP') {
          $ret = 'https://api.amazon.co.uk/user/profile';
        } else {
          $ret = 'https://api.amazon.de/user/profile';
        }
      }
      return $ret;
    }

    public static function getMWSPayUrl() {
      if (MODULE_PAYMENT_AMAZON_PAYMENT_MODE == 'Sandbox') {
          $ret = 'https://mws-eu.amazonservices.com/OffAmazonPayments_Sandbox/2013-01-01';
      } else {
          $ret = 'https://mws-eu.amazonservices.com/OffAmazonPayments/2013-01-01';
      }
      return $ret;
    }

    public static function getMWSSellerUrl() {return 'https://mws-eu.amazonservices.com//Sellers/2011-07-01';    }

    public static function getWeightUnit() { return 'KG'; }
    public static function getPaymentVersion() { return '2013-01-01'; }

    public function getOrderReferenceDetails ($ref) {
      $result = false;

      if (!empty($ref)) {
        $params = array(
          'AWSAccessKeyId' => self::getAccessKeyId(),
          'Action' => 'GetOrderReferenceDetails',
          'AmazonOrderReferenceId' => $ref,
          'SellerId' => self::getMerchantId(),
          'SignatureMethod' => 'HmacSHA256',
          'SignatureVersion' => '2',
          //'Timestamp' => date('Y-m-d').'T'.date('H:i:s').'Z',
          'Timestamp' => gmdate("Y-m-d\TH:i:s\\Z", time()),
          'Version' => '2013-01-01'
        );
        $xml = $this->sendSignedRequest(self::getMWSPayUrl(), $params);

        $xml = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $xml);
        //replace whitespace similar characters

        $xml_arr = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);

        $result = json_decode(json_encode((array)$xml_arr), true);

        $aod = self::updateDBLog(
           (['amazon_order_id' => $ref,
            'amazon_status' => $result['GetOrderReferenceDetailsResult']['OrderReferenceDetails']['OrderReferenceStatus']['State'],
            'custom_data' => serialize($result)
          ]), " amazon_order_id = '" . tep_db_input($ref) . "'");
        if ($aod['orders_id']>0) {
          self::updateOrderCustomer($result, $aod['orders_id']);
        }
      }

      return $result;
    }

    public function getAuthDetails ($ref) {
      $result = false;

      if (!empty($ref)) {
        $params = array(
          'AWSAccessKeyId' => self::getAccessKeyId(),
          'Action' => 'GetAuthorizationDetails',
          'AmazonAuthorizationId' => $ref,
          'SellerId' => self::getMerchantId(),
          'SignatureMethod' => 'HmacSHA256',
          'SignatureVersion' => '2',
          //'Timestamp' => date('Y-m-d').'T'.date('H:i:s').'Z',
          'Timestamp' => gmdate("Y-m-d\TH:i:s\\Z", time()),
          'Version' => '2013-01-01'
        );
        $xml = $this->sendSignedRequest(self::getMWSPayUrl(), $params);

        $xml = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $xml);
        //replace whitespace similar characters

        $xml_arr = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);

        $result = json_decode(json_encode((array)$xml_arr), true);

        $aod = self::updateDBLog(
           (['amazon_auth_id' => $ref,
            'amazon_auth_status' => $result['GetAuthorizationDetailsResult']['AuthorizationDetails']['AuthorizationStatus']['State'],
            'custom_data' => serialize($result)
          ]), " amazon_auth_id = '" . tep_db_input($ref) . "'");

        if ($aod['orders_id']>0) {
          self::updateOrderCustomer($result, $aod['orders_id']);
        }

      }

      return $result;
    }

    public function getCaptureDetails ($ref) {
      $result = false;

      if (!empty($ref)) {
        $params = array(
          'AWSAccessKeyId' => self::getAccessKeyId(),
          'Action' => 'GetCaptureDetails',
          'AmazonCaptureId' => $ref,
          'SellerId' => self::getMerchantId(),
          'SignatureMethod' => 'HmacSHA256',
          'SignatureVersion' => '2',
          //'Timestamp' => date('Y-m-d').'T'.date('H:i:s').'Z',
          'Timestamp' => gmdate("Y-m-d\TH:i:s\\Z", time()),
          'Version' => '2013-01-01'
        );
        $xml = $this->sendSignedRequest(self::getMWSPayUrl(), $params);

        $xml = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $xml);
        //replace whitespace similar characters

        $xml_arr = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);

        $result = json_decode(json_encode((array)$xml_arr), true);

        if (!self::hasError($result) && isset($result['GetCaptureDetailsResult']['CaptureDetails']['AmazonCaptureId']) ) {
          $aod = self::updateDBLog(
             (['amazon_capture_id' => $ref,
              'amazon_capture_status' => $result['GetCaptureDetailsResult']['CaptureDetails']['CaptureStatus']['State'],
              'custom_data' => serialize($result)
            ]), " amazon_capture_id = '" . tep_db_input($ref) . "'");
        } else {
          Yii::warning($result, 'amazon_payment_capture');
        }
      }

      return $result;
    }

    public function getRefundDetails ($ref) {
      $result = false;

      if (!empty($ref)) {
        $params = array(
          'AWSAccessKeyId' => self::getAccessKeyId(),
          'Action' => 'GetRefundDetails',
          'AmazonRefundId' => $ref,
          'SellerId' => self::getMerchantId(),
          'SignatureMethod' => 'HmacSHA256',
          'SignatureVersion' => '2',
          //'Timestamp' => date('Y-m-d').'T'.date('H:i:s').'Z',
          'Timestamp' => gmdate("Y-m-d\TH:i:s\\Z", time()),
          'Version' => '2013-01-01'
        );
        $xml = $this->sendSignedRequest(self::getMWSPayUrl(), $params);

        $xml = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $xml);
        //replace whitespace similar characters

        $xml_arr = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);

        $result = json_decode(json_encode((array)$xml_arr), true);

        if (!self::hasError($result) && isset($result['GetRefundDetailsResult']['RefundDetails']['AmazonRefundId']) ) {
          $aod = self::updateDBLog(
             (['amazon_refund_id' => $ref,
              'amazon_refund_status' => $result['GetRefundDetailsResult']['RefundDetails']['RefundStatus']['State'],
              'custom_data' => serialize($result)
            ]), " amazon_refund_id = '" . tep_db_input($ref) . "'");
        } else {
          Yii::warning($result, 'amazon_payment_capture');
        }
      }

      return $result;
    }



    public function sendSignedRequest($url, $params, $method = 'POST') {
      $params_str = '';
      foreach ($params as $param => $value) {
        if (strlen($value)) {
          $params_str .= ($params_str?'&':'') . $param . '=' . str_replace('%7E', '~',rawurlencode($value));
        }
      }

      $endpoint = parse_url ($url);
      $host = $endpoint['host'];
      $uri = array_key_exists('path', $endpoint) ? $endpoint['path'] : "/";

//nice to have... but suppose API url does't require encoding; - $uriencoded = implode("/", array_map(array($this, "_urlencode"), explode("/", $uri)));

      $request = $method."\n".$host."\n".$uri."\n".$params_str;
      $signature = base64_encode(hash_hmac('sha256', $request, self::getSecretAccessKey(), true));

      $signature = str_replace('%7E', '~', rawurlencode($signature));

      $link = $url."?".$params_str.'&Signature='.$signature;

      $result = $this->sendRequest($link, $method);

      Yii::trace($result);
          //debug($result);

      return $result;

    }

    public function sendRequest($link, $method = 'POST'){
        $curl = curl_init($link);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        if ($method == 'POST') {
          curl_setopt($curl, CURLOPT_POST, true);
          curl_setopt($curl, CURLOPT_POSTFIELDS, '');
        }

        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }

    public function processIPN() {
      //2do
      Yii::warning('2do', 'amazon_payment');
    }

    public function isOnline() {
      return true;
    }
}