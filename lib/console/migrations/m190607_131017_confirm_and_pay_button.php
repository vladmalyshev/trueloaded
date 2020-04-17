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
 * Class m190607_131017_confirm_and_pay_button
 */
class m190607_131017_confirm_and_pay_button extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('main', [
            'TEXT_CONFIRM_AND_PAY' => 'Confirm and pay',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {

        $this->removeTranslation('main', [
            'TEXT_CONFIRM_AND_PAY' => 'Confirm and pay',
        ]);

    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190607_131017_confirm_and_pay_button cannot be reverted.\n";

        return false;
    }
    */
}
