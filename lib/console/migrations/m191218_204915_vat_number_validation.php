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
 * Class m191218_204915_vat_number_validation
 */
class m191218_204915_vat_number_validation extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
      $this->addTranslation('main', [
        'TEXT_NOT_VALIDATED' => 'Can\'t validate. Try again later',

      ]);
      $this->addTranslation('admin/main', [
        'TEXT_NOT_VALIDATED' => 'Can\'t validate. Try again later',

      ]);

      if (!$this->isFieldExists('entry_company_vat_date', 'address_book')) { // entry_company_vat
        $this->addColumn('address_book', 'entry_company_vat_date', $this->date()->notNull());
      }

      if (!$this->isFieldExists('entry_company_vat_status', 'address_book')) { // entry_company_vat
        $this->addColumn('address_book', 'entry_company_vat_status', $this->integer(4)->notNull()->defaultValue(0));
      }

      if ($this->isFieldExists('customers_company_vat', 'customers')) { // not visible in admin, can't be changed - rename inDB
        $this->addCommentOnColumn('customers', 'customers_company_vat', 'not visible in admin, cant be changed so renamed');
        $this->renameColumn('customers', 'customers_company_vat', 'dnu_customers_company_vat');
        echo "update customers, address_book set entry_company=customers_company, entry_company_vat = dnu_customers_company_vat WHERE customers_default_address_id=address_book_id and (entry_company=''or entry_company is null) and customers_company!='' and entry_company_vat='' and dnu_customers_company_vat!='' \n";
      }

      // 

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        //echo "m191218_204915_vat_number_validation cannot be reverted.\n";

        //return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191218_204915_vat_number_validation cannot be reverted.\n";

        return false;
    }
    */
}
