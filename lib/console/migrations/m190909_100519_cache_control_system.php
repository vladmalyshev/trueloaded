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
 * Class m190909_100519_cache_control_system
 */
class m190909_100519_cache_control_system extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('admin/cache_control',[
            'TEXT_SYSTEM' => 'System cache',
            'TEXT_SYSTEM_WARNING' => 'System cache has been flushed'
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->removeTranslation('admin/cache_control',[
            'TEXT_SYSTEM',
            'TEXT_SYSTEM_WARNING',
        ]);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190909_100519_cache_control_system cannot be reverted.\n";

        return false;
    }
    */
}
