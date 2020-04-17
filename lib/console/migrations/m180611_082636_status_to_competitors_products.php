<?php

use yii\db\Migration;

/**
 * Class m180611_082636_status_to_competitors_products
 */
class m180611_082636_status_to_competitors_products extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('competitors_products', 'status', $this->integer(1)->notNull()->defaultExpression('1'));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180611_082636_status_to_competitors_products cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180611_082636_status_to_competitors_products cannot be reverted.\n";

        return false;
    }
    */
}
