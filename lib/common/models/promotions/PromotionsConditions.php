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


class PromotionsConditions extends \yii\db\ActiveRecord {
    
    public static function tableName() {
        return 'promotions_conditions';
    }
    
    public static function primaryKey() {
        return ['promotions_conditions_id'];
    }
    
    public function rules() {
        return [
            [['promo_id', 'groups_id'], 'required' ],
            [['promo_deduction', 'promo_condition', 'promo_type'], 'default'],
            ['promo_amount', 'default', 'value' => 0],
            [['promo_limit', 'promo_limit_block'], 'default', 'value' => 0],
        ];
    }
    
    
}