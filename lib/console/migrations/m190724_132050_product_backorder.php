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
 * Class m190724_132050_product_backorder
 */
class m190724_132050_product_backorder extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
      try {
        $this->addColumn('products', 'allow_backorder', $this->integer(2)->notNull()->defaultValue(0));
        $this->addColumn('inventory', 'allow_backorder', $this->integer(2)->notNull()->defaultValue(0));
      } catch (\Exception $ex) {
// ok if it exists
      }


      $this->insert('configuration',[
          'configuration_title' => 'Allow backorder by default',
          'configuration_key' => 'STOCK_ALLOW_BACKORDER_BY_DEFAULT',
          'configuration_value' => 'True',
          'configuration_description' => 'Allow backorder by default',
          'configuration_group_id' => 9,
          'sort_order' => 36,
          'date_added' => new \yii\db\Expression('NOW()'),
          'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'),'
      ]);

      $this->addTranslation('admin/main', [
          'TEXT_ALLOW_BACKORDER' => 'Allow backorders',
      ]);
      
      $this->addTranslation('admin/design', [
          'HEADING_LAYOUT' => 'Box Layout',
          'SHOW_DETAILS' => 'Show details',
          'SHOW_DETAILS_ONLY_LABEL' => 'Only label',
          'SHOW_DETAILS_DETAILED' => 'Detailed info (with PO)',
      ]);

      $this->addTranslation('main', [
          'BUTTON_TEXT_PREORDER' => 'Pre Order',
          'TEXT_COMING_SOON' => 'Coming soon',
          'TEXT_QTY_IN_STOCK' => '%s in stock',
      ]);

      $this->addTranslation('catalog/product', [
          'TEXT_EXPECTED_DELIVERY_DATE' => 'ETA date',
          'TEXT_EXPECTED_QTY' => 'Qty Incoming',
          'TEXT_EXPECTED_ON' => '%s expected on %s',
      ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
      $this->delete('configuration', [
          'configuration_key' => 'STOCK_ALLOW_BACKORDER_BY_DEFAULT']);
        //echo "m190724_132050_product_backorder cannot be reverted.\n";

        //return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190724_132050_product_backorder cannot be reverted.\n";

        return false;
    }
    */
}
