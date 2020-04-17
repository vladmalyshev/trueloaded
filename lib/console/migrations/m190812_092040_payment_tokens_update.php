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
 * Class m190812_092040_payment_tokens_update
 */
class m190812_092040_payment_tokens_update extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
      $this->addTranslation('account',
          [
            'CONFIRM_DELETE_TOKEN' => 'Are you sure you want to delete the following token',
            'SMALL_IMAGE_RENAME' => 'rename',
            'HEADING_TITLE_TOKENS' => 'Saved Tokens',
            'PAYMENT_TOKEN_WASNT_UPDATED' => 'There was a problem saving token',
            'PAYMENT_TOKEN_WASNT_DELETED' => 'There was a problem deleting token',
            'PAYMENT_TOKEN_UPDATED' => 'Payment token has been renamed',
            'PAYMENT_TOKEN_DELETED' => 'Payment token has been deleted',
            'ACCOUNT_NO_TOKENS' => 'None payment tokens found'
          ]);
      $this->addTranslation('admin/main',
          [
            'TEXT_TOKENS' => 'Credit Card tokens'
          ]);

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
      $this->removeTranslation('account', ['CONFIRM_DELETE_TOKEN']);
      $this->removeTranslation('admin/main', ['TEXT_TOKENS']);
        //echo "m190812_092040_payment_tokens_update cannot be reverted.\n";
        //return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190812_092040_payment_tokens_update cannot be reverted.\n";

        return false;
    }
    */
}
