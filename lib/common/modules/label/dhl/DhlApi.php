<?php

/* 
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

/**
 * Class Api
 * realize DHL API
 */

use DHL\Entity\GB\ShipmentResponse;
use DHL\Entity\GB\ShipmentRequest;
use DHL\Client\Web as WebserviceClient;
use DHL\Datatype\GB\Piece;
use DHL\Datatype\GB\SpecialService;

require('init.php');

// Test Site ID: v62_k1PGjT38pE
// Test Password: ZCr7uzeiPT
// Account Number: 186269617
// Email ID: cj@powerfuluk.com
// Name: Caroline Jefferson
// Country: United Kingdom


class DhlApi {

    private $_mode = 'staging';
    private $_site_id = '';
    private $_password = '';
    private $_account_number = '';
    private $_request_archive_doc = 'True';
    private $_label_template = '8X4_A4_PDF';
    private $_label_resolution = '200';
    private $_insured_from = '-1';

    public function __construct() {
        
    }

    public function setConfig($config) {
        if (isset($config['mode'])) {
            $this->_mode = $config['mode'];
        }
        if (isset($config['site_id'])) {
            $this->_site_id = $config['site_id'];
        }
        if (isset($config['password'])) {
            $this->_password = $config['password'];
        }
        if (isset($config['account_number'])) {
            $this->_account_number = $config['account_number'];
        }
        if (isset($config['request_archive_doc'])) {
            $this->_request_archive_doc = $config['request_archive_doc'];
        }
        if (isset($config['label_template'])) {
            $this->_label_template = $config['label_template'];
        }
        if (isset($config['label_resolution'])) {
            $this->_label_resolution = $config['label_resolution'];
        }
        if (isset($config['insured_from'])) {
            $this->_insured_from = $config['insured_from'];
        }
    }

    public function _create_shipment($order, $method, $shipping_weight = 0, $package_total = 0) {
        $config = Yii::$app->get('platform')->config($order->info['platform_id']);
        $platform_data = $config->getPlatformData();
        $platform_address = $config->getPlatformAddress();
        $platform_country = \common\helpers\Country::get_country_info_by_id($platform_address['country_id']);

        if (!($shipping_weight > 0)) {
            $shipping_weight = $order->info['shipping_weight'];
        }

        $shipment = new ShipmentRequest();

        // Set values of the request
        $shipment->MessageTime = date(DATE_ATOM); // '2001-12-17T09:30:47-05:00';
        $shipment->MessageReference = '1234567890123456789012345678901';
        $shipment->SiteID = $this->_site_id;
        $shipment->Password = $this->_password;
        $shipment->RegionCode = 'EU';
        //$shipment->RequestedPickupTime = 'Y';
        //$shipment->NewShipper = 'N';
        $shipment->LanguageCode = 'en';
        $shipment->PiecesEnabled = 'Y';
        $shipment->Billing->ShipperAccountNumber = $this->_account_number;
        $shipment->Billing->ShippingPaymentType = 'S';
        $shipment->Billing->BillingAccountNumber = $this->_account_number;
        $shipment->Billing->DutyPaymentType = 'R';
        //$shipment->Billing->DutyAccountNumber = $this->_account_number;

        $shipment->Consignee->CompanyName = ($order->delivery['company'] ? $order->delivery['company'] : ($order->delivery['name'] ? $order->delivery['name'] : $order->delivery['firstname'] . ' ' . $order->delivery['lastname']));
        $shipment->Consignee->addAddressLine($order->delivery['street_address']);
        $shipment->Consignee->addAddressLine($order->delivery['suburb']);
        $shipment->Consignee->City = $order->delivery['city'];
        $shipment->Consignee->PostalCode = $order->delivery['postcode'];
        $shipment->Consignee->CountryCode = $order->delivery['country']['iso_code_2'];
        $shipment->Consignee->CountryName = $order->delivery['country']['title'];
        $shipment->Consignee->Contact->PersonName = ($order->delivery['name'] ? $order->delivery['name'] : $order->delivery['firstname'] . ' ' . $order->delivery['lastname']);
        $shipment->Consignee->Contact->PhoneNumber = $order->customer['telephone'];
        $shipment->Consignee->Contact->Email = $order->customer['email_address'];

        $shipment->Dutiable->DeclaredValue = number_format($order->info['subtotal'], 2, '.', '');
        $shipment->Dutiable->DeclaredCurrency = $order->info['currency'];

        $shipment->Reference->ReferenceID = 'Order # ' . $order->order_id;
        //$shipment->Reference->ReferenceType = 'St';
        $shipment->ShipmentDetails->NumberOfPieces = 1;

        $piece = new Piece();
        $piece->PieceID = '1';
        $piece->PackageType = 'EE';
        $piece->Weight = round($shipping_weight > 0 ? $shipping_weight : '0.1', 3);
/*
        $piece->DimWeight = '600.0';
        $piece->Width = '50';
        $piece->Height = '100';
        $piece->Depth = '150';
*/
        $shipment->ShipmentDetails->addPiece($piece);

        $shipment->ShipmentDetails->Weight = round($shipping_weight > 0 ? $shipping_weight : '0.1', 3);
        $shipment->ShipmentDetails->WeightUnit = 'K';
        $shipment->ShipmentDetails->GlobalProductCode = $method;
        $shipment->ShipmentDetails->LocalProductCode = $method;
        $shipment->ShipmentDetails->Date = ($order->info['delivery_date'] > date('Y-m-d') ? date('Y-m-d', strtotime($order->info['delivery_date'])) : (date('H:i') > '16:30' ? date('Y-m-d', strtotime('+1 day')) : date('Y-m-d')));
        $shipment->ShipmentDetails->Contents = $platform_data['platform_name'] . ' - Order # ' . $order->order_id;
        $shipment->ShipmentDetails->DoorTo = 'DD';
        $shipment->ShipmentDetails->DimensionUnit = 'C';
        if ( is_numeric($this->_insured_from) && $this->_insured_from>=0 && $package_total>=floatval($this->_insured_from) && in_array($method, ['N','I']) ) {
            $shipment->ShipmentDetails->InsuredAmount = number_format($package_total, 2, '.', '');
        }
        $shipment->ShipmentDetails->PackageType = 'EE';
        $shipment->ShipmentDetails->IsDutiable = 'N';
        $shipment->ShipmentDetails->CurrencyCode = $order->info['currency'];

        $shipment->Shipper->ShipperID = $this->_account_number;
        $shipment->Shipper->CompanyName = $platform_address['company'];
        $shipment->Shipper->RegisteredAccount = $this->_account_number;
        $shipment->Shipper->addAddressLine($platform_address['street_address']);
        $shipment->Shipper->addAddressLine($platform_address['suburb']);
        $shipment->Shipper->City = $platform_address['city'];
        $shipment->Shipper->PostalCode = $platform_address['postcode'];
        $shipment->Shipper->CountryCode = $platform_country['countries_iso_code_2'];
        $shipment->Shipper->CountryName = $platform_country['text'];
        $shipment->Shipper->Contact->PersonName = $platform_data['platform_owner'];
        $shipment->Shipper->Contact->PhoneNumber = $platform_data['platform_telephone'];
        $shipment->Shipper->Contact->Email = $platform_data['platform_email_address'];
/*
        $specialService = new SpecialService();
        $specialService->SpecialServiceType = 'II';
        $shipment->addSpecialService($specialService);
*/
        //$shipment->EProcShip = 'N';
        $shipment->RequestArchiveDoc = ($this->_request_archive_doc == 'True' ? 'Y' : 'N');
        $shipment->LabelImageFormat = 'PDF';
        $shipment->Label->LabelTemplate = $this->_label_template;
        $shipment->Label->Resolution = $this->_label_resolution;

        //echo $shipment->toXML();

        try {
            // Call DHL XML API
            $client = new WebserviceClient($this->_mode);
            $xml = $client->call($shipment);

            $response = new ShipmentResponse();
            $response->initFromXML($xml);
            return $response;
            //echo $xml . PHP_EOL . $response->toXML();
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

}
