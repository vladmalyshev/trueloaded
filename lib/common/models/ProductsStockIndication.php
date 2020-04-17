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

class ProductsStockIndication extends ActiveRecord
{
    /**
     * set table name
     * @return string
     */
    public static function tableName()
    {
        return 'products_stock_indication';
    }

    /**
     * one-to-many
     * @return array
     */
    public function getProductsStockIndicationText()
    {
        return $this->hasMany(ProductsStockIndicationText::className(), ['stock_indication_id' => 'stock_indication_id']);
    }

    /**
     * @param null $language_id
     * @return array|null|ActiveRecord
     */
    public function getCurrentText($language_id = null)
    {
        if(!is_null($language_id))
        {
            return static::find()
                ->joinWith('productsStockIndicationText', function($q) use ($language_id) {
                    $q->andWhere(['language_id' => $language_id]);
                })
                ->limit(1)
                ->one();
        }
        else
        {
            return null;
        }
    }

    /**
     * find default ProductsStockIndication
     * @return null|object
     */
    static public function getDefault()
    {
        return static::findOne(['is_default' => 1]);
    }
    
    public static function getHidden(){
        static $_cached = null;
        if (is_null($_cached)){
            $_cached = self::findAll(['is_hidden' => 1]);
        }
        return $_cached;
    }
    
    public function beforeDelete() {
        Products::updateAll(['stock_indication_id' => 0], ['stock_indication_id' => $this->stock_indication_id]);
        Inventory::updateAll(['stock_indication_id' => 0], ['stock_indication_id' => $this->stock_indication_id]);
        ProductsStockIndicationText::deleteAll(['stock_indication_id' => $this->stock_indication_id]);
        return parent::beforeDelete();
    }
}