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
 * Class m190807_104043_sales_person
 */
class m190807_104043_sales_person extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {

      $this->addTranslation('admin/main', [
        'TABLE_HEADING_SALES_PERSON' => 'Sales Person'
      ]);

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->removeTranslation('admin/main', [
        'TABLE_HEADING_SALES_PERSON'
      ]);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190807_104043_sales_person cannot be reverted.\n";

        return false;
    }
    */
}
