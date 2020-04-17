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
 * Class m190628_181542_share_shopping_cart_config
 */
class m190628_181542_share_shopping_cart_config extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {

        $this->insert('configuration',[
            'configuration_title' => 'Customer Basket Share',
            'configuration_key' => 'SHOPPING_CART_SHARE',
            'configuration_value' => 'True',
            'configuration_description' => 'Share customer basket between frontends',
            'configuration_group_id' => 1,
            'sort_order' => 35,
            'date_added' => new \yii\db\Expression('NOW()'),
            'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'),'
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190628_181542_share_shopping_cart_config cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190628_181542_share_shopping_cart_config cannot be reverted.\n";

        return false;
    }
    */
}
