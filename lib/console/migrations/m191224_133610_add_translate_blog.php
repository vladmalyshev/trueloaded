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
 * Class m191224_133610_add_translate_blog
 */
class m191224_133610_add_translate_blog extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
		$this->addTranslation('main', [
        'TEXT_BLOG_VIEWED' => 'Viewed',
        'TEXT_BLOG_COMMENTS' => 'comments',
        'TEXT_TAGGED_AS' => 'Tagged as',
		]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m191224_133610_add_translate_blog cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191224_133610_add_translate_blog cannot be reverted.\n";

        return false;
    }
    */
}
