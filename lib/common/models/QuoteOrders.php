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

class QuoteOrders extends ActiveRecord
{
    /**
     * set table name
     * @return string
     */
    public static function tableName()
    {
        return 'quote_orders';
    }

    /**
     * one-to-many
     * @return array
     */
    public function getQuoteOrdersProducts()
    {
        return $this->hasMany(QuoteOrdersProducts::className(), ['orders_id' => 'orders_id']);
    }

    /**
     * one-to-many
     * @return array
     */
    public function getQuoteOrdersTotals()
    {
        return $this->hasMany(QuoteOrdersTotal::className(), ['orders_id' => 'orders_id']);
    }

    /**
     * one-to-many
     * @return array
     */
    public function getQuoteOrdersStatusHistory()
    {
        return $this->hasMany(QuoteOrdersStatusHistory::className(), ['orders_id' => 'orders_id']);
    }

    /**
     * one-to-many
     * @return array
     */
    public function getQuoteOrdersProductsAttributes()
    {
        return $this->hasMany(QuoteOrdersProductsAttributes::className(), ['orders_id' => 'orders_id']);
    }
    
    public function getCustomer()
    {
        return $this->hasOne(Customers::className(), ['customers_id' => 'customers_id']);
    }
}
