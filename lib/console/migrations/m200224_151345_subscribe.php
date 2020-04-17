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
 * Class m200224_151345_subscribe
 */
class m200224_151345_subscribe extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('main',[
            'TEXT_CHECK_EMAIL' => 'Please check your email',
            'TEXT_CHECK_EMAIL_UNSUBSCRIBE' => 'Please check your email for unsubscribe',
            'TEXT_SUCCESSFULLY_SUBSCRIBED' => 'You was successfully subscribed',
            'TEXT_SUCCESSFULLY_UNSUBSCRIBED' => 'You was successfully unsubscribed',
            'TEXT_EMAIL_NOT_FOUND' => 'Email not found',
            'TEXT_UNSUBSCRIBE' => 'Unsubscribe',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->removeTranslation('main',[
            'TEXT_CHECK_EMAIL',
            'TEXT_CHECK_EMAIL_UNSUBSCRIBE',
            'TEXT_SUCCESSFULLY_SUBSCRIBED',
            'TEXT_SUCCESSFULLY_UNSUBSCRIBED',
            'TEXT_EMAIL_NOT_FOUND',
            'TEXT_UNSUBSCRIBE',
        ]);
    }
}
