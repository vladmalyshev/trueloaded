<?php

/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

/**
 * Class Api
 * realize RoyalMail API
 */

class RoyalMailApi {

    private $_server = 'https://api.royalmail.net/shipping/v2';
    private $_username = '';
    private $_password = '';
    private $_client_id = '';
    private $_client_secret = '';
    private $_auth_token = '';

    public function __construct() {
        
    }

    public function setConfig($config) {
        if (isset($config['server'])) {
            $this->_server = $config['server'];
        }
        if (isset($config['username'])) {
            $this->_username = $config['username'];
        }
        if (isset($config['password'])) {
            $this->_password = $config['password'];
        }
        if (isset($config['client_id'])) {
            $this->_client_id = $config['client_id'];
        }
        if (isset($config['client_secret'])) {
            $this->_client_secret = $config['client_secret'];
        }
    }

    private function _get_auth_token() {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->_server . '/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'X-IBM-Client-Id: ' . $this->_client_id,
                'X-IBM-Client-Secret: ' . $this->_client_secret,
                'X-RMG-User-Name: ' . $this->_username,
                'X-RMG-Password: ' . $this->_password,
            ),
        ));

        $response = curl_exec($curl);
        $errCode = curl_errno($curl);
        $errMessage = curl_error($curl);

        curl_close($curl);

        if ($errCode || $errMessage) {
            return ['httpCode' => $errCode, 'httpMessage' => 'cURL Error: ' . $errMessage];
        } else {
            $result = json_decode($response, true);
            return $result['token'];
        }
    }

    public function _create_shipment($order, $method, $shipping_weight = 0) {
        if ($this->_auth_token == '') {
            $this->_auth_token = $this->_get_auth_token();
        }

        if ($method == '') {
            if ($order->delivery['country']['iso_code_2'] == 'GB') {
                $method = '1-CRL-F-6';
            } else {
                $method = 'I-OLS-E';
            }
        }
        list($service_type, $service_offering, $service_format, $service_enhancements) = explode('-', $method);

        if (!($shipping_weight > 0)) {
            $shipping_weight = $order->info['shipping_weight'];
        }

        $request = array(
            'shipmentType' => 'Delivery',
            'service' => array(
                'format' => $service_format,
                'occurrence' => '1',
                'offering' => $service_offering,
                'type' => $service_type,
                'signature' => 'false'
            ),
            'shippingDate' => ($order->info['delivery_date'] > date('Y-m-d') ? date('Y-m-d', strtotime($order->info['delivery_date'])) : (date('H:i') > '16:30' ? date('Y-m-d', strtotime('+1 day')) : date('Y-m-d'))),
            'items' => array(
                0 => array(
                    'count' => '1',
                    'weight' => array('unitOfMeasure' => 'g', 'value' => ($shipping_weight > 0 ? $shipping_weight * 1000 : '100'))
                )
            ),
            'recipientContact' => array(
                'name' => $this->transliterate($order->delivery['name'] ? $order->delivery['name'] : $order->delivery['firstname'] . ' ' . $order->delivery['lastname']),
                'complementaryName' => $this->transliterate($order->delivery['company']),
                'email' => $this->transliterate($order->customer['email_address'])
            ),
            'recipientAddress' => array(
                'addressLine1' => $this->transliterate($order->delivery['street_address']),
                'addressLine2' => $this->transliterate($order->delivery['suburb']),
                'postTown' => $this->transliterate($order->delivery['city']),
                'county' => $this->transliterate($order->delivery['state']),
                'postCode' => $this->transliterate($order->delivery['postcode']),
                'countryCode' => $this->transliterate($order->delivery['country']['iso_code_2'])
            ),
            'senderReference' => ('Order # ' . $order->order_id)
        );

        if ($service_enhancements > 0) {
            $request['service']['enhancements'] = array($service_enhancements);
        }

        if (tep_not_null($order->customer['telephone'])) {
            $request['recipientContact']['telephoneNumber'] = $this->transliterate($order->customer['telephone']);
        }

        if ($order->delivery['country']['iso_code_2'] != 'GB') {
            //unset($request['items']);
            $request['internationalInfo'] = array(
                'parcels' => array(
                    0 => array(
                        'weight' => array('unitOfMeasure' => 'g', 'value' => ($shipping_weight > 0 ? $shipping_weight * 1000 : '100')),
                        //'length' => array('unitOfMeasure' => 'cm', 'value' => '10'),
                        //'height' => array('unitOfMeasure' => 'cm', 'value' => '10'),
                        //'width' => array('unitOfMeasure' => 'cm', 'value' => '10'),
                    )
                ),
                //'invoiceDate' => date('Y-m-d'),
                //'termsOfDelivery' => 'DAP',
            );
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->_server . '/shipments',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($request),
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'X-IBM-Client-Id: ' . $this->_client_id,
                'X-IBM-Client-Secret: ' . $this->_client_secret,
                'X-RMG-Auth-Token: ' . $this->_auth_token
            ),
        ));

        $response = curl_exec($curl);
        $errCode = curl_errno($curl);
        $errMessage = curl_error($curl);

        curl_close($curl);

        if ($errCode || $errMessage) {
            return ['httpCode' => $errCode, 'httpMessage' => 'cURL Error: ' . $errMessage];
        } else {
            return json_decode($response, true);
        }
    }

    public function _parcel_label($shipmentNumber) {
        if ($this->_auth_token == '') {
            $this->_auth_token = $this->_get_auth_token();
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->_server . '/' . $shipmentNumber . '/label?outputFormat=PDF',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'X-IBM-Client-Id: ' . $this->_client_id,
                'X-IBM-Client-Secret: ' . $this->_client_secret,
                'X-RMG-Auth-Token: ' . $this->_auth_token
            ),
        ));

        $response = curl_exec($curl);
        $errCode = curl_errno($curl);
        $errMessage = curl_error($curl);

        curl_close($curl);

        if ($errCode || $errMessage) {
            return ['httpCode' => $errCode, 'httpMessage' => 'cURL Error: ' . $errMessage];
        } else {
            return json_decode($response, true);
        }
    }

    public function _update_shipment($order, $shipmentNumber) {
        if ($this->_auth_token == '') {
            $this->_auth_token = $this->_get_auth_token();
        }

        $request = array(
            'recipientContact' => array(
                'name' => $this->transliterate($order->delivery['name'] ? $order->delivery['name'] : $order->delivery['firstname'] . ' ' . $order->delivery['lastname']),
                'complementaryName' => $this->transliterate($order->delivery['company']),
                'email' => $this->transliterate($order->customer['email_address'])
            ),
            'recipientAddress' => array(
                'addressLine1' => $this->transliterate($order->delivery['street_address']),
                'addressLine2' => $this->transliterate($order->delivery['suburb']),
                'postTown' => $this->transliterate($order->delivery['city']),
                'county' => $this->transliterate($order->delivery['state']),
                'postCode' => $this->transliterate($order->delivery['postcode']),
                'countryCode' => $this->transliterate($order->delivery['country']['iso_code_2'])
            )
        );

        if (tep_not_null($order->customer['telephone'])) {
            $request['recipientContact']['telephoneNumber'] = $this->transliterate($order->customer['telephone']);
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->_server . '/' . $shipmentNumber,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => json_encode($request),
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'X-IBM-Client-Id: ' . $this->_client_id,
                'X-IBM-Client-Secret: ' . $this->_client_secret,
                'X-RMG-Auth-Token: ' . $this->_auth_token
            ),
        ));

        $response = curl_exec($curl);
        $errCode = curl_errno($curl);
        $errMessage = curl_error($curl);

        curl_close($curl);

        if ($errCode || $errMessage) {
            return ['httpCode' => $errCode, 'httpMessage' => 'cURL Error: ' . $errMessage];
        } else {
            return json_decode($response, true);
        }
    }

    public function _cancel_shipment($shipmentNumber) {
        if ($this->_auth_token == '') {
            $this->_auth_token = $this->_get_auth_token();
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->_server . '/' . $shipmentNumber,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'X-IBM-Client-Id: ' . $this->_client_id,
                'X-IBM-Client-Secret: ' . $this->_client_secret,
                'X-RMG-Auth-Token: ' . $this->_auth_token
            ),
        ));

        $response = curl_exec($curl);
        $errCode = curl_errno($curl);
        $errMessage = curl_error($curl);

        curl_close($curl);

        if ($errCode || $errMessage) {
            return ['httpCode' => $errCode, 'httpMessage' => 'cURL Error: ' . $errMessage];
        } else {
            return json_decode($response, true);
        }
    }

    function transliterate($str) {
        if (function_exists('transliterator_transliterate')) {
            $str = transliterator_transliterate('Any-Latin; Latin-ASCII; [\u0080-\u7fff] remove', $str);
        }
        return iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
    }

}
