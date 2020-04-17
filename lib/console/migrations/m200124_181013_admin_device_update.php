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
 * Class m200124_181013_admin_device_update
 */
class m200124_181013_admin_device_update extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        try {
            $this->addTranslation('admin/admin-device-view', [
                'TABLE_HEADING_MEMBER' => 'Member'
            ]);
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
        try {
            $this->addTranslation('admin/admin-login-session-view', [
                'TABLE_HEADING_MEMBER' => 'Member'
            ]);
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200124_181013_admin_device_update cannot be reverted.\n";

        return false;
    }
    */
}
