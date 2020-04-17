<?php

namespace common\models;

use backend\models\forms\RecoverCartConfigForm;
use Yii;

/**
 * This is the model class for table "recover_cart_config".
 *
 * @property int $platform_id
 * @property int $enable_email_delivery
 * @property int $first_email_start
 * @property int $first_email_coupon_id
 * @property int $second_email_start
 * @property int $second_email_coupon_id
 * @property int $third_email_start
 * @property int $third_email_coupon_id
 */
class RecoverCartConfig extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'recover_cart_config';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['platform_id'], 'required'],
            [['platform_id', 'enable_email_delivery', 'first_email_start', 'first_email_coupon_id', 'second_email_start', 'second_email_coupon_id', 'third_email_start', 'third_email_coupon_id'], 'integer'],
            [['platform_id'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'platform_id' => 'Platform ID',
            'enable_email_delivery' => 'Enable Email Delivery',
            'first_email_start' => 'First Email Start',
            'first_email_coupon_id' => 'First Email Coupon ID',
            'second_email_start' => 'Second Email Start',
            'second_email_coupon_id' => 'Second Email Coupon ID',
            'third_email_start' => 'Third Email Start',
            'third_email_coupon_id' => 'Third Email Coupon ID',
        ];
    }


    public function edit(RecoverCartConfigForm $form){
	    foreach($form->attributes as $attribute => $value){
		    if($this->hasAttribute($attribute)){
			    $this->setAttribute($attribute, $value);
		    }
	    }
    }

    public static function getConfigForCart($platform_id)
    {
        static $loaded_config = [];
        if ( !isset($loaded_config[$platform_id]) ){
            $recoveryCartConfig = RecoverCartConfig::findOne(['platform_id' => $platform_id]);
            if ( $recoveryCartConfig ) {
                if ($recoveryCartConfig->third_email_coupon_id){
                    if (0 == Coupons::find()->where(['coupon_id' => $recoveryCartConfig->third_email_coupon_id, 'coupon_active' => 'Y', 'coupon_for_recovery_email' => 1])->count()){
                        $recoveryCartConfig->third_email_coupon_id = 0;
                    }
                }
                if ($recoveryCartConfig->second_email_coupon_id){
                    if (0 == Coupons::find()->where(['coupon_id' => $recoveryCartConfig->second_email_coupon_id, 'coupon_active' => 'Y', 'coupon_for_recovery_email' => 1])->count()){
                        $recoveryCartConfig->second_email_coupon_id = 0;
                    }
                }
                if ($recoveryCartConfig->first_email_coupon_id){
                    if (0 == Coupons::find()->where(['coupon_id' => $recoveryCartConfig->first_email_coupon_id, 'coupon_active' => 'Y', 'coupon_for_recovery_email' => 1])->count()){
                        $recoveryCartConfig->first_email_coupon_id = 0;
                    }
                }
            }
            $loaded_config[$platform_id] = $recoveryCartConfig;
        }
        return $loaded_config[$platform_id];
    }

    public static function create(RecoverCartConfigForm $form){
    	$conf = new static();
	    foreach($form->attributes as $attribute => $value){
		    if($conf->hasAttribute($attribute)){
			    $conf->setAttribute($attribute, $value);
		    }
	    }
	    return $conf;
    }


}
