<?php

use yii\db\Migration;

/**
 * Class m180525_120012_add_supplier_to_warehouse_products
 */
class m180525_120012_add_supplier_to_warehouse_products extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('warehouses_products', 'suppliers_id', $this->integer()->notNull());
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180525_120012_add_supplier_to_warehouse_products cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180525_120012_add_supplier_to_warehouse_products cannot be reverted.\n";

        return false;
    }
    */
}
