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
 * Class m190606_122443_pay_order_on_widgets
 */
class m190606_122443_pay_order_on_widgets extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {

        $this->addTranslation('admin/main',[
            'TEXT_PAY_FORM' => 'Pay Form',
            'TEXT_PAY_CONFIRM' => 'Pay Confirm',
            'TEXT_ORDER_HEADING' => 'Order Heading',
            'TEXT_ORDER_PAY' => 'Order Pay',
            'TEXT_ORDER_CONFIRMATION' => 'Order Confirmation',
        ]);

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190606_122443_pay_order_on_widgets cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190606_122443_pay_order_on_widgets cannot be reverted.\n";

        return false;
    }
    */
}
