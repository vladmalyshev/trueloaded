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

/*
 * suppliers_data entity
 * @suppliers_id
 * @email_address
 * @password
 */
class SuppliersAuthData extends \yii\db\ActiveRecord {
    
    public static function tableName() {
        return '{{%suppliers_auth_data}}';
    }
    
    public function getSupplier(){
        return $this->hasOne(Suppliers::className(), ['suppliers_id' => 'suppliers_id']);
    }
    
}