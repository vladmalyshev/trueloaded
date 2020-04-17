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
 * Class m191224_141853_display_billing_address_first
 */
class m191224_141853_display_billing_address_first extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $check = (new yii\db\Query())->from('configuration')->where(['configuration_key' => 'BILLING_FIRST'])->exists();
        if (!$check) {
            $this->insert('configuration', [
                'configuration_key' => 'BILLING_FIRST',
                'configuration_title' => 'Billing address is shown first',
                'configuration_description' => 'Billing address is shown first on checkout, so "same as" appears on shipping address',
                'configuration_group_id' => '1',
                'configuration_value' => 'False',
                'use_function' => '',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'),',
                'sort_order' => '101',
                'date_added' => (new yii\db\Expression('now()'))
            ]);
        }
        $this->addTranslation('main', ['SAME_AS_BILLING' => 'Same as billing']);
        $this->addTranslation('admin/main', ['SAME_AS_BILLING' => 'Same as billing']);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
     //   echo "m191224_141853_display_billing_address_first cannot be reverted.\n";

       // return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191224_141853_display_billing_address_first cannot be reverted.\n";

        return false;
    }
    */
}
