<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "purchase_orders_products".
 *
 * @property integer $orders_products_id
 * @property integer $orders_id
 * @property integer $products_id
 * @property string $products_model
 * @property string $products_name
 * @property string $products_price
 * @property string $final_price
 * @property string $products_tax
 * @property integer $products_quantity
 * @property integer $qty_cnld
 * @property integer $qty_rcvd
 * @property string $uprid
 * @property string $sets_array
 * @property integer $is_giveaway
 * @property string $gift_wrap_price
 * @property integer $gift_wrapped
 * @property integer $is_virtual
 * @property string $gv_state
 * @property string $overwritten
 * @property integer $packagings
 * @property integer $units
 * @property integer $packs
 * @property string $elements
 * @property string $template_uprid
 * @property string $parent_product
 * @property string $sub_products
 * @property string $bonus_points_cost
 * @property integer $orders_products_status
 * @property integer $products_quantity_recieved
 * @property integer $promo_id
 * @property integer $sort_order
 */
class PurchaseOrdersProducts extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'purchase_orders_products';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['orders_id', 'products_id', 'products_quantity', 'qty_cnld', 'qty_rcvd', 'is_giveaway', 'gift_wrapped', 'is_virtual', 'packagings', 'units', 'packs', 'orders_products_status', 'products_quantity_recieved', 'promo_id', 'sort_order'], 'integer'],
            [['products_name', 'uprid', 'sets_array', 'is_giveaway', 'overwritten', 'packs', 'elements', 'template_uprid', 'parent_product', 'sub_products'], 'required'],
            [['products_name', 'sets_array', 'gv_state', 'overwritten', 'elements', 'template_uprid', 'parent_product', 'sub_products'], 'string'],
            [['products_price', 'final_price', 'products_tax', 'gift_wrap_price', 'bonus_points_cost'], 'number'],
            [['products_model'], 'string', 'max' => 25],
            [['uprid'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'orders_products_id' => 'Orders Products ID',
            'orders_id' => 'Orders ID',
            'products_id' => 'Products ID',
            'products_model' => 'Products Model',
            'products_name' => 'Products Name',
            'products_price' => 'Products Price',
            'final_price' => 'Final Price',
            'products_tax' => 'Products Tax',
            'products_quantity' => 'Products Quantity',
            'qty_cnld' => 'Qty Cnld',
            'qty_rcvd' => 'Qty Rcvd',
            'uprid' => 'Uprid',
            'sets_array' => 'Sets Array',
            'is_giveaway' => 'Is Giveaway',
            'gift_wrap_price' => 'Gift Wrap Price',
            'gift_wrapped' => 'Gift Wrapped',
            'is_virtual' => 'Is Virtual',
            'gv_state' => 'Gv State',
            'overwritten' => 'Overwritten',
            'packagings' => 'Packagings',
            'units' => 'Units',
            'packs' => 'Packs',
            'elements' => 'Elements',
            'template_uprid' => 'Template Uprid',
            'parent_product' => 'Parent Product',
            'sub_products' => 'Sub Products',
            'bonus_points_cost' => 'Bonus Points Cost',
            'orders_products_status' => 'Orders Products Status',
            'products_quantity_recieved' => 'Products Quantity Recieved',
            'promo_id' => 'Promo ID',
            'sort_order' => 'Sort Order',
        ];
    }
}
