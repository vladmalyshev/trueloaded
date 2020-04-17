<?php

use yii\db\Migration;

/**
 * Class m180531_093047_warehouses_products_reindex
 */
class m180531_093047_warehouses_products_reindex extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->dropPrimaryKey('', 'warehouses_products');
        $this->addPrimaryKey('', 'warehouses_products', ['warehouse_id', 'products_id', 'suppliers_id']);
        $this->createIndex('idx_supplier', 'warehouses_products', 'suppliers_id');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180531_093047_warehouses_products_reindex cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180531_093047_warehouses_products_reindex cannot be reverted.\n";

        return false;
    }
    */
}
