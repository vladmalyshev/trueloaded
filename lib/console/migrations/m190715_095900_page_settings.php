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
 * Class m190715_095900_page_settings
 */
class m190715_095900_page_settings extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('admin/design', [
            'TEXT_NO_FILTERS' => 'No filters',
            'TEXT_HAS_ATTRIBUTES' => 'Has attributes',
            'TEXT_IS_BUNDLE' => 'Is bundle',
            'TEXT_FIRST_VISIT' => 'First visit',
            'TEXT_MORE_THEN_ONE_VISIT' => 'More then one visit',
            'TEXT_LOGGED_CUSTOMER' => 'Logged customer',
            'TEXT_NOT_LOGGED_CUSTOMER' => 'Not logged customer',
        ]);

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->removeTranslation('admin/design', [
            'TEXT_NO_FILTERS',
            'TEXT_HAS_ATTRIBUTES',
            'TEXT_IS_BUNDLE',
            'TEXT_FIRST_VISIT',
            'TEXT_MORE_THEN_ONE_VISIT',
            'TEXT_LOGGED_CUSTOMER',
            'TEXT_NOT_LOGGED_CUSTOMER',
        ]);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190715_095900_page_settings cannot be reverted.\n";

        return false;
    }
    */
}
