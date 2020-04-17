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
 * Class m190220_091830_redesign_splinters
 */
class m190220_091830_redesign_splinters extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->alterColumn('orders_splinters', 'splinters_status', $this->tinyInteger());
        $this->addColumn('orders_splinters', 'splinters_suborder_id', $this->integer());
        $this->addColumn('orders_splinters', 'qty', $this->integer());
        $this->addColumn('orders_splinters', 'value_exc_vat', $this->float());
        $this->addColumn('orders_splinters', 'value_inc_tax', $this->float());
        $this->addColumn('orders_splinters', 'splinters_owner', $this->string(255));
        $this->addColumn('orders_transactions', 'splinters_suborder_id', $this->integer());
        $this->addColumn('orders_splinters', 'date_status_changed', $this->datetime());
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {        
        
        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190220_091830_redesign_splinters cannot be reverted.\n";

        return false;
    }
    */
}

