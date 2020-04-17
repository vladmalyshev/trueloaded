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
 * Class m200213_195140_review_stars_titles
 */
class m200213_195140_review_stars_titles extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
/*define('TEXT_RATE_ONE', 'Rate this 1 star out of 5');
define('TEXT_RATE_TWO', 'Rate this 2 stars out of 5');
define('TEXT_RATE_THREE', 'Rate this 3 stars out of 5');
define('TEXT_RATE_FOUR', 'Rate this 3 stars out of 5');
define('TEXT_RATE_FIVE', 'Rate this 5 stars out of 5');*/

      $this->addTranslation('main', [
        'TEXT_RATE_1' => 'Bad',
        'TEXT_RATE_2' => 'Poor',
        'TEXT_RATE_3' => 'Average',
        'TEXT_RATE_4' => 'Good',
        'TEXT_RATE_5' => 'Excellent'
      ]);

        $this->insert('configuration',[
            'configuration_title' => 'Display review rating title',
            'configuration_key' => 'DISPLAY_REVIEW_RATING_TITLE',
            'configuration_value' => 'False',
            'configuration_description' => 'Display review rating title next to stars',
            'configuration_group_id' => 'BOX_CONFIGURATION_MYSTORE',
            'date_added' => new \yii\db\Expression('NOW()'),
            'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'),'
        ]);

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
        echo "m200213_195140_review_stars_titles cannot be reverted.\n";

        return false;
    }
    */
}
