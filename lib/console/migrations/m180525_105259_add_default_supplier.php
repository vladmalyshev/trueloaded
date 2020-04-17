<?php

use yii\db\Migration;
use yii\db\Schema;
/**
 * Class m180525_105259_add_default_supplier
 */
class m180525_105259_add_default_supplier extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn("suppliers", "status", $this->smallInteger()->defaultValue('1')->notNull());
        
        $result = $this->getDb()->createCommand('select suppliers_id from suppliers where is_default = 1')->execute();
        if (!$result){
            $this->insert("suppliers", [
                'suppliers_id' => null,
                'suppliers_name' => 'Default Supplier',
                'date_added' => $this->dateTime(),
                'is_default' => '1',
                'status' => '1',
            ]);
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180525_105259_add_default_supplier cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {
                
    }
    
    public function down()
    {
        echo "m180525_105259_add_default_supplier cannot be reverted.\n";

        return false;
    }
    */
}
