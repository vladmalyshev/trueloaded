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

use yii\db\ActiveRecord;

class CustomersErrors extends ActiveRecord {

    public static function tableName() {
        return 'customers_errors';
    }

    public function getCustomer() {
        return $this->hasOne(Customers::className(), ['customers_id' => 'customers_id']);
    }
    
    public static function find() {
        return new queries\CustomersErrorsQuery(get_called_class());
    }
}
