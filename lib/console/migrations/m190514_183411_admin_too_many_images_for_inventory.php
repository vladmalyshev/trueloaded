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
 * Class m190514_183411_admin_too_many_images_for_inventory
 */
class m190514_183411_admin_too_many_images_for_inventory extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('admin/main',[
            'TOO_MANY' => '....'
        ]);
        $this->addTranslation('admin/categories',[
            'TOO_MANY_IMAGES_SETUP_AT_IMAGES_TAB' => 'Too many images - setup at Images tab'
        ]);
        $this->addTranslation('admin/categories',[
            'TOO_MANY_IMAGES_SETUP_ATTRIBUTES' => 'too many variations and images. Option is not available, assign images to attributes instead'
        ]);

        $this->insert('configuration',[
            'configuration_title' => 'Disable inventory images (edit product too slow)',
            'configuration_key' => 'ADMIN_TOO_MANY_IMAGES',
            'configuration_value' => '20',
            'configuration_description' => 'Disable inventory image module (admin) if more than NN images are uploaded ',
            'configuration_group_id' => 3,
            'sort_order' => 3245,
            'date_added' => new \yii\db\Expression('NOW()'),
            'use_function' => '',
            'set_function' => ''
        ]);

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
      $this->delete( 'configuration', ['configuration_key' => 'ADMIN_TOO_MANY_IMAGES'] );
        //echo "set ADMIN_TOO_MANY_IMAGES to high value and delete translation if required.\n";

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190514_183411_admin_too_many_images_for_inventory cannot be reverted.\n";

        return false;
    }
    */
}
