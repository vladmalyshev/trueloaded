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
 * Class m200114_155141_suppliers_email_sent_increase
 */
class m200114_155141_suppliers_email_sent_increase extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
      $sc = Yii::$app->db->getTableSchema('suppliers', true);
      if ($sc && $sc->getColumn('send_email')->size < 2048) {
        $this->alterColumn('suppliers', 'send_email', $this->string(2048)->notNull()->defaultValue(''));
      }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
       // echo "m200114_155141_suppliers_email_sent_increase cannot be reverted.\n";

        //return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200114_155141_suppliers_email_sent_increase cannot be reverted.\n";

        return false;
    }
    */
}
