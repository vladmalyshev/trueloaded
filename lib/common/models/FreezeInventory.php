<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "freeze_inventory".
 *
 * @property int $inventory_id
 * @property string $products_id
 * @property int $prid
 * @property int $products_quantity
 * @property int $allocated_stock_quantity
 * @property int $temporary_stock_quantity
 * @property int $warehouse_stock_quantity
 * @property int $suppliers_stock_quantity
 * @property int $ordered_stock_quantity
 */
class FreezeInventory extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'freeze_inventory';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['inventory_id', 'products_id', 'prid'], 'required'],
            [['inventory_id', 'prid', 'products_quantity', 'allocated_stock_quantity', 'temporary_stock_quantity', 'warehouse_stock_quantity', 'suppliers_stock_quantity', 'ordered_stock_quantity'], 'integer'],
            [['products_id'], 'string', 'max' => 255],
            [['inventory_id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'inventory_id' => 'Inventory ID',
            'products_id' => 'Products ID',
            'prid' => 'Prid',
            'products_quantity' => 'Products Quantity',
            'allocated_stock_quantity' => 'Allocated Stock Quantity',
            'temporary_stock_quantity' => 'Temporary Stock Quantity',
            'warehouse_stock_quantity' => 'Warehouse Stock Quantity',
            'suppliers_stock_quantity' => 'Suppliers Stock Quantity',
            'ordered_stock_quantity' => 'Ordered Stock Quantity',
        ];
    }
}
