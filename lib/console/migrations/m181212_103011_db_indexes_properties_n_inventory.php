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
 * Class m181212_103011_db_indexes_properties_n_inventory
 */
class m181212_103011_db_indexes_properties_n_inventory extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createIndex('properties_IDX','properties_to_products',['properties_id', 'products_id']);
        $this->createIndex('properties_values_IDX','properties_to_products',['values_id', 'products_id']);

        $this->createIndex('inventory_model_IDX','inventory', ['products_model(16)']);
        $this->createIndex('inventory_ean_IDX','inventory', ['products_ean']);
        $this->createIndex('inventory_upc_IDX','inventory', ['products_upc']);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropIndex('properties_IDX','properties_to_products');
        $this->dropIndex('properties_values_IDX','properties_to_products');

        $this->dropIndex('inventory_model_IDX','inventory');
        $this->dropIndex('inventory_ean_IDX','inventory');
        $this->dropIndex('inventory_upc_IDX','inventory');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m181212_103011_db_indexes_properties_n_inventory cannot be reverted.\n";

        return false;
    }
    */
}
