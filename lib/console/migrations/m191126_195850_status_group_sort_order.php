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
 * Class m191126_195850_status_group_sort_order
 */
class m191126_195850_status_group_sort_order extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
      if (!$this->isFieldExists('sort_order', 'orders_status_groups')) {
        $this->addColumn('orders_status_groups', 'sort_order', $this->integer(5)->notNull()->defaultValue(0));
      }

      $this->getDb()->createCommand("update  `orders_status_groups` set sort_order=orders_status_groups_id")->execute();

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
       // echo "m191126_195850_status_group_sort_order cannot be reverted.\n";

        //return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191126_195850_status_group_sort_order cannot be reverted.\n";

        return false;
    }
    */
}
