<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

use common\classes\Migration;

/**
 * Class m190719_102644_cloud_printers
 */
class m190719_102644_cloud_printers extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        
        if (!$this->isTableExists('cloud_services')){
            $this->createTable('cloud_services', [
                'id' => \yii\db\Schema::TYPE_PK,
                'service' => $this->string(128),
                'platform_id' => $this->integer()->notNull(),
                'key' => $this->text(),
                'date_added' => $this->dateTime(),
            ], 'engine=InnoDB DEFAULT CHARSET=utf8');
            $this->createIndex('idx_platform', 'cloud_services', ['platform_id']);
        }
        
        if (!$this->isTableExists('cloud_printers')){
            $this->createTable('cloud_printers', [
                'id' => \yii\db\Schema::TYPE_PK,
                'service_id' => $this->integer()->notNull(),
                'cloud_printer_id' => $this->string(128),
                'cloud_printer_name' => $this->string(128),
                'status' => $this->integer()->notNull()->defaultValue(0),
                'date_added' => $this->dateTime(),
            ], 'engine=InnoDB DEFAULT CHARSET=utf8');
            $this->createIndex('idx_service', 'cloud_printers', ['service_id']);
        }
        
        $this->addTranslation('admin/main', [
            'BOX_HEADING_PRINTERS' => 'Cloud Printers',
        ]);
        
        $this->addTranslation('admin/printers', [
            'HEADING_TITLE' => 'Cloud Printers',
            'TEXT_UNLINK_PRINTER_CONFIRM' => 'Do you confirm unlink this printer?',
            'TEXT_KEY' => 'Key',
            'TEXT_GET_PRINTERS' => 'Get cloud printers',
            'TEXT_ACCEPTED_PRINTERS' => 'Accepted printers',
            'TABLE_HEADING_PRINTER' => 'Printer'
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190719_102644_cloud_printers cannot be reverted.\n";
        
        $this->dropTable('cloud_services');
        $this->dropTable('cloud_printers');

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190719_102644_cloud_printers cannot be reverted.\n";

        return false;
    }
    */
}
