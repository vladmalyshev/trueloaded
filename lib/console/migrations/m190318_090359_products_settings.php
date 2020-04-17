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
 * Class m190318_090359_products_settings
 */
class m190318_090359_products_settings extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('main', [
            'TEXT_CREDITNOTE' => 'Credit Note',
        ]);
        $this->addTranslation('admin/main', [
            'TEXT_CREDITNOTE' => 'Credit Note',
        ]);
        $this->addTranslation('admin/categories', [
            'TEXT_ATTRIBUTES_W_QUANTITY' => 'Show Combined Attributes with Quantity',
        ]);
        
        $this->createTable('products_settings', [
            'products_id' => $this->primaryKey(),
            'show_attributes_quantity' => $this->tinyInteger()->defaultValue('0'),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');
        
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropTable('products_settings');
        $this->removeTranslation('main', ['TEXT_CREDITNOTE']);
        $this->removeTranslation('admin/main', ['TEXT_CREDITNOTE']);
        $this->removeTranslation('admin/categories', ['TEXT_ATTRIBUTES_W_QUANTITY']);
        
        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190318_090359_products_settings cannot be reverted.\n";

        return false;
    }
    */
}
