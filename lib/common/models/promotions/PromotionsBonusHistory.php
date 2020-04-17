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
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "promotions_bonus_groups_description".
 *
 * @property int $promotions_bonus_history_id
 * @property int $customer_id
 * @property int $bonus_points_id
 * @property int $bonus_points_award
 * @property int $bonus_points_occasion
 * @property date $action_date 
 */

class PromotionsBonusHistory extends ActiveRecord {
    
    public static function tableName() {
        return 'promotions_bonus_history';
    }
    
    public static function primaryKey() {
        return ['promotions_bonus_history_id'];
    }
    
    public function rules() {
        return [
            [['customer_id', 'bonus_points_id'], 'required'],
            [['bonus_points_award', 'bonus_points_occasion'], 'default', 'value' => 0]
        ];
    }
    
    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['action_date'],
                    //ActiveRecord::EVENT_BEFORE_UPDATE => ['action_date'],
                ],              
                 'value' => new \yii\db\Expression('CURDATE()'),
            ],
        ];
    }
    
    public static function getTodayHistory(PromotionsBonusPoints $oAction, $customer_id){
        return self::find()->where('customer_id =:cid and bonus_points_id =:bid and action_date = CURDATE()', 
                    [':cid' => $customer_id, ':bid' => $oAction->bonus_points_id])->one();
    }
    
    public static function getFullHistoryAmount($customer_id){
        return self::find()->where('customer_id =:cid', 
                    [':cid' => $customer_id])->sum('bonus_points_award');
    }

    /*
    * update customer bonus history
    * return $addBonusAmount - updated bonus amount of action
    */    
    public static function updateHistory(PromotionsBonusPoints $oAction, $customer_id, $info){
        $addBonusAmount = 0;
        if ($customer_id && $oAction->bonus_points_id){
            $history = self::getTodayHistory($oAction, $customer_id);
            if (!$history){
                $history = new self();
                $history->setAttributes([
                   'bonus_points_id' => $oAction->bonus_points_id,
                   'customer_id' => (int)$customer_id,
                ]);
                $addBonusAmount = $oAction->bonus_points_award * $info['occasion'];
            } else {
                $not_saved = $info['occasion'] - $info['saved']; //legaly +1
                $can_be_saved = $info['limit'] - $history->bonus_points_occasion;
                if ($not_saved > $can_be_saved){ //may be cheating
                    $not_saved = $can_be_saved;
                }
                $info['occasion'] = $not_saved + $history->bonus_points_occasion;
                $addBonusAmount = $oAction->bonus_points_award * $not_saved;                
            }            
            
            if ($history){
                $history->bonus_points_award = $info['occasion'] * $oAction->bonus_points_award;
                $history->bonus_points_occasion = $info['occasion'];
                $history->save();
            }
        }
        return $addBonusAmount;
    }
   
}