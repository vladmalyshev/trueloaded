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
 * Class m191211_180653_PO_translations
 */
class m191211_180653_PO_translations extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
      $this->addTranslation('admin/main', [
        'TEXT_NOT_AVAILABLE_ONLY' => 'not available only',
        'TEXT_CHOOSE_ORDERS' => 'select orders',
        'TEXT_BY_ORDERS' => 'By orders',
        'TEXT_BY_REORDER_LEVEL' => 'By re-order level',
      ]);
      if (!$this->isFieldExists('orders_number', 'purchase_orders')){
        $this->addColumn('purchase_orders', 'orders_number', $this->string(50));
      }

      $this->createIndex('idx_orders_status', 'orders', 'orders_status');

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        //echo "m191211_180653_PO_translations cannot be reverted.\n";

        //return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191211_180653_PO_translations cannot be reverted.\n";

        return false;
    }
    */
}
