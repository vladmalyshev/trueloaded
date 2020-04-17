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
 * Class m200213_142014_product_data
 */
class m200213_142014_product_data extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('main',[
            'TEXT_MAX_QTY_TO_PURCHASE' => 'Max qty to purchase',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->removeTranslation('main',[
            'TEXT_MAX_QTY_TO_PURCHASE',
        ]);
    }
}
