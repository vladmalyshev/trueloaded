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
 * Class m190917_100020_speedup_orders_list
 */
class m190917_100020_speedup_orders_list extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createIndex('admin_id_idx','orders',['admin_id']);
        $this->createIndex('payment_method_idx','orders',['payment_method']);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropIndex('admin_id_idx','orders');
        $this->dropIndex('payment_method_idx','orders');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190917_100020_speedup_orders_list cannot be reverted.\n";

        return false;
    }
    */
}
