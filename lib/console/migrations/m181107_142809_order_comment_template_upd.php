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
 * Class m181107_142809_order_comment_template_upd
 */
class m181107_142809_order_comment_template_upd extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('orders_comment_template', 'show_for_admin_group', $this->string(1024)->notNull()->defaultValue(',*,')->after('hide_from_admin'));

        $this->addTranslation('admin/orders-comment-template',[
            'TEXT_ONLY_FOR_ADMIN_GROUP' => 'Show for manager groups',]
        );
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn('orders_comment_template','show_for_admin_group');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m181107_142809_order_comment_template_upd cannot be reverted.\n";

        return false;
    }
    */
}
