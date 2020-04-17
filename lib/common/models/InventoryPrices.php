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
 * This is the model class for table "inventory_prices".
 *
 * @property integer $inventory_id
 * @property string $products_id
 * @property integer $prid
 * @property integer $groups_id
 * @property integer $currencies_id
 * @property string $price_prefix
 * @property double $inventory_group_price
 * @property string $inventory_group_discount_price
 * @property double $inventory_full_price
 * @property string $inventory_discount_full_price
 */
class InventoryPrices extends ActiveRecord
{
    /**
     * set table name
     * @return string
     */
    public static function tableName()
    {
        return 'inventory_prices';
    }    
}