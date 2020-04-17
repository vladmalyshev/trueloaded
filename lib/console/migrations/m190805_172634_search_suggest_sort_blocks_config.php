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
 * Class m190805_172634_search_suggest_sort_blocks_config
 */
class m190805_172634_search_suggest_sort_blocks_config extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {

      $this->insert('configuration',[
          'configuration_title' => 'Search suggest blocks',
          'configuration_key' => 'SEARCH_SUGGEST_BLOCKS_ORDER',
          'configuration_value' => 'Products, Manufacturers, Information',
          'configuration_description' => 'Display search suggest blocks in following sort order',
          'configuration_group_id' => 333,
          'sort_order' => 36,
          'date_added' => new \yii\db\Expression('NOW()'),
          'set_function' => 'cfgMultiSortable(array(\'Products\', \'Manufacturers\', \'Information\'),'
      ]);
      
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        //echo "m190805_172634_search_suggest_sort_blocks_config cannot be reverted.\n";
        $this->delete('configuration', ['configuration_key' => 'SEARCH_SUGGEST_BLOCKS_ORDER']);

//        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190805_172634_search_suggest_sort_blocks_config cannot be reverted.\n";

        return false;
    }
    */
}
