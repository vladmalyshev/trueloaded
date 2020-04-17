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
 * Class m190711_161115_product_source
 */
class m190711_161115_product_source extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('suppliers_products', 'source', $this->string(32)->notNull()->defaultValue(''));
        $this->createIndex('suppliers_products_source','suppliers_products', ['source']);
        $this->addTranslation('admin/categories', [
            'TEXT_PRODUCT_SOURCE' => 'Source',
            'TEXT_CHOOSE_SUPPLIER_SOURCE' => 'Please type or choose supplier product source',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropIndex('suppliers_products_source','suppliers_products');
        $this->dropColumn('suppliers_products', 'source');
        $this->removeTranslation('admin/categories', [
            'TEXT_PRODUCT_SOURCE',
            'TEXT_CHOOSE_SUPPLIER_SOURCE',
        ]);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190711_161115_product_source cannot be reverted.\n";

        return false;
    }
    */
}
