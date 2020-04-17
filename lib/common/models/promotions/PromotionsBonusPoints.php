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

/**
 * This is the model class for table "promotions_bonus_points_description".
 *
 * @property int $bonus_groups_id
 * @property string $bonus_points_code
 * @property int $bonus_points_award
 * @property int $bonus_points_limit
 * @property int $bonus_points_sort
 * @property int $bonus_points_status
 */

class PromotionsBonusPoints extends \yii\db\ActiveRecord {
    
    public function __construct($config = array()) {
        parent::__construct($config);
    }
    
    public static function getActionByCode($code){
        return static::find()->where(['bonus_points_code' => $code]);
    }
    
    public static function getEnabledActionByCode($code){
        return static::getActionByCode($code)->andWhere('bonus_points_status = 1');
    }
    
    public static function create($code){
        $model = static::getActionByCode($code)->with('description')->one();
        if(!$model) {
            $model = new static();
            $model->bonus_points_code = $code;
        }
        return $model;
    }
    
    public function getDescription(){
        $languages_id = \Yii::$app->settings->get('languages_id');
        return $this->hasOne(PromotionsBonusPointsDescription::className(), ['bonus_points_id' => 'bonus_points_id'])
                ->where('language_id = :lid', [':lid' => $languages_id]);
    }

    public static function tableName() {
        return 'promotions_bonus_points';
    }
    
    public static function primaryKey() {
        return ['bonus_points_id'];
    }    
    
    public function rules() {
        return [
            ['bonus_groups_id', 'required'],
            [['bonus_points_code'], 'string', 'min' => 3],
            [['bonus_points_code'], 'unique'],
            [['bonus_points_award', 'bonus_points_limit', 'bonus_points_sort'],'integer'],
            [['bonus_points_award', 'bonus_points_limit', 'bonus_points_sort'],'default', 'value' => 1],
            ['bonus_points_status', 'default', 'value' => 0]
        ];
    }
    
    public function attributeLabels() {
        return [
            'bonus_points_status' => '',
            'bonus_points_award' => '',
            'bonus_points_limit' => ''
        ];
    }


    public function getPointsTitle($language_id = 0){
        $languages_id = \Yii::$app->settings->get('languages_id');
        if (!$language_id) $language_id = $languages_id;
        $bpd = PromotionsBonusPointsDescription::findOne(['bonus_points_id' => $this->bonus_points_id, 'language_id' => $language_id]);
        if ($bpd){
            return $bpd->points_title;
        } else {
            return static::getDefaultActionTitle();
        }
    }
    
    public function getBonusPointsAward(){
        if (!empty($this->bonus_points_award)) {
            return $this->bonus_points_award;
        } else {
            return static::getDefaultActionAward();
        }        
    }
    
    public function getBonusDailyLimit(){
        if (!empty($this->bonus_points_limit)) {
            return $this->bonus_points_limit;
        } else {
            return static::getDefaultActionLimit();
        }        
    }
    
    public function getBonusPointsLimit(){
        return $this->getBonusPointsAward() * $this->getBonusDailyLimit();
    }
    
    public function getBonusPointsStatus(){
        if (!is_null($this->bonus_points_status)){
            return $this->bonus_points_status;
        }
        return false;
    }

}