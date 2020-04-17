<?php

use yii\db\Migration;

/**
 * Class m180810_153935_add_status_to_supplier_products
 */
class m180810_153935_add_status_to_supplier_products extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('suppliers_products', 'status' , $this->smallInteger(1)->defaultValue(1));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180810_153935_add_status_to_supplier_products cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180810_153935_add_status_to_supplier_products cannot be reverted.\n";

        return false;
    }
    */
}
