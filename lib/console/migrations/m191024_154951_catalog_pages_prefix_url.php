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
 * Class m191024_154951_catalog_pages_prefix_url
 */
class m191024_154951_catalog_pages_prefix_url extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {

        try {
            $this->insert('configuration', [
                'configuration_key' => 'CATALOG_PAGES_PREFIX_URL',
                'configuration_title' => 'Catalog Pages prefix url',
                'configuration_description' => 'Catalog Pages prefix url',
                'configuration_group_id' => '1',
                'configuration_value' => 'pages',
                'sort_order' => '100',
                'date_added' => (new yii\db\Expression('now()'))
            ]);
        } catch (\Exception $e) {}
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        try {
            $this->delete('configuration',['configuration_key' => 'CATALOG_PAGES_PREFIX_URL']);
        } catch (\Exception $e) {}
    }

}
