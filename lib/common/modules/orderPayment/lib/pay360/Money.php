<?php
namespace common\modules\orderPayment\lib\pay360;
/**
 * Transaction component for pay360 payment flow
 * @author A.Kosheliev
 */
class Money extends AbstractComponent {
    
    protected $currency;
    protected $amount;    
        
    public function setCurrency(string $currency){
        $this->currency = $currency;
    }
    
    public function setFixedAmount($amount){
        $this->amount = [
            'fixed' => $amount
        ];
    }
    
}
