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

class m190712_192031_sub_product_update extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('admin/categories', [
            'TEXT_SUB_PRODUCT_PARENT_MARK_AS_MASTER' => 'Do you want the parent product not to show on the frontend (marked as master)?',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->removeTranslation('admin/categories', [
            'TEXT_SUB_PRODUCT_PARENT_MARK_AS_MASTER',
        ]);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190712_114531_product_source_update cannot be reverted.\n";

        return false;
    }
    */
}