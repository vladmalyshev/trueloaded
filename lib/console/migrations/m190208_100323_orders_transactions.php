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
use yii\db\Schema;

/**
 * Class m190208_100323_orders_transactions
 */
class m190208_100323_orders_transactions extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable('orders_transactions', [
            'orders_transactions_id' => $this->primaryKey(),
            'orders_id' => $this->integer(),
            'transaction_id' => $this->string(128),
            'payment_class' => $this->string(64),
            'transaction_amount' => $this->decimal(15, 2),
            'transaction_currency' => $this->string(3),
            'transaction_status' => $this->string(128),
            'comments' => $this->text(),
            'date_created' => $this->dateTime(),
            'admin_id' => $this->integer(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');
        $this->createIndex('idx_orders_id', 'orders_transactions', 'orders_id');
        $this->createTable('orders_transactions_children', [
            'orders_transactions_child_id' => $this->primaryKey(),
            'orders_transactions_id' => $this->integer(),
            'transaction_id' => $this->string(128),
            'transaction_amount' => $this->decimal(15, 2),
            'transaction_currency' => $this->string(3),
            'transaction_status' => $this->string(128),
            'comments' => $this->text(),
            'date_created' => $this->dateTime(),
            'admin_id' => $this->integer(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');
        $this->createIndex('idx_orders_transactions_id', 'orders_transactions_children', 'orders_transactions_id');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropIndex('idx_orders_id', 'orders_transactions');
        $this->dropTable('orders_transactions');
        $this->dropIndex('idx_orders_transactions_id', 'orders_transactions_children');
        $this->dropTable('orders_transactions_children');
        
        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190208_100323_orders_transactions cannot be reverted.\n";

        return false;
    }
    */
}
