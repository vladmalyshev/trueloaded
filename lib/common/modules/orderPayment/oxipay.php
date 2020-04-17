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

use common\classes\modules\ModuleStatus;
use common\classes\modules\ModuleSortOrder;

/**
 * Class oxipay
 */
class oxipay extends \common\classes\modules\ModulePayment {

	const TRANSACT_MODE_URL = "https://secure.oxipay.co.nz";
	const TEST_MODE_URL = "https://securesandbox.oxipay.co.nz"; //Checkout?platform=Default
	const AS_TEMP_ORDER = false; //2do transform to real order on callback

	private $_oxipayUserId;
	private $_oxipayKey;

	public $paid_status;
	public $processing_status;
	public $fail_paid_status;

        protected $defaultTranslationArray = [
            'MODULE_PAYMENT_OXIPAY_TEXT_TITLE' => 'Oxipay',
            'MODULE_PAYMENT_OXIPAY_TEXT_DESCRIPTION' => 'Oxipay'
        ];

	public function __construct() {
                parent::__construct();

		$this->code        = 'oxipay';
		$this->title       = defined( 'MODULE_PAYMENT_OXIPAY_TEXT_TITLE' ) ? MODULE_PAYMENT_OXIPAY_TEXT_TITLE : 'Oxipay';
		$this->description = defined( 'MODULE_PAYMENT_OXIPAY_TEXT_DESCRIPTION' ) ? MODULE_PAYMENT_OXIPAY_TEXT_DESCRIPTION : 'Oxipay';
		$this->enabled = true;

		if( ! defined( 'MODULE_PAYMENT_OXIPAY_STATUS' ) || MODULE_PAYMENT_OXIPAY_STATUS != 'True') {
			$this->enabled = false;
			return false;
		}

		$this->setUrl();

		$this->_oxipayKey = defined('MODULE_PAYMENT_OXIPAY_KEY') ? MODULE_PAYMENT_OXIPAY_KEY : '';
		$this->_oxipayUserId = defined('MODULE_PAYMENT_OXIPAY_USER_ID') ? MODULE_PAYMENT_OXIPAY_USER_ID : '';
		$this->paid_status       = MODULE_PAYMENT_OXIPAY_ORDER_PAID_STATUS_ID;

		$this->update();

	}

	private function update(){
		if(!$this->_oxipayUserId || !$this->_oxipayKey){
			$this->enabled = false;
		}
	}

	private function setUrl() {
		if ( defined( 'MODULE_PAYMENT_OXIPAY_TEST_MODE' ) && MODULE_PAYMENT_OXIPAY_TEST_MODE == 'True' ) {
			$this->form_action_url = self::TEST_MODE_URL;
		} else {
      $this->form_action_url = self::TRANSACT_MODE_URL;
    }
	}

   function before_process() {
     $this->manager->clearAfterProcess();
     tep_redirect(tep_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL'));
     return false;
    }

    function after_process() {
    }


	function process_button() {
    global $oxipay_order_id;

    $oxipay_order_id=false; // 2do - manager - update tmp order and delete this line.

    if (tep_session_is_registered('oxipay_order_id') && $oxipay_order_id) {
        $order = $this->manager->getParentToInstanceWithId('\common\classes\TmpOrder', $oxipay_order_id);
    } else {
        $order = $this->manager->getParentToInstance('\common\classes\TmpOrder');
        $order->info['order_status'] = MODULE_PAYMENT_AUTHORIZENET_ACCEPT_HOSTED_ORDER_STATUS_ID_BEFORE;

        $insert_id = $order->save_order();
        $order->save_details();
        $order->save_products(false);
        $oxipay_order_id = $insert_id;
    }

    if (!tep_session_is_registered('oxipay_order_id')) {
        tep_session_register('oxipay_order_id');
    }
    $currencies = \Yii::$container->get('currencies');

		$recalculate = ( USE_MARKET_PRICES == 'True' ? false : true );
		$total       = $currencies->format_clear( $currencies->calculate_price_in_order( $order->info, $order->info['total_inc_tax'] ), $recalculate, $order->info['currency'] );

    $data = [
      'x_account_id' => $this->_oxipayUserId,
      'x_reference'  => $order->order_id,
      'x_shop_country'  => strtoupper(\common\helpers\Country::get_country_info_by_id(STORE_COUNTRY)['countries_iso_code_2']),
      'x_shop_name'  => STORE_NAME,
      // generate 'x_signature'  => ,
      'x_url_callback'  => \Yii::$app->urlManager->createAbsoluteUrl( [ 'callback/webhooks', 'set' => 'payment', 'module' => $this->code] ),
      'x_url_cancel'  => \Yii::$app->urlManager->createAbsoluteUrl( [ 'checkout' ] ),
      'x_url_complete'  => \Yii::$app->urlManager->createAbsoluteUrl(['checkout/process']),
      'x_amount'  => $total,
      'x_currency'  => $order->info['currency'],
      /// optional
      'x_customer_billing_address1' => $order->billing['street_address'],
      'x_customer_billing_address2'  => $order->billing['suburb'],
      'x_customer_billing_city'  => $order->billing['city'],
      'x_customer_billing_country'  => $order->billing['country']['iso_code_2'],
      'x_customer_billing_state'  => $order->billing['state'],
      'x_customer_billing_postcode'  => $order->billing['postcode'],
      'x_customer_email'  => $order->customer['email_address'],
      'x_customer_first_name'  => $order->billing['firstname'],
      'x_customer_last_name'  => $order->billing['lastname'],
      'x_customer_phone'  => $order->billing['telephone'],
      'x_customer_shipping_address1'  => $order->shipping['street_address'],
      'x_customer_shipping_address2'  => $order->shipping['suburb'],
      'x_customer_shipping_city'  => $order->shipping['city'],
      'x_customer_shipping_country'  => $order->shipping['country']['iso_code_2'],
      'x_customer_shipping_first_name'  => $order->shipping['firstname'],
      'x_customer_shipping_last_name'  => $order->shipping['lastname'],
      'x_customer_shipping_phone'  => $order->shipping['telephone'],
      'x_customer_shipping_state'  => $order->shipping['state'],
      'x_customer_shipping_postcode'  => $order->shipping['postcode'],
      'x_description'  => STORE_NAME . ' tmp #' . $order->order_id,
    ];

    $data['x_signature'] = str_replace('-', '', $this->generateSignature($data, $this->_oxipayKey));

    $process_button_string = '';

    foreach ($data as $k => $v) {

      if (!empty($v)) {
        $process_button_string .= tep_draw_hidden_field($k, $v);
      }
    }

    return $process_button_string;

	}

  function get_error() {
    return (defined('TEXT_GENERAL_PAYMENT_ERROR')?TEXT_GENERAL_PAYMENT_ERROR:'Please select different payment method');
  }

	public function describe_status_key() {
		return new ModuleStatus( 'MODULE_PAYMENT_OXIPAY_STATUS', 'True', 'False' );
	}


	public function describe_sort_key() {
		return new ModuleSortOrder( 'MODULE_PAYMENT_OXIPAY_SORT_ORDER' );
	}

	public function configure_keys() {
        $status_id = defined('MODULE_PAYMENT_OXIPAY_ORDER_PAID_STATUS_ID') ? MODULE_PAYMENT_OXIPAY_ORDER_PAID_STATUS_ID : $this->getDefaultOrderStatusId();
        return array(
			'MODULE_PAYMENT_OXIPAY_STATUS' => array(
				'title'        => 'Oxipay Enable Module',
				'value'        => 'True',
				'description'  => 'Do you want to accept Oxipay payments?',
				'sort_order'   => '1',
				'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
			),

			'MODULE_PAYMENT_OXIPAY_KEY'     => array(
				'title'       => 'Oxipay Key',
				'value'       => '',
				'description' => '',
				'sort_order'  => '2',
				//'use_function' => '\\common\\helpers\\Zones::get_zone_class_title',
				//'set_function' => 'tep_cfg_pull_down_zone_classes(',
			),
			'MODULE_PAYMENT_OXIPAY_USER_ID' => array(
				'title'       => 'Oxipay Merchant ID that is assigned by Oxipay to individual merchants',
				'value'       => '',
				'description' => '',
				'sort_order'  => '3',
				//'use_function' => '\\common\\helpers\\Zones::get_zone_class_title',
				//'set_function' => 'tep_cfg_pull_down_zone_classes(',
			),

			'MODULE_PAYMENT_OXIPAY_SORT_ORDER'              => array(
				'title'       => 'Sort order of display.',
				'value'       => '0',
				'description' => 'Sort order of display. Lowest is displayed first.',
				'sort_order'  => '5',
			),
			'MODULE_PAYMENT_OXIPAY_TEST_MODE'               => array(
				'title'        => 'Oxipay Test mode',
				'value'        => 'True',
				'description'  => 'Test mode',
				'sort_order'   => '6',
				'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
			),
			'MODULE_PAYMENT_OXIPAY_ORDER_PAID_STATUS_ID'    => array(
				'title'        => 'Oxipay Set Order Paid Status',
				'value'        => $status_id,
				'description'  => 'Set the paid status of orders made with this payment module to this value',
				'sort_order'   => '15',
				'set_function' => 'tep_cfg_pull_down_order_statuses(',
				'use_function' => '\\common\\helpers\\Order::get_order_status_name',
			),

		);
	}


	public function install( $platform_id ) {

		return parent::install( $platform_id );
	}

	function isOnline() {
		return true;
	}

	function selection() {
		return array(
			'id'     => $this->code,
			'module' => $this->title
		);
	}

  public function call_webhooks() {

    /*if (tep_session_is_registered('oxipay_order_id')) {
      tep_session_unregister('oxipay_order_id');
      global $oxipay_order_id;
      $oxipay_order_id = false;
    }*/
    $post = \Yii::$app->request->post();
    //$post = \Yii::$app->request->get();
    $debug = false;

    $data = [];
    foreach (['x_account_id',
              'x_reference',
              'x_currency',
              'x_test',
              'x_amount',
              'x_gateway_reference',
              'x_timestamp',
              'x_result'] as $k) {
        $data[$k] = $post[$k];
      }

      if ($debug || $post['x_signature'] == str_replace('-', '', $this->generateSignature($data, $this->_oxipayKey))) {
        if ($data['x_result'] == 'completed') {

          $tmpOrderID = $data['x_reference'];

          if ($tmpOrderID) {
            $tmpOrder = $this->manager->getParentToInstanceWithId('\common\classes\TmpOrder', $tmpOrderID);
            $tmpOrder->info['order_status'] = $this->paid_status;
            $tmpOrder->save_order($tmpOrderID);

            $orderId = $tmpOrder->createOrder();
            $this->manager->getTotalCollection()->apply_credit();

            $this->transactionInfo['order_id'] = $orderId;

            $this->transactionInfo['transaction_id'] = $data['x_gateway_reference'];

            $this->transactionInfo['transaction_details'] = implode(' ', $data);
            $this->transactionInfo['silent'] = true;
            parent::processPaymentNotification(false);


            return true;
          }
        }
      }
  }

}