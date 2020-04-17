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
 * Class m190313_205715_speedup
 */
class m190313_205715_speedup extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createIndex(
            'idx_currencies_id',
            'products_prices',
            'currencies_id'
        );
        $this->createIndex(
            'idx_groups_id',
            'products_prices',
            'groups_id'
        );

        $this->createIndex(
            'idx_tax_class_pri',
            'product_price_index',
            ['products_tax_class_id', 'products_status', 'groups_id', 'currencies_id']
        );
        
        $this->createIndex(
            'idx_products_status',
            'product_price_index',
            ['products_id', 'groups_id', 'currencies_id', 'products_status', 'products_price_min']
        );
        
        $this->createIndex(
            'products_id',
            'specials',
            ['products_id', 'status']
        );

        $this->createIndex(
            'idx_id_status',
            'products',
            ['products_id', 'products_status'],
            true
        );
        $this->createIndex(
            'idx_products_tax_class_status',
            'products',
            ['products_tax_class_id', 'products_status', 'stock_indication_id']
        );

        $this->createIndex(
            'idx_products_ordered_status',
            'products',
            ['products_ordered', 'products_status']
        );

        $this->createIndex(
            'idx_sort_id',
            'products_global_sort',
            ['sort_order', 'products_id']
        );

        $this->createIndex(
            'categories_id',
            'products_to_categories',
            ['categories_id', 'sort_order', 'products_id']
        );
        //delete  from filters  where filters_of = 'category' and filters_type='property' and  properties_id not in (select properties_id from properties)
        //delete  from filters  where filters_of = 'category' and filters_type='attribute' and  options_id not in (select distinct products_options_id from products_options)
//ALTER TABLE `vlad_massive`.`products` DROP INDEX `id_status`, ADD INDEX `id_status` (`products_status`, `stock_indication_id`) USING BTREE;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        //echo "m190313_205715_speedup cannot be reverted.\n";

        //return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190313_205715_speedup cannot be reverted.\n";

        return false;
    }
    */
}
