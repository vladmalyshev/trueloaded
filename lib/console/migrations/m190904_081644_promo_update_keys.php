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
 * Class m190904_081644_promo_update_keys
 */
class m190904_081644_promo_update_keys extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->dropPrimaryKey('BTREE', 'promotions_sets_conditions');
        $this->addColumn('promotions_sets_conditions', 'sets_id', \yii\db\Schema::TYPE_PK);
        $this->createIndex('promo_idx', 'promotions_sets_conditions', ['promo_id']);
        $this->createIndex('promo_sets_idx', 'promotions_sets_conditions', ['promo_sets_id']);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190904_081644_promo_update_keys cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190904_081644_promo_update_keys cannot be reverted.\n";

        return false;
    }
    */
}
