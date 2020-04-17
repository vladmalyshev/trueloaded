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
 * Class m190401_122239_dashboard_search_product_translation
 */
class m190401_122239_dashboard_search_product_translation extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
      $this->addTranslation('admin/main', [
        'TEXT_GO_TO_PRODUCT' => 'Find product'
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        //$this->removeTranslation($entity, $keys)
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190401_122239_dashboard_search_product_translation cannot be reverted.\n";

        return false;
    }
    */
}
