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
 * Class m190828_092848_platform_logo
 */
class m190828_092848_platform_logo extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn("platforms", 'logo', $this->string(255)->notNull());
        $this->addTranslation('admin/design', [
            'TEXT_WIDGET_SETTINGS' => 'Widget settings',
            'TEXT_USE_LOGO_FROM' => 'Use logo from',
        ]);
        $this->addTranslation('admin/platforms', [
            'TEXT_ORGANIZATION' => 'Organization',
            'TEXT_ORGANIZATION_SITE' => 'Organization Site',
            'TEXT_ORGANIZATION_TYPE' => 'Organization Type',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn("platforms", 'logo');
        $this->removeTranslation('admin/design', [
            'TEXT_WIDGET_SETTINGS',
            'TEXT_USE_LOGO_FROM',
        ]);
        $this->removeTranslation('admin/platforms', [
            'TEXT_ORGANIZATION',
            'TEXT_ORGANIZATION_SITE',
            'TEXT_ORGANIZATION_TYPE',
        ]);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190828_092848_platform_logo cannot be reverted.\n";

        return false;
    }
    */
}
