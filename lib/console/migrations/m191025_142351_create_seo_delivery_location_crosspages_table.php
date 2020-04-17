<?php

use common\classes\Migration;

/**
 * Handles the creation of table `{{%seo_delivery_location_crosspages}}`.
 */
class m191025_142351_create_seo_delivery_location_crosspages_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
      $tbl = $this->getDb()->getTableSchema('seo_delivery_location_crosspages');

      if (is_null($tbl)) {
        $this->createTable('seo_delivery_location_crosspages', [
            'id' => $this->integer()->notNull()->defaultValue(0),
            'crosspage_id' => $this->integer()->notNull()->defaultValue(0),
        ]);
        $this->addPrimaryKey('', 'seo_delivery_location_crosspages', ['id', 'crosspage_id']);
      }

        $this->addTranslation('admin/design',[
            'TEXT_CROSS_PAGES' => 'Cross Pages',
        ]);
        $this->addTranslation('admin/main',[
            'TEXT_FIND_PAGES' => 'Find pages',
            'TEXT_PAGES' => 'Pages',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('seo_delivery_location_crosspages');

        $this->removeTranslation('admin/design',[
            'TEXT_CROSS_PAGES',
        ]);
        $this->removeTranslation('admin/main',[
            'TEXT_FIND_PAGES',
            'TEXT_PAGES',
        ]);
    }
}
