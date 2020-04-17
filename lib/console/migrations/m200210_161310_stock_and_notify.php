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
 * Class m200210_161310_stock_and_notify
 */
class m200210_161310_stock_and_notify extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
      $this->createIndex('idx_products_notify', 'products_notify', [
        'products_notify_products_id',
        'suppliers_id',
        'products_notify_email',
        'products_notify_sent'
      ]);
      $this->addTranslation('admin/main', [
        'TEXT_STOCK_CODE_TYPE' => 'Stock group'
      ]);
      $this->db->createCommand(
          "update translation t, languages l set translation_value='Sell incoming PO stock' where l.code='en' and l.languages_id=t.language_id and t.translation_entity='admin/main' and translation_key='TEXT_ALLOW_BACKORDER' and t.translation_value='Allow backorders' "
          )->execute();
      $this->db->createCommand(
          "update translation t, languages l set translation_value='Allow to sell incoming PO stock by default' where l.code='en' and l.languages_id=t.language_id and t.translation_entity='configuration' and translation_key='STOCK_ALLOW_BACKORDER_BY_DEFAULT_DESC' and t.translation_value='Allow backorder by default' "
          )->execute();
      $this->db->createCommand(
          "update translation t, languages l set translation_value='Allow to sell incoming PO stock' where l.code='en' and l.languages_id=t.language_id and t.translation_entity='configuration' and translation_key='STOCK_ALLOW_BACKORDER_BY_DEFAULT_TITLE' and t.translation_value='Allow backorder by default' "
          )->execute();

      $this->db->createCommand(
          "update configuration set configuration_title='Allow to sell incoming PO stock', configuration_description='Allow to sell incoming PO stock by default' where configuration_key='STOCK_ALLOW_BACKORDER_BY_DEFAULT' "
          )->execute();
      //

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
//        echo "m200210_161310_stock_and_notify cannot be reverted.\n";

  //      return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200210_161310_stock_and_notify cannot be reverted.\n";

        return false;
    }
    */
}
