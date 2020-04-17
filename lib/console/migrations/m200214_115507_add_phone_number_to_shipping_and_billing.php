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
 * Class m200214_115507_add_phone_number_to_shipping_and_billing
 */
class m200214_115507_add_phone_number_to_shipping_and_billing extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $customerDetailsPhone = (new yii\db\Query())->from('configuration')->where(['configuration_key' => 'ACCOUNT_TELEPHONE'])->count();
        $shippingDetailsPhone = (new yii\db\Query())->from('configuration')->where(['configuration_key' => 'SHIPPING_TELEPHONE'])->count();
        $billingDetailsPhone = (new yii\db\Query())->from('configuration')->where(['configuration_key' => 'BILLING_TELEPHONE'])->count();

        if ($customerDetailsPhone != 0 && $shippingDetailsPhone == 0) {           
            
            $customerDetailsPhoneContainer = (new yii\db\Query())->from('configuration')->where(['configuration_key' => 'ACCOUNT_TELEPHONE'])->one();
            $sortOrderContainer = (new yii\db\Query())
                ->from('configuration')
                ->select(['sort_order'  => new \yii\db\Expression('(MAX(sort_order)+1)')])
                ->where(['configuration_group_id' => 'BOX_SHIPPING_CUSTOMER_DETAILS'])
                ->one();
            
            //SELECT (MAX(sort_order)+1) as sort_order FROM `configuration` WHERE configuration_group_id=100684 
            $this->insert('configuration',[
                'configuration_title' => $customerDetailsPhoneContainer['configuration_title'],
                'configuration_key' => 'SHIPPING_TELEPHONE',
                'configuration_value' => 'disabled', //$customerDetailsPhoneContainer['configuration_value'],
                'configuration_description' => 'Display telephone in the account shipping details',
                'configuration_group_id' => 'BOX_SHIPPING_CUSTOMER_DETAILS',/*Shipping Customer Details*/
                'sort_order' => $sortOrderContainer['sort_order'],
                'set_function' => $customerDetailsPhoneContainer['set_function'],
                'date_added' => new \yii\db\Expression('NOW()'),
            ]);
        }
        
        if ($customerDetailsPhone != 0 && $billingDetailsPhone == 0) {           
            
            $customerDetailsPhoneContainer = (new yii\db\Query())->from('configuration')->where(['configuration_key' => 'ACCOUNT_TELEPHONE'])->one();
            $sortOrderContainer = (new yii\db\Query())
                ->from('configuration')
                ->select(['sort_order'  => new \yii\db\Expression('(MAX(sort_order)+1)')])
                ->where(['configuration_group_id' => 'BOX_BILLING_CUSTOMER_DETAILS'])
                ->one();
            
            $this->insert('configuration',[
                'configuration_title' => $customerDetailsPhoneContainer['configuration_title'],
                'configuration_key' => 'BILLING_TELEPHONE',
                'configuration_value' => 'disabled', //$customerDetailsPhoneContainer['configuration_value'],
                'configuration_description' => 'Display telephone in the account billing details',
                'configuration_group_id' => 'BOX_BILLING_CUSTOMER_DETAILS',/*Billing Customer Details*/
                'sort_order' => $sortOrderContainer['sort_order'],
                'set_function' => $customerDetailsPhoneContainer['set_function'],
                'date_added' => new \yii\db\Expression('NOW()'),
            ]);
        }
        
        $this->addTranslation('admin/main',[
            'ENTRY_TELEPHONE_ADRESS_BOOK' => 'Phone number',
            'ENTRY_TELEPHONE_ADRESS_BOOK_ERROR' => 'Please enter the telephone number',
        ]);
        $this->addTranslation('main',[
            'ENTRY_TELEPHONE_ADRESS_BOOK' => 'Phone number',
            'ENTRY_TELEPHONE_ADRESS_BOOK_ERROR' => 'Please enter the telephone number',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->removeTranslation('admin/main', [
            'ENTRY_TELEPHONE_ADRESS_BOOK',
            'ENTRY_TELEPHONE_ADRESS_BOOK_ERROR',
        ]);
        $this->removeTranslation('main', [
            'ENTRY_TELEPHONE_ADRESS_BOOK',
            'ENTRY_TELEPHONE_ADRESS_BOOK_ERROR',
        ]);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200214_115507_add_phone_number_to_shipping_and_billing cannot be reverted.\n";

        return false;
    }
    */
}
