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
 * Class m191014_130214_missed_translation
 */
class m191014_130214_missed_translation extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
      $this->addTranslation('main', ['TEXT_GENERAL_ERROR' => 'Unexpected error has occurred. Please reload page or contact us if it persists']);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        //echo "m191014_130214_missed_translation cannot be reverted.\n";

        //return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191014_130214_missed_translation cannot be reverted.\n";

        return false;
    }
    */
}
