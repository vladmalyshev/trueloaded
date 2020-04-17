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
 * Class m190704_183344_quote_skip_shipping
 */
class m190704_183344_quote_skip_shipping extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {

        $this->insert('configuration',[
            'configuration_title' => 'Skip shipping estimate for quotations',
            'configuration_key' => 'QUOTE_SKIP_SHIPPING',
            'configuration_value' => 'False',
            'configuration_description' => 'Shipping selection won\'t be shown on quotation checkout page',
            'configuration_group_id' => 100683,
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
        echo "m190704_183344_quote_skip_shipping cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190704_183344_quote_skip_shipping cannot be reverted.\n";

        return false;
    }
    */
}
