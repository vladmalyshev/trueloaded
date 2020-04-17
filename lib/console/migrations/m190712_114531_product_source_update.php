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
 * Class m190712_114531_product_source_update
 */
class m190712_114531_product_source_update extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('products', 'source', $this->string(32)->notNull()->defaultValue(''));
        $this->createIndex('products_source','products', ['source']);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropIndex('products_source','products');
        $this->dropColumn('products', 'source');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190712_114531_product_source_update cannot be reverted.\n";

        return false;
    }
    */
}
