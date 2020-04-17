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
 * Class m190926_102124_change_np_shipping_param
 */
class m190926_102124_change_np_shipping_param extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {

        if ($this->isTableExists('shipping_np_order_params')) {
            $this->db->createCommand('
                DROP TABLE `shipping_np_order_params`;
            ')->execute();
        }
        $this->db->createCommand('
            CREATE TABLE `shipping_np_order_params` (
                `orders_id` INT(11) NOT NULL,
                `name` VARCHAR(50) NOT NULL,
                `value` TEXT NOT NULL DEFAULT \'\',
                `type` VARCHAR(96) NOT NULL DEFAULT \'\',
                PRIMARY KEY (`orders_id`, `type`)
            )
            COLLATE=\'utf8_general_ci\'
            ENGINE=InnoDB;
        ')->execute();
        $check = (new yii\db\Query())->from('configuration')->where(['configuration_key' => 'MODULE_NP_SENDER_AREA_REF'])->exists();
        if (!$check) {
            $this->insert('configuration', [
                'configuration_key' => 'MODULE_NP_SENDER_AREA_REF',
                'configuration_title' => 'Sender Area',
                'configuration_description' => 'Sender Area Ref (for Nova Poshta)',
                'configuration_group_id' => '7',
                'configuration_value' => '',
                'sort_order' => '0',
                'date_added' => (new yii\db\Expression('now()'))
            ]);
            $this->insert('configuration', [
                'configuration_key' => 'MODULE_NP_SENDER_CITY_REF',
                'configuration_title' => 'Sender City',
                'configuration_description' => 'Sender City Ref (for Nova Poshta)',
                'configuration_group_id' => '7',
                'configuration_value' => '',
                'sort_order' => '0',
                'date_added' => (new yii\db\Expression('now()'))
            ]);
            $this->insert('configuration', [
                'configuration_key' => 'MODULE_NP_SENDER_WAREHOUSE_REF',
                'configuration_title' => 'Sender Warehouse',
                'configuration_description' => 'Sender Warehouse Ref (for Nova Poshta)',
                'configuration_group_id' => '7',
                'configuration_value' => '',
                'sort_order' => '0',
                'date_added' => (new yii\db\Expression('now()'))
            ]);
        }

        $this->addTranslation('main', [
            'TEXT_SENDER' => 'Sender',
            'TEXT_MIDDLE_NAME' => 'Middle name',
            'TEXT_RECIPIENT' => 'Recipient',
            'WAREHOUSE_WAREHOUSE_TEXT' => 'Warehouse To Warehouse',
            'WAREHOUSE_WAREHOUSE_DOORS' => 'Warehouse To Doors',
            'TEXT_TYPE_DELIVERY' => 'Type of Delivery',
            'TEXT_RECIPIENT_CITY_NP' => 'City of the recipient',
            'TEXT_RECIPIENT_WAREHOUSE_NP' => 'Warehouse of the recipient',
            'TEXT_RECIPIENT_AREAS_NP' => 'Areas of the recipient',
            'TEXT_RECIPIENT_HOUSE_NP' => 'Number house of the recipient',
            'TEXT_RECIPIENT_FLAT_NP' => 'Number flat of the recipient',
            'TEXT_SHIPMENT_PAY' => 'Shipment Pay',
        ]);
        $this->addTranslation('admin/main', [
            'TEXT_SENDER' => 'Sender',
            'TEXT_MIDDLE_NAME' => 'Middle name',
            'TEXT_RECIPIENT' => 'Recipient',
            'WAREHOUSE_WAREHOUSE_TEXT' => 'Warehouse To Warehouse',
            'WAREHOUSE_WAREHOUSE_DOORS' => 'Warehouse To Doors',
            'TEXT_TYPE_DELIVERY' => 'Type of Delivery',
            'TEXT_RECIPIENT_CITY_NP' => 'City of the recipient',
            'TEXT_RECIPIENT_WAREHOUSE_NP' => 'Warehouse of the recipient',
            'TEXT_RECIPIENT_AREAS_NP' => 'Areas of the recipient',
            'TEXT_RECIPIENT_HOUSE_NP' => 'Number house of the recipient',
            'TEXT_RECIPIENT_FLAT_NP' => 'Number flat of the recipient',
            'TEXT_SHIPMENT_PAY' => 'Shipment Pay',
        ]);
        \Yii::$app->cache->flush();
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {

        if ($this->isTableExists('shipping_np_order_params')) {
            $this->db->createCommand('
                DROP TABLE `shipping_np_order_params`;
            ')->execute();
        }
        $this->db->createCommand('
            CREATE TABLE `shipping_np_order_params` (
                `orders_id` INT(11) NOT NULL,
                `name` VARCHAR(50) NOT NULL,
                `value` VARCHAR(255) NULL DEFAULT NULL,
                PRIMARY KEY (`orders_id`, `name`)
            )
            COLLATE=\'utf8_general_ci\'
            ENGINE=InnoDB;
        ')->execute();

        try {
            $this->delete('configuration',['OR',
                'configuration_key' => 'MODULE_NP_SENDER_AREA_REF',
                'configuration_key' => 'MODULE_NP_SENDER_CITY_REF',
                'configuration_key' => 'MODULE_NP_SENDER_WAREHOUSE_REF',
            ]);
        }catch (\Exception $e) {}

        $this->removeTranslation('main', [
            'TEXT_SENDER',
            'TEXT_RECIPIENT',
            'TEXT_MIDDLE_NAME',
            'WAREHOUSE_WAREHOUSE_TEXT',
            'WAREHOUSE_WAREHOUSE_DOORS',
            'TEXT_TYPE_DELIVERY',
            'TEXT_RECIPIENT_CITY_NP',
            'TEXT_RECIPIENT_WAREHOUSE_NP',
            'TEXT_RECIPIENT_AREAS_NP',
            'TEXT_RECIPIENT_HOUSE_NP',
            'TEXT_RECIPIENT_FLAT_NP',
            'TEXT_SHIPMENT_PAY',
        ]);

        $this->removeTranslation('admin/main', [
            'TEXT_SENDER',
            'TEXT_RECIPIENT',
            'WAREHOUSE_WAREHOUSE_TEXT',
            'TEXT_MIDDLE_NAME',
            'WAREHOUSE_WAREHOUSE_DOORS',
            'TEXT_TYPE_DELIVERY',
            'TEXT_RECIPIENT_CITY_NP',
            'TEXT_RECIPIENT_WAREHOUSE_NP',
            'TEXT_RECIPIENT_AREAS_NP',
            'TEXT_RECIPIENT_HOUSE_NP',
            'TEXT_RECIPIENT_FLAT_NP',
            'TEXT_SHIPMENT_PAY',
        ]);
        \Yii::$app->cache->flush();
    }

}
