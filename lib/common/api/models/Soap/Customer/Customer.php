<?php

/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */
namespace common\api\models\Soap\Customer;

use common\api\models\Soap\SoapModel;

class Customer extends SoapModel
{

    /**
     * @var integer
     * @soap
     */
    public $customers_id;

    /**
     * @var integer {minOccurs=0}
     * @soap
     */
    public $client_customers_id;

    /**
     * @var integer {minOccurs=1}
     * @soap
     */
    public $platform_id;

    /**
     * @var string {minOccurs=1}
     * @soap
     */
    public $platform_name;

    /**
     * @var string
     * @soap
     */
    public $customers_gender;

    /**
     * @var string {minOccurs=1}
     * @soap
     */
    public $customers_firstname;

    /**
     * @var string {minOccurs=1}
     * @soap
     */
    public $customers_lastname;

    /**
     * @var string
     * @soap
     */
    public $customers_dob;
    /**
     * @var string {minOccurs=1}
     * @soap
     */

    public $customers_email_address;

    /**
     * @var string
     * @soap
     */
    public $customers_telephone;

    /**
     * @var string
     * @soap
     */
    public $customers_landline;

    /**
     * @var string
     * @soap
     */
    public $customers_fax;

    /**
     * @var integer
     * @soap
     */
    public $customers_newsletter;

    /**
     * @var integer {minOccurs=0}
     * @soap
     */
    public $customers_bonus_points;

    /**
     * @var double {minOccurs=0}
     * @soap
     */
    public $customers_credit_avail;
    /**
     * @var integer
     * @soap
     */
    public $groups_id;
    /**
     * @var integer
     * @soap
     */
    public $customers_status;
    /**
     * @var integer
     * @soap
     */
    public $is_guest;
    /**
     * @var string
     * @soap
     */
    public $customers_company;
    /**
     * @var string
     * @soap
     */
    public $customers_company_vat;

    /**
     * @var double {minOccurs=0}
     * @soap
     */
    public $credit_amount;

    /**
     * @var int {minOccurs=0}
     * @soap
     */
    public $sap_servers_id;

    /**
     * @var string {minOccurs=0}
     * @soap
     */
    public $customers_cardcode;

    /**
     * @var string {minOccurs=0}
     * @soap
     */
    public $customers_currency;

    /**
     * @var bool {minOccurs=0}
     * @soap
     */
    public $currency_switcher;

    /**
     * @var \common\api\models\Soap\Customer\ArrayOfAddresses ArrayOfAddresses {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $addresses;

    /**
     * @var datetime {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $modify_time;

    public function __construct(array $config = [])
    {
        if ( array_key_exists('opc_temp_account', $config) ) {
            $config['is_guest'] = $config['opc_temp_account'];
        }

        if ( array_key_exists('currency_switcher', $config) ) {
            $config['currency_switcher'] = !!$config['currency_switcher'];
        }

        if ( array_key_exists('_api_time_modified', $config) ) {
            $config['modify_time'] = $config['_api_time_modified'];
        }

        parent::__construct($config);

        $addresses = ['address' => []];
        $get_addresses_r = tep_db_query(
            "SELECT ab.*, z.zone_name, c.countries_iso_code_2 ".
            "FROM ".TABLE_ADDRESS_BOOK." ab ".
            " LEFT JOIN " . TABLE_ZONES . " z on (ab.entry_zone_id = z.zone_id) ".
            " LEFT JOIN " . TABLE_COUNTRIES . " c on (c.countries_id = ab.entry_country_id and c.language_id='".\common\classes\language::defaultId()."') ".
            "WHERE ab.customers_id='".intval($this->customers_id)."' ".
            "ORDER BY IF(ab.address_book_id='".(int)$config['customers_default_address_id']."',0,1)"
        );
        if ( tep_db_num_rows($get_addresses_r)>0 ) {
            while( $_address = tep_db_fetch_array($get_addresses_r) ) {
                if ($_address['zone_name']){
                    $_address['entry_state'] = $_address['zone_name'];
                }
                if ($_address['countries_iso_code_2']){
                    $_address['entry_country_iso2'] = $_address['countries_iso_code_2'];
                }else{
                    $_address['entry_country_iso2'] = '';
                    \Yii::error('Customer #'.(int)$this->customers_id.' has invalid country in address book = '.$_address['entry_country_id'],'soap_server');
                }
                $_address['is_default'] = $_address['address_book_id']==$config['customers_default_address_id'];
                $addresses['address'][] = $_address;
                if ($_address['_api_time_modified']>$this->modify_time){
                    $this->modify_time = $_address['_api_time_modified'];
                }
            }
        }
        $this->addresses = new ArrayOfAddresses($addresses);

        if ( !empty($this->modify_time) && $this->modify_time>1000 ) {
            $this->modify_time = \common\api\SoapServer\SoapHelper::soapDateTimeOut($this->modify_time);
        }

    }


}