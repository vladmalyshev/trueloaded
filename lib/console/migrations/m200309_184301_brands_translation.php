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
 * Class m200309_184301_brands_translation
 */
class m200309_184301_brands_translation extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
      $this->addTranslation('admin/design', [
        'TEXT_SHOW_ABC' => 'Show ABC'
      ]);

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        //echo "m200309_184301_brands_translation cannot be reverted.\n";

        //return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200309_184301_brands_translation cannot be reverted.\n";

        return false;
    }
    */
}
