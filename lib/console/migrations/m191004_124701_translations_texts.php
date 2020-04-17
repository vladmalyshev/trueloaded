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
 * Class m191004_124701_translations_texts
 */
class m191004_124701_translations_texts extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
      $this->addTranslation('admin/texts', [
          'TEXT_SKIP_ADMIN' => 'Skip admin and configuration',
          'TEXT_UNTRANSLATED_ONLY' => 'Not translated only',
          'TEXT_UNVERIFIED_ONLY' => 'Not verified only',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        

        //return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191004_124701_translations_texts cannot be reverted.\n";

        return false;
    }
    */
}
