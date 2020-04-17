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
 * Class m191112_170902_prev_next_product
 */
class m191112_170902_prev_next_product extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('main',[
            'PREVIOUS_PRODUCT' => 'Previous product',
            'NEXT_PRODUCT' => 'Next product',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->removeTranslation('main',[
            'PREVIOUS_PRODUCT',
            'NEXT_PRODUCT',
        ]);
    }
}
