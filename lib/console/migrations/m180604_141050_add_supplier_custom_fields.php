<?php

use yii\db\Migration;

/**
 * Class m180604_141050_add_supplier_custom_fields
 */
class m180604_141050_add_supplier_custom_fields extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('suppliers', 'condition', $this->text()->notNull());
        $this->addColumn('suppliers', 'condition_description', $this->text()->notNull());
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180604_141050_add_supplier_custom_fields cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180604_141050_add_supplier_custom_fields cannot be reverted.\n";

        return false;
    }
    */
}
