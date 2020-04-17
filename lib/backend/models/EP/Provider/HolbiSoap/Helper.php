<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models\EP\Provider\HolbiSoap;


use backend\models\EP\DatasourceBase;
use backend\models\EP\DataSources;
use backend\models\EP\Directory;
use backend\models\EP\Job;
use backend\models\EP\Messages;
use common\api\models\AR\Products;
use backend\models\EP\Tools;
use common\api\models\AR\Customer;
use common\api\models\AR\Supplier;
use common\classes\Order;

class Helper
{

    public static function getKeyValue($directoryId, $keyName)
    {
        $value = null;
        $get_value_r = tep_db_query("SELECT key_value FROM ep_holbi_soap_kv_storage WHERE ep_directory_id='".(int)$directoryId."' AND key_name='".tep_db_input($keyName)."'");
        if ( tep_db_num_rows($get_value_r) ) {
            $_value = tep_db_fetch_array($get_value_r);
            $value = $_value['key_value'];
        }
        return $value;
    }

    public static function setKeyValue($directoryId, $keyName, $value)
    {
        tep_db_query(
            "INSERT INTO ep_holbi_soap_kv_storage (ep_directory_id, key_name, key_value) ".
            "VALUES ('".(int)$directoryId."', '".tep_db_input($keyName)."', '".tep_db_input($value)."')".
            "ON DUPLICATE KEY UPDATE key_value='".tep_db_input($value)."' "
        );
    }

    public static function syncOrderStatuses(\SoapClient $soapClient, DatasourceBase $datasource)
    {
        \Yii::info('[HolbiSoap::syncOrderStatuses] Start','datasource');
        try{
            $statuses = static::getOrderStatusesFromServer($soapClient, true);

            \Yii::info('[HolbiSoap::syncOrderStatuses] GET '.var_export($statuses,true),'datasource');

            if ( is_array($statuses) ) {
                $serverMapping = [];
                foreach($statuses as $status){
                    if (is_null($status['external_status_id'])) continue;
                    $serverMapping[(int)$status['external_status_id']] = $status['_id'];
                }

                $config = $datasource->getJobConfig();
                $status_map_local_to_server = (isset($config['status_map_local_to_server']) && is_array($config['status_map_local_to_server']))?$config['status_map_local_to_server']:[];
                if ( count($serverMapping)>0 && $serverMapping!=$status_map_local_to_server ) {
                    $datasource->updateSettingKey('status_map_local_to_server', $serverMapping);
                    \Yii::info('[HolbiSoap::syncOrderStatuses] Update local mapping to '.preg_replace('/\s{2,}/ims',' ',var_export($serverMapping,true)),'datasource');
                }
                static::putOrderStatusesOnServer($soapClient, $datasource->getJobConfig());
            }
        }catch (\Exception $ex){
            \Yii::info('[HolbiSoap::syncOrderStatuses] Exception:'.$ex->getMessage(),'datasource');
        }
        \Yii::info('[HolbiSoap::syncOrderStatuses] Done.','datasource');
    }

    public static function putOrderStatusesOnServer(\SoapClient $soapClient, $config)
    {
        try {
            $list = [];
            $configured_languages = [0];
            foreach (\common\classes\language::get_all() as $_lang) {
                $configured_languages[] = $_lang['id'];
            }

            $get_data_r = tep_db_query(
                "SELECT os.orders_status_id, os.language_id, os.orders_status_name, " .
                " osg.orders_status_groups_id, osg.orders_status_groups_name " .
                "FROM " . TABLE_ORDERS_STATUS . " os " .
                " LEFT JOIN " . TABLE_ORDERS_STATUS_GROUPS . " osg ON osg.orders_status_groups_id=os.orders_status_groups_id AND osg.language_id=os.language_id " .
                "WHERE os.language_id IN (" . implode(",", $configured_languages) . ") " .
                " AND osg.orders_status_type_id = '".intval(\common\helpers\Order::getStatusTypeId())."' " .
                "ORDER BY osg.orders_status_groups_id, os.orders_status_id, os.language_id"
            );
            if (tep_db_num_rows($get_data_r) > 0) {
                while ($data = tep_db_fetch_array($get_data_r)) {
                    $data['language'] = \common\classes\language::get_code($data['language_id']);
                    if (!isset($list[$data['orders_status_id']])) {
                        $list[$data['orders_status_id']] = [
                            'id' => $data['orders_status_id'],
                            'names' => [],
                            'group_id' => $data['orders_status_groups_id'],
                            'group_names' => [],
                        ];
                    }
                    $list[$data['orders_status_id']]['names'][] = ['language' => $data['language'], 'text' => $data['orders_status_name']];
                    $list[$data['orders_status_id']]['group_names'][] = ['language' => $data['language'], 'text' => $data['orders_status_groups_name']];
                    if (isset($config['status_map_local_to_server'][$data['orders_status_id']]) && !empty($config['status_map_local_to_server'][$data['orders_status_id']])) {
                        if ( strpos($config['status_map_local_to_server'][$data['orders_status_id']],'create_in_')===0 ) {
                            $list[$data['orders_status_id']]['createInGroup'] = str_replace('create_in_','',$config['status_map_local_to_server'][$data['orders_status_id']]);
                        }else {
                            $list[$data['orders_status_id']]['external_status_id'] = $config['status_map_local_to_server'][$data['orders_status_id']];
                        }
                    }

                }
            }

            $soapClient->putOrderStatuses(
                0,
                array_values($list)
            /*
                [
                    ['id'=>1,'group_id'=>1,'names'=>[['text'=>'test1','language'=>'en']],'group_names'=>[['text'=>'test1','language'=>'en']]],
                    ['id'=>1,'group_id'=>1,'names'=>[['text'=>'test1','language'=>'en']],'group_names'=>[['text'=>'test1','language'=>'en']]],
                ]
            */
            );
//            \Yii::info($soapClient->__getLastRequest(),'datasource');
//            \Yii::info($soapClient->__getLastResponse(),'datasource');
            $status = true;
        }catch (\Exception $ex){
            $status = false;
        }
        return $status;
    }

    public static function getOrderStatusesFromServer(\SoapClient $soapClient, $processCreateRequest=false)
    {
        $serverStatuses = [];
        try {

            $response = $soapClient->getOrderStatuses();
            if ( $response && $response->status=='OK' ) {
                $order_statuses = $response->statuses->order_status;
                if ( is_object($order_statuses) ) $order_statuses = [$order_statuses];


                $last_group_id = -1;
                foreach( $order_statuses as $remote_status ) {
                    $id = $remote_status->id;
                    $name = '';
                    $namesIn = is_array($remote_status->names->language_value)?$remote_status->names->language_value:[$remote_status->names->language_value];
                    $names = [];
                    foreach ($namesIn as $_name) {
                        if ( empty($name) ) $name = $_name->text;
                        $names[ $_name->language ] = $_name->text;
                    }
                    $group_id = $remote_status->group_id;
                    $group_name = '';
                    $group_names = is_array($remote_status->group_names->language_value)?$remote_status->group_names->language_value:[$remote_status->group_names->language_value];
                    foreach ($group_names as $_group_name) {
                        $group_name = $_group_name->text;
                        break;
                    }

                    // {{ create
                    if ( $processCreateRequest && isset($remote_status->createInGroup) && is_numeric($remote_status->createInGroup) ){
                        $groupId = (int)$remote_status->createInGroup;
                        $check_group = tep_db_fetch_array(tep_db_query("SELECT COUNT(*) AS c FROM ".TABLE_ORDERS_STATUS_GROUPS." WHERE orders_status_groups_id='{$groupId}' "));
                        if ( $check_group['c']>0 ) {
                            $defaultName = current($names);
                            if ( isset($names[DEFAULT_LANGUAGE]) ) {
                                $defaultName = $names[DEFAULT_LANGUAGE];
                                $check_exists_r = tep_db_query(
                                    "SELECT orders_status_id ".
                                    "FROM ".TABLE_ORDERS_STATUS." ".
                                    "WHERE orders_status_name='".tep_db_input($defaultName)."' ".
                                    " AND language_id='".\common\classes\language::get_id(DEFAULT_LANGUAGE)."' ".
                                    " AND orders_status_groups_id='{$groupId}' ".
                                    "LIMIT 1"
                                );
                                if ( tep_db_num_rows($check_exists_r)>0 ) {
                                    $check_exists = tep_db_fetch_array($check_exists_r);
                                    $remote_status->external_status_id = $check_exists['orders_status_id'];
                                    break;
                                }
                            }else{
                                $check_exists_r = tep_db_query(
                                    "SELECT orders_status_id ".
                                    "FROM ".TABLE_ORDERS_STATUS." ".
                                    "WHERE orders_status_name='".tep_db_input($defaultName)."' ".
                                    " AND orders_status_groups_id='{$groupId}' ".
                                    "LIMIT 1"
                                );
                                if ( tep_db_num_rows($check_exists_r)>0 ) {
                                    $check_exists = tep_db_fetch_array($check_exists_r);
                                    $remote_status->external_status_id = $check_exists['orders_status_id'];
                                    break;
                                }
                            }
                            $get_current_status_id = tep_db_fetch_array(tep_db_query(
                                "SELECT MAX(orders_status_id) AS current_max_id FROM ".TABLE_ORDERS_STATUS." "
                            ));
                            $new_status_id = intval($get_current_status_id['current_max_id'])+1;
                            tep_db_query(
                                "INSERT INTO ".TABLE_ORDERS_STATUS." (orders_status_id, orders_status_groups_id, language_id, orders_status_name) ".
                                " SELECT {$new_status_id}, {$groupId}, languages_id, '".tep_db_input($defaultName)."' FROM ".TABLE_LANGUAGES." "
                            );
                            $defLangId = \common\classes\language::get_id(DEFAULT_LANGUAGE);
                            foreach ( $names as $langCode=>$langName ) {
                                $langId = \common\classes\language::get_id($langCode);
                                if ( $defLangId==$langId ) continue;
                                tep_db_query(
                                    "UPDATE ".TABLE_ORDERS_STATUS." ".
                                    "SET orders_status_name='".tep_db_input($defaultName)."' ".
                                    "WHERE orders_status_id='{$new_status_id}' AND language_id='".$langId."'"
                                );
                            }
                            $remote_status->external_status_id = $new_status_id;
                        }
                    }
                    // }} create

                    if ( $last_group_id!=$group_id ) {
                        $serverStatuses[] = [
                            'id' => 'group_'.$group_id,
                            '_id' => $group_id,
                            'name' => $group_name,
                            'text' => $group_name,
                            'color' => $remote_status->color,
                        ];
                        $last_group_id = $group_id;
                    }
                    $serverStatuses[] = [
                        'id' => 'status_'.$id,
                        '_id' => $id,
                        'group_id' => $group_id,
                        'name' => $name,
                        'text' => ($group_id>0?str_repeat('&nbsp;',4):'').$name,
                        'external_status_id' => $remote_status->external_status_id,
                    ];
                }
            }
        }catch (\Exception $ex){
            $serverStatuses = false;
            \Yii::error('HolbiSoap callSoap::getOrderStatuses exception '.$ex->getMessage().($soapClient?"\nSoap Request:\n".$soapClient->__getLastRequest()."\nSoap Response:\n".$soapClient->__getLastResponse()."\n":''),'datasource');
        }
        return $serverStatuses;
    }

    static public function generateOrderHash($orderId)
    {
        if ( $orderId instanceof \common\classes\extended\OrderAbstract ) {
            $order = $orderId;
        }else {
            $order = new Order($orderId);
        }
        $orderHash = md5(json_encode($order));
        unset($order);
        return $orderHash;
    }

    static public function updateCustomerModifyTime($customerId=null)
    {
        tep_db_query(
            "UPDATE ".TABLE_CUSTOMERS." c ".
            "  INNER JOIN ".TABLE_ADDRESS_BOOK." ab ON ab.customers_id=c.customers_id ".
            "  SET c._api_time_modified = GREATEST(c._api_time_modified,IFNULL(ab._api_time_modified,0)) ".
            "WHERE 1 ".(is_numeric($customerId)?" AND c.customers_id='".(int)$customerId."' ":'')
        );
    }

    static public function makeCustomerRequestData($customer)
    {
        if ( is_object($customer) && $customer instanceof Customer ) {
            $localCustomer = $customer->exportArray([]);
        }elseif ( is_array($customer) ){
            $localCustomer = $customer;
        }elseif (is_numeric($customer)){
            $customerObj = Customer::findOne(['customers_id'=>(int)$customer]);
            $localCustomer = $customerObj->exportArray([]);
        }else{
            return [];
        }

        $customerDataArray = [
            'platform_id' => $localCustomer['platform_id'],
            'platform_name' => \common\classes\platform::name($localCustomer['platform_id']),
            'customers_id' => $localCustomer['customers_id'],
            'customers_gender' => $localCustomer['customers_gender'],
            'customers_firstname' => $localCustomer['customers_firstname'],
            'customers_lastname' => $localCustomer['customers_lastname'],
            'customers_dob' => $localCustomer['customers_dob'],
            'customers_email_address' => $localCustomer['customers_email_address'],
            'customers_telephone' => $localCustomer['customers_telephone'],
            'customers_landline' => $localCustomer['customers_landline'],
            'customers_fax' => $localCustomer['customers_fax'],
            'customers_newsletter' => $localCustomer['customers_newsletter'],
            //'customers_bonus_points' => $localCustomer['customers_bonus_points'],
            //'customers_credit_avail'=> $localCustomer['customers_credit_avail'],
            'groups_id' => $localCustomer['groups_id'],
            'customers_status' => $localCustomer['customers_status'],
            'is_guest' => $localCustomer['is_guest'],
            'customers_company' => $localCustomer['customers_company'],
            'customers_company_vat' => $localCustomer['customers_company_vat'],
            'sap_servers_id' => $localCustomer['sap_servers_id'],
            'customers_cardcode' => $localCustomer['customers_cardcode'],
            'customers_currency' => \common\helpers\Currencies::getCurrencyCode($localCustomer['customers_currency_id']),
            'currency_switcher' => !!$localCustomer['currency_switcher'],
            //'credit_amount'=> $localCustomer['credit_amount'],
            'addresses' => [
                'address' => [],
            ],
        ];
        foreach ($localCustomer['addresses'] as $localAB) {
            $localAB['is_default'] = $localAB['address_book_id'] == $localCustomer['customers_default_address_id'];
            $customerDataArray['addresses']['address'][] = $localAB;
        }

        return $customerDataArray;
    }

    public static function getRemoteCategoryId($directoryId, $localCategoryId)
    {
        $remote_category_id = false;
        $get_remote_category_id_r = tep_db_query(
            "SELECT remote_category_id ".
            "FROM ep_holbi_soap_link_categories ".
            "WHERE ep_directory_id='".(int)$directoryId."' AND local_category_id='".(int)$localCategoryId."' ".
            "LIMIT 1"
        );
        if ( tep_db_num_rows($get_remote_category_id_r)>0 ) {
            $_remote_category_id = tep_db_fetch_array($get_remote_category_id_r);
            $remote_category_id = $_remote_category_id['remote_category_id'];
        }elseif ( $localCategoryId==0 ) {
            $remote_category_id = 0;
        }
        return $remote_category_id;
    }

    public static function getLocalCategoryId($directoryId, $remoteCategoryId)
    {
        $local_category_id = false;
        $get_local_category_id_r = tep_db_query(
            "SELECT local_category_id ".
            "FROM ep_holbi_soap_link_categories ".
            "WHERE ep_directory_id='".(int)$directoryId."' AND remote_category_id='".(int)$remoteCategoryId."' ".
            "LIMIT 1"
        );
        if ( tep_db_num_rows($get_local_category_id_r)>0 ) {
            $_local_category = tep_db_fetch_array($get_local_category_id_r);
            $local_category_id = $_local_category['local_category_id'];
        }elseif ( $remoteCategoryId==0 ) {
            $local_category_id = 0;
        }
        return $local_category_id;
    }

    public static function makeExportProductStock(Products $product)
    {
        $exportArray = $product->exportArray([
            'attributes'=>['*'=>[]],
            'inventory'=>['*'=>[]],
        ]);

        $stock_info = [
            'quantity' => $exportArray['products_quantity'],
            'allocated_quantity' => $exportArray['allocated_quantity'],
            'stock_indication_id' => $exportArray['stock_indication_id'],
            'stock_indication_text' => $exportArray['stock_indication_text'],
            'stock_delivery_terms_id' => $exportArray['stock_delivery_terms_id'],
            'stock_delivery_terms_text' => $exportArray['stock_delivery_terms_text'],
        ];

        return $stock_info;
    }

    public static function makeExportProductPrices($products_id, $directoryId=null)
    {
        $price_info = [];

        static $currencies_map = false;
        if (!is_array($currencies_map)){
            $currencies = new \common\classes\Currencies();
            foreach( $currencies->currencies as $code=>$currencyInfo ) {
                $currencies_map[ $currencyInfo['id'] ] = $code;
            }
        }

        $join_tables = '';
        $filter_sql = "AND p.products_id='".(int)$products_id."' ";

        $columns =
            " p.products_price AS price, " .
            " p.products_price_discount AS discount_table, ".
            " p.products_price_full, ".
            " p.pack_unit, p.products_price_pack_unit, p.products_price_discount_pack_unit, ".
            " p.packaging, p.products_price_packaging, p.products_price_discount_packaging, ".
            " 0 AS currencies_id, "
        ;
        if (defined('USE_MARKET_PRICES') && USE_MARKET_PRICES=='True') {
            $join_tables .= "INNER JOIN ".TABLE_PRODUCTS_PRICES." pp ON pp.products_id=p.products_id AND pp.groups_id=0 ";
            $columns =
                " pp.products_group_price AS price, " .
                " pp.products_group_discount_price AS discount_table, ".
                " p.products_price_full, ".
                " p.pack_unit, pp.products_group_price_pack_unit AS products_price_pack_unit, pp.products_group_discount_price_pack_unit AS products_price_discount_pack_unit, ".
                " p.packaging, pp.products_group_price_packaging AS products_price_packaging, pp.products_group_discount_price_packaging AS products_price_discount_packaging, ".
                " pp.currencies_id, "
            ;
        }

        $main_sql =
            "SELECT p.products_id AS products_id, p.products_id AS prid, " .
            $columns.
            " IF(pa.products_id IS NULL, 0, 1) AS have_attributes ".
            " " .
            "FROM " . TABLE_PRODUCTS . " p " .
            " {$join_tables} " .
            " LEFT JOIN ".TABLE_PRODUCTS_ATTRIBUTES." pa ON pa.products_id=p.products_id ".
            "WHERE 1 {$filter_sql} " .
            "GROUP BY p.products_id ".((defined('USE_MARKET_PRICES') && USE_MARKET_PRICES=='True')?', pp.currencies_id':'')." " .
            "ORDER BY p.products_id " .
            "";

        $data_r = tep_db_query($main_sql);
        if ( tep_db_num_rows($data_r)>0 ) {
            while( $pInfo = tep_db_fetch_array($data_r) ){
                $pInfo['currency'] = DEFAULT_CURRENCY;
                if ( !empty($pInfo['currencies_id']) ) {
                    if ( isset($currencies_map[$pInfo['currencies_id']]) ) {
                        $pInfo['currency'] = $currencies_map[$pInfo['currencies_id']];
                    }else{
                        continue;
                    }
                }

                $price_info[] = static::makeExportVariantPrice($pInfo, $directoryId);
            }
        }
        return $price_info;
    }

    private static function makeExportVariantPrice($config, $directoryId)
    {
        $priceData = [];
        if ( isset($config['currencies_id']) && !empty($config['currencies_id']) ) {
            //$priceData['currency'] = $config['currencies_id'];
        }

        if ($config['products_price_full'] && ($config['have_attributes'] && (defined('PRODUCTS_INVENTORY') && PRODUCTS_INVENTORY == 'True') && strpos($config['products_id'], '{') === false)) {
            $priceData['inventory_prices'] = [
                'inventory_price' => [],
            ];
            if (defined('USE_MARKET_PRICES') && USE_MARKET_PRICES=='True') {
                $get_inventory_prices_r = tep_db_query(
                    "SELECT DISTINCT i.products_id AS products_id, i.prid AS prid, " .
                    (
                    $config['products_price_full'] ?
                        " ip.inventory_full_price AS price, " .
                        " ip.inventory_discount_full_price AS discount_table, "
                        :
                        " ip.inventory_group_price AS price, " .
                        " ip.inventory_group_discount_price AS discount_table, "
                    ) .
                    " " . (int)$config['products_price_full'] . " AS products_price_full, " .
                    " 0 AS have_attributes " .
                    "FROM " . TABLE_INVENTORY . " i " .
                    " INNER JOIN ".TABLE_INVENTORY_PRICES." ip ON ip.inventory_id=i.inventory_id AND ip.groups_id=0 AND ip.currencies_id='".(int)$config['currencies_id']."' ".
                    "WHERE i.prid='" . (int)$config['prid'] . "'"
                );
            }else{
                $get_inventory_prices_r = tep_db_query(
                    "SELECT DISTINCT i.products_id AS products_id, i.prid AS prid, " .
                    (
                    $config['products_price_full'] ?
                        " i.inventory_full_price AS price, " .
                        " i.inventory_discount_full_price AS discount_table, "
                        :
                        " i.inventory_price AS price, " .
                        " i.inventory_discount_price AS discount_table, "
                    ) .
                    " " . (int)$config['products_price_full'] . " AS products_price_full, " .
                    " 0 AS have_attributes " .
                    "FROM " . TABLE_INVENTORY . " i " .
                    "WHERE i.prid='" . (int)$config['prid'] . "'"
                );
            }
            if (tep_db_num_rows($get_inventory_prices_r)) {
                while ($_inventory_price = tep_db_fetch_array($get_inventory_prices_r)) {
                    if (!empty($_inventory_price['discount_table'])) {
                        $_inventory_price['discount_table'] = static::makeQuantityDiscountArray($_inventory_price['discount_table']);
                    }else{
                        unset($_inventory_price['discount_table']);
                    }
                    $_inventory_price['attribute_maps'] = static::makeAttributeMap($_inventory_price['products_id'], $directoryId);
                    unset($_inventory_price['products_id']);
                    $priceData['inventory_prices']['inventory_price'][] = $_inventory_price;//new InventoryPrice($_inventory_price);
                }
            }
        }

        if (isset($config['discount_table'])) {
            if (!empty($config['discount_table'])) {
                $priceData['discount_table'] = static::makeQuantityDiscountArray($config['discount_table']);
            }
            unset($config['discount_table']);
        }

        if (false && !$config['products_price_full'] && ($config['have_attributes'] || strpos($config['products_id'], '{') !== false)) {
            $tools = new Tools();
            $lang_id = \common\classes\language::defaultId();
            $priceData['attributes_prices'] = [
                'attribute_price' => [],
            ];
            $sql_where = '';
            if (strpos($config['products_id'], '{') !== false) {
                $where_attribute_pair = [];
                preg_match_all('/{(\d+)}(\d+)/', $config['products_id'], $matches);
                foreach ($matches[1] as $idx => $optId) {
                    $where_attribute_pair[$optId] = $optId . '-' . $matches[2][$idx];
                }
                $sql_where .=
                    "AND pa.options_id IN('" . implode("','", array_keys($where_attribute_pair)) . "') " .
                    "AND CONCAT(pa.options_id,'-',pa.options_values_id) IN ('" . implode("','", array_values($where_attribute_pair)) . "') ";
            }

            if (defined('USE_MARKET_PRICES') && USE_MARKET_PRICES=='True') {
                $get_attributes_r = tep_db_query(
                    "SELECT pa.products_attributes_id, " .
                    " pa.options_id AS option_id, pa.options_values_id AS option_value_id, " .
                    " pa.price_prefix, ".
                    " IFNULL(pap.attributes_group_price,0) AS options_values_price, " .
                    " IFNULL(pap.attributes_group_discount_price,'') AS discount_table " .
                    "FROM " . TABLE_PRODUCTS_ATTRIBUTES . " pa " .
                    " LEFT JOIN ".TABLE_PRODUCTS_ATTRIBUTES_PRICES." pap ON pap.products_attributes_id=pa.products_attributes_id AND pap.groups_id=0 AND pap.currencies_id='".(int)$config['currencies_id']."' ".
                    "WHERE pa.products_id='" . (int)$config['prid'] . "' " .
                    " {$sql_where}" .
                    "ORDER BY pa.options_id, pa.products_options_sort_order, pa.options_values_id"
                );
            }else{
                $get_attributes_r = tep_db_query(
                    "SELECT pa.products_attributes_id, " .
                    " pa.options_id AS option_id, pa.options_values_id AS option_value_id, " .
                    " pa.price_prefix, ".
                    " pa.options_values_price, " .
                    " pa.products_attributes_discount_price AS discount_table " .
                    "FROM " . TABLE_PRODUCTS_ATTRIBUTES . " pa " .
                    "WHERE pa.products_id='" . (int)$config['prid'] . "' " .
                    " {$sql_where}" .
                    "ORDER BY pa.options_id, pa.products_options_sort_order, pa.options_values_id"
                );
            }

            while ($_attribute = tep_db_fetch_array($get_attributes_r)) {
                $_attribute['option_name'] = $tools->get_option_name($_attribute['option_id'], $lang_id);
                $_attribute['option_value_name'] = $tools->get_option_value_name($_attribute['option_value_id'], $lang_id);
                $_attribute['price_prefix'] = ($_attribute['price_prefix'] == '-' ? $_attribute['price_prefix'] : '+');
                $_attribute['price'] = $_attribute['options_values_price'];
                $AttributePrice = [
                    'option_id' => '',
                    'option_value_id' => '',
                    'option_name' => '',
                    'option_value_name' => '',
                    'price_prefix' => '',
                    'price' => '',
                    'discount_table' => '',
                ];
                $priceData['attributes_prices']['attribute_price'][] = new AttributePrice($_attribute);
            }
        }
        if ( !empty($config['pack_unit']) || ($config['products_price_pack_unit']>0 || !empty($config['products_price_discount_pack_unit'])) ) {
            $_tmp = [
                'products_qty' => $config['pack_unit'],
                'price' => $config['products_price_pack_unit'],
                'discount_table' => empty($config['products_price_discount_pack_unit'])?null:static::makeQuantityDiscountArray($config['products_price_discount_pack_unit']),
            ];
            if ( $_tmp['price']==-2 ) $_tmp['price'] = null;

            $priceData['pack'] = $_tmp;
        }
        if ( !empty($config['packaging']) || ($config['products_price_packaging']>0 || !empty($config['products_price_discount_packaging'])) ) {
            $_tmp = [
                'pack_qty' => $config['packaging'],
                'price' => $config['products_price_packaging'],
                'discount_table' => empty($config['products_price_discount_packaging'])?null:static::makeQuantityDiscountArray($config['products_price_discount_packaging']),
            ];
            if ( $_tmp['price']==-2 ) $_tmp['price'] = null;

            $priceData['pallet'] = $_tmp;
        }

        $priceData = array_merge_recursive($priceData, $config);
        return $priceData;
    }

    private static function makeQuantityDiscountArray($discount_string)
    {
        $out = [
            'price' => [],
        ];

        $ar = preg_split("/[:;]/", rtrim($discount_string, ' ;'));

        for ($i = 0, $n = sizeof($ar); $i < $n; $i = $i + 2) {
            $out['price'][] = [
                'quantity' => (int)$ar[$i],
                'discount_price' => (float)$ar[$i + 1],
            ];
        }

        return $out;
    }

    public static function lookupLocalPropertyId($directoryId, $remotePropertyId)
    {
        static $_cached = [];
        $cache_key = (int)$directoryId.'@'.(int)$remotePropertyId;
        if ( !isset($_cached[$cache_key]) ) {
            $resultId = false;
            $getMapping_r = tep_db_query(
                "SELECT m.local_id as id, p.properties_id " .
                "FROM ep_holbi_soap_mapping m " .
                " LEFT JOIN " . TABLE_PROPERTIES . " p ON p.properties_id=m.local_id " .
                "WHERE m.ep_directory_id='" . intval($directoryId) . "' AND m.mapping_type='property' " .
                " AND m.remote_id='" . $remotePropertyId . "' " .
                "LIMIT 1"
            );
            if (tep_db_num_rows($getMapping_r) > 0) {
                $_arr = tep_db_fetch_array($getMapping_r);
                if (!is_null($_arr['properties_id'])) {
                    $resultId = $_arr['id'];
                }
            }
            $_cached[$cache_key] = $resultId;
        }
        return $_cached[$cache_key];
    }

    public static function lookupLocalOptionId($directoryId, $remoteOptionId)
    {
        $resultId = false;
        $getMapping_r = tep_db_query(
            "SELECT m.local_id as id, m.remote_id, po.products_options_id " .
            "FROM ep_holbi_soap_mapping m " .
            " LEFT JOIN ".TABLE_PRODUCTS_OPTIONS." po ON po.products_options_id=m.local_id ".
            "WHERE m.ep_directory_id='" . intval($directoryId) . "' AND m.mapping_type='attr_option' " .
            " AND m.remote_id='" . $remoteOptionId . "' ".
            "LIMIT 1"
        );
        if (tep_db_num_rows($getMapping_r) > 0) {
            $_arr = tep_db_fetch_array($getMapping_r);
            if ( !is_null($_arr['products_options_id']) ) {
                $resultId = $_arr['id'];
            }
        }
        return $resultId;
    }

    public static function lookupRemoteOptionId($directoryId, $localOptionId)
    {
        $resultId = false;
        $getMapping_r = tep_db_query(
            "SELECT m.local_id, m.remote_id as id, po.products_options_id " .
            "FROM ep_holbi_soap_mapping m " .
            " LEFT JOIN ".TABLE_PRODUCTS_OPTIONS." po ON po.products_options_id=m.local_id ".
            "WHERE m.ep_directory_id='" . intval($directoryId) . "' AND m.mapping_type='attr_option' " .
            " AND m.remote_id='" . $localOptionId . "' ".
            "LIMIT 1"
        );
        if (tep_db_num_rows($getMapping_r) > 0) {
            $_arr = tep_db_fetch_array($getMapping_r);
            if ( !is_null($_arr['products_options_id']) ) {
                $resultId = $_arr['id'];
            }
        }
        return $resultId;
    }

    public static function lookupRemoteOptionValueId($directoryId, $localOptionId, $localOptValueId)
    {
        $resultId = false;
        $getMapping_r = tep_db_query(
            "SELECT m.local_id, m.remote_id as id, pov.products_options_values_id " .
            "FROM ep_holbi_soap_mapping m " .
            " LEFT JOIN ".TABLE_PRODUCTS_OPTIONS_VALUES." pov ON pov.products_options_values_id=m.local_id ".
            "WHERE m.ep_directory_id='" . intval($directoryId) . "' AND m.mapping_type='attr_option_value' " .
            " AND m.local_id='" . (int)$localOptValueId . "' ".
            "LIMIT 1"
        );
        if ( tep_db_num_rows($getMapping_r)>0 ) {
            $_arr = tep_db_fetch_array($getMapping_r);
            if ( !is_null($_arr['products_options_values_id']) ) {
                $resultId = $_arr['id'];
            }
        }
        return $resultId;
    }

    public static function lookupLocalOptionValueId($directoryId, $localOptionId, $remoteOptValueId)
    {
        $resultId = false;
        $getMapping_r = tep_db_query(
            "SELECT m.local_id as id, m.remote_id, pov.products_options_values_id " .
            "FROM ep_holbi_soap_mapping m " .
            " LEFT JOIN ".TABLE_PRODUCTS_OPTIONS_VALUES." pov ON pov.products_options_values_id=m.local_id ".
            "WHERE m.ep_directory_id='" . intval($directoryId) . "' AND m.mapping_type='attr_option_value' " .
            " AND m.remote_id='" . (int)$remoteOptValueId . "' ".
            "LIMIT 1"
        );
        if ( tep_db_num_rows($getMapping_r)>0 ) {
            $_arr = tep_db_fetch_array($getMapping_r);
            if ( !is_null($_arr['products_options_values_id']) ) {
                $resultId = $_arr['id'];
            }
        }
        return $resultId;
    }

    public static function setOrderSapState($orderId, $new_state)
    {
        $remote_order_id = 0;
        $get_remote_order_id_r = tep_db_query(
            "SELECT remote_orders_id ".
            "FROM ep_holbi_soap_link_orders ".
            "WHERE local_orders_id='".$orderId."'"
        );
        if ( tep_db_num_rows($get_remote_order_id_r)>0 ) {
            $remote_order_id_arr = tep_db_fetch_array($get_remote_order_id_r);
            $remote_order_id = $remote_order_id_arr['remote_orders_id'];
        }
        if ( $remote_order_id ) {
            $provider = \backend\models\EP\Provider\HolbiSoap\Helper::getDatasourceForOrder($orderId);
            if (is_object($provider) && $provider instanceof \backend\models\EP\Datasource\HolbiSoap) {
                $soapClient = $provider->getClient(null);
                if ($soapClient) {
                    $soapClient->resetSapError($remote_order_id, $new_state);
                }
            }
        }
    }

    public static function getDatasourceForOrder($orderId)
    {
        $datasource = false;
        $get_directory_id_r = tep_db_query(
            "SELECT ep_directory_id ".
            "FROM ep_holbi_soap_link_orders ".
            "WHERE local_orders_id='".$orderId."'"
        );
        if ( tep_db_num_rows($get_directory_id_r)>0 ) {
            $get_directory_id = tep_db_fetch_array($get_directory_id_r);
            $directory = \backend\models\EP\Directory::findById($get_directory_id['ep_directory_id']);
            if ( $directory )  {
                $datasource = $directory->getDatasource();
            }
        }
        return $datasource;
    }

    public static function makeLanguageValueMap($languageMap)
    {
        $resultMap = [
            'language_value' => [],
        ];
        foreach ($languageMap as $languageCode=>$languageValue)
        {
            $resultMap['language_value'][] = [
                'language' => $languageCode,
                'text' => $languageValue,
            ];
        }
        return $resultMap;
    }

    public static function exportOrdersToSap( $orderIds )
    {
        $result = [
            'status' => 'error',
            'messages' => [],
        ];

        $orderIds = !is_array($orderIds)?[$orderIds]:$orderIds;
        $orderIds = array_unique(array_map('intval',$orderIds));

        if ( count($orderIds)==0 ) {
            $result['messages'][] = 'Orders not selected';
            return $result;
        }

        ob_start();
        $datasources = DataSources::getActiveByClass('HolbiSoap');
        if ( count($datasources)==1 ) {
            foreach ( $datasources as $directoryId=>$datasource ){
                $epDirectory = Directory::loadById($directoryId);
                $jobId = $epDirectory->touchImportJob('HolbiSoap_ExportToSap_'.date('YmdHis'),'configured','HolbiSoap\\ExportToSap');
                $exportOrderJob = Job::loadById($jobId);
                if ( !$exportOrderJob ) continue;
                if ( !is_array($exportOrderJob->job_configure) ) $exportOrderJob->job_configure = [];
                $exportOrderJob->job_configure['oneTimeJob'] = true;
                $exportOrderJob->job_configure['forceProcessOrders'] = $orderIds;
                $exportOrderJob->saveConfigureState();
                $exportOrderJob->setJobStartTime(time());
                $messages = new Messages([
                    'job_id' => $jobId,
                    'output' => 'db',
                ]);
                ob_start();
                try {
                    $messages->info('Run export manually');
                    $exportOrderJob->run($messages);

                    $result['status'] = 'ok';
                    $result['messages'] = $messages->getMessages();
                }catch (\Exception $ex){
                    $result['messages'][] = $ex->getMessage();
                }
                ob_end_flush();
                $exportOrderJob->jobFinished();

            }
        }
        ob_get_clean();

        return $result;

/*        return [
            'status' => 'error',
            'messages' => [
                'Not implemented yet',
            ],
        ];*/

    }

    public static function getLocalSupplierId($directoryId, $remoteId)
    {
        static $cached = [];
        $key = (int)$directoryId.'@'.(int)$remoteId;
        if ( !isset($cached[$key]) ) {
            $getLocalId_r = tep_db_query(
                "SELECT s.suppliers_id " .
                "FROM ep_holbi_soap_mapping em " .
                " LEFT JOIN suppliers s ON em.local_id=s.suppliers_id " .
                "WHERE em.ep_directory_id ='" . (int)$directoryId . "' " .
                "  AND em.remote_id='" . (int)$remoteId . "' " .
                "  AND em.mapping_type='supplier' " .
                "ORDER BY IF(s.suppliers_id IS NULL, 1, 0) " .
                "LIMIT 1"
            );
            if (tep_db_num_rows($getLocalId_r) > 0) {
                $getLocalId = tep_db_fetch_array($getLocalId_r);
                if (is_null($getLocalId['suppliers_id'])) {
                    // supplier removed
                    $cached[$key] = -1;
                } else {
                    $cached[$key] = (int)$getLocalId['suppliers_id'];
                }
            }else{
                return false;
            }
        }
        return $cached[$key];
    }

    public static function createMapLocalSupplier(\SoapClient $soapClient, $directoryId, $remoteSupplierId)
    {
        $localId = false;
        try {
            $response = $soapClient->getSupplier($remoteSupplierId);
            if ( $response && $response->supplier){
                $remoteSupplierData = json_decode(json_encode($response->supplier),true);
                $localSupplier = \common\api\models\AR\Supplier::findOne(['suppliers_name'=>$remoteSupplierData['suppliers_name']]);
                if ( $localSupplier && $localSupplier->suppliers_id ) {
                    // found by name
                    $localId = $localSupplier->suppliers_id;
                }else{
                    // make new
                    $supplierData = [
                        'suppliers_name' => $remoteSupplierData['suppliers_name'],
                    ];
                    if ( $remoteSupplierData['suppliers_surcharge_amount'] ) {
                        $supplierData['suppliers_surcharge_amount'] = $remoteSupplierData['suppliers_surcharge_amount'];
                    }
                    if ( $remoteSupplierData['suppliers_margin_percentage'] ) {
                        $supplierData['suppliers_margin_percentage'] = $remoteSupplierData['suppliers_margin_percentage'];
                    }
                    if ( $remoteSupplierData['is_default'] ) {
                        $supplierData['is_default'] = $remoteSupplierData['is_default'];
                    }
                    if ( $remoteSupplierData['price_formula'] ) {
                        $supplierData['price_formula'] = $remoteSupplierData['price_formula'];
                    }
                    if ( $remoteSupplierData['date_added'] && $remoteSupplierData['date_added']>1000 ) {
                        $supplierData['date_added'] = date('Y-m-d H:i:s', strtotime($remoteSupplierData['date_added']));
                    }
                    if ( $remoteSupplierData['last_modified'] && $remoteSupplierData['last_modified']>1000 ) {
                        $supplierData['last_modified'] = date('Y-m-d H:i:s', strtotime($remoteSupplierData['last_modified']));
                    }

                    $newSupplier = new \common\api\models\AR\Supplier();
                    $newSupplier->importArray($supplierData);
                    $newSupplier->save(false);
                    $newSupplier->refresh();
                    $localId = $newSupplier->suppliers_id;
                }
                if ( $localId ) {
                    tep_db_perform('ep_holbi_soap_mapping',[
                        'ep_directory_id' => $directoryId,
                        'mapping_type'=>'supplier',
                        'remote_id' => (int)$remoteSupplierId,
                        'local_id' => $localId,
                    ]);
                }
            }
        }catch (\Exception $ex){

        }
        return $localId;
    }

    public static function makeAttributeMap($uprid, $directoryId = null)
    {
        $attribute_map = [];
        preg_match_all('/{(\d+)}(\d+)/', $uprid, $_attr);
        foreach ($_attr[1] as $idx => $optId) {
            $exportAttribute = [
                'options_name' => Tools::getInstance()->get_option_name($optId, \common\helpers\Language::get_default_language_id()),
                'options_values_name' => Tools::getInstance()->get_option_value_name($_attr[2][$idx], \common\helpers\Language::get_default_language_id()),
            ];
            $options_id = \backend\models\EP\Provider\HolbiSoap\Helper::lookupRemoteOptionId((int)$directoryId, $optId);
            if ($options_id !== false) {
                $exportAttribute['options_id'] = $options_id;
                $options_values_id = \backend\models\EP\Provider\HolbiSoap\Helper::lookupRemoteOptionValueId((int)$directoryId, $optId, $_attr[2][$idx]);
                if ($options_values_id !== false) {
                    $exportAttribute['options_values_id'] = $options_values_id;
                }
            }
            $attribute_map[] = $exportAttribute;
        }
        return $attribute_map;
    }

}