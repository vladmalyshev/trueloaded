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
 * Class m200324_173629_cart_free_delivery
 */
class m200324_173629_cart_free_delivery extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('main', [
            'TEXT_SPEND_ANOTHER_XX_FOR_FREE_DELIVERY' => 'Spend another <strong>%s</strong> for free delivery',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200324_173629_cart_free_delivery cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200324_173629_cart_free_delivery cannot be reverted.\n";

        return false;
    }
    */
}
