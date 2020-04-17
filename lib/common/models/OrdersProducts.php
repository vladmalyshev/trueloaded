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


class OrdersProducts extends ActiveRecord
{
    /**
     * set table name
     * @return string
     */
    public static function tableName()
    {
        return 'orders_products';
    }
    
    public function beforeDelete() {
        if ($this->orders_products_id){
            OrdersProductsAttributes::deleteAll(['orders_products_id' => $this->orders_products_id]);
            OrdersProductsDownload::deleteAll(['orders_products_id' => $this->orders_products_id]);
        }
        return parent::beforeDelete();
    }

    /*
     * one-to-one
     * @return object
     */
    public function getOrder()
    {
        return $this->hasOne(Orders::className(), ['orders_id' => 'orders_id']);
    }

    /*
     * one-to-one
     * @return object
     */
    public function getProduct()
    {
        return $this->hasOne(Products::className(), ['products_id' => 'products_id']);
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

    public function getUpsell(){
		return $this->hasMany(ProductsUpsell::className(),['products_id' => 'products_id']);
    }

    public function getXsell(){
	    return $this->hasMany(ProductsXsell::className(),['products_id' => 'products_id']);
    }

    public function stockUpdateExtraParams()
    {
        $extraStockUpdateParams = [];
        if ( !empty($this->relation_type) ){
            $extraStockUpdateParams['relation_type'] = $this->relation_type;
        }
        if ( !empty($this->parent_product) ) {
            $parent_data = static::find()
                ->where(['orders_id'=>$this->orders_id,'products_id'=>(int)$this->parent_product])
                ->andWhere(['template_uprid'=>$this->parent_product])
                ->asArray()
                ->one();
            if (is_array($parent_data)) {
                $extraStockUpdateParams['parent_id'] = (int)$this->parent_product;
                $extraStockUpdateParams['parent_products_model'] = $parent_data['products_model'];
                $extraStockUpdateParams['parent_products_name'] = $parent_data['products_name'];
            }
        }

        return $extraStockUpdateParams;
    }

    public function getBackendDescription() {
      $languages_id = \Yii::$app->settings->get('languages_id');

      return $this->hasOne(\common\models\ProductsDescription::class, ['products_id' => 'products_id'])->via('product')->addSelect(['platform_id', 'products_id', 'language_id'])
                ->andOnCondition([\common\models\ProductsDescription::tableName() . '.language_id' => (int)$languages_id,
                         \common\models\ProductsDescription::tableName() . '.platform_id' => intval(\common\classes\platform::defaultId())
                  ])
          ;
    }

}
