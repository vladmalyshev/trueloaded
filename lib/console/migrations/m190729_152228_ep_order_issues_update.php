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
 * Class m190729_152228_ep_order_issues_update
 */
class m190729_152228_ep_order_issues_update extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
      
      if (!$this->isTableExists('ep_order_issues')){
        $this->createTable('ep_order_issues', [
              'orders_id' => $this->integer()->notNull()->defaultValue(0),
              'status' => $this->integer()->notNull()->defaultValue(0),
              'ep_directory_id' => $this->integer()->notNull()->defaultValue(0),
              'date_added' => $this->dateTime()->notNull(),
              'issue_text' => $this->text(),
            ], 'engine=InnoDB DEFAULT CHARSET=utf8');
      }

// should not send same order in less a second
      $this->createIndex('orders_id', 'ep_order_issues', [
        'orders_id',
        'ep_directory_id',
        'date_added'
      ], true);

      $this->addTranslation('admin/main',
          [
            'TEXT_ERROR_INTRO' => 'Export disabled due export error(s)',
            'TEXT_RESET_ERROR_NOTE' => 'fix the error(s) and reset error log',
            'IMAGE_RESET' => 'Reset',
          ]);


    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        //echo "m190729_152228_ep_order_issues_update cannot be reverted.\n";
      $this->removeTranslation('admin/main', ['TEXT_ERROR_INTRO', 'TEXT_RESET_ERROR_NOTE', 'IMAGE_RESET']);

        //return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190729_152228_ep_order_issues_update cannot be reverted.\n";

        return false;
    }
    */
}
