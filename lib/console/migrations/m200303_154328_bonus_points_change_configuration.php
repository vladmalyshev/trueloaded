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
 * Class m200303_154328_bonus_points_change_configuration
 */
class m200303_154328_bonus_points_change_configuration extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->update(
            'configuration',[
            'set_function' => 'inputWithChoice([\'' . \common\helpers\Points::class . '\',\'getOptions\'], ',
            ], [
                'configuration_key' => 'BONUS_POINT_CURRENCY_COEFFICIENT',
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->update(
            'configuration',[
            'set_function' => '',
        ], [
                'configuration_key' => 'BONUS_POINT_CURRENCY_COEFFICIENT',
            ]
        );
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200303_154328_bonus_points_change_configuration cannot be reverted.\n";

        return false;
    }
    */
}
