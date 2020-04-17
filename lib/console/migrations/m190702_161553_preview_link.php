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
 * Class m190702_161553_preview_link
 */
class m190702_161553_preview_link extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->update('translation',
            ['translation_entity' => 'admin/main'],
            ['translation_key' => 'TEXT_PREVIEW_ON_SITE', 'translation_entity' => 'admin/categories']
        );

        $this->addTranslation('admin/main',[
            'CHOOSE_FRONTEND' => 'Choose frontend'
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190702_161553_preview_link cannot be reverted.\n";

        //return false;
    }
}
