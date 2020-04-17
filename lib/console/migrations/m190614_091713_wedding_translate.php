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
 * Class m190614_091713_wedding_translate
 */
class m190614_091713_wedding_translate extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {

        $this->addTranslation('admin/design', [
            'TEXT_WEDDING_HOMEPAGE' => 'Wedding homepage',
            'TEXT_SHARE' => 'Share',
            'TEXT_MANAGE' => 'Manage',
        ]);

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {

        $this->removeTranslation('admin/design', [
            'TEXT_WEDDING_HOMEPAGE' => 'Wedding homepage',
            'TEXT_SHARE' => 'Share',
            'TEXT_MANAGE' => 'Manage',
        ]);

    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190614_091713_wedding_translate cannot be reverted.\n";

        return false;
    }
    */
}
