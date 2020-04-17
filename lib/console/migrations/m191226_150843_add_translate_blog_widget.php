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
 * Class m191226_150843_add_translate_blog_widget
 */
class m191226_150843_add_translate_blog_widget extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
		$this->addTranslation('main', [
        'TEXT_BLOG_RELATED_PRODUCTS' => 'Related products',
        'TEXT_BLOG_RELATED_BY_TAGS' => 'Related Posts by tags',
        'TEXT_BLOG_RELATED_BY_CATEGORIES' => 'Related Posts by categories',
      ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m191226_150843_add_translate_blog_widget cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191226_150843_add_translate_blog_widget cannot be reverted.\n";

        return false;
    }
    */
}
