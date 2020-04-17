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
 * Class m200320_104750_limit_characters
 */
class m200320_104750_limit_characters extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('admin/main',[
            'TEXT_ENTERED_CHARACTERS' => 'You entered %s characters',
            'TEXT_LEFT_CHARACTERS' => 'Left %s characters',
            'TEXT_OVERFLOW_CHARACTERS' => 'You overflow %s characters',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->removeTranslation('admin/main',[
            'TEXT_ENTERED_CHARACTERS',
            'TEXT_LEFT_CHARACTERS',
            'TEXT_OVERFLOW_CHARACTERS',
        ]);
    }
}
