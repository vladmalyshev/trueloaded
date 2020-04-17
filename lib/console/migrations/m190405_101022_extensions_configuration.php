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
 * Class m190405_101022_extensions_configuration
 */
class m190405_101022_extensions_configuration extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('admin/main', [
            'BOX_MODULES_EXTENSIONS'  => 'Extensions',
            'HEADING_TITLE_MODULES_EXTENSIONS' => 'Extensions Modules',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->removeTranslation('admin/main', [
            'BOX_MODULES_EXTENSIONS',
            'HEADING_TITLE_MODULES_EXTENSIONS'
        ]);
        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190405_101022_extensions_configuration cannot be reverted.\n";

        return false;
    }
    */
}
