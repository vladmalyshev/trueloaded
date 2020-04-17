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
 * Class m200219_115641_admin_order_editor_text
 */
class m200219_115641_admin_order_editor_text extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
      $this->addTranslation('admin/orders', [
        'IMAGE_SAVE_SESSION' => 'Save current session',
        'TEXT_TAB_ORDER_DETAILS' => 'Contact/Delivery/Payment details'
      ]);

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200219_115641_admin_order_editor_text cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200219_115641_admin_order_editor_text cannot be reverted.\n";

        return false;
    }
    */
}
