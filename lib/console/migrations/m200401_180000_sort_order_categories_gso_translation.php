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
 * Class m200401_180000_sort_order_categories_gso_translation
 */
class m200401_180000_sort_order_categories_gso_translation extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
      $this->addTranslation('admin/main', [
        'TEXT_FROM_CONFIGURATION' => ' (from store settings)',
        'TEXT_MARKETING_SORT_ORDER' => 'Marketing (as specified in admin)'
      ]);

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200401_180000_sort_order_categories_gso_translation cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200401_180000_sort_order_categories_gso_translation cannot be reverted.\n";

        return false;
    }
    */
}
