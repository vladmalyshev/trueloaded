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
 * Class m190902_114613_frontend_translation
 */
class m190902_114613_frontend_translation extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('admin', 'frontend_translation', $this->integer(2)->notNull()->defaultValue(0));
        $this->addTranslation('admin/texts', [
            'TEXT_EDIT_TRANSLATIONS_FRONTEND' => 'Edit translations on frontend',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        return $this->dropColumn('admin', 'frontend_translation');
        $this->removeTranslation('admin/texts', [
            'TEXT_EDIT_TRANSLATIONS_FRONTEND',
        ]);
    }
}
