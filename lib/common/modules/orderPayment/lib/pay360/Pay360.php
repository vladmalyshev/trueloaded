<?php
namespace common\modules\orderPayment\lib\pay360;
/**
 * Description of Pay360
 * Php service to work with pay360 lib
 * @author A.Kosheliev
 */

use Yii;
use common\modules\orderPayment\lib\pay360\acceptors\AbstractAcceptor;

class Pay360 {
    
    private $session;
    private $transaction;
    private $customer;
    private $financialServices;
    private $paymentMethod;
    
    public $username;
    public $password;
            
    public function makePayment($installationId, $url){
        $data = [];
        foreach(['session', 'transaction', 'customer', 'financialServices'] as $collector){
            if (property_exists($this, $collector) && is_object($this->{$collector})){
                $data[$collector] = $this->{$collector}->getData();
            }
        }
        return (new Sessions($installationId, $this->username, $this->password))->payment($url, $data);
    }
    
    public function makeAcceptorPayment(AbstractAcceptor $acceptor, $installationId, $url){
        $data = [];
        foreach(['transaction', 'customer'] as $collector){
            if (property_exists($this, $collector) && is_object($this->{$collector})){
                $data[$collector] = $this->{$collector}->getData();
            }
        }
        $data['paymentMethod'] = $acceptor->getData();
        return (new Transactions($installationId, null, $this->username, $this->password))->payment($url, $data);
    }
    
    public function createAcceptor($name){
        $name = ucfirst($name);
        $class = __NAMESPACE__ ."\\acceptors\\".$name;
        if (class_exists($class)){
            return Yii::createObject([
                'class' =>$class,
            ]);
        }
        throw new \Exception('Invalid Acceptor: '. $name);
    }

    public function getPaymentStatus($installationId, $url, $sessionId){
        return (new Sessions($installationId, $this->username, $this->password))->status($url, $sessionId);
    }

    public function capturePayment($installationId, $url, $transactionId){
        //2Do: collect params to capture
        return (new Transactions($installationId, $transactionId, $this->username, $this->password))->capture($url);
    }

    public function findTransaction($installationId, $url, $transactionId){
        return (new Transactions($installationId, $transactionId, $this->username, $this->password))->find($url);
    }
    
    public function makeRefund($installationId, $url, $transactionId){
        $data = [];
        foreach(['transaction'] as $collector){
            if (property_exists($this, $collector) && is_object($this->{$collector})){
                $data[$collector] = $this->{$collector}->getData();
            }
        }
        return (new Transactions($installationId, $transactionId, $this->username, $this->password))->refund($url, $data);
    }

    public function session(){
        $this->session = new Session;
        return $this->session;
    }
    
    public function transaction($istallationId = null){
        $this->transaction = new Transaction($istallationId);
        return $this->transaction;
    }
    
    public function customer($customerId = null){
        $this->customer = new Customer($customerId);
        return $this->customer;
    }
    
    public function financialServices(){
        $this->financialServices = new FinancialServices();
        return $this->financialServices;
    }
}
