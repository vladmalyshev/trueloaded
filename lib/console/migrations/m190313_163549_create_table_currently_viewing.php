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
 * Class m190313_163549_create_table_currently_viewing
 */
class m190313_163549_create_table_currently_viewing extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable('currently_viewing', [
            'products_id' => $this->integer()->notNull(),
            'customers_ip' => $this->string()->notNull(),
            'last_click' => $this->timestamp()->notNull(),
        ]);

        $this->createIndex(
            'idx_products_id',
            'currently_viewing',
            ['products_id', 'customers_ip'],
            true
        );

        $this->createIndex(
            'idx_last_click',
            'currently_viewing',
            'last_click'
        );

        $this->addTranslation('admin/design', [
            'TEXT_ACTIVE_CUSTOMERS' => 'Active customers'
        ]);
        
        $this->addTranslation('catalog/product', [
            'PRODUCT_OTHER_PURCHASING' => '<b>%s</b> other customers added the product to shopping cart',
            'PRODUCT_OTHER_VIEWING' => '<b>%s</b> other customers are viewing the product right now',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
      $this->dropTable('currently_viewing');
      $this->removeTranslation('admin/design', 'TEXT_ACTIVE_CUSTOMERS');
      $this->removeTranslation('catalog/product', ['PRODUCT_OTHER_PURCHASING', 'PRODUCT_OTHER_VIEWING']);

    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190313_163549_create_table_currently_viewing cannot be reverted.\n";

        return false;
    }
    */
}
