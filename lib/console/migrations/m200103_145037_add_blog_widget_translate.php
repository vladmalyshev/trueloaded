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
 * Class m200103_145037_add_blog_widget_translate
 */
class m200103_145037_add_blog_widget_translate extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
		$this->addTranslation('main', [
			'TEXT_BLOG_CATEGORIES' => 'Category',
			'TEXT_BLOG_TAGS' => 'Tags',
			'TEXT_BLOG_POPULAR_ARTICLES' => 'Most popular articles',
		]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200103_145037_add_blog_widget_translate cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200103_145037_add_blog_widget_translate cannot be reverted.\n";

        return false;
    }
    */
}
