<?php

use yii\db\Migration;

/**
 * Class m180619_130417_market_pace_field
 */
class m180619_130417_market_place_field extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('platforms', 'is_marketplace', $this->integer(1)->notNull()->defaultValue('0'));
        $this->db->createCommand("update platforms set is_marketplace = 1, is_virtual = 0 where is_virtual=1")
                ->execute();
        $this->addColumn('platforms', 'sattelite_platform_id', $this->integer()->notNull()->defaultValue('0'));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180619_130417_market_pace_field cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180619_130417_market_pace_field cannot be reverted.\n";

        return false;
    }
    */
}
