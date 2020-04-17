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
/**
 * This is the model class for table "warehouses_to_platforms".
 *
 * @property int $warehouse_id
 * @property int $platform_id
 * @property int $status
 * @property int $sort_order 
 */
class WarehousesPlatforms extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'warehouses_to_platforms';
    }
   
}
