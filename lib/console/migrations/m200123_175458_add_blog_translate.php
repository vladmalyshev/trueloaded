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
 * Class m200123_175458_add_blog_translate
 */
class m200123_175458_add_blog_translate extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('admin/meta-tags', [
            'BLOG_INDEX_TAGS' => 'Blog Index',
            'BLOG_CATEGORY_TAGS' => 'Blog Category',
            'BLOG_TAG_TAGS' => 'Blog Tag',
            'BLOG_POST_TAGS' => 'Blog Post',
        ]);
        if($this->isTableExists('blog_post_text')) {
            if (!$this->isFieldExists('overwrite_head_title_tag', 'blog_post_text')) {
                $this->addColumn('blog_post_text', 'overwrite_head_title_tag', $this->integer(1)->notNull()->defaultValue(1));
            }
            if (!$this->isFieldExists('overwrite_head_desc_tag', 'blog_post_text')) {
                $this->addColumn('blog_post_text', 'overwrite_head_desc_tag', $this->integer(1)->notNull()->defaultValue(1));
            }
        }
        if($this->isTableExists('blog_category_text')) {
            if (!$this->isFieldExists('overwrite_head_title_tag', 'blog_category_text')) {
                $this->addColumn('blog_category_text', 'overwrite_head_title_tag', $this->integer(1)->notNull()->defaultValue(1));
            }
            if (!$this->isFieldExists('overwrite_head_desc_tag', 'blog_category_text')) {
                $this->addColumn('blog_category_text', 'overwrite_head_desc_tag', $this->integer(1)->notNull()->defaultValue(1));
            }
        }
        if($this->isTableExists('blog_tags_text')) {
            if (!$this->isFieldExists('overwrite_head_title_tag', 'blog_tags_text')) {
                $this->addColumn('blog_tags_text', 'overwrite_head_title_tag', $this->integer(1)->notNull()->defaultValue(1));
            }
            if (!$this->isFieldExists('overwrite_head_desc_tag', 'blog_tags_text')) {
                $this->addColumn('blog_tags_text', 'overwrite_head_desc_tag', $this->integer(1)->notNull()->defaultValue(1));
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {

    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200113_175458_add_blog_translate cannot be reverted.\n";

        return false;
    }
    */
}
