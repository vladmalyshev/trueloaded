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
 * Class m190815_100255_organization_data
 */
class m190815_100255_organization_data extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn("platforms", 'organization_site', $this->string(255)->notNull());
        $this->addColumn("platforms", 'organization_type', $this->string(255)->notNull());
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn("platforms", 'organization_site');
        $this->dropColumn("platforms", 'organization_type');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190815_100255_organization_data cannot be reverted.\n";

        return false;
    }
    */
}
