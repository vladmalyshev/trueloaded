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
 * Class m190424_155803_stock_history_for_related
 */
class m190424_155803_stock_history_for_related extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $relation_type_tables = [
            'customers_basket',
            'orders_products',
            'tmp_orders_products',
            'purchase_orders_products',
            'quote_orders_products',
            'sample_orders_products',
            'stock_history'
        ];
        foreach( $relation_type_tables as $relation_type_table ) {
            $table = $this->db->schema->getTableSchema($relation_type_table, true);
            if( isset($table->columns['relation_type']) ) continue;

            $this->addColumn($relation_type_table,'relation_type',$this->string(32)->notNull()->defaultValue(''));
        }

        $this->addColumn('stock_history', 'parent_id', $this->integer(11)->null());
        $this->addColumn('stock_history', 'parent_products_model', $this->string(32)->null());
        $this->addColumn('stock_history', 'parent_products_name', $this->string(255)->null());
        $this->addTranslation('admin/main', [
            'TEXT_RELATION_TYPE' => 'Relation type',
            'TEXT_RELATION_TYPE_LINKED' => 'Linked Product',
            'TEXT_RELATION_TYPE_BUNDLE' => 'Bundle Product',
            'TEXT_RELATION_TYPE_SUB_PRODUCT' => 'Sub product child',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $relation_type_tables = [
            'customers_basket',
            'orders_products',
            'tmp_orders_products',
            'purchase_orders_products',
            'quote_orders_products',
            'sample_orders_products',
            'stock_history'
        ];
        foreach( $relation_type_tables as $relation_type_table ) {
            $table = $this->db->schema->getTableSchema($relation_type_table, true);
            if( !isset($table->columns['relation_type']) ) continue;

            $this->dropColumn($relation_type_table,'relation_type');
        }

        $this->dropColumn('stock_history', 'parent_id');
        $this->dropColumn('stock_history', 'parent_products_model');
        $this->dropColumn('stock_history', 'parent_products_name');

    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190424_155803_stock_history_for_related cannot be reverted.\n";

        return false;
    }
    */
}
