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
 * Class m191016_075938_np_add_cargo_description
 */
class m191016_075938_np_add_cargo_description extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $check = (new yii\db\Query())->from('configuration')->where(['configuration_key' => 'MODULE_NP_CARGO_DESCRIPTION'])->exists();
        if (!$check) {
            $this->insert('configuration', [
                'configuration_key' => 'MODULE_NP_CARGO_DESCRIPTION',
                'configuration_title' => 'Cargo Description',
                'configuration_description' => 'Default value cargo description (for Nova Poshta)',
                'configuration_group_id' => '7',
                'configuration_value' => '',
                'sort_order' => '0',
                'date_added' => (new yii\db\Expression('now()'))
            ]);
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        try {
            $this->delete('configuration',[
                'configuration_key' => 'MODULE_NP_CARGO_DESCRIPTION',
            ]);
        }catch (\Exception $e) {}
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191016_075938_np_add_cargo_description cannot be reverted.\n";

        return false;
    }
    */
}
