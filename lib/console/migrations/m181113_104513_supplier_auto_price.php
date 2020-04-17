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
 * Class m181113_104513_supplier_auto_price
 */
class m181113_104513_supplier_auto_price extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $group = $this->db->createCommand("SELECT configuration_group_id FROM configuration_group WHERE configuration_group_title='Suppliers'")->queryOne();
        if ( is_array($group) ) {
            $this->db->createCommand(
                "INSERT INTO `configuration` (`configuration_title`, `configuration_key`, `configuration_value`, `configuration_description`, `configuration_group_id`, `sort_order`, `date_added`, `use_function`, `set_function`) ".
                "VALUES ".
                "('Price selection mode', 'SUPPLIER_UPDATE_PRICE_MODE', 'Manual', '', ".(int)$group['configuration_group_id'].", 1, now(), NULL, 'cfg_supplier_price_selection_mode(')")
                ->execute();
        }else{
            echo "Missing Suppliers config group\n";
            return false;
        }
        $this->addTranslation('admin/categories',[
            'TEXT_PRICE_BASED_ON_SUPPLIER_AUTO' => 'Auto',
        ]);
        $this->addTranslation('admin/main',[
            'TEXT_AUTO' => 'Automatically',
            'TEXT_MANUAL' => 'Manual',
        ]);

        $this->addColumn('products','supplier_price_manual', $this->boolean()->null());
        $this->addColumn('products_prices','supplier_price_manual', $this->boolean()->null());
        $this->addColumn('inventory','supplier_price_manual', $this->boolean()->null());
        $this->addColumn('inventory_prices','supplier_price_manual', $this->boolean()->null());
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->removeTranslation('admin/categories',[
            'TEXT_PRICE_BASED_ON_SUPPLIER_AUTO',
        ]);

        $this->dropColumn('products','supplier_price_manual');
        $this->dropColumn('products_prices','supplier_price_manual');
        $this->dropColumn('inventory','supplier_price_manual');
        $this->dropColumn('inventory_prices','supplier_price_manual');

        $this->db->createCommand("DELETE FROM configuration WHERE configuration_key='SUPPLIER_UPDATE_PRICE_MODE'")->execute();

    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m181113_104513_supplier_auto_price cannot be reverted.\n";

        return false;
    }
    */
}
