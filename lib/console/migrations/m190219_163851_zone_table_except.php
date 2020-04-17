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
 * Class m190219_163851_zone_table_except
 */
class m190219_163851_zone_table_except extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('zones_to_ship_zones', 'except_flag', $this->tinyInteger(1)->notNull()->defaultValue(0));
        $this->addTranslation('admin/main',[
            'TEXT_EXCEPT' => 'Except',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->removeTranslation('admin/main',[
            'TEXT_EXCEPT',
        ]);
        $this->dropColumn('zones_to_ship_zones', 'except_flag');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190219_163851_zone_table_except cannot be reverted.\n";

        return false;
    }
    */
}
