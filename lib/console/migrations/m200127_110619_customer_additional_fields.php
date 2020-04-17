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
 * Class m200127_110620_customer_additional_fields
 */
class m200127_110619_customer_additional_fields extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('admin/main',[
            'ADDITIONAL_CUSTOMER_FIELDS' => 'Additional customer fields',
            'ADDITIONAL_CUSTOMER_FIELDS_GROUPS' => 'Additional customer fields groups',
            'ADDITIONAL_CUSTOMER_FIELDS_EDIT' => 'Edit field',
            'ADDITIONAL_CUSTOMER_FIELDS_GROUPS_EDIT' => 'Edit field group',
            'TEXT_ADD_GROUP' => 'Add group',
            'TEXT_ADD_FIELD' => 'Add field',
        ]);
        $this->addTranslation('admin/customers',[
            'TEXT_CUSTOMER_DETAILS' => 'Customer details',
            'TEXT_BANK_ACCOUNT_DETAILS' => 'Bank account details',
        ]);

        $this->addColumn('additional_fields', 'required', $this->integer(1)->notNull()->defaultValue(0));

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->removeTranslation('admin/customers',[
            'TEXT_CUSTOMER_DETAILS',
            'TEXT_BANK_ACCOUNT_DETAILS',
        ]);
        $this->removeTranslation('admin/main',[
            'ADDITIONAL_CUSTOMER_FIELDS',
            'ADDITIONAL_CUSTOMER_FIELDS_GROUPS',
            'ADDITIONAL_CUSTOMER_FIELDS_EDIT',
            'ADDITIONAL_CUSTOMER_FIELDS_GROUPS_EDIT',
            'TEXT_ADD_GROUP',
            'TEXT_ADD_FIELD',
            'TEXT_TRADE_FORM',
        ]);

        $this->dropColumn("additional_fields", 'required');
    }
}
