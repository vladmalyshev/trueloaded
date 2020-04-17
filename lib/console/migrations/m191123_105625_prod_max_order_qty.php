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
 * Class m191118_105625_zone_table_checkout_note
 */
class m191123_105625_prod_max_order_qty extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->isFieldExists('order_quantity_max', 'products')){
            $this->addColumn('products', 'order_quantity_max', $this->integer(11)->notNull()->defaultValue(0));
        }
        $this->addTranslation('admin/categories', [
            'TEXT_PRODUCTS_ORDER_QUANTITY_MAX' => 'Max order q-ty',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        if ($this->isFieldExists('order_quantity_max', 'products')){
            $this->dropColumn('products', 'order_quantity_max');
        }
        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191118_105625_zone_table_checkout_note cannot be reverted.\n";

        return false;
    }
    */
}
