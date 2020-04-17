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
 * Class m190320_095251_transaction_transalations
 */
class m190320_095251_transaction_translations extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('admin/orders', [
            'TEXT_LOG_REFUND_START' => "Refunding started..",
            'TEXT_LOG_REFUND_AMOUNT_LIMIT' => "Refunding stopped by achieving amount",
            'TEXT_LOG_REFUND_SUCCESSFUL' => "%s transaction refunded successfully",
            'TEXT_LOG_REFUND_ERROR' => "% transaction refund error",
            'TEXT_LOG_REFUND_COMPLETE' => "Refund completed",
            'TEXT_LOG_REFUND_IMCOMPLETE' => "Refund incomplete",
            'TEXT_LOG_REFUND_PROCESS_ERROR' => "Refund processing error",
            'TEXT_CHECK_UNCLOSED_CREDITNNOTE' => "You have unclosed Credit Notes without refunds. Please check Transactions",
            'TEXT_TRANSACTIONS' => "Transactions",
            'TEXT_TRANSACTION_AMOUNT' => "Transaction Amount",
            'TEXT_TRANSACTION_ID' => "Transaction id",
            'TEXT_ASSIGN_TRANSACTIONS' => "Assign",
            'TEXT_NO_TRANSACTIONS' => 'No transactions',
            'TEXT_REFUND_AMOUNT_ERROR' => "Amount could not be more than allowed by transactions",
            'TEXT_REFUND_UNDEFINED_TRANSACTION' => "Undefined tarnsaction id",
            'TEXT_REFUND_AMOUNT_ENOUGH' => "Sorry, it is not enough amount to make refund",
            'TEXT_CAN_VOID_ONLY' => "Can be fully voided only",
            'TEXT_REFUND_PROMPT' => "Are you sure?",
            'TEXT_REFUND_AMOUNT' => "Refund Amount",
            'TEXT_REFUNDED_AMOUNT' => "Refunded Amount",
            'TEXT_PREFFERED' => "Preffered",
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190320_095251_transaction_transalations cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190320_095251_transaction_transalations cannot be reverted.\n";

        return false;
    }
    */
}
