<?php

use yii\db\Migration;

use yii\db\Schema;
/**
 * Class m180710_120723_new_admin_messages
 */
class m180710_120723_new_admin_messages extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable('admin_messages', [
            'id' => Schema::TYPE_PK,
            'class' => $this->string(255),
            'message' => $this->text(),
            'status' => $this->string(64),
            'type' => $this->string(32),
            'date_added' => $this->dateTime()->notNull(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180710_120723_new_admin_messages cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180710_120723_new_admin_messages cannot be reverted.\n";

        return false;
    }
    */
}
