<?php

use common\classes\Migration;

class m190721_134404_global_payments_transactions extends Migration
{
    public function up()
    {
        if ( !$this->isTableExists('global_payments_transactions') ) {
            $this->createTable('global_payments_transactions', [
                'transaction_id' => $this->bigPrimaryKey(),
                'srd' => $this->string(255)->notNull()->defaultValue(''),
                'store' => $this->string(50)->notNull()->defaultValue(''),
                'order_id' => $this->integer(11)->notNull()->defaultValue(0),
                'gp_order_id' => $this->string(255)->notNull()->defaultValue(''),
                'customer_id' => $this->integer(11)->notNull()->defaultValue(0),
                'amount' => $this->integer(11)->notNull()->defaultValue(0),
                'customer_name' => $this->string(255)->notNull()->defaultValue(''),
                'card_holder_name' => $this->string(255)->notNull()->defaultValue(''),
                'card_details' => $this->string(255)->notNull()->defaultValue(''),
                'card_type' => $this->string(50)->notNull()->defaultValue(''),
                'exp_date' => $this->string(10)->notNull()->defaultValue(''),
                'customer_ref' => $this->string(255)->notNull()->defaultValue(''),
                'card_ref' => $this->string(255)->notNull()->defaultValue(''),
                'code' => $this->string(2)->notNull()->defaultValue(''),
                'raw' => $this->text()->defaultValue('')->notNull(),
            ]);
            $this->createIndex('customer_id_idx', 'global_payments_transactions', ['customer_id']);
            $this->createIndex('order_id_idx', 'global_payments_transactions', ['order_id']);
        }
        $this->addColumn('customers', 'payerreference', $this->string(255)->notNull()->defaultValue(''));
    }

    public function down()
    {
        if ($this->isTableExists('global_payments_transactions')) {
            $this->dropIndex('customer_id_idx','global_payments_transactions');
            $this->dropTable('global_payments_transactions');
        }
        if ($this->isFieldExists('payerreference', 'customers')) {
            $this->dropColumn('customers', 'payerreference');
        }

    }

}
