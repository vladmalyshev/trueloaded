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
 * Class m191003_140746_link_on_email
 */
class m191003_140746_link_on_email extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('admin/design',[
            'USE_AT_IN_EMAIL_ADDRESS' => 'Use (at) instead @ in email address',
            'ADD_LINK_ON_EMAIL_ADDRESS' => 'Add link on email address',
            'ADD_LINK_ON_TELEPHONE_NUMBER' => 'Add link on telephone number',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->removeTranslation('admin/design',[
            'ADD_FILTER_ITEM',
            'FILTERS_SIMPLE',
        ]);
    }
}
