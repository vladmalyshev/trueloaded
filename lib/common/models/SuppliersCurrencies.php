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

class SuppliersCurrencies extends ActiveRecord
{
    /**
     * set table name
     * @return string
     */
    public static function tableName()
    {
        return 'suppliers_currencies';
    }
    
    public static function primaryKey() {
        return ['suppliers_id', 'currencies_id'];
    }
    
    public function getCurrencies(){
        return $this->hasOne(Currencies::className(), ['currencies_id' => 'currencies_id' ]);
    }


    public static function create($suppliers_id, $currencies_id){
        $sCurrency = self::findOne(['suppliers_id' => $suppliers_id, 'currencies_id' => $currencies_id]);
        if (!$sCurrency) {
            $sCurrency = new self();
            $sCurrency->suppliers_id = $suppliers_id;
            $sCurrency->currencies_id = $currencies_id;
        }
        return $sCurrency;
    }
    
    public function prepareData($data){
        if (!($this->suppliers_id || $this->currencies_id)){
            throw new \Exception('Currencies id and suppliers id are not defined');
        }
        
        $currency = Yii::$container->get('currencies');
        $rates = \yii\helpers\ArrayHelper::map($currency->currencies, 'id', 'value');
        
        $this->setAttributes([            
            'status' => (int)$data['status'],
            'use_custom_currency_value' => !(int)$data['use_default'],
            'currency_value' => !(int)$data['use_default'] ? (float)$data['custom_currency_value']: $rates[$this->currencies_id],
            'margin_value' => (float)$data['margin_value'],
            'margin_type' => $data['margin_type']
        ], false);
    }
}