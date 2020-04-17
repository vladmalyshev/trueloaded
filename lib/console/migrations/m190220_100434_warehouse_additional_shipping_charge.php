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
 * Class m190220_100434_warehouse_additional_shipping_charge
 */
class m190220_100434_warehouse_additional_shipping_charge extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('warehouses', 'shipping_additional_charge', $this->decimal(6,2)->notNull()->defaultValue(0.00));
        $this->addTranslation('admin/warehouses',[
            'TEXT_ADDITIONAL_SHIPPING_CHARGE' => 'Additional shipping charge (net)',
        ]);
        $this->addTranslation('admin/main',[
            'CATEGORY_EXTRA_DATA' => 'Extra config',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->removeTranslation('admin/warehouses',[
            'TEXT_ADDITIONAL_SHIPPING_CHARGE',
        ]);
        $this->removeTranslation('admin/main',[
            'CATEGORY_EXTRA_DATA',
        ]);
        $this->dropColumn('warehouses', 'shipping_additional_charge');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190220_100434_warehouse_additional_shipping_charge cannot be reverted.\n";

        return false;
    }
    */
}
