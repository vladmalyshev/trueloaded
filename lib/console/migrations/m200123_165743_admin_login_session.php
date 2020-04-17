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
 * Class m200123_165743_admin_login_session
 */
class m200123_165743_admin_login_session extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        try {
            $this->removeTranslation('admin/main', [
                'TEXT_ADMIN_LOGIN_SESSION_VIEW'
            ]);
            $this->addTranslation('admin/main', [
                'TEXT_ADMIN_LOGIN_SESSION_VIEW' => 'Login sessions'
            ]);
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
        try {
            $this->removeTranslation('admin/admin-device-view', [
                'MESSAGE_ADMIN_DEVICE_BLOCK_CONFIRM'
            ]);
            $this->addTranslation('admin/admin-device-view', [
                'MESSAGE_ADMIN_DEVICE_BLOCK_CONFIRM' => 'Device blocking will automatically log off Member on this device!\nDo you really wish to block device?'
            ]);
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
        try {
            $this->removeTranslation('admin/admin-session-view', [
                'TEXT_ADMIN_SESSION_DELETE_CONFIRM',
                'TEXT_LOGIN_SECURITY_KEY_DELETE_CONFIRM'
            ]);
            $this->addTranslation('admin/admin-session-view', [
                'TEXT_LOGIN_SECURITY_KEY_DELETE_CONFIRM' => 'Do you really wish to delete login security key record?'
            ]);
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
        try {
            $this->removeTranslation('admin/admin-login-session-view', [
                'HEADING_TITLE',
                'TABLE_HEADING_DEVICE',
                'TABLE_HEADING_DATE_LOGIN',
                'TABLE_HEADING_DATE_ACTIVITY',
                'TEXT_BUTTON_DELETE',
                'TEXT_LOGIN_SESSION_DELETE_CONFIRM'
            ]);
            $this->addTranslation('admin/admin-login-session-view', [
                'HEADING_TITLE' => 'Member login sessions',
                'TABLE_HEADING_DEVICE' => 'Device Id',
                'TABLE_HEADING_DATE_LOGIN' => 'Date of logging in',
                'TABLE_HEADING_DATE_ACTIVITY' => 'Date of last activity',
                'TEXT_BUTTON_DELETE' => 'Delete',
                'TEXT_LOGIN_SESSION_DELETE_CONFIRM' => 'Login session deletion will automatically log off Member!\nDo you really wish to delete login session record?'
            ]);
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
        try {
            $this->db->createCommand("
                CREATE TABLE `admin_login_session` (
                    `als_admin_id` int(11) NOT NULL,
                    `als_device_id` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
                    `als_date_activity` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `als_date_login` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
                ALTER TABLE `admin_login_session`
                    ADD PRIMARY KEY (`als_admin_id`,`als_device_id`),
                    ADD KEY `als_admin_id` (`als_admin_id`),
                    ADD KEY `als_device_id` (`als_device_id`);
            ")->execute();
        } catch (\Exception $exc) {
            return false;
        }
        return true;
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
        echo "m200123_165743_admin_login_session cannot be reverted.\n";

        return false;
    }
    */
}
