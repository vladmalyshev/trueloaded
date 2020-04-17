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
 * Class m190913_192330_admin_onbehalf_payment
 */
class m190913_192330_admin_onbehalf_payment extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
      $this->addTranslation('main', [
        'TEXT_ON_BEHALF_PAYMENT_SUCCESSFUL' => 'Payment has been processed.',
        'TEXT_PAY_ON_BEHALF' => 'Process card payment by phone',
        'TEXT_SCROLL_TO_CONFIRM' => 'Select payment method, scroll down to review order and confirm payment',
      ]);

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
//        echo "m190913_192330_admin_onbehalf_payment cannot be reverted.\n";

//        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190913_192330_admin_onbehalf_payment cannot be reverted.\n";

        return false;
    }
    */
}
