<?php

/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */
namespace backend\models\EP\Provider\Magento\helpers;

use Yii;

class SoapClient {
    
    private $config;
    private $client;
    
    public function __construct($config) {
        $this->config = $config;
        try {
            $this->client = new \SoapClient(
                $this->config['location'] . "/api/soap/?wsdl",
                [
                    'trace' => 1,
                    //'proxy_host'     => "localhost",
                    //'proxy_port'     => 8080,
                    //'proxy_login'    => "some_name",
                    //'proxy_password' => "some_password",
                    'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP,
                    'stream_context' => stream_context_create([
                        'http' => [
                            //'header'  => "APIToken: $api_token\r\n",
                        ]
                    ]),
                ]
            );
            $auth = new \stdClass();
            $auth->api_key = $this->config['api_key'];
            $soapHeaders = new \SoapHeader('http://schemas.xmlsoap.org/ws/2002/07/utility', 'auth', $auth, false);
            $this->client->__setSoapHeaders($soapHeaders);
        }catch (\Exception $ex) {
            throw new Exception('Configuration error');
        }
    }
    
    public function getClient(){
        return $this->client; 
    }
    
    public function loginClient(){
        try{
            $session = $this->client->login($this->config['api_user'], $this->config['api_key']);    
        } catch (\Exception $ex) {
            throw new \Exception('Authentication error');
        }        
        
        return $session;
    }
}