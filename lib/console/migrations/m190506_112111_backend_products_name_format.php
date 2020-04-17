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
 * Class m190506_112111_backend_products_name_format
 */
class m190506_112111_backend_products_name_format extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $column = $this->getDb()->getTableSchema('products_description')->getColumn('products_internal_name');
        if ( $column->size<255 ) {
            $this->alterColumn('products_description', 'products_internal_name', $this->string(255)->null());
        }

        $this->insert('configuration',[
            'configuration_title' => 'Backend internal product name',
            'configuration_key' => 'BACKEND_PRODUCT_NAME_FORMAT',
            'configuration_value' => '',
            'configuration_description' => 'Use internal product name on',
            'configuration_group_id' => 1,
            'sort_order' => 3245,
            'date_added' => new \yii\db\Expression('NOW()'),
            'use_function' => 'getBackendProductName',
            'set_function' => 'setBackendProductName('
        ]);

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->delete( 'configuration', ['configuration_key' => 'BACKEND_PRODUCT_NAME_FORMAT'] );
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190506_112111_backend_products_name_format cannot be reverted.\n";

        return false;
    }
    */
}
