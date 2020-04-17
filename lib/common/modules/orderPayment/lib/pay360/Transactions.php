<?php
namespace common\modules\orderPayment\lib\pay360;
/**
 * Transaction component for pay360 payment flow
 * @author A.Kosheliev
 */
class Transactions {
    
    private $installationId;
    private $transactionId;
    private $username;
    private $password;
    
    public function __construct($installationId, $transactionId = null, $username, $password){
        $this->installationId = $installationId;
        $this->transactionId = $transactionId;
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
    
    public function capture(string $gateWayUrl, array $data){
        $uri = '/acceptor/rest/transactions/{instId}/{transactionId}/capture';
        if (!$this->transactionId){
            throw new \Exception('Transaction ID is not defined');
        }
        $uri = preg_replace("/{instId}/", $this->installationId, $uri);
        $uri = preg_replace("/{transactionId}/", $this->transactionId, $uri);
        $client = new Transport($gateWayUrl . $uri, $this->username, $this->password);
        $response = $client->post($data);
        return $response;
    }
    
    public function find(string $gateWayUrl){
        $uri = '/acceptor/rest/transactions/{instId}/{transactionId}';
        if (!$this->transactionId){
            throw new \Exception('Transaction ID is not defined');
        }
        $uri = preg_replace("/{instId}/", $this->installationId, $uri);
        $uri = preg_replace("/{transactionId}/", $this->transactionId, $uri);
        $client = new Transport($gateWayUrl . $uri, $this->username, $this->password);
        $response = $client->get();
        return $response;
    }
    
    public function refund(string $gateWayUrl, array $data = []){
        $uri = '/acceptor/rest/transactions/{instId}/{transactionId}/refund';
        if (!$this->transactionId){
            throw new \Exception('Transaction ID is not defined');
        }
        $uri = preg_replace("/{instId}/", $this->installationId, $uri);
        $uri = preg_replace("/{transactionId}/", $this->transactionId, $uri);
        $client = new Transport($gateWayUrl . $uri, $this->username, $this->password);
        $response = $client->post($data);
        return $response;
    }
    
    public function payment(string $gateWayUrl, array $data = []){
        $uri = '/acceptor/rest/transactions/{instId}/payment';
        $uri = preg_replace("/{instId}/", $this->installationId, $uri);
        $client = new Transport($gateWayUrl . $uri, $this->username, $this->password);
        $response = $client->post($data);
        return $response;
    }
    
}
