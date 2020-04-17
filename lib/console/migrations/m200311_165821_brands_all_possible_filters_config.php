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
 * Class m200311_165821_brands_all_possible_filters_config
 */
class m200311_165821_brands_all_possible_filters_config extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->insert('configuration',[
            'configuration_title' => 'Always show filters on brand page',
            'configuration_key' => 'ALWAYS_SHOW_FILTERS_ON_BRAND_PAGE',
            'configuration_value' => 'True',
            'configuration_description' => 'Always show all possible filters on brand/manufacturer page (if filters are not set up for the brand)',
            'configuration_group_id' => 'TEXT_LISTING_PRODUCTS',
            'sort_order' => 7777,
            'date_added' => new \yii\db\Expression('NOW()'),
            'use_function' => '',
            'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'),'
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
      $this->delete('configuration', 'configuration_key="ALWAYS_SHOW_FILTERS_ON_BRAND_PAGE"');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200311_165821_brands_all_possible_filters_config cannot be reverted.\n";

        return false;
    }
    */
}
