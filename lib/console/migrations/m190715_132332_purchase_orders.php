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
 * Class m190715_132332_purchase_orders
 */
class m190715_132332_purchase_orders extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('admin/design', [
            'TEXT_PURCHASE_ORDERS_HISTORY' => 'Purchase orders history',
            'TEXT_PURCHASE_ORDER_DATA' => 'Purchase order data',
            'TEXT_PURCHASE_ORDER_PRODUCTS' => 'Purchase order products',
            'TEXT_PURCHASE_ORDER_SUBTOTAL' => 'Purchase order subtotal',
            'TEXT_PURCHASE_ORDER_HISTORY' => 'Purchase order history',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->removeTranslation('admin/design', [
            'TEXT_PURCHASE_ORDERS_HISTORY',
            'TEXT_PURCHASE_ORDER_DATA',
            'TEXT_PURCHASE_ORDER_PRODUCTS',
            'TEXT_PURCHASE_ORDER_SUBTOTAL',
            'TEXT_PURCHASE_ORDER_HISTORY',
        ]);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190715_132332_purchase_orders cannot be reverted.\n";

        return false;
    }
    */
}
