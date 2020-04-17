<?php
namespace common\modules\orderPayment\lib\pay360\acceptors;
/**
 * Transaction component for pay360 payment flow
 * @author A.Kosheliev
 */
class Sofort extends AbstractAcceptor {
        
    public function getData(){
        return [
            'billingAddress' => $this->billingAddress,
            'sofort' => parent::getData(),
        ];
    }
    
    public function getDependencies(){
        return [
            'currencies' => ['EUR'],
            'countries' => ['AUT', 'BEL', 'CHE', 'DEU', 'ESP', 'ITA', 'NLD', 'POL'],
            'minimalAmount' => 1
        ];
    }
    
}
