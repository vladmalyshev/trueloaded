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
 * Class m191214_065848_bonus_points_currency
 */
class m191214_065848_bonus_points_currency extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        //$this->alterColumn('products','bonus_points_price', $this->decimal(15,4)->notNull()->defaultValue('0.0000'));
        //$this->alterColumn('products','bonus_points_cost', $this->decimal(15,4)->notNull()->defaultValue('0.0000'));
        //$this->alterColumn('products_prices','bonus_points_cost', $this->decimal(15,4)->notNull()->defaultValue('-2.0000'));

        $this->insert(
            'configuration',
            [
                'configuration_title' => 'Bonus points to currency coefficient',
                'configuration_key' => 'BONUS_POINT_CURRENCY_COEFFICIENT',
                'configuration_description' => 'To activate recalculate bonus points to currency enter coefficient',
                'configuration_group_id' => 100676,
                'configuration_value' => '',
                'date_added' => new \yii\db\Expression('NOW()'),
                'sort_order' => 2
            ]
        );
        $this->insert(
            'configuration',
            [
                'configuration_title' => 'Transfer points to credit amount',
                'configuration_key' => 'BONUS_POINT_TO_CREDIT_AMOUNT',
                'configuration_description' => 'Allow users to transfer bonus points to credit amount',
                'configuration_group_id' => 100676,
                'configuration_value' => 'False',
                'date_added' => new \yii\db\Expression('NOW()'),
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'),',
                'sort_order' => 3
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        //$this->alterColumn('products','bonus_points_price', $this->decimal(12,2)->notNull()->defaultValue('0.00'));
        //$this->alterColumn('products','bonus_points_cost', $this->decimal(15,2)->notNull()->defaultValue('0.00'));
        //$this->alterColumn('products_prices','bonus_points_cost', $this->decimal(15,2)->notNull()->defaultValue('-2.00'));
        $this->delete('configuration', ['configuration_key' => 'BONUS_POINT_CURRENCY_COEFFICIENT']);
        $this->delete('configuration', ['configuration_key' => 'BONUS_POINT_TO_CREDIT_AMOUNT']);
    }
}
