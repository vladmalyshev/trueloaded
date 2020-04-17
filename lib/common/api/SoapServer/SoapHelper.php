<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\SoapServer;


class SoapHelper
{
    public static function createDiscountTableArray($discount_string)
    {
        $table = [];
        $ar = preg_split("/[:;]/", rtrim($discount_string, ' ;'));

        for ($i = 0, $n = sizeof($ar); $i < $n; $i = $i + 2) {
            $table[] = [
                'quantity' => (int)$ar[$i],
                'discount_price' => (float)$ar[$i + 1],
            ];
        }
        return $table;
    }

    public static function createDiscountTableString($table)
    {
        $string = '';
        if ( is_array($table) ) {
            foreach ($table as $tableItem) {
                $string .= $tableItem['quantity'] . ':' . $tableItem['discount_price'] . ';';
            }
        }
        return $string;
    }

    public static function applyOutgoingPriceFormula($price)
    {
        if ( is_numeric($price) && $price>=0 && ServerSession::get()->getDepartmentId() ) {
            $params = \Yii::$app->get('department')->getApiOutgoingPriceParams();
            $params['price'] = $price;
            $formula = \Yii::$app->get('department')->getApiOutgoingPriceFormula();

            $productFormula = \common\classes\ApiDepartment::get()->getCurrentResponseProductPriceFormulaData();

            if ( is_array($productFormula) && is_array($productFormula['formula']) ) {
                $formula = $productFormula['formula'];
                $params['discount'] = $productFormula['discount'];
                $params['surcharge'] = $productFormula['surcharge'];
                $params['margin'] = $productFormula['margin'];;
            }

            $result = \common\helpers\PriceFormula::apply($formula, $params);

            if ( is_numeric($result) ) {
                return $result;
            }
        }
        return $price;
    }

    public static function hasCategory($categoryId)
    {
        if ( ServerSession::get()->getDepartmentId() ) {
            return \Yii::$app->get('department')->hasCategory($categoryId);
        }elseif( ServerSession::get()->getPlatformId() ) {
            if (ServerSession::get()->acl()->siteAccessPermission()){
                $check = tep_db_fetch_array(tep_db_query(
                    "SELECT COUNT(*) AS assigned " .
                    "FROM " . TABLE_CATEGORIES . " c " .
                    "WHERE c.categories_id='" . (int)$categoryId . "' " .
                    ""
                ));
            }else {
                $check = tep_db_fetch_array(tep_db_query(
                    "SELECT COUNT(*) AS assigned " .
                    "FROM " . TABLE_CATEGORIES . " c " .
                    " INNER JOIN " . TABLE_PLATFORMS_CATEGORIES . " pc ON c.categories_id=pc.categories_id AND pc.platform_id='" . ServerSession::get()->getPlatformId() . "' " .
                    "WHERE c.categories_id='" . (int)$categoryId . "' " .
                    ""
                ));
            }
            return $check['assigned']>0;
        }
    }

    public static function hasProduct($productId)
    {
        if ( ServerSession::get()->getDepartmentId() ) {
            return \Yii::$app->get('department')->hasProduct($productId);
        }elseif( ServerSession::get()->getPlatformId() ) {
            if (ServerSession::get()->acl()->siteAccessPermission()){
                $check = tep_db_fetch_array(tep_db_query(
                    "SELECT COUNT(*) AS assigned " .
                    "FROM " . TABLE_PRODUCTS . " p " .
                    "WHERE p.products_id='" . (int)$productId . "' " .
                    ""
                ));
            }else{
                $check = tep_db_fetch_array(tep_db_query(
                    "SELECT COUNT(*) AS assigned " .
                    "FROM " . TABLE_PRODUCTS . " p " .
                    " INNER JOIN " . TABLE_PLATFORMS_PRODUCTS . " pp ON pp.products_id=p.products_id AND pp.platform_id='" . ServerSession::get()->getPlatformId() . "' " .
                    " INNER JOIN " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c ON p2c.products_id=p.products_id " .
                    " INNER JOIN " . TABLE_PLATFORMS_CATEGORIES . " pc ON p2c.categories_id=pc.categories_id AND pc.platform_id='" . ServerSession::get()->getPlatformId() . "' " .
                    "WHERE p.products_id='" . (int)$productId . "' " .
                    ""
                ));
            }

            return $check['assigned']>0;
        }
    }

    static public function getServerKeyValue($keyName)
    {
        $value = null;
        $get_kv_r = tep_db_query(
            "SELECT key_value ".
            "FROM ep_holbi_soap_server_kv_storage ".
            "WHERE key_name='".tep_db_input($keyName)."' ".
            " AND departments_id='" . ServerSession::get()->getDepartmentId() . "' ".
            " AND platform_id='" . ServerSession::get()->getPlatformId() . "' "
        );
        if ( tep_db_num_rows($get_kv_r)>0 ) {
            $get_kv = tep_db_fetch_array($get_kv_r);
            $value = $get_kv['key_value'];
            tep_db_free_result($get_kv_r);
        }
        return $value;
    }

    static public function setServerKeyValue($keyName, $value)
    {
        if ( is_array($value) || is_object($value) ) {
            $value = json_encode($value);
        }
        tep_db_query(
            "INSERT INTO ep_holbi_soap_server_kv_storage (departments_id, platform_id, key_name, key_value) ".
            "VALUES ('".ServerSession::get()->getDepartmentId()."', '".ServerSession::get()->getPlatformId()."', '".tep_db_input($keyName)."','".tep_db_input($value)."') ".
            "ON DUPLICATE KEY UPDATE key_value='".tep_db_input($value)."'"
        );
    }

    static public function productFlags()
    {
        return [
            [
                'label' => 'Name and description',
                'server' => 'description_server',
                'server_own' => 'description_server_own',
                'client' => 'description_client',
            ],
            [
                'label' => 'SEO',
                'server' => 'seo_server',
                'server_own' => 'seo_server_own',
                'client' => 'seo_client',
            ],
            [
                'label' => 'Prices',
                'server' => 'prices_server',
                'server_disable' => true,
                'server_own' => 'prices_server_own',
                'client' => 'prices_client',
            ],
            [
                'label' => 'Stock',
                'server' => 'stock_server',
                'server_own' => 'stock_server_own',
                'client' => 'stock_client',
            ],
            [
                'label' => 'Attributes and inventory',
                'server' => 'attr_server',
                'server_own' => 'attr_server_own',
                'client' => 'attr_client',
            ],
            [
                'label' => 'Product identifiers',
                'server' => 'identifiers_server',
                'server_own' => 'identifiers_server_own',
                'client' => 'identifiers_client',
            ],
            [
                'label' => 'Images',
                'server' => 'images_server',
                'server_own' => 'images_server_own',
                'client' => 'images_client',
            ],
            [
                'label' => 'Size and Dimensions',
                'server' => 'dimensions_server',
                'server_own' => 'dimensions_server_own',
                'client' => 'dimensions_client',
            ],
            [
                'label' => 'Properties',
                'server' => 'properties_server',
                'server_own' => 'properties_server_own',
                'client' => 'properties_client',
            ],
        ];
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

    static public function soapDateTimeOut($dateTime)
    {
        return (new \DateTime($dateTime))->format('c');
    }
}