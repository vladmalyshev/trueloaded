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
 * Class m190513_155623_nova_poshta
 */
class m190513_155623_nova_poshta extends Migration
{

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('admin/main',[
            'MODULE_SHIPPING_NP_WAREHOUSE_WAY' => 'Collection Point',
            'MODULE_SHIPPING_NP_ADDRESS_WAY' => 'Direct Delivery',
            'DELIVERY_SERVICE_COST_TEXT' => 'At carrier prices',
            'ENTRY_STATE_TEXT' => 'State',
            'ENTRY_TOWN_TEXT' => 'Locality',
            'ENTRY_DEPARTMENT_TEXT' => 'Warehouse'
        ]);
        $this->addTranslation('main',[
            'MODULE_SHIPPING_NP_WAREHOUSE_WAY' => 'Collection Point',
            'MODULE_SHIPPING_NP_ADDRESS_WAY' => 'Direct Delivery',
            'DELIVERY_SERVICE_COST_TEXT' => 'At carrier prices',
            'ENTRY_STATE_TEXT' => 'State',
            'ENTRY_TOWN_TEXT' => 'Locality',
            'ENTRY_DEPARTMENT_TEXT' => 'Warehouse'
        ]);
        // $this->db->createCommand("UPDATE platforms_configuration SET configuration_value = 'https://novaposhta.ua/tracking/?cargo_number=' WHERE configuration_key = 'TRACKING_NUMBER_URL' ")->execute();
        // $this->db->createCommand("UPDATE configuration SET configuration_value = 'https://novaposhta.ua/tracking/?cargo_number=' WHERE configuration_key = 'TRACKING_NUMBER_URL' ")->execute();
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190513_155623_nova_poshta cannot be reverted.\n";

        return false;
    }
    */
}
