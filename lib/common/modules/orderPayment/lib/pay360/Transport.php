<?php
namespace common\modules\orderPayment\lib\pay360;
/**
 * Transport class
 * @author A.Kosheliev
 */
class Transport {
    
    private $url;
    private $username;
    private $password;
    
    public function __construct($url, $username, $password) {
        $this->url = $url;
        $this->username = $username;
        $this->password = $password;
        if (!( $this->username || $this->password || $this->url)) 
            throw new \Exception('Invalid request settings');
    }

    public function sendRequest($method, $data = []) {
        try{
            $client = new \GuzzleHttp\Client([
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                        'Authorization'=> $this->getAuth()
                    ]
                ]);
            $response = $client->request($method, $this->url, [
                \GuzzleHttp\RequestOptions::JSON => $data
            ]);
            return $response->getBody()->getContents();
        } catch (\Exception $ex) {
            \Yii::warning($ex->getMessage() . $ex->getTraceAsString(), 'secpay');
        }
    }
    
    public function post($data){
        try{
            $response = $this->sendRequest("POST", $data);
            return \GuzzleHttp\json_decode($response, true);
        } catch (\Exception $ex) {
            \Yii::warning($ex->getMessage(), 'secpay');
        }
    }
    
    public function get(){
        try{
            $response = $this->sendRequest("GET");
            return \GuzzleHttp\json_decode($response, true);
        } catch (\Exception $ex) {
            \Yii::warning($ex->getMessage(), 'secpay');
        }
    }
    
    private function getAuth(){
        return "Basic " . base64_encode($this->username.":".$this->password);
    }
    
}
