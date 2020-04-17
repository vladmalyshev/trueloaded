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
 * Class m200211_122730_sorting_translation
 */
class m200211_122730_sorting_translation extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
      $this->db->createCommand(
          "update translation t set translation_value='Model A-Z' where t.translation_entity in ('admin/main', 'admin/design', 'main') and translation_key='TEXT_BY_MODEL' and t.translation_value like 'model' "
          )->execute();
      $this->db->createCommand(
          "update translation t set translation_value='Model Z-A' where t.translation_entity in ('admin/main', 'admin/design', 'main') and translation_key='TEXT_BY_MODEL_TO_LESS' and t.translation_value in ('model', 'model desc') "
          )->execute();
      
      $this->db->createCommand(
          "update translation t set translation_value='Name A-Z' where t.translation_entity in ('admin/main', 'admin/design', 'main') and translation_key='TEXT_BY_NAME' and t.translation_value like 'name' "
          )->execute();
      $this->db->createCommand(
          "update translation t set translation_value='Name Z-A' where t.translation_entity in ('admin/main', 'admin/design', 'main') and translation_key='TEXT_BY_NAME_TO_LESS' and t.translation_value in ('name', 'name desc') "
          )->execute();

      $this->db->createCommand(
          "update translation t set translation_value='Brand A-Z' where t.translation_entity in ('admin/main', 'admin/design', 'main') and translation_key='TEXT_BY_MANUFACTURER' and t.translation_value like 'manufacturer' "
          )->execute();
      $this->db->createCommand(
          "update translation t set translation_value='Brand Z-A' where t.translation_entity in ('admin/main', 'admin/design', 'main') and translation_key='TEXT_BY_MANUFACTURER_TO_LESS' and t.translation_value in ('manufacturer', 'manufacturer desc') "
          )->execute();

      $this->db->createCommand(
          "update translation t set translation_value='Price Low to High' where t.translation_entity in ('admin/main', 'admin/design', 'main') and translation_key='TEXT_BY_PRICE' and t.translation_value like 'price' "
          )->execute();
      $this->db->createCommand(
          "update translation t set translation_value='Price High to Low' where t.translation_entity in ('admin/main', 'admin/design', 'main') and translation_key='TEXT_BY_PRICE_TO_LESS' and t.translation_value in ('price', 'price desc') "
          )->execute();
      
      $this->db->createCommand(
          "update translation t set translation_value='Quantity Low to High' where t.translation_entity in ('admin/main', 'admin/design', 'main') and translation_key='TEXT_BY_QUANTITY' and t.translation_value like 'quantity' "
          )->execute();
      $this->db->createCommand(
          "update translation t set translation_value='Quantity High to Low' where t.translation_entity in ('admin/main', 'admin/design', 'main') and translation_key='TEXT_BY_QUANTITY_TO_LESS' and t.translation_value in ('quantity', 'quantity desc') "
          )->execute();
      
      $this->db->createCommand(
          "update translation t set translation_value='Weight Low to High' where t.translation_entity in ('admin/main', 'admin/design', 'main') and translation_key='TEXT_BY_WEIGHT' and t.translation_value like 'weight' "
          )->execute();
      $this->db->createCommand(
          "update translation t set translation_value='Weight High to Low' where t.translation_entity in ('admin/main', 'admin/design', 'main') and translation_key='TEXT_BY_WEIGHT_TO_LESS' and t.translation_value in ('weight', 'weight desc') "
          )->execute();

      $this->db->createCommand(
          "update translation t set translation_value='Popularity Low to High' where t.translation_entity in ('admin/main', 'admin/design', 'main') and translation_key='TEXT_BY_POPULARITY' and t.translation_value like 'popularity' "
          )->execute();
      $this->db->createCommand(
          "update translation t set translation_value='Popularity High to Low' where t.translation_entity in ('admin/main', 'admin/design', 'main') and translation_key='TEXT_BY_POPULARITY_TO_LESS' and t.translation_value in ('popularity', 'popularity desc') "
          )->execute();
      
      $this->db->createCommand(
          "update translation t set translation_value='Date New to Old' where t.translation_entity in ('admin/main', 'admin/design', 'main') and translation_key='TEXT_BY_DATE' and t.translation_value like 'date' "
          )->execute();
      $this->db->createCommand(
          "update translation t set translation_value='Date Old to New' where t.translation_entity in ('admin/main', 'admin/design', 'main') and translation_key='TEXT_BY_DATE_TO_LESS' and t.translation_value in ('date', 'date desc') "
          )->execute();

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        //echo "m200211_122730_sorting_translation cannot be reverted.\n";

        //return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200211_122730_sorting_translation cannot be reverted.\n";

        return false;
    }
    */
}
