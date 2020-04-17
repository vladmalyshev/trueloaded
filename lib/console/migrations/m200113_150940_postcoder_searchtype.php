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
 * Class m200113_150940_postcoder_searchtype
 */
class m200113_150940_postcoder_searchtype extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->insert('configuration',[
            'configuration_title' => 'Postcode search type?',
            'configuration_key' => 'POSTCODE_SEARCH_TYPE',
            'configuration_value' => 'inline',
            'configuration_description' => 'Postcode search type',
            'configuration_group_id' => 100680,
            'date_added' => new \yii\db\Expression('NOW()'),
            'set_function' => 'tep_cfg_select_option(array(\'inline\', \'popup\'),'
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200113_150940_postcoder_searchtype cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200113_150940_postcoder_searchtype cannot be reverted.\n";

        return false;
    }
    */
}
