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
 * Class m190626_160919_sub_product_update
 */
class m190626_160919_sub_product_update extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('admin/categories',[
            'BUTTON_ATTACH_TO_PARENT_LISTING_PRODUCT' => 'Attach to Parent',
            'BUTTON_DETACH_LISTING_PRODUCT' => 'Detach from Parent',
            'TEXT_CONFIRM_DETACH_PRODUCT_S' => 'Do you confirm detaching product <b>%s</b> from parent <b>%s</b>?',
            'TEXT_SELECT_PARENT_PRODUCT_S' => 'Select parent product for <b>%s</b>',
            'TEXT_SELECT_PRODUCT' => 'Please select product',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->removeTranslation('admin/categories',[
            'BUTTON_ATTACH_TO_PARENT_LISTING_PRODUCT',
            'BUTTON_DETACH_LISTING_PRODUCT',
            'TEXT_CONFIRM_DETACH_PRODUCT_S',
            'TEXT_SELECT_PRODUCT',
        ]);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190626_160919_sub_product_update cannot be reverted.\n";

        return false;
    }
    */
}
