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
 * Class m200120_100005_def_fields_ga
 */
class m200120_100005_def_fields_ga extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->alterColumn('ga', 'utmgclid', $this->string('100')->notNull()->defaultValue(''));
        $this->alterColumn('ga', 'utmccn', $this->string('255')->notNull()->defaultValue(''));
        $this->alterColumn('ga', 'utmcmd', $this->string('255')->notNull()->defaultValue(''));
        $this->alterColumn('ga', 'utmctr', $this->string('255')->notNull()->defaultValue(''));
        $this->alterColumn('ga', 'utmcsr', $this->string('100')->notNull()->defaultValue(''));
        $this->alterColumn('ga', 'ip_address', $this->string('15')->notNull()->defaultValue(''));
        $this->alterColumn('ga', 'last_page_url', $this->string('64')->notNull()->defaultValue(''));
        $this->alterColumn('ga', 'http_referer', $this->string('255')->notNull()->defaultValue(''));
        $this->alterColumn('ga', 'user_agent', $this->string('255')->notNull()->defaultValue(''));
        $this->alterColumn('ga', 'resolution', $this->string('15')->notNull()->defaultValue(''));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200120_100005_def_fields_ga cannot be reverted.\n";

        return false;
    }
    */
}
