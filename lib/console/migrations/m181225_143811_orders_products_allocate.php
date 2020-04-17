<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

use common\classes\Migration;

/**
 * Class m181225_143811_orders_products_allocate
 */
class m181225_143811_orders_products_allocate extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        try {
            $this->createTable('orders_products_allocate', [
                'orders_products_id' => $this->integer(11)->notNull(),
                'warehouse_id' => $this->integer(11)->notNull(),
                'suppliers_id' => $this->integer(11)->notNull(),
                'location_id' => $this->integer(11)->notNull(),
                'platform_id' => $this->integer(11)->notNull(),
                'orders_id' => $this->integer(11)->notNull(),
                'prid' => $this->integer(11)->notNull(),
                'products_id' => $this->string(255)->notNull(),
                'allocate_received' => $this->integer(11)->notNull(),
                'allocate_dispatched' => $this->integer(11)->notNull()->defaultValue(0),
                'allocate_delivered' => $this->integer(11)->notNull()->defaultValue(0)
            ]);
            $this->addPrimaryKey('', 'orders_products_allocate', ['orders_products_id', 'warehouse_id', 'suppliers_id', 'location_id']);
            $this->createIndex('idx_orders_products_id', 'orders_products_allocate', 'orders_products_id');
            $this->createIndex('idx_warehouse_id', 'orders_products_allocate', 'warehouse_id');
            $this->createIndex('idx_suppliers_id', 'orders_products_allocate', 'suppliers_id');
            $this->createIndex('idx_location_id', 'orders_products_allocate', 'location_id');
            $this->createIndex('idx_platform_id', 'orders_products_allocate', 'platform_id');
            $this->createIndex('idx_orders_id', 'orders_products_allocate', 'orders_id');
            $this->createIndex('idx_prid', 'orders_products_allocate', 'prid');
            $this->createIndex('idx_products_id', 'orders_products_allocate', 'products_id');
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        /*try {
            $this->dropTable('orders_products_allocate');
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
        return true;*/

        echo "m181225_143811_orders_products_allocate cannot be reverted.\n";
        return false;
    }
}
