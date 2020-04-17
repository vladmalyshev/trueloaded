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
 * Class m190603_133010_terms_widget
 */
class m190603_133010_terms_widget extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('admin/design', [
            'TEXT_USE_SWITCHER' => 'Use switcher',
            'TEXT_HIDE_PAGE' => 'Hide page',
            'TEXT_HIDE_CONTINUE_BUTTON' => 'Hide continue button',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190603_133010_terms_widget cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190603_133010_terms_widget cannot be reverted.\n";

        return false;
    }
    */
}
