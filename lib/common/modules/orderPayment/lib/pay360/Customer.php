<?php
namespace common\modules\orderPayment\lib\pay360;
/**
 * Transaction component for pay360 payment flow
 * @author A.Kosheliev
 */
class Customer extends AbstractComponent {
    
    protected $registered;
    protected $identity = [];
    protected $details = [];
    protected $billingAddress;
    
    protected $displayName;
        
    public function __construct($customerId = null) {
        $this->registered = is_null($customerId) ? false : true;
    }
    
    public function setRegistered(bool $registered = true){
        $this->registered = $registered;
    }
    
    public function setIdentity($platformCustomerId = null, $merchantCustomerId = null){
        if ($platformCustomerId){
            $this->identity = [
                'platformCustomerId' => $platformCustomerId,
            ];
        } else {
            $this->identity = [
                'merchantCustomerId' => $merchantCustomerId,
            ];
        }
    }
    
    public function setName(string $name){
        $this->details['name'] = $name;
    }
    
    public function setTelephone(string $phone){
        $this->details['telephone'] = $phone;
    }
    
    public function setEmailAddress(string $emailAddress){
        $this->details['emailAddress'] = $emailAddress;
    }
    
    public function setIpAddress(string $ipAddress){
        $this->details['ipAddress'] = $ipAddress;
    }
    
    public function address(){
        if (is_null($this->details['address'])){
            $this->details['address'] = new CustomerAddress();
        }
        return $this->details['address'];
    }
    
    public function billingAddress(){
        if (is_null($this->billingAddress)){
            $this->billingAddress = new CustomerAddress();
        }
        return $this->billingAddress;
    }
    
    public function setDisplayName(string $name){
        $this->displayName = $name;
    }
    
}
