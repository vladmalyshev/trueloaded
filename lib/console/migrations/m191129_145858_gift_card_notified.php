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
 * Class m191129_145858_gift_card_notified
 */
class m191129_145858_gift_card_notified extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->isFieldExists('activated', 'virtual_gift_card_basket')){
            $this->addColumn('virtual_gift_card_basket', 'activated', $this->integer(1)->defaultValue(0));
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        if ($this->isFieldExists('activated', 'virtual_gift_card_basket')){
            $this->dropColumn('virtual_gift_card_basket', 'activated');
        }

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191129_145858_gift_card_notified cannot be reverted.\n";

        return false;
    }
    */
}
