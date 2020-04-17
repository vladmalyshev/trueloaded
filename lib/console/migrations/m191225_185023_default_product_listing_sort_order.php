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
 * Class m191225_185023_default_product_listing_sort_order
 */
class m191225_185023_default_product_listing_sort_order extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $check = (new yii\db\Query())->from('configuration')->where(['configuration_key' => 'PRODUCT_LISTING_DEFAULT_SORT_ORDER'])->exists();
        if (!$check) {
            $this->insert('configuration', [
                'configuration_key' => 'PRODUCT_LISTING_DEFAULT_SORT_ORDER',
                'configuration_title' => 'Product listing default sort order',
                'configuration_description' => 'Product listing default sort order',
                'configuration_group_id' => '8',
                'configuration_value' => '',
                'use_function' => 'getListingSortOrder',
                'set_function' => 'setListingSortOrder(',
                'sort_order' => '101',
                'date_added' => (new yii\db\Expression('now()'))
            ]);
        }

        if (!$this->isFieldExists('default_sort_order', 'categories') ){
          $this->addColumn('categories', 'default_sort_order', $this->string(5)->notNull());
        }

        $this->addTranslation('admin/main', ['TEXT_DEFAULT_SORT_ORDER' => 'Default sort order']);
        
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        //echo "m191225_185023_default_product_listing_sort_order cannot be reverted.\n";

       // return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191225_185023_default_product_listing_sort_order cannot be reverted.\n";

        return false;
    }
    */
}
