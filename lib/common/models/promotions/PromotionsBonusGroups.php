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
 * This is the model class for table "promotions_bonus_groups".
 *
 * @property int $bonus_groups_id
 * @property int $bonus_group_sort
 * @property int $bonus_group_code
 */

class PromotionsBonusGroups extends \yii\db\ActiveRecord {
    
    public function __construct($config = array()) {
        parent::__construct($config);
    }
    
    public static function tableName() {
        return 'promotions_bonus_groups';
    }
    
    public static function primaryKey() {
        return ['bonus_groups_id'];
    }
    
    public function rules() {
        return [
            ['bonus_group_code', 'required'],
            ['bonus_group_code', 'string', 'min' => 3],
            ['bonus_group_sort', 'default', 'value' => 0]
        ];
    }
    
    public static function create($code){
        $model = self::findOne(['bonus_group_code' => $code]);
        if(!$model) {
            $model = new self();
            $model->bonus_group_code = $code;
        }
        return $model;
    }
    
    public static function getGroupTitle($group, $language_id = 0){
        $languages_id = \Yii::$app->settings->get('languages_id');
        if (!$language_id) $language_id = $languages_id;
        $bgroup = PromotionsBonusGroups::findOne(['bonus_group_code' => $group]);
        if ($bgroup){
            $desc = PromotionsBonusGroupsDescription::findOne(['bonus_groups_id' => $bgroup->bonus_groups_id, 'language_id' => $language_id]);
            if ($desc){
                return $desc->bonus_group_title;
            }
        }
        return (new PromotionsBonusService)->getDefaultGroupTitle($group);
    }

}