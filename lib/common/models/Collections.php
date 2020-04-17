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

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

class Collections extends ActiveRecord
{
    /**
     * set table name
     * @return string
     */
    public static function tableName()
    {
        return 'collections';
    }
    
    public static function primaryKey() {
        return ['collections_id', 'language_id'];
    }


    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['date_added', 'date_last_modified'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['date_last_modified'],
                ],              
                 'value' => new \yii\db\Expression('NOW()'),
            ],
        ];
    }
    
    public function beforeDelete() {
        CollectionsProducts::deleteAll('collections_id =:id',[':id' => $this->collections_id]);
        CollectionsDiscountPrices::deleteAll('collections_id =:id',[':id' => $this->collections_id]);
        return parent::beforeDelete();
    }
    
    public function getProducts(){
        return $this->hasMany(CollectionsProducts::className(), ['collections_id' => 'collections_id']);
    }
    
}