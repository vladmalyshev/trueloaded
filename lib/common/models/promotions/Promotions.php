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
use common\models\promotions\PromotionsSets;
use common\models\promotions\PromotionsConditions;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

class Promotions extends ActiveRecord {
    
    public static function tableName() {
        return 'promotions';
    }
    
    public static function primaryKey() {
        return ['promo_id'];
    }
    
    public function load($data, $formName = null) {
        $data = $data[$formName]??$data;
        if (!isset($data['auto_push'])){
            $data['auto_push'] = 0;
        }
        return parent::load($data, $formName);
    }


    public function rules() {
        return [
            [['promo_code', 'promo_icon'], 'default', 'value' => ''],
            ['promo_label', 'default', 'value' => function(){ return TEXT_PRMOTION_TITLE . " " . date(DATE_FORMAT); }],
            [['promo_class', 'platform_id'], 'required'],
            [['promo_date_expired', 'promo_date_start'], 'default', 'value' => ''],
            ['promo_status', 'required', 'isEmpty' => function($value){ return 0;}],
            [['promo_type', 'uses_per_code', 'uses_per_customer', 'auto_push'], 'default', 'value' => 0],
        ];
    }
    
    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['date_added', 'last_modified'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['last_modified'],
                ],              
                 'value' => new \yii\db\Expression('NOW()'),
            ],
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_VALIDATE => ['promo_date_expired'],
                ],              
                'value' => function (){
                    if (!empty($this->promo_date_expired) && is_scalar($this->promo_date_expired)){
                        return  \common\helpers\Date::prepareInputDate($this->promo_date_expired, true);
                    }
                },
            ],
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_VALIDATE => ['promo_date_start'],
                ],
                'value' => function (){
                    if (!empty($this->promo_date_start) && is_scalar($this->promo_date_start)){
                        return \common\helpers\Date::prepareInputDate($this->promo_date_start, true);
                    }
                },
            ],
        ];
    }
    
    public function getDefaultLabel($attribute){
        
    }

    public function validateDate($attribute){
        try {
            if (!empty($this->$attribute)) {
                return \common\helpers\Date::prepareInputDate($this->$attribute);
            }
        } catch (\Exception $ex) {
          \Yii::warning($ex->getMessage() . ' ' . $ex->getTraceAsString());
        }
        
    }

    
    public function getSets(){
        return $this->hasMany(PromotionsSets::className(), ['promo_id' => 'promo_id']);
    }
    
    public function getConditions(){
        $customer_groups_id = (int) \Yii::$app->storage->get('customer_groups_id');
        return $this->hasMany(PromotionsConditions::className(), ['promo_id' => 'promo_id'])->where(['groups_id' => [(int)$customer_groups_id,0]])->orderBy('groups_id desc')->asArray();
    }
    
    public function getSetsConditions(){
        return $this->hasMany(PromotionsSetsConditions::className(), ['promo_id' => 'promo_id', 'promo_sets_id' => 'promo_sets_id'])->via('sets');
    }
    
    public function getCustomerCode($customer_id = 0){
        if ($customer_id){
            return $this->hasMany(PromotionsCustomerCodes::className(), ['promo_id' => 'promo_id'])
                    ->where('customer_id =:cid', [':cid' => (int)$customer_id]);
        } else {
            return $this->hasMany(PromotionsCustomerCodes::className(), ['promo_id' => 'promo_id']);
        }
    }
    
    public static function getPromotionByPromoCode($code){
        return self::find()->where('promo_code = :code', [':code' => $code]);
    }
    
    public function beforeDelete() {
        PromotionsSets::deleteAll('promo_id = :promo_id', [':promo_id' => $this->promo_id]);
        PromotionsConditions::deleteAll('promo_id = :promo_id', [':promo_id' => $this->promo_id]);
        PromotionsSetsConditions::deleteAll('promo_id = :promo_id', [':promo_id' => $this->promo_id]);
        PromotionsCustomerCodes::deleteAll('promo_id = :promo_id', [':promo_id' => $this->promo_id]);
        PromotionsAssignement::deleteAll('promo_id = :promo_id', [':promo_id' => $this->promo_id]);
        return parent::beforeDelete();
    }
    
    public function hasPromoCode(){
        return !empty($this->promo_code) == true;
    }
    
    public function isPromoCodeUnused(){
        if ($this->hasPromoCode() && PromotionsCustomerCodes::find()->where('promo_id =:id', [':id' => $this->promo_id])->count() > 0){
            return false;
        }
        return true;
    }
    
    public static function getCurrentPromotions($platform_id, $status = 1){
        $select = static::find()->where(['promo_status' => $status, 'platform_id' => $platform_id])
            ->andWhere('promo_date_expired >= now() or promo_date_expired = "0000-00-00" or promo_date_expired is null')
            ->andWhere('promo_date_start <= now() or promo_date_start = "0000-00-00" or promo_date_start is null')
            ->andWhere('promo_class <> "specials"')->andWhere("promo_code = ''")
            ->orderBy('promo_priority')->with('sets')->with('setsConditions')->with('conditions');
        
        if (Yii::$app->user->isGuest){
            $customer = new \common\components\Customer();
        } else {
            $customer = Yii::$app->user->getIdentity();
        }
        
        $registered = $customer->getPromoCodes();
        
        if ($registered){
            $select->orWhere(['in', 'promo_id', \yii\helpers\ArrayHelper::getColumn($registered, 'promo_id')]);
        }
        
        return $select;
    }
    
    public static function onlyPromotion($promo_class, $platform_id){
        return self::getCurrentPromotions($platform_id)->andWhere('promo_class = :class', ["class" => $promo_class]);
    }

    public static function getPromotionName($promo_id){
        static $cache = [];

        if ($cache[$promo_id]) {
            return $cache[$promo_id];
        }

        $promo = self::findOne($promo_id);

        $cache[$promo_id] = $promo->promo_label;

        return $promo->promo_label;
    }
        
}