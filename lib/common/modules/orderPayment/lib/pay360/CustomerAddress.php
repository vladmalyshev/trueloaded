<?php
namespace common\modules\orderPayment\lib\pay360;
/**
 * Transaction component for pay360 payment flow
 * @author A.Kosheliev
 */
class CustomerAddress extends AbstractComponent {
    
    protected $line1;
    protected $line2;
    protected $line3;
    protected $line4;
    protected $city;
    protected $region;
    protected $postcode;
    protected $countryCode;
    
    public function setLine1(string $line1){
        $this->line1 = $line1;
    }
    
    public function setLine2(string $line2){
        $this->line2 = $line2;
    }
    
    public function setLine3(string $line3){
        $this->line3 = $line3;
    }
    
    public function setLine4(string $line4){
        $this->line4 = $line4;
    }
    
    public function setCity(string $city){
        $this->city = $city;
    }
    
    public function setRegion(string $region){
        $this->region = $region;
    }
    
    public function setPostcode(string $postcode){
        $this->postcode = $postcode;
    }
    
    public function setCountryCode(string $countryCode){
        $this->countryCode = $countryCode;
    }
    
}
