<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models\EP\Provider\NetSuite;


use backend\models\EP\DatasourceBase;
use backend\models\EP\DataSources;
use backend\models\EP\Directory;
use backend\models\EP\Job;
use backend\models\EP\Messages;
use common\api\models\AR\Products;
use common\api\models\AR\Warehouses;
use common\api\models\AR\Group;
use common\api\models\AR\Customer;

use backend\models\EP\Tools;
use common\classes\Order;

use NetSuite\Classes\Customer as NsCustomer;
use NetSuite\Classes\RecordRef;
use NetSuite\Classes\AddRequest;
use NetSuite\Classes\UpdateRequest;

class Helper
{
  const TAX_CLASS_ID = 3;
  public static $current_product = []; /// :( need current product details but don't want to move helper functions to download class.
  public static $config = [];
  /**
   *                        [name] => Advertising Preferences
                            [owner] =>
                            [isOrdered] =>
                            [description] =>
                            [isMatrixOption] =>
                            [scriptId] => customlist2
                            [convertToCustomRecord] =>
                            [isInactive] =>
[customValueList] => NetSuite\Classes\CustomListCustomValueList Object
                (
                    [customValue] => Array
                        (
                            [0] => NetSuite\Classes\CustomListCustomValue Object
                                (
                                    [value] => A
                                    [abbreviation] =>
                                    [isInactive] =>
                                    [valueId] => 1
                                    [valueLanguageValueList] =>
                                )
                        )
                    [replaceAll] => 1
                )

            [translationsList] => NetSuite\Classes\CustomListTranslationsList Object
                (
                    [translations] => Array
                        (
                            [0] => NetSuite\Classes\CustomListTranslations Object
                                (
                                    [locale] => _german
                                    [localeDescription] => German
                                    [name] =>
                                )


                        )

                    [replaceAll] => 1
                )

                            [internalId] => 2
                            [nullFieldList] =>
   * @var array of records
   */
  public static $customFields = []; /// anti-pattern? tbc
  
  /**
   * customers groups (price levels)
   * @var array
   */
  public static $nsGroups = [];
  public static $nsCategories = [];
  public static $nsCurrencies = [];
  public static $nsSuppliers = [];
  public static $nsLocations = [];
  
  public static function anyConfigured() {
    $d = tep_db_fetch_array(tep_db_query("select ld.directory_id  "
        . " from ep_directories ld "
        . " where ld.directory_config like '%NetSuiteLink%'  and ld.directory_type='datasource' "
        . " " ));
    return ($d?$d['directory_id']:false);
  }

  public static function getCountriesList() {
    $refl = new \ReflectionClass('\NetSuite\Classes\Country');
    $ret = $refl->getConstants();
    foreach ($ret as $k => $v) {
      $ret[$k] = ltrim($v, '_');
    }
    return $ret;
  }
  
  public static function buildTree() {
    if (is_array(self::$nsCategories) && count(self::$nsCategories)) {
      foreach (self::$nsCategories as $key => $value) {
        ///$key == internalId

        if (!empty($value['internalId'])) {
          self::$nsCategories[$key]['id'] = $value['internalId'];
        }
        if (isset($value['name'])) {
          self::$nsCategories[$key]['text'] = $value['name'];
        }

        if (!empty($value['parent']['internalId'])) {
          $parents = self::parentCategory($value);
          if (is_array($parents)) {
            self::$nsCategories[$key]['categories_path_array'] = $parents;
            self::$nsCategories[$key]['categories_path'] = '';
            foreach ($parents as $v) {
              if (!isset($v['name'])) {
                break;
              }
              self::$nsCategories[$key]['categories_path'] .= ';' . $v['name'];
            }
            self::$nsCategories[$key]['categories_path'] = substr(self::$nsCategories[$key]['categories_path'], 1);
          }
        }

      }
      //make it smaller
      foreach (self::$nsCategories as $key => $value) {
        ///$key == internalId
        if (isset($value['categories_path_array'])) {
          foreach ($value['categories_path_array'] as $kk => $vv) {
            unset(self::$nsCategories[$key]['categories_path_array'][$kk]['categories_path_array']);
          }
        }
      }

    }
  }

  public static function parentCategory($value) {
    static $level=0;

    if ($level>100) {// incorrect structure
      return false;
    }

    $ret = [];

    if (!empty($value['parent']['internalId'])) {
      $level++;
      $ret = self::parentCategory(self::$nsCategories[$value['parent']['internalId']]);
      $level--;
    }
    if (!is_array($ret)) {
      return false;
    }

    unset($value['categories_path']);
    $ret[] = $value;

    return $ret;
  }
/**
 * search for $directoryId/$keyName in kv storage. Returns value or null
 * @param int $directoryId
 * @param varchar(64) $keyName
 * @return varchar(1024)
 */
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

    /**
     * returns array of values (for HTML select)
     * @param int $directoryId
     * @param string $keyPrefix
     * @return array
     */
    public static function getKVArray($directoryId, $keyPrefix)
    {
      $ret = [];
      $get_value_r = tep_db_query("SELECT key_value, key_name FROM ep_holbi_soap_kv_storage WHERE ep_directory_id='" . (int)$directoryId . "' AND key_name like '" . tep_db_input($keyPrefix) . "%' order by key_name");
      while ($_value = tep_db_fetch_array($get_value_r)) {
        $ret[str_replace($keyPrefix, '', $_value['key_name'])] = trim($_value['key_value']);
      }
      return $ret;
    }

    public static function setKeyValue($directoryId, $keyName, $value)
    {
        tep_db_query(
            "INSERT INTO ep_holbi_soap_kv_storage (ep_directory_id, key_name, key_value) ".
            "VALUES ('".(int)$directoryId."', '".tep_db_input($keyName)."', '".tep_db_input($value)."')".
            "ON DUPLICATE KEY UPDATE key_value='".tep_db_input($value)."' "
        );
    }

    protected static function lookupCustomersGroupId($remoteId)
    {
      static $mapping = [];
        if ( !isset($mapping[$remoteId]) ) {
          $get_local_id_r = tep_db_query(
              "SELECT local_id ".
              "FROM ep_holbi_soap_mapping ".
              "WHERE ep_directory_id='".(int)$this->config['directoryId']."' ".
              " AND remote_id='".$remoteId."' AND mapping_type='groups'"
          );
          if ( tep_db_num_rows($get_local_id_r)>0 ) {
              $_local_id = tep_db_fetch_array($get_local_id_r);
              tep_db_free_result($get_local_id_r);
              $mapping[$remoteId] = $_local_id['local_id'];
              return $_local_id['local_id'];
          }
          return false;
        }
        return intval($mapping[$remoteId]);
    }

    protected static function lookupProductsId($remoteId)
    {
      static $mapping = [];
        if ( !isset($mapping[$remoteId]) ) {
          $get_local_id_r = tep_db_query(
              "SELECT local_products_id ".
              "FROM ep_holbi_soap_link_products ".
              "WHERE ep_directory_id='".(int)self::$config['directoryId']."' ".
              " AND remote_products_id='".$remoteId."'"
          );
          if ( tep_db_num_rows($get_local_id_r)>0 ) {
              $_local_id = tep_db_fetch_array($get_local_id_r);
              tep_db_free_result($get_local_id_r);
              $mapping[$remoteId] = $_local_id['local_id'];
              return $_local_id['local_id'];
          }
          return false;
        }
        return intval($mapping[$remoteId]);
    }



    public static function getWarehousesMap()
    {
      $ret = [];
      $get_local_id_r = tep_db_query(
          "SELECT local_id, remote_id ".
          "FROM ep_holbi_soap_mapping ".
          "WHERE ep_directory_id='".(int)self::$config['directoryId']."' ".
          "  AND mapping_type='warehouses'"
      );
      if ( tep_db_num_rows($get_local_id_r)>0 ) {
        while ($l  = tep_db_fetch_array($get_local_id_r)) {
          $ret[$l['remote_id']] = $l['local_id'];
        }
      }
      tep_db_free_result($get_local_id_r);
      return $ret;
    }
    
    public static function getGroupsMap()
    {
      $ret = [];
      $get_local_id_r = tep_db_query(
          "SELECT local_id, remote_id ".
          "FROM ep_holbi_soap_mapping ".
          "WHERE ep_directory_id='".self::$config['directoryId']."' ".
          "  AND mapping_type='groups'"
      );
      if ( tep_db_num_rows($get_local_id_r)>0 ) {
        while ($l  = tep_db_fetch_array($get_local_id_r)) {
          $ret[$l['remote_id']] = $l['local_id'];
        }
      }
      tep_db_free_result($get_local_id_r);
      return $ret;
    }

    public static function getCategoriesMap()
    {
      $ret = [];
      $get_local_id_r = tep_db_query(
          "SELECT m.local_id, UPPER(c.code) as code, m.remote_id, c.value ".
          "FROM ep_holbi_soap_mapping m left join " . TABLE_CURRENCIES . " c on m.local_id=c.currencies_id " .
          "WHERE ep_directory_id='".(int)self::$config['directoryId']."' ".
          "  AND mapping_type='currencies'"
      );
      if ( tep_db_num_rows($get_local_id_r)>0 ) {
        while ($l  = tep_db_fetch_array($get_local_id_r)) {
          $ret[$l['remote_id']] = $l;
        }
      }
      tep_db_free_result($get_local_id_r);
      return $ret;
    }

    public static function getCurrenciesMap()
    {
      $ret = [];
      $get_local_id_r = tep_db_query(
          "SELECT m.local_id, UPPER(c.code) as code, m.remote_id, c.value ".
          "FROM ep_holbi_soap_mapping m left join " . TABLE_CURRENCIES . " c on m.local_id=c.currencies_id " .
          "WHERE ep_directory_id='".(int)self::$config['directoryId']."' ".
          "  AND mapping_type='currencies'"
      );
      if ( tep_db_num_rows($get_local_id_r)>0 ) {
        while ($l  = tep_db_fetch_array($get_local_id_r)) {
          $ret[$l['remote_id']] = $l;
        }
      }
      tep_db_free_result($get_local_id_r);
      return $ret;
    }

    /**
     * search for NS currency Id by iso2 code in local DB (re-download currencies if not found)
     * @staticvar array $map // cache values -not to much help in speedup, few extra memory
     * @param string $iso2 code of currency optional (default currency)
     * @return int NS currency Id
     */
    public static function getNsCurrency($iso2='')
    {
      static $map = [];
      $ret = 0;

      $iso2 = trim($iso2);
      if ($iso2=='') {
        $iso2 = DEFAULT_CURRENCY;
      }
      $iso2 = strtoupper($iso2);
      if (empty($map[$iso2])) {
        if (empty(self::$nsCurrencies)) {
          self::$nsCurrencies = self::getCurrenciesMap();
        }
        if (is_array(self::$nsCurrencies)) {
          foreach (self::$nsCurrencies as $id => $currency) {
            if ($iso2 == $currency['code']) {
              $ret = $map[$iso2] = $id;
              break;
            }
          }
        }
      } else {
        $ret = $map[$iso2];
      }
      return $ret;
    }

    public static function getSuppliersMap()
    {
      $ret = [];
      $get_local_id_r = tep_db_query(
          "SELECT m.local_id, UPPER(c.code) as code, m.remote_id, c.value ".
          "FROM ep_holbi_soap_mapping m left join " . TABLE_CURRENCIES . " c on m.local_id=c.currencies_id " .
          "WHERE ep_directory_id='".(int)self::$config['directoryId']."' ".
          "  AND mapping_type='suppliers'"
      );
      if ( tep_db_num_rows($get_local_id_r)>0 ) {
        while ($l  = tep_db_fetch_array($get_local_id_r)) {
          $ret[$l['remote_id']] = $l;
        }
      }
      tep_db_free_result($get_local_id_r);
      return $ret;
    }

    public static function syncOrderStatuses(\NetSuite\NetSuiteService &$soapClient, DatasourceBase $datasource)
    {
      return false; // impossible
    }

    public static function putOrderStatusesOnServer(\NetSuite\NetSuiteService &$soapClient, $config)
    {
      //impossible??
        return TRUE;
    }

    public static function getOrderStatusesFromServer(\NetSuite\NetSuiteService &$soapClient, $processCreateRequest=false)
    {
        $serverStatuses = [];
        try {

            $response = self::getOrderStatuses($soapClient);

            if ( $response && $response->status=='OK' ) {
                $order_statuses = $response->statuses->order_status;
                if ( is_object($order_statuses) ) $order_statuses = [$order_statuses];
//echo "#### <PRE>" .print_r($order_statuses, 1) ."</PRE>";die;

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

    public static function makeExportProductPrices($products_id)
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

                $price_info[] = static::makeExportVariantPrice($pInfo);
            }
        }
        return $price_info;
    }

    private static function makeExportVariantPrice($config)
    {
        $priceData = [];
        if ( isset($config['currencies_id']) && !empty($config['currencies_id']) ) {
            //$priceData['currency'] = $config['currencies_id'];
        }

        if (false && $config['products_price_full'] && ($config['have_attributes'] && (defined('PRODUCTS_INVENTORY') && PRODUCTS_INVENTORY == 'True') && strpos($config['products_id'], '{') === false)) {
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
                    $priceData['inventory_prices']['inventory_price'][] = new InventoryPrice($_inventory_price);
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
                $matches = [];
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

////////////////////////
  /**
   *
   * @param \backend\models\EP\Provider\NetSuite\NetSuite\NetSuiteService &$soapClient
   * @param string $recordType
   * @param array $conditions
   * ['fieldName' => ['fieldType'=> 'String', 'operator' => 'startsWith', 'value' => ''], []]
   * @return array of records
   */
  public static function basicSearch (\NetSuite\NetSuiteService &$soapClient, $recordType, $conditions = [], $page=0) {
    $ret = [];
    $search = false;
    //$soapClient->setSearchPreferences(true,1000); //if required Debug test - seems too small (2) doesn't work
    // cd [....]/ns-php/src/Classes && ls |grep earchBasic | sed "s/\.php//g" | tr "\n" " "

    //AccountingPeriodSearchBasic AccountingTransactionSearchBasic AccountSearchBasic AddressSearchBasic AssemblyItemBomSearchBasic BillingAccountSearchBasic BillingScheduleSearchBasic BinSearchBasic BudgetSearchBasic CalendarEventSearchBasic CampaignSearchBasic ChargeSearchBasic ClassificationSearchBasic ConsolidatedExchangeRateSearchBasic ContactCategorySearchBasic ContactRoleSearchBasic ContactSearchBasic CostCategorySearchBasic CouponCodeSearchBasic CurrencyRateSearchBasic CustomerCategorySearchBasic CustomerMessageSearchBasic CustomerSearchBasic CustomerStatusSearchBasic CustomListSearchBasic CustomRecordSearchBasic DepartmentSearchBasic EmployeeSearchBasic EntityGroupSearchBasic EntitySearchBasic ExpenseCategorySearchBasic FairValuePriceSearchBasic FileSearchBasic FolderSearchBasic GiftCertificateSearchBasic GlobalAccountMappingSearchBasic GroupMemberSearchBasic HcmJobSearchBasic InboundShipmentSearchBasic InventoryDetailSearchBasic InventoryNumberBinSearchBasic InventoryNumberSearchBasic IssueSearchBasic ItemAccountMappingSearchBasic ItemBinNumberSearchBasic ItemDemandPlanSearchBasic ItemRevisionSearchBasic ItemSearchBasic ItemSupplyPlanSearchBasic JobSearchBasic JobStatusSearchBasic JobTypeSearchBasic LocationSearchBasic ManufacturingCostTemplateSearchBasic ManufacturingOperationTaskSearchBasic ManufacturingRoutingSearchBasic MessageSearchBasic MseSubsidiarySearchBasic NexusSearchBasic NoteSearchBasic NoteTypeSearchBasic OpportunitySearchBasic OriginatingLeadSearchBasic OtherNameCategorySearchBasic PartnerCategorySearchBasic PartnerSearchBasic PaycheckSearchBasic PaymentMethodSearchBasic PayrollItemSearchBasic PhoneCallSearchBasic PriceLevelSearchBasic PricingGroupSearchBasic PricingSearchBasic ProjectTaskAssignmentSearchBasic ProjectTaskSearchBasic PromotionCodeSearchBasic ResourceAllocationSearchBasic RevRecScheduleSearchBasic RevRecTemplateSearchBasic SalesRoleSearchBasic SalesTaxItemSearchBasic SiteCategorySearchBasic SolutionSearchBasic SubsidiarySearchBasic SupportCaseSearchBasic TaskSearchBasic TaxDetailSearchBasic TaxGroupSearchBasic TaxTypeSearchBasic TermSearchBasic TimeBillSearchBasic TimeEntrySearchBasic TimeSheetSearchBasic TopicSearchBasic TransactionSearchBasic UnitsTypeSearchBasic UsageSearchBasic VendorCategorySearchBasic VendorSearchBasic WinLossReasonSearchBasic

    $search = self::objectByRecordType($recordType);

    if ($search) {
      try {

        $search = new $search;

        if (is_array($conditions) && count($conditions)>0) {
          foreach ($conditions as $field => $condition) {
            // validate field and operator.
            if (!empty($condition['value'])) {
              if (!empty($condition['fieldType']) &&
                    (
                    class_exists('\\NetSuite\\Classes\\Search' . ucfirst($condition['fieldType']) . 'Field') ||
                    class_exists('\\NetSuite\\Classes\\' . $condition['fieldType'] . '')
                    )
                  ) {
                $sf = '\\NetSuite\\Classes\\Search' . ucfirst($condition['fieldType']) . 'Field';
                $searchField = new $sf;
              } else {
                $searchField = new \NetSuite\Classes\SearchStringField();
              }
              $operatorsClass = '\\NetSuite\\Classes\\' . get_class($searchField) . 'Operator';
              /*vl2do
              if (!empty($condition['operator']) && defined($operatorsClass . '::' . $condition['operator'])) {
                $searchField->operator = $condition['operator'];
              } elseif (empty($condition['operator'])) {
                $searchField->operator = "startsWith";
              }*/
              if (empty($condition['operator'])) {
                $searchField->operator = "startsWith";
              } else {
                $searchField->operator = $condition['operator'];
              }


              $searchField->searchValue = $condition['value'];
              $search->$field = $searchField;
            }
          }
        }


        if ($page>0) {
          $request = new \NetSuite\Classes\SearchNextRequest();
          $request->searchRecord = $search;
          $searchResponse = $soapClient->searchNext($request);
        } else {
          $request = new \NetSuite\Classes\SearchRequest();
          $request->searchRecord = $search;
          $searchResponse = $soapClient->search($request);
        }

        if (!$searchResponse->searchResult->status->isSuccess) {
            //echo "SEARCH ERROR";
        } else {
          $ret = $searchResponse->searchResult;
/*
$ret->
        status
        totalRecords
        pageSize
        totalPages
        pageIndex
        recordList Record[]
  */

        }
      }catch (\Exception $ex){
//echo "\n search error ". $ex->getMessage();
          $result['messages'][] = $ex->getMessage();
      }
    }
    return $ret;
  }
  
  public static function objectByRecordType($recordType, $operation = 'SearchBasic') {
    $sc = '\\NetSuite\\Classes\\';
    $search = false;
    if (class_exists($sc . $recordType)) {
      $sc .= $recordType;
      $search = $sc;
    } else {
      switch ($recordType) {
        case 'customer':
          $sc .= 'Customer';
          break;
        case 'admin':
          $sc .= 'Employee';
          break;
        case 'address':
          $sc .= 'Address';
          break;
        case 'product':
          $sc .= 'Item';
          break;
        case 'warehouse':
          $sc .= 'Location';
          break;
        case 'payment':
          $sc .= 'PaymentMethod';
          break;
        case 'group':
          $sc .= 'PriceLevel';
          break;
        case 'tax':
          $sc .= 'SalesTaxItem';
          break;
        case 'platform':
          $sc .= 'Subsidiary';
          break;
        case 'CustomList':
          $sc .= 'CustomList';
          break;
        case 'supplier':
          $sc .= 'Vendor';
          break;
        case 'category':
          $sc .= 'Classification';
          break;
        default:
          break;
      }
      if (class_exists($sc . $operation)) {
        $search = $sc . $operation;
      } else {
//echo "\n " . ($sc . $operation) ." class not exists";
      }
    }
    //echo "\n $search class exists";

    return $search;
  }

  /**
   * Search record by id(s)
   * @param \NetSuite\NetSuiteService &$soapClient
   * @param type $recordType
   * @param type $paramms
   */

  public static function get(\NetSuite\NetSuiteService &$soapClient, $recordType, $nsId) {
    $ret = [];
    try {
      $request = new \NetSuite\Classes\GetRequest();
      $request->baseRef = new \NetSuite\Classes\RecordRef();
      $request->baseRef->internalId = $nsId;
      $request->baseRef->type = $recordType;

      $getResponse = $soapClient->get($request);

      

      if (!$getResponse->readResponse->status->isSuccess) { ////readResponseList;
          //echo "SEARCH ERROR";
      } else {
/*
status Status
recordList Record[] 
*/
        $ret = $getResponse->readResponse;
      }
    }catch (\Exception $ex){
      echo $ex->getMessage();
        $result['messages'][] = $ex->getMessage();
    }
    return $ret;
  }
  /**
   * Search records by id(s)
   * @param \NetSuite\NetSuiteService &$soapClient
   * @param type $recordType
   * @param type $paramms
   */

  public static function getList(\NetSuite\NetSuiteService &$soapClient, $params) {
    $ret = [];
    try {
      $request = new \NetSuite\Classes\GetListRequest();
      $request->baseRef = $params;

      $getListResponse = $soapClient->getList($request);

      if (!$getListResponse->readResponseList->status->isSuccess) { ////readResponseList;
          //echo "SEARCH ERROR";
      } else {
/*
status Status
recordList Record[]
*/
        $ret = $getListResponse->readResponseList;
      }
    }catch (\Exception $ex){
      echo $ex->getMessage();
        $result['messages'][] = $ex->getMessage();
    }
    return $ret;
  }

  /**
   * All records
   * @param \NetSuite\NetSuiteService &$soapClient
   * @param type $recordType
   * @param type $paramms
   */

  public static function getAll(\NetSuite\NetSuiteService &$soapClient, $recordType) {
    $ret = [];
    try {
      $request = new \NetSuite\Classes\GetAllRequest();
      $request->record = new \NetSuite\Classes\GetAllRecord();
      $request->record->recordType = $recordType;

      $getListResponse = $soapClient->getAll($request);

      if (!$getListResponse->getAllResult->status->isSuccess) { ////readResponseList;
          //echo "SEARCH ERROR";
      } else {
/*
status Status
recordList Record[]
*/
        $ret = $getListResponse->getAllResult;
      }
    }catch (\Exception $ex){
      echo $ex->getMessage();
        $result['messages'][] = $ex->getMessage();
    }
    return $ret;
  }

  /**
   * 
   * @param \NetSuite\NetSuiteService $soapClient
   * @param type $recordType
   * @param type $paramms
   */
  public static function getCustomizationId(\NetSuite\NetSuiteService &$soapClient , $recordType='itemCustomField') {
    $ret = [];
    try {
      $ct = new \NetSuite\Classes\CustomizationType();
      $ct->getCustomizationType = $recordType; //\NetSuite\Classes\GetCustomizationType.itemCustomField;

      $request = new \NetSuite\Classes\GetCustomizationIdRequest();
      $request->customizationType = $ct;
      $request->includeInactives = false;

      $getCustomizationIdResponse = $soapClient->getCustomizationId($request);



      if (!$getCustomizationIdResponse->getCustomizationIdResult->status->isSuccess) { ////readResponseList;
          //echo "SEARCH ERROR";
      } else {
/*
status Status
totalRecords int
customizationRefList Record[]
*/
        $ret = $getCustomizationIdResponse->getCustomizationIdResult;
      }
    }catch (\Exception $ex){
//echo $ex->getMessage();
        $result['messages'][] = $ex->getMessage();
    }
    return $ret;

  }
  /**
   * insert/update record
   * @param \NetSuite\NetSuiteService &$soapClient
   * @param type $recordType
   * @param type $paramms
   */

  public static function upsert(\NetSuite\NetSuiteService &$soapClient, $recordType, $paramms = []) {

  }



  public static function getOrderStatuses (\NetSuite\NetSuiteService &$soapClient) {
    $response = new \stdClass();
    try {
      $refl = new \ReflectionClass('\\NetSuite\\Classes\\SalesOrderOrderStatus');
      $response->status='OK';
      $response->statuses = new \stdClass();
      $lng = \common\classes\language::get_code();
      if (is_array($refl->getConstants())) {
        foreach ($refl->getConstants() as $k => $v) {
          $response->statuses->order_status[$k] = (object)[
              'id' => $k,
              'names' => (object)['language_value' => [(object)['language' => $lng, 'text' => $v ]] ],
 /*             'group_id' => -1,
              'group_names' => (object)['language_value' => [(object)['language' => $lng, 'text' => '--' ]] ],*/
            ];
        }
      }
      
      
    } catch (\Exception $ex) {
    }
    return $response;
  }

 /**
 * look up NS country const by iso 2
 * @static var array $mapping 
 * @param string $iso2 code of country
 * @return string
 */
  public static function lookupNSCountry($iso2)
  {
    static $mapping = [];
    if ( !isset($mapping[$iso2]) ) {
      $mapping[$iso2] = self::getKeyValue(self::$config['directoryId'], $iso2);
    }
    return $mapping[$iso2];
  }
  
  public static function applyMap(&$mapRL, &$src, &$dst) {
    $dstKey = $fname = ''; $params = [];
    foreach ($mapRL as $key => $a) {
      if (isset($src[$key]) && is_array($a)) {
        try {

          if (count($a)>1 && (is_callable(__NAMESPACE__ . '\\Helper::' . $a[1]) || is_callable($a[1])) ) {

            $dstKey = array_shift($a);
            $fname = array_shift($a);
            $tmp = '';

            if (is_array($a) && count($a)>0) {
              $params = array_merge([$src[$key]], $a);
            } else {
              $params = [$src[$key]];
            }

            if (is_callable(__NAMESPACE__ . '\\Helper::' . $fname)) {
              $tmp = call_user_func_array(__NAMESPACE__ . '\\Helper::' . $fname, $params);

            } else {
              $tmp = call_user_func_array($fname, $params);

            }

            if ($dstKey == -1) {
              $dst = array_merge_recursive($tmp, $dst);
              self::fixRecursiveLang($dst);
//VL2do fix language arrays
            } elseif (empty($dst[$dstKey])) {
              $dst[$dstKey] = $tmp;
            }

          } elseif (empty($dst[$a[0]])) {
            $dst[$a[0]] = $src[$key];

          }
        } catch (\Exception $ex){
          echo $ex->getMessage() . " \$key = $key; \$dstKey $dstKey \$fname $fname params=" . print_r($params,1) . "val=" . $src[$key] . "\n";
        }
      }
    }
  }

/**
 * [language] => Array
 *    (
 *        [0] => ua
 *        [1] => ua
 *        [2] => ua
 *    )
 * [language] => ua
 * @param array $data
 */
  public static function fixRecursiveLang(&$data) {
    foreach ($data as $k => $v) {
      if ($k == 'language') {
        if (is_array($v) ) {
          $res = array_unique($v);
          if (count($res)==1 && strlen($res[0])==2 ) {
            $data[$k] = $res[0]; // 2check - indexed etc??
          } else {
            self::fixRecursiveLang($data[$k]);
          }
        }
      } elseif (is_array($v)) {
        self::fixRecursiveLang($data[$k]);
      }
    }
    
  }

  public static function _Not($v) {
    return !$v;
  }

  public static function getDateTime($v) {
    return date('Y-m-d H:i:s', strtotime($v));
  }

  public static function getManufacturersId($v) {
    return \common\helpers\Manufacturers::findManufacturerByName($v);
  }

  public static function getWeightDetails($v, $unit='') {
    $ret = [];
    if ($unit=='') {
      $unit = self::$current_product['weightUnit'];
    }

    if ($unit=='_lb' ) { //LBS
      $ret['weight_in'] = $v;
      $ret['products_weight'] = $ret['weight_cm'] = $v * 0.45359237;
    } elseif ($unit=='_kg' ) {
      $ret['products_weight'] = $ret['weight_cm'] = $v;
      $ret['weight_in'] = $v / 0.45359237;
    } elseif ($unit=='_oz' ) { 
      $ret['weight_in'] = $v;
      $ret['products_weight'] = $ret['weight_cm'] = $v * 28.3495;
    } else { //eif ($unit=='_g' )
      $ret['products_weight'] = $ret['weight_cm'] = $v;
      $ret['weight_in'] = $v / 453.59237;
    }
    return $ret;
  }

  public static function getDescriptions($v, $to, $langCode='') {

    $ret = [];
    if ($langCode == '') {
      //$langCode = \common\helpers\Language::get_language_code(DEFAULT_LANGUAGE);
      $langCode = \common\classes\language::get_code();
    }
    if (is_array($v) ) { //translations
      foreach ($v['translation'] as $d) {
        // arrrays !!! //[translationsList->translation] => Array([0] => NetSuite\Classes\Translation Object([locale] => _german [language] => German [displayName] => /*[description] => */ [salesDescription] => [storeDisplayName] => [storeDescription] => [storeDetailedDescription] => [featuredDescription] => [specialsDescription] => [pageTitle] => [noPriceMessage] => [outOfStockMessage] =>
          $langCode = \common\classes\language::get_code(strtolower(substr($d['locale'],1)));

          $ret[$langCode . '_0']['language'] = $langCode;

          $ret[$langCode . '_0']['products_name'] = $d['displayName'];
          if (!empty($d['storeDisplayName'])) {
            $ret[$langCode . '_0']['products_name'] = $d['storeDisplayName'];
          }
          if (!empty($d['description'])) {
            $ret[$langCode . '_0']['products_description_short'] = $d['description'];
          }
          if (!empty($d['storeDescription']) && empty($ret[$langCode . '_0']['products_description_short'])) {
            $ret[$langCode . '_0']['products_description_short'] = $d['storeDescription'];
          }
          if (!empty($d['salesDescription']) && empty($ret[$langCode . '_0']['products_description_short'])) {
            $ret[$langCode . '_0']['products_description_short'] = $d['salesDescription'];
          }
          if (!empty($d['storeDetailedDescription'])) {
              $ret[$langCode . '_0']['products_description'] = $d['storeDetailedDescription'];
          }
          if (!empty($d['featuredDescription'])) {
            $ret[$langCode . '_0']['products_description'] = $d['featuredDescription'];
          }
          if (!empty($d['specialsDescription'])) {
            $ret[$langCode . '_0']['products_description'] = $d['specialsDescription'];
          }
          if (!empty($d['pageTitle'])) {
            $ret[$langCode . '_0']['products_head_title_tag'] = $d['pageTitle'];
          }
        }

    } else {
      ///["descriptions"]=>  array(4) {    ["nl_0"]=>    array(20) {      ["products_name"]=>
      $ret[$langCode . '_0']['language'] = $langCode;
      $ret[$langCode . '_0'][$to] = $v;
    }

    return ['descriptions'=> $ret];
  }



  public static function getSetProducts($v) {
    $ret = [];
/*
          [itemMember] => Array
                (
                    [0] => NetSuite\Classes\ItemMember Object
                        (
                            [memberDescr] =>
                            [componentYield] =>
                            [bomQuantity] =>
                            [itemSource] =>
                            [quantity] => 1
                            [memberUnit] =>
                            [vsoeDeferral] =>
                            [vsoePermitDiscount] =>
                            [vsoeDelivered] =>
                            [taxSchedule] =>
                            [taxcode] =>
                            [item] => NetSuite\Classes\RecordRef Object
                                (
                                    [internalId] => 78
                                    [externalId] =>
                                    [type] =>
                                    [name] => Motherboard - Impressivo 1700
                                )

                            [taxrate] =>
                            [effectiveDate] =>
                            [obsoleteDate] =>
                            [effectiveRevision] =>
                            [obsoleteRevision] =>
                            [lineNumber] => 1
                            [memberKey] => 10
                        )

 */
    if (isset($v['itemMember']) && is_array($v['itemMember'])) {
      foreach ($v['itemMember'] as $item) {
        $tmp = self::lookupProductsId($item['item']['internalId']);
        if ($tmp>0) {
          $prod = [];
          $prod['product_id'] = $tmp;
          $prod['num_product'] = $item['quantity'];
          $prod['sort_order'] = $item['lineNumber'];
          $ret[] = $prod;
        } else {
          /* empty bundle instead of partial
           * ret = []; break;
           */

        }

      }
    }
    return $ret;
  }

  public static function getStock($v, $inv=false) {
    $ret = [];
 /*   [locations] => Array(
                    [0] => Array(
                            [location] => 1
                            [quantityOnHand] =>
                            [onHandValueMli] => [averageCostMli] => [lastPurchasePriceMli] =>
                            [reorderPoint] =>
                            [locationAllowStorePickup] =>
  [locationStorePickupBufferStock] =>
  [locationQtyAvailForStorePickup] =>
                            [preferredStockLevel] =>
                            [leadTime] =>
                            [defaultReturnCost] => [safetyStockLevel] => [cost] => [inventoryCostTemplate] =>
                            [buildTime] =>
                            [lastInvtCountDate] => [nextInvtCountDate] =>
                            [isWip] =>
                            [invtCountInterval] => [invtClassification] => [costingLotSize] =>
                            [quantityOnOrder] =>
                            [quantityCommitted] =>
                            [quantityAvailable] => 0
                            [quantityBackOrdered] =>
                            [locationId] => Array(
                                    [internalId] => 1
                                    [externalId] => [type] => [name] => )
                            [supplyReplenishmentMethod] => [alternateDemandSourceItem] =>
                            [fixedLotSize] =>
                            [periodicLotSizeType] => [periodicLotSizeDays] =>
                            [supplyType] =>
                            [supplyLotSizingMethod] =>
                            [demandSource] =>
                            [backwardConsumptionDays] => [forwardConsumptionDays] => [demandTimeFence] => [supplyTimeFence] =>
                            [rescheduleInDays] =>
                            [rescheduleOutDays] =>
                        )*/
    if (isset($v['locations']) && is_array($v['locations'])) {
      $defWarehouse = false;
      if (isset(self::$current_product['preferredLocation']['internalId'])) {
        $defWarehouse = self::$nsLocations[self::$current_product['preferredLocation']['internalId']];
      }

      foreach ($v['locations'] as $item) {
        $tmp = self::$nsLocations[$item['locationId']['internalId']];
        if ($tmp>0) {
          $prod = [];
          $prod['warehouse_id'] = $tmp;
          if (!$defWarehouse  || $defWarehouse == $tmp) {
            $defWarehouse = $tmp;
            
            $prod['products_quantity'] = $item['quantityAvailable'];
            
            $ret['products_quantity'] = $item['quantityAvailable'];
            $ret['warehouse_stock_quantity'] = $item['quantityAvailable'];
          }
          $prod['warehouse_stock_quantity'] = $item['quantityAvailable'];
          $ret['warehouses_products'][$tmp] = $prod;
        } else {
// message?
        }

      }
    }
    return $ret;

  }
  
  public static function getAttributesAndProperties($v) {
//echo "\n\n -   -----  #### <PRE>" .print_r(self::$current_product['inventory'], 1) ."</PRE>";

    $ret = [];
    $productsPriceFull = false;
    // split attributes and properties
    $attr = [];
    // get all option_ids
    if (isset(self::$current_product['inventory'][0]['internalId']) ) {
      foreach (self::$current_product['inventory'] as $invProduct) {
        if (isset($invProduct['matrixOptionList']) && is_array($invProduct['matrixOptionList']['matrixOption'])) {
          foreach ($invProduct['matrixOptionList']['matrixOption'] as $option) { //
            $attr[$option['internalId']][] = $option;
          }
        }
      }
    }

    $properties = [];
    if (count($attr)>0) {
      $attrIds = array_keys($attr);
      foreach ($v['customField'] as $k => $cf) {
        if (in_array($cf['internalId'], $attrIds)) {
          unset($v['customField'][$k]);
        }
      }
    }
    if (count($v['customField'])>0) {
      $properties = array_values($v['customField']);
    }
//echo "\n\n  ATTR #### <PRE>" .print_r($attr, 1) ."</PRE>";
//echo "\n count(\$attr)" .count($attr) . " props #### <PRE>" .print_r($properties, 1) ."</PRE>\n";


    //["attributes"]=> array(    [0]=>   ["options_id", "options_values_id", "options_name", "options_values_name"] )
    if (count($attr)>0) {
      foreach ($attr as $options_id => $values) {
        $option_name = (isset(self::$customFields[$values[0]['scriptId']]['label'])?self::$customFields[$values[0]['scriptId']]['label']:''); //ToDo translation translationsList->translations[0,1...]->[locale, label]
/*        if (isset(self::$customFields[$value['scriptId']]['customValueList']['customValue'])) {
          foreach (self::$customFields[$value['scriptId']]['customValueList']['customValue'] as $ov) {*/
        if (isset(self::$customFields[$values[0]['scriptId']]['selectRecordType']['scriptId'])) {
          $valsO = self::$customFields[self::$customFields[$values[0]['scriptId']]['selectRecordType']['scriptId']];
          if (is_array($valsO['customValueList']['customValue'])) {
            foreach ($valsO['customValueList']['customValue'] as $ov) {
              $ret['attributes'][] = [
                'options_id' => $options_id,
                'options_values_id' => $ov['valueId'],
                'options_name' => $option_name,
                'options_values_name' => $ov['value'],
              ];
            }
          } else {
            foreach ($values as $ov ) {
              $ret['attributes'][] = [
                'options_id' => $options_id,
                'options_values_id' => $ov['value']['internalId'],
                'options_name' => $option_name,
                'options_values_name' => $ov['value']['name'],
              ];
            }
          }

        } else {
          foreach ($values as $ov ) {
            $ret['attributes'][] = [
              'options_id' => $options_id,
              'options_values_id' => $ov['value']['internalId'],
              'options_name' => $option_name, 
              'options_values_name' => $ov['value']['name'],
            ];
          }
        }
      }
      //inventory
      $ret['inventory'] = [];
      foreach (self::$current_product['inventory'] as $invProduct) {
        $inv = [];
        if (isset($invProduct['matrixOptionList']) && is_array($invProduct['matrixOptionList']['matrixOption'])) {
          $inv = self::getInventoryPrices($invProduct['pricingMatrix']);
          if (isset($inv['inventory_full_price']) && $inv['inventory_full_price']>0) {
            $productsPriceFull = true;
          }
          $inv['products_model'] = $invProduct['itemId'];
          $inv['products_upc'] = $invProduct['upcCode'];
          $tmp = self::getWeightDetails($invProduct['weight'], $invProduct['weightUnit']);
          $inv['inventory_weight'] = $tmp['products_weight'];
          $tmp = self::getStock($invProduct['locationsList'], true);
          $inv['warehouses_products'] = $tmp['warehouses_products'];
          $tmp = self::getSuppliers($invProduct['itemVendorList']);
          $inv['suppliers_data'] = $tmp['suppliers_data'];
          $attribute_map = [];
          foreach ($invProduct['matrixOptionList']['matrixOption'] as $option) {
            $attribute_map[] = [
              'options_id' => $option['internalId'],
              'options_values_id' => $option['value']['internalId'],
              'options_name' => (isset(self::$customFields[$option['scriptId']]['label'])?self::$customFields[$option['scriptId']]['label']:''), /// no translation :(
              'options_values_name' => $option['value']['name'],
            ];

          }
          $inv['attribute_map'] = $attribute_map;
        }
        $ret['inventory'][] = $inv;
      }
    }
    if ($productsPriceFull ) {
      $ret['products_price_full'] = 1;
    }

    if (count($properties)>0) {
      $ret['properties'] = [];
      $langCode = \common\classes\language::get_code();
      foreach ($properties as $v) {
        if (is_array($v['value'])) {
          // array - list or value - skip (no easy way to generate unique unique external Id)
          $values_id = $v['value']['internalId'];
          $values_name = $v['value']['name'];
        
          $options_id = $v['internalId'];
          $scriptId = $v['scriptId'];
          $option_name = (isset(self::$customFields[$scriptId]['name'])?self::$customFields[$scriptId]['name']:''); /// no translation :(
          if (!empty($option_name)) {
            $ret['properties'][] = [
              'properties_id' => $options_id,
              'values_id' => $values_id,
              'names' => [[$langCode => $option_name]],
              'name_path' => [[$langCode => $option_name]],
              'values' => [[$langCode => $values_name]],
            ];
          }
        } /*else {
          $values_name = $v['value'];
          $values_id = '-10000';
        }*/
      }
    }

//echo "\n\n res#################################### <PRE>" .print_r($ret, 1) ."</PRE>";


    return $ret;
  }

  public static function getSuppliers($vl) {
    $ret = [];
    if (isset($vl['itemVendor'])  && is_array($vl['itemVendor'])) {

      if (!is_array(self::$nsSuppliers)) {
        self::$nsSuppliers = self::getSuppliersMap();
      }
/*
      [itemVendor] => Array
          (
              [0] => NetSuite\Classes\ItemVendor Object
                  (
                      [vendor] => NetSuite\Classes\RecordRef Object
                          (
                              [internalId] => 38
                              [externalId] =>
                              [type] =>
                              [name] => American Computers
                          )

                      [vendorCode] => IMPD1000-24
                      [vendorCurrencyName] => USD
                      [vendorCurrency] =>
                      [purchasePrice] => 799
                      [preferredVendor] => 1
                      [schedule] =>
                      [subsidiary] =>
                  )

          ) */
      foreach ($vl['itemVendor'] as $key => $v) {
        if (is_array($v) && !empty($v['vendor']['internalId']) && isset(self::$nsSuppliers[$v['vendor']['internalId']])) {
          $ret['suppliers_data'][] = [
                'suppliers_id' => self::$nsSuppliers[$v['internalId']],
                'suppliers_model' => $v['vendorCode'],
                'suppliers_price' => $v['purchasePrice'],
                'is_default' => $v['preferredVendor'],
            ];
        }
      }
    }
    return $ret;
  }

  public static function getCategoriesFromClass($v) {
    $ret = [];

    if (is_array($v) && !empty($v['internalId']) && isset(self::$nsCategories[$v['internalId']])) {
      $cat = self::$nsCategories[$v['internalId']];
      $ret['assigned_categories'][] = [
        'categories_id' => $cat['id'],
        'categories_path' => isset($cat['categories_path'])?$cat['categories_path']:$cat['text'],
        'categories_path_array' => isset($cat['categories_path_array'])?$cat['categories_path_array']:[],
          ];
  /*["assigned_categories"]=>  array {[0]=> array {
    ["categories_id"]=> ["sort_order"]=> ["categories_path"]=> "GAMING CHAIRS;Quersus"
      ["categories_path_array"]=>array  {[0]=>
        array(2) {
          ["id"]=>
          string(3) "238"
          ["text"]=>
          string(13) "GAMING CHAIRS"
        }
        [1]=>
        array(2) {
          ["id"]=>
          int(285)
          ["text"]=>
          string(7) "Quersus"
        }
      }
    }
  }    */
    }

    return $ret;
  }

  public static function getInventoryPrices($v) {
    return self::getPrices($v, true);
  }

  public static function getPrices($v, $inventory=false) {
    $ret = [];
    if (is_array($v['pricing'])) {
      if (empty(self::$nsCurrencies)) {
        self::$nsCurrencies = self::getCurrenciesMap();
      }
      if (empty(self::$nsGroups)) {
        self::$nsGroups = self::getGroupsMap();
      }

      foreach ($v['pricing'] as $group_price) {
        //$group_price[currency =>[id, name], priceLevel/*group*/ => [id ,name], discount => -15, priceList=> price => [0=>[value, quantity]]
        //to structure  ["prices"]=>[  ["EUR_5"]=>  ["price_prefix", "inventory_group_price"=> "-2.000000", "inventory_group_discount_price"]=>'',"inventory_full_price" => "-2.000000", "inventory_discount_full_price"=>'']]

        $qtyDiscount = '';
        //$defCurrency = (self::$nsCurrencies[$group_price['currency']['internalId']]['code'] == DEFAULT_CURRENCY);
        /* default currency in NS and on site is different :( - use rate to convert 
        if (!$defCurrency && USE_MARKET_PRICES != 'True') {
          continue;
        }
         */
        $defCurrency = false; // correct fields will be filled in later, during import
        $curIndex = self::$nsCurrencies[$group_price['currency']['internalId']]['code'] . '_' . self::$nsGroups[$group_price['priceLevel']['internalId']];
        $curRate = self::$nsCurrencies[$group_price['currency']['internalId']]['value'];
        if (is_array($group_price['priceList']['price'])) {
          foreach ($group_price['priceList']['price'] as $priceO) {
            if ($priceO['quantity']==0) {
              $price = $priceO['value'];
            } else {
              $qtyDiscount .= ';' . $priceO['quantity'] . ':' . $priceO['value'];
            }
          }
          if (strlen($qtyDiscount)>0) {
            $qtyDiscount = substr($qtyDiscount, 1);
          }
          if ($inventory) {
            if ($defCurrency) {
              /*
              $ret['inventory_price'] = $price;
              $ret['price_prefix'] = '+';
              $ret['inventory_discount_price'] = $qtyDiscount;
               */
              $ret['inventory_full_price'] = $price;
              $ret['inventory_discount_full_price'] = $qtyDiscount;
            } else {
              /*
              $ret['prices'][$curIndex]['price_prefix'] = '+';
              $ret['prices'][$curIndex]['inventory_group_price'] = '-2';
              $ret['prices'][$curIndex]['inventory_group_discount_price'] = '';
               */
              $ret['prices'][$curIndex]['inventory_full_price'] = $price;
              $ret['prices'][$curIndex]['inventory_discount_full_price'] = $qtyDiscount;
              if (!isset($ret['inventory_full_price']) && $curRate>0 ) { ///our def price could be empty at NS
                $ret['inventory_full_price'] = $price / $curRate;
              }
            }

          } else {
//products_price_discount, products_sets_discount, products_sets_price
    //products_price
    //["prices"]=>   array(14) {     ["EUR_5"]=>     array(10) {       ["products_group_price"]=>        string(9) "-2.000000"       ["products_group_discount_price"]=>       string(0) ""       ["bonus_points_price"]=>       string(7) "-2.0000"        ["bonus_points_cost"]=>        string(5) "-2.00"        ["products_group_price_pack_unit"]=>        string(9)   -2.000000"        ["products_group_discount_price_pack_unit"]=>        string(0) ""        ["products_group_price_packaging"]=>       string(9) "-2.000000"      ["products_group_iscount_price_packaging"]=>      string(0) ""      ["products_price_configurator"]=>      string(8) "0.000000"      ["shipping_surcharge_price"]=>      string(8) "0.000000"

            if ($defCurrency) {
              $ret['products_price'] = $price;
              $ret['products_price_discount'] = $qtyDiscount;
              /// 2check
              $ret['products_price_pack_unit'] = -2;
              $ret['products_price_packaging'] = -2;
              $ret['prices'][$curIndex]['products_group_price'] = $price;
              $ret['prices'][$curIndex]['products_group_discount_price'] = $qtyDiscount;
              
            } else {
              /*
              $ret['prices'][$curIndex]['bonus_points_price'] = 0;
              $ret['prices'][$curIndex]['bonus_points_cost'] = 0;
              $ret['prices'][$curIndex]['products_price_configurator'] = 0;
              */
              $ret['prices'][$curIndex]['products_group_price'] = $price;
              $ret['prices'][$curIndex]['products_group_discount_price'] = $qtyDiscount;
              //2check
              $ret['prices'][$curIndex]['products_group_price_pack_unit'] = -2;
              $ret['prices'][$curIndex]['products_group_discount_price_pack_unit'] = '';
              $ret['prices'][$curIndex]['products_group_price_packaging'] = -2;
              $ret['prices'][$curIndex]['products_group_discount_price_packaging'] = '';

              if (!isset($ret['products_price']) && $curRate>0 ) { ///our def price could be empty at NS
                $ret['products_price'] = $price / $curRate;
              }

            }
          }
        }

      }

    }

    return $ret;
  }


  public static function getTaxId($v) {
    //2do
    $m = [];
    $r = 0;/*
    if (preg_match('/[0-9\.]+', $v, $m)) {
      $r = max($m);
    }*/
//    if ($r>0) {
    if (is_array($v)){
      $ret = self::TAX_CLASS_ID;
    } else {
      $ret = 0;
    }
    return $ret;
  }

  public static function updateCustomer($client, $customerDataArray, $remoteCustomerId) {
//vl2do something to update (what ?)
    $ret = $remoteCustomerId;
    if (!empty($customerDataArray['usedNsCurrencies']) && is_array($customerDataArray['usedNsCurrencies'])) {
      $customer = new NsCustomer();
      $nclist = new \NetSuite\Classes\CustomerCurrencyList();
      $list = [];
      foreach ($customerDataArray['usedNsCurrencies'] as $value) {
        $ccr = new \NetSuite\Classes\CustomerCurrency();
        $ccr->currency = new RecordRef();
        $ccr->currency->internalId = $value;
        $list[] = $ccr;
      }
      $nclist->currency = $list;
      $nclist->replaceAll = false;
      $customer->currencyList = $nclist;
      $customer->internalId = $remoteCustomerId;

      $request = new UpdateRequest();
      $request->record = $customer;

      $addResponse = $client->update($request);
///echo "#### <PRE>" .print_r($addResponse, 1) ."</PRE>";die;

      if ($addResponse->writeResponse->status->isSuccess) {
        $ret = $addResponse->writeResponse->baseRef->internalId;
      } elseif (is_array($addResponse->writeResponse->status->statusDetail) || !empty($addResponse->writeResponse->status->statusDetail->message)) {
        $ret['error'] = '';
        if (is_array($addResponse->writeResponse->status->statusDetail)) {
          foreach ($addResponse->writeResponse->status->statusDetail as $value) {
            $ret['error'] .= (!empty($value->message)?$value->message ."\n":'');
          }
        } else {
          $ret['error'] .= $addResponse->writeResponse->status->statusDetail->message . "\n";
        }
      } else {
        $ret = ['error' => 'Could not update customer'];
      }
    }
    return $ret;
  }


  public static function createCustomer ($client, $customerDataArray) {
    /*
 [customForm] =>

[phoneticName] =>
[salutation] =>
[firstName] => Alex
[middleName] =>
[lastName] => Test
[companyName] => Check Inc.
[entityStatus] => NetSuite\Classes\RecordRef Object
    (
        [internalId] => 13
        [externalId] =>
        [type] =>
        [name] => CUSTOMER-Closed Won
    )

[parent] =>
[phone] => 11222222
[fax] =>
[email] => atkach@holbi.co.uk
[url] =>
[defaultAddress] => Alex Test<br>Alex Test<br>Street addr<br>LD8 343<br>United Kingdom (GB)
[isInactive] =>
[category] =>
[title] =>
[printOnCheckAs] =>
[language] =>
[comments] =>
[numberFormat] =>
[negativeNumberFormat] =>
[dateCreated] => 2012-10-05T02:15:48.000-07:00
[image] =>
[emailPreference] => _default
[subsidiary] =>
[representingSubsidiary] =>
[salesRep] => NetSuite\Classes\RecordRef Object
    (
        [internalId] => -5
        [externalId] =>
        [type] =>
        [name] => MYACCT1
    )

[territory] =>
[contribPct] =>
[partner] =>
[salesGroup] =>
[accountNumber] =>
[taxExempt] =>
[terms] =>
[creditLimit] => 0
[creditHoldOverride] => _auto
[monthlyClosing] =>
[overrideCurrencyFormat] =>
[displaySymbol] =>
[symbolPlacement] =>
[balance] =>
[overdueBalance] =>
[daysOverdue] =>
[consolOverdueBalance] => 0
[consolDepositBalance] => 0
[consolBalance] => 0
[consolAging] => 0
[consolAging1] => 0
[consolAging2] => 0
[consolAging3] => 0
[consolAging4] => 0
[consolDaysOverdue] =>
[priceLevel] => NetSuite\Classes\RecordRef Object
    (
        [internalId] => 2
        [externalId] =>
        [type] =>
        [name] => Corporate Discount Price
    )

[currency] => NetSuite\Classes\RecordRef Object
    (
        [internalId] => 2
        [externalId] =>
        [type] =>
        [name] => British pound
    )

[prefCCProcessor] =>
[depositBalance] =>
[shipComplete] =>
[taxable] => 1
[taxItem] =>
[resaleNumber] =>
[aging] => 0
[aging1] => 0
[aging2] => 0
[aging3] => 0
[aging4] => 0
[startDate] =>
[alcoholRecipientType] => _consumer
[endDate] =>
[reminderDays] =>
[shippingItem] =>
[thirdPartyAcct] =>
[thirdPartyZipcode] =>
[thirdPartyCountry] =>
[giveAccess] =>
[estimatedBudget] =>
[accessRole] => NetSuite\Classes\RecordRef Object
    (
        [internalId] => 14
        [externalId] =>
        [type] =>
        [name] => Customer Center
    )

[sendEmail] =>
[password] =>
[password2] =>
[requirePwdChange] =>
[campaignCategory] =>
[leadSource] =>
[receivablesAccount] => NetSuite\Classes\RecordRef Object
    (
        [internalId] => -10
        [externalId] =>
        [type] =>
        [name] => Use System Preference
    )

[drAccount] =>
[fxAccount] =>
[defaultOrderPriority] =>
[webLead] => No
[referrer] =>
[keywords] =>
[clickStream] =>
[lastPageVisited] =>
[visits] =>
[firstVisit] =>
[lastVisit] =>
[billPay] =>
[openingBalance] =>
[lastModifiedDate] => 2018-04-23T11:32:08.000-07:00
[openingBalanceDate] =>
[openingBalanceAccount] =>
[stage] => _customer
[emailTransactions] =>
[printTransactions] =>
[faxTransactions] =>
[syncPartnerTeams] =>
[isBudgetApproved] =>
[globalSubscriptionStatus] => _softOptIn
[salesReadiness] =>
[salesTeamList] =>
[buyingReason] =>
[downloadList] =>
[buyingTimeFrame] =>
[addressbookList] =>
[subscriptionsList] =>
[contactRolesList] =>
[currencyList] =>
[creditCardsList] =>
[partnersList] =>
[groupPricingList] =>
[itemPricingList] =>
[customFieldList] =>
[internalId] => 1582
[externalId] =>*/


    $ret = 0;
    try {
      $customer = new NsCustomer();
      $customer->lastName = $customerDataArray['customers_firstname'];
      $customer->firstName = $customerDataArray['customers_lastname'];
      $customer->companyName = $customerDataArray['customers_company'];

      $customer->entityId = str_replace('  ', ' ', $customerDataArray['customers_firstname'] . ' ' . $customerDataArray['customers_lastname'] . ' ' . $customerDataArray['customers_company'] . ' ' . $customerDataArray['customers_email_address']);

      $customer->isPerson = empty($customerDataArray['customers_company']?1:0);

      $customer->phone = $customerDataArray['customers_telephone'];
      $customer->email = $customerDataArray['customers_email_address'];
      $customer->altPhone = $customerDataArray['customers_alt_telephone'];
      $customer->homePhone = $customerDataArray['customers_landline'];
      $customer->mobilePhone = $customerDataArray['customers_cell'];
      $customer->altEmail = $customerDataArray['customers_alt_email_address'];

      $customer->phone = $customerDataArray['customers_telephone'];
      $customer->phone = $customerDataArray['customers_telephone'];

      if (!empty($customerDataArray['usedNsCurrencies']) && is_array($customerDataArray['usedNsCurrencies'])) {
        $nclist = new \NetSuite\Classes\CustomerCurrencyList();
        $list = [];
        foreach ($customerDataArray['usedNsCurrencies'] as $value) {
          $ccr = new \NetSuite\Classes\CustomerCurrency();
          $ccr->currency = new RecordRef();
          $ccr->currency->internalId = $value;
          $list[] = $ccr;
        }
        $nclist->currency = $list;
        $nclist->replaceAll = true;
        $customer->currencyList = $nclist;
      }
/*
      $customer->customForm = new RecordRef();
      $customer->customForm->internalId = -8;
 */

      $request = new AddRequest();
      $request->record = $customer;

      $addResponse = $client->add($request);

      if ($addResponse->writeResponse->status->isSuccess) {
        $ret = $addResponse->writeResponse->baseRef->internalId;
      } elseif (is_array($addResponse->writeResponse->status->statusDetail) || !empty($addResponse->writeResponse->status->statusDetail->message)) {
        $ret['error'] = '';
        if (is_array($addResponse->writeResponse->status->statusDetail)) {
          foreach ($addResponse->writeResponse->status->statusDetail as $value) {
            $ret['error'] .= (!empty($value->message)?$value->message ."<br>\n":'');
          }
        } else {
          $ret['error'] .= $addResponse->writeResponse->status->statusDetail->message . "<br>\n";
        }
      } else {
        $ret = ['error' => 'Could not save customer'];
      }
    } catch (\Exception $ex){
      $ret = ['error' => $ex->getMessage()];
    }
//echo "#### <PRE>" .print_r($addResponse, 1) ."</PRE>";die;

    return $ret;
    
  }

  public static function prepareStrCmp ($str) {
    return trim(strtolower($str));
  }

  /**
   * 
   * @param array $nsAddress
   * @param array $tlAddress
   */
  public static function compareAddresses($nsAddress, $tlAddress) {
    $tlStreetAddresses = self::splitTlStreet($tlAddress);
    if (
        self::prepareStrCmp($nsAddress['addressee']) == self::prepareStrCmp($tlAddress['firstname'] . ' ' .$tlAddress['lastname']) &&
        self::prepareStrCmp($nsAddress['addr1']) == self::prepareStrCmp($tlStreetAddresses['addr1']) &&
        self::prepareStrCmp($nsAddress['addr2']) == self::prepareStrCmp($tlStreetAddresses['addr2']) &&
        self::prepareStrCmp($nsAddress['addr3']) == self::prepareStrCmp($tlStreetAddresses['addr3']) &&
        self::prepareStrCmp($nsAddress['city']) == self::prepareStrCmp($tlAddress['city']) &&
        self::prepareStrCmp($nsAddress['zip']) == self::prepareStrCmp($tlAddress['postcode']) &&
        self::prepareStrCmp($nsAddress['state']) == self::prepareStrCmp($tlAddress['zone_id']>0?
            \common\helpers\Zones::get_zone_name($tlAddress['country_id'], $tlAddress['zone_id'], '') : $tlAddress['state']) &&
        self::prepareStrCmp($nsAddress['country']) == self::prepareStrCmp(self::lookupNSCountry($tlAddress['country']['iso_code_2'])) &&
        1
        )
    {
      $ret = true;
    } else {
      $ret = false;
    }
    return $ret;
/* NS
address_line_1	VARCHAR2	150					Address line 1
address_line_2	VARCHAR2	150					Address line 2
address_line_3	VARCHAR2	150					Address line 3
attention	VARCHAR2	150					Attention
city	VARCHAR2	50					City
company	VARCHAR2	100					Company
name	VARCHAR2	150					Name
phone	VARCHAR2	100					Phone number
state	VARCHAR2	50					State
zip	VARCHAR2	36					Zip
*/
  }

  public static function splitTlStreet($tlAddress) {
    $ret = [];
    if (!isset($tlAddress['street_address']) && isset($tlAddress['suburb'])) {
      $tlAddress['street_address'] = $tlAddress['suburb'];
      $tlAddress['suburb'] = '';
    }
    $maxLen = 150;

    if (strlen($tlAddress['street_address'])<=$maxLen && strlen($tlAddress['suburb'])<=$maxLen) {
      $ret['addr1'] = $tlAddress['street_address'];
      $ret['addr2'] = $tlAddress['suburb'];
      $ret['addr3'] = '';
    } else {
      //2check if address is updated in DB
      if (strlen($tlAddress['street_address'])<=$maxLen && strlen($tlAddress['suburb'])>$maxLen) {

        $ret['addr1'] = $tlAddress['street_address'];
        $lines = explode("\n", wordwrap(str_replace("\n", " ", $tlAddress['suburb']), $maxLen));
        $ret['addr2'] = $lines[0];
        unset($lines[0]);
        $ret['addr3'] = implode(' ', $lines);

      } elseif (strlen($tlAddress['street_address'])>$maxLen && strlen($tlAddress['suburb'])<=$maxLen) {

        $lines = explode("\n", wordwrap(str_replace("\n", " ", $tlAddress['street_address']), $maxLen));
        $ret['addr1'] = $lines[0];
        unset($lines[0]);
        $ret['addr2'] = implode(' ', $lines);
        $ret['addr3'] = $tlAddress['suburb'];

      } else {

        $lines = explode("\n", wordwrap(str_replace("\n", " ", $tlAddress['street_address'] . ' ' . $tlAddress['suburb']), $maxLen));
        $ret['addr1'] = $lines[0];
        $ret['addr2'] = $lines[1];
        unset($lines[0]);
        unset($lines[1]);
        $ret['addr3'] = implode(' ', $lines);

      }
    }
    return $ret;

  }

  /**
   * create NS address (object) from TL-order address
   * @param array $tlAddress
   * @return \NetSuite\Classes\Address
   */
  public static function nsFromTlAddress(array $tlAddress) {
    $tlStreetAddresses = self::splitTlStreet($tlAddress);
    $address = new \NetSuite\Classes\Address();
    $address->addressee = $tlAddress['firstname'] . ' ' .$tlAddress['lastname'];
    $address->addr1 = $tlStreetAddresses['addr1'];
    $address->addr2 = $tlStreetAddresses['addr2'];
    $address->addr3 = $tlStreetAddresses['addr3'];
    $address->city = $tlAddress['city'];
    $address->zip = $tlAddress['postcode'];
    $address->state = ($tlAddress['zone_id']>0?\common\helpers\Zones::get_zone_name($tlAddress['country_id'], $tlAddress['zone_id'], '') : $tlAddress['state']);
    $address->country = self::lookupNSCountry($tlAddress['country']['iso_code_2']);

    return $address;
  }

  /**
   * add new record into NS addressbook
   * @param NSClient $client
   * @param CustomerAddressbook $addressBookId
   * @param array $tlAddress
   * @param int $customerId
   */
  public static function addAddress(\NetSuite\NetSuiteService $client, $tlAddress, $customerId) {
    if (!is_array($tlAddress)) {
      $ret = ['error' => 'Empty Address'];
    } else {
      $tlStreetAddresses = self::splitTlStreet($tlAddress);
      if (!is_array($tlStreetAddresses)) {
        $ret = ['error' => 'Incorrect Street Address'];
      } else {
        try {
          $address = self::nsFromTlAddress($tlAddress);
          $addressbook = new \NetSuite\Classes\CustomerAddressbook();
          $addressbook->addressbookAddress = $address;
          $addressbook->label = \common\helpers\Address::address_format($tlAddress['format_id'], $tlAddress, false, '', "\r\n");

          // create address book

          $abList = new \NetSuite\Classes\CustomerAddressbookList();
          $abList->addressbook = [$addressbook];
          $abList->replaceAll = false;

          $updateCustomer = new NsCustomer();
          $updateCustomer->internalId = $customerId;
          $updateCustomer->addressbookList = $abList;

          $request = new UpdateRequest();
          $request->record = $updateCustomer;
  

          $addResponse = $client->update($request);
///echo "#### <PRE>" .print_r($addResponse, 1) ."</PRE>";die;

          if ($addResponse->writeResponse->status->isSuccess) {
            $ret = $addResponse->writeResponse->baseRef->internalId; // customer, not address ID
          } elseif (is_array($addResponse->writeResponse->status->statusDetail) || !empty($addResponse->writeResponse->status->statusDetail->message)) {
            $ret['error'] = '';
            if (is_array($addResponse->writeResponse->status->statusDetail)) {
              foreach ($addResponse->writeResponse->status->statusDetail as $value) {
                $ret['error'] .= (!empty($value->message)?$value->message ."\n":'');
              }
            } else {
              $ret['error'] .= $addResponse->writeResponse->status->statusDetail->message . "\n";
            }
          } else {
            $ret = ['error' => 'Could not save address'];
          }

          //$response = self::upsert($client, $recordType, $paramms = [])


        } catch (\Exception $ex){
          $ret = ['error' => $ex->getMessage()];
        }
      }
    }
    return $ret;

  }


  public static function createOrder(\NetSuite\NetSuiteService $client, $orderData) {
    if (true) {
      $ret = self::createSalesOrder($client, $orderData);
    } else {
      $ret = self::createCashSale($client, $orderData);
    }
    return $ret;
  }

  /**
   * Create Sales Order in NS from order Data
   * @param \NetSuite\NetSuiteService $client
   * @param array $orderData
   */
  public static function createSalesOrder(\NetSuite\NetSuiteService $client, $orderData) {
    /*echo "#### <PRE>" .print_r($orderData, 1) ."</PRE>";
    die;
     (
    [customerId] => 1382
    [billingAddressId] => 248825
    [shippingAddressId] => 0
    [products] => Array
        (
            [0] => Array
                (
                    [packs] => 0
                    [units] => 0
                    [packagings] => 0
                    [qty] => 1
                    [id] => 1809
                    [name] => marix 1 DISPLAY NAME/CODE
                    [model] => marix1 name/number-B-15BB
                    [tax] => 20
                    [ga] => 0
                    [is_virtual] => 0
                    [gv_state] => none
                    [gift_wrap_price] => 0.00
                    [gift_wrapped] => 
                    [price] => 10.588173
                    [final_price] => 10.588173
                    [sets_array] => 
                    [template_uprid] => 1047{3}279{4}281
                    [parent_product] => 
                    [sub_products] => 
                    [status] => 0
                    [orders_products_id] => 98040
                    [promo_id] => 0
                    [tax_selected] => 3_4
                    [tax_class_id] => 3
                    [tax_description] => VAT 20%
                    [attributes] => Array
                        (
                            [0] => Array
                                (
                                    [option] => Cable Length
                                    [value] => 15 ft
                                    [prefix] => 
                                    [price] => 0.000000
                                    [option_id] => 3
                                    [value_id] => 279
                                )
                        )

                )
              )

                [date_purchased] => 2018-05-17T20:51:51+0100
                [last_modified] => 2018-05-25T16:12:55+0100
            )
    */
    $nsOrder = new \NetSuite\Classes\SalesOrder();
//RO    $nsOrder->createdDate = $orderData['date_purchased'];
//RO    $nsOrder->lastModifiedDate = $orderData['last_modified'];
    $nsOrder->memo = $orderData['id'];
    // [orderStatus] => _pendingFulfillment
    
    //custom form
    /*
    $rr = new \NetSuite\Classes\RecordRef();
    $rr->internalId = 100;
    $nsOrder->customForm = $rr;
*/
    //entity/customer
    $rr = new \NetSuite\Classes\RecordRef();
    $rr->internalId = $orderData['customerId'];
    $nsOrder->entity = $rr;

    //location / warehouse
    if (isset($orderData['locationId']) && $orderData['locationId']>0) {
      $rr = new \NetSuite\Classes\RecordRef();
      $rr->internalId = $orderData['locationId'];
      $nsOrder->location = $rr;
    }

    //currency
    if (isset($orderData['currencyId']) && $orderData['currencyId']>0) {
      $rr = new \NetSuite\Classes\RecordRef();
      $rr->internalId = $orderData['currencyId'];
      $nsOrder->currency = $rr;
    }

    //billing address
    if (isset($orderData['billingAddress']) && is_object($orderData['billingAddress'])) {
      $nsOrder->billingAddress = $orderData['billingAddress'];
    }

    //shipping address
    if (isset($orderData['shippingAddress']) && is_object($orderData['shippingAddress'])) {
      $nsOrder->shippingAddress = $orderData['shippingAddress'];
    }
    
    if (!empty($orderData['taxItemId'])) {
      $nsOrder->taxItem = new RecordRef();
      $nsOrder->taxItem->internalId = $orderData['taxItemId'];
      $nsOrder->isTaxable = true;
    }

    if (!empty($orderData['shippingCost'])) {
      $nsOrder->shippingCost = $orderData['shippingCost'];
    }


    if (is_array($orderData['products'])) {
      $line = 1;
      $nsp = [];
      $itemList = new \NetSuite\Classes\SalesOrderItemList();
      $itemList->replaceAll = true;
      foreach ($orderData['products'] as $product) {
        $p = new \NetSuite\Classes\SalesOrderItem();
        $p->item = new RecordRef();
        $p->item->internalId = $product['id'];
        $p->line = $line++;
        $p->quantity = $product['qty'];
        $p->amount = $product['final_price'];
        $p->isTaxable = ($product['tax_class_id']>0 || !empty($product['taxItemId']));
        if ($product['tax_class_id']>0 && !empty($product['taxItemId'])) {
          $p->taxItem = new RecordRef();
          $p->taxItem->internalId = $product['taxItemId'];
        }
        $p->location = new RecordRef(); // required
        $p->location->internalId = (!empty($product['locationId'])?$product['locationId']:(!empty($orderData['locationId'])?$orderData['locationId']:''));
        
        $nsp[] = $p;
      }
      $itemList->item = $nsp;
      $nsOrder->itemList = $itemList;
    }
 //echo "#### <PRE>" .print_r($orderData['shippingCost'], 1) ."</PRE>";  echo "#### <PRE>" .print_r($nsOrder, 1) ."</PRE>";  die;

    try {
      $request = new AddRequest();
      $request->record = $nsOrder;

      $addResponse = $client->add($request);

      if ($addResponse->writeResponse->status->isSuccess) {
          $ret = $addResponse->writeResponse->baseRef->internalId;
      }  elseif (is_array($addResponse->writeResponse->status->statusDetail) || !empty($addResponse->writeResponse->status->statusDetail->message) ) {
        $ret['error'] = '';
        if (is_array($addResponse->writeResponse->status->statusDetail )) {
          foreach ($addResponse->writeResponse->status->statusDetail as $value) {
            $ret['error'] .= (!empty($value->message)?$value->message ."\n":'');
          }
        } else {
          $ret['error'] .= $addResponse->writeResponse->status->statusDetail->message . "\n";
        }
      } else {
        $ret = ['error' => 'unknown error'];
      }
    } catch (\Exception $ex){
      $ret = ['error' => $ex->getMessage()];
    }
    return $ret;
/*
        (
            [createdDate] => 2018-06-06T12:05:40.000-07:00
            [customForm] => NetSuite\Classes\RecordRef Object
                (
                    [internalId] => 68
                    [externalId] =>
                    [type] =>
                    [name] => Standard Sales Order
                )

            [entity] => NetSuite\Classes\RecordRef Object
                (
                    [internalId] => 1382
                    [externalId] =>
                    [type] =>
                    [name] => Test1 Testl
                )

            [job] =>
            [currency] => NetSuite\Classes\RecordRef Object
                (
                    [internalId] => 2
                    [externalId] =>
                    [type] =>
                    [name] => British pound
                )

            [drAccount] =>
            [fxAccount] =>
            [tranDate] => 2018-06-05T16:00:00.000-07:00
            [tranId] => 257
            [entityTaxRegNum] =>
            [source] =>
            [createdFrom] =>
            [orderStatus] => _pendingFulfillment
            [nextBill] =>
            [opportunity] =>
            [salesRep] => NetSuite\Classes\RecordRef Object
                (
                    [internalId] => -5
                    [externalId] =>
                    [type] =>
                    [name] => MYACCT1
                )

            [contribPct] =>
            [partner] =>
            [salesGroup] =>
            [syncSalesTeams] =>
            [leadSource] =>
            [startDate] =>
            [endDate] =>
            [otherRefNum] =>
            [memo] =>
            [salesEffectiveDate] => 2018-06-05T16:00:00.000-07:00
            [excludeCommission] =>
            [totalCostEstimate] =>
            [estGrossProfit] =>
            [estGrossProfitPercent] =>
            [exchangeRate] => 1
            [promoCode] =>
            [currencyName] => British pound
            [discountItem] =>
            [discountRate] =>
            [isTaxable] => 1
            [taxItem] => NetSuite\Classes\RecordRef Object
                (
                    [internalId] => -7
                    [externalId] =>
                    [type] =>
                    [name] => -Not Taxable-
                )

            [taxRate] => 0
            [toBePrinted] =>
            [toBeEmailed] =>
            [email] => vkoshelev@trianic.com
            [toBeFaxed] =>
            [fax] =>
            [messageSel] =>
            [message] =>
            [billingAddress] => NetSuite\Classes\Address Object
                (
                    [internalId] =>
                    [country] => _unitedKingdom
                    [attention] =>
                    [addressee] => test testl
                    [addrPhone] =>
                    [addr1] => 9 Jupiter house 2
                    [addr2] => edited
                    [addr3] =>
                    [city] => Reading
                    [state] => Berkshire
                    [zip] => rg7 8nn
                    [addrText] => test testl
9 Jupiter house 2
edited
Reading Berkshire rg7 8nn
United Kingdom
                    [override] =>
                    [customFieldList] =>
                    [nullFieldList] =>
                )

            [billAddressList] => NetSuite\Classes\RecordRef Object
                (
                    [internalId] => 248824
                    [externalId] =>
                    [type] =>
                    [name] => test testl 9 Jupiter house 2 Reading Berkshire RG7 8NN United Kingdom
                )

            [shippingAddress] => NetSuite\Classes\Address Object
                (
                    [internalId] =>
                    [country] => _unitedKingdom
                    [attention] =>
                    [addressee] => test testl
                    [addrPhone] =>
                    [addr1] => 9 Jupiter house 2
                    [addr2] => edited
                    [addr3] =>
                    [city] => Reading
                    [state] => Berkshire
                    [zip] => rg7 8nn
                    [addrText] => test testl
9 Jupiter house 2
edited
Reading Berkshire rg7 8nn
United Kingdom
                    [override] =>
                    [customFieldList] =>
                    [nullFieldList] =>
                )

            [shipIsResidential] =>
            [shipAddressList] => NetSuite\Classes\RecordRef Object
                (
                    [internalId] => 248824
                    [externalId] =>
                    [type] =>
                    [name] => test testl 9 Jupiter house 2 Reading Berkshire RG7 8NN United Kingdom
                )

            [fob] =>
            [shipDate] => 2018-06-05T16:00:00.000-07:00
            [actualShipDate] =>
            [shipMethod] =>
            [shippingCost] =>
            [shippingTax1Rate] =>
            [isMultiShipTo] =>
            [shippingTax2Rate] =>
            [shippingTaxCode] =>
            [handlingTaxCode] =>
            [handlingTax1Rate] =>
            [handlingTax2Rate] =>
            [handlingCost] =>
            [trackingNumbers] =>
            [linkedTrackingNumbers] =>
            [shipComplete] =>
            [paymentMethod] =>
            [shopperIpAddress] =>
            [saveOnAuthDecline] => 1
            [canHaveStackable] =>
            [creditCard] =>
            [revenueStatus] => _pending
            [recognizedRevenue] => 0
            [deferredRevenue] => 0
            [revRecOnRevCommitment] =>
            [revCommitStatus] =>
            [ccNumber] =>
            [ccExpireDate] =>
            [ccName] =>
            [ccStreet] =>
            [ccZipCode] =>
            [payPalStatus] =>
            [creditCardProcessor] =>
            [payPalTranId] =>
            [ccApproved] =>
            [getAuth] =>
            [authCode] =>
            [ccAvsStreetMatch] =>
            [ccAvsZipMatch] =>
            [isRecurringPayment] =>
            [ccSecurityCodeMatch] =>
            [altSalesTotal] =>
            [ignoreAvs] =>
            [paymentEventResult] =>
            [paymentEventHoldReason] =>
            [paymentEventType] =>
            [paymentEventDate] =>
            [paymentEventUpdatedBy] =>
            [subTotal] => 891
            [discountTotal] => 0
            [taxTotal] => 0
            [altShippingCost] =>
            [altHandlingCost] =>
            [total] => 891
            [revRecSchedule] =>
            [revRecStartDate] =>
            [revRecEndDate] =>
            [paypalAuthId] =>
            [balance] => 0
            [paypalProcess] =>
            [billingSchedule] =>
            [ccSecurityCode] =>
            [threeDStatusCode] =>
            [class] =>
            [department] =>
            [subsidiary] => NetSuite\Classes\RecordRef Object
                (
                    [internalId] => 1
                    [externalId] =>
                    [type] =>
                    [name] => Parent Company
                )

            [intercoTransaction] =>
            [intercoStatus] =>
            [debitCardIssueNo] =>
            [lastModifiedDate] => 2018-06-06T12:13:48.000-07:00
            [nexus] =>
            [subsidiaryTaxRegNum] =>
            [taxRegOverride] =>
            [taxDetailsOverride] =>
            [location] => NetSuite\Classes\RecordRef Object
                (
                    [internalId] => 2
                    [externalId] =>
                    [type] =>
                    [name] => Warehouse - West Coast
                )

            [pnRefNum] =>
            [status] => Pending Fulfillment
            [tax2Total] =>
            [terms] =>
            [validFrom] =>
            [vatRegNum] =>
            [giftCertApplied] =>
            [oneTime] =>
            [recurWeekly] =>
            [recurMonthly] =>
            [recurQuarterly] =>
            [recurAnnually] =>
            [tranIsVsoeBundle] =>
            [vsoeAutoCalc] =>
            [syncPartnerTeams] =>
            [salesTeamList] =>
            [partnersList] =>
            [giftCertRedemptionList] =>
            [promotionsList] =>
            [itemList] => NetSuite\Classes\SalesOrderItemList Object
                (
                    [item] => Array
                        (
                            [0] => NetSuite\Classes\SalesOrderItem Object
                                (
                                    [job] =>
                                    [subscription] =>
                                    [item] => NetSuite\Classes\RecordRef Object
                                        (
                                            [internalId] => 1809
                                            [externalId] =>
                                            [type] =>
                                            [name] => marix1 name/number : marix1 name/number-B-15BB
                                        )

                                    [quantityAvailable] =>
                                    [expandItemGroup] =>
                                    [lineUniqueKey] => 10269
                                    [quantityOnHand] =>
                                    [quantity] => 2
                                    [units] =>
                                    [inventoryDetail] =>
                                    [description] =>
                                    [price] => NetSuite\Classes\RecordRef Object
                                        (
                                            [internalId] => 1
                                            [externalId] =>
                                            [type] =>
                                            [name] => Base Price
                                        )

                                    [rate] => 10.00
                                    [serialNumbers] =>
                                    [amount] => 20
                                    [isTaxable] => 1
                                    [commitInventory] => _availableQty
                                    [orderPriority] =>
                                    [licenseCode] =>
                                    [options] =>
                                    [department] =>
                                    [class] =>
                                    [location] =>
                                    [createPo] =>
                                    [createdPo] =>
                                    [altSalesAmt] =>
                                    [createWo] =>
                                    [poVendor] =>
                                    [poCurrency] =>
                                    [poRate] =>
                                    [revRecSchedule] =>
                                    [revRecStartDate] =>
                                    [revRecTermInMonths] =>
                                    [revRecEndDate] =>
                                    [deferRevRec] =>
                                    [isClosed] =>
                                    [itemFulfillmentChoice] =>
                                    [catchUpPeriod] =>
                                    [billingSchedule] =>
                                    [fromJob] =>
                                    [grossAmt] =>
                                    [taxAmount] =>
                                    [excludeFromRateRequest] =>
                                    [isEstimate] =>
                                    [line] => 1
                                    [percentComplete] =>
                                    [costEstimateType] =>
                                    [costEstimate] =>
                                    [quantityBackOrdered] => 0
                                    [quantityBilled] => 0
                                    [quantityCommitted] => 2
                                    [quantityFulfilled] => 0
                                    [quantityPacked] =>
                                    [quantityPicked] =>
                                    [tax1Amt] =>
                                    [taxCode] =>
                                    [taxRate1] =>
                                    [taxRate2] =>
                                    [giftCertFrom] =>
                                    [giftCertRecipientName] =>
                                    [giftCertRecipientEmail] =>
                                    [giftCertMessage] =>
                                    [giftCertNumber] =>
                                    [shipGroup] =>
                                    [itemIsFulfilled] =>
                                    [shipAddress] =>
                                    [shipMethod] =>
                                    [vsoeSopGroup] =>
                                    [vsoeIsEstimate] =>
                                    [vsoePrice] =>
                                    [vsoeAmount] =>
                                    [vsoeAllocation] =>
                                    [vsoeDeferral] =>
                                    [vsoePermitDiscount] =>
                                    [vsoeDelivered] =>
                                    [expectedShipDate] =>
                                    [noAutoAssignLocation] =>
                                    [locationAutoAssigned] =>
                                    [taxDetailsReference] =>
                                    [chargeType] =>
                                    [customFieldList] =>
                                )

                            [1] => NetSuite\Classes\SalesOrderItem Object
                                (
                                    [job] =>
                                    [subscription] =>
                                    [item] => NetSuite\Classes\RecordRef Object
                                        (
                                            [internalId] => 5
                                            [externalId] =>
                                            [type] =>
                                            [name] => Computer Systems : Desktop : HP Compaq d230
                                        )

                                    [quantityAvailable] =>
                                    [expandItemGroup] =>
                                    [lineUniqueKey] => 10271
                                    [quantityOnHand] =>
                                    [quantity] => 1
                                    [units] =>
                                    [inventoryDetail] =>
                                    [description] =>
                                    [price] => NetSuite\Classes\RecordRef Object
                                        (
                                            [internalId] => 1
                                            [externalId] =>
                                            [type] =>
                                            [name] => Base Price
                                        )

                                    [rate] => 299.00
                                    [serialNumbers] =>
                                    [amount] => 299
                                    [isTaxable] => 1
                                    [commitInventory] => _availableQty
                                    [orderPriority] =>
                                    [licenseCode] =>
                                    [options] =>
                                    [department] =>
                                    [class] =>
                                    [location] =>
                                    [createPo] =>
                                    [createdPo] =>
                                    [altSalesAmt] =>
                                    [createWo] =>
                                    [poVendor] =>
                                    [poCurrency] =>
                                    [poRate] =>
                                    [revRecSchedule] =>
                                    [revRecStartDate] =>
                                    [revRecTermInMonths] =>
                                    [revRecEndDate] =>
                                    [deferRevRec] =>
                                    [isClosed] =>
                                    [itemFulfillmentChoice] =>
                                    [catchUpPeriod] =>
                                    [billingSchedule] =>
                                    [fromJob] =>
                                    [grossAmt] =>
                                    [taxAmount] =>
                                    [excludeFromRateRequest] =>
                                    [isEstimate] =>
                                    [line] => 3
                                    [percentComplete] =>
                                    [costEstimateType] =>
                                    [costEstimate] =>
                                    [quantityBackOrdered] => 0
                                    [quantityBilled] => 0
                                    [quantityCommitted] => 1
                                    [quantityFulfilled] => 0
                                    [quantityPacked] =>
                                    [quantityPicked] =>
                                    [tax1Amt] =>
                                    [taxCode] =>
                                    [taxRate1] =>
                                    [taxRate2] =>
                                    [giftCertFrom] =>
                                    [giftCertRecipientName] =>
                                    [giftCertRecipientEmail] =>
                                    [giftCertMessage] =>
                                    [giftCertNumber] =>
                                    [shipGroup] =>
                                    [itemIsFulfilled] =>
                                    [shipAddress] =>
                                    [shipMethod] =>
                                    [vsoeSopGroup] =>
                                    [vsoeIsEstimate] =>
                                    [vsoePrice] =>
                                    [vsoeAmount] =>
                                    [vsoeAllocation] =>
                                    [vsoeDeferral] =>
                                    [vsoePermitDiscount] =>
                                    [vsoeDelivered] =>
                                    [expectedShipDate] =>
                                    [noAutoAssignLocation] =>
                                    [locationAutoAssigned] =>
                                    [taxDetailsReference] =>
                                    [chargeType] =>
                                    [customFieldList] =>
                                )

                            [2] => NetSuite\Classes\SalesOrderItem Object
                                (
                                    [job] =>
                                    [subscription] =>
                                    [item] => NetSuite\Classes\RecordRef Object
                                        (
                                            [internalId] => 1812
                                            [externalId] =>
                                            [type] =>
                                            [name] => c d530 new
                                        )

                                    [quantityAvailable] =>
                                    [expandItemGroup] =>
                                    [lineUniqueKey] => 10272
                                    [quantityOnHand] =>
                                    [quantity] => 1
                                    [units] =>
                                    [inventoryDetail] =>
                                    [description] =>
                                    [price] => NetSuite\Classes\RecordRef Object
                                        (
                                            [internalId] => 1
                                            [externalId] =>
                                            [type] =>
                                            [name] => Base Price
                                        )

                                    [rate] => 572.00
                                    [serialNumbers] =>
                                    [amount] => 572
                                    [isTaxable] =>
                                    [commitInventory] =>
                                    [orderPriority] =>
                                    [licenseCode] =>
                                    [options] =>
                                    [department] =>
                                    [class] =>
                                    [location] =>
                                    [createPo] =>
                                    [createdPo] =>
                                    [altSalesAmt] =>
                                    [createWo] =>
                                    [poVendor] =>
                                    [poCurrency] =>
                                    [poRate] =>
                                    [revRecSchedule] =>
                                    [revRecStartDate] =>
                                    [revRecTermInMonths] =>
                                    [revRecEndDate] =>
                                    [deferRevRec] =>
                                    [isClosed] =>
                                    [itemFulfillmentChoice] =>
                                    [catchUpPeriod] =>
                                    [billingSchedule] =>
                                    [fromJob] =>
                                    [grossAmt] =>
                                    [taxAmount] =>
                                    [excludeFromRateRequest] =>
                                    [isEstimate] =>
                                    [line] => 4
                                    [percentComplete] =>
                                    [costEstimateType] =>
                                    [costEstimate] =>
                                    [quantityBackOrdered] => 0
                                    [quantityBilled] => 0
                                    [quantityCommitted] => 1
                                    [quantityFulfilled] => 0
                                    [quantityPacked] =>
                                    [quantityPicked] =>
                                    [tax1Amt] =>
                                    [taxCode] =>
                                    [taxRate1] =>
                                    [taxRate2] =>
                                    [giftCertFrom] =>
                                    [giftCertRecipientName] =>
                                    [giftCertRecipientEmail] =>
                                    [giftCertMessage] =>
                                    [giftCertNumber] =>
                                    [shipGroup] =>
                                    [itemIsFulfilled] =>
                                    [shipAddress] =>
                                    [shipMethod] =>
                                    [vsoeSopGroup] =>
                                    [vsoeIsEstimate] =>
                                    [vsoePrice] =>
                                    [vsoeAmount] =>
                                    [vsoeAllocation] =>
                                    [vsoeDeferral] =>
                                    [vsoePermitDiscount] =>
                                    [vsoeDelivered] =>
                                    [expectedShipDate] =>
                                    [noAutoAssignLocation] =>
                                    [locationAutoAssigned] =>
                                    [taxDetailsReference] =>
                                    [chargeType] =>
                                    [customFieldList] =>
                                )

                        )

                    [replaceAll] => 1
                )

            [shipGroupList] =>
            [accountingBookDetailList] =>
            [taxDetailsList] =>
            [customFieldList] => NetSuite\Classes\CustomFieldList Object
                (
                    [customField] => Array
                        (
                            [0] => NetSuite\Classes\StringCustomFieldRef Object
                                (
                                    [value] => 0123456789
                                    [internalId] => 1
                                    [scriptId] => custbody1
                                )

                        )

                )

            [internalId] => 5874
            [externalId] =>
            [nullFieldList] =>
        )

  */
  }



  /**
   * Create Cash Sale in NS from order Data
   * @param \NetSuite\NetSuiteService $client
   * @param array $orderData
   */
  public static function createCashSale(\NetSuite\NetSuiteService $client, $orderData) {
    /*echo "#### <PRE>" .print_r($orderData, 1) ."</PRE>";
    die;
     (
    [customerId] => 1382
    [billingAddressId] => 248825
    [shippingAddressId] => 0
    [products] => Array
        (
            [0] => Array
                (
                    [packs] => 0
                    [units] => 0
                    [packagings] => 0
                    [qty] => 1
                    [id] => 1809
                    [name] => marix 1 DISPLAY NAME/CODE
                    [model] => marix1 name/number-B-15BB
                    [tax] => 20
                    [ga] => 0
                    [is_virtual] => 0
                    [gv_state] => none
                    [gift_wrap_price] => 0.00
                    [gift_wrapped] => 
                    [price] => 10.588173
                    [final_price] => 10.588173
                    [sets_array] => 
                    [template_uprid] => 1047{3}279{4}281
                    [parent_product] => 
                    [sub_products] => 
                    [status] => 0
                    [orders_products_id] => 98040
                    [promo_id] => 0
                    [tax_selected] => 3_4
                    [tax_class_id] => 3
                    [tax_description] => VAT 20%
                    [attributes] => Array
                        (
                            [0] => Array
                                (
                                    [option] => Cable Length
                                    [value] => 15 ft
                                    [prefix] => 
                                    [price] => 0.000000
                                    [option_id] => 3
                                    [value_id] => 279
                                )
                        )

                )
              )

                [date_purchased] => 2018-05-17T20:51:51+0100
                [last_modified] => 2018-05-25T16:12:55+0100
            )
    */
    $nsOrder = new \NetSuite\Classes\CashSale();
//RO    $nsOrder->createdDate = $orderData['date_purchased'];
//RO    $nsOrder->lastModifiedDate = $orderData['last_modified'];
    $nsOrder->memo = $orderData['id'];
    
    //custom form
    $rr = new \NetSuite\Classes\RecordRef();
    $rr->internalId = 100;
    $nsOrder->customForm = $rr;

    //entity/customer
    $rr = new \NetSuite\Classes\RecordRef();
    $rr->internalId = $orderData['customerId'];
    $nsOrder->entity = $rr;

    //location / warehouse
    if (isset($orderData['locationId']) && $orderData['locationId']>0) {
      $rr = new \NetSuite\Classes\RecordRef();
      $rr->internalId = $orderData['locationId'];
      $nsOrder->location = $rr;
    }

    //currency
    if (isset($orderData['currencyId']) && $orderData['currencyId']>0) {
      $rr = new \NetSuite\Classes\RecordRef();
      $rr->internalId = $orderData['currencyId'];
      $nsOrder->currency = $rr;
    }

    //billing address
    if (isset($orderData['billingAddress']) && is_object($orderData['billingAddress'])) {
      $nsOrder->billingAddress = $orderData['billingAddress'];
    }

    //shipping address
    if (isset($orderData['shippingAddress']) && is_object($orderData['shippingAddress'])) {
      $nsOrder->shippingAddress = $orderData['shippingAddress'];
    }
    if (is_array($orderData['products'])) {
      $line = 1;
      $nsp = [];
      $itemList = new \NetSuite\Classes\CashSaleItemList();
      $itemList->replaceAll = true;
      foreach ($orderData['products'] as $product) {
        $p = new \NetSuite\Classes\CashSaleItem();
        $p->item = new RecordRef();
        $p->item->internalId = $product['id'];
        $p->line = $line++;
        $p->quantity = $product['qty'];
        $p->amount = $product['final_price'];
        $p->isTaxable = ($product['tax_class_id']>0);
        $p->location = new RecordRef(); // required
        $p->location->internalId = ($product['locationId']>0?$product['locationId']:($orderData['locationId']>0?$orderData['locationId']:''));
        
        $nsp[] = $p;
      }
      $itemList->item = $nsp;
      $nsOrder->itemList = $itemList;
    }
    
    try {
      $request = new AddRequest();
      $request->record = $nsOrder;

      $addResponse = $client->add($request);

      if ($addResponse->writeResponse->status->isSuccess) {
          $ret = $addResponse->writeResponse->baseRef->internalId;
      }  elseif (is_array($addResponse->writeResponse->status->statusDetail) || !empty($addResponse->writeResponse->status->statusDetail->message) ) {
        $ret['error'] = '';
        if (is_array($addResponse->writeResponse->status->statusDetail )) {
          foreach ($addResponse->writeResponse->status->statusDetail as $value) {
            $ret['error'] .= (!empty($value->message)?$value->message ."\n":'');
          }
        } else {
          $ret['error'] .= $addResponse->writeResponse->status->statusDetail->message . "\n";
        }
      } else {
        $ret = ['error' => 'unknown error'];
      }
    } catch (\Exception $ex){
      $ret = ['error' => $ex->getMessage()];
    }
    return $ret;

    /*
    (
            [createdDate] => 2012-10-09T02:14:19.000-07:00
            [lastModifiedDate] => 2013-01-25T05:16:25.000-08:00
            [nexus] =>
            [subsidiaryTaxRegNum] =>
            [taxRegOverride] =>
            [taxDetailsOverride] =>
            [customForm] => NetSuite\Classes\RecordRef Object
                (
                    [internalId] => 100
                    [externalId] =>
                    [type] =>
                    [name] => Wolfe Retail Cash Sale
                )

            [entity] => NetSuite\Classes\RecordRef Object
                (
                    [internalId] => 1064
                    [externalId] =>
                    [type] =>
                    [name] => Vladislav Koshelev
                )

            [billingAccount] =>
            [recurringBill] =>
            [tranDate] => 2012-10-08T16:00:00.000-07:00
            [tranId] => 1342
            [entityTaxRegNum] =>
            [source] =>
            [postingPeriod] => NetSuite\Classes\RecordRef Object
                (
                    [internalId] => 123
                    [externalId] =>
                    [type] =>
                    [name] => M2012-10
                )

            [createdFrom] => NetSuite\Classes\RecordRef Object
                (
                    [internalId] => 2938
                    [externalId] =>
                    [type] =>
                    [name] => Sales Order #206
                )

            [opportunity] =>
            [department] =>
            [class] =>
            [location] => NetSuite\Classes\RecordRef Object
                (
                    [internalId] => 3
                    [externalId] =>
                    [type] =>
                    [name] => Warehouse test
                )

            [subsidiary] =>
            [salesRep] =>
            [contribPct] =>
            [partner] =>
            [leadSource] =>
            [startDate] =>
            [endDate] =>
            [otherRefNum] =>
            [memo] => voipon.co.uk-30613
            [salesEffectiveDate] =>
            [excludeCommission] =>
            [revRecSchedule] =>
            [undepFunds] =>
            [canHaveStackable] =>
            [currency] => NetSuite\Classes\RecordRef Object
                (
                    [internalId] => 2
                    [externalId] =>
                    [type] =>
                    [name] => British pound
                )

            [account] => NetSuite\Classes\RecordRef Object
                (
                    [internalId] => 1
                    [externalId] =>
                    [type] =>
                    [name] => 1000 Checking
                )

            [revRecStartDate] =>
            [revRecEndDate] =>
            [totalCostEstimate] =>
            [estGrossProfit] =>
            [estGrossProfitPercent] =>
            [exchangeRate] => 1
            [currencyName] => British pound
            [promoCode] =>
            [discountItem] =>
            [discountRate] =>
            [isTaxable] => 1
            [taxItem] => NetSuite\Classes\RecordRef Object
                (
                    [internalId] => 95
                    [externalId] =>
                    [type] =>
                    [name] => S-GB
                )

            [taxRate] => 20
            [toBePrinted] =>
            [toBeEmailed] =>
            [toBeFaxed] =>
            [fax] => 866 469-8549
            [messageSel] =>
            [message] =>
            [billingAddress] => NetSuite\Classes\Address Object
                (
                    [internalId] =>
                    [country] => _unitedKingdom
                    [attention] => Test Test
                    [addressee] => Test Test
                    [addrPhone] =>
                    [addr1] => main 1234
                    [addr2] =>
                    [addr3] =>
                    [city] => Reading
                    [state] => rg7 8nn
                    [zip] => Berkshire
                    [addrText] => Test Test
Test Test
main 1234
Reading rg7 8nn Berkshire
United Kingdom (GB)
                    [override] =>
                    [customFieldList] =>
                    [nullFieldList] =>
                )

            [billAddressList] =>
            [shippingAddress] => NetSuite\Classes\Address Object
                (
                    [internalId] =>
                    [country] => _unitedKingdom
                    [attention] =>
                    [addressee] => Test Test
                    [addrPhone] =>
                    [addr1] => main 1234
                    [addr2] =>
                    [addr3] =>
                    [city] => Reading
                    [state] => rg7 8nn
                    [zip] => Berkshire
                    [addrText] => Test Test
Test Test
main 1234
Reading rg7 8nn Berkshire
United Kingdom (GB)
                    [override] =>
                    [customFieldList] =>
                    [nullFieldList] =>
                )

            [shipIsResidential] =>
            [shipAddressList] =>
            [fob] =>
            [shipDate] =>
            [shipMethod] => NetSuite\Classes\RecordRef Object
                (
                    [internalId] => 94
                    [externalId] =>
                    [type] =>
                    [name] => Shipping
                )

            [shippingCost] => 5.98
            [shippingTax1Rate] =>
            [shippingTax2Rate] =>
            [shippingTaxCode] =>
            [handlingTaxCode] =>
            [handlingTax1Rate] =>
            [handlingCost] =>
            [handlingTax2Rate] =>
            [linkedTrackingNumbers] =>
            [trackingNumbers] =>
            [salesGroup] =>
            [revenueStatus] => _completed
            [recognizedRevenue] => 490.58
            [deferredRevenue] => 0
            [revRecOnRevCommitment] =>
            [syncSalesTeams] =>
            [paymentMethod] => NetSuite\Classes\RecordRef Object
                (
                    [internalId] => 7
                    [externalId] =>
                    [type] =>
                    [name] => BACS Payments
                )

            [payPalStatus] =>
            [creditCard] =>
            [ccNumber] =>
            [ccExpireDate] =>
            [ccName] =>
            [ccStreet] =>
            [ccZipCode] =>
            [creditCardProcessor] =>
            [ccApproved] =>
            [authCode] =>
            [ccAvsStreetMatch] =>
            [ccAvsZipMatch] =>
            [isRecurringPayment] =>
            [payPalTranId] =>
            [subTotal] => 484.6
            [ccIsPurchaseCardBin] =>
            [ignoreAvs] =>
            [ccProcessAsPurchaseCard] =>
            [itemCostDiscount] =>
            [itemCostDiscRate] =>
            [itemCostDiscAmount] =>
            [itemCostTaxRate1] =>
            [itemCostTaxRate2] =>
            [itemCostDiscTaxable] =>
            [itemCostTaxCode] =>
            [itemCostDiscTax1Amt] =>
            [itemCostDiscPrint] =>
            [expCostDiscount] =>
            [expCostDiscRate] =>
            [expCostDiscAmount] =>
            [expCostDiscTaxable] =>
            [expCostDiscprint] =>
            [expCostTaxRate1] =>
            [timeDiscount] =>
            [expCostTaxCode] =>
            [timeDiscRate] =>
            [expCostTaxRate2] =>
            [expCostDiscTax1Amt] =>
            [timeDiscAmount] =>
            [timeDiscTaxable] =>
            [timeDiscPrint] =>
            [discountTotal] => 0
            [taxTotal] => 98.12
            [timeTaxRate1] =>
            [altShippingCost] => 5.98
            [timeTaxCode] =>
            [altHandlingCost] =>
            [total] => 588.7
            [timeDiscTax1Amt] =>
            [ccSecurityCode] =>
            [timeTaxRate2] =>
            [ccSecurityCodeMatch] =>
            [chargeIt] =>
            [debitCardIssueNo] =>
            [threeDStatusCode] =>
            [pnRefNum] =>
            [paypalAuthId] =>
            [status] => Deposited
            [paypalProcess] =>
            [job] =>
            [billingSchedule] =>
            [email] => vkoshelev@holbi.co.uk
            [tax2Total] =>
            [validFrom] =>
            [vatRegNum] =>
            [giftCertApplied] =>
            [tranIsVsoeBundle] =>
            [vsoeAutoCalc] =>
            [syncPartnerTeams] =>
            [salesTeamList] =>
            [partnersList] =>
            [itemList] => NetSuite\Classes\CashSaleItemList Object
                (
                    [item] => Array
                        (
                            [0] => NetSuite\Classes\CashSaleItem Object
                                (
                                    [job] =>
                                    [item] => NetSuite\Classes\RecordRef Object
                                        (
                                            [internalId] => 1109
                                            [externalId] =>
                                            [type] =>
                                            [name] => Test_1_OPT1
                                        )

                                    [line] => 1
                                    [quantityAvailable] =>
                                    [quantityOnHand] =>
                                    [quantityFulfilled] =>
                                    [quantity] => 1
                                    [units] =>
                                    [inventoryDetail] =>
                                    [serialNumbers] =>
                                    [binNumbers] =>
                                    [description] => Part of 1 Test
                                    [price] => NetSuite\Classes\RecordRef Object
                                        (
                                            [internalId] => 2
                                            [externalId] =>
                                            [type] =>
                                            [name] => Corporate Discount Price
                                        )

                                    [rate] => 1.80
                                    [amount] => 9
                                    [orderLine] => 1
                                    [licenseCode] =>
                                    [isTaxable] => 1
                                    [options] =>
                                    [deferRevRec] =>
                                    [currentPercent] =>
                                    [department] =>
                                    [percentComplete] =>
                                    [class] =>
                                    [location] => NetSuite\Classes\RecordRef Object
                                        (
                                            [internalId] => 3
                                            [externalId] =>
                                            [type] =>
                                            [name] => Warehouse test
                                        )

                                    [revRecSchedule] =>
                                    [revRecStartDate] =>
                                    [revRecEndDate] =>
                                    [subscriptionLine] =>
                                    [grossAmt] =>
                                    [costEstimateType] =>
                                    [excludeFromRateRequest] =>
                                    [catchUpPeriod] =>
                                    [costEstimate] =>
                                    [taxDetailsReference] =>
                                    [amountOrdered] =>
                                    [tax1Amt] =>
                                    [quantityOrdered] =>
                                    [quantityRemaining] => 0
                                    [taxCode] =>
                                    [taxRate1] =>
                                    [taxRate2] =>
                                    [giftCertFrom] =>
                                    [giftCertRecipientName] =>
                                    [giftCertRecipientEmail] =>
                                    [giftCertMessage] =>
                                    [taxAmount] =>
                                    [giftCertNumber] =>
                                    [shipGroup] =>
                                    [itemIsFulfilled] =>
                                    [shipAddress] =>
                                    [shipMethod] =>
                                    [vsoeSopGroup] =>
                                    [vsoeIsEstimate] =>
                                    [vsoePrice] =>
                                    [vsoeAmount] =>
                                    [vsoeAllocation] =>
                                    [vsoeDeferral] =>
                                    [vsoePermitDiscount] =>
                                    [vsoeDelivered] =>
                                    [chargeType] =>
                                    [chargesList] =>
                                    [customFieldList] =>
                                )

                        )

                    [replaceAll] => 1
                )

            [accountingBookDetailList] =>
            [itemCostList] =>
            [giftCertRedemptionList] =>
            [promotionsList] =>
            [expCostList] =>
            [timeList] =>
            [shipGroupList] =>
            [taxDetailsList] =>
            [customFieldList] => NetSuite\Classes\CustomFieldList Object
                (
                    [customField] => Array
                        (
                            [0] => NetSuite\Classes\StringCustomFieldRef Object
                                (
                                    [value] => 0123456789
                                    [internalId] => 1
                                    [scriptId] => custbody1
                                )

                        )

                )

            [internalId] => 3039
            [externalId] =>
            [nullFieldList] =>
        )
    */
  }

}