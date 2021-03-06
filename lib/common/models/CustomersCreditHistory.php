<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */


namespace common\models;


use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

class CustomersCreditHistory extends ActiveRecord {

    public static function tableName() {
        return 'customers_credit_history';
    }
    
    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['date_added'],
                ],              
                 'value' => new \yii\db\Expression('NOW()'),
            ],
        ];
    }

}