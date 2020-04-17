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
 * Class m191002_145558_translation_listings_on_product_info
 */
class m191002_145558_translation_listings_on_product_info extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
      $this->addTranslation('main', [
        'TEXT_SELECT_ALL' => 'Select all',
        'TEXT_SELECT_NONE' => 'Select none',
        'ADD_ALL_TO_CART' => 'Add all to cart',
      ]);

      $q1 = (new yii\db\Query())->from('configuration')->where(['configuration_key' => 'PRODUCTS_ATTRIBUTES_SHOW_SELECT'])->count();
      if ($q1 == 0) {
        $this->insert('configuration',[
            'configuration_title' => 'Show "Please select" option in product attributes',
            'configuration_key' => 'PRODUCTS_ATTRIBUTES_SHOW_SELECT',
            'configuration_value' => 'False',
            'configuration_description' => 'Show "Please select" option in product attributes',
            'configuration_group_id' => 8,
            'set_function' => "tep_cfg_select_option(array('True', 'False'),",
            'date_added' => new \yii\db\Expression('NOW()'),
        ]);
      }




    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
      $this->removeTranslation('main', [
        'TEXT_SELECT_ALL',
        'TEXT_SELECT_NONE',
        'ADD_ALL_TO_CART'
      ]);
//        echo "m191002_145558_translation_listings_on_product_info cannot be reverted.\n";

//        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191002_145558_translation_listings_on_product_info cannot be reverted.\n";

        return false;
    }
    */
}
