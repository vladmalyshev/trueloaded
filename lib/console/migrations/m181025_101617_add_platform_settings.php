<?php

use yii\db\Migration;

/**
 * Class m181025_101617_add_platform_price_settings
 */
class m181025_101617_add_platform_settings extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        
        $this->createTable('platforms_settings',[
            'platform_id' => $this->integer()->notNull(),
            'use_own_prices' => $this->integer(1)->defaultValue(0),
            'use_owner_prices' => $this->integer()->defaultValue(0),
            'use_own_descriptions'=> $this->integer(1)->defaultValue(0),
            'use_owner_descriptions'=> $this->integer(1)->defaultValue(0),
            'PRIMARY KEY(platform_id)'
        ], 'engine=InnoDB');
        $defaultPlatform = $this->getDb()->createCommand("select platform_id from platforms where is_default = 1")->queryOne();
        $allNonVirtual = $this->getDb()->createCommand("select platform_id from platforms where is_virtual = 0 and is_default = 0")->queryAll();
        $this->getDb()->createCommand("insert into platforms_settings select platform_id, 1, 0, 1, 0 from platforms where is_default = 1")->execute();//default
        if ($allNonVirtual && $defaultPlatform){
            foreach ($allNonVirtual as $platform){
                $this->getDb()->createCommand("insert into platforms_settings values (".intval($platform['platform_id']).", 0, ".intval($defaultPlatform['platform_id']).", 0, ".intval($defaultPlatform['platform_id'])." )")->execute();
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m181025_101617_add_platform_price_settings cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m181025_101617_add_platform_price_settings cannot be reverted.\n";

        return false;
    }
    */
}
