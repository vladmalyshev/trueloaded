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
 * Class m190726_125125_product_dimension
 */
class m190726_125125_product_dimension extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('main', [
            'TEXT_DIMENSION' => 'Dimension L x W x H',
            'TEXT_CBM' => 'CBM',
        ]);
        $this->addTranslation('admin/design', [
            'TEXT_DIMENSION' => 'Dimension',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->removeTranslation('main', [
            'TEXT_DIMENSION',
            'TEXT_CBM',
        ]);
        $this->removeTranslation('admin/design', [
            'TEXT_DIMENSION',
        ]);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190726_125125_product_dimension cannot be reverted.\n";

        return false;
    }
    */
}
