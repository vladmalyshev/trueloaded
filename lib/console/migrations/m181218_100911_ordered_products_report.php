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
 * Class m181218_100911_ordered_products_report
 */
class m181218_100911_ordered_products_report extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('admin/main',[
            'BOX_REPORTS_ORDERED_PRODUCT' => 'Ordered Products',
        ]);
        $this->appendAcl(['BOX_HEADING_REPORTS', 'BOX_REPORTS_ORDERED_PRODUCT']);
        $this->addAdminMenuAfter([
            'path' => 'ordered-products-report',
            'title' => 'BOX_REPORTS_ORDERED_PRODUCT'
        ],'BOX_REPORTS_COMPARE');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m181218_100911_ordered_products_report cannot be reverted.\n";

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m181218_100911_ordered_products_report cannot be reverted.\n";

        return false;
    }
    */
}
