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


use common\api\models\Soap\Products\ArrayOfProductPrices;
use common\api\models\Soap\Products\ArrayOfPriceInfo;
use common\api\SoapServer\ServerSession;

class GetPricesResponse extends SoapModel
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
     * @var \common\api\models\Soap\Paging {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $paging;
    /**
     * @var \common\api\models\Soap\Products\ArrayOfProductPrices Array of ArrayPriceInfo {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $product_prices;

    /**
     * @var ArrayOfSearchConditions
     */
    public $searchCondition = false;

    protected $inventoryPresent = false;

    public function __construct(array $config = [])
    {
        $this->inventoryPresent = (defined('PRODUCTS_INVENTORY') && PRODUCTS_INVENTORY == 'True');

        $this->product_prices = new ArrayOfProductPrices();
        if ( !is_object($this->paging) ) {
            $this->paging = new Paging([
                'maxPerPage' => 200,
            ]);
        }
        parent::__construct($config);
    }

    public function setSearchCondition(ArrayOfSearchConditions $searchCondition)
    {
        $this->searchCondition = $searchCondition;
    }

    public function build()
    {

        $this->searchCondition->setAllowedOperators(['=','IN']);
        $filter_conditions = $this->searchCondition->buildRequestCondition([
            'products_id' => ($this->inventoryPresent?'(IFNULL(i.products_id, p.products_id) ? OR p.products_id ?)':'p.products_id'),
            //'products_model' => ($this->inventoryPresent?'(IFNULL(i.products_model, p.products_model) ? OR p.products_model ?)':'p.products_model'),
        ]);
        $union_inventory_where = '';
        $union_products_where = '';
        if ( $filter_conditions===false ) {
            $this->error($this->searchCondition->getLastError());
            return;
        }elseif (!empty($filter_conditions)) {
            $filter_conditions = '';
            $_tmp_inventory = [];
            $_tmp_products = [];
            foreach ($this->searchCondition->getSearchConditions() as $searchCondition) {
                if ( !empty($filter_conditions) ) $filter_conditions .= ' AND ';
                $filter_conditions .= 'p.products_id IN(\''.implode("','",array_unique(array_map('intval',$searchCondition->values))).'\') ';
                if ( $searchCondition->values ) {
                    foreach($searchCondition->values as $value) {
                        if ( strpos($value,'{')!==false ) {
                            $_tmp_inventory[$value] = $value;
                        }else{
                            $_tmp_products[$value] = $value;
                        }
                    }
                }
            }
            if (count($_tmp_inventory)>0) {
                $union_inventory_where .= 'AND i.products_id IN (\''.implode("','",$_tmp_inventory).'\') AND i.prid IN('.implode(', ',array_unique(array_map('intval',$_tmp_inventory))).') ';
            }else{
                $union_inventory_where .= "AND 1=0 ";
            }
            if (count($_tmp_products)>0) {
                $union_products_where .= 'AND p.products_id IN('.implode(', ',array_unique(array_map('intval',$_tmp_products))).') ';
            }else{
                $union_products_where .= "AND 1=0 ";
            }
        }

        $join_tables = '';
        $filter_sql = '';
        if ( !empty($filter_conditions) ) {
            $filter_sql .= "AND {$filter_conditions} ";
        }

        $ownerColumn = ' 0 as is_own_product, ';
        if ( ServerSession::get()->getDepartmentId() ) {
            $join_tables .=
                " INNER JOIN ".TABLE_DEPARTMENTS_PRODUCTS." dp ON dp.products_id=p.products_id AND dp.departments_id='".ServerSession::get()->getDepartmentId()."' ".
                " INNER JOIN ".TABLE_PRODUCTS_TO_CATEGORIES." p2c ON p2c.products_id=p.products_id ".
                " INNER JOIN ".TABLE_DEPARTMENTS_CATEGORIES." dc ON p2c.categories_id=dc.categories_id AND dc.departments_id='".ServerSession::get()->getDepartmentId()."' ";

            $ownerColumn = " IF(p.created_by_department_id='".ServerSession::get()->getDepartmentId()."',1,0) as is_own_product, ";
        }
        if ( ServerSession::get()->getPlatformId() ) {
            if (\common\classes\platform::isMulti() && !ServerSession::get()->acl()->siteAccessPermission()) {
                $join_tables .=
                    "INNER JOIN " . TABLE_PLATFORMS_PRODUCTS . " plp ON plp.products_id=p.products_id AND plp.platform_id='" . ServerSession::get()->getPlatformId() . "' " .
                    "INNER JOIN " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c ON p2c.products_id=p.products_id " .
                    "INNER JOIN " . TABLE_PLATFORMS_CATEGORIES . " plc ON p2c.categories_id=plc.categories_id AND plc.platform_id='" . ServerSession::get()->getPlatformId() . "' ";
            }
            $ownerColumn = " IF(p.created_by_platform_id='".ServerSession::get()->getPlatformId()."',1,0) as is_own_product, ";
        }

        if ( $this->inventoryPresent ) {
            $main_sql =
                "SELECT SQL_CALC_FOUND_ROWS un.* FROM " .
                "(".
                " ( ".
                "SELECT DISTINCT i.products_id AS products_id, i.prid AS prid, " .
                " IF(p.products_price_full, i.inventory_full_price, i.inventory_price) AS price, " .
                " IF(p.products_price_full, i.inventory_discount_full_price, i.inventory_discount_price) AS discount_table, ".
                " p.products_price_full, ".
                " 0 AS pack_unit, 0 AS products_price_pack_unit, '' AS products_price_discount_pack_unit, ".
                " 0 AS packaging, 0 AS products_price_packaging, '' AS products_price_discount_packaging, ".
                " {$ownerColumn} ".
                " 0 AS have_attributes ".
                " " .
                "FROM " . TABLE_PRODUCTS . " p " .
                " {$join_tables} " .
                " INNER JOIN " . TABLE_INVENTORY . " i ON p.products_id=i.prid " .
                "WHERE 1 {$union_inventory_where} " .
                " ) ".
                " UNION ".
                " ( ".
                "SELECT DISTINCT CONCAT('',p.products_id) AS products_id, p.products_id AS prid, " .
                " p.products_price AS price, " .
                " p.products_price_discount AS discount_table, ".
                " p.products_price_full, ".
                " p.pack_unit, p.products_price_pack_unit, p.products_price_discount_pack_unit, ".
                " p.packaging, p.products_price_packaging, p.products_price_discount_packaging, ".
                " {$ownerColumn} ".
                " IF(pa.products_id IS NULL, 0, 1) AS have_attributes ".
                " " .
                "FROM " . TABLE_PRODUCTS . " p " .
                " {$join_tables} " .
                " LEFT JOIN ".TABLE_PRODUCTS_ATTRIBUTES." pa ON pa.products_id=p.products_id ".
                "WHERE 1 {$union_products_where} " .
                "GROUP BY p.products_id " .
                " ) ".
                ") un ".
                "ORDER BY un.prid";
        }else{
            $main_sql =
                "SELECT SQL_CALC_FOUND_ROWS DISTINCT p.products_id AS products_id, p.products_id AS prid, " .
                " p.products_price AS price, " .
                " p.products_price_discount, ".
                " p.products_price_full, ".
                " p.pack_unit, p.products_price_pack_unit, p.products_price_discount_pack_unit, ".
                " p.packaging, p.products_price_packaging, p.products_price_discount_packaging, ".
                " {$ownerColumn} ".
                " IF(pa.products_id IS NULL, 0, 1) AS have_attributes ".
                " " .
                "FROM " . TABLE_PRODUCTS . " p " .
                " {$join_tables} " .
                " LEFT JOIN ".TABLE_PRODUCTS_ATTRIBUTES." pa ON pa.products_id=p.products_id ".
                "WHERE 1 {$filter_sql} " .
                "GROUP BY p.products_id " .
                "ORDER BY p.products_id " .
                "";
        }
        $main_sql .= " LIMIT ".$this->paging->getPageOffset().", ".$this->paging->getPerPage();
        //echo $main_sql; die;

        $get_stock_r = tep_db_query($main_sql);
        $getRows = tep_db_fetch_array(tep_db_query("SELECT FOUND_ROWS() AS rows_count"));
        $this->paging->setFoundRows(tep_db_num_rows($get_stock_r), (int)$getRows['rows_count']);

        if ( tep_db_num_rows($get_stock_r)>0 ) {
            while($_data = tep_db_fetch_array($get_stock_r)){
                if ( !isset($this->product_prices->product_price[$_data['products_id']]) ) {
                    if ( ServerSession::get()->getDepartmentId() ) {
                        \common\classes\ApiDepartment::get()->setCurrentResponseProductId($_data['products_id']);
                        $this->product_prices->product_price[$_data['products_id']] = ArrayOfPriceInfo::forProduct($_data['products_id'], !!$_data['is_own_product']);
                        \common\classes\ApiDepartment::get()->setCurrentResponseProductId(0);
                    }else{
                        $this->product_prices->product_price[$_data['products_id']] = ArrayOfPriceInfo::forProduct($_data['products_id'], !!$_data['is_own_product']);
                    }
                }
            }
            $this->product_prices->product_price = array_values($this->product_prices->product_price);
        }

        $this->product_prices->build();

        parent::build();
    }
}