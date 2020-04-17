<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\modules\label;

use Yii;
use common\classes\modules\ModuleLabel;
use common\classes\modules\ModuleStatus;
use common\classes\modules\ModuleSortOrder;
use FedEx\ShipService;
use FedEx\ShipService\ComplexType;
use FedEx\ShipService\SimpleType;

class fedex extends ModuleLabel
{
    public $title;
    public $description;
    public $code = 'fedex';

    private $last_errors = [];

    public function __construct()
    {
        parent::__construct();
        $this->title = 'Fedex';
        $this->description = 'Fedex';
    }

    public function configure_keys() {
        return array(
            'MODULE_LABEL_FEDEX_STATUS' => [
                'title' => 'Enable Label module?',
                'value' => 'True',
                'description' => 'Do you want to offer Fedex labels?',
                'sort_order' => '0',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
            ],
            'MODULE_LABEL_FEDEX_MODE' => [
                'title' => 'Web Service Mode',
                'value' => 'Test',
                'description' => 'Web Service Mode',
                'sort_order' => '2',
                'set_function' => 'tep_cfg_select_option(array(\'Test\', \'Production\'), ',
            ],

            'MODULE_LABEL_FEDEX_KEY' => [
                'title' => 'FedEx Web Services Key',
                'value' => '',
                'description' => 'Enter FedEx Web Services Key',
                'sort_order' => '2',
            ],
            'MODULE_LABEL_FEDEX_PWD' => [
                'title' => 'FedEx Web Services Password',
                'value' => '',
                'description' => 'Enter FedEx Web Services Password',
                'sort_order' => '3',
            ],
            'MODULE_LABEL_FEDEX_ACT_NUM' => [
                'title' => 'FedEx Account Number',
                'value' => '',
                'description' => 'Enter FedEx Account Number',
                'sort_order' => '4',
            ],
            'MODULE_LABEL_FEDEX_METER_NUM' => [
                'title' => 'FedEx Meter Number',
                'value' => '',
                'description' => 'Enter FedEx Meter Number',
                'sort_order' => '5',
            ],

            'MODULE_LABEL_FEDEX_ADDRESS_1' => [
                'title' => 'First line of street address',
                'value' => '',
                'description' => 'Enter the first line of your ship-from street address, required',
                'sort_order' => '7',
            ],
            'MODULE_LABEL_FEDEX_ADDRESS_2' => [
                'title' => 'Second line of street address',
                'value' => '',
                'description' => 'Enter the second line of your ship-from street address, leave blank if you do not need to specify a second line',
                'sort_order' => '8',
            ],
            'MODULE_LABEL_FEDEX_CITY' => [
                'title' => 'City name',
                'value' => '',
                'description' => 'Enter the city name for the ship-from street address, required',
                'sort_order' => '9',
            ],
            'MODULE_LABEL_FEDEX_STATE' => [
                'title' => 'State or Province name',
                'value' => '',
                'description' => 'Enter the 2 letter state or province name for the ship-from street address, required for Canada and US',
                'sort_order' => '10',
            ],
            'MODULE_LABEL_FEDEX_POSTAL' => [
                'title' => 'Postal code',
                'value' => '',
                'description' => 'Enter the postal code for the ship-from street address, required',
                'sort_order' => '11',
            ],
            'MODULE_LABEL_FEDEX_COUNTRY' => [
                'title' => 'Country',
                'value' => '',
                'description' => 'Select the postal country for the ship-from address, required',
                'sort_order' => '11',
                'use_function' => '\\backend\\models\\Configuration::tep_get_country_name',
                'set_function' => 'tep_cfg_pull_down_country_list(',
            ],

            'MODULE_LABEL_FEDEX_WEIGHT_UOM' => [
                'title' => 'Weight Units',
                'value' => 'LB',
                'description' => 'Weight Units:',
                'sort_order' => '6',
                'set_function' => 'tep_cfg_select_option(array(\'LB\', \'KG\'), ',
            ],

            'MODULE_LABEL_FEDEX_LABEL_IMAGE_TYPE' => [
                'title' => 'Label Image Type',
                'value' => 'PDF',
                'description' => '',
                'sort_order' => '5',
                'set_function' => 'tep_cfg_select_option(array(\'PDF\', \'EPL2\', \'PNG\', \'ZPL2\'), ',
            ],
            'MODULE_LABEL_FEDEX_LABEL_STOCK_TYPE' => [
                'title' => 'Label Stock Type',
                'value' => 'PAPER_4X6',
                'description' => '',
                'sort_order' => '5',
                'set_function' => 'cfgLabelStockType(',
            ],

            'MODULE_LABEL_FEDEX_SORT_ORDER' => [
                'title' => 'Sort Order',
                'value' => '0',
                'description' => 'Sort order of display.',
                'sort_order' => '10',
            ],
        );
    }

    protected function get_install_keys($platform_id)
    {
        $install_config = parent::get_install_keys($platform_id);
        $config = Yii::$app->get('platform')->config($platform_id);
        $platform_address = $config->getPlatformAddress();
        $defaults = [
            'MODULE_LABEL_FEDEX_ADDRESS_1' => $config->const_value('MODULE_SHIPPING_FEDEX_ADDRESS_1',$platform_address['street_address']),
            'MODULE_LABEL_FEDEX_ADDRESS_2' => $config->const_value('MODULE_SHIPPING_FEDEX_ADDRESS_2',$platform_address['suburb']),
            'MODULE_LABEL_FEDEX_CITY' => $config->const_value('MODULE_SHIPPING_FEDEX_CITY',$platform_address['city']),
            'MODULE_LABEL_FEDEX_POSTAL' => $config->const_value('MODULE_SHIPPING_FEDEX_POSTAL',$platform_address['postcode']),
            'MODULE_LABEL_FEDEX_STATE' => $config->const_value('MODULE_SHIPPING_FEDEX_STATE',$platform_address['state']),
            'MODULE_LABEL_FEDEX_COUNTRY' => $config->const_value('MODULE_SHIPPING_FEDEX_COUNTRY',$platform_address['country_id']),
            'MODULE_LABEL_FEDEX_MODE' => $config->const_value('MODULE_SHIPPING_FEDEX_MODE',$install_config['MODULE_LABEL_FEDEX_MODE']['value']),
            'MODULE_LABEL_FEDEX_KEY' => $config->const_value('MODULE_SHIPPING_FEDEX_KEY',$install_config['MODULE_LABEL_FEDEX_KEY']['value']),
            'MODULE_LABEL_FEDEX_PWD' => $config->const_value('MODULE_SHIPPING_FEDEX_PWD',$install_config['MODULE_LABEL_FEDEX_PWD']['value']),
            'MODULE_LABEL_FEDEX_ACT_NUM' => $config->const_value('MODULE_SHIPPING_FEDEX_ACT_NUM',$install_config['MODULE_LABEL_FEDEX_ACT_NUM']['value']),
            'MODULE_LABEL_FEDEX_METER_NUM' => $config->const_value('MODULE_SHIPPING_FEDEX_METER_NUM',$install_config['MODULE_LABEL_FEDEX_METER_NUM']['value']),
            'MODULE_LABEL_FEDEX_WEIGHT_UOM' => $config->const_value('MODULE_SHIPPING_FEDEX_WEIGHT',$install_config['MODULE_LABEL_FEDEX_WEIGHT_UOM']['value']),
        ];
        foreach ( $defaults as $key=>$val ) {
            if ( isset($install_config[$key]) ) $install_config[$key]['value'] = $val;
        }
        return $install_config;
    }


    public static function cfgLabelStockType($key_value, $key='')
    {
        return \backend\models\Configuration::multiOption('dropdown', array('PAPER_4X6', 'PAPER_4X6.75', 'PAPER_4X8', 'PAPER_4X9', 'PAPER_7X4.75', 'PAPER_8.5X11_BOTTOM_HALF_LABEL', 'PAPER_8.5X11_TOP_HALF_LABEL', 'PAPER_LETTER', 'STOCK_4X6', 'STOCK_4X6.75', 'STOCK_4X6.75_LEADING_DOC_TAB', 'STOCK_4X6.75_TRAILING_DOC_TAB', 'STOCK_4X8', 'STOCK_4X9', 'STOCK_4X9_LEADING_DOC_TAB', 'STOCK_4X9_TRAILING_DOC_TAB'), $key_value, $key);
    }

    public function describe_status_key() {
        return new ModuleStatus('MODULE_LABEL_FEDEX_STATUS', 'True', 'False');
    }

    public function describe_sort_key() {
        return new ModuleSortOrder('MODULE_LABEL_FEDEX_SORT_ORDER');
    }

    public function possibleMethods()
    {
        return [
            'EUROPE_FIRST_INTERNATIONAL_PRIORITY' => 'Europe First International Priority',
            'FEDEX_1_DAY_FREIGHT' => 'FedEx 1 Day Freight',
            'FEDEX_2_DAY' => 'FedEx 2 Day',
            'FEDEX_2_DAY_AM' => 'FedEx 2 Day AM',
            'FEDEX_2_DAY_FREIGHT' => 'FedEx 2 Day Freight',
            'FEDEX_3_DAY_FREIGHT' => 'FedEx 3 Day Freight',
            'FEDEX_DISTANCE_DEFERRED' => 'FedEx Distance Deferred',
            'FEDEX_EXPRESS_SAVER' => 'FedEx Express Saver',
            'FEDEX_FIRST_FREIGHT' => 'FedEx First Freight',
            'FEDEX_FREIGHT_ECONOMY' => 'FedEx Freight Economy',
            'FEDEX_FREIGHT_PRIORITY' => 'FedEx Freight Priority',
            'FEDEX_GROUND' => 'FedEx Ground',
            'FEDEX_NEXT_DAY_AFTERNOON' => 'FedEx Next Day Afternoon',
            'FEDEX_NEXT_DAY_EARLY_MORNING' => 'FedEx Next Day Early Morning',
            'FEDEX_NEXT_DAY_END_OF_DAY' => 'FedEx Next Day End Of Day',
            'FEDEX_NEXT_DAY_FREIGHT' => 'FedEx Next Day Freight',
            'FEDEX_NEXT_DAY_MID_MORNING' => 'FedEx Next Day Mid Morning',
            'FIRST_OVERNIGHT' => 'First Overnight',
            'GROUND_HOME_DELIVERY' => 'Ground Home Delivery',
            'INTERNATIONAL_ECONOMY' => 'International Economy',
            'INTERNATIONAL_ECONOMY_FREIGHT' => 'International Economy Freight',
            'INTERNATIONAL_FIRST' => 'International First',
            'INTERNATIONAL_PRIORITY' => 'International Priority',
            'INTERNATIONAL_PRIORITY_EXPRESS' => 'International Priority Express',
            'INTERNATIONAL_PRIORITY_FREIGHT' => 'International Priority Freight',
            'PRIORITY_OVERNIGHT' => 'Priority Overnight',
            'SAME_DAY' => 'Same Day',
            'SAME_DAY_CITY' => 'Same Day City',
            'SMART_POST' => 'Smart Post',
            'STANDARD_OVERNIGHT' => 'Standard Overnight',
        ];
    }

    public function get_methods()
    {
        $methods = [];
        foreach (static::possibleMethods() as $key=>$title){
            $methods[$this->code.'_'.$key] = $title;
        }
        return $methods;
    }

    function create_shipment($order_id, $orders_label_id, $method = '') {
        \common\helpers\Translation::init('admin/orders');

        $manager = \common\services\OrderManager::loadManager();
        $order = $manager->getOrderInstanceWithId('\common\classes\Order', $order_id);
        Yii::$app->get('platform')->config($order->info['platform_id'])->constant_up();
        $manager->set('platform_id', $order->info['platform_id']);

        $return = array();
        $oLabel = \common\models\OrdersLabel::findOne(['orders_id' => $order_id, 'orders_label_id' => $orders_label_id]);
        if (tep_not_null($oLabel->tracking_number)) {
            $return['tracking_number'] = $oLabel->tracking_number;
            $return['parcel_label'] = base64_decode($oLabel->parcel_label_pdf);
        } else {
            $result = $this->createShipment($order, $method, $orders_label_id);
            if ( is_array($result) ) $return = $result;
            if ( is_array($result) && !empty($result['tracking_number']) ) {
                $tracking_number = $result['tracking_number'];
                if (tep_not_null($tracking_number)) {
                    $addTracking = \common\classes\OrderTrackingNumber::instanceFromString('FedEx,'.$tracking_number, $order_id);
                    $addTracking->setOrderProducts($oLabel->getOrdersLabelProducts());
                    $order->info['tracking_number'][] = $addTracking;
                    $order->saveTrackingNumbers();

                    $oLabel->tracking_number = $tracking_number;
                    $oLabel->tracking_numbers_id = $addTracking->tracking_numbers_id;
                    if ( !empty($result['parcel_label']) ) {
                        $oLabel->parcel_label_pdf = base64_encode($result['parcel_label']);
                    }
                    $oLabel->save();
                }
            }
        }
        return $return;
    }

    protected function createShipment($order, $method, $order_label_id)
    {
        $this->last_errors = [];
        $order_id = $order->order_id;

        $shipment_weight = $this->shipment_weight($order_id, $order_label_id);
        $shipment_total = $this->shipment_total($order_id, $order_label_id);

        $labelCurrency = $order->info['currency'];
        if ( $labelCurrency=='GBP' ) $labelCurrency = 'UKL';

        $config = Yii::$app->get('platform')->config($order->info['platform_id']);
        $platform_data = $config->getPlatformData();
        $platform_address = $config->getPlatformAddress();
        $platform_country = \common\helpers\Country::get_country_info_by_id($platform_address['country_id']);

        $shipperAddress = new ComplexType\Address();
        /*
        $shipperAddress
            ->setStreetLines([$platform_address['street_address'], $platform_address['suburb']])
            ->setCity($platform_address['city'])
            ->setPostalCode($platform_address['postcode'])
            ->setCountryCode($platform_country['countries_iso_code_2']);
        $shipperAddress->setStateOrProvinceCode(\common\helpers\Zones::get_zone_code($platform_address['country_id'],$platform_address['zone_id'],$platform_address['state']));
        */

        $shipperAddress->setStreetLines([MODULE_LABEL_FEDEX_ADDRESS_1, MODULE_LABEL_FEDEX_ADDRESS_2]);
        $shipperAddress->setCity(MODULE_LABEL_FEDEX_CITY);
        $shipperAddress->setPostalCode(MODULE_LABEL_FEDEX_POSTAL);
        $shipper_country = \common\helpers\Country::get_country_info_by_id(MODULE_LABEL_FEDEX_COUNTRY);
        $shipperAddress->setCountryCode($shipper_country['countries_iso_code_2']);
        if ( in_array($shipper_country['countries_iso_code_2'], ['US','CA']) ) {
            $shipperAddress->setStateOrProvinceCode(MODULE_LABEL_FEDEX_STATE);
        }

        $shipperContact = new ComplexType\Contact();
        $shipperContact
            ->setCompanyName($platform_address['company'])
            ->setEMailAddress($platform_data['platform_email_address'])
            ->setPersonName($platform_data['platform_owner'])
            ->setPhoneNumber($platform_data['platform_telephone']);

        $shipper = new ComplexType\Party();
        $shipper
            ->setAccountNumber(MODULE_LABEL_FEDEX_ACT_NUM)
            ->setAddress($shipperAddress)
            ->setContact($shipperContact);

        $deliveryAddress = $order->delivery;

        $recipientAddress = new ComplexType\Address();
        $recipientAddress
            ->setStreetLines([$deliveryAddress['street_address'], $deliveryAddress['suburb']])
            ->setCity($deliveryAddress['city'])
            ->setStateOrProvinceCode(\common\helpers\Zones::get_zone_code($deliveryAddress['country']['id'], $deliveryAddress['zone_id'], $deliveryAddress['state']))
            ->setPostalCode($deliveryAddress['postcode'])
            ->setCountryCode($deliveryAddress['country']['iso_code_2']);


        $recipientContact = new ComplexType\Contact();
        $recipientContact
            ->setCompanyName($deliveryAddress['company'])
            ->setPersonName($deliveryAddress['name'])
            ->setPhoneNumber($deliveryAddress['telephone']);

        $recipient = new ComplexType\Party();
        $recipient
            ->setAddress($recipientAddress)
            ->setContact($recipientContact);

        $ShippingDocumentImageType = MODULE_LABEL_FEDEX_LABEL_IMAGE_TYPE;
        if ( $ShippingDocumentImageType=='ZPL2' ) $ShippingDocumentImageType = 'ZPLII';

        $labelSpecification = new ComplexType\LabelSpecification();
        $labelSpecification
            ->setLabelStockType(new SimpleType\LabelStockType(MODULE_LABEL_FEDEX_LABEL_STOCK_TYPE))
            ->setImageType(new SimpleType\ShippingDocumentImageType($ShippingDocumentImageType))
            ->setLabelFormatType(new SimpleType\LabelFormatType(SimpleType\LabelFormatType::_COMMON2D));
        //$labelSpecification->CustomerSpecifiedDetail = new ComplexType\CustomerSpecifiedLabelDetail();
        //$labelSpecification->CustomerSpecifiedDetail->AirWaybillSuppressionCount = 0;

        $processShipmentRequest = new ComplexType\ProcessShipmentRequest();
        $processShipmentRequest->WebAuthenticationDetail->UserCredential->Key = MODULE_LABEL_FEDEX_KEY;
        $processShipmentRequest->WebAuthenticationDetail->UserCredential->Password = MODULE_LABEL_FEDEX_PWD;
        $processShipmentRequest->ClientDetail->AccountNumber = MODULE_LABEL_FEDEX_ACT_NUM;
        $processShipmentRequest->ClientDetail->MeterNumber = MODULE_LABEL_FEDEX_METER_NUM;
        $processShipmentRequest->Version->Major = 23;
        $processShipmentRequest->Version->Intermediate = 0;
        $processShipmentRequest->Version->Minor = 0;
        $processShipmentRequest->Version->ServiceId = 'ship';

        $requestedShipment = new ComplexType\RequestedShipment();
//        $originContactAndAddress = new ComplexType\ContactAndAddress();
//        $originAddress = new ComplexType\Address();
//        $originAddress->
//        $originContactAndAddress->setAddress($originAddress);
//        $requestedShipment->setOrigin($originContactAndAddress);

        $processShipmentRequest->setRequestedShipment($requestedShipment);
        $requestedShipment->setRecipient($recipient);

        // Identifies the date and time the package is tendered to FedEx. Both the date and time portions of the string are expected to be used. The date should not be a past date or a date more than 10 days in the future. The time is the local time of the shipment based on the shipper's time zone.
        $requestedShipment->setShipTimestamp(date('c', strtotime('+1 hour')));

        $requestedShipment->setDropoffType(SimpleType\DropoffType::_REGULAR_PICKUP);
        $requestedShipment->setLabelSpecification($labelSpecification);
        $requestedShipment->setServiceType($method);
        $requestedShipment->setPackagingType('YOUR_PACKAGING');
        $requestedShipment->setPreferredCurrency($labelCurrency);
        $requestedShipment->setShipper($shipper);

        /*
        $requestedShipment->ShippingDocumentSpecification = new ComplexType\ShippingDocumentSpecification();
        $requestedShipment->ShippingDocumentSpecification->setShippingDocumentTypes([SimpleType\RequestedShippingDocumentType::_LABEL]);
*/
        $chargesPayment = new ComplexType\Payment();
        $chargesPayment->setPaymentType(SimpleType\PaymentType::_SENDER);
        $shippingChargesPayor = new ComplexType\Payor();
        $shippingChargesPayor->setResponsibleParty($shipper);
        $chargesPayment->setPayor($shippingChargesPayor);
        $requestedShipment->setShippingChargesPayment($chargesPayment);
        $packageItems = [];
        $packageItem = new ComplexType\RequestedPackageLineItem();
        $packageItem->setSequenceNumber(count($packageItems)+1);
        $packageItem->setWeight( new ComplexType\Weight([
            'Value' => number_format($shipment_weight,3,'.',''),
            'Units' => MODULE_LABEL_FEDEX_WEIGHT_UOM,
        ]) );
        $CustomerReference = new ComplexType\CustomerReference();
        $CustomerReference->setCustomerReferenceType(SimpleType\CustomerReferenceType::_CUSTOMER_REFERENCE);
        $CustomerReference->setValue($order->order_id);
        $packageItem->setCustomerReferences([$CustomerReference]);
        $packageItems[] = $packageItem;

        $requestedShipment->setRequestedPackageLineItems($packageItems);
        $requestedShipment->setPackageCount(count($packageItems));

        //{{ Customs Clearance
        if ( $shipper->Address->CountryCode!=$recipient->Address->CountryCode ) {
            $customsClearanceDetail = new ComplexType\CustomsClearanceDetail();
            $customsClearanceDetail->DutiesPayment = new ComplexType\Payment([
                'PaymentType' => SimpleType\PaymentType::_SENDER,
                'Payor' => new ComplexType\Payor([
                    'ResponsibleParty' => new ComplexType\Party([
                        'AccountNumber' => $shipper->AccountNumber,
                        'Address' => new ComplexType\Address([
                            'CountryCode' => $shipper->Address->CountryCode,
                        ])
                    ])
                ])
            ]);
            $customsClearanceDetail->CustomsValue = new ComplexType\Money([
                'Amount' => number_format($shipment_total,2,'.',''),
                'Currency' => $labelCurrency,
            ]);
            $Commodities = [];
            $Commodity = new ComplexType\Commodity([
                'NumberOfPieces' => 1,
                'Description' => 'Product name',
                'CountryOfManufacture' => $shipper->Address->CountryCode,
                'Quantity' => 1,
                'QuantityUnits' => 'EA',
                'UnitPrice' => new ComplexType\Money([
                    'Amount' => number_format($shipment_total,2,'.',''),
                    'Currency' => $labelCurrency,
                ]),
                'Weight' => new ComplexType\Weight([
                    'Value' => number_format($shipment_weight,3,'.',''),
                    'Units' => MODULE_LABEL_FEDEX_WEIGHT_UOM,
                ])
            ]);
            $Commodities[] = $Commodity;
            $customsClearanceDetail->setCommodities($Commodities);
            $requestedShipment->setCustomsClearanceDetail($customsClearanceDetail);
        }
        //}} Customs Clearance

//        last_errors
//echo '<pre>'; var_dump($processShipmentRequest); echo '</pre>';
        try {
            $serviceRequest = new ShipService\Request();
            $response = $serviceRequest->getProcessShipmentReply($processShipmentRequest);
//        echo '<textarea>'.$serviceRequest->getSoapClient()->__getLastRequest().'</textarea>';
//        echo '<textarea>'.$serviceRequest->getSoapClient()->__getLastResponse().'</textarea>';
            /**
             * @var $response ComplexType\ProcessShipmentReply
             */
            $result = false;
            if (is_array($response->Notifications) && count($response->Notifications) > 0) {
                foreach ($response->Notifications as $Notification) {
                    /**
                     * @var $Notification ComplexType\Notification
                     */
                    if ($Notification->Severity == 'ERROR') {
                        $this->last_errors[] = $Notification->Code . ' - ' . $Notification->Message;
                    }
                }
            }
            if (count($this->last_errors) > 0) {
                $result = [
                    'errors' => $this->last_errors,
                ];
            } else
                if (isset($response->CompletedShipmentDetail) && isset($response->CompletedShipmentDetail->CompletedPackageDetails) && is_array($response->CompletedShipmentDetail->CompletedPackageDetails)) {
                    foreach ($response->CompletedShipmentDetail->CompletedPackageDetails as $completedShipmentDetail) {
                        /**
                         * @var $completedShipmentDetail ComplexType\CompletedPackageDetail
                         */
                        foreach ($completedShipmentDetail->TrackingIds as $_idx => $TrackingId) {
                            $result = [
                                'tracking_number' => $TrackingId->TrackingNumber,
                                'parcel_label' => $completedShipmentDetail->Label->Parts[$_idx]->Image,
                                'parcel_label_format' => MODULE_LABEL_FEDEX_LABEL_IMAGE_TYPE,
                                'tracking_data' => [
                                    ''
                                ]
                            ];
                        }
                    }
                }
        }catch (\Exception $ex){
            $result = [
                'errors' => [ $ex->getMessage() ]
            ];
        }
        return $result;

        echo '<pre>'; var_dump($response); echo '</pre>'; die;
    }

}