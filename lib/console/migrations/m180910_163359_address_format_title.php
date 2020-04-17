<?php

use yii\db\Migration;

/**
 * Class m180910_163359_address_format_title
 */
class m180910_163359_address_format_title extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('address_format', 'address_format_title', $this->string(64));
        $this->getDb()->createCommand("update address_format set address_format_title = concat('Untitled Format ', address_format_id) where address_format_title is null")->execute();
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180910_163359_address_format_title cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180910_163359_address_format_title cannot be reverted.\n";

        return false;
    }
    */
}
