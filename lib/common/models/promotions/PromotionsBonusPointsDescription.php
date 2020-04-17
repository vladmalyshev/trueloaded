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
 * @property int $bonus_points_id
 * @property int $language_id
 * @property string $points_title
 */


class PromotionsBonusPointsDescription extends \yii\db\ActiveRecord {
    
    public static function tableName() {
        return 'promotions_bonus_points_description';
    }
    
    public static function primaryKey() {
        return ['bonus_points_id', 'language_id'];
    }
    
    public function rules() {
        return [
            [['bonus_points_id','language_id'], 'required'],
            ['points_title', 'default']
        ];
    }
    
    public static function create(PromotionsBonusPoints $bp, $language_id){
        if ($bp){
            $model = self::findOne(['bonus_points_id' => $bp->bonus_points_id, 'language_id' => $language_id]);
            if(!$model) {
                $model = new self();
                $model->bonus_points_id = $bp->bonus_points_id;
                $model->language_id = $language_id;
            }
            return $model;
        }
        return false;
    }

}