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
 * Class m190718_150807_settings_quote_price
 */
class m190718_150807_settings_quote_price extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('main', [
            'HIDE_ALL_ATTRIBUTES' => 'Hide all attributes',
        ]);

        $this->insert('configuration',[
            'configuration_title' => 'Show price for quote product',
            'configuration_key' => 'SHOW_PRICE_FOR_QUOTE_PRODUCT',
            'configuration_value' => '',
            'configuration_description' => 'Show price for quote product',
            'configuration_group_id' => 9,
            'sort_order' => 36,
            'date_added' => new \yii\db\Expression('NOW()'),
            'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'),'
        ]);

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->removeTranslation('main', [
            'HIDE_ALL_ATTRIBUTES',
        ]);

    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190718_150807_settings_quote_price cannot be reverted.\n";

        return false;
    }
    */
}
