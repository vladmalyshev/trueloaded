<?php

use yii\db\Migration;

/**
 * Class m180601_160033_add_supplier_delivery_time
 */
class m180601_160033_add_supplier_delivery_time extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('suppliers', 'stock_delivery_terms_id', $this->integer()->notNull());
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180601_160033_add_supplier_delivery_time cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180601_160033_add_supplier_delivery_time cannot be reverted.\n";

        return false;
    }
    */
}
