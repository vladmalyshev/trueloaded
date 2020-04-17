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
 * Class m190912_115318_sagepay_server_skip_3ds
 */
class m190912_115318_sagepay_server_skip_3ds extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
      $q = (new yii\db\Query())->from(TABLE_PLATFORMS_CONFIGURATION)->where(['configuration_key' => 'MODULE_PAYMENT_SAGE_PAY_3DS_SKIP'])->count();
      $q1 = (new yii\db\Query())->from(TABLE_PLATFORMS_CONFIGURATION)->where(['configuration_key' => 'MODULE_PAYMENT_SAGE_PAY_SERVER_STATUS'])->count();
      if ($q1 && $q == 0) {
        $this->insert(TABLE_PLATFORMS_CONFIGURATION, [
          'configuration_key' => 'MODULE_PAYMENT_SAGE_PAY_3DS_SKIP',
          'configuration_title' => 'Skip 3D secure amount',
          'configuration_description' => 'Skip 3D secure verification when paid by token on orders below',
          'configuration_group_id' => '6',
          'sort_order' => '100',
          'platform_id' => '0',
          'date_added' => (new yii\db\Expression('now()'))
        ]);

      }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
//        echo "m190912_115318_sagepay_server_skip_3ds cannot be reverted.\n";

//        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190912_115318_sagepay_server_skip_3ds cannot be reverted.\n";

        return false;
    }
    */
}
