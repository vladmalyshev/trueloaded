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

    final class usps extends ModuleShipping
    {
        public $code;
        public $title;
        public $description;
        public $icon, $enabled;
        public $countriesList;

        protected $defaultTranslationArray = [
            'MODULE_SHIPPING_USPS_TEXT_TITLE' => 'United States Postal Service',
            'MODULE_SHIPPING_USPS_TEXT_DESCRIPTION' => 'United States Postal Service<br><br>You will need to have registered an account with USPS at http://www.uspsprioritymail.com/et_regcert.html to use this module<br><br>USPS expects you to use pounds as weight measure for your products.',
            'MODULE_SHIPPING_USPS_TEXT_OPT_PP' => 'Parcel Post',
            'MODULE_SHIPPING_USPS_TEXT_OPT_PM' => 'Priority Mail',
            'MODULE_SHIPPING_USPS_TEXT_OPT_EX' => 'Express Mail',
            'MODULE_SHIPPING_USPS_TEXT_ERROR' => 'An error occured with the USPS shipping calculations.<br>If you prefer to use USPS as your shipping method, please contact the store owner.',
            'MODULE_SHIPPING_USPS_TEXT_DAY' => 'Day',
            'MODULE_SHIPPING_USPS_TEXT_DAYS' => 'Days',
            'MODULE_SHIPPING_USPS_TEXT_WEEKS' => 'Weeks'
        ];

    // class constructor
    public function __construct()
    {
        parent::__construct();

        $this->code = 'usps';
        $this->title = MODULE_SHIPPING_USPS_TEXT_TITLE;
        $this->description = MODULE_SHIPPING_USPS_TEXT_DESCRIPTION;
        if (!defined('MODULE_SHIPPING_USPS_STATUS')) {
            $this->enabled = false;
            return false;
        }
        $this->sort_order = MODULE_SHIPPING_USPS_SORT_ORDER;
        $this->icon = DIR_WS_ICONS . 'shipping_usps.gif';
        $this->tax_class = MODULE_SHIPPING_USPS_TAX_CLASS;
        $this->enabled = ((MODULE_SHIPPING_USPS_STATUS == 'True') ? true : false);

        if (($this->enabled == true) && ((int) MODULE_SHIPPING_USPS_ZONE > 0)) {
            $check_flag = false;
            $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_SHIPPING_USPS_ZONE . "' and zone_country_id = '" . $this->delivery['country']['id'] . "' order by zone_id");
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

        $this->types = [
          'First Class' => 'First-Class Mail',
          'Priority Mail' => 'Priority Mail',
          'Priority Mail Express' => 'Priority Mail Express',
          ];

        $this->intl_types = [
          'Priority Mail Express International' => 'Priority Mail Express International',
          'Priority Mail International' => 'Priority Mail International',
          ];

        $this->countriesList = $this->country_list();

    }

    // class methods
    public function quote($method = '')
    {
      global $transittime;
      if ( tep_not_null($method) && (isset($this->types[$method]) || in_array($method, $this->intl_types)) ) {
        $this->_setService($method);
      }

      $this->_valueOfContents(100);
      $this->_mailType('Package');
      $this->_setMachinable('False');
      $this->_setContainer('VARIABLE');
      $this->_setSize('REGULAR');
      $this->_setWidth(15);
      $this->_setLength(30);
      $this->_setHeight(15);
      $this->_setGirth(55);

      // usps doesnt accept zero weight
      $shipping_weight = ($this->shipping_weight < 0.1 ? 0.1 : $this->shipping_weight);
      $shipping_pounds = floor ($shipping_weight);
      $shipping_ounces = round(16 * ($shipping_weight - floor($shipping_weight)));
      $this->_setWeight($shipping_pounds, $shipping_ounces);

      if (in_array('Display weight', explode(', ', MODULE_SHIPPING_USPS_OPTIONS))) {
        $shiptitle = ' (' . $this->shipping_num_boxes . ' x ' . $shipping_weight . 'lbs)';
      } else {
        $shiptitle = '';
      }

      $uspsQuote = $this->_getQuote();
      // var_dump($uspsQuote);die;
      if (is_array($uspsQuote)) {
        if (isset($uspsQuote['error'])) {
          $this->quotes = array('module' => $this->title,
                                'error' => $uspsQuote['error']);
        } else {
          $this->quotes = array('id' => $this->code,
                                'module' => $this->title . $shiptitle);

          $methods = [];
          $size = sizeof($uspsQuote);
          for ($i=0; $i<$size; $i++) {
            $type = key($uspsQuote[$i]);
            $cost = current($uspsQuote[$i]);

            $title = ((isset($this->types[$type])) ? $this->types[$type] : $type);

            $methods[] = array('id' => $type,
                               'title' => $type . ' ' . $transittime[$type],
                               'cost' => ($cost + MODULE_SHIPPING_USPS_HANDLING) * $this->shipping_num_boxes);
          }

          $this->quotes['methods'] = $methods;

          if ($this->tax_class > 0) {
            $this->quotes['tax'] = \common\helpers\Tax::get_tax_rate($this->tax_class, $this->delivery['country']['id'], $this->delivery['zone_id']);
          }
        }
      } else {
        $this->quotes = array('module' => $this->title,
                              'error' => MODULE_SHIPPING_USPS_TEXT_ERROR);
      }

      // if (tep_not_null($this->icon)) $this->quotes['icon'] = tep_image($this->icon, $this->title);

      return $this->quotes;
    }


    public function configure_keys()
    {
      return array (
        'MODULE_SHIPPING_USPS_STATUS' =>
          array (
            'title' => 'Enable USPS Shipping',
            'value' => 'True',
            'description' => 'Do you want to offer USPS shipping?',
            'sort_order' => '0',
            'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
          ),
        'MODULE_SHIPPING_USPS_USERID' =>
          array (
            'title' => 'Enter the USPS User ID',
            'value' => 'NONE',
            'description' => 'Enter the USPS USERID assigned to you.',
            'sort_order' => '0',
          ),
        'MODULE_SHIPPING_USPS_PASSWORD' =>
          array (
            'title' => 'Enter the USPS Password',
            'value' => 'NONE',
            'description' => 'See USERID, above.',
            'sort_order' => '0',
          ),
        'MODULE_SHIPPING_USPS_SERVER' =>
          array (
            'title' => 'Which server to use',
            'value' => 'production',
            'description' => 'An account at USPS is needed to use the Production server',
            'sort_order' => '0',
            'set_function' => 'tep_cfg_select_option(array(\'test\', \'production\'), ',
          ),
        'MODULE_SHIPPING_USPS_HANDLING' =>
          array (
            'title' => 'Handling Fee',
            'value' => '0',
            'description' => 'Handling fee for this shipping method.',
            'sort_order' => '0',
          ),
        'MODULE_SHIPPING_USPS_TAX_CLASS' =>
          array (
            'title' => 'Tax Class',
            'value' => '0',
            'description' => 'Use the following tax class on the shipping fee.',
            'sort_order' => '0',
            'use_function' => '\\common\\helpers\\Tax::get_tax_class_title',
            'set_function' => 'tep_cfg_pull_down_tax_classes(',
          ),
        'MODULE_SHIPPING_USPS_ZONE' =>
          array (
            'title' => 'Shipping Zone',
            'value' => '0',
            'description' => 'If a zone is selected, only enable this shipping method for that zone.',
            'sort_order' => '0',
            'use_function' => '\\common\\helpers\\Zones::get_zone_class_title',
            'set_function' => 'tep_cfg_pull_down_zone_classes(',
          ),

        'MODULE_SHIPPING_USPS_SORT_ORDER' =>
          array (
            'title' => 'Sort Order',
            'value' => '0',
            'description' => 'Sort order of display.',
            'sort_order' => '0',
          ),
        'MODULE_SHIPPING_USPS_OPTIONS' =>
          array (
            'title' => 'USPS Options',
            'value' => 'Display weight, Display transit time',
            'description' => 'Select from the following the USPS options.',
            'sort_order' => '16',
            'set_function' => 'tep_cfg_select_multioption(array(\'Display weight\', \'Display transit time\'), ',
          ),
        'MODULE_SHIPPING_USPS_TYPES' =>
          [
            'title' => 'Domestic Shipping Methods',
            'value' => 'First Class, Priority Mail, Priority Mail Express',
            'description' => 'Select the domestic services to be offered:',
            'sort_order' => '14',
            'set_function' => 'tep_cfg_select_multioption(array('
              . '\'First Class\', '
              . '\'Priority Mail\', '
              . '\'Priority Mail Express\'), ',
          ],
        'MODULE_SHIPPING_USPS_TYPES_INTL' =>
          [
            'title' => 'Int\'l Shipping Methods',
            'value' => 'Priority Mail Express International, Priority Mail International',
            'description' => 'Select the international services to be offered:',
            'sort_order' => '15',
            'set_function' => 'tep_cfg_select_multioption(array(\'Priority Mail Express International\', \'Priority Mail International\'), ',
          ],
      );
    }

    public function describe_status_key()
    {
      return new ModuleStatus('MODULE_SHIPPING_USPS_STATUS','True','False');
    }

    public function describe_sort_key()
    {
      return new ModuleSortOrder('MODULE_SHIPPING_ZONES_SORT_ORDER');
    }

    public function _setService($service)
    {
      $this->service = $service;
    }

    public function _setWeight($pounds, $ounces=0)
    {
      $this->pounds = $pounds;
      $this->ounces = $ounces;
    }

    public function _setContainer($container)
    {
      $this->container = $container;
    }

    public function _valueOfContents($valueOfContents)
    {
      $this->valueOfContents = $valueOfContents;
    }

    public function _setSize($size)
    {
      $this->size = $size;
    }

    public function _mailType($mailType)
    {
      $this->mailType = $mailType;
    }

    public function _setMachinable($machinable)
    {
      $this->machinable = $machinable;
    }

    public function _setWidth($width)
    {
        $this->width = $width;
    }

    function _setLength($length)
    {
        $this->length = $length;
    }

    public function _setHeight($height)
    {
        $this->height = $height;
    }

    public function _setGirth($girth)
    {
        $this->girth = $girth;
    }

    public function _getQuote()
    {
      global $transittime;
      if(in_array('Display transit time', explode(', ', MODULE_SHIPPING_USPS_OPTIONS))) $transit = TRUE;

      $config = Yii::$app->get('platform')->config(/*$order->info['platform_id']*/);
      $platformsAddressBook = $config->getPlatformAddress();
      $platform_country = \common\helpers\Country::get_country_info_by_id($platformsAddressBook['country_id']);
      
      if ($this->delivery['country']['id'] == $platformsAddressBook['country_id']) {
        $request  = '<RateV4Request USERID="' . MODULE_SHIPPING_USPS_USERID . '" PASSWORD="' . MODULE_SHIPPING_USPS_PASSWORD . '">';
        $services_count = 0;

        if (isset($this->service)) {
          $this->types = array($this->service => $this->types[$this->service]);
        }

        $dest_zip = str_replace(' ', '', $this->delivery['postcode']);
        if ($this->delivery['country']['iso_code_2'] == 'US') $dest_zip = substr($dest_zip, 0, 5);

        $allowed_types = explode(", ", MODULE_SHIPPING_USPS_TYPES);

        if (is_array($this->types)) foreach ($this->types as $key => $value) {

	  if ( !in_array($key, $allowed_types) ) continue;

          $request .= '<Package ID="' . $services_count . '">' .
                      '<Service>' . $key . '</Service>' .
                      '<ZipOrigination>' . $platformsAddressBook['postcode'] . '</ZipOrigination>' .
                      '<ZipDestination>' . $dest_zip . '</ZipDestination>' .
                      '<Pounds>' . $this->pounds . '</Pounds>' .
                      '<Ounces>' . $this->ounces . '</Ounces>' .
                      '<Container>' . $this->container . '</Container>' .
                      '<Size>' . $this->size . '</Size>' .
                      //'<Machinable>' . $this->machinable . '</Machinable>' .
                      '<Width>'  . $this->width . '</Width>' .
                      '<Length>' . $this->length . '</Length>' .
                      '<Height>' . $this->height . '</Height>' .
                      '<Girth>' . $this->girth . '</Girth>' .
                      '</Package>';
          if($transit){
            $transitreq  = 'USERID="' . MODULE_SHIPPING_USPS_USERID .
                         '" PASSWORD="' . MODULE_SHIPPING_USPS_PASSWORD . '">' .
                         '<OriginZip>' . $platformsAddressBook['postcode'] . '</OriginZip>' .
                         '<DestinationZip>' . $dest_zip . '</DestinationZip>';

            switch ($key) {
              case 'Express':  $transreq[$key] = 'API=ExpressMail&XML=' .
                               urlencode( '<ExpressMailRequest>' . $transitreq . '</ExpressMailRequest>');
                               break;
              case 'Priority': $transreq[$key] = 'API=PriorityMail&XML=' .
                               urlencode( '<PriorityMailRequest>' . $transitreq . '</PriorityMailRequest>');
                               break;
              case 'Parcel':   $transreq[$key] = 'API=StandardB&XML=' .
                               urlencode( '<StandardBRequest>' . $transitreq . '</StandardBRequest>');
                               break;
              default:         $transreq[$key] = '';
                               break;
            }
          }

          $services_count++;
        }
        $request .= '</RateV4Request>';

        // var_dump($request);die;
        $api = 'RateV4';


      } else {

        $dest_zip = str_replace(' ', '', $this->delivery['postcode']);
        $request  = '<IntlRateV2Request USERID="' . MODULE_SHIPPING_USPS_USERID . '" PASSWORD="' . MODULE_SHIPPING_USPS_PASSWORD . '">' .
                    //'<Revision>' . 2 . '</Revision>' .
                    '<Package ID="0">' .
                    '<Pounds>' . $this->pounds . '</Pounds>' .
                    '<Ounces>' . $this->ounces . '</Ounces>' .
                    '<Machinable>' . $this->machinable . '</Machinable>' .
                    '<MailType>' . $this->mailType . '</MailType>' .
                    '<ValueOfContents>2499</ValueOfContents>' .
                    '<Country>' . $this->countriesList[$this->delivery['country']['iso_code_2']] . '</Country>' .
                    '<Container>' . $this->container . '</Container>' .
                    '<Size>' . $this->size . '</Size>' .
                    '<Width>'  . $this->width . '</Width>' .
                    '<Length>' . $this->length . '</Length>' .
                    '<Height>' . $this->height . '</Height>' .
                    '<Girth>' . $this->girth . '</Girth>' .
                    // '<OriginZip>' . $dest_zip . '</OriginZip>' .
                    '</Package>' .
                    '</IntlRateV2Request>';

        $api = 'IntlRateV2';
      }
       // var_dump($request);die;

      switch (MODULE_SHIPPING_USPS_SERVER) {
        case 'production': $usps_server = 'production.shippingapis.com';
                           $api_dll = 'ShippingApi.dll';
                           break;
        case 'test':
        default:           $usps_server = 'production.shippingapis.com';
                           $api_dll = 'ShippingAPITest.dll';
                           break;
      }

      $body = '';

      $_transport = 'yii\httpclient\CurlTransport';

      $http = new \yii\httpclient\Client(
                [
                'transport' => $_transport,
                'baseUrl' => $usps_server,
                ]
            );

      $params = [
              'API' => $api,
              'XML'  => $request,
            ];

      $resp = $http->createRequest()
                    ->setMethod('get')
                    ->setUrl($api_dll)
                    ->setData($params)
                    ->send();

      if ($resp)

      $body = $resp->content;
        //  mail('you@yourdomain.com','USPS rate quote response',$body,'From: <you@yourdomain.com>');
        /*
        if ($transit && is_array($transreq) && ($order->delivery['country']['id'] == STORE_COUNTRY)) {
          foreach ($transreq as $key => $value) {
              // var_dump($value);die;
            if ($http->Get('/' . $api_dll . '?' . $value))
                    $transresp[$key] = $http->getBody();
          }
        }
         *
         */
      $response = [];
      while (true) {
        if ($start = strpos($body, '<Package ID=')) {
          $body = substr($body, $start);
          $end = strpos($body, '</Package>');
          $response[] = substr($body, 0, $end+10);
          $body = substr($body, $end+9);
        } else {
          break;
        }
      }

      // var_dump($response);die;
      $rates = [];
      if ($this->delivery['country']['id'] == $platformsAddressBook['country_id']) {
        if (sizeof($response) == '1') {
          if (preg_match('/<Error>/', $response[0])) {
            $number = preg_match('/<Number>(.*)<\/Number>/', $response[0], $regs);
            $number = $regs[1];
            $description = preg_match('/<Description>(.*)<\/Description>/', $response[0], $regs);
            $description = $regs[1];

            return array('error' => $number . ' - ' . $description);
          }
        }

        // var_dump($response);die;
        $n = sizeof($response);

        for ($i=0; $i<$n; $i++) {
          if (strpos($response[$i], '<Postage')) {
            $service = preg_match('/<MailService>(.*)<\/MailService>/', $response[$i], $regs);
            // $service = strstr( $regs[1] . ' ', ' ' , true );
            $service = strstr( $regs[1] . ' ', ' 2-D' , true );
            // var_dump($service);die;
            $postage = preg_match('/<Rate>(.*)<\/Rate>/', $response[$i], $regs);
            $postage = $regs[1];
            $rates[] = array($service => $postage);
            if ($transit) {
              switch ($service) {
                case 'Priority Mail':
                                    $time = '1-2' . ' ' . MODULE_SHIPPING_USPS_TEXT_DAYS;
                                    break;
                case 'Priority Mail Express':
                                    $time = '2-7' . ' ' . MODULE_SHIPPING_USPS_TEXT_DAYS;
                                    break;
                                    break;
                case 'First Class':
                                    $time = '2 - 5 ' . MODULE_SHIPPING_USPS_TEXT_DAYS;
                                    break;
                default:            $time = '';
                                    break;
              }
              if ($time != '') $transittime[$service] = ' (' . $time . ')';
            }
          }
        }
      } else {
        if (preg_match('/<Error>/', $response[0])) {
          $number = preg_match('/<Number>(.*)<\/Number>/', $response[0], $regs);
          $number = $regs[1];
          $description = preg_match('/<Description>(.*)<\/Description>/', $response[0], $regs);
          $description = $regs[1];

          return array('error' => $number . ' - ' . $description);
        } else {
          $body = $response[0];
          $services = array();
          while (true) {
            if ($start = strpos($body, '<Service ID=')) {
              $body = substr($body, $start);
              $end = strpos($body, '</Service>');
              $services[] = substr($body, 0, $end+10);
              $body = substr($body, $end+9);
            } else {
              break;
            }
          }
          // var_dump($services);die;
          $allowed_types = [];
          $array = explode(", ", MODULE_SHIPPING_USPS_TYPES_INTL);
          foreach ($array as $value) {
              $allowed_types[$value] = $this->intl_types[$value];
          }
          $size = sizeof($services);
          for ($i=0, $n=$size; $i<$n; $i++) {
            if (strpos($services[$i], '<ExtraServices/>')) {
              $service = preg_match('/<SvcDescription>(.*)<\/SvcDescription>/', $services[$i], $regs);
              $service = strstr( $regs[1] . ' ', '&amp' , true );
              // $service = strstr( $regs[1] . ' ', ' Mail' , true );
              // var_dump($service);
              $postage = preg_match('/<Postage>(.*)<\/Postage>/', $services[$i], $regs);
              $postage = $regs[1];
              $time = preg_match('/<SvcCommitments>(.*)<\/SvcCommitments>/', $services[$i], $tregs);
              $time = $tregs[1];
              $time = preg_replace('/Weeks$/', MODULE_SHIPPING_USPS_TEXT_WEEKS, $time);
              $time = preg_replace('/Days$/', MODULE_SHIPPING_USPS_TEXT_DAYS, $time);
              $time = preg_replace('/Day$/', MODULE_SHIPPING_USPS_TEXT_DAY, $time);
              if( !in_array($service, $allowed_types) ) continue;
              if (isset($this->service) && ($service != $this->service) ) {
                continue;
              }

              $rates[] = array($service => $postage);
	      if ($time != '') $transittime[$service] = ' (' . $time . ')';
            }
          }
        }
      }

      return ((sizeof($rates) > 0) ? $rates : false);
    }

    function country_list() {
      $list = array('AF' => 'Afghanistan',
                    'AL' => 'Albania',
                    'DZ' => 'Algeria',
                    'AD' => 'Andorra',
                    'AO' => 'Angola',
                    'AI' => 'Anguilla',
                    'AG' => 'Antigua and Barbuda',
                    'AR' => 'Argentina',
                    'AM' => 'Armenia',
                    'AW' => 'Aruba',
                    'AU' => 'Australia',
                    'AT' => 'Austria',
                    'AZ' => 'Azerbaijan',
                    'BS' => 'Bahamas',
                    'BH' => 'Bahrain',
                    'BD' => 'Bangladesh',
                    'BB' => 'Barbados',
                    'BY' => 'Belarus',
                    'BE' => 'Belgium',
                    'BZ' => 'Belize',
                    'BJ' => 'Benin',
                    'BM' => 'Bermuda',
                    'BT' => 'Bhutan',
                    'BO' => 'Bolivia',
                    'BA' => 'Bosnia-Herzegovina',
                    'BW' => 'Botswana',
                    'BR' => 'Brazil',
                    'VG' => 'British Virgin Islands',
                    'BN' => 'Brunei Darussalam',
                    'BG' => 'Bulgaria',
                    'BF' => 'Burkina Faso',
                    'MM' => 'Burma',
                    'BI' => 'Burundi',
                    'KH' => 'Cambodia',
                    'CM' => 'Cameroon',
                    'CA' => 'Canada',
                    'CV' => 'Cape Verde',
                    'KY' => 'Cayman Islands',
                    'CF' => 'Central African Republic',
                    'TD' => 'Chad',
                    'CL' => 'Chile',
                    'CN' => 'China',
                    'CX' => 'Christmas Island (Australia)',
                    'CC' => 'Cocos Island (Australia)',
                    'CO' => 'Colombia',
                    'KM' => 'Comoros',
                    'CG' => 'Congo (Brazzaville),Republic of the',
                    'ZR' => 'Congo, Democratic Republic of the',
                    'CK' => 'Cook Islands (New Zealand)',
                    'CR' => 'Costa Rica',
                    'CI' => 'Cote d\'Ivoire (Ivory Coast)',
                    'HR' => 'Croatia',
                    'CU' => 'Cuba',
                    'CY' => 'Cyprus',
                    'CZ' => 'Czech Republic',
                    'DK' => 'Denmark',
                    'DJ' => 'Djibouti',
                    'DM' => 'Dominica',
                    'DO' => 'Dominican Republic',
                    'TP' => 'East Timor (Indonesia)',
                    'EC' => 'Ecuador',
                    'EG' => 'Egypt',
                    'SV' => 'El Salvador',
                    'GQ' => 'Equatorial Guinea',
                    'ER' => 'Eritrea',
                    'EE' => 'Estonia',
                    'ET' => 'Ethiopia',
                    'FK' => 'Falkland Islands',
                    'FO' => 'Faroe Islands',
                    'FJ' => 'Fiji',
                    'FI' => 'Finland',
                    'FR' => 'France',
                    'GF' => 'French Guiana',
                    'PF' => 'French Polynesia',
                    'GA' => 'Gabon',
                    'GM' => 'Gambia',
                    'GE' => 'Georgia, Republic of',
                    'DE' => 'Germany',
                    'GH' => 'Ghana',
                    'GI' => 'Gibraltar',
                    'GB' => 'Great Britain and Northern Ireland',
                    'GR' => 'Greece',
                    'GL' => 'Greenland',
                    'GD' => 'Grenada',
                    'GP' => 'Guadeloupe',
                    'GT' => 'Guatemala',
                    'GN' => 'Guinea',
                    'GW' => 'Guinea-Bissau',
                    'GY' => 'Guyana',
                    'HT' => 'Haiti',
                    'HN' => 'Honduras',
                    'HK' => 'Hong Kong',
                    'HU' => 'Hungary',
                    'IS' => 'Iceland',
                    'IN' => 'India',
                    'ID' => 'Indonesia',
                    'IR' => 'Iran',
                    'IQ' => 'Iraq',
                    'IE' => 'Ireland',
                    'IL' => 'Israel',
                    'IT' => 'Italy',
                    'JM' => 'Jamaica',
                    'JP' => 'Japan',
                    'JO' => 'Jordan',
                    'KZ' => 'Kazakhstan',
                    'KE' => 'Kenya',
                    'KI' => 'Kiribati',
                    'KW' => 'Kuwait',
                    'KG' => 'Kyrgyzstan',
                    'LA' => 'Laos',
                    'LV' => 'Latvia',
                    'LB' => 'Lebanon',
                    'LS' => 'Lesotho',
                    'LR' => 'Liberia',
                    'LY' => 'Libya',
                    'LI' => 'Liechtenstein',
                    'LT' => 'Lithuania',
                    'LU' => 'Luxembourg',
                    'MO' => 'Macao',
                    'MK' => 'Macedonia, Republic of',
                    'MG' => 'Madagascar',
                    'MW' => 'Malawi',
                    'MY' => 'Malaysia',
                    'MV' => 'Maldives',
                    'ML' => 'Mali',
                    'MT' => 'Malta',
                    'MQ' => 'Martinique',
                    'MR' => 'Mauritania',
                    'MU' => 'Mauritius',
                    'YT' => 'Mayotte (France)',
                    'MX' => 'Mexico',
                    'MD' => 'Moldova',
                    'MC' => 'Monaco (France)',
                    'MN' => 'Mongolia',
                    'MS' => 'Montserrat',
                    'MA' => 'Morocco',
                    'MZ' => 'Mozambique',
                    'NA' => 'Namibia',
                    'NR' => 'Nauru',
                    'NP' => 'Nepal',
                    'NL' => 'Netherlands',
                    'AN' => 'Netherlands Antilles',
                    'NC' => 'New Caledonia',
                    'NZ' => 'New Zealand',
                    'NI' => 'Nicaragua',
                    'NE' => 'Niger',
                    'NG' => 'Nigeria',
                    'KP' => 'North Korea (Korea, Democratic People\'s Republic of)',
                    'NO' => 'Norway',
                    'OM' => 'Oman',
                    'PK' => 'Pakistan',
                    'PA' => 'Panama',
                    'PG' => 'Papua New Guinea',
                    'PY' => 'Paraguay',
                    'PE' => 'Peru',
                    'PH' => 'Philippines',
                    'PN' => 'Pitcairn Island',
                    'PL' => 'Poland',
                    'PT' => 'Portugal',
                    'QA' => 'Qatar',
                    'RE' => 'Reunion',
                    'RO' => 'Romania',
                    'RU' => 'Russia',
                    'RW' => 'Rwanda',
                    'SH' => 'Saint Helena',
                    'KN' => 'Saint Kitts (St. Christopher and Nevis)',
                    'LC' => 'Saint Lucia',
                    'PM' => 'Saint Pierre and Miquelon',
                    'VC' => 'Saint Vincent and the Grenadines',
                    'SM' => 'San Marino',
                    'ST' => 'Sao Tome and Principe',
                    'SA' => 'Saudi Arabia',
                    'SN' => 'Senegal',
                    'YU' => 'Serbia-Montenegro',
                    'SC' => 'Seychelles',
                    'SL' => 'Sierra Leone',
                    'SG' => 'Singapore',
                    'SK' => 'Slovak Republic',
                    'SI' => 'Slovenia',
                    'SB' => 'Solomon Islands',
                    'SO' => 'Somalia',
                    'ZA' => 'South Africa',
                    'GS' => 'South Georgia (Falkland Islands)',
                    'KR' => 'South Korea (Korea, Republic of)',
                    'ES' => 'Spain',
                    'LK' => 'Sri Lanka',
                    'SD' => 'Sudan',
                    'SR' => 'Suriname',
                    'SZ' => 'Swaziland',
                    'SE' => 'Sweden',
                    'CH' => 'Switzerland',
                    'SY' => 'Syrian Arab Republic',
                    'TW' => 'Taiwan',
                    'TJ' => 'Tajikistan',
                    'TZ' => 'Tanzania',
                    'TH' => 'Thailand',
                    'TG' => 'Togo',
                    'TK' => 'Tokelau (Union) Group (Western Samoa)',
                    'TO' => 'Tonga',
                    'TT' => 'Trinidad and Tobago',
                    'TN' => 'Tunisia',
                    'TR' => 'Turkey',
                    'TM' => 'Turkmenistan',
                    'TC' => 'Turks and Caicos Islands',
                    'TV' => 'Tuvalu',
                    'UG' => 'Uganda',
                    'UA' => 'Ukraine',
                    'AE' => 'United Arab Emirates',
                    'UY' => 'Uruguay',
                    'UZ' => 'Uzbekistan',
                    'VU' => 'Vanuatu',
                    'VA' => 'Vatican City',
                    'VE' => 'Venezuela',
                    'VN' => 'Vietnam',
                    'WF' => 'Wallis and Futuna Islands',
                    'WS' => 'Western Samoa',
                    'YE' => 'Yemen',
                    'ZM' => 'Zambia',
                    'ZW' => 'Zimbabwe');

      return $list;
    }
}
