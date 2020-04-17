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
 * Class m191105_195410_orders_status_active
 */
class m191105_195410_orders_status_active extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
      $tbl = $this->getDb()->getTableSchema(TABLE_ORDERS_STATUS);

      if (!isset($tbl->columns['hidden'])) {
        $this->addColumn(TABLE_ORDERS_STATUS, 'hidden', $this->integer(1)->notNull()->defaultValue(0));
      }

      $this->update(TABLE_TRANSLATION, ['translation_entity' => 'admin/main'], ['translation_entity' => 'admin/categories', 'translation_key' => 'TEXT_INACTIVE']);

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
      //  echo "m191105_195410_orders_status_active cannot be reverted.\n";

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191105_195410_orders_status_active cannot be reverted.\n";

        return false;
    }
    */
}
