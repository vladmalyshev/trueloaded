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
 * Class m191118_105625_zone_table_checkout_note
 */
class m191118_105625_zone_table_checkout_note extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable('zone_table_checkout_note',[
            'id' => $this->primaryKey(),
            'zone_table_id' => $this->integer(4)->notNull(),
            'ship_zone_id' => $this->integer(4)->notNull(),
            'ship_options_id' => $this->integer(4)->notNull(),
            'platform_id' => $this->integer(4)->notNull(),
            'language_id' => $this->integer(4)->notNull(),
            'checkout_note' => $this->string(255)->notNull()->defaultValue(''),
        ]);
        $this->createIndex('zone_table_checkout_note_idx', 'zone_table_checkout_note',
            ['zone_table_id', 'ship_zone_id', 'ship_options_id', 'platform_id', 'language_id']
        );
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropTable('zone_table_checkout_note');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191118_105625_zone_table_checkout_note cannot be reverted.\n";

        return false;
    }
    */
}
