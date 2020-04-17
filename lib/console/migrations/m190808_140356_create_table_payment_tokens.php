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
 * Class m190808_140356_create_table_payment_tokens
 */
class m190808_140356_create_table_payment_tokens extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
      $tbl = $this->getDb()->getTableSchema('payment_tokens');

      if (is_null($tbl)) {
        $this->createTable('payment_tokens', [
          'payment_tokens_id' => $this->primaryKey(11),
          'customers_id' => $this->integer(11)->notNull()->defaultValue(0),
          'payment_class' => $this->string(127)->notNull()->defaultValue(''),
          'card_name' => $this->string(200)->notNull()->defaultValue(''),
          'card_type' => $this->string(20)->notNull()->defaultValue(''),
          'exp_date' => $this->string(10)->notNull()->defaultValue(''),
          'last_digits' => $this->string(20)->notNull()->defaultValue(''),
          'token' => $this->string(8096)->notNull()->defaultValue(''),
        ]);
      }
      $this->createIndex('IDX_customers_id', 'payment_tokens', 'customers_id');
      $this->createIndex('IDX_payment_class', 'payment_tokens', 'payment_class');

      $this->insert('configuration',[
          'configuration_title' => 'Allow save tokens (OpenSSL is required)',
          'configuration_key' => 'USE_TOKENS_IN_PAYMENT_METHODS',
          'configuration_value' => 'False',
          'configuration_description' => 'Switching it off will clean up all saved tokens in the system',
          'configuration_group_id' => 100684, //	Configuration :: Billing Customer Details
          'sort_order' => 35,
          'date_added' => new \yii\db\Expression('NOW()'),
          'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'),'
      ]);

      $this->addTranslation('main', [
        'PAYMENT_USE_TOKEN_TEXT' => 'Save credit card details at %s to use in next payment',
        'PAYMENT_USE_DIFFERENT_CARD' => 'Different card',
      ]);

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
      $this->dropTable('payment_tokens');
      $this->delete('configuration', ['configuration_key' => 'USE_TOKENS_IN_PAYMENT_METHODS']);

//        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190808_140356_create_table_payment_tokens cannot be reverted.\n";

        return false;
    }
    */
}
