<?php

use common\classes\Migration;

/**
 * Handles the creation of table `{{%plain_products_name_search}}`.
 */
class m190409_154347_create_plain_products_name_search_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%plain_products_name_search}}', [
            'id' => $this->primaryKey(),
            'status' => $this->integer()->notNull()->defaultValue(0),
            'language_id' => $this->integer()->notNull()->defaultValue(0),
            'products_name' => $this->string(255)->notNull(),
            'search_details' =>  $this->getDb()->getSchema()->createColumnSchemaBuilder('longtext')->notNull(),
            'search_soundex' => $this->getDb()->getSchema()->createColumnSchemaBuilder('longtext')->notNull(),
            //'search_fulltext' => $this->getDb()->getSchema()->createColumnSchemaBuilder('longtext')->notNull(),
            'tmp_prid' => $this->integer()->notNull()->defaultValue(0),
        ]);

        $this->createIndex(
            'idx_status',
            '{{%plain_products_name_search}}',
            ['status', 'language_id']
        );

        $this->createIndex(
            'idx_id_status',
            '{{%plain_products_name_search}}',
            ['id', 'language_id']
        );

        $this->createIndex(
            'idx_language',
            '{{%plain_products_name_search}}',
            ['language_id']
        );

        $this->createIndex(
            'idx_tmp_prid',
            '{{%plain_products_name_search}}',
            ['tmp_prid']
        );
        
        $this->createIndex(
            'idx_products_name',
            '{{%plain_products_name_search}}',
            'products_name'
        );

        $this->createTable('{{%plain_products_name_to_products}}', [
          'plain_id' => $this->integer()->notNull()->defaultValue(0),
          'products_id' => $this->integer()->notNull()->defaultValue(0),
          'platform_id' => $this->integer()->notNull()->defaultValue(0),
          'department_id' => $this->integer()->notNull()->defaultValue(0),
          'last_modified' => $this->dateTime()->notNull()->defaultValue(new \yii\db\Expression( 'now()')),
        ]);

        $this->addPrimaryKey(null, '{{%plain_products_name_to_products}}', [
          'plain_id',
          'products_id',
          'platform_id',
          'department_id'
        ]);

        $this->createIndex(
            'idx_last_modified',
            '{{%plain_products_name_to_products}}',
            ['last_modified', 'products_id']
        );
        /*
         $this->getDb()->createCommand("INSERT INTO plain_products_name_search "
             . "             (status, language_id, tmp_prid, products_name, search_details) "
             . " select distinct 1, language_id, products_id, products_name, concat(products_name, ' ', products_description_short, ' ', products_description,' ', products_head_keywords_tag) from products_description ")->execute();

          $this->getDb()->createCommand("INSERT INTO plain_products_name_to_products "

              . " select pln.id, pln.tmp_prid, pl.platform_id, ifnull(dpp.departments_id, 0), now() "
              . " from plain_products_name_search pln "
              . "   left join departments_products dpp on pln.tmp_prid=dpp.products_id "
              . "   join platforms_products pl on pln.tmp_prid=pl.products_id  "

              . "")->execute();*/

          $this->getDb()->createCommand("update configuration set set_function=\"cfgMultiSortable(array('SKU','ASIN', 'EAN', 'ISBN','UPC', 'Description', 'Categories', 'Attributes', 'Properties', 'Manufacturer'),\", configuration_value=concat('Description, Manufacturer, ', configuration_value, ', Categories, Attributes, Properties')  where configuration_key='SEARCH_BY_ELEMENTS' "
              . "")->execute();
          $this->getDb()->createCommand("update platforms_configuration set set_function=\"cfgMultiSortable(array('SKU','ASIN', 'EAN', 'ISBN','UPC', 'Description', 'Categories', 'Attributes', 'Properties', 'Manufacturer'),\", configuration_value=concat('Description, Manufacturer, ', configuration_value, ', Categories, Attributes, Properties')  where configuration_key='SEARCH_BY_ELEMENTS' "
              . "")->execute();

          $this->getDb()->createCommand("update configuration set set_function=\"tep_cfg_select_option(array('Like','Soundex', 'Fulltext'),\", configuration_group_id =333 where configuration_key='MSEARCH_ENABLE'")->execute();
          $this->getDb()->createCommand("update platforms_configuration set set_function=\"tep_cfg_select_option(array('Like','Soundex', 'Fulltext'),\", configuration_group_id =333 where configuration_key='MSEARCH_ENABLE' ")->execute();
          echo date('H:i:s') ." index start \n";
          \common\extensions\PlainProductsDescription\PlainProductsDescription::reindex(false, 100000);
          echo date('H:i:s') ." index end \n";

          $this->getDb()->createCommand('alter table {{%plain_products_name_search}} ADD FULLTEXT ft_search_details(search_details)')->execute();
          
//        $r = common\models\Products::find()->with()

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%plain_products_name_search}}');
        $this->dropTable('{{%plain_products_name_to_products}}');
    }
}
