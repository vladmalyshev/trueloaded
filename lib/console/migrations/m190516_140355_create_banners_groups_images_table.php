<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%banners_groups_images}}`.
 */
class m190516_140355_create_banners_groups_images_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%banners_groups_images}}', [
            'id' => $this->primaryKey(),
            'banners_id' => $this->integer()->notNull()->defaultValue(0),
            'language_id' => $this->integer()->notNull()->defaultValue(0),
            'image_width' => $this->integer()->notNull()->defaultValue(0),
            'image' => $this->string()->notNull()->defaultValue(''),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%banners_groups_images}}');
    }
}
