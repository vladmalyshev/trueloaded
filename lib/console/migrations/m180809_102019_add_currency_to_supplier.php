<?php

use yii\db\Migration;

/**
 * Class m180809_102019_add_currency_to_supplier
 */
class m180809_102019_add_currency_to_supplier extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('suppliers', 'currencies_id', $this->integer()->notNull()->defaultValue(15));
        $this->addColumn('suppliers_products', 'currencies_id', $this->integer()->notNull());
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180809_102019_add_currency_to_supplier cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180809_102019_add_currency_to_supplier cannot be reverted.\n";

        return false;
    }
    */
}
