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
 * Class m190906_105107_default_token
 */
class m190906_105107_default_token extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
      $table = $this->db->getTableSchema('payment_tokens', true);
      if (!isset($table->columns['is_default'])) {
        $this->addColumn('payment_tokens', 'is_default', $this->smallInteger(1)->notNull()->defaultValue(0));
        $tokens = (new \yii\db\Query())->select('min(payment_tokens_id) as payment_tokens_id, customers_id, payment_class')->groupBy('customers_id, payment_class')->from('payment_tokens')->all();
        $ids = \yii\helpers\ArrayHelper::getColumn($tokens, 'payment_tokens_id');
        $this->update('payment_tokens', ['is_default'=>1], ['payment_tokens_id' => $ids]);
      }

      $this->addTranslation('main', ['TEXT_DEFAULT' => 'Default']);

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
       // echo "m190906_105107_default_token cannot be reverted.\n";
      $this->dropColumn('payment_tokens', 'is_default');

       // return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190906_105107_default_token cannot be reverted.\n";

        return false;
    }
    */
}
