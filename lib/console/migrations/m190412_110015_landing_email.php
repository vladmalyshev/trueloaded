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
 * Class m190412_110015_landing_email
 */
class m190412_110015_landing_email extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('platforms','landing_contact_email',$this->string(96)->notNull()->defaultValue(''));
        $this->addTranslation('admin/platforms',[
            'TEXT_LANDING_FORM_EMAIL' => '&quot;Landing&quot; form email',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn('platforms','landing_contact_email');
        $this->removeTranslation('admin/platforms',[
            'TEXT_LANDING_FORM_EMAIL',
        ]);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190412_110015_landing_email cannot be reverted.\n";

        return false;
    }
    */
}
