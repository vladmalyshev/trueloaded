<?php
namespace common\modules\orderPayment\lib\pay360\acceptors;
/**
 * Transaction component for pay360 payment flow
 * @author A.Kosheliev
 */
use common\modules\orderPayment\lib\pay360\AbstractComponent;

abstract class AbstractAcceptor extends AbstractComponent{
    
    protected $accountHolderName;
    protected $returnUrl;
    protected $errorUrl;
    
    
    /**
     * return [
     *      'currencies' => ['EUR'],
     *      'countries' => ['AUT'],
     *      'minimalAmount' => 1.00
     * ]
     */
    abstract public function getDependencies();

    public function setAccountHolder(string $accountHolderName){
        $this->accountHolderName = $accountHolderName;
    }
    
    public function setReturnUrl(string $url){
        $this->returnUrl = $url;
    }
    
    public function setErrorUrl(string $url){
        $this->errorUrl = $url;
    }
    
    public function isAllowed(string $currency, string $country, float $totalAmount){
        try{
            $dependencies = $this->getDependencies();
            return in_array($currency, $dependencies['currencies']) && in_array($country, $dependencies['countries'])
                    && $totalAmount >= (float)$dependencies['minimalAmount'];
        } catch (\Exception $ex) {
            return false;
        }
    }
    
    public function setBillingCountry(string $iso3){
        $this->billingAddress = [
            'country' => $iso3
        ];
    }
        
}
