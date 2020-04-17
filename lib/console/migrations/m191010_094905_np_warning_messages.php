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
 * Class m191010_094905_np_warning_messages
 */
class m191010_094905_np_warning_messages extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('main', [
            'ENTRY_NP_AREA_SELECT' => 'Не встановлена область',
            'ENTRY_NP_CITY_SELECT' => 'Не встановлено населений пункт',
            'ENTRY_NP_WAREHOUSE_SELECT' => 'Не встановлено склад',
            'TEXT_NALOZHENNUJ_PLATEZH_NP' => 'Післяплата',
            'TEXT_PLEASE_WAIT' => 'Please wait...',
        ]);
        $this->addTranslation('admin/main', [
            'ENTRY_NP_AREA_SELECT' => 'Не встановлена область',
            'ENTRY_NP_CITY_SELECT' => 'Не встановлено населений пункт',
            'ENTRY_NP_WAREHOUSE_SELECT' => 'Не встановлено склад',
            'TEXT_NALOZHENNUJ_PLATEZH_NP' => 'Післяплата',
            'TEXT_PLEASE_WAIT' => 'Please wait...',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->removeTranslation('main', [
            'ENTRY_NP_AREA_SELECT',
            'ENTRY_NP_CITY_SELECT',
            'ENTRY_NP_WAREHOUSE_SELECT',
            'TEXT_NALOZHENNUJ_PLATEZH_NP',
            'TEXT_PLEASE_WAIT',
        ]);
        $this->removeTranslation('admin/main', [
            'ENTRY_NP_AREA_SELECT',
            'ENTRY_NP_CITY_SELECT',
            'ENTRY_NP_WAREHOUSE_SELECT',
            'TEXT_NALOZHENNUJ_PLATEZH_NP',
            'TEXT_PLEASE_WAIT',
        ]);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191010_094905_np_warning_messages cannot be reverted.\n";

        return false;
    }
    */
}
