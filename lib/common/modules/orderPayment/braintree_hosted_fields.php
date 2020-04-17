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
use common\helpers\Output;
use common\classes\modules\TransactionalInterface;
use common\classes\modules\TransactionSearchInterface;
use common\helpers\OrderPayment as OrderPaymentHelper;

require_once('lib/Braintree.php');

class braintree_hosted_fields extends ModulePayment implements TransactionalInterface, TransactionSearchInterface {

    var $code, $title, $description, $enabled;

    protected $defaultTranslationArray = [
        'MODULE_PAYMENT_BRAINTREE_HOSTED_TEXT_PUBLIC_TITLE' => 'Credit Card / Debit Card',
        'MODULE_PAYMENT_BRAINTREE_HOSTED_TEXT_DESCRIPTION' => 'Braintree',
        'MODULE_PAYMENT_BRAINTREE_HOSTED_ERROR_ADMIN_CONFIGURATION' => 'This module will not load until the Client Authorization parameter have been configured. Please edit and configure the settings of this module.',
        'MODULE_PAYMENT_BRAINTREE_HOSTED_ERROR_TITLE' => 'There has been an error processing your credit card',
        'MODULE_PAYMENT_BRAINTREE_HOSTED_ERROR_GENERAL' => 'Please try again and if problems persist, please try another payment method.'
    ];

    public function __construct() {
        parent::__construct();

        $this->code = 'braintree_hosted_fields';

        $this->title = MODULE_PAYMENT_BRAINTREE_HOSTED_TEXT_PUBLIC_TITLE;
        $this->description = MODULE_PAYMENT_BRAINTREE_HOSTED_TEXT_DESCRIPTION;
        $this->sort_order = defined('MODULE_PAYMENT_BRAINTREE_HOSTED_SORT_ORDER') ? MODULE_PAYMENT_BRAINTREE_HOSTED_SORT_ORDER : 0;
        $this->enabled = defined('MODULE_PAYMENT_BRAINTREE_HOSTED_STATUS') && (MODULE_PAYMENT_BRAINTREE_HOSTED_STATUS == 'True') ? true : false;
        $this->order_status = defined('MODULE_PAYMENT_BRAINTREE_HOSTED_ORDER_STATUS_ID') && ((int) MODULE_PAYMENT_BRAINTREE_HOSTED_ORDER_STATUS_ID > 0) ? (int) MODULE_PAYMENT_BRAINTREE_HOSTED_ORDER_STATUS_ID : 0;

        if (defined('MODULE_PAYMENT_BRAINTREE_HOSTED_STATUS')) {
            if ($this->_getServerType() != 'Live' ) {
                $this->title .= ' [Test]';
            }
        }

        if ($this->enabled === true) {
            if (!tep_not_null(MODULE_PAYMENT_BRAINTREE_HOSTED_CLIENT_AUTHORIZATION)) {
                $this->description = '<div class="secWarning">' . MODULE_PAYMENT_BRAINTREE_HOSTED_ERROR_ADMIN_CONFIGURATION . '</div>' . $this->description;
                $this->enabled = false;
            }
        }

        if ($this->enabled === true) {
            $this->update_status();
        }
    }

    private function _getServerType(){
        return (strpos(MODULE_PAYMENT_BRAINTREE_HOSTED_CLIENT_AUTHORIZATION, 'sandbox') !== false? 'Test': 'Live');
    }

    function update_status() {

        if (($this->enabled == true) && ((int) MODULE_PAYMENT_BRAINTREE_HOSTED_ZONE > 0)) {
            $check_flag = false;
            $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_BRAINTREE_HOSTED_ZONE . "' and zone_country_id = '" . $this->billing['country']['id'] . "' order by zone_id");
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
            'module' => $this->title,
            'fields' => [$this->get_fields()]
        );
    }

    function get_fields() {

        $content = '';
        if ($this->enabled) {
            if (MODULE_PAYMENT_BRAINTREE_PAYPAL_INTEGRATE == 'True'){
                $content .= '<div id="paypal-container"></div>';
            } else {
            $content .= '<!-- Add animations on Braintree Hosted Fields events -->

<!-- Some test Card numbers
4111 1111 1111 1111: Visa
5555 5555 5555 4444: MasterCard
3714 496353 98431: American Express
-->

<div>
    <div class="cardinfo-card-number">
      <label class="cardinfo-label" for="card-number-btree">'.ENTRY_CARD_NUMBER.'</label>
      <div class="input-wrapper" id="card-number-btree"></div>
      <div id="card-image"></div>
    </div>

    <div class="cardinfo-wrapper">
      <div class="cardinfo-exp-date">
        <label class="cardinfo-label" for="expiration-date-btree">'.ENTRY_CARD_EXPIRES.'</label>
        <div class="input-wrapper" id="expiration-date-btree"></div>
      </div>

      <div class="cardinfo-cvv">
        <label class="cardinfo-label" for="cvv-btree">'.ENTRY_CARD_CVV.'</label>
        <div class="input-wrapper" id="cvv-btree"></div>
      </div>
    </div>
</div>
';
            }
            $content .= $this->getSubmitCardDetailsJavascript();
        }
        $confirmation = array('title' => $content);

        return $confirmation;
    }

    function getSubmitCardDetailsJavascript() {
        $key = MODULE_PAYMENT_BRAINTREE_HOSTED_CLIENT_AUTHORIZATION;
        $threeD = MODULE_PAYMENT_BRAINTREE_HOSTED_3DSECURE == 'True' ? '1':'0';
        $usePaypal = MODULE_PAYMENT_BRAINTREE_PAYPAL_INTEGRATE == 'True'? '1': '0';
        $order = $this->manager->getOrderInstance();
        if (MODULE_PAYMENT_BRAINTREE_HOSTED_3DSECURE == 'True'){
            $key = $this->getThreeDToken();
        }
        $_error = json_encode(MODULE_PAYMENT_BRAINTREE_HOSTED_ERROR_GENERAL);
        $transaction_currency = \Yii::$app->settings->get('currency');
        $total = $this->formatCurrencyRaw($order->info['total_inc_tax'], $transaction_currency);
        $order = $this->manager->getOrderInstance();
        $address = [];
        $addressEnable = false;
        if ($this->manager->isShippingNeeded()){
            $address = [
                'recipientName' => $order->delivery['name'],
                'streetAddress' => $order->delivery['street_address'],
                'locality' => $order->delivery['city'],
                'countryCodeAlpha2' => $order->delivery['country']['iso_code_2'],
                'postalCode' => $order->delivery['postcode'],
                'region' => $order->delivery['state'],
                'editable' => false
            ];
            $addressEnable = true;
        }
        $address = json_encode($address);
        $js = <<<EOD
<script type="text/javascript">
var braintree = false;
tl(function(){
    var general_error = '{$_error}';
    var done = false;
    var _brainTreeClient = document.createElement('script');
        if ('{$usePaypal}' == '1'){
            _brainTreeClient.setAttribute('src', 'https://js.braintreegateway.com/js/braintree-2.32.1.min.js');
        } else {
            _brainTreeClient.setAttribute('src', 'https://js.braintreegateway.com/web/3.42.0/js/client.min.js');
        }
        _brainTreeClient.onload = handleLoadClient;
        document.head.appendChild(_brainTreeClient);
    var _brainTree3D;
    if ({$threeD} == '1'){
        _brainTree3D = document.createElement('script');
        _brainTree3D.setAttribute('src', 'https://js.braintreegateway.com/web/3.42.0/js/three-d-secure.min.js');
        _brainTree3D.onload = handleLoad3D;
        document.head.appendChild(_brainTree3D);
    }
    var _brainTreeFields = document.createElement('script');
    _brainTreeFields.setAttribute('src', 'https://js.braintreegateway.com/web/3.42.0/js/hosted-fields.min.js');
    _brainTreeFields.onload = handleLoadFields;
    document.head.appendChild(_brainTreeFields);
    

    var stateClient = false;
    var stateFields = false;
    var state3d = false;
    var useBrainTree = document.querySelector('input[value=braintree_hosted_fields]').checked;
    if (!useBrainTree){
        $('.payment_class_braintree_hosted_fields .sub-item').hide();
    } else {
        $('button[type="submit"]').prop('disabled', 'disabled');
    }

    $('input[name=payment]').change(function(e){
        useBrainTree = e.target.defaultValue == '{$this->code}';
        if (useBrainTree) {
            $('.payment_class_braintree_hosted_fields .sub-item').show();
            $('button[type="submit"]').prop('disabled', 'disabled');
            brainTreef();
        } else {
            $('.payment_class_braintree_hosted_fields .sub-item').hide();
            $('button[type="submit"]').prop('disabled', false);
        }
    })
    var braintreeCreated = false;
    function brainTreef(){
        if (stateFields && stateClient){
            if (!useBrainTree) return;
            var form = document.querySelector('#frmCheckout');
            var submit = document.querySelector('#frmCheckout button[type="submit"]');
            if (!braintreeCreated){
                if ('{$usePaypal}' == '1'){
                    $(submit).prop('disabled', false);
                    var _hasNonce = false;
                    $(form).submit(function(event){
                        if ($('input[name=payment]:checked').val() == '{$this->code}'){
                            if (_hasNonce) return true;
                            event.preventDefault();
                            braintree.setup("{$key}", "custom", {
                                onReady: function (integration) {
                                    integration.paypal.initAuthFlow();;
                                },
                                paypal: {
                                    container: "paypal-container",
                                    singleUse: true,
                                    amount: '{$order->info['total_inc_tax']}',
                                    currency: '{$order->info['currency']}',
                                    intent: 'sale',
                                    headless: true,
                                    enableShippingAddress: '{$addressEnable}',
                                    shippingAddressOverride: {$address}
                                },
                                onPaymentMethodReceived: function (payload) {
                                    if (!$(form).get('[name=token]:hidden')){
                                        $(form).append('<input type="hidden" name="braintree_token" value="'+payload.nonce+'">');
                                    } else {
                                        $('[name="braintree_token"]:hidden').val(payload.nonce);
                                    }
                                    _hasNonce = true;
                                    $(form).trigger(event.type);
                                }
                            });
                        }
                    });
                    return
                }
                braintree.client.create({
                    authorization: '{$key}'
                }, function (err, clientInstance) {
                      if (err) {
                          //disable payment method
                          console.error(err);
                          return;
                      }

                      // Create input fields and add text styles
                      braintree.hostedFields.create({
                          client: clientInstance,
                          styles: {
                              'input': {
                                  'color': '#282c37',
                                  'font-size': '16px',
                                  'transition': 'color 0.1s',
                                  'line-height': '3'
                              },
                              // Style the text of an invalid input
                              'input.invalid': {
                                  'color': '#E53A40'
                              },
                              // placeholder styles need to be individually adjusted
                              '::-webkit-input-placeholder': {
                                'color': 'rgba(0,0,0,0.6)'
                              },
                              ':-moz-placeholder': {
                                'color': 'rgba(0,0,0,0.6)'
                              },
                              '::-moz-placeholder': {
                                'color': 'rgba(0,0,0,0.6)'
                              },
                              ':-ms-input-placeholder': {
                                'color': 'rgba(0,0,0,0.6)'
                              }
                          },
                          // Add information for individual fields
                          fields: {
                              number: {
                                  selector: '#card-number-btree',
                                  placeholder: '1111 1111 1111 1111'
                              },
                              cvv: {
                                  selector: '#cvv-btree',
                                  placeholder: '123'
                              },
                              expirationDate: {
                                  selector: '#expiration-date-btree',
                                  placeholder: '10 / ' + ((new Date()).getFullYear() + 1)
                              }
                          }
                      }, function (err, hostedFieldsInstance) {
                          if (err) {
                              console.error(err);
                              return;
                          }
                          braintreeCreated = true;

                          hostedFieldsInstance.on('validityChange', function (event) {
                          // Check if all fields are valid, then show submit button
                              var formValid = Object.keys(event.fields).every(function (key) {
                                return event.fields[key].isValid;
                              });
                              if (formValid) {
                                $(submit).prop('disabled', false);
                              } else {
                                $(submit).prop('disabled', 'disabled');
                              }
                          });

                          hostedFieldsInstance.on('empty', function (event) {
                              $('header').removeClass('header-slide');
                              $('#card-image').removeClass();
                              $(form).removeClass();
                          });

                          hostedFieldsInstance.on('cardTypeChange', function (event) {
                              // Change card bg depending on card type
                              if (event.cards.length === 1) {
                                  $(form).removeClass().addClass(event.cards[0].type);
                                  $('#card-image').removeClass().addClass(event.cards[0].type);
                                  $('header').addClass('header-slide');

                                  // Change the CVV length for AmericanExpress cards
                                  if (event.cards[0].code.size === 4) {
                                      hostedFieldsInstance.setAttribute({
                                          field: 'cvv',
                                          attribute: 'placeholder',
                                          value: '1234'
                                      });
                                  }
                              } else {
                                  hostedFieldsInstance.setAttribute({
                                      field: 'cvv',
                                      attribute: 'placeholder',
                                      value: '123'
                                  });
                              }
                          });

                          //submit.removeEventListener('click', function(){ });
                          //submit.addEventListener('click', function (event) {
                          $(form).submit(function(event){
                              //$('button[type="submit"]').prop('disabled', false);
                              $(submit).prop('disabled', false);
                              if ($('input[name=payment]:checked').val() == '{$this->code}'){
                                if ($(form).has('input[name=braintree_token]').size()) return true;

                                event.preventDefault();
                                hostedFieldsInstance.tokenize(function (err, payload) {
                                    if (err) {
                                       alertMessage(document.querySelector('input[value=braintree_hosted_fields]').nextElementSibling.innerText+': '+err.message);
                                       console.error(err);
                                       return;
                                    }

                                    if (payload.nonce){
                                        if ({$threeD} == '1'){
                                            braintree.threeDSecure.create({
                                                client: clientInstance
                                            }, function (threeDSecureErr, threeDSecureInstance) {
                                                if (threeDSecureErr) {
                                                  // Handle error in 3D Secure component creation
                                                  return;
                                                }

                                                threeDSecure = threeDSecureInstance;
                                                var my3DSContainer = document.createElement('a');

                                                threeDSecure.verifyCard({
                                                  amount: '{$total}',
                                                  nonce: payload.nonce,
                                                  addFrame: function (err, iframe) {
                                                    // Set up your UI and add the iframe.
                                                    $(my3DSContainer).html(iframe);
                                                    $(my3DSContainer).popUp({
                                                        event:'show'
                                                    }).trigger('click');
                                                    //document.body.appendChild(my3DSContainer);
                                                  },
                                                  removeFrame: function () {
                                                    // Remove UI that you added in addFrame.
                                                    $(my3DSContainer).remove();
                                                  }
                                                }, function (err, response) {
                                                    if (err){
                                                        return false;
                                                    }

                                                    if (response.liabilityShifted && response.liabilityShiftPossible){
                                                        if (!$(form).get('[name=token]:hidden')){
                                                            $(form).append('<input type="hidden" name="braintree_token" value="'+response.nonce+'">');
                                                        } else {
                                                            $('[name="braintree_token"]:hidden').val(response.nonce);
                                                        }
                                                        //form.submit();
                                                        $(form).trigger(event.type);
                                                        return true;
                                                    } else {
                                                        alertMessage(general_error);
                                                    }
                                                });
                                            });
                                        } else {
                                            if (!$(form).get('[name=token]:hidden')){
                                                $(form).append('<input type="hidden" name="braintree_token" value="'+payload.nonce+'">');
                                            } else {
                                                $('[name="braintree_token"]:hidden').val(payload.nonce);
                                            }
                                            $(form).trigger(event.type);
                                            return true;
                                            //form.submit();
                                        }
                                    } else {
                                        alertMessage(general_error);
                                        return false;
                                    }
                                  });
                              } else {
                                //form.submit();
                                return true;
                              }
                      });
                    });
                });

            } else {//oef !bratntree
                $('.payment_class_braintree_hosted_fields .sub-item').show();
            }
        }//eof ready

    }//end brainTreeF

    function handleLoadClient(e) {
        stateClient = e.returnValue;
        if (stateClient) {
            brainTreef();
        }
    }

    function handleLoadFields(e) {
        stateFields = e.returnValue;
        if (stateFields) {
            brainTreef();
        }
    }

    function handleLoad3D(e) {
        state3d = e.returnValue;
    }
});

</script>
EOD;

        return $js;
    }

    function pre_confirmation_check() {

    }

    function confirmation() {
        $content = "";
        if (is_array($_POST)) {
            if (isset($_POST['braintree_token'])) {
                $content .= "<input type='hidden' name='braintree_token' value='" . $_POST['braintree_token'] . "'>";
                $this->manager->set('braintree_token', $_POST['braintree_token']);
            }
        }
        return array('title' => $content);
    }

    function setupCredentials($server = null) {
      $status = ((isset($server) && ($server === 'Live')) || (!isset($server) && (MODULE_PAYMENT_BRAINTREE_HOSTED_STATUS === 'True'))) ? '1' : '0';

      \Braintree_Configuration::environment($status === '1' ? 'production' : 'sandbox');
      \Braintree_Configuration::merchantId(MODULE_PAYMENT_BRAINTREE_HOSTED_MERCHANT_ID);
      \Braintree_Configuration::publicKey(MODULE_PAYMENT_BRAINTREE_HOSTED_PUBLIC_KEY);
      \Braintree_Configuration::privateKey(MODULE_PAYMENT_BRAINTREE_HOSTED_SECRET_KEY);

    }

    function getThreeDToken(){
        $this->setupCredentials($this->_getServerType());
        return \Braintree_Configuration::gateway()->clientToken()->generate();
    }

    function getMerchantAccountId($currency) {

        $currencies_ma = MODULE_PAYMENT_BRAINTREE_HOSTED_MERCHANT_CU_ID;
        if (empty($currencies_ma)) return MODULE_PAYMENT_BRAINTREE_HOSTED_MERCHANT_ID;

        preg_match("/[;,:]*(\w*$currency?)[;,:]*/", $currencies_ma, $matches);

        if ($matches && isset($matches[1])){
            return trim($matches[1]);
        }
        return '';
    }

    function process_button() {
        return false;
    }

    function formatCurrencyRaw($total, $currency_code = null, $currency_value = null) {

        if ( !isset($currency_code) ) {
            $currency_code =  DEFAULT_CURRENCY;
        }

        if ( !isset($currency_value) || !is_numeric($currency_value) ) {
            $currencies = \Yii::$container->get('currencies');
            $currency_value = $currencies->currencies[$currency_code]['value'];
        }

        return number_format(self::round($total * $currency_value, $currencies->currencies[$currency_code]['decimal_places']), $currencies->currencies[$currency_code]['decimal_places'], '.', '');
    }

    function before_process() {

        $this->setupCredentials($this->_getServerType());

        $transaction_currency = \Yii::$app->settings->get('currency');
        $order = $this->manager->getOrderInstance();
        if ($this->manager->has('braintree_token')){
            $data = array(
                'amount' => $this->formatCurrencyRaw($order->info['total_inc_tax'], $transaction_currency),
                'paymentMethodNonce' => $this->manager->get('braintree_token'),
                'merchantAccountId' => $this->getMerchantAccountId($transaction_currency),
                'options' => [
                    'submitForSettlement' => true
                ]
            );
        } else { //may be useful on confirmation page
            $this->manager->set('braintree_token', $_POST['braintree_token']);
            $data = array(
                'paymentMethodNonce' => $_POST['braintree_token'],
                'amount' => $this->formatCurrencyRaw($order->info['total_inc_tax'], $transaction_currency),
                'merchantAccountId' => $this->getMerchantAccountId($transaction_currency),
                'customer' => array(
                  'firstName' => $order->customer['firstname'],
                  'lastName' => $order->customer['lastname'],
                  'company' => $order->customer['company'],
                  'phone' => $order->customer['telephone'],
                  'email' => $order->customer['email_address']
                ),
                'billing' => array(
                  'firstName' => $order->billing['firstname'],
                  'lastName' => $order->billing['lastname'],
                  'company' => $order->billing['company'],
                  'streetAddress' => $order->billing['street_address'],
                  'extendedAddress' => $order->billing['suburb'],
                  'locality' => $order->billing['city'],
                  'region' => \common\helpers\Zones::get_zone_code($order->billing['country']['id'], $order->billing['zone_id'], $order->billing['state']),
                  'postalCode' => $order->billing['postcode'],
                  'countryCodeAlpha2' => $order->billing['country']['iso_code_2']
                ),
                'options' => array()
            );
        }

        if ($this->manager->isShippingNeeded()) {
            $data['shipping'] = array(
              'firstName' => $order->delivery['firstname'],
              'lastName' => $order->delivery['lastname'],
              'company' => $order->delivery['company'],
              'streetAddress' => $order->delivery['street_address'],
              'extendedAddress' => $order->delivery['suburb'],
              'locality' => $order->delivery['city'],
              'region' => \common\helpers\Zones::get_zone_code($order->delivery['country']['id'], $order->delivery['zone_id'], $order->delivery['state']),
              'postalCode' => $order->delivery['postcode'],
              'countryCodeAlpha2' => $order->delivery['country']['iso_code_2']
            );
        }

        $data['channel'] = 'Trueloaded_BTapp_v1';

        try {
            $orderPaymentTransactionId = $this->manager->get('braintree_token');
            $orderPayment = $this->searchRecord($orderPaymentTransactionId);
            $orderPayment->orders_payment_id_parent = 0;
            $orderPayment->orders_payment_order_id = 0;
            $orderPayment->orders_payment_is_credit = 0;
            $orderPayment->orders_payment_status = OrderPaymentHelper::OPYS_PROCESSING;
            $orderPayment->orders_payment_amount = $this->formatCurrencyRaw($order->info['total_inc_tax'], $transaction_currency);
            $orderPayment->orders_payment_currency = trim($order->info['currency']);
            $orderPayment->orders_payment_currency_rate = (float)$order->info['currency_value'];
            $orderPayment->orders_payment_snapshot = json_encode(OrderPaymentHelper::getOrderPaymentSnapshot($order));
            $orderPayment->orders_payment_transaction_id = trim($orderPaymentTransactionId);
            $orderPayment->orders_payment_transaction_status = '';
            $orderPayment->orders_payment_transaction_commentary = '';
            $orderPayment->orders_payment_date_create = date('Y-m-d H:i:s');
            $orderPayment->save();
            
            $braintree_result = \Braintree_Transaction::sale($data);
            if ($braintree_result->success) {
                $currencies = \Yii::$container->get('currencies');
                $transaction_details = [
                    'Transaction Id' => $braintree_result->transaction->id,
                    'Transaction Amount' => $currencies->format($braintree_result->transaction->amount, true, $order->info['currency'], $order->info['currency_value']),
                    'Response Text' => $braintree_result->transaction->processorResponseText,
                    'Authorization Code'  => $braintree_result->transaction->processorAuthorizationCode,
                    'Payment Instrument' => $braintree_result->transaction->paymentInstrumentType,
                ];
                if (is_object($braintree_result->transaction->threeDSecureInfo)){
                    $transaction_details['3D Secure status'] = $braintree_result->transaction->threeDSecureInfo->status;
                }

                $this->manager->set('transaction_details', $transaction_details);
                if ($braintree_result->transaction->id){
                    $this->manager->set('tx_transaction_id', $braintree_result->transaction->id);
                }
                $orderPayment->orders_payment_transaction_id = $braintree_result->transaction->id;
                $orderPayment->orders_payment_status = OrderPaymentHelper::OPYS_SUCCESSFUL;
                $orderPayment->orders_payment_transaction_status = $braintree_result->transaction->status;
                $orderPayment->save(false);
                return true;
            } else {
                $orderPayment->orders_payment_status = OrderPaymentHelper::OPYS_REFUSED;
                $orderPayment->save(false);
                if (is_object($braintree_result->errors->forKey('transaction'))){
                    tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'error_message=' . urlencode($braintree_result->errors->forKey('transaction')->__get('errors')[0]->__get('message')), 'SSL'));
                }
                tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'error_message=' . urlencode(MODULE_PAYMENT_BRAINTREE_HOSTED_ERROR_TITLE), 'SSL'));
            }
        } catch (\Exception $e) {
            $this->sendDebugEmail($e);
        }
        tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'error_message=' . urlencode(MODULE_PAYMENT_BRAINTREE_HOSTED_ERROR_TITLE), 'SSL'));
    }

    function after_process() {

        $order = $this->manager->getOrderInstance();
        if ($order && $order->order_id){
            $sql_data_array = array('orders_id' => $order->order_id,
                'orders_status_id' => (defined('MODULE_PAYMENT_BRAINTREE_HOSTED_TRANSACTION_ORDER_STATUS_ID') && (int)MODULE_PAYMENT_BRAINTREE_HOSTED_TRANSACTION_ORDER_STATUS_ID >0? MODULE_PAYMENT_BRAINTREE_HOSTED_TRANSACTION_ORDER_STATUS_ID: $order->info['order_status']),
                'date_added' => 'now()',
                'customer_notified' => '0'
            );
            $comment = [];
            if ($this->manager->has('transaction_details')){
                foreach ($this->manager->get('transaction_details') as $field => $value){
                    $comment[] = $field.": ".$value;
                }
                $sql_data_array['comments'] = implode("\n", $comment);
            }
            tep_db_perform($order->table_prefix . TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
            if ($this->manager->has('tx_transaction_id')){
                $orderPayment = $this->searchRecord($this->manager->get('tx_transaction_id'));
                $orderPayment->orders_payment_order_id = $order->order_id;
                $orderPayment->orders_payment_snapshot = json_encode(OrderPaymentHelper::getOrderPaymentSnapshot($order));
                $orderPayment->orders_payment_transaction_commentary = implode("\n", $comment);
                $orderPayment->orders_payment_transaction_date = new \yii\db\Expression('now()');
                tep_db_query("update " . $order->table_prefix . TABLE_ORDERS . " set transaction_id='".strval($this->manager->get('tx_transaction_id'))."', last_modified = now() where orders_id = '" . (int)$order->order_id . "'");
                $braintree_result = $this->getTransactionDetails($this->manager->get('tx_transaction_id'));
                if ($braintree_result){
                    $invoice_id = $this->manager->getOrderSplitter()->getInvoiceId();
                    $this->manager->getTransactionManager($this)->addTransaction($braintree_result->id, $braintree_result->status, $braintree_result->amount, $invoice_id, 'Customer\'s payment');                    
                    $orderPayment->orders_payment_transaction_status = $braintree_result->status;
                }
                $orderPayment->save(false);
            }
        }

        $this->manager->remove('braintree_token');
        $this->manager->remove('transaction_details');
        $this->manager->remove('tx_transaction_id');
    }

    function get_error() {

        if (isset($_GET['error']) && !empty($_GET['error'])) {
            switch ($_GET['error']) {
                case 'cardstored':
                    $message = MODULE_PAYMENT_BRAINTREE_HOSTED_ERROR_CARDSTORED;
                    break;
            }
        }

        $error = array('title' => MODULE_PAYMENT_BRAINTREE_HOSTED_ERROR_TITLE,
            'error' => $message);

        return $error;
    }

    public function describe_status_key() {
        return new ModuleStatus('MODULE_PAYMENT_BRAINTREE_HOSTED_STATUS', 'True', 'False');
    }

    public function describe_sort_key() {
        return new ModuleSortOrder('MODULE_PAYMENT_BRAINTREE_HOSTED_SORT_ORDER');
    }

    public function configure_keys() {

        $status_id = defined('MODULE_PAYMENT_BRAINTREE_HOSTED_TRANSACTION_ORDER_STATUS_ID') ? MODULE_PAYMENT_BRAINTREE_HOSTED_TRANSACTION_ORDER_STATUS_ID : $this->getDefaultOrderStatusId();
        $status_id_p = defined('MODULE_PAYMENT_BRAINTREE_HOSTED_ORDER_STATUS_ID') ? MODULE_PAYMENT_BRAINTREE_HOSTED_ORDER_STATUS_ID : $this->getDefaultOrderStatusId();

        $params = array('MODULE_PAYMENT_BRAINTREE_HOSTED_STATUS' => array('title' => 'Enable BrainTree Module',
                'desc' => 'Do you want to accept BrainTree payments?',
                'value' => 'True',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '),
            'MODULE_PAYMENT_BRAINTREE_HOSTED_MERCHANT_ID' => array('title' => 'Merchant ID',
                'desc' => 'The BrainTree account Merchant ID to use.',
                'value' => ''),
            'MODULE_PAYMENT_BRAINTREE_HOSTED_MERCHANT_CU_ID' => array('title' => 'Merchant Currencies ID',
                'desc' => 'The BrainTree account Merchant Currencies ID to use',
                'value' => ''),
            'MODULE_PAYMENT_BRAINTREE_HOSTED_CLIENT_AUTHORIZATION' => array('title' => 'Tokenization API Key',
                'desc' => 'The BrainTree account tokenization API key to use.',
                'value' => ''),
            'MODULE_PAYMENT_BRAINTREE_HOSTED_PUBLIC_KEY' => array('title' => 'Public API Key',
                'desc' => 'The BrainTree account public API key to use.',
                'value' => ''),
            'MODULE_PAYMENT_BRAINTREE_HOSTED_SECRET_KEY' => array('title' => 'Secret API Key',
                'desc' => 'The BrainTree account secret API key to use with the publishable key.',
                'value' => ''),
            'MODULE_PAYMENT_BRAINTREE_HOSTED_3DSECURE' => array('title' => '3D Secure',
                'desc' => 'Use 3D secure?',
                'value' => 'False',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '),
            'MODULE_PAYMENT_BRAINTREE_PAYPAL_INTEGRATE' => array('title' => 'Paypal via Braintree?',
                'desc' => 'Integrate Paypal and pay via Braintree?',
                'value' => 'False',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '),
            /* 'MODULE_PAYMENT_BRAINTREE_HOSTED_VERIFY_WITH_CVC' => array('title' => 'Verify With CVC',
              'desc' => 'Verify the credit card billing address with the Card Verification Code (CVC)?',
              'value' => 'True',
              'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '),
              'MODULE_PAYMENT_BRAINTREE_HOSTED_TRANSACTION_METHOD' => array('title' => 'Transaction Method',
              'desc' => 'The processing method to use for each transaction.',
              'value' => 'Authorize',
              'set_function' => 'tep_cfg_select_option(array(\'Authorize\', \'Capture\'), '), */
            'MODULE_PAYMENT_BRAINTREE_HOSTED_ORDER_STATUS_ID' => array('title' => 'Set Order Status',
                'desc' => 'Set the status of orders made with this payment module to this value',
                'value' => $status_id_p,
                'use_function' => '\\common\\helpers\\Order::get_order_status_name',
                'set_function' => 'tep_cfg_pull_down_order_statuses('),
            'MODULE_PAYMENT_BRAINTREE_HOSTED_TRANSACTION_ORDER_STATUS_ID' => array('title' => 'Transaction Order Status',
                'desc' => 'Include transaction information in this order status level',
                'value' => $status_id,
                'set_function' => 'tep_cfg_pull_down_order_statuses(',
                'use_function' => '\\common\\helpers\\Order::get_order_status_name'),
            'MODULE_PAYMENT_BRAINTREE_HOSTED_ZONE' => array('title' => 'Payment Zone',
                'desc' => 'If a zone is selected, only enable this payment method for that zone.',
                'value' => '0',
                'use_function' => '\\common\\helpers\\Zones::get_zone_class_title',
                'set_function' => 'tep_cfg_pull_down_zone_classes('),
            /* 'MODULE_PAYMENT_BRAINTREE_HOSTED_VERIFY_SSL' => array('title' => 'Verify SSL Certificate',
              'desc' => 'Verify gateway server SSL certificate on connection?',
              'value' => 'True',
              'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '), */
            'MODULE_PAYMENT_BRAINTREE_HOSTED_DEBUG_EMAIL' => array('title' => 'Debug E-Mail Address',
                'desc' => 'All parameters of an invalid transaction will be sent to this email address.'),
            'MODULE_PAYMENT_BRAINTREE_HOSTED_SORT_ORDER' => array('title' => 'Sort order of display.',
                'desc' => 'Sort order of display. Lowest is displayed first.',
                'value' => '0'));

        return $params;
    }

    function sendDebugEmail($response = array()) {

        if (tep_not_null(MODULE_PAYMENT_BRAINTREE_HOSTED_DEBUG_EMAIL)) {
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
                \common\helpers\Mail::send('', MODULE_PAYMENT_BRAINTREE_HOSTED_DEBUG_EMAIL, 'BrainTree Debug E-Mail', trim($email_body), STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
            }
        }
    }

    function isOnline() {
        return true;
    }

    //interface
    public function getTransactionDetails($transaction_id, \common\services\PaymentTransactionManager $tManager = null){
        try{
            $this->setupCredentials($this->_getServerType());
            $response = \Braintree_Transaction::find($transaction_id);
            if ($tManager && $response) {
                $tManager->updateTransactionFromPayment($transaction_id, $response->status, $response->amount, $response->createdAt->format('Y-m-d H:i:s'));
            }
            return $response;
        } catch (\Exception $ex) {
            $this->sendDebugEmail($ex);
        }
    }

    public function canRefund($transaction_id){
        try{
            $tManager = $this->manager->getTransactionManager($this);
            $response = $this->getTransactionDetails($transaction_id, $tManager);
            if ($response){
                $_refundedAmount = 0;
                if (is_array($response->refundIds) && count($response->refundIds)){
                    foreach ($response->refundIds as $refId){
                        $responseRef = $this->getTransactionDetails($refId);
                        if ($responseRef){
                            $_refundedAmount += $this->formatCurrencyRaw($responseRef->amount, $responseRef->currencyIsoCode);
                            $tManager->updateTransactionChild($transaction_id, $refId, $responseRef->status, $responseRef->amount);
                        }
                    }
                }
                if (in_array($response->status, ['settling', 'settled']) && $response->amount > $_refundedAmount && empty($response->refundedTransactionId)){
                    return true;
                }
            }
        } catch (\Exception $ex) {
            $this->sendDebugEmail($ex);
        }
        return false;
    }

    public function refund($transaction_id, $amount = 0){
        $this->setupCredentials($this->_getServerType());
        try {
            if ($amount){ //partial refund
                $response = \Braintree_Transaction::refund($transaction_id, $amount);
            } else {
                $response = \Braintree_Transaction::refund($transaction_id);
            }
            if ($response->success){//after refund child transactions should be added!
                $this->manager->getTransactionManager($this)
                        ->addTransactionChild($transaction_id, $response->transaction->id, $response->transaction->status, $response->transaction->amount, ($amount? 'Partial Refund':'Full Refund'));
                $currencies = \Yii::$container->get('currencies');
                $order = $this->manager->getOrderInstance();
                $order->info['comments'] = "Refund State: " . $response->transaction->status . "\n" .
                        "Refund Date: " . $response->transaction->createdAt->format('d-m-Y H:i:s') . "\n" .
                        "Refund Amount: " . $currencies->format($response->transaction->amount, true, $order->info['currency'], $order->info['currency_value']);
                $this->_savePaymentTransactionRefund($response, $transaction_id);
                return true;
            }
        } catch (\Exception $ex) {
            $this->sendDebugEmail($ex);
        }
        return false;
    }
    
    private function _savePaymentTransactionRefund($response, $transaction_id){
        $orderPaymentParentRecord = $this->searchRecord($transaction_id);
        if ($orderPaymentParentRecord) {
            $orderPaymentRecord = $this->searchRecord($response->transaction->id);
            if ($orderPaymentRecord) {
                $order = $this->manager->getOrderInstance();
                $orderPaymentRecord->orders_payment_id_parent = (int)$orderPaymentParentRecord->orders_payment_id;
                $orderPaymentRecord->orders_payment_order_id = (int)$order->order_id;
                $orderPaymentRecord->orders_payment_is_credit = 1;
                $orderPaymentRecord->orders_payment_status = \common\helpers\OrderPayment::OPYS_REFUNDED;
                $orderPaymentRecord->orders_payment_amount = (float)$response->transaction->amount;
                $orderPaymentRecord->orders_payment_currency = trim($order->info['currency']);
                $orderPaymentRecord->orders_payment_currency_rate = (float)$order->info['currency_value'];
                $orderPaymentRecord->orders_payment_snapshot = json_encode(\common\helpers\OrderPayment::getOrderPaymentSnapshot($order));
                $orderPaymentRecord->orders_payment_transaction_status = trim($response->transaction->status);
                $orderPaymentRecord->orders_payment_transaction_date = date('Y-m-d H:i:s');
                $orderPaymentRecord->orders_payment_date_create = date('Y-m-d H:i:s');
                $orderPaymentRecord->save();
            }
        }
    }

    public function canVoid($transaction_id){
        $response = $this->getTransactionDetails($transaction_id, $this->manager->getTransactionManager($this));
        if (in_array($response->status, ['authorized', 'submitted_for_settlement', 'settlement_pending']) && empty($response->refundedTransactionId) ){
            return true;
        }
        return false;
    }

    public function void($transaction_id){
        $this->setupCredentials($this->_getServerType());
        try{
            $response = \Braintree_Transaction::void($transaction_id);
            if ($response->success){
                $this->manager->getTransactionManager($this)
                        ->addTransactionChild($transaction_id, $transaction_id, $response->transaction->status, $response->transaction->amount, 'Fully Voided payment');
                $order->info['comments'] = "Void State: " . $response->transaction->status . "\n" .
                        "Void Date: " . \common\helpers\Date::datetime_short($response->transaction->createdAt->format('d-m-Y H:i:s')) . "\n";
                $this->_savePaymentTransactionRefund($response, $transaction_id);
                return true;
            }
        } catch (\Exception $ex) {
            $this->sendDebugEmail($ex);
        }
        return false;
    }

    public function getFields(){
        return [
            [['start_date'], 'datetime', 'format' => 'yyyy-MM-dd HH:mm:ss'],
            [['end_date'], 'datetime', 'format' => 'yyyy-MM-dd HH:mm:ss'],//MM/dd/yyyy HH:mm            
            [['transaction_id', 'amount', 'customer_email'], 'string'],
        ];
    }

    public function search($queryParams){
        try{
            $fields = $this->getFields();

            foreach($queryParams as $key => $param){
                if(empty($param)) {
                    unset($queryParams[$key]);
                    continue;
                }
                array_map(function($item) use (&$queryParams, $key, $param) {
                    if (is_array($item[0])){
                        if (in_array($key, $item[0]) && $item[1] == 'datetime'){
                            $queryParams[$key] = date(DATE_ATOM, strtotime($param));
                        }
                    } else {
                        if ($key == $item[0] && $item[1] == 'datetime'){
                            $queryParams[$key] = date(DATE_ATOM, strtotime($param));
                        } 
                    }
                }, $fields);
            }
            
            $searchArray = [];
            
            static $settled;
            foreach($queryParams as $key => $param){
                switch($key){
                    case 'transaction_id':
                        $searchArray[] = \Braintree_TransactionSearch::id()->is($param);
                        break;
                    case 'amount':
                        $searchArray[] = \Braintree_TransactionSearch::amount()->is($param);
                        break;
                    case 'start_date':
                    case 'end_date':
                        if (!$settled) $settled = \Braintree_TransactionSearch::settledAt();
                        if ($key == 'start_date'){
                            $settled->greaterThanOrEqualTo($param);
                        }
                        if ($key == 'end_date'){
                            $settled->lessThanOrEqualTo($param);
                        }
                        break;
                    case 'customer_email':
                        $searchArray[] = \Braintree_TransactionSearch::customerEmail()->is($param);
                        break;
                }
            }
            if ($settled){
                $searchArray[] = $settled;
            }
            
            $list = [];
            if ($searchArray){
                
                /* search only sale transactions */
                $searchArray[] = \Braintree_TransactionSearch::type()->is(\Braintree_Transaction::SALE);
                $searchArray[] = \Braintree_TransactionSearch::refund()->is(false);
                /* search only sale transactions */
                
                $this->setupCredentials($this->_getServerType());
                $gateway = \Braintree_Configuration::gateway();
                $response =  $gateway->transaction()->search($searchArray);
                if ($response){
                    $limit = 50;
                    $currencies = \Yii::$container->get('currencies');
                    $tm = $this->manager->getTransactionManager();
                    foreach($response->getIds() as $iter => $id){
                        if ($tm->isLinkedTransaction($id)) continue;
                        if ($iter >= $limit) break;
                        if ($transaction = $this->getTransactionDetails($id)){

                            $name = ($transaction->billing['firstName'] ? $transaction->billing['firstName'] : $transaction->shipping['firstName']) . " ";
                            $name .= ($transaction->billing['lastName'] ? $transaction->billing['lastName'] : $transaction->shipping['lastName']);
                            
                            $list[] = [
                                'id' => $transaction->id,
                                'date' => \common\helpers\Date::formatDateTimeJS($transaction->createdAt->format(DATE_ATOM)),
                                'amount' => $currencies->format( $transaction->amount, true, $transaction->currencyIsoCode),
                                'negative' => $transaction->amount < 0,
                                'name' =>  $name . ($transaction->customer['email'] ? ", " . $transaction->customer['email']: ""),
                                'status' => $transaction->status,
                            ];
                        }
                    }
                }
            }
            
            return $list;
        } catch (\Exception $ex) {
            $this->sendDebugEmail($ex);
        }
    }

}