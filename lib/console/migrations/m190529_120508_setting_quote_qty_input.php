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
 * Class m190529_120508_setting_quote_qty_input
 */
class m190529_120508_setting_quote_qty_input extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {

        $this->insert('configuration',[
            'configuration_title' => 'Show quantity input for quote product',
            'configuration_key' => 'SHOW_QUANTITY_INPUT_FOR_QUOTE_BUTTON',
            'configuration_value' => '',
            'configuration_description' => 'Show quantity input for quote product',
            'configuration_group_id' => 9,
            'sort_order' => 35,
            'date_added' => new \yii\db\Expression('NOW()'),
            'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'),'
        ]);

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190529_120508_setting_quote_qty_input cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190529_120508_setting_quote_qty_input cannot be reverted.\n";

        return false;
    }
    */
}
