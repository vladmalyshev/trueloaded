<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */
namespace common\modules\orderShipping;

use common\classes\modules\ModuleShipping;
use common\classes\modules\ModuleStatus;
use common\classes\modules\ModuleSortOrder;

  class ups extends ModuleShipping{
    var $code, $title, $descrption, $icon, $enabled, $types;

    protected $defaultTranslationArray = [
        'MODULE_SHIPPING_UPS_TEXT_TITLE' => 'United Parcel Service',
        'MODULE_SHIPPING_UPS_TEXT_DESCRIPTION' => 'United Parcel Service',
        'MODULE_SHIPPING_UPS_TEXT_OPT_GND' => 'UPS Ground',
        'MODULE_SHIPPING_UPS_TEXT_OPT_1DM' => 'Next Day Air Early AM',
        'MODULE_SHIPPING_UPS_TEXT_OPT_1DA' => 'Next Day Air',
        'MODULE_SHIPPING_UPS_TEXT_OPT_1DP' => 'Next Day Air Saver',
        'MODULE_SHIPPING_UPS_TEXT_OPT_2DM' => '2nd Day Air Early AM',
        'MODULE_SHIPPING_UPS_TEXT_OPT_3DS' => '3 Day Select',
        'MODULE_SHIPPING_UPS_TEXT_OPT_STD' => 'Canada Standard',
        'MODULE_SHIPPING_UPS_TEXT_OPT_XPR' => 'Worldwide Express',
        'MODULE_SHIPPING_UPS_TEXT_OPT_XDM' => 'Worldwide Express Plus',
        'MODULE_SHIPPING_UPS_TEXT_OPT_XPD' => 'Worldwide Expedited'
    ];

// class constructor
    function __construct() {
        parent::__construct();

        $this->code = 'ups';
        $this->title = MODULE_SHIPPING_UPS_TEXT_TITLE;
        $this->description = MODULE_SHIPPING_UPS_TEXT_DESCRIPTION;
        if (!defined('MODULE_SHIPPING_UPS_STATUS')) {
            $this->enabled = false;
            return false;
        }
        $this->sort_order = MODULE_SHIPPING_UPS_SORT_ORDER;
        $this->icon = DIR_WS_ICONS . 'shipping_ups.gif';
        $this->tax_class = MODULE_SHIPPING_UPS_TAX_CLASS;
        $this->enabled = ((MODULE_SHIPPING_UPS_STATUS == 'True') ? true : false);

      if ( ($this->enabled == true) && ((int)MODULE_SHIPPING_UPS_ZONE > 0) ) {
        $check_flag = false;
        $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_SHIPPING_UPS_ZONE . "' and zone_country_id = '" . $this->delivery['country']['id'] . "' order by zone_id");
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

      $this->types = array('1DM' => 'Next Day Air Early AM',
                           '1DML' => 'Next Day Air Early AM Letter',
                           '1DA' => 'Next Day Air',
                           '1DAL' => 'Next Day Air Letter',
                           '1DAPI' => 'Next Day Air Intra (Puerto Rico)',
                           '1DP' => 'Next Day Air Saver',
                           '1DPL' => 'Next Day Air Saver Letter',
                           '2DM' => '2nd Day Air AM',
                           '2DML' => '2nd Day Air AM Letter',
                           '2DA' => '2nd Day Air',
                           '2DAL' => '2nd Day Air Letter',
                           '3DS' => '3 Day Select',
                           'GND' => 'Ground',
                           'GNDCOM' => 'Ground Commercial',
                           'GNDRES' => 'Ground Residential',
                           'STD' => 'Canada Standard',
                           'XPR' => 'Worldwide Express',
                           'XPRL' => 'worldwide Express Letter',
                           'XDM' => 'Worldwide Express Plus',
                           'XDML' => 'Worldwide Express Plus Letter',
                           'XPD' => 'Worldwide Expedited');
    }

// class methods
    function quote($method = '') {

      if ( (tep_not_null($method)) && (isset($this->types[$method])) ) {
        $prod = $method;
      } else if ($this->delivery['country']['iso_code_2'] == 'CA') {
	    $prod = 'STD';
      } else {
        $prod = 'GNDRES';
      }

      if ($method) $this->_upsAction('3'); // return a single quote

      $this->_upsProduct($prod);
      
      $config = Yii::$app->get('platform')->config(/*$order->info['platform_id']*/);
      $platformsAddressBook = $config->getPlatformAddress();
      $platform_country = \common\helpers\Country::get_country_info_by_id($platformsAddressBook['country_id']);
      $this->_upsOrigin($platformsAddressBook['postcode'], $platform_country['countries_iso_code_2']);
      $this->_upsDest($this->delivery['postcode'], $this->delivery['country']['iso_code_2']);
      $this->_upsRate(MODULE_SHIPPING_UPS_PICKUP);
      $this->_upsContainer(MODULE_SHIPPING_UPS_PACKAGE);
      $this->_upsWeight($this->shipping_weight);
      $this->_upsRescom(MODULE_SHIPPING_UPS_RES);
      $upsQuote = $this->_upsGetQuote();

      if ( (is_array($upsQuote)) && (sizeof($upsQuote) > 0) ) {
        $this->quotes = array('id' => $this->code,
                              'module' => $this->title . ' (' . $this->shipping_num_boxes . ' x ' . $this->shipping_weight . 'lbs)');

        $methods = array();
		$allowed_methods = explode(", ", MODULE_SHIPPING_UPS_TYPES);
		$std_rcd = false;
        $qsize = sizeof($upsQuote);
        for ($i=0; $i<$qsize; $i++) {
          $type = key($upsQuote[$i]);
          $cost = current($upsQuote[$i]);
		  if ($type=='STD') {
			  if ($std_rcd) continue;
			  else $std_rcd = true;
			};
		  if (!in_array($type, $allowed_methods)) continue;
          $methods[] = array('id' => $type,
                             'title' => $this->types[$type],
                             'cost' => ($cost + MODULE_SHIPPING_UPS_HANDLING) * $this->shipping_num_boxes);
        }

        $this->quotes['methods'] = $methods;

        if ($this->tax_class > 0) {
          $this->quotes['tax'] = \common\helpers\Tax::get_tax_rate($this->tax_class, $this->delivery['country']['id'], $this->delivery['zone_id']);
        }
      } else {
        $this->quotes = array('module' => $this->title,
                              'error' => 'We are unable to obtain a rate quote for UPS shipping.<br>Please contact the store if no other alternative is shown.');
      }

      if (tep_not_null($this->icon)) $this->quotes['icon'] = tep_image($this->icon, $this->title);

      return $this->quotes;
    }


    public function configure_keys()
    {
      return array (
        'MODULE_SHIPPING_UPS_STATUS' =>
          array (
            'title' => 'Enable UPS Shipping',
            'value' => 'True',
            'description' => 'Do you want to offer UPS shipping?',
            'sort_order' => '0',
            'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
          ),
        'MODULE_SHIPPING_UPS_PICKUP' =>
          array (
            'title' => 'UPS Pickup Method',
            'value' => 'CC',
            'description' => 'How do you give packages to UPS? CC - Customer Counter, RDP - Daily Pickup, OTP - One Time Pickup, LC - Letter Center, OCA - On Call Air',
            'sort_order' => '0',
          ),
        'MODULE_SHIPPING_UPS_PACKAGE' =>
          array (
            'title' => 'UPS Packaging?',
            'value' => 'CP',
            'description' => 'CP - Your Packaging, ULE - UPS Letter, UT - UPS Tube, UBE - UPS Express Box',
            'sort_order' => '0',
          ),
        'MODULE_SHIPPING_UPS_RES' =>
          array (
            'title' => 'UPS Residential Delivery?',
            'value' => 'RES',
            'description' => 'Quote for Residential (RES) or Commercial Delivery (COM)',
            'sort_order' => '0',
          ),
        'MODULE_SHIPPING_UPS_HANDLING' =>
          array (
            'title' => 'UPS Handling Fee',
            'value' => '0',
            'description' => 'Handling fee for this shipping method.',
            'sort_order' => '0',
          ),
        'MODULE_SHIPPING_UPS_TAX_CLASS' =>
          array (
            'title' => 'UPS Tax Class',
            'value' => '0',
            'description' => 'Use the following tax class on the shipping fee.',
            'sort_order' => '0',
            'use_function' => '\\common\\helpers\\Tax::get_tax_class_title',
            'set_function' => 'tep_cfg_pull_down_tax_classes(',
          ),
        'MODULE_SHIPPING_UPS_ZONE' =>
          array (
            'title' => 'UPS Shipping Zone',
            'value' => '0',
            'description' => 'If a zone is selected, only enable this shipping method for that zone.',
            'sort_order' => '0',
            'use_function' => '\\common\\helpers\\Zones::get_zone_class_title',
            'set_function' => 'tep_cfg_pull_down_zone_classes(',
          ),
        'MODULE_SHIPPING_UPS_SORT_ORDER' =>
          array (
            'title' => 'UPS Sort order of display.',
            'value' => '0',
            'description' => 'Sort order of display. Lowest is displayed first.',
            'sort_order' => '0',
          ),
        'MODULE_SHIPPING_UPS_TYPES' =>
          array (
            'title' => 'UPS Shipping Methods',
            'value' => '1DM, 1DML, 1DA, 1DAL, 1DAPI, 1DP, 1DPL, 2DM, 2DML, 2DA, 2DAL, 3DS, GND, STD, XPR, XPRL, XDM, XDML, XPD',
            'description' => 'Select the UPS services to be offered.',
            'sort_order' => '13',
            'set_function' => 'tep_cfg_select_multioption(array(\'1DM\',\'1DML\', \'1DA\', \'1DAL\', \'1DAPI\', \'1DP\', \'1DPL\', \'2DM\', \'2DML\', \'2DA\', \'2DAL\', \'3DS\',\'GND\', \'STD\', \'XPR\', \'XPRL\', \'XDM\', \'XDML\', \'XPD\'), ',
          ),
      );
    }

    public function describe_status_key()
    {
      return new ModuleStatus('MODULE_SHIPPING_UPS_STATUS','True','False');
    }

    public function describe_sort_key()
    {
      return new ModuleSortOrder('MODULE_SHIPPING_UPS_SORT_ORDER');
    }

    function _upsProduct($prod){
      $this->_upsProductCode = $prod;
    }

    function _upsOrigin($postal, $country){
      $this->_upsOriginPostalCode = $postal;
      $this->_upsOriginCountryCode = $country;
    }

    function _upsDest($postal, $country){
      $postal = str_replace(' ', '', $postal);

      if ($country == 'US') {
        $this->_upsDestPostalCode = substr($postal, 0, 5);
      } else {
        $this->_upsDestPostalCode = $postal;
      }

      $this->_upsDestCountryCode = $country;
    }

    function _upsRate($foo) {
      switch ($foo) {
        case 'RDP':
          $this->_upsRateCode = 'Regular+Daily+Pickup';
          break;
        case 'OCA':
          $this->_upsRateCode = 'On+Call+Air';
          break;
        case 'OTP':
          $this->_upsRateCode = 'One+Time+Pickup';
          break;
        case 'LC':
          $this->_upsRateCode = 'Letter+Center';
          break;
        case 'CC':
          $this->_upsRateCode = 'Customer+Counter';
          break;
      }
    }

    function _upsContainer($foo) {
      switch ($foo) {
        case 'CP': // Customer Packaging
          $this->_upsContainerCode = '00';
          break;
        case 'ULE': // UPS Letter Envelope
          $this->_upsContainerCode = '01';
          break;
        case 'UT': // UPS Tube
          $this->_upsContainerCode = '03';
          break;
        case 'UEB': // UPS Express Box
          $this->_upsContainerCode = '21';
          break;
        case 'UW25': // UPS Worldwide 25 kilo
          $this->_upsContainerCode = '24';
          break;
        case 'UW10': // UPS Worldwide 10 kilo
          $this->_upsContainerCode = '25';
          break;
      }
    }

    function _upsWeight($foo) {
      $this->_upsPackageWeight = $foo;
    }

    function _upsRescom($foo) {
      switch ($foo) {
        case 'RES': // Residential Address
          $this->_upsResComCode = '1';
          break;
        case 'COM': // Commercial Address
          $this->_upsResComCode = '2';
          break;
      }
    }

    function _upsAction($action) {
      /* 3 - Single Quote
         4 - All Available Quotes */

      $this->_upsActionCode = $action;
    }

    function _upsGetQuote() {
      if (!isset($this->_upsActionCode)) $this->_upsActionCode = '4';

      $request = join('&', array('accept_UPS_license_agreement=yes',
                                 '10_action=' . $this->_upsActionCode,
                                 '13_product=' . $this->_upsProductCode,
                                 '14_origCountry=' . $this->_upsOriginCountryCode,
                                 '15_origPostal=' . $this->_upsOriginPostalCode,
                                 '19_destPostal=' . $this->_upsDestPostalCode,
                                 '22_destCountry=' . $this->_upsDestCountryCode,
                                 '23_weight=' . $this->_upsPackageWeight,
                                 '47_rate_chart=' . $this->_upsRateCode,
                                 '48_container=' . $this->_upsContainerCode,
                                 '49_residential=' . $this->_upsResComCode));
      $http = new \common\classes\httpClient();
      if ($http->Connect('www.ups.com', 80)) {
        $http->addHeader('Host', 'www.ups.com');
        $http->addHeader('User-Agent', 'osCommerce');
        $http->addHeader('Connection', 'Close');

        if ($http->Get('/using/services/rave/qcostcgi.cgi?' . $request)) $body = $http->getBody();

        $http->Disconnect();
      } else {
        return 'error';
      }
      $body_array = explode("\n", $body);

      $returnval = array();
      $errorret = 'error'; // only return error if NO rates returned

      $n = sizeof($body_array);
      for ($i=0; $i<$n; $i++) {
        $result = explode('%', $body_array[$i]);
        $errcode = substr($result[0], -1);
        switch ($errcode) {
          case 3:
            if (is_array($returnval)) $returnval[] = array($result[1] => $result[8]);
            break;
          case 4:
            if (is_array($returnval)) $returnval[] = array($result[1] => $result[8]);
            break;
          case 5:
            $errorret = $result[1];
            break;
          case 6:
            if (is_array($returnval)) $returnval[] = array($result[3] => $result[10]);
            break;
        }
      }
      if (empty($returnval)) $returnval = $errorret;

      return $returnval;
    }
  }
