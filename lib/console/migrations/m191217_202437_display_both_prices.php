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
 * Class m191217_202437_display_both_prices
 */
class m191217_202437_display_both_prices extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $check = (new yii\db\Query())->from('configuration')->where(['configuration_key' => 'DISPLAY_BOTH_PRICES'])->exists();
        if (!$check) {
            $this->insert('configuration', [
                'configuration_key' => 'DISPLAY_BOTH_PRICES',
                'configuration_title' => 'Display Both Prices',
                'configuration_description' => 'Display Both Prices with and without Tax',
                'configuration_group_id' => '1',
                'configuration_value' => 'False',
                'use_function' => '',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'),',
                'sort_order' => '100',
                'date_added' => (new yii\db\Expression('now()'))
            ]);
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        //echo "m191217_202437_display_both_prices cannot be reverted.\n";
       // return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191217_202437_display_both_prices cannot be reverted.\n";

        return false;
    }
    */
}
