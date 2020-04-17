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
 * Class m191108_165126_add_props
 */
class m191108_165126_add_props extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->isFieldExists('props', 'customers_basket')){
            $this->addColumn('customers_basket', 'props', $this->text()->notNull()->defaultValue(''));
        }
        if (!$this->isFieldExists('props', 'customers_wishlist')){
            $this->addColumn('customers_wishlist', 'props', $this->text()->notNull()->defaultValue(''));
        }
        if (!$this->isFieldExists('props', 'orders_products')){
            $this->addColumn('orders_products', 'props', $this->text()->notNull()->defaultValue(''));
        }
        if (!$this->isFieldExists('props', 'purchase_orders_products')){
            $this->addColumn('purchase_orders_products', 'props', $this->text()->notNull()->defaultValue(''));
        }
        if (!$this->isFieldExists('props', 'quote_orders_products')){
            $this->addColumn('quote_orders_products', 'props', $this->text()->notNull()->defaultValue(''));
        }
        if (!$this->isFieldExists('props', 'sample_orders_products')){
            $this->addColumn('sample_orders_products', 'props', $this->text()->notNull()->defaultValue(''));
        }
        if (!$this->isFieldExists('props', 'tmp_orders_products')){
            $this->addColumn('tmp_orders_products', 'props', $this->text()->notNull()->defaultValue(''));
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m191108_165126_add_props cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191108_165126_add_props cannot be reverted.\n";

        return false;
    }
    */
}
