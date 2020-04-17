<?php

namespace common\modules\orderPayment\lib\SecurePay;

/**
 * Description of SecurePay
 * Php service to work with SecurePay lib
 * @author A.Kosheliev
 */
use Yii;

class Token {

    CONST SANDBOX_OAUTH2_URL = 'https://hello.sandbox.auspost.com.au/oauth2/ausujjr7T0v0TTilk3l5/v1/token';
    CONST LIVE_OAUTH_URL = 'https://hello.auspost.com.au/oauth2/ausrkwxtmx9Jtwp4s356/v1/token';

    private $token;
    private $grant_type = 'client_credentials';
    private $username;
    private $password;
    private $isTestMode;
    private $scopes = [
        'https://api.payments.auspost.com.au/payhive/payments/read',
        'https://api.payments.auspost.com.au/payhive/payments/write'
    ];

    /**
     * 
     * @param type $username
     * @param type $password
     * @param type $mode te
     * @throws \Exception
     */
    public function __construct($username, $password, $testMode = true) {
        $this->username = $username;
        $this->password = $password;
        if (empty($this->username) || empty($this->password)) {
            throw new \Exception('Invalid credentials');
        }
        $this->isTestMode = $testMode;
    }

    public function getRequestUrl() {
        return $this->isTestMode ? self::SANDBOX_OAUTH2_URL : self::LIVE_OAUTH_URL;
    }

    public function addInstrumentScopes() {
        $this->scopes[] = 'https://api.payments.auspost.com.au/payhive/payment-instruments/read';
        $this->scopes[] = 'https://api.payments.auspost.com.au/payhive/payment-instruments/write';
    }

    private function getAuthorizationHead() {
        return "Basic " . base64_encode($this->username . ":" . $this->password);
    }

    private function getScopes() {
        return implode(" ", $this->scopes);
    }

    public function requestToken() {
        try {
            $client = new \GuzzleHttp\Client([
                'headers' => [
                    "Content-Type" => "application/x-www-form-urlencoded",
                    "Authorization" => $this->getAuthorizationHead()
                ]
            ]);

            $response = $client->request("POST", $this->getRequestUrl(), [
                'form_params' => [
                    'grant_type' => $this->grant_type,
                    'scope' => $this->getScopes()
                ]
            ]);
            $data = $response->getBody()->getContents();
            return \GuzzleHttp\json_decode($data, true);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getAccessToken() {
        if (!isset($_SESSION['secpayAccessToken']) || !$this->isTokenValid()) {
            if ($token = $this->requestToken()) {
                $_SESSION['secpayAccessToken'] = $token['access_token'];
                $now = new \DateTime();
                $now->add(new \DateInterval("PT{$this->getSecInHours($token['expires_in'])}H"));
                $_SESSION['secpayAccessTokenTill'] = $now->format("Y-m-d\TH:i:s");
            }
        }
        return $_SESSION['secpayAccessToken'];
    }

    private function getSecInHours($sec) {
        return $sec / 3600;
    }

    public function isTokenValid() {
        if (isset($_SESSION['secpayAccessTokenTill'])) {
            $now = new \DateTime();
            $til = new \DateTime($_SESSION['secpayAccessTokenTill']);
            if ($now < $til) {
                return true;
            }
        }
        return false;
    }

}
