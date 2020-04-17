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
 * Class m190617_100606_add_order_invoice_comment
 */
class m190617_100606_add_order_invoice_comment extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->isTableExists('orders_comments')){
            $this->createTable('orders_comments', [
                'orders_comments_id' => \yii\db\Schema::TYPE_PK,
                'orders_id' => $this->integer()->notNull(),
                'comments' => $this->text(),
                'date_added' => $this->dateTime(),
                'admin_id' => $this->integer(),
                'for_invoice' => $this->integer(1)->notNull()->defaultValue('0'),
                'visible' => $this->string(255),
            ],'ENGINE=InnoDB DEFAULT CHARSET=utf8');
            
            $this->createIndex('idx_order', 'orders_comments', 'orders_id');
            $this->createIndex('idx_admin', 'orders_comments', 'admin_id');
        }
        
        $this->addTranslation('admin/orders', [
            'TEXT_CUSTOMER_COMMENTS' => 'Customers Commments',
            'TEXT_INNER_COMMENTS' => 'Inner Commments',
            'TEXT_INVOICE_COMMENTS' => 'Invoice Commments',
            'TEXT_ADD_COMMENT' => 'Add comment',
            'TEXT_VISIBLE_TO' => 'Visible To:',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        return $this->dropTable('orders_comments');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190617_100606_add_order_invoice_comment cannot be reverted.\n";

        return false;
    }
    */
}
