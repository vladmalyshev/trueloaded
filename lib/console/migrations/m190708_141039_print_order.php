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
 * Class m190708_141039_print_order
 */
class m190708_141039_print_order extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {

        $this->addTranslation('admin/main',[
            'TEXT_PRINT_ORDER' => 'Print order'
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190708_141039_print_order cannot be reverted.\n";

        return false;
    }
    */
}
