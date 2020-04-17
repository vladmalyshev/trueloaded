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
 * Class m200305_145423_blog_translations
 */
class m200305_145423_blog_translations extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
		$this->addTranslation('main', [
            'TEXT_BLOG_READ_MORE' => 'Read more'
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200305_145423_blog_translations cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200305_145423_blog_translations cannot be reverted.\n";

        return false;
    }
    */
}
