<?php
namespace common\modules\orderPayment\lib\pay360\acceptors;
/**
 * Transaction component for pay360 payment flow
 * @author A.Kosheliev
 */
class Ideal extends AbstractAcceptor {
        
    public function getData(){
        return [
            'billingAddress' => $this->billingAddress,
            'ideal' => parent::getData(),
        ];
    }
    
    public function getDependencies(){
        return [
            'currencies' => ['EUR'],
            'countries' => ['NLD'],
            'minimalAmount' => 0.01
        ];
    }
    
}
