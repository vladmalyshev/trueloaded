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

class SampleOrdersProducts extends ActiveRecord
{
    /**
     * set table name
     * @return string
     */
    public static function tableName()
    {
        return 'sample_orders_products';
    }
    
    public function beforeDelete() {
        if ($this->orders_products_id){
            SampleOrdersProductsAttributes::deleteAll(['orders_products_id' => $this->orders_products_id]);
            SampleOrdersProductsDownload::deleteAll(['orders_products_id' => $this->orders_products_id]);
        }
        return parent::beforeDelete();
    }

}
