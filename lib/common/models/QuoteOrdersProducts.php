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
use common\extensions\ProductDesigner\models as ProductDesignerORM;

class QuoteOrdersProducts extends ActiveRecord
{
    /**
     * set table name
     * @return string
     */
    public static function tableName()
    {
        return 'quote_orders_products';
    }
    
    public function beforeDelete() {
        if ($this->orders_products_id){
            QuoteOrdersProductsAttributes::deleteAll(['orders_products_id' => $this->orders_products_id]);
            QuoteOrdersProductsDownload::deleteAll(['orders_products_id' => $this->orders_products_id]);
        }
        return parent::beforeDelete();
    }

    /*
     * one-to-one
     * @return object
     */
    public function getQuoteOrder()
    {
        return $this->hasOne(QuoteOrders::className(), ['orders_id' => 'orders_id']);
    }

    /*
     * one-to-one
     * @return object
     */
    public function getProduct()
    {
        return $this->hasOne(Orders::className(), ['products_id' => 'products_id']);
    }

    /*
     * one-to-one
     * @return object
     */
    public function getInventory()
    {
        return $this->hasOne(Inventory::className(), ['products_id' => 'uprid']);
    }

    /*
     * one-to-one
     * @return object
     */
    public function getSuppliersProduct()
    {
        return $this->hasOne(SuppliersProducts::className(), ['uprid' => 'uprid']);
    }
}
