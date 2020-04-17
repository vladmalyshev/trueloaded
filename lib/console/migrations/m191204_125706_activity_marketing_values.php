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
 * Class m191204_125706_activity_marketing_values
 */
class m191204_125706_activity_marketing_values extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {


      $this->insert('configuration',[
            'configuration_title' => 'Product activity stat view coefficient',
            'configuration_key' => 'PRODUCTS_ACTIVITY_VIEW_COEFFICIENT',
            'configuration_value' => '0',
            'configuration_description' => 'Product activity stat marketing: view coefficient',
            'configuration_group_id' => 1,
            'sort_order' => 1000,
            'date_added' => new \yii\db\Expression('NOW()'),
        ]);
        $this->insert('configuration',[
            'configuration_title' => 'Product activity stat view min',
            'configuration_key' => 'PRODUCTS_ACTIVITY_VIEW_MIN',
            'configuration_value' => '0',
            'configuration_description' => 'Product activity stat marketing: view min value',
            'configuration_group_id' => 1,
            'sort_order' => 1001,
            'date_added' => new \yii\db\Expression('NOW()'),
        ]);
        $this->insert('configuration',[
            'configuration_title' => 'Product activity stat view max',
            'configuration_key' => 'PRODUCTS_ACTIVITY_VIEW_MAX',
            'configuration_value' => '0',
            'configuration_description' => 'Product activity stat marketing: view max value',
            'configuration_group_id' => 1,
            'sort_order' => 1002,
            'date_added' => new \yii\db\Expression('NOW()'),
        ]);
        $this->insert('configuration',[
            'configuration_title' => 'Product activity stat in cart coefficient',
            'configuration_key' => 'PRODUCTS_ACTIVITY_CART_COEFFICIENT',
            'configuration_value' => '0',
            'configuration_description' => 'Product activity stat marketing: in cart coefficient',
            'configuration_group_id' => 1,
            'sort_order' => 1003,
            'date_added' => new \yii\db\Expression('NOW()'),
        ]);
        $this->insert('configuration',[
            'configuration_title' => 'Product activity stat in cart min',
            'configuration_key' => 'PRODUCTS_ACTIVITY_CART_MIN',
            'configuration_value' => '0',
            'configuration_description' => 'Product activity stat marketing: in cart min value',
            'configuration_group_id' => 1,
            'sort_order' => 1003,
            'date_added' => new \yii\db\Expression('NOW()'),
        ]);
        $this->insert('configuration',[
            'configuration_title' => 'Product activity stat in cart max',
            'configuration_key' => 'PRODUCTS_ACTIVITY_CART_MAX',
            'configuration_value' => '0',
            'configuration_description' => 'Product activity stat marketing: in cart max value',
            'configuration_group_id' => 1,
            'sort_order' => 1005,
            'date_added' => new \yii\db\Expression('NOW()'),
        ]);
        
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
      $this->delete('configuration', ['configuration_key' => ['PRODUCTS_ACTIVITY_VIEW_COEFFICIENT', 'PRODUCTS_ACTIVITY_VIEW_MIN', 'PRODUCTS_ACTIVITY_VIEW_MAX', 'PRODUCTS_ACTIVITY_CART_COEFFICIENT', 'PRODUCTS_ACTIVITY_CART_MIN', 'PRODUCTS_ACTIVITY_CART_MAX']]);
        //echo "m191204_125706_activity_marketing_values cannot be reverted.\n";
        //return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191204_125706_activity_marketing_values cannot be reverted.\n";

        return false;
    }
    */
}
