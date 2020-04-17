<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap\Products;


use backend\models\EP\Tools;
use common\api\models\Soap\SoapModel;

class ArrayOfCustomerGroupProductPrices extends SoapModel
{

    /**
     * @var \common\api\models\Soap\Products\PriceInfoCustomerGroup PriceInfoCustomerGroup {nillable = 0, minOccurs=0, maxOccurs = unbounded}
     * @soap
     */
    public $customer_groups_price = [];


    static public function forProduct($products_id, $currencies_id=0, $isProductOwner=false)
    {
        static $currencies_map = false;
        if (!is_array($currencies_map)){
            $currencies = new \common\classes\Currencies();
            foreach( $currencies->currencies as $code=>$currencyInfo ) {
                $currencies_map[ $currencyInfo['id'] ] = $code;
            }
        }
        $that = new ArrayOfCustomerGroupProductPrices();
        $that->customer_groups_price = [];

        foreach(\common\helpers\Group::get_customer_groups_list() as $group_id=>$group_name) {
            $join_tables = '';
            $filter_sql = "AND p.products_id='" . (int)$products_id . "' ";

            $join_tables .= "LEFT JOIN " . TABLE_PRODUCTS_PRICES . " pp ON pp.products_id=p.products_id AND pp.groups_id='" . (int)$group_id . "' AND pp.currencies_id='".(int)$currencies_id."' ";
            $columns =
                " pp.products_group_price AS price, " .
                " pp.products_group_discount_price AS discount_table, " .
                " p.products_price_full, " .
                " p.pack_unit, pp.products_group_price_pack_unit AS products_price_pack_unit, pp.products_group_discount_price_pack_unit AS products_price_discount_pack_unit, " .
                " p.packaging, pp.products_group_price_packaging AS products_price_packaging, pp.products_group_discount_price_packaging AS products_price_discount_packaging, " .
                " ".(int)$group_id." AS groups_id, ".(int)$currencies_id." AS currencies_id, ";

            $main_sql =
                "SELECT p.products_id AS products_id, p.products_id AS prid, " .
                $columns .
                " IF(pa.products_id IS NULL, 0, 1) AS have_attributes " .
                " " .
                "FROM " . TABLE_PRODUCTS . " p " .
                " {$join_tables} " .
                " LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES . " pa ON pa.products_id=p.products_id " .
                "WHERE 1 {$filter_sql} " .
                "GROUP BY p.products_id " .
                "ORDER BY p.products_id " .
                "";
            $data_r = tep_db_query($main_sql);
            if (tep_db_num_rows($data_r) > 0) {
                while ($pInfo = tep_db_fetch_array($data_r)) {
                    $pInfo['isProductOwner'] = $isProductOwner;
                    $pInfo['groups_name'] = Tools::getInstance()->getCustomerGroupName($pInfo['groups_id']);
                    $that->customer_groups_price[] = new PriceInfoCustomerGroup($pInfo);
                }
            }
        }
        return $that;
    }

}