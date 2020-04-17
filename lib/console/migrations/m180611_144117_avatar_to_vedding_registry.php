<?php

use yii\db\Migration;

/**
 * Class m180611_144117_avatar_to_vedding_registry
 */
class m180611_144117_avatar_to_vedding_registry extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('wedding_registry', 'avatar', $this->string(128)->notNull());
        $this->addColumn('wedding_registry', 'banner', $this->string(128)->notNull());
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180611_144117_avatar_to_vedding_registry cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180611_144117_avatar_to_vedding_registry cannot be reverted.\n";

        return false;
    }
    */
}
