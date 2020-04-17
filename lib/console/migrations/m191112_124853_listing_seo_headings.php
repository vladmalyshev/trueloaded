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
 * Class m191112_124853_listing_seo_headings
 */
class m191112_124853_listing_seo_headings extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('admin/design',[
            'PRODUCT_NAME_IN_H2' => 'Product name in h2',
            'PRODUCT_NAME_IN_H3' => 'Product name in h3',
            'PRODUCT_NAME_IN_H4' => 'Product name in h4',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->removeTranslation('admin/design',[
            'PRODUCT_NAME_IN_H2',
            'PRODUCT_NAME_IN_H3',
            'PRODUCT_NAME_IN_H4',
        ]);
    }
}
