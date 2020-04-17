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
 * Class m191001_162146_simple_form
 */
class m191001_162146_simple_form extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('main',[
            'TEXT_OK' => 'Ok',
        ]);
        $this->addTranslation('admin/design',[
            'ADD_FILTER_ITEM' => 'Add filter item',
            'FILTERS_SIMPLE' => 'Filters Simple',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->removeTranslation('main',[
            'TEXT_OK',
        ]);
        $this->removeTranslation('admin/design',[
            'ADD_FILTER_ITEM',
            'FILTERS_SIMPLE',
        ]);
    }
}
