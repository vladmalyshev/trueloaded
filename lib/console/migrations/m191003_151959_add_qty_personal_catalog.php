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
 * Class m191003_151959_add_qty_personal_catalog
 */
class m191003_151959_add_qty_personal_catalog extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('personal_catalog', 'qty', $this->integer()->notNull()->defaultValue(1));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn('personal_catalog', 'qty');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191003_151959_add_qty_personal_catalog cannot be reverted.\n";

        return false;
    }
    */
}
