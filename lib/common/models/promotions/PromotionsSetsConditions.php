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

class PromotionsSetsConditions extends \yii\db\ActiveRecord {
    
    public static function tableName() {
        return 'promotions_sets_conditions';
    }
    
    public static function primaryKey() {
        return ['sets_id'];
    }
    
    public function rules() {
        $currencies = Yii::$container->get('currencies');
        return [
            ['promotions_sets_conditions_currency_id', 'default', 'value' => $currencies->currencies[DEFAULT_CURRENCY]['id']],
            ['promotions_sets_conditions_hash', 'default', 'value' => 0]
        ];
    }

}