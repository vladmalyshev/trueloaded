<?php
namespace common\modules\orderPayment\lib\pay360\acceptors;
/**
 * Transaction component for pay360 payment flow
 * @author A.Kosheliev
 */
class Giropay extends AbstractAcceptor {
        
    public function getData(){
        return [
            'billingAddress' => $this->billingAddress,
            'giropay' => parent::getData(),
        ];
    }
    
    public function getDependencies(){
        return [
            'currencies' => ['EUR'],
            'countries' => ['DEU'],
            'minimalAmount' => 1
        ];
    }
    
}
