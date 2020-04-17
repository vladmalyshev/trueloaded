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
 * Class m190830_123328_promo_apdate_multidiscount
 */
class m190830_123328_promo_update_multidiscount extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('promotions_sets', 'promo_nindex', $this->integer()->notNull()->defaultvalue(0));
        $this->addTranslation('admin/promotions',[
            'TEXT_NOT_ALL_PRODS_NECESSARY' => 'Not all producta are necessary',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190830_123328_promo_apdate_multidiscount cannot be reverted.\n";
        $this->dropColumn('promotions_sets', 'promo_nindex');
        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190830_123328_promo_apdate_multidiscount cannot be reverted.\n";

        return false;
    }
    */
}
