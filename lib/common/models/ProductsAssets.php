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

class ProductsAssets extends ActiveRecord
{
    /**
     * set table name
     * @return string
     */
    public static function tableName()
    {
        return 'products_assets';
    }
    
    public function getAssetValues(){
        return $this->hasMany(ProductsAssetsValues::className(), ['products_assets_id' => 'products_assets_id']);
    }
    
    public function beforeDelete() {
        ProductsAssetsValues::deleteAll(['products_assets_id' => $this->products_assets_id]);
        return parent::beforeDelete();
    }
}