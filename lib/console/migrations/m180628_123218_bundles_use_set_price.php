<?php

use yii\db\Migration;

/**
 * Class m180628_123218_bundles_use_set_price
 */
class m180628_123218_bundles_use_set_price extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
     $this->addColumn('products', 'use_sets_discount', $this->integer(1)->notNull()->defaultValue('0'));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180628_123218_bundles_use_set_price cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180628_123218_bundles_use_set_price cannot be reverted.\n";

        return false;
    }
    */
}
