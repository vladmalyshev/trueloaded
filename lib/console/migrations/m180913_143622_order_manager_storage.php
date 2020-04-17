<?php

use yii\db\Migration;

/**
 * Class m180913_143622_order_manager_storage
 */
class m180913_143622_order_manager_storage extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable('data_storage', [
            'pointer' => $this->string(255)->notNull(),
            'data' => $this->text(),
            'date_modified' => $this->dateTime()
        ], 'engine=InnoDB DEFAULT CHARSET=utf8');
        $this->createIndex('idx_pointer', 'data_storage', 'pointer');
        $this->addPrimaryKey('', 'data_storage', 'pointer');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180913_143622_order_manager_storage cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180913_143622_order_manager_storage cannot be reverted.\n";

        return false;
    }
    */
}
