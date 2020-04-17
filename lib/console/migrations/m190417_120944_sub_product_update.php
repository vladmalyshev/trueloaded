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
 * Class m190417_120944_sub_product_update
 */
class m190417_120944_sub_product_update extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // normalize prev created
        $this->getDb()->createCommand(
            "UPDATE products SET products_id_stock=products_id, products_id_price=products_id"
        )->execute();
        $this->getDb()->createCommand(
            "UPDATE products SET products_id_stock=parent_products_id, products_id_price=parent_products_id WHERE parent_products_id!=0"
        )->execute();

        $this->addColumn('products','sub_product_children_count', $this->integer(11)->notNull()->defaultValue(0)->after('is_listing_product'));

        $updateCounters = $this->getDb()->createCommand(
            "SELECT parent_products_id, COUNT(*) AS children_product_count ".
            "FROM products ".
            "WHERE parent_products_id>0 ".
            "GROUP BY parent_products_id"
        )->queryAll();
        foreach ($updateCounters as $updateCounter) {
            $this->getDb()->createCommand(
                "UPDATE products ".
                "SET sub_product_children_count='".$updateCounter['children_product_count']."' ".
                "WHERE products_id = '".$updateCounter['parent_products_id']."'"
            )->execute();
        }

        $this->addTranslation('admin/main',[
            'TEXT_PARENT_PRODUCT' => 'Parent product',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn('products','sub_product_children_count');
        $this->removeTranslation('admin/main',[
            'TEXT_PARENT_PRODUCT',
        ]);

    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190417_120944_sub_product_update cannot be reverted.\n";

        return false;
    }
    */
}
