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
 * Class m190422_162454_setup_order_status_bind
 */
class m190422_162454_setup_order_status_bind extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {

$this->getDb()->createCommand("
update `orders_status` set order_evaluation_state_id=50 WHERE order_evaluation_state_id=0 and orders_status_groups_id=5;
");

$this->getDb()->createCommand("
update `orders_status` set order_evaluation_state_id=30 WHERE order_evaluation_state_id=0 and orders_status_groups_id=4;
");

$this->getDb()->createCommand("
update `orders_status` set order_evaluation_state_id=10 WHERE order_evaluation_state_id=0 and orders_status_groups_id=2;
");

$this->getDb()->createCommand("
update `orders_status` set order_evaluation_state_id=1 WHERE order_evaluation_state_id=0 and orders_status_groups_id<6;
");



    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190422_162454_setup_order_status_bind no backup - just mark asa down";
       return true;

//        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190422_162454_setup_order_status_bind cannot be reverted.\n";

        return false;
    }
    */
}
