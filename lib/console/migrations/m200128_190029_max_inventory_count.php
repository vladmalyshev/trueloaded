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
 * Class m200128_190029_max_inventory_count
 */
class m200128_190029_max_inventory_count extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->insert('configuration',[
            'configuration_title' => 'Max Inventory Count',
            'configuration_key' => 'MAX_INVENTORY_COUNT',
            'configuration_value' => '128',
            'configuration_description' => 'Maximum inventory count to show on product edit page (without filters).',
            'configuration_group_id' => 3,
            'sort_order' => 7777,
            'date_added' => new \yii\db\Expression('NOW()'),
            'use_function' => '',
            'set_function' => ''
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->delete( 'configuration', ['configuration_key' => 'MAX_INVENTORY_COUNT'] );
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200128_190029_max_inventory_count cannot be reverted.\n";

        return false;
    }
    */
}
