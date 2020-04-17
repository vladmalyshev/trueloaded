<?php
namespace common\modules\orderPayment\lib\pay360;
/**
 * Transaction component for pay360 payment flow
 * @author A.Kosheliev
 */
class FinancialServices extends AbstractComponent{
    
    protected $dateOfBirth;
    protected $surname;
    protected $accountNumber;
    protected $postCode;
        
    /**
     * 
     * @param string $dateOfBirth in YYYYMMDD format
     */
    public function setDateOfBirth(string $dateOfBirth){
        try{
            if (checkdate(substr($dateOfBirth,4 ,2), substr($dateOfBirth,6 ,2), substr($dateOfBirth,0 ,4))){
                $this->dateOfBirth = $dateOfBirth;
            }
        } catch (Exception $ex) {

        } catch (Error $er){
            
        }
    }
    
    public function setSurname(string $surname){
        $this->surname = $surname;
    }
    
    public function setAccountNumber(string $accountNumber){
        $this->accountNumber = $accountNumber;
    }
    
    public function setPostcode(string $postCode){
        $this->postCode = $postCode;
    }
}
