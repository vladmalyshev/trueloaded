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
 * Class m190723_115016_theme_settings
 */
class m190723_115016_theme_settings extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('admin/design', [
            'TEXT_FONTS' => 'Fonts',
            'TEXT_FAVICON' => 'Favicon',
            'DEFAULT_IMAGE_FOR_CATEGORY' => 'Default image for category',
            'DEFAULT_IMAGE_FOR_PRODUCT' => 'Default image for product',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->removeTranslation('admin/design', [
            'TEXT_FONTS',
            'TEXT_FAVICON',
            'DEFAULT_IMAGE_FOR_CATEGORY',
            'DEFAULT_IMAGE_FOR_PRODUCT',
        ]);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190723_115016_theme_settings cannot be reverted.\n";

        return false;
    }
    */
}
