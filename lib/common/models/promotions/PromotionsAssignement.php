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

class PromotionsAssignement extends \yii\db\ActiveRecord {
    
    CONST OWNER_GROUP = 1;
    CONST OWNER_CUSTOMER = 2;
    
    public static function tableName() {
        return 'promotions_assignement';
    }
    
    public static function primaryKey() {
        return ['promo_assignement_id'];
    }

    public static function getPromoOwnerIds($promo_id, $owner_type)
    {
        static $last_call = [];
        $key = (int)$promo_id.'^'.(int)$owner_type;
        if ( !isset($last_call[$key]) ) {
            $promo_owner_id_array = \yii\helpers\ArrayHelper::map(self::find()
                ->where(['promo_id' => $promo_id, 'promo_owner_type' => $owner_type])
                ->select('promo_owner')
                ->asArray()
                ->all(), 'promo_owner','promo_owner');
            $promo_owner_id_array = array_values($promo_owner_id_array);
            $last_call[$key] = array_map('intval', $promo_owner_id_array);
        }
        return $last_call[$key];
    }
    
    public static function getPromoOwners($promo_id, $owner_type, $only = []){
        $oQuery = self::find()->where(['promo_id' => $promo_id, 'promo_owner_type' => $owner_type]);
        if ($only){
            $oQuery->andWhere(['in', 'promo_owner', $only]);
        }
        return $oQuery->all();
    }

    public static function deletePromoOwners($promo_id, $owner_type, $only = []){
        foreach(self::getPromoOwners($promo_id, $owner_type, $only) as $pOwner){
            $pOwner->delete();
        }
    }
    
    public static function addPromoOwners($promo_id, $owner_type, array $items){
        foreach($items as $item){
            $assign = new self();
            $assign->setAttributes([
                'promo_id' => $promo_id,
                'promo_owner' => $item,
                'promo_owner_type' => $owner_type,
            ], false);
            $assign->save();
        }
    }
    
    public function getPromotion(){
        return $this->hasOne(Promotions::className(), ['promo_id' => 'promo_id']);
    }

    public static function getOwnerPromo($owner_type, $owner){
        return self::find()->where(['promo_owner_type' => $owner_type, 'promo_owner' => $owner])->joinWith('promotion')->all();
    }

}