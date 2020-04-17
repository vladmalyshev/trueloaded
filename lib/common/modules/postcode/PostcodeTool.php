<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\modules\postcode;

use Yii;

class PostcodeTool {
    
    private $allowOnCheckout = false;
    
    private $allowOnCustomerEdit = false;
    
    private $allowOnOrderEdit = false;
    
    private $maxQueriesAllowed;
    
    private $searchType = 'inline';
    
    public static $cookieName = 'foli';
    
    public function __construct() {
        $this->allowOnCheckout = defined('PCA_PREDICT_ALLOW_PAGE_CHECKOUT') && PCA_PREDICT_ALLOW_PAGE_CHECKOUT == 'true';
        $this->allowOnCustomerEdit = defined('PCA_PREDICT_ALLOW_PAGE_CUSTOMER_EDIT') && PCA_PREDICT_ALLOW_PAGE_CUSTOMER_EDIT == 'true';
        $this->allowOnOrderEdit = defined('PCA_PREDICT_ALLOW_PAGE_ORDER_EDIT') && PCA_PREDICT_ALLOW_PAGE_ORDER_EDIT == 'true';
        $this->maxQueriesAllowed = defined('PCA_PREDICT_SERVICE_LIMIT') ? (int)PCA_PREDICT_SERVICE_LIMIT :0;
        $this->searchType = defined('POSTCODE_SEARCH_TYPE') ? POSTCODE_SEARCH_TYPE : $this->searchType;
    }

    public function getProvider(){
        if (defined('POSTCODE_PROVIDER')){
            $providerClass = __NAMESPACE__ . '\\'. POSTCODE_PROVIDER;
            if (class_exists($providerClass)){
                $reflection = new \ReflectionClass($providerClass);
                if ($reflection->isSubclassOf('\yii\base\Widget')){
                    return $providerClass;
                }
            }
        }
        return false;
    }
    
    public function getKey(){
        if (defined('PCA_PREDICT_SERVICE_KEY') && !empty(PCA_PREDICT_SERVICE_KEY)){
            return PCA_PREDICT_SERVICE_KEY;
        }
        return false;
    }
    
    public function getMaxQueriesAllowed(){
        if (\frontend\design\Info::isTotallyAdmin()){
            return 0;
        }
        return $this->maxQueriesAllowed;
    }
    
    public function getSearchType(){
        return $this->searchType;
    }
    
    protected function _drawWidget($model, $callback = ''){
        $provider = $this->getProvider();
        if ($provider && !$this->overLimited() && $this->getKey()){
            return $provider::widget([
                'model' => $model,
                'key' => $this->getKey(),
                'callback' => $callback,
                'maxAllowed' => $this->getMaxQueriesAllowed(),
                'searchType' => $this->getSearchType()]);
        }
    }

    public function drawCheckoutPostcodeHelper(\common\forms\AddressForm $model, string $callback = ''){
        if ($this->allowOnCheckout){
            return $this->_drawWidget($model, $callback);
        }
    }
    
    public function drawEditorPostcodeHelper(\common\forms\AddressForm $model, string $callback = ''){
        if ($this->allowOnOrderEdit){
            return $this->_drawWidget($model, $callback);
        }
    }
    
    public function drawAccountPostcodeHelper(\common\forms\AddressForm $model, string $callback = ''){
        if ($this->allowOnCustomerEdit){
            return $this->_drawWidget($model, $callback);
        }
    }
    
    public function overLimited(){
        if($this->_getCookieValue() > $this->getMaxQueriesAllowed()){
            return true;
        }
        return false;
    }
    
    private function _getCookieValue(){
        $usedTimes = 0;
        
        if ($this->getMaxQueriesAllowed()){
            $_cookie = null;
            $__cookie = substr(\common\helpers\System::getcookie(self::$cookieName), 64);
            $_cookie = unserialize($__cookie);
            $usedTimes = (int)$_cookie[1]??0;
            
            if (!Yii::$app->session->has(self::$cookieName)) {
                Yii::$app->session->set(self::$cookieName, $usedTimes);
            } elseif (Yii::$app->session->has(self::$cookieName) && $usedTimes < Yii::$app->session->get(self::$cookieName)){
                Yii::$app->session->set(self::$cookieName, Yii::$app->session->get(self::$cookieName) + 1);
            } else if (Yii::$app->session->has(self::$cookieName) && $usedTimes > Yii::$app->session->get(self::$cookieName)) {
                Yii::$app->session->set(self::$cookieName, $usedTimes);
            }
            
            $usedTimes = Yii::$app->session->get(self::$cookieName);
            
            $params = array_merge(\common\helpers\System::get_cookie_params(), 
                    ['name' => self::$cookieName, 'value' => $usedTimes, 'httpOnly' => false]);

            Yii::$app->response->cookies->add(new \yii\web\Cookie($params));
        }
        
        return $usedTimes;
    }
}
