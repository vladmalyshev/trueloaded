<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 22.02.18
 * Time: 10:37
 */
namespace common\modules\orderShipping;

use common\classes\modules\ModuleStatus;
use common\classes\modules\ModuleSortOrder;

final class ukrpost extends \common\classes\modules\ModuleShipping {


	private $_bearerEcom;
	private $_bearerPriceCalculation;
	private $_bearerStatusTracking;
	private $_counterPartyToken;

	private $_sandboxBearerEcom;
	private $_sandboxBearerPriceCalculation;
	private $_sandboxBearerStatusTracking;
	private $_sandboxCounterPartyToken;

	private $_storeLogin;
	private $_storePassword;
	private $_counterPartyUUID;

	private $_cost;

	public $enabled;
	public $title;
	public $description;

	public $quotes;

	/**
	 * @var null | \common\extensions\UkrPost\services\Service
	 */
	private $service = null;

        protected $defaultTranslationArray = [
            'MODULE_SHIPPING_UKRPOST_TEXT_TITLE' => 'Ukr Poshta Express',
            'MODULE_SHIPPING_UKRPOST_TEXT_DESCRIPTION' => 'Ukr Poshta Express',
            'MODULE_SHIPPING_UKRPOST_TEXT_WAY' => 'Ukr Poshta Express'
        ];

	public function __construct() {
                parent::__construct();

		$this->countries = [ 'UKR' ];

		$this->code        = 'ukrpost';
		$this->title       = MODULE_SHIPPING_UKRPOST_TEXT_TITLE;
		$this->description = MODULE_SHIPPING_UKRPOST_TEXT_DESCRIPTION;

		if ( ! defined( 'MODULE_SHIPPING_UKRPOST_STATUS' ) ) {
			$this->enabled = false;
			return false;
		}

		$this->init();
	}

	private function init(){
		$this->_bearerEcom = defined('MODULE_SHIPPING_UKRPOST_BEARER_ECOM') ? MODULE_SHIPPING_UKRPOST_BEARER_ECOM : '';
		$this->_bearerPriceCalculation = defined('MODULE_SHIPPING_UKRPOST_BEARER_PRICE_CALCULATION') ? MODULE_SHIPPING_UKRPOST_BEARER_PRICE_CALCULATION  : '';
		$this->_bearerStatusTracking = defined('MODULE_SHIPPING_UKRPOST_BEARER_STATUS_TRACKING') ? MODULE_SHIPPING_UKRPOST_BEARER_STATUS_TRACKING : '';
		$this->_counterPartyToken = defined('MODULE_SHIPPING_UKRPOST_COUNTERPARTY_TOKEN') ? MODULE_SHIPPING_UKRPOST_COUNTERPARTY_TOKEN : '';

		$this->_sandboxBearerEcom = defined('MODULE_SHIPPING_UKRPOST_SAND_BEARER_ECOM') ? MODULE_SHIPPING_UKRPOST_SAND_BEARER_ECOM : '';
		$this->_sandboxBearerPriceCalculation = defined('MODULE_SHIPPING_UKRPOST_SAND_BEARER_PRICE_CALCULATION') ? MODULE_SHIPPING_UKRPOST_SAND_BEARER_PRICE_CALCULATION : '';
		$this->_sandboxBearerStatusTracking = defined('MODULE_SHIPPING_UKRPOST_BEARER_STATUS_TRACKING') ? MODULE_SHIPPING_UKRPOST_BEARER_STATUS_TRACKING : '';
		$this->_sandboxCounterPartyToken = defined('MODULE_SHIPPING_UKRPOST_SAND_COUNTERPARTY_TOKEN') ? MODULE_SHIPPING_UKRPOST_SAND_COUNTERPARTY_TOKEN : '';

		$this->_storeLogin = defined('MODULE_SHIPPING_UKRPOST_STORE_LOGIN') ? MODULE_SHIPPING_UKRPOST_STORE_LOGIN : '';
		$this->_storePassword = defined('MODULE_SHIPPING_UKRPOST_STORE_PASSWORD') ? MODULE_SHIPPING_UKRPOST_STORE_PASSWORD : '';
		$this->_counterPartyUUID = defined('MODULE_SHIPPING_UKRPOST_COUNTERPARTY_UUID') ? MODULE_SHIPPING_UKRPOST_COUNTERPARTY_UUID : '';

		$this->_cost = defined('MODULE_SHIPPING_UKRPOST_COST') ? MODULE_SHIPPING_UKRPOST_COST : '';



		$this->enabled = $this->enable();
	}

	public function getProdBearerEcom(){
		return $this->_bearerEcom;
	}

	public function getProdToken(){
		return $this->_counterPartyToken;
	}

	public function getSandBearerEcom(){
		return $this->_sandboxBearerEcom;
	}

	public function getSandToken(){
		return $this->_sandboxCounterPartyToken;
	}

	public function enable(){
		if(!$this->_bearerEcom || !$this->_counterPartyToken || !$this->_counterPartyUUID){
			return false;
		}

		return true;
	}

	public function quote( $method = '' ) {
		$this->quotes = array(
			'id'      => $this->code,
			'module'  => MODULE_SHIPPING_UKRPOST_TEXT_TITLE,
			'methods' => array(
				array(
					'id'    => $this->code,
					'title' => MODULE_SHIPPING_UKRPOST_TEXT_WAY,
					'cost'  => $this->cost()
				)
			)
		);
		return $this->quotes;
	}

	public function cost(){
		return $this->_cost;
	}

	public function configure_keys() {
		return array(
			'MODULE_SHIPPING_UKRPOST_STATUS'     =>
				array(
					'title'        => 'Enable Item Shipping',
					'value'        => 'True',
					'description'  => 'Do you want to offer UKRPOST shipping?',
					'sort_order'   => '0',
					'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
				),
			'MODULE_SHIPPING_UKRPOST_BEARER_ECOM'     =>
				array(
					'title'       => 'PRODUCTION BEARER eCom',
					'value'       => '',
					'description' => '',
					'sort_order'  => '1',
				),
			'MODULE_SHIPPING_UKRPOST_BEARER_PRICE_CALCULATION'     =>
				array(
					'title'       => 'PRODUCTION BEARER PriceCalculation',
					'value'       => '',
					'description' => '',
					'sort_order'  => '2',
				),
			'MODULE_SHIPPING_UKRPOST_BEARER_STATUS_TRACKING'     =>
				array(
					'title'       => 'PRODUCTION BEARER StatusTracking',
					'value'       => '',
					'description' => '',
					'sort_order'  => '3',
				),

			'MODULE_SHIPPING_UKRPOST_COUNTERPARTY_TOKEN'     =>
				array(
					'title'       => 'PROD_COUNTERPARTY TOKEN',
					'value'       => '',
					'description' => '',
					'sort_order'  => '4',
				),

			'MODULE_SHIPPING_UKRPOST_SAND_BEARER_ECOM'     =>
				array(
					'title'       => 'SAND BEARER eCom',
					'value'       => '',
					'description' => '',
					'sort_order'  => '5',
				),
			'MODULE_SHIPPING_UKRPOST_SAND_BEARER_PRICE_CALCULATION'     =>
				array(
					'title'       => 'SAND BEARER PriceCalculation',
					'value'       => '',
					'description' => '',
					'sort_order'  => '6',
				),
			'MODULE_SHIPPING_UKRPOST_SAND_BEARER_STATUS_TRACKING'     =>
				array(
					'title'       => 'SAND BEARER StatusTracking',
					'value'       => '',
					'description' => '',
					'sort_order'  => '7',

				),

			'MODULE_SHIPPING_UKRPOST_SAND_COUNTERPARTY_TOKEN'     =>
				array(
					'title'       => 'SAND_COUNTERPARTY TOKEN',
					'value'       => '',
					'description' => '',
					'sort_order'  => '8',
				),

			'MODULE_SHIPPING_UKRPOST_STORE_LOGIN'       =>
				array(
					'title'       => 'STORE LOGIN',
					'value'       => '40',
					'description' => '',
					'sort_order'  => '9',
				),

			'MODULE_SHIPPING_UKRPOST_STORE_PASSWORD'       =>
				array(
					'title'       => 'STORE PASSWORD',
					'value'       => '40',
					'description' => '',
					'sort_order'  => '10',
				),

			'MODULE_SHIPPING_UKRPOST_COUNTERPARTY_UUID'       =>
				array(
					'title'       => 'COUNTERPARTY UUID',
					'value'       => '40',
					'description' => '',
					'sort_order'  => '10',
				),

			'MODULE_SHIPPING_UKRPOST_COST'       =>
				array(
					'title'       => 'UKRPOST Shipping Shipping Cost',
					'value'       => '40',
					'description' => '',
					'sort_order'  => '0',
				),
			'MODULE_SHIPPING_UKRPOST_SORT_ORDER' =>
				array(
					'title'       => 'UKRPOST Shipping Sort Order',
					'value'       => '0',
					'description' => 'Sort order of display.',
					'sort_order'  => '0',
				),


		);
	}

	public function install( $platform_id ) {
		return parent::install( $platform_id );
	}

	public function describe_status_key() {
		return new ModuleStatus( 'MODULE_SHIPPING_UKRPOST_STATUS', 'True', 'False' );
	}

	public function describe_sort_key() {
		return new ModuleSortOrder( 'MODULE_SHIPPING_UKRPOST_SORT_ORDER' );
	}


	public function widget($order_id = null){
		return '';
	}

	public function setAdditionalOrderParams( $order_id, $params  = null) {
		$this->service = new \common\extensions\UkrPost\services\Service($this);
		try{
			$this->service->saveShippingOrderParams($this);
		} catch (Exception $e){
			throw $e;
		}
	}

	public function getAdditionalOrderParams( $params = [] , $order_id = null) {
		if ( \common\helpers\Acl::checkExtension( 'UkrPost', 'allowed' ) ) {
			if(!$order_id){
				return '';
			} else {
				return \common\extensions\UkrPost\UkrPost::widget( [ 'view'      => 'backend',
				                                                    'order_id'     => $order_id,

				]);
			}

		}
		return '';
	}


}