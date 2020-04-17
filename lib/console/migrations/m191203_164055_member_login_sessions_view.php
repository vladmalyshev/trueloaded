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
 * Class m191203_164055_member_login_sessions_view
 */
class m191203_164055_member_login_sessions_view extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        try {
            $this->addTranslation('admin/main', [
                'TEXT_ADMIN_SESSION_VIEW' => 'Login sessions'
            ]);
            $this->addTranslation('admin/admin-session-view', [
                'HEADING_TITLE' => 'Member login sessions',
                'TABLE_HEADING_COMPUTER' => 'Computer Id',
                'TABLE_HEADING_DATE_EXPIRE' => 'Expiration date',
                'TABLE_HEADING_DATE_CREATE' => 'Creation date',
                'TEXT_BUTTON_DELETE' => 'Delete',
                'TEXT_ADMIN_SESSION_DELETE_CONFIRM' => 'Do you really wish to delete login session record?'
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
        echo "m191203_164055_member_login_sessions_view cannot be reverted.\n";

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191203_164055_member_login_sessions_view cannot be reverted.\n";

        return false;
    }
    */
}
