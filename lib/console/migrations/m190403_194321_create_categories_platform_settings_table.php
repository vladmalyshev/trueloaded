<?php

use common\classes\Migration;

/**
 * Handles the creation of table `{{%categories_platform_settings}}`.
 */
class m190403_194321_create_categories_platform_settings_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%categories_platform_settings}}', [
            'categories_id' => $this->integer()->notNull(),
            'platform_id' => $this->integer()->notNull(),
            'categories_image' => $this->string()->notNull(),
            'categories_image_2' => $this->string()->notNull(),
            'show_on_home' => $this->integer(1)->notNull(),
            'maps_id' => $this->integer()->notNull(),
        ]);

        $this->createIndex(
            'idx_categories_id',
            'categories_platform_settings',
            ['categories_id', 'platform_id'],
            true
        );

        $this->createIndex(
            'idx_platform_id',
            'categories_platform_settings',
            ['platform_id', 'show_on_home']
        );

        $this->addTranslation('admin/categories', [
          'TEXT_GALLERY_IMAGE_INTRO' => 'This image is shown with other (sub-)categores',
          'TEXT_HERO_IMAGE_INTRO' => 'This image is shown above the products list',
          'TEXT_HERO_IMAGE' => 'Hero image',
        ]);

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%categories_platform_settings}}');
        $this->removeTranslation('admin/categories',
             [
                'TEXT_GALLERY_IMAGE_INTRO',
                'TEXT_HERO_IMAGE_INTRO',
                'TEXT_HERO_IMAGE',
              ]

            );
    }
}
