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
 * Class m191204_225200_giveaway_page_translations
 */
class m191204_225200_giveaway_page_translations extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
$this->addTranslation('admin/main', [
'TEXT_ACTIVE_ON' => 'Active',
'TEXT_START_BETWEEN' => 'Start',
'TEXT_END_BETWEEN' => 'End',
]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
//        echo "m191204_225200_giveaway_page_translations cannot be reverted.\n";

//        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191204_225200_giveaway_page_translations cannot be reverted.\n";

        return false;
    }
    */
}
