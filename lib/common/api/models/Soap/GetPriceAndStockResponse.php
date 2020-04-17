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

use common\api\models\Soap\Products\ArrayOfPriceAndStockInfo;
use common\api\models\Soap\Products\ArrayOfPriceInfo;
use common\api\models\Soap\Products\ArrayOfStockInfo;
use common\api\models\Soap\Products\ArrayOfSupplierProductData;
use common\api\models\Soap\Products\PriceAndStockInfo;
use common\api\models\Soap\Products\StockInfo;
use common\api\models\AR\Products\SuppliersData;
use common\api\SoapServer\ServerSession;

class GetPriceAndStockResponse extends SoapModel
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
     * @var \common\api\models\Soap\Products\ArrayOfPriceAndStockInfo Array of PriceAndStockInfo {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $products_price_stock;

    /**
     * @var ArrayOfSearchConditions
     */
    public $searchCondition = false;

    protected $inventoryPresent = false;

    public function __construct(array $config = [])
    {
        $this->inventoryPresent = (defined('PRODUCTS_INVENTORY') && PRODUCTS_INVENTORY == 'True');

        $this->products_price_stock = new ArrayOfPriceAndStockInfo();
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
            'products_id' => 'p.products_id',
            'products_model' => 'p.products_model',
        ]);

        if ( $filter_conditions===false ) {
            $this->error($this->searchCondition->getLastError());
            return;
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
        if ( ServerSession::get()->getPlatformId()) {
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
                "SELECT SQL_CALC_FOUND_ROWS ".
                " DISTINCT p.products_id AS products_id, p.products_id AS prid, " .
                " p.products_quantity AS products_quantity, " .
                " p.products_model, ".
                " p.products_status, ".
                " p.stock_indication_id, p.stock_delivery_terms_id, ".
                " {$ownerColumn} ".
                " IF(pa.products_id IS NULL,0,1) AS inventory_present ".
                " " .
                "FROM " . TABLE_PRODUCTS . " p " .
                " LEFT JOIN ".TABLE_PRODUCTS_ATTRIBUTES." pa ON pa.products_id=p.products_id ".
                " {$join_tables} " .
                "WHERE 1 {$filter_sql} " .
                "GROUP BY p.products_id ".
                "ORDER BY p.products_id";
        }else{
            $main_sql =
                "SELECT SQL_CALC_FOUND_ROWS DISTINCT p.products_id AS products_id, p.products_id AS prid, " .
                " p.products_quantity AS products_quantity, " .
                " p.products_model, ".
                " p.products_status, ".
                " p.stock_indication_id, p.stock_delivery_terms_id, ".
                " {$ownerColumn} ".
                " 0 AS inventory_present " .
                "FROM " . TABLE_PRODUCTS . " p " .
                " {$join_tables} " .
                "WHERE 1 {$filter_sql} " .
                "ORDER BY p.products_id " .
                "";
        }
        $main_sql .= " LIMIT ".$this->paging->getPageOffset().", ".$this->paging->getPerPage();
        //echo $main_sql; die;


        $get_stock_r = tep_db_query($main_sql);
        $getRows = tep_db_fetch_array(tep_db_query("SELECT FOUND_ROWS() AS rows_count"));
        $this->paging->setFoundRows(tep_db_num_rows($get_stock_r), (int)$getRows['rows_count']);

        if ( tep_db_num_rows($get_stock_r)>0 ) {
            $_last_product_id = false;
            $grouped_config = false;
            while($_stock = tep_db_fetch_array($get_stock_r)){
                if ( $_last_product_id===false || $_last_product_id!=$_stock['prid'] ) {
                    if ( is_array($grouped_config) ) {
                        $grouped_config['stock']->stock_info = array_values($grouped_config['stock']->stock_info);
                        $this->products_price_stock->product_price_stock[] = new PriceAndStockInfo($grouped_config);
                    }

                    $_last_product_id = $_stock['prid'];
                    if ( ServerSession::get()->getDepartmentId() ) {
                        \common\classes\ApiDepartment::get()->setCurrentResponseProductId($_stock['prid']);
                    }
                    $grouped_config = [
                        'products_id' => $_stock['prid'],
                        'status' => $_stock['products_status'],
                        'prices' => ArrayOfPriceInfo::forProduct($_stock['prid'], !!$_stock['is_own_product']),
                        'stock' => new ArrayOfStockInfo(),
                    ];
                    // {{
                    $stockInfoObjectArray = SuppliersData::findAll(['products_id'=>$_stock['prid']]);
                    $stockInfoArray = [];
                    foreach ($stockInfoObjectArray as $stockInfoObject){
                        $stockInfoArray[] = $stockInfoObject->exportArray([]);
                    }
                    $grouped_config['supplier_product_data'] = new ArrayOfSupplierProductData($stockInfoArray);
                    // }}
                    if ( ServerSession::get()->getDepartmentId() ) {
                        \common\classes\ApiDepartment::get()->setCurrentResponseProductId(0);
                    }
                }
                $_stock['quantity'] = $_stock['products_quantity'];
                $grouped_config['stock']->stock_info[$_stock['products_id']] = new StockInfo($_stock);
                if ( $_stock['inventory_present'] ) {
                    $get_inventory_stock_r = tep_db_query(
                        "SELECT DISTINCT i.products_id AS products_id, i.prid AS prid, " .
                        " i.products_model, ".
                        " i.products_quantity AS products_quantity, " .
                        " i.stock_indication_id, i.stock_delivery_terms_id ".
                        " " .
                        "FROM " . TABLE_INVENTORY . " i " .
                        "WHERE i.prid='".$_stock['prid']."' ".
                        "ORDER BY i.products_id "
                    );
                    if ( tep_db_num_rows($get_inventory_stock_r)>0 ) {
                        while( $_inventory_stock = tep_db_fetch_array($get_inventory_stock_r) ) {
                            $_inventory_stock['quantity'] = $_inventory_stock['products_quantity'];
                            $grouped_config['stock']->stock_info[$_inventory_stock['products_id']] = new StockInfo($_inventory_stock);
                        }
                    }
                }
            }
            if ( is_array($grouped_config) ) {
                $grouped_config['stock']->stock_info = array_values($grouped_config['stock']->stock_info);
                $this->products_price_stock->product_price_stock[] = new PriceAndStockInfo($grouped_config);
            }
        }

        $this->products_price_stock->build();

        parent::build();
    }
}