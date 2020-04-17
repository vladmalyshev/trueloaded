<?php

use yii\db\Migration;

/**
 * Class m181026_091939_rename_affilifate_id_to_platform_id
 */
class m181026_091939_rename_affilifate_id_to_platform_id extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->renameColumn('products_description', 'affiliate_id', 'platform_id');
        $this->getDb()->createCommand("update products_description set platform_id = '" . \common\classes\platform::defaultId(). "'")->execute();
        $schema = $this->db->getTableSchema('products_description');
        if ($schema){
            $columns = array_keys($schema->columns);
            if ($columns){
                $columns = array_flip($columns);
                unset($columns['platform_id']);
                $columns = array_flip($columns);                
                $cListwp = implode(",", $columns);
                foreach(\common\models\Platforms::getPlatformsByType('non-virtual')->all() as $platform){
                    if ($platform->platform_id != \common\classes\platform::defaultId()){                        
                        $this->getDb()->createCommand("insert into products_description ($cListwp, platform_id) select {$cListwp}, " . $platform->platform_id . " from products_description pd where pd.platform_id = '" .\common\classes\platform::defaultId(). "'")->execute();
                    }
                }
            }
        }       
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m181026_091939_rename_affilifate_id_to_platform_id cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m181026_091939_rename_affilifate_id_to_platform_id cannot be reverted.\n";

        return false;
    }
    */
}
