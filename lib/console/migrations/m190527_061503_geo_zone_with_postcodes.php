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
 * Class m190527_061503_geo_zone_with_postcodes
 */
class m190527_061503_geo_zone_with_postcodes extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('zones_to_geo_zones', 'postcode_start', $this->string(10)->notNull()->defaultValue(''));
        $this->addColumn('zones_to_geo_zones', 'postcode_end', $this->string(10)->notNull()->defaultValue(''));
        $this->addTranslation('admin/geo_zones', [
            'TEXT_POSTCODE_START' => 'Postcode from',
            'TEXT_POSTCODE_END' => 'Postcode to',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn('zones_to_geo_zones', 'postcode_start');
        $this->dropColumn('zones_to_geo_zones', 'postcode_end');

        $this->removeTranslation('admin/geo_zones', [
            'TEXT_POSTCODE_START',
            'TEXT_POSTCODE_END',
        ]);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190527_061503_geo_zone_with_postcodes cannot be reverted.\n";

        return false;
    }
    */
}
