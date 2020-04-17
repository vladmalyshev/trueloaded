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
 * Class m190402_121503_add_newsletter_config
 */
class m190402_121503_add_newsletter_config extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->getDb()->createCommand("INSERT INTO `configuration` (`configuration_title`, `configuration_key`, `configuration_value`, `configuration_description`, `configuration_group_id`, `set_function`) VALUES
('Newsletter Provider', 'NEWSLETTER_PROVIDER', 'Mailchimp', 'Newsletter Provider',0, 'tep_cfg_select_option(array(\'Mailchimp\', \'Mailup\',),');")->execute();
        
        $this->addTranslation('admin/main', [
                'TEXT_MAILUP_CLIENT_ID' => 'MailUp Client Id',
                'TEXT_MAILUP_CLIENT_SECRET_KEY' => 'MailUp Secret Key',
                'TEXT_MAILUP_USERNAME' => 'MailUp username',
                'TEXT_MAILUP_PASSWORD' => 'MailUp password'
            ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
         $this->getDb()->createCommand("delete from `configuration` where `configuration_key` = 'NEWSLETTER_PROVIDER' ")->execute();

        return trues;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190402_121503_add_newsletter_config cannot be reverted.\n";

        return false;
    }
    */
}
