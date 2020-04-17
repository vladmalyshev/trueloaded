<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\controllers;

use backend\models\ProductNameDecorator;
use Yii;
use common\classes\platform;
use common\helpers\Date;
use common\api\models\AR\Products;

class StockManufacturerController extends Sceleton {

    public $showSupplierPrice = false;
    public $acl = ['BOX_HEADING_REPORTS', 'BOX_MANUFACTURER_STOCK'];
    public $inventory = (PRODUCTS_INVENTORY == 'True') ? true : false;
    
    public function __construct($id, $module = null) {        
        \common\helpers\Translation::init('admin/stock-manufacturer');
        \common\helpers\Translation::init('admin/categories');
        if (isset($_GET['ssp']) && $_GET['ssp']){
            $this->showSupplierPrice = true;
        }
        parent::__construct($id, $module);
    }
    
    public function getUnknown(){
        return [TEXT_UNKNOWN];
    }
        
    public function actionIndex() {

        $this->selectedMenu = array('reports', 'stock-manufacturer');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('stock-manufacturer/index'), 'title' => HEADING_TITLE);
        $this->view->headingTitle = HEADING_TITLE;

        $platforms = platform::getList(true);

        $this->view->filters = new \stdClass();
        if (Products::find()->where('manufacturers_id is null or manufacturers_id = 0')->exists()){
            $this->view->filters->brands = array_merge($this->getUnknown(), \common\helpers\Manufacturers::getManufacturersList());
        } else {
            $this->view->filters->brands = array_merge([], \common\helpers\Manufacturers::getManufacturersList());
        }
        $this->view->filters->selected_brands = $_GET['brand'];
        
        $this->view->filters->row = (int)$_GET['row'];
        $this->view->filters->product = $_GET['product'];
        $this->view->filters->products_id = (int)$_GET['products_id'];

        $this->view->reportTable = $this->getTable();
        return $this->render('index', [
		  ]);
    }
    
    public function getTable() {
        $table = array(
            array(
                'title' => TABLE_HEADING_PRODUCTS_MODEL,
                'not_important' => 0,
            ),
            array(
                'title' => TEXT_PRODUCT_NAME,
                'not_important' => 0,
            ),
            array(
                'title' => TABLE_HEADING_QUANTITY,
                'not_important' => 0,
            ),
            array(
                'title' => ($this->showSupplierPrice ? TEXT_SUPPLIER_PREFIX . " ": '') . TABLE_HEADING_PRODUCTS_PRICE,
                'not_important' => 0,
            ),
            array(
                'title' => ($this->showSupplierPrice ? TEXT_SUPPLIER_PREFIX . " " : '') . TEXT_INFO_VALUE,
                'not_important' => 0,
            ),            
        );
        if ($this->inventory){
            $table = array_merge( $table, 
                    [
                         array(
                            'title' => TABLE_HEADING_TOTAL . " " . TABLE_HEADING_QUANTITY,
                            'not_important' => 0,
                        ),
                        array(
                            'title' => TABLE_HEADING_TOTAL . " " . TEXT_INFO_VALUE,
                            'not_important' => 0,
                        ),
                    ]
                    );
        }
        return $table;
    }
    
    public function build($full = false){
        $languages_id = \Yii::$app->settings->get('languages_id');
        
        $draw = Yii::$app->request->get('draw', 1);
        $start = Yii::$app->request->get('start', 0);
        $length = Yii::$app->request->get('length', 10);
        $current_category_id = Yii::$app->request->get('id', 0);

        if ($length == -1)
            $length = 10000;

        $output = [];
        $formFilter = Yii::$app->request->get('filter');        
        parse_str($formFilter, $output);
        if (is_null($formFilter) && count($_GET)){
            foreach($_GET as $key => $value){
                $output[$key] = $value;
            }
        }
        
        if (isset($output['ssp']) && $output['ssp']){
            $this->showSupplierPrice = true;
        }
        
        $responseList = [];
        $brand_str = ' 1 ';
        if (isset($output['brand']) && $output['brand'] ){
            $_brands = $output['brand'];
            if (!is_array($_brands)) $_brands = [$output['brand']];
            $brand_str = " p.manufacturers_id in ('" . implode("','", $_brands) . "') ";
        }/* else {
            $brand_str = " (p.manufacturers_id='0' or p.manufacturers_id is null) ";
        }*/
        
        if (isset($output['products_id']) && $output['products_id']){
            $brand_str .= " and p.products_id = '" . (int)$output['products_id'] . "'";
        }
        $exact_date = null;
        if (isset($output['exact_date']) && !empty($output['exact_date'])){
            $exact_date = \common\helpers\Date::prepareInputDate($output['exact_date'], true);
        }
        
        $search = '';
        
        if ($this->inventory){
            if (isset($_GET['search']['value']) && tep_not_null($_GET['search']['value'])) {
                $keywords = tep_db_input(tep_db_prepare_input($_GET['search']['value']));
                $search = " and (pd.products_name like '%" . tep_db_input($keywords) . "%' or pd.products_internal_name like '%" . tep_db_input($keywords) . "%') ";
            }
            $query_raw =
                "select p.products_id, IF(LENGTH(i.products_model)>0,i.products_model,p.products_model) AS products_model, i.products_id as uprid, IF(LENGTH(i.products_name)>0, i.products_name, ".ProductNameDecorator::instance()->listingQueryExpression('pd','').") as products_name, " .
                "p.products_quantity as main_qty, ifnull(i.products_quantity, p.products_quantity) as variant_qty " . ($this->showSupplierPrice? ", ( select ifnull(min(sup.suppliers_price),0) from " . TABLE_SUPPLIERS_PRODUCTS . " sup where sup.products_id = p.products_id and (i.products_id = sup.uprid or if(length(sup.products_id)=length(sup.uprid), i.prid = sup.products_id, 0) ) ) as products_price " : ", p.products_price as products_price ") .
                ($exact_date?", sh.products_quantity_update_prefix, sh.products_quantity_before, sh.products_quantity_update, sh.prid " :"").
                "from ".TABLE_PRODUCTS." p ".
                "left join ". TABLE_INVENTORY . " i on p.products_id=i.prid and i.non_existent = 0 " .
                ($exact_date? "left join ". TABLE_STOCK_HISTORY ." sh on sh.products_id = ifnull(i.products_id, p.products_id) and sh.is_temporary = 0 and sh.date_added = (select max(sh1.date_added) from " . TABLE_STOCK_HISTORY . " sh1 where sh1.products_id = ifnull(i.products_id, p.products_id) and sh1.date_added < '{$exact_date}' and sh1.is_temporary = 0 ) ":'').
                ", " . TABLE_PRODUCTS_DESCRIPTION . " pd ".
                "where p.products_status=1 and pd.products_id = p.products_id and pd.language_id = '" . (int)$languages_id . "' and pd.platform_id = '".intval(\common\classes\platform::defaultId())."' " .
                "/*AND p.products_id NOT IN( SELECT DISTINCT sets_id FROM ".TABLE_SETS_PRODUCTS." sp, ".TABLE_PRODUCTS." spt WHERE spt.products_id=sp.sets_id AND spt.manufacturers_id='".(isset($output['brand'])?(int)$output['brand']:0)."' )*/" .
                "AND {$brand_str} {$search} " 
                . "group by i.products_id, p.products_id "
                . " order by pd.products_name, i.products_name";

            $query_total =
                "select SUM(ifnull(i.products_quantity, p.products_quantity)) as total_quantity, SUM(" .($this->showSupplierPrice? "( select ifnull(min(sup.suppliers_price),0) from " . TABLE_SUPPLIERS_PRODUCTS . " sup where sup.products_id = p.products_id and (i.products_id = sup.uprid or if(length(sup.products_id)=length(sup.uprid), i.prid = sup.products_id, 0) )) " : "p.products_price ") . " *  ifnull(i.products_quantity, p.products_quantity)  ) as total_value " .
                ($exact_date?", sh.products_quantity_update_prefix, sh.products_quantity_before, sh.products_quantity_update, sh.prid " :"").
                "from ".TABLE_PRODUCTS." p " .
                "left join ". TABLE_INVENTORY . " i on p.products_id=i.prid and i.non_existent = 0 " .
                ($exact_date? "left join ". TABLE_STOCK_HISTORY ." sh on sh.products_id = ifnull(i.products_id, p.products_id) and sh.is_temporary = 0 and sh.date_added = (select max(sh1.date_added) from " . TABLE_STOCK_HISTORY . " sh1 where sh1.products_id = ifnull(i.products_id, p.products_id) and sh1.date_added < '{$exact_date}' and sh1.is_temporary = 0 ) ":'').
                ", " . TABLE_PRODUCTS_DESCRIPTION . " pd ".
                "where p.products_status=1 and pd.products_id = p.products_id and pd.language_id = '" . (int)$languages_id . "' and pd.platform_id = '".intval(\common\classes\platform::defaultId())."' " .
                "AND p.products_id NOT IN( SELECT DISTINCT sets_id FROM ".TABLE_SETS_PRODUCTS." sp, ".TABLE_PRODUCTS." spt WHERE spt.products_id=sp.sets_id AND spt.manufacturers_id='".(isset($output['brand'])?(int)$output['brand']:0)."' )" .
                "AND {$brand_str} $search ";
                //. "group by i.products_id, p.products_id ";
            
        } else {
            if (isset($_GET['search']['value']) && tep_not_null($_GET['search']['value'])) {
                $keywords = tep_db_input(tep_db_prepare_input($_GET['search']['value']));
                $search = " and (pd.products_name like '%" . tep_db_input($keywords) . "%' or p.products_model like '%" . tep_db_input($keywords) . "%' or pd.products_internal_name like '%" . tep_db_input($keywords) . "%' ) ";
            }
            $query_raw =
                "select p.products_id, p.products_model, ".ProductNameDecorator::instance()->listingQueryExpression('pd','')." AS products_name, " .
                "p.products_quantity as main_qty, p.products_quantity as variant_qty " . ($this->showSupplierPrice? ", ( select ifnull(min(sup.suppliers_price),0) from " . TABLE_SUPPLIERS_PRODUCTS . " sup where sup.products_id = p.products_id ) as products_price " : ", p.products_price as products_price ") .
                "from ".TABLE_PRODUCTS." p ".
                ($exact_date? "left join ". TABLE_STOCK_HISTORY ." sh on sh.products_id = p.products_id and sh.is_temporary = 0 and sh.date_added = (select max(sh1.date_added) from " . TABLE_STOCK_HISTORY . " sh1 where sh1.products_id = p.products_id and sh1.date_added < '{$exact_date}' and sh1.is_temporary = 0 ) ":'').
                ", " . TABLE_PRODUCTS_DESCRIPTION . " pd ".
                "where p.products_status=1 and pd.products_id = p.products_id and pd.language_id = '" . (int)$languages_id . "' and pd.platform_id = '".intval(\common\classes\platform::defaultId())."' " .
                "AND p.products_id NOT IN( SELECT DISTINCT sets_id FROM ".TABLE_SETS_PRODUCTS." sp, ".TABLE_PRODUCTS." spt WHERE spt.products_id=sp.sets_id AND spt.manufacturers_id='".(isset($output['brand'])?(int)$output['brand']:0)."' )" .
                "AND {$brand_str} {$search} " 
                . "group by p.products_id "
                . " order by pd.products_name";

            $query_total =
                "select SUM(p.products_quantity) as total_quantity, SUM(" .($this->showSupplierPrice? "( select ifnull(min(sup.suppliers_price),0) from " . TABLE_SUPPLIERS_PRODUCTS . " sup where sup.products_id = p.products_id ) " : "p.products_price ") . " * p.products_quantity  ) as total_value " .
                "from ".TABLE_PRODUCTS." p " .
                ", " . TABLE_PRODUCTS_DESCRIPTION . " pd ".
                "where p.products_status=1 and pd.products_id = p.products_id and pd.language_id = '" . (int)$languages_id . "' and pd.platform_id = '".intval(\common\classes\platform::defaultId())."' " .
                "AND p.products_id NOT IN( SELECT DISTINCT sets_id FROM ".TABLE_SETS_PRODUCTS." sp, ".TABLE_PRODUCTS." spt WHERE spt.products_id=sp.sets_id AND spt.manufacturers_id='".(isset($output['brand'])?(int)$output['brand']:0)."' )" .
                "AND {$brand_str} $search ";
                //. "group by i.products_id, p.products_id ";
        }
               
        
        $currencies = \Yii::$container->get('currencies');

//echo $query_raw;die;
        $current_page_number = ($start / $length) + 1;
        $itemQuery_numrows = 0;
        if (!$full){            
            $itemQuery_numrows = (new \yii\db\Query)->createCommand()->setSql($query_raw)->execute();
            $query_raw .= " limit {$start}, {$length}";
        }
        
        $report_query = tep_db_query($query_raw);
        $responseList = array();    
        $total_count = 0;
        $var_total = $var_key = [];
        $pid_trigger = '';
        $_key = $__key = $_summ = 0;
        $__totals = [];
        while ($row = tep_db_fetch_array($report_query)) {
            if ($exact_date)$row['main_qty'] = 0;
            $_qty = ($exact_date ?$this->getQty($row):$row['variant_qty']);
            
            $var_total[$row['products_id']]['price'] += ($_qty * $row['products_price']);
            $var_total[$row['products_id']]['qty'] += $_qty;
            $var_key[$_key] = $row['products_id'];
            
            if ($exact_date){
               $responseList[$_key] = array(
                    $row['products_model'],
                    $row['products_name'],
                    $_qty,
                    $currencies->format($row['products_price']),
                    $currencies->format($_qty * $row['products_price']),
                    (( $pid_trigger!=$row['prid'] )?0:''),
                    (( $pid_trigger!=$row['prid'] )?0:''),
                  );
                if ( $pid_trigger!=$row['prid'] ) {
                    $pid_trigger = $row['prid'];
                } 
            } else {
                $responseList[$_key] = array(
                    $row['products_model'],
                    $row['products_name'],
                    $_qty,
                    $currencies->format($row['products_price']),
                    $currencies->format($_qty * $row['products_price']),
                    (( $pid_trigger!=$row['products_id'] )?$row['main_qty']:''),
                    (( $pid_trigger!=$row['products_id'] )?0:''),
                  );
                if ( $pid_trigger!=$row['products_id'] ) {
                    $pid_trigger = $row['products_id'];
                }
            }
            
            $_key++;
        }
        
        
        tep_db_free_result($report_query);
        unset($report_query);
        $tQty = $tVal = 0;
        foreach($responseList as $key => $rl){
            if (!$this->inventory ){
                $tQty += $var_total[$var_key[$key]]['qty'];
                $tVal += $var_total[$var_key[$key]]['price'];
                unset($responseList[$key][5]);
                unset($responseList[$key][6]);
            } else {
                if (isset($var_total[$var_key[$key]])){
                    $responseList[$key][5] = $var_total[$var_key[$key]]['qty'];
                    $responseList[$key][6] = $currencies->format($var_total[$var_key[$key]]['price']);
                    $tQty += $var_total[$var_key[$key]]['qty'];
                    $tVal += $var_total[$var_key[$key]]['price'];
                }
                unset($var_total[$var_key[$key]]);
            }
        }
        
        $itemQuery = tep_db_query($query_total);
        $totalOrderAmount = tep_db_fetch_array($itemQuery);
        if ($this->inventory){
            $responseList[] = array(
                TEXT_AMOUNT,
                '',
                '',
                '',
                '',
                $tQty,//$totalOrderAmount['total_quantity'],
                $currencies->format($tVal)
            );
        } else {
            $responseList[] = array(
                TEXT_AMOUNT,
                '',
                '',
                $tQty,
                $currencies->format($tVal)
            );
        }
        tep_db_free_result($itemQuery);
        unset($itemQuery);
    
       return ['responseList' => $responseList, 'count' =>$itemQuery_numrows,  'head' => $totalOrderAmount];
    }
    
    private function getQty(&$row){
        $value = 0;
        if ($row['products_quantity_update_prefix'] == '-'){
            $value = $row['products_quantity_before'] - $row['products_quantity_update'];
        } else {
            $value = $row['products_quantity_before'] + $row['products_quantity_update'];
        }
        $row['main_qty'] += $value;
        return $value;
    }

    public function actionList() {        

        $draw = Yii::$app->request->get('draw', 1);
        $start = Yii::$app->request->get('start', 0);
        $length = Yii::$app->request->get('length', 10);
        $current_page_number = ($start / $length) + 1;
        $platform_id = Yii::$app->request->get('platform_id');

        $data = $this->build();
        $responseList = $data['responseList'];
                
        $response = array(
            'draw' => $draw,
            'recordsTotal' => $data['count'],
            'recordsFiltered' => $data['count'],
            'data' => $responseList,
            'head' => $data['head']
        );
        echo json_encode($response);
    }

    public function actionExport() {
        $data = $this->build(true);
        $head = $this->getTable();
        
        $writer = new \backend\models\EP\Formatter\CSV('write', array(), 'expo.csv');
        $a = [];
        foreach($head as $m){
          $a[] = $m['title'];
        }        
        $writer->write_array($a);
        
        foreach($data['responseList'] as $row){
            $newArray = array_map(function($v){
                $vv = trim(strip_tags($v));
                $vv = str_replace(['&nbsp;&raquo;&nbsp;', '&nbsp;', ], [' / ', '', ], $vv);
                return $vv;
            }, $row);
             $writer->write_array($newArray);
        }
        exit();
    }
    
    public function actionSeacrhProduct(){
        $languages_id = \Yii::$app->settings->get('languages_id');
        $seacrh = Yii::$app->request->get('search', null);
        
        if (!empty($seacrh)){
            $searchBuilder = new \common\components\SearchBuilder('simple');
            $searchBuilder->setSearchInDesc(false);
            $searchBuilder->searchInProperty = false;
            $searchBuilder->searchInAttributes = false;
            $searchBuilder->prepareRequest($seacrh);
            $filters_where = $searchBuilder->getProductsArray(false);
        
            $productsQuery = \common\models\Products::find()
                    ->distinct()->alias('p')
                    ->select(['p.products_id', 'pd1.*', 'if(length(pd1.products_name), pd1.products_name, pd.products_name) as products_name'])
                    ->joinWith('manufacturer m')
                    ->joinWith('productsDescriptions pd');

            $productsQuery->joinWith([
                'productsDescriptions pd1' => function($query) use ($languages_id) {
                    $query->onCondition('pd1.language_id = ' . (int) $languages_id . ' and pd1.platform_id = ' . (int)\common\classes\platform::defaultId());
                }
                    ])
                    ->andWhere('pd.products_id = p.products_id and pd.language_id = ' . (int) $languages_id . ' and pd.platform_id = "' . \common\classes\platform::defaultId() . '"')
                    ->andWhere($filters_where);
            
            $products = [];
            foreach ($productsQuery->all() as $product){
                $products[] = [
                    'id' => $product->products_id,
                    'text' => $product->productsDescriptions[0]->products_name,
                    ];
            }
            echo json_encode($products);
            exit;
        }        
        
    }
}
