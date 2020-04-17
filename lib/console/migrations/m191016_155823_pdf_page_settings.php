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
 * Class m191016_155823_pdf_page_settings
 */
class m191016_155823_pdf_page_settings extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('admin/design',[
            'TEXT_PAGE_FORMAT' => 'Page format',
            'TEXT_ENTER_CUSTOM_SIZE' => 'Enter custom size',
            'TEXT_ORIENTATION' => 'Orientation',
            'TEXT_PORTRAIT' => 'Portrait',
            'TEXT_LANDSCAPE' => 'Landscape',
            'TEXT_PAGE_WIDTH' => 'Page width',
            'TEXT_PAGE_HEIGHT' => 'Page height',
            'TEXT_MARGIN_TOP' => 'Margin top',
            'TEXT_MARGIN_LEFT' => 'Margin left',
            'TEXT_MARGIN_RIGHT' => 'Margin right',
            'TEXT_MARGIN_BOTTOM' => 'Margin bottom',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->removeTranslation('admin/design',[
            'TEXT_PAGE_FORMAT',
            'TEXT_ENTER_CUSTOM_SIZE',
            'TEXT_ORIENTATION',
            'TEXT_PORTRAIT',
            'TEXT_LANDSCAPE',
            'TEXT_PAGE_WIDTH',
            'TEXT_PAGE_HEIGHT',
            'TEXT_MARGIN_TOP',
            'TEXT_MARGIN_LEFT',
            'TEXT_MARGIN_RIGHT',
            'TEXT_MARGIN_BOTTOM',
        ]);
    }
}
