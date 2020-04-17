<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

use common\classes\Migration;

/**
 * Class m190215_153356_orders_splinters
 */
class m190215_153356_orders_splinters extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable('orders_splinters', [
            'splinters_id' => $this->primaryKey(),
            'orders_id' => $this->integer(),
            'splinters_status' => $this->string(64),
            'splinters_order' => $this->text(),
            'splinters_type' => $this->tinyInteger(),
            'admin_id' => $this->integer(),
            'date_added' => $this->dateTime(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');
        $this->createIndex('idx_orders_id', 'orders_splinters', 'orders_id');
        $this->createIndex('idx_splinters_type', 'orders_splinters', 'splinters_type');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropIndex('idx_orders_id', 'orders_splinters');
        $this->dropIndex('idx_splinters_type', 'orders_splinters');
        $this->dropTable('orders_splinters');

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190215_153356_orders_splinters cannot be reverted.\n";

        return false;
    }
    */
}
