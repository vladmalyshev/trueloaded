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
 * Class m190816_170212_quick_order_update
 */
class m190816_170212_quick_order_update extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
      $this->addTranslation('main', [
        'TEXT_ATTRIBUTES' => 'Options',
        //'TEXT_SUB_CATEGOR' => 'Category',
      ]);

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190816_170212_quick_order_update cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190816_170212_quick_order_update cannot be reverted.\n";

        return false;
    }
    */
}
