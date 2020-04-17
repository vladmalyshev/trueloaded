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
 * This is the model class for table "promotions_bonus_groups_description".
 *
 * @property int $bonus_groups_id
 * @property int $bonus_group_sort
 */

class PromotionsBonusGroupsDescription extends \yii\db\ActiveRecord {
    
    public static function tableName() {
        return 'promotions_bonus_groups_description';
    }
    
    public static function primaryKey() {
        return ['bonus_groups_id'];
    }
    
    public function rules() {
        return [
            [['language_id', ], 'required'],
            ['bonus_group_title', 'string', 'min' => 3]
        ];
    }
    
    public static function create(PromotionsBonusGroups $group, $language_id){
        if ($group){
            $model = self::findOne(['bonus_groups_id' => $group->bonus_groups_id, 'language_id' => $language_id]);
            if(!$model) {
                $model = new self();
                $model->bonus_groups_id = $group->bonus_groups_id;
                $model->language_id = $language_id;
            }
            return $model;
        }
        return false;
    }

}