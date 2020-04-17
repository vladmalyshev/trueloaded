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
 * Class m190822_164407_design_text
 */
class m190822_164407_design_text extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
      $this->update('translation', ['translation_entity' => 'admin/main'], 'translation_key="TEXT_CART_BTN" and translation_entity="admin/categories"');
      $this->addTranslation('admin/main', [
        'TEXT_CUSTOM_TITLE_CONSTANT' => 'Custom title constant',
        'TEXT_CUSTOM_TITLE_CONSTANT_DESCRIPTION' => 'Add new key at Admin > Design & CMS > Translation, with unique "CUSTOM_***" key and "main" entity. Then save the key here.',
        'TEXT_CUSTOM_GET' => 'Custom params',
        'TEXT_CUSTOM_GET_DESCRIPTION' => 'HTTP get style: pr208[]=1&at2[]=3&brand[]=3&pfrom=12.55',
        'TEXT_FROM_SAME_CATEGORY' => 'same category',
        'TEXT_SAME_PROPERTIES_VALUE_LIST' => 'Same properties values',
        'TEXT_SAME_PROPERTIES_VALUE_LIST_DESCRIPTION' => '(as current product) Comma separated list of property ids',
        
      ]);

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        //echo "m190822_164407_design_text cannot be reverted.\n";

        //return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190822_164407_design_text cannot be reverted.\n";

        return false;
    }
    */
}
