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
 * Class m190814_090551_social_links
 */
class m190814_090551_social_links extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn("socials", 'link', $this->string(255)->notNull());
        $this->addColumn("socials", 'image', $this->string(255)->notNull());
        $this->addColumn("socials", 'css_class', $this->string(255)->notNull());
        $this->addTranslation('admin/socials/edit',[
            'TEXT_AUTHORIZATION' => 'Authorization',
            'TEXT_CLIENT_ID' => 'Client ID',
            'TEXT_CLIENT_SECRET' => 'Client Secret',
            'TEXT_SOCIAL_HOMEPAGE_LINK' => 'Social homepage link',
            'TEXT_SOCIAL_CSS_CLASS' => 'Social css class',
            'TEXT_SOCIAL_IMAGE' => 'Social image',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn("socials", 'link');
        $this->dropColumn("socials", 'image');
        $this->dropColumn("socials", 'css_class');
        $this->removeTranslation('admin/socials/edit', [
            'TEXT_TOKENS',
        ]);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190814_090551_social_links cannot be reverted.\n";

        return false;
    }
    */
}
