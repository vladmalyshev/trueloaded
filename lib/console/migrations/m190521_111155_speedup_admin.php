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
 * Class m190521_111155_speedup_admin
 */
class m190521_111155_speedup_admin extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createIndex('customers_info_date_account_created','customers_info',['customers_info_date_account_created']);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190521_111155_speedup_admin cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190521_111155_speedup_admin cannot be reverted.\n";

        return false;
    }
    */
}
