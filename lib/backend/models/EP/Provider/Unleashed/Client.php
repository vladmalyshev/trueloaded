<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models\EP\Provider\Unleashed;


use yii\httpclient\Client as HttpClient;

class Client
{
    /**
     * @var HttpClient
     */
    protected $client;

    protected $api_id = '';
    protected $api_key = '';

    private $debug = false;

    /**
     * Client constructor.
     * @param HttpClient $client
     */
    public function __construct($apiId, $apiKey)
    {
        $this->api_id = $apiId;
        $this->api_key = $apiKey;
        $this->client = new HttpClient([
            'baseUrl' => 'https://api.unleashedsoftware.com/',
            'parsers' => [
                'json' => '\backend\models\EP\Provider\Unleashed\JsonParser',
            ]
        ]);
        if (version_compare(phpversion(), '7.1', '>=')) {
          ini_set('serialize_precision', -1);
        }
  }


    /**
     * @param $endpoint
     * @param string $requestParams
     * @return \yii\httpclient\Request
     */
    public function get($endpoint, $requestParams = '')
    {
        if ( is_array($requestParams) ) {
            $requestParams = http_build_query($requestParams);
        }

        $request = $this->client->createRequest()
            ->setMethod('GET')
            ->setUrl($endpoint.(empty($requestParams)?'':'?').strval($requestParams))
            ->addHeaders($this->apiHeaders($requestParams))
            ->addOptions([]);

        if ($this->debug) {
          \Yii::warning(" Headers:\n" . print_r($this->apiHeaders(''),1) . "\n Request: " .strval($requestParams)
              , "UNLEASHED_CLIENT GET");
        }
        return $request;
    }

    /**
     * @param $endpoint
     * @param string $requestParams
     * @param array  $data
     * @return \yii\httpclient\Request
     */
    public function post($endpoint, $requestParams = '', $data = [])
    {
        if ( is_array($requestParams) ) {
            $requestParams = http_build_query($requestParams);
        }

        $request = $this->client->createRequest()
            ->setMethod('POST')
            ->setUrl($endpoint.(empty($requestParams)?'':'?').strval($requestParams))
            ->addHeaders($this->apiHeaders(''))
            ->addHeaders([
              'Content-Length' => strlen(json_encode($data)),
              ])
            ->addOptions([])
            ->setFormat(HttpClient::FORMAT_JSON)
            ->setContent(json_encode($data))
            ;

        if ($this->debug) {
          \Yii::warning("Data: " . json_encode($data) . "\n Headers:\n" . print_r($this->apiHeaders(''),1) . "\n Request: " .strval($requestParams)
              , "UNLEASHED_CLIENT POST");
        }

        return $request;
    }

    protected function apiHeaders($requestParams)
    {
        return [
            'Content-Type' => 'application/json', //Content-Type - This must be either application/xml or application/json.
            'Accept' => 'application/json', //Accept - This must be either application/xml or application/json.
            'api-auth-id' => $this->api_id, // api-auth-id - You must send your API id in this header.
            'api-auth-signature' => base64_encode(hash_hmac('sha256', $requestParams, $this->api_key, true)), //api-auth-signature - You must send the method signature in this header.
        ];
    }

}