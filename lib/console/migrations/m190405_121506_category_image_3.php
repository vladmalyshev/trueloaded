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
 * Class m190405_121506_category_image_3
 */
class m190405_121506_category_image_3 extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('categories', 'categories_image_3', $this->string(128)->notNull());
        $this->addColumn('categories_platform_settings', 'categories_image_3', $this->string(128)->notNull());

        $this->addTranslation('admin/categories', [
          'TEXT_HOMEPAGE_IMAGE_INTRO' => 'This image is shown on homepage',
          'TEXT_HOMEPAGE_IMAGE' => 'Homepage image',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn('categories', 'categories_image_3');
        $this->dropColumn('categories_platform_settings', 'categories_image_3');

        $this->removeTranslation('admin/categories', [
          'TEXT_HOMEPAGE_IMAGE_INTRO' ,
          'TEXT_HOMEPAGE_IMAGE',
        ]);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190405_121506_category_image_3 cannot be reverted.\n";

        return false;
    }
    */
}
