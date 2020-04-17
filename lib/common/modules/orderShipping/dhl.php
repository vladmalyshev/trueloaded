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
use common\classes\modules\ModuleSortOrder;
use common\classes\modules\ModuleStatus;
use common\classes\platform_config;

/**
 * Class dhl
 * @package common\modules\orderShipping
 *
 * SiteId and get here https://xmlportal.dhl.com/register
 *
 */
class dhl extends ModuleShipping
{
    var $code, $title, $description, $icon, $enabled;

    protected $defaultTranslationArray = [
        'MODULE_SHIPPING_DHL_TEXT_TITLE' => 'DHL',
        'MODULE_SHIPPING_DHL_TEXT_DESCRIPTION' => 'DHL XMLPI Services',
        'MODULE_SHIPPING_DHL_TEXT_ERROR_NO_RATES' => 'Please enter a ZIP Code to obtain your shipping quote.',
        'MODULE_SHIPPING_DHL_TEXT_ERROR_NOT_AVAILABLE' => 'The requested service is unavailable between the selected locations.',
    ];

    private $shipFromAddress = [];
    private $types;
    private static $requestResult = [];

    function __construct()
    {
        parent::__construct();

        $this->code = 'dhl';
        $this->title = MODULE_SHIPPING_DHL_TEXT_TITLE;
        $this->description = MODULE_SHIPPING_DHL_TEXT_DESCRIPTION;
        if (!defined('MODULE_SHIPPING_DHL_STATUS')) {
            $this->enabled = false;
            return false;
        }
        $this->sort_order = MODULE_SHIPPING_DHL_SORT_ORDER;

        $this->tax_class = MODULE_SHIPPING_DHL_TAX_CLASS;
        $this->enabled = ((MODULE_SHIPPING_DHL_STATUS == 'True') ? true : false);

        if (($this->enabled == true) && ((int)MODULE_SHIPPING_DHL_ZONE > 0)) {
            $check_flag = false;
            $check_query = tep_db_query("SELECT zone_id FROM " . TABLE_ZONES_TO_GEO_ZONES . " WHERE geo_zone_id = '" . MODULE_SHIPPING_DHL_ZONE . "' AND zone_country_id = '" . $this->delivery['country']['id'] . "' ORDER BY zone_id");
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
        $this->initVariables();
    }

    protected function initVariables()
    {
        $country_info = \common\helpers\Country::get_country_info_by_id(MODULE_SHIPPING_DHL_COUNTRY);
        $this->shipFromAddress = [
            'City' => MODULE_SHIPPING_DHL_CITY,
            'PostalCode' => MODULE_SHIPPING_DHL_POSTAL,
            'CountryCode' => isset($country_info['countries_iso_code_2']) ? $country_info['countries_iso_code_2'] : '',
        ];

        $this->types = [
            'DOMESTIC' => [
                'icon' => '',
                'handling_fee' => MODULE_SHIPPING_DHL_DOM_HANDLING_FEE,
            ],
            'INTERNATIONAL' => [
                'icon' => '',
                'handling_fee' => MODULE_SHIPPING_DHL_INT_HANDLING_FEE,
            ]
        ];
    }

    function quote($method = '')
    {
        $methods = [];
        $shipping_weight = $this->shipping_weight;
        $cart = $this->manager->getCart();
        $order_total = $cart->show_total();
        $shipping_num_boxes = 1;

        $currency = \Yii::$app->settings->get('currency');
        if (empty($currency)) $currency = \Yii::$app->get('platform')->config()->getDefaultCurrency();
        if (empty($currency)) $currency = DEFAULT_CURRENCY;
        $requestCurrency = $currency;

        $this->quotes = array(
            'id' => $this->code,
            'module' => $this->title
        );

        $response = $this->fetchRates($this->delivery, $shipping_weight, $order_total, $requestCurrency);
        if (is_array($response)) {
            if (isset($response['error'])) {
                $this->quotes['error'] = $response['error'];
            } else {
                $allowed_product_codes = preg_split('/,\s*/', MODULE_SHIPPING_DHL_ALLOWED_PRODUCT_CODES, -1, PREG_SPLIT_NO_EMPTY);
                $allowed_product_codes = array_flip($allowed_product_codes);

                $type = $this->shipFromAddress['CountryCode'] == $this->delivery['country']['iso_code_2'] ? 'DOMESTIC' : 'INTERNATIONAL';
                $methods = [];
                foreach ($response as $serverRate) {
                    if ( !empty($method) && strval($method)!==strval($serverRate['id']) ) continue;
                    if (!isset($allowed_product_codes[$serverRate['id']])) continue;
                    $cost = $serverRate['cost'];
                    if (strpos($this->types[$type]['handling_fee'], '%') !== false) {
                        $additional_fee = ($cost * (float)$this->types[$type]['handling_fee'] / 100);
                    } else {
                        $additional_fee = (float)$this->types[$type]['handling_fee'];
                    }

                    $methods[] = [
                        'id' => $serverRate['id'],
                        'title' => ucwords(strtolower(str_replace('_', ' ', $serverRate['title']))),
                        'cost' => $cost + $additional_fee,
                    ];
                }
                // Limit to cheapest
                // begin sort order control - low to high is set
                usort($methods, array($this, 'cmp'));
                // end sort order control - comment out section to apply high to low rate sort order

                $this->quotes['methods'] = $methods;
            }
            if ($this->tax_class > 0) {
                $this->quotes['tax'] = \common\helpers\Tax::get_tax_rate($this->tax_class, $this->delivery['country']['id'], $this->delivery['zone_id']);
            }
        }

        if ((!isset($this->quotes['methods']) || count($this->quotes['methods']) == 0) && empty($this->quotes['error'])) {
            $message = MODULE_SHIPPING_DHL_TEXT_ERROR_NO_RATES;
            $this->quotes = array(
                'id' => $this->code,
                'module' => $this->title,
                'error' => $message,
            );
        }

        if (tep_not_null($this->icon)) $this->quotes['icon'] = tep_image($this->icon, $this->title);

        return $this->quotes;
    }

    function cmp($a, $b)
    {
        if ($a['cost'] == $b['cost']) {
            return 0;
        }
        return ($a['cost'] < $b['cost']) ? -1 : 1;
    }

    protected function fetchRates($deliveryAddress, $shipping_weight, $total, $requestCurrency)
    {
        $weightUOM = MODULE_SHIPPING_DHL_WEIGHT_UOM;
        $dimensionUOM = MODULE_SHIPPING_DHL_WEIGHT_UOM == 'LB' ? 'IN' : 'CM';

        $insureData = '';
        if (MODULE_SHIPPING_DHL_INSURE != '' && MODULE_SHIPPING_DHL_INSURE >= 0 && $total >= MODULE_SHIPPING_DHL_INSURE) {
            $insureData =
                '<InsuredValue>' . sprintf("%01.2f", ceil($total)) . '</InsuredValue>' .
                '<InsuredCurrency>' . $requestCurrency . '</InsuredCurrency>';
        }

        $networkType = substr(MODULE_SHIPPING_DHL_NETWORK, 0, 2);
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<p:DCTRequest xmlns:p="http://www.dhl.com" xmlns:p1="http://www.dhl.com/datatypes" xmlns:p2="http://www.dhl.com/DCTRequestdatatypes" schemaVersion="2.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.dhl.com DCT-req.xsd ">
   <GetQuote>
      <Request>
         <ServiceHeader>
            <MessageTime>' . date('c') . '</MessageTime>
            <MessageReference>' . md5(time()) . '</MessageReference>
            <SiteID>' . MODULE_SHIPPING_DHL_SERVICE_SITE_ID . '</SiteID>
            <Password>' . MODULE_SHIPPING_DHL_SERVICE_PASSWORD . '</Password>
         </ServiceHeader>
         <MetaData>
            <SoftwareName>Trueloaded</SoftwareName>
            <SoftwareVersion>3.0</SoftwareVersion>
         </MetaData>
      </Request>
      <From>
         <CountryCode>' . static::xmlString($this->shipFromAddress['CountryCode']) . '</CountryCode>
         <Postalcode>' . static::xmlString($this->shipFromAddress['PostalCode']) . '</Postalcode>
         <City>' . static::xmlString($this->shipFromAddress['City']) . '</City>
      </From>
      <BkgDetails>
         <PaymentCountryCode>' . static::xmlString($this->shipFromAddress['CountryCode']) . '</PaymentCountryCode>
         <Date>' . date('Y-m-d') . '</Date>
         <ReadyTime>PT5M</ReadyTime>
         <DimensionUnit>' . $dimensionUOM . '</DimensionUnit>
         <WeightUnit>' . $weightUOM . '</WeightUnit>
         <NumberOfPieces>1</NumberOfPieces>
         <ShipmentWeight>' . number_format($shipping_weight, 3, '.', '') . '</ShipmentWeight>
         <Pieces>
            <Piece>
               <PieceID>1</PieceID>
               <Weight>' . number_format($shipping_weight, 3, '.', '') . '</Weight>
            </Piece>
         </Pieces>
         <NetworkTypeCode>' . $networkType . '</NetworkTypeCode>
         ' . $insureData . '
      </BkgDetails>
      <To>
         <CountryCode>' . static::xmlString($deliveryAddress['country']['iso_code_2']) . '</CountryCode>
         <Postalcode>' . static::xmlString($deliveryAddress['postcode']) . '</Postalcode>
         <!-- City>' . static::xmlString($deliveryAddress['city']) . '</City -->
      </To>
   </GetQuote>
</p:DCTRequest>';

        $opts = array('http' =>
            array(
                'method' => 'POST',
                //'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'header' => 'Content-Type: text/xml',
                'content' => $xml,
            )
        );
        $context = stream_context_create($opts);

        if (MODULE_SHIPPING_DHL_MODE == 'Production') {
            $requestURL = 'https://xmlpi-ea.dhl.com/XMLShippingServlet?isUTF8Support=true';
        } else {
            $requestURL = 'https://xmlpitest-ea.dhl.com/XMLShippingServlet?isUTF8Support=true';
        }
        $result = file_get_contents($requestURL, false, $context);


        $xmlObj = simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA);

        $dhlArray = json_decode(json_encode($xmlObj), true);
        $QuoteResponse = $dhlArray['GetQuoteResponse'];

        if (isset($QuoteResponse['BkgDetails']) && isset($QuoteResponse['BkgDetails']['QtdShp'])) {
            $shipOP = $QuoteResponse['BkgDetails']['QtdShp'];

            if (!\yii\helpers\ArrayHelper::isIndexed($shipOP)) $shipOP = [$shipOP];
            $methods = [];
            foreach ($shipOP as $shipMethod) {
                $methods[] = [
                    'id' => $shipMethod['GlobalProductCode'],
                    'title' => $shipMethod['ProductShortName'],
                    'cost' => $shipMethod['ShippingCharge'],
                ];
            }
            return $methods;
        } elseif (isset($QuoteResponse['Note']['Condition'])) {
            $RequestNotes = \yii\helpers\ArrayHelper::isIndexed($QuoteResponse['Note']['Condition']) ? $QuoteResponse['Note']['Condition'] : [$QuoteResponse['Note']['Condition']];
            foreach ($RequestNotes as $RequestNote) {
                //"410301" "Products not available between this origin and destination (network segment)"
                if ($RequestNote["ConditionCode"] == '410301' && !empty($deliveryAddress['postcode'])) {
                    return [
                        'error' => MODULE_SHIPPING_DHL_TEXT_ERROR_NOT_AVAILABLE,
                    ];
                }
                return false;
                return [
                    'error' => $RequestNote['ConditionData'],
                ];
                break;
            }
        }
        return false;
    }

    protected static function xmlString($value)
    {
        return htmlspecialchars($value,ENT_XML1,'UTF-8');
    }

    protected static function productCodesList()
    {
        return [
            'A' => 'AUTO REVERSALS [N/A]',
            '2' => 'B2C [BTC]',
            '3' => 'B2C [B2C]',
            'B' => 'BREAKBULK EXPRESS [BBX]',
            'Z' => 'Destination Charges [N/A]',
            'G' => 'DOMESTIC ECONOMY SELECT [DES]',
            'N' => 'DOMESTIC EXPRESS [DOM]',
            'O' => 'DOMESTIC EXPRESS 10:30 [DOL]',
            '1' => 'DOMESTIC EXPRESS 12:00 [DOT]',
            'I' => 'DOMESTIC EXPRESS 9:00 [DOK]',
            'H' => 'ECONOMY SELECT [ESI]',
            'W' => 'ECONOMY SELECT [ESU]',
            '9' => 'EUROPACK [EPA]',
            'V' => 'EUROPACK [EPP]',
            'L' => 'EXPRESS 10:30 [TDL]',
            'M' => 'EXPRESS 10:30 [TDM]',
            'T' => 'EXPRESS 12:00 [TDT]',
            'Y' => 'EXPRESS 12:00 [TDY]',
            'E' => 'EXPRESS 9:00 [TDE]',
            'K' => 'EXPRESS 9:00 [TDK]',
            '7' => 'EXPRESS EASY [XED]',
            '8' => 'EXPRESS EASY [XEP]',
            'X' => 'EXPRESS ENVELOPE [XPD]',
            'D' => 'EXPRESS WORLDWIDE [DOX]',
            'P' => 'EXPRESS WORLDWIDE [WPX]',
            'U' => 'EXPRESS WORLDWIDE [ECX]',
            'F' => 'FREIGHT WORLDWIDE [FRT]',
            'R' => 'GLOBALMAIL BUSINESS [GMB]',
            '4' => 'JETLINE [NFO]',
            'J' => 'JUMBO BOX [JBX]',
            '0' => 'LOGISTICS SERVICES [LOG]',
            'C' => 'MEDICAL EXPRESS [CMX]',
            'Q' => 'MEDICAL EXPRESS [WMX]',
            'S' => 'SAME DAY [SDX]',
            '5' => 'SPRINTLINE [SPL]',
        ];
    }

    public function possibleMethods()
    {
        return static::productCodesList();
    }

    protected function get_install_keys($platform_id)
    {
        $keys = parent::get_install_keys($platform_id);
        $platformConfig = \Yii::$app->get('platform')->getConfig($platform_id);
        /**
         * @var platform_config $platformConfig
         */
        $storeAddress = $platformConfig->getPlatformAddress();
        foreach (array_keys($keys) as $KEY) {
            if ($KEY == 'MODULE_SHIPPING_DHL_POSTAL') {
                $keys[$KEY]['value'] = $storeAddress['postcode'];
            } elseif ($KEY == 'MODULE_SHIPPING_DHL_CITY') {
                $keys[$KEY]['value'] = $storeAddress['city'];
            } elseif ($KEY == 'MODULE_SHIPPING_DHL_COUNTRY') {
                $keys[$KEY]['value'] = $storeAddress['country_id'];
            }
        }
        return $keys;
    }

    public function configure_keys()
    {
        return [
            'MODULE_SHIPPING_DHL_STATUS' => [
                'title' => 'Enable Shipping',
                'value' => 'True',
                'description' => 'Do you want to offer this shipping?',
                'sort_order' => '0',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
            ],

            'MODULE_SHIPPING_DHL_MODE' => [
                'title' => 'Web Service Mode',
                'value' => 'Production',
                'description' => 'Web Service Mode',
                'sort_order' => '2',
                'set_function' => 'tep_cfg_select_option(array(\'Test\', \'Production\'), ',
            ],

            'MODULE_SHIPPING_DHL_SERVICE_SITE_ID' => [
                'title' => 'DHL Site Id',
                'value' => '',
                'description' => 'Enter DHL Site Id',
                'sort_order' => '2',
            ],
            'MODULE_SHIPPING_DHL_SERVICE_PASSWORD' => [
                'title' => 'DHL Password',
                'value' => '',
                'description' => 'Enter DHL Password',
                'sort_order' => '3',
            ],
            'MODULE_SHIPPING_DHL_CITY' => [
                'title' => 'City name',
                'value' => '',
                'description' => 'Enter the city name for the ship-from street address, required',
                'sort_order' => '9',
            ],
            'MODULE_SHIPPING_DHL_POSTAL' => [
                'title' => 'Postal code',
                'value' => '',
                'description' => 'Enter the postal code for the ship-from street address, required',
                'sort_order' => '11',
            ],
            'MODULE_SHIPPING_DHL_COUNTRY' => [
                'title' => 'Country',
                'value' => '',
                'description' => 'SELECT the postal country FOR the ship-FROM address, required',
                'sort_order' => '11',
                'use_function' => '\\backend\\models\\Configuration::tep_get_country_name',
                'set_function' => 'tep_cfg_pull_down_country_list(',
            ],
            'MODULE_SHIPPING_DHL_WEIGHT_UOM' => [
                'title' => 'Weight Units',
                'value' => 'KG',
                'description' => 'Weight Units:',
                'sort_order' => '6',
                'set_function' => 'tep_cfg_select_option(array(\'LB\', \'KG\'), ',
            ],
            'MODULE_SHIPPING_DHL_NETWORK' => [
                'title' => 'DHL Network type',
                'value' => 'AL – Both Time and Day Definite',
                'description' => 'DHL Network type',
                'sort_order' => '15',
                'set_function' => 'tep_cfg_select_option(array(\'AL – Both Time and Day Definite\', \'DD - Day Definite\',\'TD - Time Definite\'), ',
            ],
            'MODULE_SHIPPING_DHL_ALLOWED_PRODUCT_CODES' => [
                'title' => 'Allowed product codes',
                'value' => implode(',', array_keys(static::productCodesList())),
                'description' => '',
                'sort_order' => '15',
                'set_function' => 'cfgAllowedProductCodes(',
            ],
            'MODULE_SHIPPING_DHL_DOM_HANDLING_FEE' => [
                'title' => 'Domestic Handling Fee',
                'value' => '',
                'description' => 'Add a domestic handling fee or leave blank (example: 15 or 15%)',
                'sort_order' => '20',
            ],
            'MODULE_SHIPPING_DHL_INT_HANDLING_FEE' => [
                'title' => 'International Handling Fee',
                'value' => '',
                'description' => 'Add an international handling fee or leave blank (example: 15 or 15%)',
                'sort_order' => '21',
            ],
            'MODULE_SHIPPING_DHL_INSURE' => [
                'title' => 'Insurance?',
                'value' => '-1',
                'description' => 'Insure packages over what amount? (set to -1 to disable)',
                'sort_order' => '22',
            ],
            'MODULE_SHIPPING_DHL_TAX_CLASS' => [
                'title' => 'Shipping Tax Class',
                'value' => '0',
                'description' => 'Use the following tax class on the shipping fee.',
                'sort_order' => '0',
                'use_function' => '\\common\\helpers\\Tax::get_tax_class_title',
                'set_function' => 'tep_cfg_pull_down_tax_classes(',
            ],
            'MODULE_SHIPPING_DHL_ZONE' => [
                'title' => 'Shipping Shipping Zone',
                'value' => '0',
                'description' => 'If a zone is selected, only enable this shipping method for that zone.',
                'sort_order' => '0',
                'use_function' => '\\common\\helpers\\Zones::get_zone_class_title',
                'set_function' => 'tep_cfg_pull_down_zone_classes(',
            ],
            'MODULE_SHIPPING_DHL_SORT_ORDER' => [
                'title' => 'Shipping Sort Order',
                'value' => '0',
                'description' => 'Sort order of display.',
                'sort_order' => '0',
            ],
        ];
    }

    public function describe_status_key()
    {
        return new ModuleStatus('MODULE_SHIPPING_DHL_STATUS', 'True', 'False');
    }

    public function describe_sort_key()
    {
        return new ModuleSortOrder('MODULE_SHIPPING_DHL_SORT_ORDER');
    }

    public static function cfgAllowedProductCodes($key_value, $key)
    {
        $selected_values = preg_split('/,\s?/',$key_value,-1,PREG_SPLIT_NO_EMPTY);
        $variants = static::productCodesList();

        $string = '';
        $name = (($key) ? 'configuration[' . $key . ']' : 'configuration_value');

        foreach ($variants as $value => $valueText) {
            $string .= '<div><label>'.\common\helpers\Html::checkbox($name.'[]', in_array($value, $selected_values), ['value'=>$value, 'class' => 'uniform']). ' '.$valueText.'</label></div>';
        }
        return $string;
    }


}