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
 * Class m190724_121830_ep_orders_link_primary
 */
class m190724_121830_ep_orders_link_primary extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
      try {
        $this->dropPrimaryKey('PRIMARY', 'ep_holbi_soap_link_orders');
        $this->dropPrimaryKey('idx_PRIMARY', 'ep_holbi_soap_link_orders');
      } catch (\Exception $e) {

      }
      $this->createIndex('idx_PRIMARY', 'ep_holbi_soap_link_orders', ['ep_directory_id', 'local_orders_id', 'remote_orders_id', 'cfg_export_as'], true);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190724_121830_ep_orders_link_primary cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190724_121830_ep_orders_link_primary cannot be reverted.\n";

        return false;
    }
    */
}
