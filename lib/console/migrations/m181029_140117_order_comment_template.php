<?php

use common\classes\Migration;

/**
 * Class m181029_140117_order_comment_template
 */
class m181029_140117_order_comment_template extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $tableOption = 'ENGINE=InnoDB DEFAULT CHARSET=utf8';

        $this->createTable('orders_comment_template', [
            'comment_template_id' => $this->primaryKey(),
            'status' => $this->boolean()->notNull()->defaultValue(1),
            'visibility' => $this->string(255)->notNull()->defaultValue(',order,'),
            'sort_order' => $this->integer(2)->defaultValue(0),
            'hide_for_platforms' => $this->string(1024)->notNull()->defaultValue(''),
            'hide_from_admin' => $this->string(1024)->notNull()->defaultValue(''),
            'date_modified' => $this->dateTime()->null(),
            'date_added' => $this->dateTime()->notNull(),
        ],$tableOption);
        $this->createIndex('orders_comment_template_idx','orders_comment_template', ['status','sort_order']);

        $this->createTable('orders_comment_template_text',[
            'comment_template_id' => $this->integer(4)->notNull(),
            'language_id' => $this->integer(4)->notNull(),
            'name' => $this->string(255),
            'comment_template' => $this->text()->notNull(),
        ],$tableOption);
        $this->addPrimaryKey('orders_comment_template_text_pk','orders_comment_template_text',['comment_template_id','language_id']);

        $this->addTranslation('admin/main',[
            'BOX_ORDERS_COMMENT_TEMPLATE' => 'Comment templates',
            'TEXT_COMMENT_TEMPLATE_LABEL' => 'Comment template',
        ]);
        $this->appendAcl(['TEXT_SETTINGS', 'BOX_LOCALIZATION_ORDERS_STATUS', 'BOX_ORDERS_COMMENT_TEMPLATE']);
        $this->addAdminMenuAfter([
            'path' => 'orders-comment-template',
            'title' => 'BOX_ORDERS_COMMENT_TEMPLATE'
        ],'BOX_ORDERS_STATUS');

        $this->addTranslation('admin/orders-comment-template',[
            'HEADING_TITLE' => 'Comment templates',
            'TEXT_COMMENT_TEMPLATE_NAME' => 'Template name',
            'TEXT_BTN_NEW_ORDERS_COMMENT_TEMPLATE' => 'Create new comment template',
            'TEXT_DELETE_HEAD_CONFIRM' => 'Confirm removal',
            'TEXT_DELETE_CONFIRM' => 'Do you confirm? Remove &quot;%s&quot; comment template?',
            'TEXT_HEADING_NEW_ORDERS_COMMENT_TEMPLATE' => 'New comment template',
            'TEXT_HEADING_EDIT_ORDERS_COMMENT_TEMPLATE' => 'Edit comment template',
            'TEXT_TEMPLATE_NAME' => 'Name',
            'TEXT_TEMPLATE_COMMENT_TEXT' => 'Comment text',
            'TEXT_HIDE_FOR_PLATFORMS' => 'Hide for platform(s)',
            'TEXT_HIDE_FROM_ADMIN' => 'Don\'t show for admin',
            'TEXT_SUB_CUSTOMER_NAME' => 'Customer name',
            'TEXT_SUB_STORE_NAME' => 'Store name',
        ]);

/*
        $this->insert('orders_comment_template',[
            'sort_order' => 1,
            'date_added' => new \yii\db\Expression('NOW()'),
        ]);
        $id = $this->db->getLastInsertID();
        $this->db->createCommand(
            "INSERT INTO orders_comment_template_text (comment_template_id,language_id,name,comment_template) ".
            "SELECT {$id}, languages_id, 'test 1', 'test comment' FROM languages"
        )->execute();

        $this->insert('orders_comment_template',[
            'sort_order' => 2,
            'date_added' => new \yii\db\Expression('NOW()'),
        ]);
        $id = $this->db->getLastInsertID();
        $this->db->createCommand(
            "INSERT INTO orders_comment_template_text (comment_template_id,language_id,name,comment_template) ".
            "SELECT {$id}, languages_id, 'test 2', 'test comment\nline' FROM languages"
        )->execute();
*/
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropTable('orders_comment_template_text');
        $this->dropTable('orders_comment_template');

        $this->removeTranslation('admin/main', [
            'BOX_ORDERS_COMMENT_TEMPLATE',
        ]);
        $this->removeTranslation('admin/orders-comment-template');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m181029_140117_order_comment_template cannot be reverted.\n";

        return false;
    }
    */
}
