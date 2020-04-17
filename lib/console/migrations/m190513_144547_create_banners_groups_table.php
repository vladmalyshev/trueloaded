<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%banners_groups}}`.
 */
class m190513_144547_create_banners_groups_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%banners_groups}}', [
            'id' => $this->primaryKey(),
            'banners_group' => $this->string()->notNull()->defaultValue(''),
            'width_from' => $this->integer()->notNull()->defaultValue(0),
            'width_to' => $this->integer()->notNull()->defaultValue(0),
            'image_width' => $this->integer()->notNull()->defaultValue(0),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%banners_groups}}');
    }
}
