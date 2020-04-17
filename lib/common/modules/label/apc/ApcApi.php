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
 * realize apc.hypaship API
 */
class ApcApi
{
    private $_server_test = 'https://apc-training.hypaship.com/api/3.0/';
    private $_server = 'https://apc.hypaship.com/api/3.0/';
    private $_username  = 'sales@ukwristbands.com';
    private $_password = 'Streetwise10';
    private $_password_test = 'Streetwise 10';
    private $_sandBox = false;
    private $_format = '.json';

    // include_once (DIR_FS_CATALOG . DIR_WS_INCLUDES . 'modules/shipping/apc/ApcApi.php');
    public function __construct()
    {

    }

    /**
     * @param $command
     * @param null $post
     * @param string $type_request
     * @return mixed
     */
    private function _request($command, $post = null, $type_request = 'POST')
    {
        if($this->_sandBox )
        {
            $sAuth = base64_encode($this->_username . ":" . $this->_password_test);
            $uri = $this->_server_test;
        }
        else
        {
            $sAuth = base64_encode($this->_username . ":" . $this->_password);
            $uri = $this->_server;
        }

        $request_uri = $uri . $command;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'remote-user: Basic ' . $sAuth,
            'Content-Type: application/xml',
        ]);
        curl_setopt($ch, CURLOPT_URL, $request_uri);
        // curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        $post && curl_setopt($ch, CURLOPT_POSTFIELDS, ($post));
        $post && curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type_request);

        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); //get status code
        $result = curl_exec ($ch);
        curl_close ($ch);

        return $result;
    }

    /**
     * @param $command
     * @param array $post
     * @return mixed
     */
    private function _sendRequest($command, $post = null)
    {
        return $this->_request($command . $this->_format, $post);
    }

    /**
     * Inbound (post)
     * Retrieve all available service levels
     * @param array $aRequest
     * @return mixed
     */
    public function serviceAvailability($aRequest)
    {
        $command = 'ServiceAvailability';
        return $this->_sendRequest($command, $aRequest);
    }

    /**
     * Inbound (post)
     * Orders can be created by using either a shipper or depot level login on behalf of a shipper.
     * Orders created by depots must specify the account number for the shipper that they are creating orders for.
     * Only one shipper at a time can be specified in an API call.
     */
    public function createOrders($xml)
    {
        $command = 'Orders';
        return $this->_sendRequest($command, $xml);
    }

    public function cancelOrder($tracking_number, $xml)
    {
        $command = 'Orders/' . $tracking_number . '.json?searchtype=CarrierWaybill';
        return $this->_request($command, $xml, 'PUT');
    }

    /**
     * Outbound (get)
     * Retrieve labels and/or consignment data.
     * Data can be retrieved for a specific shipper or the entire depot based on which login is used.
     */
    public function getLabels($tracking_number)
    {
        $command = 'Orders/' . $tracking_number . '.json?searchtype=CarrierWaybill';
        return $this->_request($command);
    }

    /**
     * Outbound (get)
     * Tracking information can be retrieved by depot level users or shipper level users,
     * based on the account used in the request.
     */
    public function getTracks()
    {
        $command = 'Tracks';
        return $this->_sendRequest($command);
    }

    /**
     * Outbound (get)
     * Tracking information can be retrieved by depot level users or shipper level users,
     * based on the account used in the request.
     */
    public function getTracking($tracking_number)
    {
        $command = 'Tracks/' . $tracking_number . '.json?searchtype=CarrierWaybill';
        return $this->_request($command);
    }
}