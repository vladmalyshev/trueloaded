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


use common\api\models\Soap\SoapModel;


/**
 * Class ArrayOfPriceInfo
 * @package common\api\models\Soap\Products
 * @soap-wsdl <xsd:sequence>
 * @soap-wsdl <xsd:element minOccurs="0" maxOccurs="unbounded" nillable="false" name="price_info" type="tns:PriceInfo"/>
 * @soap-wsdl </xsd:sequence>
 * @soap-wsdl <xsd:attribute name="products_id" type="xsd:integer" use="required"/>
 */

class ArrayOfPriceInfo extends SoapModel
{

    /**
     * @var integer
     * @soap
     */
    public $products_id;

    /**
     * @var \common\api\models\Soap\Products\PriceInfo PriceInfo {nillable = 0, minOccurs=0, maxOccurs = unbounded}
     * @soap
     */
    public $price_info = [];

    static public function forProduct($products_id, $isProductOwner=false)
    {
        static $currencies_map = false;
        if (!is_array($currencies_map)){
            $currencies = new \common\classes\Currencies();
            foreach( $currencies->currencies as $code=>$currencyInfo ) {
                $currencies_map[ $currencyInfo['id'] ] = $code;
            }
        }
        $that = new ArrayOfPriceInfo();
        $that->products_id = $products_id;
        $that->price_info = [];

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
                $pInfo['isProductOwner'] = $isProductOwner;
                $pInfo['currency'] = \common\helpers\Currencies::systemCurrencyCode();
                if ( !empty($pInfo['currencies_id']) ) {
                    if ( isset($currencies_map[$pInfo['currencies_id']]) ) {
                        $pInfo['currency'] = $currencies_map[$pInfo['currencies_id']];
                    }else{
                        continue;
                    }
                }
                $that->price_info[] = new PriceInfo($pInfo);
            }
        }

        return $that;
    }

}