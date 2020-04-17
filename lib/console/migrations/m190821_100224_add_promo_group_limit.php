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
 * Class m190821_100224_add_promo_group_limit
 */
class m190821_100224_add_promo_group_limit extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('promotions_conditions', 'promo_limit', $this->integer()->notNull()->defaultValue('0'));
        $this->addColumn('promotions_conditions', 'promo_limit_block', $this->integer(1)->notNull()->defaultValue('0'));
        $this->addTranslation('promotions', [
            'TEXT_PROMO_LIMIT_EXCEEDED' => 'Order Limit Exceeded',
            'TEXT_INFO_CART_HAS_LIMITED_PRODUCTS' => 'Sorry, you cannot continue purchase. There are products in cart with limit exceeded'
        ]);
        $this->addTranslation('admin/promotions', [
            'TEXT_PROMO_ORDER_LIMIT' => 'Order Limit',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn('promotions_conditions', 'promo_limit');
        $this->dropColumn('promotions_conditions', 'promo_limit_block');

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190821_100224_add_promo_group_limit cannot be reverted.\n";

        return false;
    }
    */
}
