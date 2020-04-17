<?php

use yii\db\Migration;

/**
 * Class m180815_143219_email_password_for_supplier
 */
class m180815_143219_email_password_for_supplier extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $schema = $this->db->getSchema();
        $suppliers_auth_data = $schema->getTableSchema('suppliers_auth_data');
        if (!$suppliers_auth_data){
            $this->createTable('suppliers_auth_data', [
                'suppliers_id' => $this->primaryKey(),
                'email_address' => $this->string(128),
                'password'  => $this->string(128),
            ], 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB');
            $this->addForeignKey('suppliers_id', 'suppliers_auth_data', 'suppliers_id', 'suppliers', 'suppliers_id');
        }
        
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180815_143219_email_password_for_supplier cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180815_143219_email_password_for_supplier cannot be reverted.\n";

        return false;
    }
    */
}
