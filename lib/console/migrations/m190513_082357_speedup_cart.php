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
 * Class m190513_082357_speedup_cart
 */
class m190513_082357_speedup_cart extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createIndex('code_idx', 'modules_visibility', ['code']);
        $this->createIndex('products_idx', 'collections_to_products', ['products_id', 'collections_id']);
        $this->createIndex('customer_idx', 'virtual_gift_card_basket', ['customers_id', 'session_id(32)']);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190513_082357_speedup_cart cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190513_082357_speedup_cart cannot be reverted.\n";

        return false;
    }
    */
}
