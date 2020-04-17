<?php
namespace common\modules\orderPayment\lib\pay360;
/**
 * Transaction component for pay360 payment flow
 * @author A.Kosheliev
 */
class Transaction extends AbstractComponent {
    
    protected $merchantReference;
    protected $money;
    protected $description;
    protected $channel;
    protected $deferred;
    protected $recurring;
    protected $do3DSecure;
    
    protected $commerceType;
    protected $amount;
    protected $currency;
    protected $merchantRef;
    
    public function __construct($mRefId = null) {
        if (!is_null($mRefId)){
            $this->merchantReference = $mRefId;
        }
    }
    
    public function money(){
        if (!is_object($this->money)){
            $this->money = new Money;
        }
        return $this->money;
    }
    
    public function setDescription($desc){
        $this->description = $desc;
    }
    
    public function setCommerceType($type){
        if (in_array($type, ['ECOM', 'MOTO', 'CNP'])){
            $this->commerceType = $type;
        } else {
            throw new \Exception("Illegal commerce type: {$type}");
        }
    }
    
    public function setChannel($channel){
        if (in_array($channel, ['WEB', 'MOBILE', 'SMS', 'RETAIL', 'MOTO', 'IVR', 'OTHER'])){
            $this->channel = $channel;
        } else {
            throw new \Exception("Illegal channel: {$channel}");
        }
    }
    
    public function setDeferred(bool $isDeferred = true){
        $this->deferred = $isDeferred;
    }
    
    public function setRecurring(bool $recurring = false){
        $this->recurring = $recurring;
    }
    
    public function setDo3DSecure(bool $do3DSecure = true){
        $this->do3DSecure = $do3DSecure;
    }
    
    public function setAmount($amount = 0){
        if ($amount){
            $this->amount = $amount;
        }
    }
    
    public function setCurrency(string $currency){
        $this->currency = $currency;
    }
    
    public function setMerchantRef(string $merchantRef){
        $this->merchantRef = $merchantRef;
    }
    
}
