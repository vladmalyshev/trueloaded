<?php
namespace frontend\forms\affiliate;

use Yii;
use yii\base\Model;

class Signup extends Model {
    
    public $password;
    public $confirmation;
    
    public $terms;
    
    public $affiliate_firstname;
    public $affiliate_lastname;
    public $affiliate_email_address;
    
    public $affiliate_company;
    public $affiliate_company_taxid;

    public $affiliate_payment_paypal;
    public $affiliate_payment_check;
    public $affiliate_payment_bank_name;
    public $affiliate_payment_bank_branch_number;
    public $affiliate_payment_bank_swift_code;
    public $affiliate_payment_bank_account_name;
    public $affiliate_payment_bank_account_number;

    public $affiliate_street_address;
    public $affiliate_suburb;
    public $affiliate_city;
    public $affiliate_state;
    public $affiliate_postcode;
    public $affiliate_country_id;
    
    public $affiliate_telephone;
    public $affiliate_fax;
    public $affiliate_homepage;
    
    /*
    public $affiliate_password;
    public $affiliate_zone_id;
    public $affiliate_agb;
    public $affiliate_commission_percent;
    public $affiliate_payment_paypal;
    public $affiliate_date_of_last_logon;
    public $affiliate_number_of_logons;
    public $affiliate_date_account_created;
    public $affiliate_date_account_last_modified;
    public $affiliate_lft;
    public $affiliate_rgt;
    public $affiliate_root;
    public $affiliate_isactive;
    public $affiliate_email_from;
    public $affiliate_logo;
    public $affiliate_stylesheet;
    public $affiliate_template;
    public $affiliate_store_name;
    public $affiliate_manage_infobox;
    public $affiliate_manage_payments;
    public $affiliate_manage_logo;
    public $affiliate_manage_stylesheet;
    public $affiliate_manage_banners;
    public $own_descriptions;
    public $affiliate_own_product_info;
    public $affiliate_own_product_info_url;
    public $affiliate_continue_shopping_url;
    public $affiliate_directory_listing_url;
    public $affiliate_http_server;
    public $affiliate_https_server;
    public $affiliate_enable_ssl;
    public $affiliate_http_catalog;
    public $affiliate_https_catalog;
    public $affiliate_http_status;
    public $affiliate_default_language;
    public $own_customers;
    public $can_select_products;
    public $affiliate_manage_template;
    public $affiliate_see_customers;
    public $affiliate_see_orders_list;
    public $affiliate_see_orders_detail;
    public $affiliate_view_banner_page;
    public $affiliate_set_from_email_address;
    public $affiliate_see_clickthrough_overview;
    public $affiliate_see_orders_summary;
    */
    
    public function formName() {
        return 'affiliate_registration';
    }
    
    public function rules()
    {
        $_rules = [
            [['password', 'confirmation'], 'string', 'max' => 40],
            [['affiliate_country_id'], 'integer'],
            [['affiliate_firstname', 'affiliate_lastname', 'affiliate_telephone', 'affiliate_fax', 'affiliate_city', 'affiliate_state'], 'string', 'max' => 32],
            [['affiliate_postcode'], 'string', 'max' => 10],
            [['affiliate_street_address', 'affiliate_suburb', 'affiliate_company_taxid', 'affiliate_payment_bank_name', 'affiliate_payment_bank_branch_number', 'affiliate_payment_bank_swift_code', 'affiliate_payment_bank_account_name', 'affiliate_payment_bank_account_number', 'affiliate_payment_paypal'], 'string', 'max' => 64],
            [['affiliate_company'], 'string', 'max' => 60],
            [['affiliate_payment_check'], 'string', 'max' => 100],
            [['affiliate_email_address', 'affiliate_homepage'], 'string', 'max' => 96],
            ['affiliate_email_address', 'email', 'message' => ENTRY_EMAIL_ADDRESS_CHECK_ERROR],
            ['affiliate_email_address', 'emailUnique'],
            ['terms', 'requiredTrems', 'skipOnEmpty' => false]
        ];  
        return $_rules;
    }
    
    public function emailUnique($attribute, $params) {
        
        if (strlen($this->$attribute) < ENTRY_EMAIL_ADDRESS_MIN_LENGTH) {
                $this->addError($attribute, sprintf(ENTRY_EMAIL_ADDRESS_ERROR, ENTRY_EMAIL_ADDRESS_MIN_LENGTH));
            }

            if (!\common\helpers\Validations::validate_email($this->$attribute)) {
                $this->addError($attribute, ENTRY_EMAIL_ADDRESS_CHECK_ERROR);
            }
            
        $check_email = tep_db_query("select affiliate_email_address from affiliate_affiliate where affiliate_email_address = '" . tep_db_input($this->$attribute) . "'");
        if (tep_db_num_rows($check_email)) {
            $this->addError($attribute, ENTRY_EMAIL_ADDRESS_ERROR_EXISTS);
        }
    }
    
    public function requiredTrems($attribute, $params) {
        if (!$this->$attribute) {
            $this->addError($attribute, 'Please Read terms & conditions');
        }
    }
    
    public function getDefaultCountryId() {
        return $this->affiliate_country_id ? $this->affiliate_country_id : STORE_COUNTRY;
    }
    
    public function createAffiliate() {
        
        $sql_data_array = [
            'affiliate_firstname' => $this->affiliate_firstname,
            'affiliate_lastname' => $this->affiliate_lastname,
            'affiliate_email_address' => $this->affiliate_email_address,
            'affiliate_payment_check' => $this->affiliate_payment_check,
            'affiliate_payment_paypal' => $this->affiliate_payment_paypal,
            'affiliate_payment_bank_name' => $this->affiliate_payment_bank_name,
            'affiliate_payment_bank_branch_number' => $this->affiliate_payment_bank_branch_number,
            'affiliate_payment_bank_swift_code' => $this->affiliate_payment_bank_swift_code,
            'affiliate_payment_bank_account_name' => $this->affiliate_payment_bank_account_name,
            'affiliate_payment_bank_account_number' => $this->affiliate_payment_bank_account_number,
            'affiliate_street_address' => $this->affiliate_street_address,
            'affiliate_postcode' => $this->affiliate_postcode,
            'affiliate_city' => $this->affiliate_city,
            'affiliate_country_id' => $this->affiliate_country_id,
            'affiliate_telephone' => $this->affiliate_telephone,
            'affiliate_fax' => $this->affiliate_fax,
            'affiliate_homepage' => $this->affiliate_homepage,
            'affiliate_password' => \common\helpers\Password::encrypt_password($this->password),
            'affiliate_email_from' => '',
            'affiliate_store_name' => '',
            'affiliate_agb' => '1'
        ];
        
        
        $sql_data_array['affiliate_company'] = $this->affiliate_company;
        $sql_data_array['affiliate_company_taxid'] = $this->affiliate_company_taxid;
        $sql_data_array['affiliate_suburb'] = $this->affiliate_suburb;
        
        $sql_data_array['affiliate_zone_id'] = 0;
        $sql_data_array['affiliate_state'] = $this->affiliate_state;
        
        $sql_data_array['affiliate_date_account_created'] = 'now()';
        $sql_data_array['affiliate_lft'] = '1';
        $sql_data_array['affiliate_rgt'] = '2';
                
        tep_db_perform('affiliate_affiliate', $sql_data_array);
        $affiliate_id = tep_db_insert_id();
                
        tep_db_query ("update affiliate_affiliate set affiliate_root = '" . $affiliate_id . "' where affiliate_id = '" . $affiliate_id . "' ");//???
        
        $parameterArray = array();
        $parameterArray['STORE_NAME'] = STORE_NAME;
        $parameterArray['CUSTOMER_FIRSTNAME'] = $this->affiliate_firstname;
        $parameterArray['AFFILIATE_ID'] = $affiliate_id;
        $parameterArray['AFFILIATE_USERNAME'] = $this->affiliate_email_address;
        $parameterArray['AFFILIATE_PASSWORD'] = $this->password;
        $parameterArray['AFFILIATE_LINK'] = \Yii::$app->urlManager->createAbsoluteUrl(["affiliate/"]);
        list($emailSubject, $emailMessage) = \common\helpers\Mail::get_parsed_email_template('Affiliate Signup', $parameterArray);
        \common\helpers\Mail::send($this->affiliate_firstname . ' ' . $this->affiliate_lastname, $this->affiliate_email_address, $emailSubject, $emailMessage, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, $parameterArray);
        
        return $affiliate_id;
    }
    
}