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
 * Class m190513_145830_extend_weight_column
 */
class m190513_145830_extend_weight_column extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->db->createCommand(
            "ALTER TABLE products ".
            " CHANGE products_weight products_weight DECIMAL(6,3) NOT NULL DEFAULT '0.000', ".
            " CHANGE weight_cm weight_cm DECIMAL(6,3) NOT NULL DEFAULT '0.000', ".
            " CHANGE weight_in weight_in DECIMAL(6,3) NOT NULL DEFAULT '0.000', ".
            " CHANGE inner_weight_cm inner_weight_cm DECIMAL(6,3) NOT NULL DEFAULT '0.000', ".
            " CHANGE inner_weight_in inner_weight_in DECIMAL(6,3) NOT NULL DEFAULT '0.000', ".
            " CHANGE outer_weight_cm outer_weight_cm DECIMAL(6,3) NOT NULL DEFAULT '0.000', ".
            " CHANGE outer_weight_in outer_weight_in DECIMAL(6,3) NOT NULL DEFAULT '0.000' "
        )->execute();

        $this->db->createCommand(
            "ALTER TABLE inventory ".
            " CHANGE inventory_weight inventory_weight DECIMAL(6,3) NOT NULL DEFAULT '0.000' "
        )->execute();

        $this->db->createCommand(
            "ALTER TABLE options_templates_attributes ".
            " CHANGE products_attributes_weight products_attributes_weight DECIMAL(6,3) NOT NULL DEFAULT '0.000'"
        )->execute();

        $this->db->createCommand(
            "ALTER TABLE products_attributes ".
            " CHANGE products_attributes_weight products_attributes_weight DECIMAL(6,3) NOT NULL DEFAULT '0.000'"
        )->execute();

        foreach (['orders_products','purchase_orders_products','quote_orders_products','sample_orders_products','tmp_orders_products'] as $table) {
            $this->db->createCommand(
                "ALTER TABLE {$table} " .
                " CHANGE products_weight products_weight DECIMAL(6,3) NOT NULL DEFAULT '0.000'"
            )->execute();
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190513_145830_extend_weight_column cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190513_145830_extend_weight_column cannot be reverted.\n";

        return false;
    }
    */
}
