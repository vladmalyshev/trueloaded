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
 * Class m200218_172928_specials_start_date
 */
class m200218_172928_specials_start_date extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
      $sc = Yii::$app->db->getTableSchema('specials', true);
      if ($sc && empty($sc->getColumn('start_date'))) {
        $this->addColumn('specials', 'start_date', $this->dateTime()->defaultValue(null));
      }

      $this->createIndex('idx_start_date', 'specials', 'start_date');
      $this->createIndex('idx_expires_date', 'specials', 'expires_date');
      $this->addTranslation('admin/easypopulate', ['TEXT_OPTION_SALES_FEED' => 'Special Prices']);

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
//        echo "m200218_172928_specials_start_date cannot be reverted.\n";

//        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200218_172928_specials_start_date cannot be reverted.\n";

        return false;
    }
    */
}
