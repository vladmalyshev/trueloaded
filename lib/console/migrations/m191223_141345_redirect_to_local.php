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
 * Class m191223_141345_redirect_to_local
 */
class m191223_141345_redirect_to_local extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->insert('configuration',[
            'configuration_title' => 'Allow redirect customer to language locale?',
            'configuration_description' => 'Allow redirect customer to language locale?',
            'configuration_key' => 'ALLOW_LOCALE_REDIRECT',
            'configuration_value' => 'Browser',
            'date_added' => new \yii\db\Expression('NOW()'),
            'set_function' => 'tep_cfg_select_option(array(\'Browser\', \'Ip\', \'Disable\'),'
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->delete('configuration', ['configuration_key' => 'ALLOW_LOCALE_REDIRECT']);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191223_141345_redirect_to_local cannot be reverted.\n";

        return false;
    }
    */
}
