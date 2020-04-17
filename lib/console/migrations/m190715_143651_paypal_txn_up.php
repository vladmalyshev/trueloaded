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
 * Class m190715_143651_paypal_txn_up
 */
class m190715_143651_paypal_txn_up extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('paypalipn_txn', 'payment_class', $this->string(64));
        $this->createIndex('idx_class', 'paypalipn_txn', 'payment_class');
        $this->addColumn('paypalipn_txn', 'is_assigned', $this->integer(1)->defaultValue('1'));
        $this->createIndex('idx_assigned', 'paypalipn_txn', 'is_assigned');
        $this->addColumn('paypalipn_txn', 'platform_id', $this->integer()->defaultValue('0'));
        $this->createIndex('idx_platform', 'paypalipn_txn', 'platform_id');
        $this->addTranslation('admin/orders', [
            'TEXT_UNLINK_PROMPT' => 'Do you confirm unlink this transaction?',
            'TEXT_NEGATIVE_TRANSACTION_UNALLOWED' => 'Refunded transaction can\'t be applied',
            'TEXT_CONFIRM_ASSIGN_TRANSACTION' => 'Confirm assign this transactions to current order',
            'TEXT_FOUND_TRANSACTIONS' => 'Found Transactions',
            'TEXT_SELECT_TRANSACTION' => 'Please select transaction',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropIndex('idx_class', 'paypalipn_txn');
        $this->dropColumn('paypalipn_txn', 'payment_class');
        $this->dropIndex('idx_assigned', 'paypalipn_txn');
        $this->dropColumn('paypalipn_txn', 'is_assigned');
        $this->dropIndex('idx_platform', 'paypalipn_txn');
        $this->dropColumn('paypalipn_txn', 'platform_id');
        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190715_143651_paypal_txn_up cannot be reverted.\n";

        return false;
    }
    */
}
