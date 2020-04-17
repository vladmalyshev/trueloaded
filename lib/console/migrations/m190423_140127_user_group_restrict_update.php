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
 * Class m190423_140127_user_group_restrict_update
 */
class m190423_140127_user_group_restrict_update extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        \common\extensions\UserGroupsRestrictions\UserGroupsRestrictions::installUpdate190423();

        $this->addTranslation('admin/main',[
            'TEXT_ASSIGN_TAB' => 'Assign/Restrict'
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190423_140127_user_group_restrict_update cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190423_140127_user_group_restrict_update cannot be reverted.\n";

        return false;
    }
    */
}
