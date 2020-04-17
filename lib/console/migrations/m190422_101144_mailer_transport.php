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
 * Class m190422_101144_mailer_transport
 */
class m190422_101144_mailer_transport extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {       
        $this->getDb()->createCommand("INSERT INTO `configuration` (`configuration_id`, `configuration_title`, `configuration_key`, `configuration_value`, `configuration_description`, `configuration_group_id`, `sort_order`, `last_modified`, `date_added`, `use_function`, `set_function`) VALUES (NULL, 'SMTP Mailer', 'SMTP_MAILER', '', 'SMTP Mailer', '12', '6', NULL, 'now()', '', 'tep_cfg_select_option(array(\'None\', \'Mandrill\'),')")->execute();
        $this->getDb()->createCommand("INSERT INTO `configuration` (`configuration_id`, `configuration_title`, `configuration_key`, `configuration_value`, `configuration_description`, `configuration_group_id`, `sort_order`, `last_modified`, `date_added`, `use_function`, `set_function`) VALUES (NULL, 'SMTP Mailer Mandrill ApiKey', 'MANDRILL_APIKEY', '', 'SMTP Mailer Mandrill ApiKey', '12', '7', NULL, 'now()', '', '')")->execute();
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->getDb()->createCommand("delete from configuration where configuration_key='SMTP_MAILER'")->execute();
        $this->getDb()->createCommand("delete from platforms_configuration where configuration_key='SMTP_MAILER'")->execute();
        $this->getDb()->createCommand("delete from configuration where configuration_key='MANDRILL_APIKEY'")->execute();
        $this->getDb()->createCommand("delete from platforms_configuration where configuration_key='MANDRILL_APIKEY'")->execute();

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190422_101144_mailer_transport cannot be reverted.\n";

        return false;
    }
    */
}
