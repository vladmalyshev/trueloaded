<?php

namespace common\modules\orderPayment\lib\SecurePay;

/**
 * ClientApi of SecurePay
 * @author A.Kosheliev
 */
use Yii;

class ClientApi {

    private $merchantId;
    private $clientId;
    private $clientSecret;
    private $token = null;
    private $accessToken;
    private $idempotency;
    private $testMode = false;

    const LIVE_HOST = 'https://payments.auspost.net.au';
    const SANDBOX_HOST = 'https://payments-stest.npe.auspost.zone';

    public function __construct($merchantId, $clientId, $clientSecret) {
        $this->merchantId = $merchantId;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        if (empty($this->merchantId) || empty($this->clientId) || empty($this->clientSecret)) {
            throw new \Exception('Invalid SecurePay settings');
        }
    }

    public function getRequestHost(): string {
        if ($this->testMode) {
            return self::SANDBOX_HOST;
        } else {
            return self::LIVE_HOST;
        }
    }

    public function initTestMode() {
        $this->testMode = true;
    }

    public function initLiveMode() {
        $this->testMode = false;
    }

    public function getAccessToken() {
        try {
            $this->token = new Token($this->clientId, $this->clientSecret, $this->testMode);
            $this->accessToken = $this->token->getAccessToken();
            return $this->accessToken;
        } catch (\Exception $ex) {
            Yii::error($ex->getMessage(), 'securepay api');
        }
        return false;
    }

    public function setIdempotency($key) {
        $this->idempotency = $key;
    }

    public function getIdempotency() {
        return $this->idempotency;
    }

    public function createPayment($postArray = []) {
        $url = $this->getRequestHost() . "/v2/payments";
        return $this->sendRequest("POST", $url, $postArray);
    }

    private function sendRequest($method, $url, $postArray = []) {
        try {

            $client = new \GuzzleHttp\Client([
                'headers' => $this->getHeaders()
            ]);

            $response = $client->request($method, $url, [
                \GuzzleHttp\RequestOptions::JSON => $postArray
            ]);

            $data = $response->getBody()->getContents();
            return \GuzzleHttp\json_decode($data, true);
        } catch (\Exception $ex) {
            return false;
        }
    }

    private function getHeaders() {
        $headers = [
            "Content-Type" => "application/json",
            "Authorization" => "Bearer {$this->getAccessToken()}",
        ];

        if ($idempotency = $this->getIdempotency()) {
            $headers["Idempotency-Key"] = $idempotency;
        }
        return $headers;
    }

}
