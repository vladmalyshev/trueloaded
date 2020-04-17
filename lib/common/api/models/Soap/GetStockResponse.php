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


use common\api\models\Soap\Products\ArrayOfStockInfo;
use common\api\models\Soap\Products\StockInfo;
use common\api\SoapServer\ServerSession;

class GetStockResponse extends SoapModel
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
     * @var \common\api\models\Soap\Products\ArrayOfStockInfo Array of StockInfo {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $stock;

    /**
     * @var ArrayOfSearchConditions
     */
    public $searchCondition = false;

    protected $inventoryPresent = false;

    public function __construct(array $config = [])
    {
        $this->inventoryPresent = (defined('PRODUCTS_INVENTORY') && PRODUCTS_INVENTORY == 'True');

        $this->stock = new ArrayOfStockInfo();
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
            'products_model' => ($this->inventoryPresent?'(IFNULL(i.products_model, p.products_model) ? OR p.products_model ?)':'p.products_model'),
        ]);
        $limit_products_id = false;
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
                if ( $searchCondition->column=='products_id' ) {
                    $limit_products_id = [];
                    if (!empty($filter_conditions)) $filter_conditions .= ' AND ';
                    $filter_conditions .= 'p.products_id IN(\'' . implode("','", array_unique(array_map('intval', $searchCondition->values))) . '\') ';
                    if ($searchCondition->values) {
                        foreach ($searchCondition->values as $value) {
                            if (strpos($value, '{') !== false) {
                                $_tmp_inventory[$value] = $value;
                            } else {
                                $_tmp_products[$value] = $value;
                            }
                            $limit_products_id[$value] = $value;
                        }
                    }
                    if (count($_tmp_inventory)>0) {
                        $union_inventory_where .= 'AND i.products_id IN (\''.implode("','",$_tmp_inventory).'\') AND i.prid IN('.implode(', ',array_unique(array_map('intval',$_tmp_inventory))).') ';
                    }else{
                        //$union_inventory_where .= "AND 1=0 ";
                        $union_inventory_where .= 'AND i.prid IN('.implode(', ',array_unique(array_map('intval',$_tmp_products))).') ';
                    }
                    if (count($_tmp_products)>0) {
                        $union_products_where .= 'AND p.products_id IN('.implode(', ',array_unique(array_map('intval',$_tmp_products))).') ';
                    }else{
                        $union_products_where .= "AND 1=0 ";
                    }
                }elseif ($searchCondition->column=='products_model'){

                    if (!empty($filter_conditions)) $filter_conditions .= ' AND ';
                    $filter_conditions .= 'p.products_model IN(\'' . implode("','", array_unique(array_map('tep_db_input', $searchCondition->values))) . '\') ';
                    $union_products_where .= 'AND p.products_model IN(\'' . implode("','", array_unique(array_map('tep_db_input', $searchCondition->values))) . '\') ';
                    $union_inventory_where .= 'AND i.products_model IN(\'' . implode("','", array_unique(array_map('tep_db_input', $searchCondition->values))) . '\') ';
                }
            }
        }

        $languages_id = \common\classes\language::defaultId();

        $join_tables = '';
        $filter_sql = '';
        if ( !empty($filter_conditions) ) {
            $filter_sql .= "AND {$filter_conditions} ";
        }

        if ( ServerSession::get()->getDepartmentId() ) {
            $join_tables .=
                " INNER JOIN ".TABLE_DEPARTMENTS_PRODUCTS." dp ON dp.products_id=p.products_id AND dp.departments_id='".ServerSession::get()->getDepartmentId()."' ".
                " INNER JOIN ".TABLE_PRODUCTS_TO_CATEGORIES." p2c ON p2c.products_id=p.products_id ".
                " INNER JOIN ".TABLE_DEPARTMENTS_CATEGORIES." dc ON p2c.categories_id=dc.categories_id AND dc.departments_id='".ServerSession::get()->getDepartmentId()."' ";
        }
        if ( ServerSession::get()->getPlatformId() ) {
            if (\common\classes\platform::isMulti() && !ServerSession::get()->acl()->siteAccessPermission()) {
                $join_tables .=
                    "INNER JOIN " . TABLE_PLATFORMS_PRODUCTS . " plp ON plp.products_id=p.products_id AND plp.platform_id='" . ServerSession::get()->getPlatformId() . "' " .
                    "INNER JOIN " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c ON p2c.products_id=p.products_id " .
                    "INNER JOIN " . TABLE_PLATFORMS_CATEGORIES . " plc ON p2c.categories_id=plc.categories_id AND plc.platform_id='" . ServerSession::get()->getPlatformId() . "' ";
            }
        }

        if ( $this->inventoryPresent ) {
            $main_sql =
                "SELECT SQL_CALC_FOUND_ROWS un.* FROM " .
                "(".
                " ( ".
                    "SELECT DISTINCT i.products_id AS products_id, i.prid AS prid, " .
                    " i.products_quantity AS products_quantity, " .
                    " i.products_model, ".
                    " i.stock_indication_id, i.stock_delivery_terms_id ".
                    " " .
                    "FROM " . TABLE_PRODUCTS . " p " .
                    " {$join_tables} " .
                    " INNER JOIN " . TABLE_INVENTORY . " i ON p.products_id=i.prid " .
                    "WHERE 1 AND p.products_id=p.products_id_stock {$union_inventory_where} " .
                " ) ".
                " UNION ".
                " ( ".
                    "SELECT DISTINCT CONCAT('',p.products_id) AS products_id, p.products_id AS prid, " .
                    " p.products_quantity AS products_quantity, " .
                    " p.products_model, ".
                    " p.stock_indication_id, p.stock_delivery_terms_id ".
                    " " .
                    "FROM " . TABLE_PRODUCTS . " p " .
                    "  LEFT JOIN ".TABLE_INVENTORY." ie ON ie.prid=p.products_id ".
                    " {$join_tables} " .
                    "WHERE 1 AND p.products_id=p.products_id_stock AND ie.prid IS NULL {$union_products_where} " .
                " ) ".
                ") un ".
                "ORDER BY un.prid";
        }else{
            $main_sql =
                "SELECT SQL_CALC_FOUND_ROWS DISTINCT p.products_id AS products_id, " .
                " p.products_quantity AS products_quantity, " .
                " p.products_model, ".
                " p.stock_indication_id, p.stock_delivery_terms_id ".
                " " .
                "FROM " . TABLE_PRODUCTS . " p " .
                " {$join_tables} " .
                "WHERE 1 AND p.products_id=p.products_id_stock {$filter_sql} " .
                "ORDER BY p.products_id " .
                "";
        }
        $main_sql .= " LIMIT ".$this->paging->getPageOffset().", ".$this->paging->getPerPage();
        //echo $main_sql; die;

        $get_stock_r = tep_db_query($main_sql);
        $getRows = tep_db_fetch_array(tep_db_query("SELECT FOUND_ROWS() AS rows_count"));
        $this->paging->setFoundRows(tep_db_num_rows($get_stock_r), (int)$getRows['rows_count']);

        if ( tep_db_num_rows($get_stock_r)>0 ) {
            while($_stock = tep_db_fetch_array($get_stock_r)){
                if ( !isset($this->stock->stock_info[$_stock['products_id']]) && ($limit_products_id===false || (is_array($limit_products_id) && (isset($limit_products_id[$_stock['products_id']]) || isset($limit_products_id[(int)$_stock['products_id']])) )) ) {
                    $_stock['quantity'] = $_stock['products_quantity'];
                    $this->stock->stock_info[$_stock['products_id']] = new StockInfo($_stock);
                }
/*                if ( $this->inventoryPresent && isset($_stock['inv_products_id']) && ($limit_products_id===false || (is_array($limit_products_id) && isset($limit_products_id[$_stock['inv_products_id']]))) ) {
                    $this->stock->stock_info[$_stock['inv_products_id']] = new StockInfo([
                        'products_id' => $_stock['inv_products_id'],
                        'quantity' => $_stock['inv_products_quantity'],
                        'stock_indication_id' => $_stock['inv_stock_indication_id'],
                        'stock_delivery_terms_id' => $_stock['inv_stock_delivery_terms_id'],
                    ]);
                }*/
            }
            $this->stock->stock_info = array_values($this->stock->stock_info);
        }

        $this->stock->build();

        parent::build();
    }
}