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
 * Class m200114_131712_transfer_bonus_points_to_credit_amount_in_admin
 */
class m200114_131712_transfer_bonus_points_to_credit_amount_in_admin extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('admin/main',[
            'TEXT_TRANSFER_BONUS_POINTS_TO_CREDIT_AMOUNT' => 'Transfer All Bonus Points To Credit Amount',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->removeTranslation('admin/main',[
            'TEXT_TRANSFER_BONUS_POINTS_TO_CREDIT_AMOUNT',
        ]);
    }
}
