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
 * Class m190716_151738_sub_product_update
 */
class m190716_151738_sub_product_update extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('admin/categories', [
            'TEXT_SUB_PRODUCT_CONNECTED_TO_PARENT' => 'Connected parent',
            'TEXT_SUB_PRODUCT_CONNECTED_CHILDREN' => 'Connected child products',
            'TEXT_SUB_PRODUCT_SEE_ALL_CHILDREN' => 'See all child products',
            'TEXT_SUB_PRODUCT_WITH_PRICE' => 'Child product with own price'
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->addTranslation('admin/categories', [
            'TEXT_SUB_PRODUCT_CONNECTED_TO_PARENT',
            'TEXT_SUB_PRODUCT_CONNECTED_CHILDREN',
            'TEXT_SUB_PRODUCT_SEE_ALL_CHILDREN',
            'TEXT_SUB_PRODUCT_WITH_PRICE',
        ]);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190716_151738_sub_product_update cannot be reverted.\n";

        return false;
    }
    */
}
