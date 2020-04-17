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
 * Class m191206_200110_coupon_exclude_prod_cat
 */
class m191206_200110_coupon_exclude_prod_cat extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
      
      if (!$this->isFieldExists('exclude_products', 'coupons')){
        $this->addColumn('coupons', 'exclude_products', $this->string(1024)->notNull()->defaultValue(''));
      }
      if (!$this->isFieldExists('exclude_categories', 'coupons')){
        $this->addColumn('coupons', 'exclude_categories', $this->string(1024)->notNull()->defaultValue(''));
      }
      if (!$this->isFieldExists('restrict_to_customers', 'coupons')){
        $this->addColumn('coupons', 'restrict_to_customers', $this->text());
      }
      if (!$this->isFieldExists('disable_for_special', 'coupons')){
        $this->addColumn('coupons', 'disable_for_special', $this->integer(1)->notNull()->defaultValue(0));
      }
      $this->addTranslation('main', [
        'ERROR_COUPON_FOR_OTHER_CUSTOMER' => 'Coupon is for another customer',
      ]);
      $this->addTranslation('admin/main', [
        'ERROR_COUPON_FOR_OTHER_CUSTOMER' => 'Coupon is for another customer',
        'CONFIRM_SEND_TO_MULTIPLE_EMAILS' => 'Confirm you wish to email to several (could be a lot) customers',
        'TEXT_RESTRICT_TO_CUSTOMERS' => 'Only for customer',
        'TEXT_RESTRICT_TO_CUSTOMERS_HELP' => 'Specify customer email or leave empty',
        'TEXT_DISABLE_FOR_SPECIAL' => 'Disable for special products',
        'TEXT_EXCLUDE_PRODUCTS' => 'Exclude products',
        'TEXT_EXCLUDE_PRODUCTS_HELP' => 'A comma separated list of product_ids that this coupon can NOT be used with. Leave blank for no restrictions.',
        'TEXT_EXCLUDE_CATEGORIES' => 'Exclude categories',
        'TEXT_EXCLUDE_CATEGORIES_HELP' => 'A comma separated list of categories_ids that this coupon can NOT be used with, leave blank for no restrictions.',
      ]);

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
//        echo "m191206_200110_coupon_exclude_prod_cat cannot be reverted.\n";

//        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191206_200110_coupon_exclude_prod_cat cannot be reverted.\n";

        return false;
    }
    */
}
