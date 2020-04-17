<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap;


use common\api\models\Soap\Customer\Customer;
use common\api\SoapServer\ServerSession;
use yii\helpers\ArrayHelper;

class UpdateCustomerResponse extends SoapModel
{
    /**
     * @var string
     * @soap
     */
    public $status = 'OK';

    /**
     * @var \common\api\models\Soap\ArrayOfMessages Array of Messages {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $messages = [];

    /**
     * @var \common\api\models\Soap\Customer\Customer
     * @soap
     */
    public $customer;

    public $customerIn;

    public function setCustomer(Customer $customer)
    {
        $this->customerIn = $customer;
    }

    public function build()
    {
        $customerArray = (array)$this->customerIn;

        unset($customerArray['customers_id']);
        foreach ( array_keys($customerArray) as $customerArrayKey ) {
            if ( is_null($customerArray[$customerArrayKey]) ) unset($customerArray[$customerArrayKey]);
        }

        foreach( ['platform_id', 'platform_name', 'customers_email_address', 'addresses'] as $required ) {
            if ( !isset($customerArray[$required]) || empty($customerArray[$required]) ) {
                $this->error('Empty '.$required);
            }
        }
        if ( (!isset($customerArray['customers_firstname']) || empty($customerArray['customers_firstname']))
            && (!isset($customerArray['customers_lastname']) || empty($customerArray['customers_lastname'])) ){
            $this->error('Customer name required');
        }

        if ( $this->status=='ERROR' ) return;

        if ( !$customerArray['platform_id'] ) {
            $customerArray['platform_id'] = \common\classes\platform::defaultId();
        }
        if (ServerSession::get()->getDepartmentId()) {
            if (isset($customerArray['platform_name']) && !empty($customerArray['platform_name']) && $customerArray['platform_id']) {
                \Yii::$app->get('department')->updatePlatformName($customerArray['platform_id'], $customerArray['platform_name']);
            }
        }

        if ( array_key_exists('is_guest',$customerArray) ){
            $customerArray['opc_temp_account'] = $customerArray['is_guest'];
        }
        $customerArray['opc_temp_account'] = isset($customerArray['opc_temp_account'])?(int)$customerArray['opc_temp_account']:0;

        if ( array_key_exists('customers_currency',$customerArray) ){
            $customerArray['customers_currency_id'] = \common\helpers\Currencies::getCurrencyId($customerArray['customers_currency']);
        }
        if ( isset($customerArray['customers_currency']) && !empty($customerArray['customers_currency']) && empty($customerArray['customers_currency_id']) ) {
            $this->error('customers_currency='.$customerArray['customers_currency'].' not exists');
            return;
        }

        if ( array_key_exists('currency_switcher',$customerArray) ){
            $customerArray['currency_switcher'] = $customerArray['currency_switcher']?1:0;
        }

        if ( ServerSession::get()->getDepartmentId()>0 ) {
            $customerArray['departments_id'] = ServerSession::get()->getDepartmentId();
        }

        if ( is_null($customerArray['customers_bonus_points']) ) {
            unset($customerArray['customers_bonus_points']);
        }
        if ( is_null($customerArray['customers_credit_avail']) ) {
            unset($customerArray['customers_credit_avail']);
        }
        if ( is_null($customerArray['credit_amount']) ) {
            unset($customerArray['credit_amount']);
        }

        $addressBookAppend = false;
        if ( isset($customerArray['addresses']) && !empty($customerArray['addresses']->address) ) {
            $addressBookAppend = isset($customerArray['addresses']->append) && $customerArray['addresses']->append;
            $addresses = json_decode(json_encode($customerArray['addresses']->address),true);
            $customerArray['addresses'] = [];
            $addresses = ArrayHelper::isIndexed($addresses)?$addresses:[$addresses];
            foreach ( $addresses as $address ){
                unset($address['address_book_id']);
                $customerArray['addresses'][] = $address;
            }
        }else{
            $customerArray['addresses'] = [];
        }

        if ( count($customerArray['addresses'])==0 ) {
            $this->error('Empty addresses');
            return;
        }

        if ( isset($customerArray['departments_id']) && $customerArray['departments_id']>0 ) {
            $customerObj = \common\api\models\AR\Customer::findOne([
                'customers_email_address' => $customerArray['customers_email_address'],
                'departments_id' => $customerArray['departments_id'],
            ]);
        }else{
            $customerObj = \common\api\models\AR\Customer::findOne([
                'customers_email_address' => $customerArray['customers_email_address'],
                //'departments_id' => $customerArray['departments_id'],
            ]);
        }

        if ( $customerObj ) {

        }else {
            $this->error('Customer not found');
            return;
        }
        if ( $addressBookAppend ) {
            $customerObj->indexedCollectionAppendMode('addresses', true);
        }

        $customerObj->importArray($customerArray);
        $customerObj->save();
        $customerObj->refresh();
        $this->customer = new Customer($customerObj->exportArray([]));
    }

}