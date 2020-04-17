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
 * Class m191111_143515_add_restrict_to_country
 */
class m191111_143515_add_restrict_to_country extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->isFieldExists('restrict_to_countries', 'coupons')){
            $this->addColumn('coupons', 'restrict_to_countries', $this->string(255));
        }
        $this->addTranslation('admin/coupon_admin', [
            'COUPON_COUNTRIES' => 'Valid Countries',
            'COUPON_COUNTRIES_HELP' => 'A comma separated list of countries that this coupon can be used with, leave blank for no restrictions.',
        ]);
        $this->addTranslation('ordertotal', [
            'ERROR_INVALID_COUNTRY_COUPON' => 'Invalid usage for determinated country',
        ]);
        
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        if ($this->isFieldExists('restrict_to_countries', 'coupons')){
            $this->dropColumn('coupons', 'restrict_to_countries');
        }
        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191111_143515_add_restrict_to_country cannot be reverted.\n";

        return false;
    }
    */
}
