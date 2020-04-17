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
 * Class m200108_103359_add_blog_translate_cut
 */
class m200108_103359_add_blog_translate_cut extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
		$this->addTranslation('main', [
			'TEXT_DESCRIPTION_CUT_OFF' => 'Count characters of description',
		]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200108_103359_add_blog_translate_cut cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200108_103359_add_blog_translate_cut cannot be reverted.\n";

        return false;
    }
    */
}
