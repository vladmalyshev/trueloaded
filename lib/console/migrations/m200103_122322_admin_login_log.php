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
 * Class m200103_122322_admin_login_log
 */
class m200103_122322_admin_login_log extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        try {
            $this->addTranslation('admin/admin-login-view', [
                'TABLE_HEADING_AGENT' => 'Agent',
                'TABLE_HEADING_DEVICE' => 'Device'
            ]);
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
        try {
            $this->addColumn('admin_login_log', 'all_device_id', $this->string(32)->notNull()->after('all_event'));
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
        try {
            $this->addColumn('admin_login_log', 'all_agent', $this->text()->notNull()->defaultValue('')->after('all_ip'));
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
        try {
            $this->db->createCommand("UPDATE `translation` SET `translation_value` = 'Login Security keys'"
                . " WHERE `translation_key` = 'TEXT_ADMIN_SESSION_VIEW' AND `translation_entity` = 'admin/main';"
            )->execute();
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
        try {
            $this->db->createCommand("UPDATE `translation` SET `translation_value` = 'Member login Security keys'"
                . " WHERE `translation_key` = 'HEADING_TITLE' AND `translation_entity` = 'admin/admin-session-view';"
            )->execute();
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200103_122322_admin_login_log cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200103_122322_admin_login_log cannot be reverted.\n";

        return false;
    }
    */
}
