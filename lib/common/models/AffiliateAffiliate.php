<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "affiliate_affiliate".
 *
 * @property int $affiliate_id
 * @property int $platform_id
 * @property int $affiliate_groups_id
 * @property string $affiliate_gender
 * @property string $affiliate_firstname
 * @property string $affiliate_lastname
 * @property string $affiliate_dob
 * @property string $affiliate_email_address
 * @property string $affiliate_telephone
 * @property string $affiliate_fax
 * @property string $affiliate_password
 * @property string $affiliate_homepage
 * @property string $affiliate_street_address
 * @property string $affiliate_suburb
 * @property string $affiliate_city
 * @property string $affiliate_postcode
 * @property string $affiliate_state
 * @property int $affiliate_country_id
 * @property int $affiliate_zone_id
 * @property int $affiliate_agb
 * @property string $affiliate_company
 * @property string $affiliate_company_taxid
 * @property string $affiliate_commission_percent
 * @property string $affiliate_payment_check
 * @property string $affiliate_payment_paypal
 * @property string $affiliate_payment_bank_name
 * @property string $affiliate_payment_bank_branch_number
 * @property string $affiliate_payment_bank_swift_code
 * @property string $affiliate_payment_bank_account_name
 * @property string $affiliate_payment_bank_account_number
 * @property string $affiliate_date_of_last_logon
 * @property int $affiliate_number_of_logons
 * @property string $affiliate_date_account_created
 * @property string $affiliate_date_account_last_modified
 * @property int $affiliate_lft
 * @property int $affiliate_rgt
 * @property int $affiliate_root
 * @property string $affiliate_isactive
 * @property string $affiliate_email_from
 * @property string $affiliate_logo
 * @property string $affiliate_stylesheet
 * @property string $affiliate_template
 * @property string $affiliate_store_name
 * @property string $affiliate_manage_infobox
 * @property string $affiliate_manage_payments
 * @property string $affiliate_manage_logo
 * @property string $affiliate_manage_stylesheet
 * @property string $affiliate_manage_banners
 * @property int $own_descriptions
 * @property string $affiliate_own_product_info
 * @property string $affiliate_own_product_info_url
 * @property string $affiliate_continue_shopping_url
 * @property string $affiliate_directory_listing_url
 * @property string $affiliate_http_server
 * @property string $affiliate_https_server
 * @property int $affiliate_enable_ssl
 * @property string $affiliate_http_catalog
 * @property string $affiliate_https_catalog
 * @property int $affiliate_http_status
 * @property string $affiliate_default_language
 * @property int $own_customers
 * @property int $can_select_products
 * @property string $affiliate_manage_template
 * @property string $affiliate_see_customers
 * @property string $affiliate_see_orders_list
 * @property string $affiliate_see_orders_detail
 * @property string $affiliate_view_banner_page
 * @property string $affiliate_set_from_email_address
 * @property string $affiliate_see_clickthrough_overview
 * @property string $affiliate_see_orders_summary
 */
class AffiliateAffiliate extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'affiliate_affiliate';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['platform_id', 'affiliate_groups_id', 'affiliate_country_id', 'affiliate_zone_id', 'affiliate_agb', 'affiliate_number_of_logons', 'affiliate_lft', 'affiliate_rgt', 'affiliate_root', 'own_descriptions', 'affiliate_enable_ssl', 'affiliate_http_status', 'own_customers', 'can_select_products'], 'integer'],
            //[['affiliate_gender', 'affiliate_firstname', 'affiliate_lastname', 'affiliate_email_address', 'affiliate_telephone', 'affiliate_fax', 'affiliate_password', 'affiliate_homepage', 'affiliate_street_address', 'affiliate_suburb', 'affiliate_city', 'affiliate_postcode', 'affiliate_state', 'affiliate_company', 'affiliate_company_taxid', 'affiliate_payment_check', 'affiliate_payment_paypal', 'affiliate_payment_bank_name', 'affiliate_payment_bank_branch_number', 'affiliate_payment_bank_swift_code', 'affiliate_payment_bank_account_name', 'affiliate_payment_bank_account_number', 'affiliate_isactive', 'affiliate_email_from', 'affiliate_logo', 'affiliate_stylesheet', 'affiliate_template', 'affiliate_store_name', 'affiliate_own_product_info_url', 'affiliate_continue_shopping_url', 'affiliate_directory_listing_url', 'affiliate_http_server', 'affiliate_https_server'], 'required'],
            [['affiliate_dob', 'affiliate_date_of_last_logon', 'affiliate_date_account_created', 'affiliate_date_account_last_modified'], 'safe'],
            [['affiliate_commission_percent'], 'number'],
            [['affiliate_gender', 'affiliate_isactive', 'affiliate_manage_infobox', 'affiliate_manage_logo', 'affiliate_manage_stylesheet', 'affiliate_manage_banners', 'affiliate_own_product_info', 'affiliate_manage_template', 'affiliate_see_customers', 'affiliate_see_orders_list', 'affiliate_see_orders_detail', 'affiliate_view_banner_page', 'affiliate_set_from_email_address', 'affiliate_see_clickthrough_overview', 'affiliate_see_orders_summary'], 'string', 'max' => 1],
            [['affiliate_firstname', 'affiliate_lastname', 'affiliate_telephone', 'affiliate_fax', 'affiliate_city', 'affiliate_state'], 'string', 'max' => 32],
            [['affiliate_email_address', 'affiliate_homepage'], 'string', 'max' => 96],
            [['affiliate_password'], 'string', 'max' => 40],
            [['affiliate_street_address', 'affiliate_suburb', 'affiliate_company_taxid', 'affiliate_payment_paypal', 'affiliate_payment_bank_name', 'affiliate_payment_bank_branch_number', 'affiliate_payment_bank_swift_code', 'affiliate_payment_bank_account_name', 'affiliate_payment_bank_account_number'], 'string', 'max' => 64],
            [['affiliate_postcode'], 'string', 'max' => 10],
            [['affiliate_company'], 'string', 'max' => 60],
            [['affiliate_payment_check'], 'string', 'max' => 100],
            [['affiliate_email_from', 'affiliate_logo', 'affiliate_stylesheet', 'affiliate_template', 'affiliate_store_name', 'affiliate_own_product_info_url', 'affiliate_continue_shopping_url', 'affiliate_directory_listing_url'], 'string', 'max' => 255],
            [['affiliate_manage_payments', 'affiliate_http_server', 'affiliate_https_server', 'affiliate_http_catalog', 'affiliate_https_catalog'], 'string', 'max' => 128],
            [['affiliate_default_language'], 'string', 'max' => 2],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'affiliate_id' => 'Affiliate ID',
            'platform_id' => 'Platform ID',
            'affiliate_groups_id' => 'Affiliate Groups ID',
            'affiliate_gender' => 'Affiliate Gender',
            'affiliate_firstname' => 'Affiliate Firstname',
            'affiliate_lastname' => 'Affiliate Lastname',
            'affiliate_dob' => 'Affiliate Dob',
            'affiliate_email_address' => 'Affiliate Email Address',
            'affiliate_telephone' => 'Affiliate Telephone',
            'affiliate_fax' => 'Affiliate Fax',
            'affiliate_password' => 'Affiliate Password',
            'affiliate_homepage' => 'Affiliate Homepage',
            'affiliate_street_address' => 'Affiliate Street Address',
            'affiliate_suburb' => 'Affiliate Suburb',
            'affiliate_city' => 'Affiliate City',
            'affiliate_postcode' => 'Affiliate Postcode',
            'affiliate_state' => 'Affiliate State',
            'affiliate_country_id' => 'Affiliate Country ID',
            'affiliate_zone_id' => 'Affiliate Zone ID',
            'affiliate_agb' => 'Affiliate Agb',
            'affiliate_company' => 'Affiliate Company',
            'affiliate_company_taxid' => 'Affiliate Company Taxid',
            'affiliate_commission_percent' => 'Affiliate Commission Percent',
            'affiliate_payment_check' => 'Affiliate Payment Check',
            'affiliate_payment_paypal' => 'Affiliate Payment Paypal',
            'affiliate_payment_bank_name' => 'Affiliate Payment Bank Name',
            'affiliate_payment_bank_branch_number' => 'Affiliate Payment Bank Branch Number',
            'affiliate_payment_bank_swift_code' => 'Affiliate Payment Bank Swift Code',
            'affiliate_payment_bank_account_name' => 'Affiliate Payment Bank Account Name',
            'affiliate_payment_bank_account_number' => 'Affiliate Payment Bank Account Number',
            'affiliate_date_of_last_logon' => 'Affiliate Date Of Last Logon',
            'affiliate_number_of_logons' => 'Affiliate Number Of Logons',
            'affiliate_date_account_created' => 'Affiliate Date Account Created',
            'affiliate_date_account_last_modified' => 'Affiliate Date Account Last Modified',
            'affiliate_lft' => 'Affiliate Lft',
            'affiliate_rgt' => 'Affiliate Rgt',
            'affiliate_root' => 'Affiliate Root',
            'affiliate_isactive' => 'Affiliate Isactive',
            'affiliate_email_from' => 'Affiliate Email From',
            'affiliate_logo' => 'Affiliate Logo',
            'affiliate_stylesheet' => 'Affiliate Stylesheet',
            'affiliate_template' => 'Affiliate Template',
            'affiliate_store_name' => 'Affiliate Store Name',
            'affiliate_manage_infobox' => 'Affiliate Manage Infobox',
            'affiliate_manage_payments' => 'Affiliate Manage Payments',
            'affiliate_manage_logo' => 'Affiliate Manage Logo',
            'affiliate_manage_stylesheet' => 'Affiliate Manage Stylesheet',
            'affiliate_manage_banners' => 'Affiliate Manage Banners',
            'own_descriptions' => 'Own Descriptions',
            'affiliate_own_product_info' => 'Affiliate Own Product Info',
            'affiliate_own_product_info_url' => 'Affiliate Own Product Info Url',
            'affiliate_continue_shopping_url' => 'Affiliate Continue Shopping Url',
            'affiliate_directory_listing_url' => 'Affiliate Directory Listing Url',
            'affiliate_http_server' => 'Affiliate Http Server',
            'affiliate_https_server' => 'Affiliate Https Server',
            'affiliate_enable_ssl' => 'Affiliate Enable Ssl',
            'affiliate_http_catalog' => 'Affiliate Http Catalog',
            'affiliate_https_catalog' => 'Affiliate Https Catalog',
            'affiliate_http_status' => 'Affiliate Http Status',
            'affiliate_default_language' => 'Affiliate Default Language',
            'own_customers' => 'Own Customers',
            'can_select_products' => 'Can Select Products',
            'affiliate_manage_template' => 'Affiliate Manage Template',
            'affiliate_see_customers' => 'Affiliate See Customers',
            'affiliate_see_orders_list' => 'Affiliate See Orders List',
            'affiliate_see_orders_detail' => 'Affiliate See Orders Detail',
            'affiliate_view_banner_page' => 'Affiliate View Banner Page',
            'affiliate_set_from_email_address' => 'Affiliate Set From Email Address',
            'affiliate_see_clickthrough_overview' => 'Affiliate See Clickthrough Overview',
            'affiliate_see_orders_summary' => 'Affiliate See Orders Summary',
        ];
    }
}
