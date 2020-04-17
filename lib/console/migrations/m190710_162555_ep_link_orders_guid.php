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
 * Class m190710_162555_ep_link_orders_guid
 */
class m190710_162555_ep_link_orders_guid extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {

      $this->addColumn('ep_holbi_soap_link_orders', 'remote_guid', $this->string(255)->notNull()->defaultValue(''));
      $this->addColumn('ep_holbi_soap_link_orders', 'remote_order_number', $this->string(100)->notNull()->defaultValue(''));
      try {
        $this->dropPrimaryKey('PRIMARY', 'ep_holbi_soap_link_orders');
      } catch (\Exception $e) {

      }
      $this->addPrimaryKey('idx_PRIMARY', 'ep_holbi_soap_link_orders', ['ep_directory_id', 'local_orders_id', 'remote_orders_id']);



    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
      $this->dropColumn('ep_holbi_soap_link_orders', 'remote_guid');
      $this->dropColumn('ep_holbi_soap_link_orders', 'remote_order_number');

        //echo "m190710_162555_ep_link_orders_guid cannot be reverted.\n";

        //return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190710_162555_ep_link_orders_guid cannot be reverted.\n";

        return false;
    }
    */
}
