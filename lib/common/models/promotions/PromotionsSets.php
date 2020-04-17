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
use common\models\promotions\PromotionsSetsConditions;

class PromotionsSets extends \yii\db\ActiveRecord {
    
    public static function tableName() {
        return 'promotions_sets';
    }
    
    public static function primaryKey() {
        return ['promo_sets_id'];
    }
    
    public function rules(){
        return [
            [['promo_quantity', 'promo_hash', 'promo_qindex', 'promo_nindex'], 'default', 'value' => 0],
        ];
    }
    
    
    public function attributeLabels() {
        return [
          'promo_quantity' => 'qty',
        ];
    }
    
    public function getSetsConditions(){
        return $this->hasMany(PromotionsSetsConditions::className(), ['promo_sets_id' => 'promo_sets_id']);
    }   
    
}