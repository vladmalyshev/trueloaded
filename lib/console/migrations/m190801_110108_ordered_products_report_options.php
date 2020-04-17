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
 * Class m190801_110108_ordered_products_report_options
 */
class m190801_110108_ordered_products_report_options extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
      $this->addTranslation('admin/main', [
        'TABLE_HEADING_PURCHASE_PRICE' => 'Purchase Price',
        'TABLE_HEADING_PURCHASE_SUMMARY' => 'Purchase Summ',

        'OPTIONS_SHOW_COLUMNS' => 'Display columns',
        'OPTIONS_SHOW_CATEGORIES' => 'categories',
        'OPTIONS_SHOW_COUNTRY' => 'country',
        'OPTIONS_SHOW_SALE_PRICE' => 'sale price',
        'OPTIONS_SHOW_PURCHASE_PRICE' => 'purchase price',

        'OPTIONS_GROUP_BY' => 'Group by',
        'OPTIONS_GROUP_TOP_CATEGORY' => 'Top category',
        'OPTIONS_GROUP_CATEGORY' => 'category',
        'OPTIONS_GROUP_COUNTRY' => 'country',
        //2do group by inventory

        'FILTER_BY_CUSTOMER' =>  'Customer',
        'FILTER_BY_ORDERS' =>  'Orders'

      ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        //echo "m190801_110108_ordered_products_report_options cannot be reverted.\n";

        //return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190801_110108_ordered_products_report_options cannot be reverted.\n";

        return false;
    }
    */
}
