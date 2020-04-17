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
 * Class m190714_213527_bundle_volume
 */
class m190714_213527_bundle_volume extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('products', 'bundle_volume_calc', $this->integer(2)->notNull()->defaultValue(0));
        $this->addTranslation('admin/categories', [
            'VOLUME_BY_CHILDREN' => 'from children',
            'OWN_VOLUME' => 'its own (below)',
            'OWN_VOLUME_AND_CHILDREN' => 'own and from children',
            'CALCULATE_VOLUME' => 'Calculate volume:',
        ]);
        $this->addTranslation('admin/main', [
            'TEXT_INFO_VOLUME' => 'volume',
        ]);

        $this->addTranslation('shipping', [
            'TEXT_DISALLOW_ALL_ALLOW_BY_CUSTOMER' => 'Disallow, allow by customer',
        ]);

        $this->addColumn('ship_options', 'restrict_access', $this->integer(11)->notNull()->defaultValue(0));


    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
       $this->dropColumn('products', 'bundle_volume_calc');
        $this->removeTranslation('admin/categories', [
            'VOLUME_BY_CHILDREN',
            'OWN_VOLUME',
            'OWN_VOLUME_AND_CHILDREN',
            'CALCULATE_VOLUME',
        ]);
        $this->removeTranslation('admin/main', [
            'TEXT_INFO_VOLUME',
        ]);

        $this->dropColumn('ship_options', 'restrict_access');
        $this->removeTranslation('shipping', [
            'TEXT_DISALLOW_ALL_ALLOW_BY_CUSTOMER',
        ]);
        
       // return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190714_213527_bundle_volume cannot be reverted.\n";

        return false;
    }
    */
}
