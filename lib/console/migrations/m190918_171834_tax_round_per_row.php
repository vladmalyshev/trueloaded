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
 * Class m190918_171834_tax_round_per_row
 */
class m190918_171834_tax_round_per_row extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
      $q1 = (new yii\db\Query())->from('configuration')->where(['configuration_key' => 'TAX_ROUND_PER_ROW'])->count();
      if ($q1 == 0) {
        $this->insert('configuration',[
            'configuration_title' => 'Round tax per row',
            'configuration_key' => 'TAX_ROUND_PER_ROW',
            'configuration_value' => 'False',
            'configuration_description' => 'On total tax calculation round each row (product) before sum',
            'configuration_group_id' => 1,
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
//        echo "m190918_171834_tax_round_per_row cannot be reverted.\n";

//        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190918_171834_tax_round_per_row cannot be reverted.\n";

        return false;
    }
    */
}
