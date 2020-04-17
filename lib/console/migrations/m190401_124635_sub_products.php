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
 * Class m190321_124635_sub_products
 */
class m190401_124635_sub_products extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('products', 'is_listing_product', $this->integer(11)->notNull()->defaultValue(1));
        $this->addColumn('products', 'parent_products_id', $this->integer(11)->notNull()->defaultValue(0));
        $this->addColumn('products', 'products_id_stock', $this->integer(11)->notNull()->defaultValue(0));
        $this->addColumn('products', 'products_id_price', $this->integer(11)->notNull()->defaultValue(0));
        $this->getDb()->createCommand(
            "UPDATE products SET products_id_stock=products_id, products_id_price=products_id"
        )->execute();
        $this->createIndex('parent_product','products', ['parent_products_id']);
        $this->createIndex('parent_stock_product','products', ['products_id_stock']);
        $this->createIndex('parent_price_product','products', ['products_id_price']);

        $this->addTranslation('admin/main',[
            'TEXT_MASTER_PRODUCT' => 'Master',
            'TEXT_LISTING_PRODUCT' => 'Listing',
            'TEXT_CHILD_PRODUCT' => 'Child product',
        ]);

        $this->addTranslation('admin/categories',[
            'BUTTON_CREATE_LISTING_PRODUCT' => 'Create listing product',
        ]);

        $this->insert('configuration',[
            'configuration_title' => 'Activate listing sub products?',
            'configuration_key' => 'LISTING_SUB_PRODUCT',
            'configuration_value' => 'False',
            'date_added' => new \yii\db\Expression('NOW()'),
            'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'),'
        ]);

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn('products', 'products_id_stock');
        $this->dropColumn('products', 'products_id_price');
        $this->dropColumn('products', 'parent_products_id');
        $this->dropColumn('products', 'is_listing_product');

        $this->removeTranslation('admin/main',[
            'TEXT_MASTER_PRODUCT',
            'TEXT_LISTING_PRODUCT',
            'TEXT_CHILD_PRODUCT',
        ]);
        $this->removeTranslation('admin/categories',[
            'BUTTON_CREATE_LISTING_PRODUCT',
        ]);
        $this->delete('configuration','configuration_key=:key',['key'=>'LISTING_SUB_PRODUCT']);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190312_124635_sub_products cannot be reverted.\n";

        return false;
    }
    */
}
