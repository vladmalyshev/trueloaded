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
 * Class m200121_125858_transfer_bonus_points_translation
 */
class m200121_125858_transfer_bonus_points_translation extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('main',[
            'TEXT_TRANSFER_SELECTED_BONUS_POINTS_TO_CREDIT_AMOUNT' => 'Transfer Selected Bonus Points To Credit Amount',
            'TRANSFER_BONUS_POINTS_WARNING' => 'Transfer %s bonus(es)',
            'TEXT_CONVERT_TO_AMOUNT'=>'Convert to Amount',
            'TEXT_CONVERT_FROM_BONUS_POINTS' => 'Convert from Bonus Points',
        ]);
        $this->addTranslation('admin/main',[
            'TRANSFER_BONUS_POINTS_TO_CREDIT_AMOUNT_TEXT' => 'Transfer To Credit Amount',
            'TRANSFER_BONUS_POINTS_WARNING' => 'Transfer %s bonus(es)',
            'TRANSFER_BONUS_POINTS_NOTIFY' => '*Notification of the user according to the checked checkboxes in the corresponding blocks.',
            'TRANSFER_BONUS_SUCCESS' => 'Transfer %s bonus(es) to %s Credit Amount - Successfully',
            'TEXT_CONVERT_TO_AMOUNT'=>'Convert to Amount',
            'TEXT_CONVERT_FROM_BONUS_POINTS' => 'Convert from Bonus Points',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->removeTranslation('main',[
            'TEXT_TRANSFER_SELECTED_BONUS_POINTS_TO_CREDIT_AMOUNT',
            'TRANSFER_BONUS_POINTS_WARNING',
            'TEXT_CONVERT_TO_AMOUNT',
            'TEXT_CONVERT_FROM_BONUS_POINTS'
        ]);
        $this->removeTranslation('admin/main',[
            'TEXT_TRANSFER_SELECTED_BONUS_POINTS_TO_CREDIT_AMOUNT',
            'TRANSFER_BONUS_POINTS_WARNING',
            'TRANSFER_BONUS_POINTS_NOTIFY',
            'TRANSFER_BONUS_SUCCESS',
            'TEXT_CONVERT_TO_AMOUNT',
            'TEXT_CONVERT_FROM_BONUS_POINTS'
        ]);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200121_125858_transfer_bonus_points_translation cannot be reverted.\n";

        return false;
    }
    */
}
