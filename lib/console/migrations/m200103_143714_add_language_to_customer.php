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
 * Class m200103_143714_add_language_to_customer
 */
class m200103_143714_add_language_to_customer extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('customers', 'language_id', $this->integer(11)->notNull()->defaultValue((int)\common\classes\language::defaultId()));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn('customers', 'language_id');
    }

}
