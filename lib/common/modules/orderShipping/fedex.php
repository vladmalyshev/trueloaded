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
use FedEx\RateService\Request;
use FedEx\RateService\ComplexType;
use FedEx\RateService\SimpleType;

class fedex extends ModuleShipping
{
    var $code, $title, $description, $icon, $enabled;

    protected $defaultTranslationArray = [
        'MODULE_SHIPPING_FEDEX_TEXT_TITLE' => 'FedEx',
        'MODULE_SHIPPING_FEDEX_TEXT_DESCRIPTION' => 'FedEx WebServices',
        'MODULE_SHIPPING_FEDEX_TEXT_ERROR_NO_RATES' => '<strong>Please enter a ZIP Code to obtain your shipping quote.</strong><br />Or possibly:<br />If no rate is shown, the heavy weight of the item(s) in your Shopping Cart suggests a <strong>Request for Freight Quote</strong>, rather than FedEx Ground service, is recommended.',
        'MODULE_SHIPPING_FEDEX_TEXT_ERROR_PO_BOX' => '<strong>Federal Express cannot ship to Post Office Boxes.<strong><br>Use the Change Address button above to use a FedEx accepted street address.',
    ];

    private $shipFromAddress = [];
    private $types;
    private static $requestResult = [];

    function __construct()
    {
        parent::__construct();

        $this->code = 'fedex';
        $this->title = MODULE_SHIPPING_FEDEX_TEXT_TITLE;
        $this->description = MODULE_SHIPPING_FEDEX_TEXT_DESCRIPTION;
        if (!defined('MODULE_SHIPPING_FEDEX_STATUS')) {
            $this->enabled = false;
            return false;
        }
        $this->sort_order = MODULE_SHIPPING_FEDEX_SORT_ORDER;
        //$this->icon = DIR_WS_ICONS . 'shipping_dp.gif';
        $this->tax_class = MODULE_SHIPPING_FEDEX_TAX_CLASS;
        $this->enabled = ((MODULE_SHIPPING_FEDEX_STATUS == 'True') ? true : false);

        if (($this->enabled == true) && ((int)MODULE_SHIPPING_FEDEX_ZONE > 0)) {
            $check_flag = false;
            $check_query = tep_db_query("SELECT zone_id FROM " . TABLE_ZONES_TO_GEO_ZONES . " WHERE geo_zone_id = '" . MODULE_SHIPPING_FEDEX_ZONE . "' AND zone_country_id = '" . $this->delivery['country']['id'] . "' ORDER BY zone_id");
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
        $country_info = \common\helpers\Country::get_country_info_by_id(MODULE_SHIPPING_FEDEX_COUNTRY);
        $this->shipFromAddress = [
            'StreetLines' => [MODULE_SHIPPING_FEDEX_ADDRESS_1,MODULE_SHIPPING_FEDEX_ADDRESS_2],
            'City' => MODULE_SHIPPING_FEDEX_CITY,
            'StateOrProvinceCode' => MODULE_SHIPPING_FEDEX_STATE,
            'PostalCode' => MODULE_SHIPPING_FEDEX_POSTAL,
            'CountryCode' => isset($country_info['countries_iso_code_2'])?$country_info['countries_iso_code_2']:'',
        ];

        $this->types = array();
        if (MODULE_SHIPPING_FEDEX_INTERNATIONAL_PRIORITY == 'true') {
            $this->types['INTERNATIONAL_PRIORITY'] = array('icon' => '', 'handling_fee' => MODULE_SHIPPING_FEDEX_INT_EXPRESS_HANDLING_FEE);
            $this->types['EUROPE_FIRST_INTERNATIONAL_PRIORITY'] = array('icon' => '', 'handling_fee' => MODULE_SHIPPING_FEDEX_INT_EXPRESS_HANDLING_FEE);
        }
        if (MODULE_SHIPPING_FEDEX_INTERNATIONAL_ECONOMY == 'true') {
            $this->types['INTERNATIONAL_ECONOMY'] = array('icon' => '', 'handling_fee' => MODULE_SHIPPING_FEDEX_INT_EXPRESS_HANDLING_FEE);
        }
        if (MODULE_SHIPPING_FEDEX_DOMESTIC_NEXT_DAY == 'true') {
            $this->types['FEDEX_NEXT_DAY_END_OF_DAY'] = array('icon' => '', 'handling_fee' => MODULE_SHIPPING_FEDEX_DOMESTIC_HANDLING_FEE);
        }
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
        $fedexCurrency = $currency;
        if ( $fedexCurrency=='GBP' ) $fedexCurrency = 'UKL';

        $response = $this->fetchRates($this->delivery, $shipping_weight, $order_total, $fedexCurrency);

        if ($response->HighestSeverity != 'FAILURE' && $response->HighestSeverity != 'ERROR' && (is_array($response->RateReplyDetails) || is_object($response->RateReplyDetails))) {
            if (is_object($response->RateReplyDetails)) {
                $response->RateReplyDetails = get_object_vars($response->RateReplyDetails);
            }

            $show_box_weight = '';
            //$show_box_weight = ' (Total weight: ' . number_format($shipping_weight, 2) . ' ' . strtolower(MODULE_SHIPPING_FEDEX_WEIGHT) . 's.)';
            $this->quotes = array(
                'id' => $this->code,
                'module' => $this->title . $show_box_weight
            );

            $methods = array();
            $RateReplyDetails = \yii\helpers\ArrayHelper::isIndexed($response->RateReplyDetails)?$response->RateReplyDetails:[$response->RateReplyDetails];

            foreach ($RateReplyDetails as $rateReply) {
                if (array_key_exists($rateReply->ServiceType, $this->types)
                    && ($method == '' || str_replace('_', '', $rateReply->ServiceType) == $method)) {
                    $cost = false;

                    $oneRateDetail = count($rateReply->RatedShipmentDetails)==1;
                    foreach ($rateReply->RatedShipmentDetails as $ShipmentRateDetail) {
                        if ( !$oneRateDetail ) {
                            if (MODULE_SHIPPING_FEDEX_RATES == 'LIST' && !in_array($ShipmentRateDetail->ShipmentRateDetail->RateType, ['PAYOR_LIST_SHIPMENT'])) continue;
                            if (MODULE_SHIPPING_FEDEX_RATES == 'ACCOUNT' && !in_array($ShipmentRateDetail->ShipmentRateDetail->RateType, ['PAYOR_ACCOUNT_SHIPMENT'])) continue;
                        }
                        $currencyRate = 1;
                        if ( is_numeric(MODULE_SHIPPING_FEDEX_CURRENCY) && floatval(MODULE_SHIPPING_FEDEX_CURRENCY)!=0 ) $currencyRate = floatval(MODULE_SHIPPING_FEDEX_CURRENCY);
                        /*
                        $responseCurrency = $ShipmentRateDetail->ShipmentRateDetail->TotalNetCharge->Currency;
                        if ( $fedexCurrency!=$responseCurrency ){
                            $currencyRate = $ShipmentRateDetail->ShipmentRateDetail->CurrencyExchangeRate->Rate;
                            //$currencyRate = 1;
                        }
                        */
                        $cost = ($ShipmentRateDetail->ShipmentRateDetail->TotalNetCharge->Amount) / $currencyRate;
                        $cost = (float)round(preg_replace('/[^0-9.]/', '', $cost), 2);
                    }
                    if ( $cost===false ) continue;

                    $transitTime = ' (Transit time unavailable, max. 6 working days)'; // 9.4.6
                    $transitTime = ' ';
                    if (isset($rateReply->DeliveryTimestamp)) {
                        //$transitTime = ' (Estimated: ' . date('d-M-Y', strtotime($rateReply->DeliveryTimestamp)) . ') ';
                        $difference = time() - strtotime($rateReply->DeliveryTimestamp);
                        $transitTime= floor($difference / 86400);
                        $transitTime = ' (Estimated transit: ' . $transitTime . ' days) ';
                    }

                    if ( strpos($this->types[$rateReply->ServiceType]['handling_fee'], '%')!==false ){
                        $additional_fee = ($cost * (float)$this->types[$rateReply->ServiceType]['handling_fee'] / 100);
                    }else{
                        $additional_fee = (float)$this->types[$rateReply->ServiceType]['handling_fee'];
                    }

                    $methods[] = [
                        'id' => str_replace('_', '', $rateReply->ServiceType),
                        'title' => ucwords(strtolower(str_replace('_', ' ', $rateReply->ServiceType))) . $transitTime,
                        'cost' => $cost + $additional_fee,
                    ];
                }
            }

            // Limit to cheapest
            // begin sort order control - low to high is set
            usort($methods, array($this, 'cmp'));
            // end sort order control - comment out section to apply high to low rate sort order

            $this->quotes['methods'] = $methods;

            if ($this->tax_class > 0) {
                $this->quotes['tax'] = \common\helpers\Tax::get_tax_rate($this->tax_class, $this->delivery['country']['id'], $this->delivery['zone_id']);
            }
        } else {
            $message = MODULE_SHIPPING_FEDEX_TEXT_ERROR_NO_RATES;
            foreach ($response->Notifications as $notification) {
                if (is_array($response->Notifications)) {
                    if ( $notification->Severity=='NOTE' ) continue;
                    $message .= $notification->Severity;
                    $message .= ': ';
                    $message .= $notification->Message . '<br />';
                } else {
                    $message .= $notification->Message . '<br />';
                }
            }
            $this->quotes = array(
                'id' => $this->code,
                'module' => $this->title,
                'error' => $message,
            );
        }

        if (preg_match('/^P\.?\s?O\.?\s+?BOX/i', $this->delivery['street_address']) || (preg_match('/^P\.?\s?O\.?\s+?BOX/i', $this->delivery['suburb']))) {
            $this->quotes = array(
                'id' => $this->code,
                'module' => $this->title,
                'error' => MODULE_SHIPPING_FEDEX_TEXT_ERROR_PO_BOX,
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

    protected function fetchRates($deliveryAddress, $shipping_weight, $total, $fedexCurrency)
    {
        $call_key = md5(json_encode($deliveryAddress)).'|'.strval($shipping_weight).'|'.strval($total).'|'.$fedexCurrency;
        if (isset(self::$requestResult[$call_key])){
            return self::$requestResult[$call_key];
        }

        $rateRequest = new ComplexType\RateRequest();
        //authentication & client details
        $rateRequest->WebAuthenticationDetail->UserCredential->Key = MODULE_SHIPPING_FEDEX_KEY;
        $rateRequest->WebAuthenticationDetail->UserCredential->Password = MODULE_SHIPPING_FEDEX_PWD;
        $rateRequest->ClientDetail->AccountNumber = MODULE_SHIPPING_FEDEX_ACT_NUM;
        $rateRequest->ClientDetail->MeterNumber = MODULE_SHIPPING_FEDEX_METER_NUM;
        $rateRequest->TransactionDetail->CustomerTransactionId = 'Trueloaded V3 rate service request';
        //version
        $rateRequest->Version->ServiceId = 'crs';
        $rateRequest->Version->Major = 24;
        $rateRequest->Version->Minor = 0;
        $rateRequest->Version->Intermediate = 0;

        $rateRequest->ReturnTransitAndCommit = true;

        $shipperAddress = new ComplexType\Address($this->shipFromAddress);
        $rateRequest->RequestedShipment->Shipper->setAddress($shipperAddress);

        $recipientAddress = new ComplexType\Address([
            'StreetLines' => [$deliveryAddress['street_address'], $deliveryAddress['suburb']],
            'City' => $deliveryAddress['city'],
            'StateOrProvinceCode' => (in_array($deliveryAddress['country']['iso_code_2'],['US','CA'])?\common\helpers\Zones::get_zone_code($deliveryAddress['country']['id'], $deliveryAddress['zone_id'], ''):''),
            'PostalCode' => $deliveryAddress['postcode'],
            'CountryCode' => $deliveryAddress['country']['iso_code_2'],
            'Residential' => ($deliveryAddress['company'] != '' ? false : true) // Sets commercial vs residential (Home)
        ]);
        if (!in_array($deliveryAddress['country']['iso_code_2'],['US','CA'])){
            $recipientAddress->setStateOrProvinceCode(null);
        }
        if (empty($recipientAddress->StreetLines[1])) unset($recipientAddress->StreetLines[1]);

        $rateRequest->RequestedShipment->Recipient->setAddress($recipientAddress);

        $rateRequest->RequestedShipment->ShippingChargesPayment->PaymentType = SimpleType\PaymentType::_SENDER;
        $rateRequest->RequestedShipment->ShippingChargesPayment->Payor->AccountNumber = MODULE_SHIPPING_FEDEX_ACT_NUM;
        //$rateRequest->RequestedShipment->ShippingChargesPayment->Payor->CountryCode = 'US';

        $rateRequest->RequestedShipment->PreferredCurrency = $fedexCurrency;
        $rateRequest->RequestedShipment->ShipTimestamp = date('c', strtotime("+" . MODULE_SHIPPING_FEDEX_SHIPPING_DAYS_DELAY . " days"));
        $rateRequest->RequestedShipment->setDropoffType(MODULE_SHIPPING_FEDEX_DROPOFF);

        $rateRequest->RequestedShipment->RateRequestTypes = [SimpleType\RateRequestType::_LIST];

        $rateRequest->RequestedShipment->PackagingType = 'YOUR_PACKAGING';
        if ( MODULE_SHIPPING_FEDEX_INSURE!='' && MODULE_SHIPPING_FEDEX_INSURE >= 0 && $total >= MODULE_SHIPPING_FEDEX_INSURE ) {
            $rateRequest->RequestedShipment->TotalInsuredValue = new ComplexType\Money([
                'Currency' => $fedexCurrency,
                'Amount' => sprintf("%01.2f", ceil($total)),
            ]);
        }

        $rateRequest->RequestedShipment->RequestedPackageLineItems = [];

        $packageItem = new ComplexType\RequestedPackageLineItem([
            'SequenceNumber' => 1,
            'GroupNumber' => 1,
            'GroupPackageCount' => 1,
            'Weight' => new ComplexType\Weight(['Value' => $shipping_weight, 'Units' => MODULE_SHIPPING_FEDEX_WEIGHT]),
        ]);
        if (MODULE_SHIPPING_FEDEX_SIGNATURE_OPTION!='' && MODULE_SHIPPING_FEDEX_SIGNATURE_OPTION >= 0 && $total >= MODULE_SHIPPING_FEDEX_SIGNATURE_OPTION) {
            $packageItem->SpecialServicesRequested->SignatureOptionDetail = new ComplexType\SignatureOptionDetail([
                'OptionType' => MODULE_SHIPPING_FEDEX_SIGNATURE_OPTION_TYPE,
            ]);
        }
        $rateRequest->RequestedShipment->RequestedPackageLineItems[] = $packageItem;

        $rateRequest->RequestedShipment->PackageCount = count($rateRequest->RequestedShipment->RequestedPackageLineItems);

        $rateServiceRequest = new Request();
        if (MODULE_SHIPPING_FEDEX_MODE=='Production') {
            $rateServiceRequest->getSoapClient()->__setLocation(Request::PRODUCTION_URL);
        }else{
            $rateServiceRequest->getSoapClient()->__setLocation(Request::TESTING_URL);
        }

        $rateReply = $rateServiceRequest->getGetRatesReply($rateRequest); // send true as the 2nd argument to return the SoapClient's stdClass response.
        //echo $rateServiceRequest->getSoapClient()->__getLastRequest();
        //echo $rateServiceRequest->getSoapClient()->__getLastResponse();

        self::$requestResult[$call_key] = $rateReply;

        return $rateReply;
    }

    public function configure_keys()
    {
        return [
            'MODULE_SHIPPING_FEDEX_STATUS' => [
                'title' => 'Enable Collect Shipping',
                'value' => 'True',
                'description' => 'Do you want to offer collect shipping?',
                'sort_order' => '0',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
            ],

            'MODULE_SHIPPING_FEDEX_MODE' => [
                'title' => 'Web Service Mode',
                'value' => 'Test',
                'description' => 'Web Service Mode',
                'sort_order' => '2',
                'set_function' => 'tep_cfg_select_option(array(\'Test\', \'Production\'), ',
            ],

            'MODULE_SHIPPING_FEDEX_KEY' => [
                'title' => 'FedEx Web Services Key',
                'value' => '',
                'description' => 'Enter FedEx Web Services Key',
                'sort_order' => '2',
            ],
            'MODULE_SHIPPING_FEDEX_PWD' => [
                'title' => 'FedEx Web Services Password',
                'value' => '',
                'description' => 'Enter FedEx Web Services Password',
                'sort_order' => '3',
            ],
            'MODULE_SHIPPING_FEDEX_ACT_NUM' => [
                'title' => 'FedEx Account Number',
                'value' => '',
                'description' => 'Enter FedEx Account Number',
                'sort_order' => '4',
            ],
            'MODULE_SHIPPING_FEDEX_METER_NUM' => [
                'title' => 'FedEx Meter Number',
                'value' => '',
                'description' => 'Enter FedEx Meter Number',
                'sort_order' => '5',
            ],
            'MODULE_SHIPPING_FEDEX_WEIGHT' => [
                'title' => 'Weight Units',
                'value' => 'LB',
                'description' => 'Weight Units:',
                'sort_order' => '6',
                'set_function' => 'tep_cfg_select_option(array(\'LB\', \'KG\'), ',
            ],
            'MODULE_SHIPPING_FEDEX_ADDRESS_1' => [
                'title' => 'First line of street address',
                'value' => '',
                'description' => 'Enter the first line of your ship-from street address, required',
                'sort_order' => '7',
            ],
            'MODULE_SHIPPING_FEDEX_ADDRESS_2' => [
                'title' => 'Second line of street address',
                'value' => '',
                'description' => 'Enter the second line of your ship-from street address, leave blank if you do not need to specify a second line',
                'sort_order' => '8',
            ],
            'MODULE_SHIPPING_FEDEX_CITY' => [
                'title' => 'City name',
                'value' => '',
                'description' => 'Enter the city name for the ship-from street address, required',
                'sort_order' => '9',
            ],
            'MODULE_SHIPPING_FEDEX_STATE' => [
                'title' => 'State or Province name',
                'value' => '',
                'description' => 'Enter the 2 letter state or province name for the ship-from street address, required for Canada and US',
                'sort_order' => '10',
            ],
            'MODULE_SHIPPING_FEDEX_POSTAL' => [
                'title' => 'Postal code',
                'value' => '',
                'description' => 'Enter the postal code for the ship-from street address, required',
                'sort_order' => '11',
            ],
            'MODULE_SHIPPING_FEDEX_COUNTRY' => [
                'title' => 'Country',
                'value' => '',
                'description' => 'Select the postal country for the ship-from address, required',
                'sort_order' => '11',
                'use_function' => '\\backend\\models\\Configuration::tep_get_country_name',
                'set_function' => 'tep_cfg_pull_down_country_list(',
            ],
            'MODULE_SHIPPING_FEDEX_PHONE' => [
                'title' => 'Phone number',
                'value' => '',
                'description' => 'Enter a contact phone number for your company, required',
                'sort_order' => '12',
            ],

            'MODULE_SHIPPING_FEDEX_SHIPPING_DAYS_DELAY' => [
                'title' => 'Shipping Delay',
                'value' => '1',
                'description' => 'How many days from when an order is placed to when you ship it (Decimals are allowed). Arrival date estimations are based on this value.',
                'sort_order' => '13',
            ],
            'MODULE_SHIPPING_FEDEX_CURRENCY' => [
                'title' => 'Currency Exchange Rates Conversion',
                'value' => '1',
                'description' => 'Set your currency exchange rate here if needed. Note that this data must be a number (integer like 32 or with a floating point like 32.48) and will have to be updated by you regularly to match exchange fluctuations.',
                'sort_order' => '14',
            ],
            'MODULE_SHIPPING_FEDEX_DROPOFF' => [
                'title' => 'Drop off type',
                'value' => 'REGULAR_PICKUP',
                'description' => 'Dropoff type?',
                'sort_order' => '15',
                'set_function' => 'tep_cfg_select_option(array(\'REGULAR_PICKUP\',\'REQUEST_COURIER\',\'DROP_BOX\',\'BUSINESS_SERVICE_CENTER\',\'STATION\'), ',
            ],

            'MODULE_SHIPPING_FEDEX_DOMESTIC_NEXT_DAY' => [
                'title' => 'Enable Domestic NBD',
                'value' => 'true',
                'description' => 'Enable FedEx Domestic Next Business Day',
                'sort_order' => '16',
                'set_function' => 'tep_cfg_select_option(array(\'true\', \'false\'), ',
            ],
            'MODULE_SHIPPING_FEDEX_INTERNATIONAL_PRIORITY' => [
                'title' => 'Enable International Priority',
                'value' => 'true',
                'description' => 'Enable FedEx Express International Priority',
                'sort_order' => '17',
                'set_function' => 'tep_cfg_select_option(array(\'true\', \'false\'), ',
            ],
            'MODULE_SHIPPING_FEDEX_INTERNATIONAL_ECONOMY' => [
                'title' => 'Enable International Economy',
                'value' => 'true',
                'description' => 'Enable FedEx Express International Economy',
                'sort_order' => '18',
                'set_function' => 'tep_cfg_select_option(array(\'true\', \'false\'), ',
            ],

            'MODULE_SHIPPING_FEDEX_DOMESTIC_HANDLING_FEE' => [
                'title' => 'Domestic NBD Handling Fee',
                'value' => '',
                'description' => 'Add a domestic handling fee or leave blank (example: 15 or 15%)',
                'sort_order' => '20',
            ],

            'MODULE_SHIPPING_FEDEX_INT_EXPRESS_HANDLING_FEE' => [
                'title' => 'International Express Handling Fee',
                'value' => '',
                'description' => 'Add an international handling fee or leave blank (example: 15 or 15%)',
                'sort_order' => '21',
            ],

            'MODULE_SHIPPING_FEDEX_RATES' => [
                'title' => 'FedEx Rates',
                'value' => 'LIST',
                'description' => 'FedEx Rates',
                'sort_order' => '22',
                'set_function' => 'tep_cfg_select_option(array(\'LIST\',\'ACCOUNT\'), ',
            ],
            'MODULE_SHIPPING_FEDEX_SIGNATURE_OPTION' => [
                'title' => 'Signature Option',
                'value' => '-1',
                'description' => 'Require a signature on order subtotal greater than or equal to (set to -1 to disable):',
                'sort_order' => '23',
            ],
            'MODULE_SHIPPING_FEDEX_SIGNATURE_OPTION_TYPE' => [
                'title' => 'Signature Option Type',
                'value' => 'SERVICE_DEFAULT',
                'description' => 'Select Signature Option Type',
                'sort_order' => '23',
                'set_function' => 'tep_cfg_select_option(array(\'ADULT\', \'DIRECT\', \'INDIRECT\', \'NO_SIGNATURE_REQUIRED\', \'SERVICE_DEFAULT\'), ',
            ],
            'MODULE_SHIPPING_FEDEX_INSURE' => [
                'title' => 'Insurance?',
                'value' => '-1',
                'description' => 'Insure packages over what amount? (set to -1 to disable)',
                'sort_order' => '22',
            ],

            'MODULE_SHIPPING_FEDEX_TAX_CLASS' => [
                'title' => 'Shipping Tax Class',
                'value' => '0',
                'description' => 'Use the following tax class on the shipping fee.',
                'sort_order' => '0',
                'use_function' => '\\common\\helpers\\Tax::get_tax_class_title',
                'set_function' => 'tep_cfg_pull_down_tax_classes(',
            ],
            'MODULE_SHIPPING_FEDEX_ZONE' => [
                'title' => 'Shipping Shipping Zone',
                'value' => '0',
                'description' => 'If a zone is selected, only enable this shipping method for that zone.',
                'sort_order' => '0',
                'use_function' => '\\common\\helpers\\Zones::get_zone_class_title',
                'set_function' => 'tep_cfg_pull_down_zone_classes(',
            ],
            'MODULE_SHIPPING_FEDEX_SORT_ORDER' => [
                'title' => 'Shipping Sort Order',
                'value' => '0',
                'description' => 'Sort order of display.',
                'sort_order' => '0',
            ],
        ];
    }
    public function describe_status_key()
    {
        return new ModuleStatus('MODULE_SHIPPING_FEDEX_STATUS','True','False');
    }

    public function describe_sort_key()
    {
        return new ModuleSortOrder('MODULE_SHIPPING_FEDEX_SORT_ORDER');
    }
   
}