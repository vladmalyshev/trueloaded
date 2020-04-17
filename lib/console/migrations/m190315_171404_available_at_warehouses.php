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
 * Class m190315_171404_available_at_warehouses
 */
class m190315_171404_available_at_warehouses extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('admin/design', [
            'TEXT_AVAILABLE_AT_WAREHOUSES' => 'At Warehouses',
            'AVAILABLE_AT_WAREHOUSES_SHOW_ADDRESS' => 'Show address',
            'AVAILABLE_AT_WAREHOUSES_SHOW_TIME' => 'Show time',
            'AVAILABLE_AT_WAREHOUSES_SHOW_QTY' => 'Show quanity',
            'AVAILABLE_AT_WAREHOUSES_SHOW_QTY_LESS' => 'Show exact q-ty if less',
            'AVAILABLE_AT_WAREHOUSES_SHOW_QTY_AS_LEVELS' => 'Show quanity levels',
            'AVAILABLE_AT_WAREHOUSES_SHOW_QTY_LEVEL1' => 'Show quanity level 1',
            'AVAILABLE_AT_WAREHOUSES_SHOW_QTY_LEVEL2' => 'Show quanity level 2',
        ]);

        $this->addTranslation('catalog/product', [
            'AVAILABLE_AT_WAREHOUSES' => 'Available at warehouses:',
            'AVAILABLE_AT_WAREHOUSES_QTY_HIGH' => 'more than %s',
            'AVAILABLE_AT_WAREHOUSES_LEVEL_LOW' => 'few in stock',
            'AVAILABLE_AT_WAREHOUSES_LEVEL_MEDIUM' => 'avg in stock',
            'AVAILABLE_AT_WAREHOUSES_LEVEL_HIGH' => 'a lot in stock',
        ]);

        $this->addTranslation('main', [
            'CHECK_STORE_AVAILABILITY' => 'Check In-Store availability',
            'TEXT_AVAILABLE' => 'Available',
            ]);


    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
      $this->removeTranslation('admin/design', [
        'TEXT_AVAILABLE_AT_WAREHOUSES',
        'AVAILABLE_AT_WAREHOUSES_SHOW_ADDRESS',
        'AVAILABLE_AT_WAREHOUSES_SHOW_TIME',
        'AVAILABLE_AT_WAREHOUSES_SHOW_QTY',
        'AVAILABLE_AT_WAREHOUSES_SHOW_QTY_LESS',
        'AVAILABLE_AT_WAREHOUSES_SHOW_QTY_AS_LEVELS',
        'AVAILABLE_AT_WAREHOUSES_SHOW_QTY_LEVEL1',
        'AVAILABLE_AT_WAREHOUSES_SHOW_QTY_LEVEL2',
        ]);

        $this->removeTranslation('catalog/product', [
            'AVAILABLE_AT_WAREHOUSES',
            'AVAILABLE_AT_WAREHOUSES_LEVEL_LOW',
            'AVAILABLE_AT_WAREHOUSES_LEVEL_MEDIUM',
            'AVAILABLE_AT_WAREHOUSES_LEVEL_HIGH',
            'AVAILABLE_AT_WAREHOUSES_QTY_HIGH',
        ]);

        $this->addTranslation('main', [
            'CHECK_STORE_AVAILABILITY',
            'TEXT_AVAILABLE',
            ]);

    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190315_171404_available_at_warehouses cannot be reverted.\n";

        return false;
    }
    */
}
