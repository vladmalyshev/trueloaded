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
 * Class m191210_150247_postcoder_limit
 */
class m191210_150247_postcoder_limit extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->insert('configuration',[
            'configuration_title' => 'Search limitation per session',
            'configuration_key' => 'PCA_PREDICT_SERVICE_LIMIT',
            'configuration_value' => '4',
            'configuration_description' => 'Use value upper then zero to limit search operation',
            'configuration_group_id' => 100680,
            'sort_order' => 48,
            'date_added' => new \yii\db\Expression('NOW()'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m191210_150247_postcoder_limit cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191210_150247_postcoder_limit cannot be reverted.\n";

        return false;
    }
    */
}
