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
 * Class m191216_144847_search_suggest_categories
 */
class m191216_144847_search_suggest_categories extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
      
      $this->getDb()->createCommand("update  `configuration` set set_function='cfgMultiSortable(array(\'Products\', \'Manufacturers\', \'Information\', \'Categories\'),' where configuration_key='SEARCH_SUGGEST_BLOCKS_ORDER' and set_function not like '%Categories%'")->execute();
      if ($this->db->createCommand(
                "SELECT * ".
                "from translation where translation_key='TEXT_CATEGORIES' and translation_entity='admin/main'"
            )->queryOne()) {
        $this->getDb()->createCommand(
            "insert ignore into translation (`language_id`, `translation_key`, `translation_entity`, `translation_value`, `not_used`, `translated`, `checked`) select * from (
              select `language_id`, `translation_key`, 'main', `translation_value`, `not_used`, `translated`, `checked` from translation where translation_key='TEXT_CATEGORIES' and translation_entity='admin/main'
            ) dt"
            )->execute();
      } else {
        $this->addTranslation('main', ['TEXT_CATEGORIES', 'categories']); // copy out from admin/main
      }


    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        //echo "m191216_144847_search_suggest_categories cannot be reverted.\n";

        //return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191216_144847_search_suggest_categories cannot be reverted.\n";

        return false;
    }
    */
}
