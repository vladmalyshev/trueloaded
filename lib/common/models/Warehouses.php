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
/**
 * This is the model class for table "warehouses".
 *
 * @property int $warehouse_id
 * @property string $warehouse_owner
 * @property string $warehouse_name
 * @property int $status
 * @property int $is_default 
 * @property int $is_store
 * @property string $warehouse_email_address
 * @property string $warehouse_telephone
 * @property string $warehouse_landline
 * @property int $sort_order
 * @property date $date_added
 * @property date $last_modified
 */
class Warehouses extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'warehouses';
    }
    
    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['date_added', 'last_modified'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['last_modified'],
                ],
                'value' => new \yii\db\Expression('NOW()'),
            ],
        ];
    }
    
    public function getAddress(){
        return $this->hasOne(WarehousesAddressBook::className(), ['warehouse_id' => 'warehouse_id']);
    }

    public function getWarehousePlatform(){
        return $this->hasOne(WarehousesPlatforms::className(), ['warehouse_id' => 'warehouse_id']);
    }

   
}
