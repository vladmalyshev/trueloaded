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
 * Class m190729_163823_promotion_set_up
 */
class m190729_163823_promotion_set_up extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn("promotions_sets", 'promo_qindex', $this->integer()->defaultValue(0)->notNull());
        $this->addTranslation('admin/promotions',[
            'TEXT_ALL_PRODS_SAME_QTY' => 'All products have equal Qty'
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190729_163823_promotion_set_up cannot be reverted.\n";
        $this->dropColumn("promotions_sets", 'promo_qindex');
        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190729_163823_promotion_set_up cannot be reverted.\n";

        return false;
    }
    */
}
