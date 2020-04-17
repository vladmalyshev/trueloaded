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
 * Class m191028_141430_attributes_percents
 */
class m191028_141430_attributes_percents extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->alterColumn('products_attributes', 'price_prefix', $this->char(3)->notNull()->defaultValue('+'));
        $this->alterColumn('products_attributes', 'products_attributes_weight_prefix', $this->char(3)->notNull()->defaultValue('+'));
        $this->alterColumn('orders_products_attributes','price_prefix', $this->char(3)->notNull()->defaultValue('+'));
        $this->alterColumn('inventory','price_prefix', $this->char(3)->notNull()->defaultValue('+'));
        $this->alterColumn('inventory_prices','price_prefix', $this->char(3)->notNull()->defaultValue('+'));
        $this->alterColumn('options_templates_attributes','price_prefix', $this->char(3)->notNull()->defaultValue('+'));
        $this->alterColumn('options_templates_attributes','products_attributes_weight_prefix', $this->char(3)->notNull()->defaultValue('+'));
        if ($this->isTableExists('purchase_orders_products_attributes')) {
            $this->alterColumn('purchase_orders_products_attributes', 'price_prefix', $this->char(3)->notNull()->defaultValue('+'));
        }
        if ($this->isTableExists('quotation_products_attributes')) {
            $this->alterColumn('quotation_products_attributes','price_prefix', $this->char(3)->notNull()->defaultValue('+'));
        }
        if ($this->isTableExists('quote_orders_products_attributes')) {
            $this->alterColumn('quote_orders_products_attributes', 'price_prefix', $this->char(3)->notNull()->defaultValue('+'));
        }
        if ($this->isTableExists('sample_orders_products_attributes')) {
            $this->alterColumn('sample_orders_products_attributes', 'price_prefix', $this->char(3)->notNull()->defaultValue('+'));
        }
        if ($this->isTableExists('tmp_orders_products_attributes')) {
            $this->alterColumn('tmp_orders_products_attributes', 'price_prefix', $this->char(3)->notNull()->defaultValue('+'));
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m191028_141430_attributes_percents cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191028_141430_attributes_percents cannot be reverted.\n";

        return false;
    }
    */
}
