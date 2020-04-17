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
 * Class m200325_162722_admin_sms_auth_to_my_store_config
 */
class m200325_162722_admin_sms_auth_to_my_store_config extends Migration
{
    /**
     * @inheritdoc`
     */
    public function safeUp()
    {
      $this->db->createCommand(
          "update configuration set configuration_group_id='BOX_CONFIGURATION_MYSTORE' where configuration_group_id='' and configuration_key in ('ADMIN_TWO_STEP_AUTH_SERVICE_SMS', 'ADMIN_TWO_STEP_AUTH_ENABLED', 'ADMIN_TWO_STEP_AUTH_SERVICE')"
          )->execute();
      $this->db->createCommand("
        update configuration set configuration_description='<span style=\"color:red\">Double check SMS service settings before using it. You won''t be able to access admin panel if it''s wrong.</span>' where configuration_key = 'ADMIN_TWO_STEP_AUTH_SERVICE'
          ")->execute();
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
       
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200325_162722_admin_sms_auth_to_my_store_config cannot be reverted.\n";

        return false;
    }
    */
}
