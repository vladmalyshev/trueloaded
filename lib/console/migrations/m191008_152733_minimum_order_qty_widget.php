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
 * Class m191008_152733_minimum_order_qty_widget
 */
class m191008_152733_minimum_order_qty_widget extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
      $this->addTranslation('main', [
        'TEXT_MIN_QTY_TO_PURCHASE' => 'Min q-ty',
        'TEXT_QTY_STEP_TO_PURCHASE' => 'Sold by',
      ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191008_152733_minimum_order_qty_widget cannot be reverted.\n";

        return false;
    }
    */
}
