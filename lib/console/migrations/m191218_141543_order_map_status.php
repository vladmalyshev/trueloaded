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
 * Class m191218_141543_order_map_status
 */
class m191218_141543_order_map_status extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {		
		$this->insert('configuration',[
            'configuration_title' => 'Show map on order process',
			'configuration_description' => 'Show map on order process',
            'configuration_key' => 'SHOW_MAP_ORDER_PROCESS',
            'configuration_value' => 'True',
            'configuration_group_id' => '1',
            'date_added' => new \yii\db\Expression('NOW()'),
            'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'),'
        ]);
        
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m191218_141543_order_map_status cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191218_141543_order_map_status cannot be reverted.\n";

        return false;
    }
    */
}
