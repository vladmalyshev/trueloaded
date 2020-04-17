<?php

use yii\db\Migration;
use yii\db\Schema;

/**
 * Class m180904_092411_suppliers_currencies
 */
class m180904_092411_suppliers_currencies extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable('suppliers_currencies', [
            'suppliers_id' => Schema::TYPE_INTEGER,
            'currencies_id' => Schema::TYPE_INTEGER,
            'status' => $this->integer(1)->defaultValue(0),
            'use_custom_currency_value' => $this->integer(1)->defaultValue(0),
            'currency_value' => $this->double(8)->defaultValue(0),
            'margin_value' => $this->float(8)->defaultValue(0),
            'margin_type' => $this->char(8)->defaultValue('%'),
            'PRIMARY KEY(suppliers_id, currencies_id)'
        ], 'engine=InnoDB');
        
        $this->db->createCommand("insert into suppliers_currencies (suppliers_id, currencies_id, status) select s.suppliers_id, c.currencies_id, 1 from suppliers s, currencies c where c.status = 1 and s.currencies_id = c.currencies_id")->execute();        
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180904_092411_suppliers_currencies cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180904_092411_suppliers_currencies cannot be reverted.\n";

        return false;
    }
    */
}
