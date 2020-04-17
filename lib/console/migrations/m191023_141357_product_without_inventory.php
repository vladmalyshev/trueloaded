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
 * Class m191023_141357_product_without_inventory
 */
class m191023_141357_product_without_inventory extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('products', 'without_inventory', $this->integer(1)->notNull()->defaultValue(0));

        $this->insert('configuration',[
            'configuration_title' => 'New product without inventory?',
            'configuration_key' => 'PRODUCTS_INVENTORY_DEFAULT_DISABLED',
            'configuration_value' => 'False',
            'configuration_description' => 'Create new product without inventory by default',
            'configuration_group_id' => 1,
            'set_function' => "tep_cfg_select_option(array('True', 'False'),",
            'date_added' => new \yii\db\Expression('NOW()'),
        ]);

        $this->addTranslation('admin/categories', [
            'TEXT_INVENTORY_SWITCH_ON' => 'With inventory',
            'TEXT_INVENTORY_SWITCH_OFF' => 'Only attributes',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn('products', 'without_inventory');

        $this->delete('configuration', ['configuration_key' => 'PRODUCTS_INVENTORY_DEFAULT_DISABLED']);
        $this->delete('platforms_configuration', ['configuration_key' => 'PRODUCTS_INVENTORY_DEFAULT_DISABLED']);

        $this->removeTranslation('admin/categories', [
            'TEXT_INVENTORY_SWITCH_ON',
            'TEXT_INVENTORY_SWITCH_OFF',
        ]);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191023_141357_product_without_inventory cannot be reverted.\n";

        return false;
    }
    */
}
