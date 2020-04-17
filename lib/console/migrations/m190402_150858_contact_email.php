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
 * Class m190402_150858_contact_email
 */
class m190402_150858_contact_email extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('platforms','contact_us_email',$this->string(96)->notNull()->defaultValue(''));
        $this->addTranslation('admin/platforms',[
            'TEXT_CONTACT_US_EMAIL' => '&quot;Contact us&quot; form email',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn('platforms','contact_us_email');
        $this->removeTranslation('admin/platforms',[
            'TEXT_CONTACT_US_EMAIL',
        ]);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190402_150858_contact_email cannot be reverted.\n";

        return false;
    }
    */
}
