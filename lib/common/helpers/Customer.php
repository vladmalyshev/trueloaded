<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\helpers;

class Customer {

    public static function check_customer_groups($groups_id, $field) {
        static $cached = [];
        if (!isset($cached[(int) $groups_id])) {
            $query = tep_db_query("select * from " . TABLE_GROUPS . " where groups_id = '" . (int) $groups_id . "'");
            if (!($cached[(int) $groups_id] = tep_db_fetch_array($query))) {
              $cached[(int) $groups_id] = [];
            }
            if ($cached[(int) $groups_id]['groups_use_more_discount'] ){
                $cached[(int) $groups_id]['groups_discount'] += self::get_additional_discount($groups_id);
            }
        }

        static $cached_u = [];
        $multi_customer_id = \Yii::$app->get('storage')->get('multi_customer_id');

        if (!\Yii::$app->user->isGuest && !empty($multi_customer_id) && !isset($cached_u[(int)$multi_customer_id][$field])) {
            /** @var \common\extensions\CustomersMultiEmails\CustomersMultiEmails $CustomersMultiEmails */
            if ($CustomersMultiEmails = \common\helpers\Acl::checkExtension('CustomersMultiEmails', 'allowed')) {
              if ($CustomersMultiEmails::allowed() && in_array($field, ['groups_is_show_price', 'groups_disable_checkout'] )) {
                $d = \common\extensions\CustomersMultiEmails\models\CustomersMultiEmails::findOne($multi_customer_id);
                if ($d) {
                  //only disable
                  if (!$d->show_price && $field == 'groups_is_show_price') {
                    $cached_u[(int)$multi_customer_id][$field] = false;
                  }
                  if (!$d->allow_checkout && $field == 'groups_disable_checkout') { //inverse flag!!!!
                    $cached_u[(int)$multi_customer_id][$field] = true;
                  }
                }
              }
            }
        } 
        if(isset($cached_u[(int)$multi_customer_id][$field])) {
          return $cached_u[(int)$multi_customer_id][$field];
        } else


        return (isset($cached[(int) $groups_id][$field])?$cached[(int) $groups_id][$field]:false);
    }
    
    public static function get_additional_discount($groups_id, $customers_id = 0){
        $additionals = \common\models\Groups::find()->where('groups_id =:id', [':id' => $groups_id])->with('additionalDiscountsNCS')->one();
        
        if ($additionals->additionalDiscountsNCS){
            if (\Yii::$app->user->isGuest && $customers_id){
                $customer = \common\models\Customers::findOne($customers_id);
            } else {
                $customer = \Yii::$app->user->getIdentity();
            }
            if ($customer){
                $OrderedAmount = $customer->fetchOrderTotalAmount(true);
                $discounts = \yii\helpers\ArrayHelper::index($additionals->additionalDiscountsNCS, 'groups_discounts_amount');
                if (is_array($discounts)){
                    krsort($discounts);
                    foreach($discounts as $amount => $data ){
                        if ($OrderedAmount > $amount && !$data['check_supersum']){
                            return $data['groups_discounts_value'];
                        }
                    }
                }
            }
        }
        return 0;
    }
    
    public static function get_additional_superdiscount($customers_id = 0, $cartTotal = 0){
        if (\Yii::$app->user->isGuest && $customers_id){
            $customer = \common\models\Customers::findOne($customers_id);
        } else {
            $customer = \Yii::$app->user->getIdentity();
        }        
        if ($customer){
            $additionals = \common\models\Groups::find()->where('groups_id =:id', [':id' => $customer->groups_id])->with('additionalDiscountsCS')->one();
            if ($additionals->additionalDiscountsCS){
                $OrderedAmount = $customer->fetchOrderTotalAmount(true);
                $discounts = \yii\helpers\ArrayHelper::index($additionals->additionalDiscountsCS, 'groups_discounts_amount');
                $currentDiscount = self::check_customer_groups($customer->groups_id, 'groups_discount');                
                if (is_array($discounts)){
                    krsort($discounts);
                    foreach($discounts as $amount => $data ){                        
                        if ($OrderedAmount > $amount && $data['check_supersum'] && $cartTotal >= $additionals->superdiscount_summ ){
                            return max(0, $data['groups_discounts_value'] - (float)$currentDiscount);
                        }
                    }
                }
            }
        }
        return 0;
    }

    public static function count_customer_address_book_entries($id = '', $check_session = true) {

        if (is_numeric($id) == false) {
            if (!\Yii::$app->user->isGuest) {
                $id = \Yii::$app->user->getId();
            } else {
                return 0;
            }
        }

        if ($check_session == true) {
            if (\Yii::$app->user->isGuest || ($id != \Yii::$app->user->getId())) {
                return 0;
            }
        }

        $addresses_query = tep_db_query("select count(*) as total from " . TABLE_ADDRESS_BOOK . " where customers_id = '" . (int) $id . "'");
        $addresses = tep_db_fetch_array($addresses_query);

        return $addresses['total'];
    }

    public static function is_customer_exist($customer_id) {
        if (tep_db_num_rows(tep_db_query("select customers_id from " . TABLE_CUSTOMERS . " where customers_id='" . (int) $customer_id . "'")) > 0) {
            return true;
        }
        return false;
    }

    public static function count_customer_orders($id = '', $check_session = true) {
        
        if (is_numeric($id) == false) {
            if (!\Yii::$app->user->isGuest) {
                $id = \Yii::$app->user->getId();
            } else {
                return 0;
            }
        }

        if ($check_session == true) {
            if (\Yii::$app->user->isGuest || ($id != \Yii::$app->user->getId())) {
                return 0;
            }
        }

        $orders_check_query = tep_db_query("select count(*) as total from " . TABLE_ORDERS . " where customers_id = '" . (int) $id . "'");
        $orders_check = tep_db_fetch_array($orders_check_query);

        return $orders_check['total'];
    }
    
    public static function get_customers_group($customer_id) {
        if (CUSTOMERS_GROUPS_ENABLE == 'True') {
            $check = tep_db_query("select * from " . TABLE_CUSTOMERS . " where customers_id = '" . tep_db_input($customer_id) . "'");
            $checkData = tep_db_fetch_array($check);
            return $checkData['groups_id'];
        } else {
            return 0;
        }
    }
    
    public static function get_additional_fields_tree($customer_id = 0) {
        $additionalFields = [];
        
        $group_query = tep_db_query("SELECT fgd.* FROM " . TABLE_ADDITIONAL_FIELDS_GROUP . " fg INNER JOIN " . TABLE_ADDITIONAL_FIELDS_GROUP_DESCRIPTION . " fgd ON fgd.additional_fields_group_id=fg.additional_fields_group_id WHERE fgd.language_id = '" . 1 . "' order by fg.sort_order, fgd.title");
        while ($group = tep_db_fetch_array($group_query)) {

            $child = [];
            $fields_query = tep_db_query("SELECT afd.*, af.additional_fields_code, af.field_type FROM " . TABLE_ADDITIONAL_FIELDS . " af INNER JOIN " . TABLE_ADDITIONAL_FIELDS_DESCRIPTION . " afd ON afd.additional_fields_id=af.additional_fields_id WHERE additional_fields_group_id = '" . $group['additional_fields_group_id'] . "' AND afd.language_id = '" . 1 . "' order by af.sort_order, afd.title");
            while ($fields = tep_db_fetch_array($fields_query)) {
                $value = '';
                $check_query = tep_db_query("SELECT value FROM " . TABLE_CUSTOMERS_ADDITIONAL_FIELDS . " WHERE customers_id = '" . (int)$customer_id . "' AND additional_fields_id = '" . (int)$fields['additional_fields_id'] . "'");
                if ($check = tep_db_fetch_array($check_query)) {
                    $value = $check['value'];
                }
                $child[] = [
                    'id' =>  $fields['additional_fields_id'],
                    'name' => $fields['title'],
                    'code' => $fields['additional_fields_code'],
                    'field_type' => $fields['field_type'],
                    'value' => $value,
                ];
            }
            
            if (count($child) > 0) {
                $additionalFields[] = [
                    'name' => $group['title'],
                    'child' => $child,
                ];
            }
        }
        
        return $additionalFields;
    }

    public static function get_additional_fields($customer_id = 0) {
        $additionalFields = [];
        
        $fields_query = tep_db_query("SELECT * FROM " . TABLE_ADDITIONAL_FIELDS . " WHERE 1");
        while ($fields = tep_db_fetch_array($fields_query)) {
            $value = '';
            $check_query = tep_db_query("SELECT value FROM " . TABLE_CUSTOMERS_ADDITIONAL_FIELDS . " WHERE customers_id = '" . (int)$customer_id . "' AND additional_fields_id = '" . (int)$fields['additional_fields_id'] . "'");
            if ($check = tep_db_fetch_array($check_query)) {
                $value = $check['value'];
            }
            $additionalFields[$fields['additional_fields_code']] = $value;
        }
        
        return $additionalFields;
    }

    public  static function get_address_book_data($customer_id = 0){
        global $languages_id;
        $addresses = array();

        $query = tep_db_query("
            select 
                a.address_book_id as id,
                a.entry_gender as gender,
                a.entry_company as company,
                a.entry_firstname as firstname,
                a.entry_lastname as lastname,
                a.entry_street_address as street_address,
                a.entry_suburb as suburb,
                a.entry_postcode as postcode,
                a.entry_city as city,
                if (a.entry_zone_id, z.zone_name, a.entry_state) as state,
                a.entry_zone_id as zone_id,
                a.entry_company_vat as company_vat,
                a.entry_country_id as country_id,
                c.countries_name as country,
                a.entry_telephone as telephone
            from
                " . TABLE_ADDRESS_BOOK . " a left join " . TABLE_ZONES . " z on a.entry_zone_id = z.zone_id,
                " . TABLE_COUNTRIES . " c 
            where
                a.entry_country_id = c.countries_id and
                c.language_id = '" . $languages_id . "' and 
                a.customers_id = '" . $customer_id . "'
        ");
        while ($item = tep_db_fetch_array($query)){
            $addresses[] = $item;
        }
        
        return $addresses;
    }
    
    public static function getCustomerData($id){
        if ( (int)$id==0 ) return null;
        $_details = tep_db_fetch_array(tep_db_query("select * from " . TABLE_CUSTOMERS . " where customers_id = '" . (int)$id . "'"));
        return $_details;
    }
    
    public static function trunk_customers() {        
        tep_db_query("TRUNCATE TABLE " . TABLE_CUSTOMERS);
        tep_db_query("TRUNCATE TABLE " . TABLE_CUSTOMERS_INFO);
        tep_db_query("TRUNCATE TABLE " . TABLE_CUSTOMERS_BASKET);
        tep_db_query("TRUNCATE TABLE " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES);
        tep_db_query("TRUNCATE TABLE " . TABLE_CUSTOMERS_QUOTE);
        tep_db_query("TRUNCATE TABLE " . TABLE_CUSTOMERS_QUOTE_ATTRIBUTES);
        tep_db_query("TRUNCATE TABLE " . TABLE_CUSTOMERS_SAMPLE);
        tep_db_query("TRUNCATE TABLE " . TABLE_CUSTOMERS_SAMPLE_ATTRIBUTES);
        tep_db_query("TRUNCATE TABLE " . TABLE_CUSTOMERS_ADDITIONAL_FIELDS);
        tep_db_query("TRUNCATE TABLE " . TABLE_CUSTOMERS_CREDIT_HISTORY);
        tep_db_query("TRUNCATE TABLE " . TABLE_CUSTOMERS_ERRORS);
        tep_db_query("TRUNCATE TABLE " . TABLE_ADDRESS_BOOK);
        tep_db_query("TRUNCATE TABLE " . TABLE_WISHLIST);
        tep_db_query("TRUNCATE TABLE " . TABLE_WISHLIST_ATTRIBUTES);
        tep_db_query("TRUNCATE TABLE customers_emails");
        tep_db_query("TRUNCATE TABLE " . TABLE_COUPON_GV_CUSTOMER);
        tep_db_query("TRUNCATE TABLE coupon_refer_queue");
        tep_db_query("TRUNCATE TABLE " . TABLE_WHOS_ONLINE);
        $schemaCheck = Yii::$app->get('db')->schema->getTableSchema('regular_offers');
        if ( $schemaCheck ) {
            tep_db_query("TRUNCATE TABLE regular_offers");
        }
    }
    
    public static function get_customer_points($customer_id = 0){
        if ($customer_id){
            $bonuses = tep_db_fetch_array(tep_db_query("select customers_bonus_points from " . TABLE_CUSTOMERS . " where customers_id = '" . (int)$customer_id . "'"));
            return $bonuses['customers_bonus_points'];
        }        
        return 0;
    }

    public static function deleteCustomer($customer_id = 0, $notify = true) {
        // find anonymous user id
        $check_customer_query = tep_db_query("select customers_id from " . TABLE_CUSTOMERS . " where customers_email_address = 'removed'");
        if (tep_db_num_rows($check_customer_query) == 0) {
            $sqlData = [
                'customers_firstname' => 'removed',
                'customers_lastname' => 'removed',
                'customers_email_address' => 'removed',
                'customers_status' => 0
            ];
            tep_db_perform(TABLE_CUSTOMERS, $sqlData);
            $removedId = tep_db_insert_id();
            tep_db_query("insert into " . TABLE_CUSTOMERS_INFO . " (customers_info_id, customers_info_number_of_logons, customers_info_date_account_created) values ('" . (int) $removedId . "', '0', now())");
        } else {
            $check_customer = tep_db_fetch_array($check_customer_query);
            $removedId = $check_customer['customers_id'];
        }
        
        if ($removedId == $customer_id) {
            return false;
        }
        
        $check_customer_query = tep_db_query("select customers_gender, customers_firstname, customers_lastname, customers_email_address from " . TABLE_CUSTOMERS . " where customers_id = '" . (int) $customer_id . "'");
        if (tep_db_num_rows($check_customer_query) == 0) {
            return false;
        }
        $check_customer = tep_db_fetch_array($check_customer_query);

        if ($notify) {
            $gender = $check_customer['customers_gender'];
            \common\helpers\Translation::init('account/create');
            if ($gender == 'm') {
                $user_greeting = sprintf(EMAIL_GREET_MR, $check_customer['customers_lastname']);
            } elseif ($gender == 'f' || $gender == 's') {
                $user_greeting = sprintf(EMAIL_GREET_MS, $check_customer['customers_lastname']);
            } else {
                $user_greeting = sprintf(EMAIL_GREET_NONE, $check_customer['customers_firstname']);
            }

            $email_params = array();
            $email_params['STORE_NAME'] = STORE_NAME;
            $email_params['USER_GREETING'] = trim($user_greeting);
            $email_params['STORE_OWNER_EMAIL_ADDRESS'] = STORE_OWNER_EMAIL_ADDRESS;
            $email_params['HTTP_HOST'] = \common\helpers\Output::get_clickable_link(HTTP_SERVER);
            $email_params['CUSTOMER_EMAIL'] = $check_customer['customers_email_address'];
            $email_params['CUSTOMER_FIRSTNAME'] = $check_customer['customers_firstname'];

            list($email_subject, $email_text) = \common\helpers\Mail::get_parsed_email_template('Deleting an account', $email_params);

            \common\helpers\Mail::send($check_customer['customers_firstname'] . ' ' . $check_customer['customers_lastname'], $check_customer['customers_email_address'], $email_subject, $email_text, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
        }
        
        // make data anonymous
        tep_db_query("update " . TABLE_REVIEWS . " set customers_id = null, customers_name='removed' where customers_id = '" . (int) $customer_id . "'");
        $sqlData = [
            'customers_id' => (int)$removedId,
            'basket_id' => 0,
            'customers_name' => 'removed',
            'customers_firstname' => 'removed',
            'customers_lastname' => 'removed',
            'customers_company' => '',
            'customers_company_vat' => '',
            'customers_street_address' => '',
            'customers_suburb' => '',
            'customers_city' => '',
            'customers_postcode' => '',
            //customers_state
            //customers_country
            'customers_telephone' => '',
            'customers_email_address' => 'removed',
            'delivery_gender' => '',
            'delivery_name' => 'removed',
            'delivery_firstname' => 'removed',
            'delivery_lastname' => 'removed',
            'delivery_company' => '',
            'delivery_street_address' => '',
            'delivery_suburb' => '',
            'delivery_city' => '',
            'delivery_postcode' => '',
            //delivery_state
            //delivery_country
            'delivery_address_book_id' => 0,
            'billing_gender' => '',
            'billing_name' => 'removed',
            'billing_firstname' => 'removed',
            'billing_lastname' => 'removed',
            'billing_company' => '',
            'billing_street_address' => '',
            'billing_suburb' => '',
            'billing_city' => '',
            'billing_postcode' => '',
            //billing_state
            //billing_country
            'billing_address_book_id' => 0,
        ];
        tep_db_perform(TABLE_ORDERS, $sqlData, 'update', "customers_id = '" . (int)$customer_id . "'");
        tep_db_perform('quote_' . TABLE_ORDERS, $sqlData, 'update', "customers_id = '" . (int)$customer_id . "'");
        tep_db_perform('sample_' . TABLE_ORDERS, $sqlData, 'update', "customers_id = '" . (int)$customer_id . "'");
        tep_db_perform('tmp_' . TABLE_ORDERS, $sqlData, 'update', "customers_id = '" . (int)$customer_id . "'");
        unset($sqlData['basket_id']);
        tep_db_perform(TABLE_SUBSCRIPTION, $sqlData, 'update', "customers_id = '" . (int)$customer_id . "'");
        tep_db_query("update " . TABLE_COUPON_REDEEM_TRACK . " set customer_id = '" . (int) $removedId . "', redeem_ip = '' where customer_id = '" . (int) $customer_id . "';");
        tep_db_query("update " . TABLE_COUPON_GV_QUEUE . " set customer_id = '" . (int) $removedId . "', ipaddr = '' where customer_id = '" . (int) $customer_id . "';");

        // remove customer
        tep_db_query("DELETE FROM " . TABLE_CUSTOMERS . " WHERE customers_id=" . (int)$customer_id);
        tep_db_query("DELETE FROM " . TABLE_CUSTOMERS_INFO . " WHERE customers_info_id=" . (int)$customer_id);
        tep_db_query("DELETE FROM " . TABLE_ADDRESS_BOOK . " WHERE customers_id=" . (int)$customer_id);
        
        tep_db_query("DELETE FROM " . TABLE_CUSTOMERS_BASKET . " WHERE customers_id=" . (int)$customer_id);
        tep_db_query("DELETE FROM " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . " WHERE customers_id=" . (int)$customer_id);
        tep_db_query("DELETE FROM " . TABLE_CUSTOMERS_QUOTE . " WHERE customers_id=" . (int)$customer_id);
        tep_db_query("DELETE FROM " . TABLE_CUSTOMERS_QUOTE_ATTRIBUTES . " WHERE customers_id=" . (int)$customer_id);
        tep_db_query("DELETE FROM " . TABLE_CUSTOMERS_SAMPLE . " WHERE customers_id=" . (int)$customer_id);
        tep_db_query("DELETE FROM " . TABLE_CUSTOMERS_SAMPLE_ATTRIBUTES . " WHERE customers_id=" . (int)$customer_id);
        if ($mCart = \common\helpers\Acl::checkExtension('MultiCart', 'allowed')){
            $mCart::removeAllBaskets($customer_id);
        }
        //tep_db_query("DELETE FROM  WHERE customers_id=" . (int)$customer_id);
        tep_db_query("DELETE FROM " . TABLE_WISHLIST . " WHERE customers_id=" . (int)$customer_id);
        tep_db_query("DELETE FROM " . TABLE_WISHLIST_ATTRIBUTES . " WHERE customers_id=" . (int)$customer_id);
        
        tep_db_query("DELETE FROM " . TABLE_WHOS_ONLINE . " WHERE customer_id = '" . (int) $customer_id . "'");
        tep_db_query("DELETE FROM " . TABLE_CUSTOMERS_ADDITIONAL_FIELDS . " WHERE customers_id=" . (int)$customer_id);
        tep_db_query("DELETE FROM " . TABLE_CUSTOMERS_CREDIT_HISTORY . " WHERE customers_id=" . (int)$customer_id);
        tep_db_query("DELETE FROM customers_emails WHERE customers_id=" . (int)$customer_id);
        tep_db_query("DELETE FROM " . TABLE_CUSTOMERS_ERRORS . " WHERE customers_id=" . (int)$customer_id);
        tep_db_query("DELETE FROM " . TABLE_VIRTUAL_GIFT_CARD_BASKET . " WHERE customers_id=" . (int)$customer_id);
        
        tep_db_query("DELETE FROM customers_phones WHERE customers_id=" . (int)$customer_id);
        tep_db_query("DELETE FROM customers_stripe_tokens WHERE customers_id=" . (int)$customer_id);
        tep_db_query("DELETE FROM " . TABLE_COUPON_GV_CUSTOMER . " WHERE customer_id=" . (int)$customer_id);
        tep_db_query("DELETE FROM coupon_refer_queue WHERE customers_id=" . (int)$customer_id);
        
        if ($wExt = \common\helpers\Acl::checkExtension('WeddingRegistry', 'allowed')){
            $wExt::removeCustomerWedding($customer_id);
        }        
        
        if ($ext = \common\helpers\Acl::checkExtension('CustomerProducts', 'deleteCustomer')) {
            $ext::deleteCustomer($customer_id);
        }
        
        /** @var \common\extensions\ExtraGroups\ExtraGroups $ext */
        if ($ext = \common\helpers\Acl::checkExtension('ExtraGroups', 'deleteCustomer')) {
            $ext::deleteCustomer($customer_id);
        }

        /** @var \common\extensions\CustomerModules\CustomerModules $CustomerModules */
        if ($CustomerModules = \common\helpers\Acl::checkExtension('CustomerModules', 'deleteCustomer')) {
            $CustomerModules::deleteCustomer($customer_id);
        }

        if (defined('EXTENSION_MESSAGES_ENABLED')) {
            $messages_query = tep_db_query("SELECT messages_id FROM messages WHERE owner_id = 'c_" . (int) $customer_id . "'");
            while ($messages = tep_db_fetch_array($messages_query)) {
                tep_db_query("DELETE FROM messages_attachments WHERE messages_id = '" . (int) $messages['messages_id'] . "'");
            }
            tep_db_query("DELETE FROM messages WHERE owner_id = 'c_" . (int) $customer_id . "'");
        }
        
        tep_db_query("DELETE FROM promotions_customer_codes WHERE customer_id=" . (int)$customer_id);
        tep_db_query("DELETE FROM " . TABLE_PRODUCTS_NOTIFY . " WHERE products_notify_customers_id=" . (int)$customer_id);
        tep_db_query("DELETE FROM ep_holbi_soap_link_customers WHERE local_customers_id=" . (int)$customer_id);
        
        tep_db_query("DELETE FROM gdpr_check WHERE customers_id=" . (int)$customer_id);
        tep_db_query("DELETE FROM guest_check WHERE customers_id=" . (int)$customer_id);
        tep_db_query("DELETE FROM regular_offers WHERE customers_id=" . (int)$customer_id);
        
    }
    
    public static function hasAddressBook($customer_id, $addr_id){
        if ($customer_id && $addr_id){
            return tep_db_num_rows(tep_db_query("select address_book_id from " . TABLE_ADDRESS_BOOK . " where address_book_id = '" . (int)$addr_id . "' and customers_id = '" . (int)$customer_id . "'"));
        }
        return false;
    }
    
    public static function check_need_login($group_id){
        return $group_id != 0 && self::check_customer_groups($group_id, 'new_approve')? false : true;
    }
    
    public static function updateBasketId($customers_id, $oldBasketId, $newBasketId){
        if ($customers_id && $oldBasketId && $newBasketId){
            \common\models\CustomersErrors::updateAll(['basket_id' => $newBasketId], ['customers_id' => $customers_id, 'basket_id' => $oldBasketId ]);
        }
    }
}
