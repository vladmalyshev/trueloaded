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
 * Class m190917_115115_sagepay_server_api_access
 */
class m190917_115115_sagepay_server_api_access extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
      $q1 = (new yii\db\Query())->from(TABLE_PLATFORMS_CONFIGURATION)->where(['configuration_key' => 'MODULE_PAYMENT_SAGE_PAY_SERVER_STATUS'])->count();
      if ($q1 ) {
        $q = (new yii\db\Query())->from(TABLE_PLATFORMS_CONFIGURATION)->where(['configuration_key' => 'MODULE_PAYMENT_SAGE_PAY_SERVER_ACCOUNT'])->count();
        if ($q == 0) {
          $this->insert(TABLE_PLATFORMS_CONFIGURATION, [
            'configuration_key' => 'MODULE_PAYMENT_SAGE_PAY_SERVER_ACCOUNT',
            'configuration_title' => 'Account login',
            'configuration_description' => 'Account login to get transaction details',
            'configuration_group_id' => '6',
            'sort_order' => '0',
            'platform_id' => '0',
            'date_added' => (new yii\db\Expression('now()'))
          ]);
        }
        $q = (new yii\db\Query())->from(TABLE_PLATFORMS_CONFIGURATION)->where(['configuration_key' => 'MODULE_PAYMENT_SAGE_PAY_SERVER_ACCOUNT_PASSWORD'])->count();
        if ($q == 0) {
          $this->insert(TABLE_PLATFORMS_CONFIGURATION, [
            'configuration_key' => 'MODULE_PAYMENT_SAGE_PAY_SERVER_ACCOUNT_PASSWORD',
            'configuration_title' => 'Account password',
            'configuration_description' => 'Account password to get transaction details',
            'configuration_group_id' => '6',
            'sort_order' => '0',
            'platform_id' => '0',
            'date_added' => (new yii\db\Expression('now()'))
          ]);
        }

      }

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
     //   echo "m190917_115115_sagepay_server_api_access cannot be reverted.\n";

       // return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190917_115115_sagepay_server_api_access cannot be reverted.\n";

        return false;
    }
    */
}
