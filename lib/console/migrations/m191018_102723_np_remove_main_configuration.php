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
 * Class m191018_102723_np_remove_main_configuration
 */
class m191018_102723_np_remove_main_configuration extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        try {
            $this->delete('configuration',['configuration_key' => 'MODULE_NP_SENDER_AREA_REF']);
            $this->delete('configuration',['configuration_key' => 'MODULE_NP_SENDER_CITY_REF']);
            $this->delete('configuration',['configuration_key' => 'MODULE_NP_SENDER_WAREHOUSE_REF']);
            $this->delete('configuration',['configuration_key' => 'MODULE_NP_CARGO_DESCRIPTION']);
        }catch (\Exception $e) {}    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m191018_102723_np_remove_main_configuration cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191018_102723_np_remove_main_configuration cannot be reverted.\n";

        return false;
    }
    */
}
