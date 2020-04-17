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
 * Class m190802_101042_add_cloud_printer_documents
 */
class m190802_101042_add_cloud_printer_documents extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->isTableExists('cloud_printers_documents')){
            $this->createTable('cloud_printers_documents', [
                'id' => \yii\db\Schema::TYPE_PK,
                'printer_id' => $this->integer(),
                'document_name' => $this->string(128),
            ]);
        }
        
        $this->addTranslation('admin/printers', [
            'ERROR_INVALID_DOCUMENT_ASSIGNMENT' => 'Invalid document assignement',
            'TEXT_ACCEPTED_SUCCESSFULY' => 'Accepted sucessfully',
            'TEXT_ACCEPTED_ALREADY' => 'Already acepted',
            'TEXT_ASSIGNMENT_DOCUMENTS' => 'Assigned Documents',
            'TEXT_VIEW_PRINTER_OPTIONS' => 'View Printer options',
            'TEXT_UNLINK_PRINTER' => 'Unlink Printer',
            'TEXT_TEST_PRINTER' => 'Test Printer',
            'TEXT_CLOUD_SERVICE_NAME' => 'Service Name',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190802_101042_add_cloud_printer_documents cannot be reverted.\n";
        $this->dropTable('cloud_printers_documents');
        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190802_101042_add_cloud_printer_documents cannot be reverted.\n";

        return false;
    }
    */
}
