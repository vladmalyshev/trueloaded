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
use yii\behaviors\TimestampBehavior;

class CompetitorsProducts extends ActiveRecord
{
    /**
     * set table name
     * @return string
     */
    public static function tableName()
    {
        return 'competitors_products';
    }
    
    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['date_added', 'last_modified'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['last_modified'],
                ],              
                 'value' => new \yii\db\Expression('NOW()'),
            ],
        ];
    }
    
    public function getCompetitor(){
        return $this->hasOne(Competitors::className(), ['competitors_id' => 'competitors_id']);
    }
    
    public function getProduct(){
        return $this->hasOne(Products::className(), ['products_id' => 'products_id'])
                ->joinWith('taxRate tr');
    }
    
    public function getCurrency(){
        return $this->hasOne(Currencies::className(), ['code' => 'products_currency']);//->via('competitor'); if empty products_currency
    }


    public static function getTableFields(){
        return [
            'competitor_name'  => TEXT_COMPETITORS_NAME,
            'products_model' => TABLE_HEADING_PRODUCTS_MODEL,
            'products_name'  => TEXT_PRODUCTS_NAME,
            'products_url' =>   TEXT_URL,
            'products_url_short' =>    TEXT_URL_SHORT,
            'currency'  =>   TEXT_CURRENCY,
            'products_price'  =>  TEXT_LEGEND_PRICE,
            //'products_price_mask' =>  TEXT_PRICE_MASK,
            'last_modified' =>   TEXT_DATE_MODIFIED,
            'admin_id' =>   TABLE_HEADING_PROCESSED_BY
            ];
    }
}