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
 * Class m200108_141812_add_bonus_points_rates_to_customers_group
 */
class m200108_141812_add_bonus_points_rates_to_customers_group extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('groups', 'bonus_points_currency_rate', $this->decimal(8, 5)->notNull()->defaultValue(0));
        $this->addTranslation('main',[
            'BONUS_POINTS_CURRENCY_RATE' => 'Bonus Points/Currency - Rate',
            'BONUS_POINTS_CONVERTER' => 'Bonus Points Converter',
            'BONUS_POINTS_CONVERTER_INTRO_TEXT' => "Type in either box and we'll convert them for you",
            'NAME_BONUS_POINTS' => 'Bonus Points',
            'TEXT_CURRENCY' => 'Currency',
            'TEXT_TRANSFER_BONUS_POINTS_TO_CREDIT_AMOUNT' => 'Transfer All Bonus Point To Credit Amount',
            'TEXT_CONVERT' => 'Convert',
        ]);
        $this->addTranslation('admin/main',[
            'BONUS_POINTS_CURRENCY_RATE' => 'Bonus Points/Currency - Rate',
            'BONUS_POINTS_CONVERTER' => 'Bonus Points Converter',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->removeTranslation('main',[
            'BONUS_POINTS_CURRENCY_RATE',
            'BONUS_POINTS_CONVERTER',
            'BONUS_POINTS_CONVERTER_INTRO_TEXT',
            'NAME_BONUS_POINTS',
            'TEXT_CURRENCY',
            'TEXT_TRANSFER_BONUS_POINTS_TO CREDIT_AMOUNT',
            'TEXT_CONVERT',
        ]);
        $this->removeTranslation('admin/main',[
            'BONUS_POINTS_CURRENCY_RATE',
            'BONUS_POINTS_CONVERTER',
        ]);
        $this->dropColumn('groups', 'bonus_points_currency_rate');
    }

}
