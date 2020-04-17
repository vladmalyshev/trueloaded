<?php
namespace common\modules\orderPayment\lib\pay360;
/**
 * Transaction component for pay360 payment flow
 * @author A.Kosheliev
 */
class Sessions {
    
    private $installationId;
    private $username;
    private $password;
    
    public function __construct($installationId, $username, $password){
        $this->installationId = $installationId;
        $this->username = $username;
        $this->password = $password;
        if (!$this->installationId){
            throw new \Exception('Installation ID is not defined');
        }        
        if (!$this->username){
            throw new \Exception('Username is not defined');
        }
        if (!$this->password){
            throw new \Exception('Password is not defined');
        }
    }
    
    public function payment(string $gateWayUrl, array $data){
        $uri = '/hosted/rest/sessions/{instId}/payments';
        $uri = preg_replace("/{instId}/", $this->installationId, $uri);
        $client = new Transport($gateWayUrl . $uri, $this->username, $this->password);
        $response = $client->post($data);
        return $response;
    }
    
    public function status(string $gateWayUrl, string $sessionId){
        if (!$sessionId) throw new \Exception('Undefined Session Id');
        $uri = '/hosted/rest/sessions/{instId}/{sessionId}/status';
        $uri = preg_replace("/{instId}/", $this->installationId, $uri);
        $uri = preg_replace("/{sessionId}/", $sessionId, $uri);
        $client = new Transport($gateWayUrl . $uri, $this->username, $this->password);
        $response = $client->get();
        return $response;
    }
}
