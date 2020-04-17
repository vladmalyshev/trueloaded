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
 * Class m191203_164818_barcode_translations
 */
class m191203_164818_barcode_translations extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation( 'admin/main', [
            'TEXT_BARCODE' => 'Barcode',
        ]);
        
        $this->addTranslation( 'invoice', [
            'TEXT_BARCODE' => 'Barcode',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m191203_164818_barcode_translations cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191203_164818_barcode_translations cannot be reverted.\n";

        return false;
    }
    */
}
