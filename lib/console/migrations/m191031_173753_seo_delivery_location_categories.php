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
 * Class m191031_173753_seo_delivery_location_categories
 */
class m191031_173753_seo_delivery_location_categories extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable('seo_delivery_location_categories', [
            'id' => $this->integer()->notNull()->defaultValue(0),
            'categories_id' => $this->integer()->notNull()->defaultValue(0),
        ]);
        $this->addPrimaryKey('', 'seo_delivery_location_categories', ['id', 'categories_id']);


        $this->addTranslation('admin/main',[
            'DATE_PRODUCT_CATEGORIES' => 'Product Categories',
            'FIND_PRODUCT_CATEGORIES' => 'Find product categories',
            'ASSIGNED_PRODUCT_CATEGORIES' => 'Assigned product categories',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropTable('seo_delivery_location_categories');


        $this->removeTranslation('admin/main',[
            'DATE_PRODUCT_CATEGORIES',
            'FIND_PRODUCT_CATEGORIES',
            'ASSIGNED_PRODUCT_CATEGORIES',
        ]);
    }
}
