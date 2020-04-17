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
 * Class m191202_114209_index_giftwrap_messages
 */
class m191202_114209_index_giftwrap_messages extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if ($this->isTableExists('orders_giftwrap_messages')){
            $this->createIndex('idx_orders_id', 'orders_giftwrap_messages', 'orders_id');
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m191202_114209_index_giftwrap_messages cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191202_114209_index_giftwrap_messages cannot be reverted.\n";

        return false;
    }
    */
}
