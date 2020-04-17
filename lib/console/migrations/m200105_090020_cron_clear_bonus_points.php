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
 * Class m200105_090020_cron_clear_bonus_points
 */
class m200105_090020_cron_clear_bonus_points extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->insert('configuration',[
            'configuration_title' => 'Rule To Clear Bonus Points',
            'configuration_description' => 'Clear Bonus Points Rule for Cron',
            'configuration_key' => 'BONUS_POINTS_CLEAR_RULE',
            'configuration_value' => 'Never',
            'configuration_group_id' => 100676,
            'date_added' => new \yii\db\Expression('NOW()'),
            'set_function' => 'tep_cfg_select_option(array(\'Never\', \'Yearly(1st Jan)\', \'Yearly(Registration)\'),'
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->delete('configuration', ['configuration_key' => 'BONUS_POINTS_CLEAR_RULE']);
    }
}
