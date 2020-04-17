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

class ProductsStockIndicationText extends ActiveRecord
{
    /**
     * set table name
     * @return string
     */
    public static function tableName()
    {
        return 'products_stock_indication_text';
    }

    /**
     * one-to-one
     * @return object
     */
    public function getProductsStockIndication()
    {
        return $this->hasOne(ProductsStockIndication::className(), ['stock_indication_id' => 'stock_indication_id']);
    }
}