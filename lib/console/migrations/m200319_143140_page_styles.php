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
 * Class m200319_143140_page_styles
 */
class m200319_143140_page_styles extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable('page_styles', [
            'id' => $this->primaryKey(11),
            'style' => $this->string(256),
            'type' => $this->string(256),
            'page_id' => $this->integer(),
            'platform_id' => $this->integer(),
        ]);

        $this->addTranslation('admin/main',[
            'TEXT_PAGE_STYLE' => 'Page style',
            'TEXT_ADD_STYLE' => 'Add style',
            'TEXT_THEME_COLOR' => 'Color for mobile browser',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        return $this->dropTable('page_styles');

        $this->removeTranslation('admin/main',[
            'TEXT_PAGE_STYLE',
            'TEXT_ADD_STYLE',
            'TEXT_THEME_COLOR',
        ]);
    }
}
