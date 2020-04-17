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

use Yii;
use common\classes\platform;
use common\helpers\Date;

class ManufacturerSalesController extends Sceleton {

    public $acl = ['BOX_HEADING_REPORTS', 'BOX_MANUFACTURER_SALES'];
    
    public function __construct($id, $module = null) {        
        \common\helpers\Translation::init('admin/manufacturer-sales');
        parent::__construct($id, $module);
    }
    
    public function getAny(){
        return array('id' => '', 'text' => TEXT_ANY);
    }
    public function getCoutries(){
        $countryList = array($this->getAny());
        $countryQuery = tep_db_query("SELECT DISTINCT `delivery_country` FROM `" . TABLE_ORDERS . "` WHERE `orders_status` IN ('" . implode("','", $this->getOrderCompleteStatus()) . "') ORDER BY `delivery_country` ASC");
        while ($row = tep_db_fetch_array($countryQuery)) {
            $countryList[] = array('id' => $row['delivery_country'], 'text' => $row['delivery_country']);
        }
        tep_db_free_result($countryQuery);
        unset($countryQuery);
        return $countryList;
    }

    public $start_date;
    public $end_date;
    
    public function actionIndex() {
        $this->selectedMenu = array('reports', 'manufacturer-sales');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('manufacturer-sales/index'), 'title' => HEADING_TITLE);
        $this->view->headingTitle = HEADING_TITLE;

        $platforms = platform::getList(true);

        $this->view->filters = new \stdClass();
        
        $this->start_date = isset($_GET['start_date']) ? tep_db_input(trim($_GET['start_date'])) : date(DATE_FORMAT_DATEPICKER_PHP, strtotime((date('Y-m-01'))));
        $this->end_date = isset($_GET['end_date']) ? tep_db_input(trim($_GET['end_date'])) : date(DATE_FORMAT_DATEPICKER_PHP, strtotime((date('Y-m-d'))));
                
        $this->view->filters->platform = array();
        if ( isset($_GET['platform']) && is_array($_GET['platform']) ){
            foreach( $_GET['platform'] as $_platform_id ) if ( (int)$_platform_id>0 ) $this->view->filters->platform[] = (int)$_platform_id;
        }

        $this->view->filters->row = (int)$_GET['row'];
                    
        $this->view->reportTable = $this->getTable();
        return $this->render('index', [
                    'platforms' => $platforms,
                    'default_platform_id' => platform::defaultId(),
                    'isMultiPlatforms' => platform::isMulti()
		  ]);
    }
   
    
    public function getTable(){
        return array(
            array(
                'title' => TABLE_HEADING_MANUFACTURERS,
                'not_important' => 0,
            ),
            array(
                'title' => TABLE_HEADING_PRODUCTS_SOLD,
                'not_important' => 0,
            ),
            array(
                'title' => TABLE_HEADING_TOTAL_SALES,
                'not_important' => 0,
            ),            
        );
    }
    
    public function build($full = false){
        
        $draw = Yii::$app->request->get('draw', 1);
        $start = Yii::$app->request->get('start', 0);
        $length = Yii::$app->request->get('length', 10);

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
        
        $responseList = [];
        
        $currencies = Yii::$container->get('currencies');

        $order_date_filter = '';
        if ($output['start_date'] != '') {
            $start_date = \common\helpers\Date::prepareInputDate(tep_db_prepare_input($output['start_date']));
            $order_date_filter .= " AND o.date_purchased >= '" . $start_date . " 00:00:00'";
        }
        if ($output['end_date'] != '') {
            $end_date = \common\helpers\Date::prepareInputDate(tep_db_prepare_input($output['end_date']));
            $order_date_filter .= " AND o.date_purchased < '" . $end_date ." 23:59:59'";
        }
        
        $filter_by_platform = array();
        if ( isset($output['platform']) && is_array($output['platform']) ){
            foreach( $output['platform'] as $_platform_id ) if ( (int)$_platform_id>0 ) $filter_by_platform[] = (int)$_platform_id;
        }

        $search = "";
        if ( count($filter_by_platform)>0 ) {
            $search .= " and o.platform_id IN ('" . implode("', '",$filter_by_platform). "') ";
        }
        
        
        if($output['type'] == 'details'){
            $manufacturers_id = (int)Yii::$app->request->get('mID', 0);
            if ($manufacturers_id){
                $man_products_query_raw = "select op.products_id, op.products_model, op.products_name, op.products_quantity, op.final_price, SUM(op.products_quantity) as ordered_count, SUM(op.final_price*op.products_quantity) AS ordered_amount "
                        . "from " . TABLE_ORDERS_PRODUCTS . " AS op "
                        . "LEFT JOIN " . TABLE_PRODUCTS . " AS p ON op.products_id = p.products_id "
                        . "LEFT JOIN " . TABLE_ORDERS . " AS o ON op.orders_id = o.orders_id "
                        . "WHERE 1 {$order_date_filter} "
                        . "AND p.manufacturers_id = '" . $manufacturers_id . "' "
                        . "group by op.products_id ORDER BY op.products_model";

			$man_products_query = tep_db_query($man_products_query_raw);
                        $total_count = $total_amount = 0;
			while ($products = tep_db_fetch_array($man_products_query)) {
                            $responseList[] = [
                                $products['products_model'],
                                $products['products_name'],
                                $products['ordered_count'],
                                $currencies->format($products['ordered_amount']),
                            ];
                            $total_count += $products['ordered_count'];
                            $total_amount += $products['ordered_amount'];
                        }
                        $responseList[] = [
                                TEXT_AMOUNT,
                                '',
                                $total_count,
                                $currencies->format($total_amount),
                            ];
            }
        } else {
            $current_page_number = ($start / $length) + 1;
            
            if (isset($_GET['order'][0]['column']) && $_GET['order'][0]['dir']) {
                switch ($_GET['order'][0]['column']) {
                    case 0:
                        $orderBy = " m.manufacturers_name " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                        break;
                    case 1:
                        $orderBy = " ordered_count " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                        break;
                    case 2:
                        $orderBy = " ordered_amount " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                        break;
                    default:
                        $orderBy = "m.manufacturers_name " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                        break;
                }
            } else {
                $orderBy = "m.manufacturers_name asc";
            }
            
            if (isset($_GET['search']['value']) && !empty($_GET['search']['value'])){
                $search .= " and m.manufacturers_name like '%" . tep_db_input(tep_db_prepare_input($_GET['search']['value'])) . "%'";
            }
            
            $manufacturers_query_raw = "select m.manufacturers_name,  m.manufacturers_id, SUM(op.products_quantity) as ordered_count, SUM(op.final_price*op.products_quantity) AS ordered_amount "
                        . "from " . TABLE_ORDERS_PRODUCTS . " AS op "
                        . "LEFT JOIN " . TABLE_PRODUCTS . " AS p ON op.products_id = p.products_id "
                        . "LEFT JOIN " . TABLE_ORDERS . " AS o ON op.orders_id = o.orders_id "
                        . "Inner JOIN " . TABLE_MANUFACTURERS . " as m on m.manufacturers_id = p.manufacturers_id "
                        . "WHERE 1 {$order_date_filter} "
                        . $search . " group by m.manufacturers_id order by " . $orderBy;
            if (!$full){
                $split = new \splitPageResults($current_page_number, $length, $manufacturers_query_raw, $query_numrows, 'm.manufacturers_id');
            }
            
            $manufacturers_query = tep_db_query($manufacturers_query_raw);
            
            while ($manufacturers = tep_db_fetch_array($manufacturers_query)) {                
                $doubleClick = '<div data-click="' . \yii\helpers\Url::to(['manufacturer-sales/list', 'type' => 'details']) . '" data-id="' . $manufacturers['manufacturers_id'] . '">';
                $responseList[] = [
                    $doubleClick . $manufacturers['manufacturers_name'] . '</div>',
                    $manufacturers['ordered_count'],
                    $currencies->format($manufacturers['ordered_amount'])
                ];
            }
            
        }
    
       return ['responseList' => $responseList, 'count' => $query_numrows];
    }

    public function actionList() {        

        $draw = Yii::$app->request->get('draw', 1);

        $data = $this->build();
        $responseList = $data['responseList'];
                
        $response = array(
            'draw' => $draw,
            'recordsTotal' => $data['count'],
            'recordsFiltered' => $data['count'],
            'data' => $responseList,
            //'head' => $data['head']
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

}
