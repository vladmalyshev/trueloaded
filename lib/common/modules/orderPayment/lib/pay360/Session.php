<?php
namespace common\modules\orderPayment\lib\pay360;
/**
 * Session component for pay360 payment flow
 * @author A.Kosheliev
 */
class Session extends AbstractComponent {
    
    protected $returnUrl;
    protected $cancelUrl;
    protected $transactionNotification;
    protected $skin;
    
    public function setReturnUrl(string $url){
        $this->returnUrl = [
            'url' => $url
        ];
    }
    
    public function setCancelUrl(string $url){
        $this->cancelUrl = [
            'url' => $url
        ];
    }
    
    public function setTransactionNotification(string $url, $format = 'REST_JSON'){
        $this->transactionNotification = [
            'url' => $url,
            'format' => $format
        ];
    }
    
    public function setSkin($id){
        $this->skin = $id;
    }
    
    
}
