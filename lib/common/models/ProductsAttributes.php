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
use yii\db\ActiveQuery;

class ProductsAttributes extends ActiveRecord
{
    /**
     * set table name
     * @return string
     */
    public static function tableName()
    {
        return 'products_attributes';
    }
    
    public function getProductsOptions(){
        $languages_id = \Yii::$app->settings->get('languages_id');
        return $this->hasOne(ProductsOptions::className(), ['products_options_id' => 'options_id'])->where([ProductsOptions::tableName().".language_id" => (int)$languages_id]);
    }
    
    public function getProductsOptionsValues(){
        $languages_id = \Yii::$app->settings->get('languages_id');
        return $this->hasOne(ProductsOptionsValues::className(), ['products_options_values_id' => 'options_values_id'])->where([ProductsOptionsValues::tableName().".language_id" => (int)$languages_id]);
    }

    public function getSearchProductsOptions(){
        return $this->hasMany(ProductsOptions::className(), ['products_options_id' => 'options_id'])->andWhere([ProductsOptions::tableName().".display_search" => 1])
            ->select('products_options_id, language_id, products_options_name, type')->indexBy('language_id')
            ;
    }

    public function getSearchProductsOptionsValues(){
        return $this->hasMany(ProductsOptionsValues::className(), ['products_options_values_id' => 'options_values_id'])
            ->select('products_options_values_id, language_id, products_options_values_name')->indexBy('language_id')
            ;
    }
}