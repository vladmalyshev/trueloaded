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
 * Class m191007_121745_recovery_cart_only_subscribed
 */
class m191007_121745_recovery_cart_only_subscribed extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
      $q1 = (new yii\db\Query())->from('configuration')->where(['configuration_key' => 'RCS_CHECK_NEWSLETTER'])->count();
      if ($q1 == 0) {
        $this->insert('configuration',[
            'configuration_title' => 'Send automated email to subscribed customers only',
            'configuration_key' => 'RCS_CHECK_NEWSLETTER',
            'configuration_value' => 'True',
            'configuration_description' => 'Send automated email to subscribed customers only',
            'configuration_group_id' => 6501,
            'set_function' => "tep_cfg_select_option(array('True', 'False'),",
            'date_added' => new \yii\db\Expression('NOW()'),
        ]);
      }

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
//        echo "m191007_121745_recovery_cart_only_subscribed cannot be reverted.\n";

//        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191007_121745_recovery_cart_only_subscribed cannot be reverted.\n";

        return false;
    }
    */
}
