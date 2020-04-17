<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\models\promotions;

use Yii;
use yii\helpers\ArrayHelper;
use yii\base\Event;

class PromotionService {
    
    CONST MASTER_CATEGORY = 3;
    CONST MASTER_PRODUCT = 4;    
    CONST MASTER_PROPERTY = 7;
    CONST MASTER_PROPERTY_VALUE = 8;
    
    CONST SLAVE_PRODUCT = 0;
    CONST SLAVE_CATEGORY = 1;
    CONST SLAVE_MANUFACTURER = 2;
    CONST SLAVE_PROPERTY = 5;
    CONST SLAVE_PROPERTY_VALUE = 6;
    
    CONST SLAVE_COUNTRY = 9;
    CONST SLAVE_ZONE = 10;
 
    private static $promos = [
        /*'specials' => 
            [
                'class' => '\common\models\promotions\service\Specials',
            ],*/
        'specials_category' => [
                'class' => '\common\models\promotions\service\SpecialsCategory',
            ],
        'cart_discount' => [
                'class' => '\common\models\promotions\service\CartDiscount',
            ],
        'multidiscount' => [
            'class' => '\common\models\promotions\service\MultiDiscount',
            ],
        'nextdiscount' => [
            'class' => '\common\models\promotions\service\NextDiscount',
            ],
        'personalgift' => [
            'class' => '\common\models\promotions\service\PersonalGift',
            ],
        'freeshipping' => [
            'class' => '\common\models\promotions\service\FreeShipping',
            ]
        ];
    
    public static function getList( array $assign){
        $promos = self::$promos;
        try{
            foreach ($promos as $key => $promo){
                $service = new $promo['class'];
                $promos[$key]['description'] = $service->getDescription();
                $promos[$key]['key'] = $key;

            }            
        } catch (\Exception $ex) {

        }
        reset($assign);
        $key = key($assign);
        $value = current($assign);
        return ArrayHelper::map($promos, $key, $value);
    }
    
    public function __invoke($class) {
        $object = new self::$promos[$class]['class'];
        $object->useProperties = defined('PROPERTIES_IN_PROMOTIONS') && PROPERTIES_IN_PROMOTIONS == 'true';
        return $object;
    }
    
    public static function createMessage($message){
        
        if (!\frontend\design\Info::isAdmin()){
            try{
                $ref = new \ReflectionClass('\frontend\controllers\PromotionsController');
                if ($ref && $ref->getMethod('actionIndex')){
                    $message .= " please visit <a href='" . \yii\helpers\Url::to('promotions/index') . "'> promotions</a>";
                }
            } catch (\Exception $ex) {
                //
            }
        }
        
        $message = (!empty($message)?$message:TEXT_DEFAULT_DISCOUNT);
        Yii::$app->session->setFlash('promo-message', $message, true);
    }
    
    public static function getMessage(){        
        if (Yii::$app->session->has('promo-message')){
            return Yii::$app->session->getFlash('promo-message', '', true);
        }
    }
    
    public static function clearMessages(){
        Yii::$app->session->removeFlash('promo-message');
    }
    
    public static function generatePromoCode($salt="promo", $length = 8){
        $ccid = md5(uniqid("", $salt));
        $ccid .= md5(uniqid("", $salt));
        $ccid .= md5(uniqid("", $salt));
        $ccid .= md5(uniqid("", $salt));
        srand((double)microtime()*1000000);
        $random_start = @rand(0, (128-$length));
        $good_result = 0;
        while ($good_result == 0) {
          $id1=substr($ccid, $random_start,$length);
          if (Promotions::find()->where(['promo_code' => $id1])->count() == 0) $good_result = 1;
        }
        return $id1;
    }
    
    public static function setEventPromoCode(){
        
        \common\helpers\Translation::init('promotions');
        Yii::$app->on('promo-code', function($event){
            $promo = Promotions::getPromotionByPromoCode($event->sender->promo_code)->one();
            $response = [];
            if ($promo){
                $applied = $promo->getCustomerCode()->count();
                if ($promo->uses_per_code && $applied >= $promo->uses_per_code){
                    $response = ['message' => sprintf(PROMO_CODE_LIMIT_APPLIED, $promo->uses_per_code), 'title' => PROMO_TITLE_ERROR];
                } else {
                    $applied = $promo->getCustomerCode(\Yii::$app->user->getId())->one();
                    if (!$applied){
                        if (!Yii::$app->user->isGuest){
                            $customer = Yii::$app->user->getIdentity();
                        } else {
                            $customer = new \common\components\Customer();
                        }
                        if ($customer->applyPromoCode($event->sender->promo_code)){
                            $message = PROMO_CODE_APPLIED;
                            if (Yii::$app->session->hasFlash('promo-message')){
                                $message .= "<br>" . Yii::$app->session->getFlash('promo-message');
                            }
                            $response = ['message' => $message, 'title' => PROMO_TITLE_SUCCESS ];
                        }
                    } else {
                        //fail
                        $response = ['message' => PROMO_CODE_ALREADY_APPLIED, 'title' => PROMO_TITLE_ERROR];
                    }
                }
            } else {
                $response = ['message' => PROMO_CODE_INVALID, 'title' => PROMO_TITLE_ERROR];
            }
            
            Yii::$app->getResponse()->clearOutputBuffers();
            if (Yii::$app->request->isAjax){
                echo json_encode($response);
                exit;
            } else {
                return $response;
            }
        });
    }
        
    public static function checkEventPromoCode() {
        if (Yii::$app->request->isPost){
            $codeForm = new \frontend\forms\promotions\PromoCodeForm();
            if ($codeForm->load(Yii::$app->request->post()) && $codeForm->validate()){
                Yii::$app->trigger('promo-code', new Event(['sender' => $codeForm]));
            }
        }
    }
    
    public static function getPromoLinkAdmin($promo_id){
        if ($promo_id){
            $promo = \common\models\promotions\Promotions::findOne($promo_id);
            if ($promo){
                return TEXT_USED_PROMOTION . ': <a href="' . \yii\helpers\Url::to(['promotions/edit','platform_id' => $promo->platform_id, 'promo_id' => $promo->promo_id]). '" target="_blank">' . $promo->promo_label . '</a>';
            } else {
                return 'Promotion is not available more';
            }
        }
    }
    
    public static function triggerPriceActions($action = ''){
        if ($action == 'login'){
            self::checkActions('personalize');
        }
    }
    
    public static function isPersonalizedPromoToCustomer($promo_id){
        $is = true;
        if(Yii::$app->storage->has('customer_groups_id')){
            $assignedGroups = PromotionsAssignement::getPromoOwnerIds($promo_id, PromotionsAssignement::OWNER_GROUP);
            if (count($assignedGroups)>0){
                $is = false;
                /** @var \common\extensions\ExtraGroups\ExtraGroups $ext */
                if ($ext = \common\helpers\Acl::checkExtension('ExtraGroups', 'getOtherGroupsSelected')) {
                  $cg = $ext::getOtherGroupsSelected(Yii::$app->storage->get('customer_id'));
                }
                if (!is_array($cg)) {
                  $cg = [];
                }
                $cg[] = Yii::$app->storage->get('customer_groups_id');
                $inGroup = !empty(array_intersect($cg, $assignedGroups));
            }
        }
        
        if(Yii::$app->storage->has('customer_id')){
            $assignedCustomers = PromotionsAssignement::getPromoOwnerIds($promo_id, PromotionsAssignement::OWNER_CUSTOMER);
            if (count($assignedCustomers)>0){
                $is = false;
                $inCustomers = in_array(Yii::$app->storage->get('customer_id'), $assignedCustomers);
            }
        }
        
        return $inGroup || $inCustomers || $is;
    }
    
    public static function getAsset($promo_id, $uprid){
        if (\common\helpers\Product::hasAssets($uprid)){
            $oPromo = Promotions::findOne(['promo_id' => $promo_id]);
            if ($oPromo){
                $service = new self();
                $promo = $service($oPromo->promo_class);
                if (is_object($promo)){
                    if (method_exists($promo, 'getAssetData')){
                        return $promo->getAssetData($oPromo, $uprid);
                    }
                }
            }
        }
        return null;
    }
    
    private static function personalize($promo){
        $response = $promo->personalize();
        if ($response){
            PromotionsBonusNotify::setNotificationMessage($promo->getMessage());
        }
    }
    
    private static function personalCondition(\common\models\Customers $customer, service\ServiceInterface $promo, $promo_id){
        $conditions = $promo->getPersonalCondition($customer, $promo_id);
        if ($conditions){
            return $conditions;
        }
        return false;
    }

    private static function checkActions($action, $params = []){
        if (empty($action )) return false;
        $service = new self();
        foreach(\common\components\Salemaker::init() as $salemaker){
            if (self::isPersonalizedPromoToCustomer($salemaker['promo_id'])){
                $promo = $service($salemaker['class']);
                if (is_object($promo)){
                    if (method_exists($promo, $action)){
                        $salemaker['conditions']['master'] = $salemaker['master'];
                        $salemaker['conditions']['details'] = $salemaker['details'];
                        $salemaker['conditions']['promo_id'] = $salemaker['promo_id'];
                        $promo->load($salemaker['conditions']);
                        switch($action){
                            case 'personalize':
                                self::personalize($promo);
                                break;
                            case 'getPersonalCondition':
                                if ($params['customer']){
                                    $customer = $params['customer'];
                                    $response = self::personalCondition($customer, $promo, $salemaker['promo_id']);
                                    if ($response){
                                        self::personalize($promo);
                                    }
                                    return $response;
                                }
                                break;
                            case 'isFreeShipping':
                                return $promo->isFreeShipping($params);
                                break;
                        }
                    }
                }
            }
        }
        return false;
    }
    
    public static function getPesonalizedPromo($customer){
        if ($customer instanceof \common\components\Customer){
            return self::checkActions('getPersonalCondition', ['customer' => $customer]);
        }
        return false;
    }
    
    public static function confirmFreeShipping(\common\services\OrderManager $manager){
        return self::checkActions('isFreeShipping', ['manager' => $manager]);
    }
}