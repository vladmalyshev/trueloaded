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
 * Class m190708_181728_orders_export
 */
class m190708_181728_orders_export extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('admin/main',[
            'TEXT_DISABLE_EXPORT' => 'Disable Export',
            'TEXT_EXCHANGE_SWITCH_UPDATED' => 'Export status has been updated',
            'TEXT_EXCHANGE_RESET_ERROR_OK' => 'Export status has been reset',
        ]);
/*
        $this->update('translation',
            ['translation_entity' => 'admin/main'],
            ['translation_key' => ['TEXT_CATALOG_PRODUCTS', 'TEXT_OPTION_DOWNLOAD_CATALOG'], 'translation_entity' => 'admin/easypopulate']
        );
*/
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190708_181728_orders_export cannot be reverted.\n";

     //   return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190708_181728_orders_export cannot be reverted.\n";

        return false;
    }
    */
}
