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

require_once( 'lib/pxpay/PxPay_Curl.php' );
require_once( 'lib/pxpay/PxPayRequest.php' );
require_once( 'lib/pxpay/MifMessage.php' );
require_once( 'lib/pxpay/PxPayResponse.php' );

use common\classes\modules\ModuleStatus;
use common\classes\modules\ModuleSortOrder;

/**
 * Class pxpay
 */
class pxpay extends \common\classes\modules\ModulePayment {

	const TRANSACT_MODE_URL = "https://sec.paymentexpress.com/pxaccess/pxpay.aspx";
	const TEST_MODE_URL = "https://uat.paymentexpress.com/pxaccess/pxpay.aspx";
	const AS_TEMP_ORDER = false; //2do transform to real order on callback


	private $_pxPayCURL;
	private $_pxPayUrl = self::TRANSACT_MODE_URL;
	private $_pxPayUserId;
	private $_pxPayKey;
	private $_successUrl;
	private $_failUrl;

	public $countries = [];
	public $paid_status;
	public $processing_status;
	public $fail_paid_status;

  protected $defaultTranslationArray = [
      'MODULE_PAYMENT_PXPAY_TEXT_TITLE' => 'pxPay',
      'MODULE_PAYMENT_PXPAY_TEXT_DESCRIPTION' => 'pxPay',
      'MODULE_PAYMENT_PXPAY_TEXT_NOTES' => ''
  ];

  public function __construct() {
    parent::__construct();

		$this->countries   = [];
		$this->code        = 'pxpay';
		$this->title       = defined( 'MODULE_PAYMENT_PXPAY_TEXT_TITLE' ) ? MODULE_PAYMENT_PXPAY_TEXT_TITLE : 'pxPay';
		$this->description = defined( 'MODULE_PAYMENT_PXPAY_TEXT_DESCRIPTION' ) ? MODULE_PAYMENT_PXPAY_TEXT_DESCRIPTION : 'pxPay';
		$this->enabled = true;

		$this->setUrl();

		if( ! defined( 'MODULE_PAYMENT_PXPAY_STATUS' ) ) {
			$this->enabled = false;
			return;
		}
		$this->_pxPayKey = defined('MODULE_PAYMENT_PXPAY_KEY') ? MODULE_PAYMENT_PXPAY_KEY : '';
		$this->_pxPayUserId = defined('MODULE_PAYMENT_PXPAY_USER_ID') ? MODULE_PAYMENT_PXPAY_USER_ID : '';
		$this->paid_status = MODULE_PAYMENT_PXPAY_ORDER_PAID_STATUS_ID;
		$this->processing_status = MODULE_PAYMENT_PXPAY_ORDER_PROCESS_STATUS_ID;
		$this->fail_paid_status = MODULE_PAYMENT_PXPAY_FAIL_PAID_STATUS_ID;

		$this->_pxPayCURL = new \PxPay_Curl( $this->_pxPayUrl, $this->_pxPayUserId, $this->_pxPayKey );

		$this->update();
	}

	private function update(){
		if(!$this->_pxPayUserId || !$this->_pxPayKey){
			$this->enabled = false;
		}
	}

	private function setUrl() {
		if( defined( 'MODULE_PAYMENT_PXPAY_TEST_MODE' ) && MODULE_PAYMENT_PXPAY_TEST_MODE == 'True' ) {
			$this->_pxPayUrl = self::TEST_MODE_URL;
		}
		else $this->_pxPayUrl = self::TRANSACT_MODE_URL;
	}

	function before_process() {
		$order = $this->manager->getOrderInstance();
		$order->info['order_status'] = $this->processing_status;

    global $pxpay_order_id;

    if (!empty($order->order_id)) {
      $pxpay_order_id = $order->order_id;
    }
    if (isset($_GET['returned_order'])){
        $pxpay_order_id = $_GET['returned_order'];
    }
    if (empty($pxpay_order_id)) {
      $order->order_id = $pxpay_order_id = $this->saveOrder(self::AS_TEMP_ORDER);
    }

    if (!tep_session_is_registered('pxpay_order_id')) {
      tep_session_register('pxpay_order_id');
    }


		return $this->redirectForm();
	}

	function after_process() {

    if (tep_session_is_registered('pxpay_order_id')) {
      tep_session_unregister('pxpay_order_id');
    }
		$this->manager->clearAfterProcess();

	}


	private function redirectForm() {
		$order = $this->manager->getOrderInstance();
    $currencies = \Yii::$container->get('currencies');
		$pxpay = $this->_pxPayCURL;

		$this->_successUrl = \Yii::$app->urlManager->createAbsoluteUrl( [ 'callback/checkout-pxpay','order_id' => $order->order_id, 'payment' => $this->code, 'success' => 1 ] );
		$this->_failUrl    = \Yii::$app->urlManager->createAbsoluteUrl( [ 'callback/checkout-pxpay', 'order_id' => $order->order_id, 'payment' => $this->code, 'success' => 0 ] );

		$request = new \PxPayRequest();

		$MerchantReference = $order->order_id;//uniqid( "ID" ) . "pay";
		$Address1          = $order->delivery['country']['title'];
		$Address2          = $order->delivery['city'];
		$Address3          = $order->delivery['street_address'];


		$TxnId = uniqid( "ID" );

		$recalculate = ( USE_MARKET_PRICES == 'True' ? false : true );
		$total       = $currencies->format_clear( $currencies->calculate_price_in_order( $order->info, $order->info['total_inc_tax'] ), $recalculate, $order->info['currency'] );

		$request->setMerchantReference( $MerchantReference );
		$request->setAmountInput( $total );

		$request->setTxnData1( $Address1 );
		$request->setTxnData2( $Address2 );
		$request->setTxnData3( $Address3 );

		$request->setTxnType( "Purchase" );
		$request->setCurrencyInput( $order->info['currency'] );
		$request->setEmailAddress( $order->customer['customers_email_address'] );
		$request->setUrlFail( $this->_failUrl );
		$request->setUrlSuccess( $this->_successUrl );
		$request->setTxnId( $TxnId );
    \Yii::warning(print_r($order,1), 'PXPAYPARAMSORDER');
    \Yii::warning(print_r($request,1), 'PXPAYPARAMS');

    $request_string = $pxpay->makeRequest( $request );
    \Yii::warning($request_string, 'PXPAYPARAMS');
if (tep_session_is_registered('pxpay_order_id')) {
      tep_session_unregister('pxpay_order_id');
    }
		$response = new \MifMessage( $request_string );

		$url   = $response->get_element_text( "URI" );
		$valid = $response->get_attribute( "valid" );
    if (!$valid || empty($url)) {
      header( "Location: " . \Yii::$app->urlManager->createAbsoluteUrl( [ 'checkout', 'returned_order' =>$order->order_id, /*'payment_error' => $this->code,*/ 'error_message' => (defined('TEXT_GENERAL_PAYMENT_ERROR')?TEXT_GENERAL_PAYMENT_ERROR:'Please select different payment method')] ));
    } else {
      header( "Location: " . $url );
    }

		die;
	}

  function get_error() {
    return (defined('TEXT_GENERAL_PAYMENT_ERROR')?TEXT_GENERAL_PAYMENT_ERROR:'Please select different payment method');
  }

	public function describe_status_key() {
		return new ModuleStatus( 'MODULE_PAYMENT_PXPAY_STATUS', 'True', 'False' );
	}


	public function describe_sort_key() {
		return new ModuleSortOrder( 'MODULE_PAYMENT_PXPAY_SORT_ORDER' );
	}

	public function configure_keys() {
        $status_id = defined('MODULE_PAYMENT_PXPAY_ORDER_PROCESS_STATUS_ID') ? MODULE_PAYMENT_PXPAY_ORDER_PROCESS_STATUS_ID : $this->getDefaultOrderStatusId();
        $status_id_paid = defined('MODULE_PAYMENT_PXPAY_ORDER_PAID_STATUS_ID') ? MODULE_PAYMENT_PXPAY_ORDER_PAID_STATUS_ID : $this->getDefaultOrderStatusId();
        $status_id_fail = defined('MODULE_PAYMENT_PXPAY_FAIL_PAID_STATUS_ID') ? MODULE_PAYMENT_PXPAY_FAIL_PAID_STATUS_ID : $this->getDefaultOrderStatusId();

		return array(
			'MODULE_PAYMENT_PXPAY_STATUS' => array(
				'title'        => 'pxPAY Enable Module',
				'value'        => 'True',
				'description'  => 'Do you want to accept pxPAY payments?',
				'sort_order'   => '1',
				'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
			),

			'MODULE_PAYMENT_PXPAY_KEY'     => array(
				'title'       => 'pxPay Key',
				'value'       => '',
				'description' => '',
				'sort_order'  => '2',
				//'use_function' => '\\common\\helpers\\Zones::get_zone_class_title',
				//'set_function' => 'tep_cfg_pull_down_zone_classes(',
			),
			'MODULE_PAYMENT_PXPAY_USER_ID' => array(
				'title'       => 'pxPay UserID',
				'value'       => '',
				'description' => '',
				'sort_order'  => '3',
				//'use_function' => '\\common\\helpers\\Zones::get_zone_class_title',
				//'set_function' => 'tep_cfg_pull_down_zone_classes(',
			),

			'MODULE_PAYMENT_PXPAY_SORT_ORDER'              => array(
				'title'       => 'Sort order of display.',
				'value'       => '0',
				'description' => 'Sort order of display. Lowest is displayed first.',
				'sort_order'  => '5',
			),
			'MODULE_PAYMENT_PXPAY_TEST_MODE'               => array(
				'title'        => 'pxPay Test mode',
				'value'        => 'True',
				'description'  => 'Test mode',
				'sort_order'   => '6',
				'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
			),
			'MODULE_PAYMENT_PXPAY_ORDER_PROCESS_STATUS_ID' => array(
				'title'        => 'pxPay Set Order Processing Status',
				'value'        => $status_id,
				'description'  => 'Set the process status of orders made with this payment module to this value',
				'sort_order'   => '14',
				'set_function' => 'tep_cfg_pull_down_order_statuses(',
				'use_function' => '\\common\\helpers\\Order::get_order_status_name',
			),
			'MODULE_PAYMENT_PXPAY_ORDER_PAID_STATUS_ID'    => array(
				'title'        => 'pxPay Set Order Paid Status',
				'value'        => $status_id_paid,
				'description'  => 'Set the paid status of orders made with this payment module to this value',
				'sort_order'   => '15',
				'set_function' => 'tep_cfg_pull_down_order_statuses(',
				'use_function' => '\\common\\helpers\\Order::get_order_status_name',
			),
			'MODULE_PAYMENT_PXPAY_FAIL_PAID_STATUS_ID'    => array(
				'title'        => 'pxPay Set Order Fail Paid Status',
				'value'        => $status_id_fail,
				'description'  => 'Set the fail paid status of orders made with this payment module to this value',
				'sort_order'   => '15',
				'set_function' => 'tep_cfg_pull_down_order_statuses(',
				'use_function' => '\\common\\helpers\\Order::get_order_status_name',
			),
		);
	}


	public function install( $platform_id ) {
		/*$languages = \common\helpers\Language::get_languages( true );
		$newId     = \common\models\OrdersStatus::newOrdersStatusId();
		foreach( $languages as $language ) {
			$model = \common\models\OrdersStatus::create( $newId, 1, (int) $language['id'], 'PxPay processing', '', 1 );
			$model->save();
		}
		$newId     = \common\models\OrdersStatus::newOrdersStatusId();
		foreach( $languages as $language ) {
			$model = \common\models\OrdersStatus::create( $newId, 1, (int) $language['id'], 'PxPay fail pay', '', 1 );
			$model->save();
		}
        /**/
		return parent::install( $platform_id );
	}

	function isOnline() {
		return true;
	}

	function selection() {
		$selection = array(
			'id'     => $this->code,
			'module' => $this->title . $this->getJS()
		);
    if (defined('MODULE_PAYMENT_PXPAY_TEXT_NOTES') && !empty(MODULE_PAYMENT_PXPAY_TEXT_NOTES)) {
      $selection['notes'][] = MODULE_PAYMENT_PXPAY_TEXT_NOTES;
    }
    return $selection;
	}

	public function getResponse($result){
        return $this->_pxPayCURL->getResponse($result);
	}
}