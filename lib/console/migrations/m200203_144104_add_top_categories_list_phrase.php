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
 * Class m200203_144104_add_top_categories_list_phrase
 */
class m200203_144104_add_top_categories_list_phrase extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('admin/google-categories',[
            'CATEGORIES_GOOGLE_PRODUCTTYPE_LIST' => 'Categories',
        ]);

        


    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->removeTranslation('admin/google-categories',[
            'CATEGORIES_GOOGLE_PRODUCTTYPE_LIST',           
        ]);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200203_144104_add_top_categories_list_phrase cannot be reverted.\n";

        return false;
    }
    */
}
