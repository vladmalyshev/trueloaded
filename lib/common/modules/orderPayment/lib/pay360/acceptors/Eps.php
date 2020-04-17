<?php
namespace common\modules\orderPayment\lib\pay360\acceptors;
/**
 * Transaction component for pay360 payment flow
 * @author A.Kosheliev
 */
class Eps extends AbstractAcceptor {
       
    public function getData(){
        return [
            'billingAddress' => $this->billingAddress,
            'eps' => parent::getData(),
        ];
    }
    
    public function getDependencies(){
        return [
            'currencies' => ['EUR'],
            'countries' => ['AUT'],
            'minimalAmount' => 1
        ];
    }
    
}
