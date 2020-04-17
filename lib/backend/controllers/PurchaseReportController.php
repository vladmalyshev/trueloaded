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

class PurchaseReportController extends Sceleton {

    public $acl = ['BOX_HEADING_REPORTS', 'BOX_CUSTOMER_PURCHASE_REPORT'];
    
    public function __construct($id, $module = null) {        
        \common\helpers\Translation::init('admin/purchase-report');
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
        $this->selectedMenu = array('reports', 'purchase-report');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('purchase-report/index'), 'title' => HEADING_TITLE);
        $this->view->headingTitle = HEADING_TITLE;

        $platforms = platform::getList(true);

        $this->view->filters = new \stdClass();
        
        $this->start_date = isset($_GET['start_date']) ? tep_db_input(trim($_GET['start_date'])) : date(DATE_FORMAT_DATEPICKER_PHP, strtotime((date('Y-m-01'))));
        $this->end_date = isset($_GET['end_date']) ? tep_db_input(trim($_GET['end_date'])) : date(DATE_FORMAT_DATEPICKER_PHP, strtotime((date('Y-m-d'))));
        
        $by = [
            BOX_CUSTOMER => [ 
                [
                'label' => TEXT_CUSTOMER_NAME,
                'name' => 'customer_name',
                'selected' => '',
                'type' => 'text'
                ],          
                [
                'label' => TABLE_HEADING_EMAIL,
                'name' => 'email',
                'selected' => '',
                'type' => 'text'
                ],
            ],
             BOX_PRODUCT => [
                [
                'label' => TABLE_HEADING_PRODUCTS_MODEL,
                'name' => 'model',
                'selected' => '',
                'type' => 'text'
                ],
                [
                'label' => TEXT_PRODUCTS_NAME,
                'name' => 'product_name',
                'selected' => '',
                'type' => 'text'
                ],
                [
                'label' => TABLE_HEADING_MANUFACTURERS,
                'name' => 'manufacturer',
                'selected' => '',
                'type' => 'dropdown',
                'value' => array_merge([$this->getAny()], \common\helpers\Manufacturers::get_manufacturers())
                ],
            ],
            BOX_SHIPPING => [
                [
                'label' => ENTRY_SHIPPING_ADDRESS,
                'name' => 'address',
                'selected' => '',
                'type' => 'text'
                ],
                [
                'label' => TABLE_SHIPPING_SUBURB,
                'name' => 'suburb',
                'selected' => '',
                'type' => 'text'
                ],
                [
                'label' => TABLE_SHIPPING_COUNTRY,
                'name' => 'country',
                'selected' => '',
                'type' => 'dropdown',
                'value' => $this->getCoutries(),
                ],
                [
                'label' => TABLE_SHIPPING_CITY,
                'name' => 'city',
                'selected' => '',
                'type' => 'text'
                ],
                [
                'label' => TABLE_SHIPPING_STATE,
                'name' => 'state',
                'selected' => '',
                'type' => 'text'
                ],
                [
                'label' => TABLE_SHIPPING_POSTCODE,
                'name' => 'postcode',
                'selected' => '',
                'type' => 'text'
                ],
            ]
        ];
        foreach ($by as $label => $items) {
            foreach($items as $key => $item){
                if (isset($_GET[$item['name']])) {
                    if ($by[$label][$key]['type'] == 'text'){
                        $by[$label][$key]['value'] = $_GET[$item['name']];
                    } else if($by[$label][$key]['type'] == 'dropdown'){
                        $by[$label][$key]['selected'] = $_GET[$item['name']];
                    }
                    
                }
            }
            
        }
        
        $this->view->filters->by = $by;
        
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
    
    function getOrderCompleteStatus() {
        if (defined('ORDER_COMPLETE_STATUSES')) {
            $statuses = preg_split("/[, ]/", ORDER_COMPLETE_STATUSES);
            
            $statuses = is_array($statuses) ? $statuses : array();
            foreach ($statuses as $key => $value) {
                $value = trim($value);
                if (empty($value)) {
                    unset($statuses[$key]);
                } else {
                    $statuses[$key] = $value;
                }
            }
            return $statuses;
        }
        return array();
    }
    
    public function getTable(){
        return array(
            array(
                'title' => TEXT_CUSTOMER_NAME,
                'not_important' => 0,
            ),
            array(
                'title' => TABLE_HEADING_EMAIL,
                'not_important' => 0,
            ),
            array(
                'title' => ENTRY_SHIPPING_ADDRESS,
                'not_important' => 0,
            ),
            array(
                'title' => TABLE_SHIPPING_SUBURB,
                'not_important' => 0,
            ),
            array(
                'title' => TABLE_SHIPPING_COUNTRY,
                'not_important' => 0,
            ),
            array(
                'title' => TABLE_SHIPPING_CITY,
                'not_important' => 0,
            ),
            array(
                'title' => TABLE_SHIPPING_STATE,
                'not_important' => 0,
            ),
            array(
                'title' => TABLE_SHIPPING_POSTCODE,
                'not_important' => 0,
            ),
            array(
                'title' => TABLE_TOTAL_ORDERS_COUNT,
                'not_important' => 0,
            ),
            array(
                'title' => TABLE_TOTAL_ORDERS_AMOUNT,
                'not_important' => 0,
            ),
        );
    }
    
    public function build($full = false){
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
        
        $responseList = [];
        
        $itemList = array();
        if ($output['model'] != '' || $output['product_name'] != '' || $output['manufacturer'] != '') {
          $addProducts = true;
        } else {
          $addProducts = false;
        }


        $itemQuery = "SELECT distinct o.`orders_id`
            FROM `" . TABLE_ORDERS . "` o ";
        if ($addProducts ) {
         $itemQuery .= "LEFT JOIN `" . TABLE_ORDERS_PRODUCTS . "` op ON o.`orders_id` = op.`orders_id`
            LEFT JOIN `" . TABLE_PRODUCTS . "` p ON op.`products_id` = p.`products_id` ";
        }
        $itemQuery .= " LEFT JOIN `" . TABLE_CUSTOMERS . "` c ON o.`customers_id` = c.`customers_id`
            WHERE o.`orders_status` IN ('" . implode("','", $this->getOrderCompleteStatus()) . "')
            /*AND c.`customers_id` IS NOT NULL*/ ";
        
        if ($output['start_date'] != '') {
            $start_date = \common\helpers\Date::prepareInputDate(tep_db_prepare_input($output['start_date']));
            $itemQuery .= " AND o.`date_purchased` >= '" . $start_date . " 00:00:00'";
        }
        if ($output['end_date'] != '') {
            $end_date = \common\helpers\Date::prepareInputDate(tep_db_prepare_input($output['end_date']));
            $itemQuery .= " AND o.`date_purchased` < '" . $end_date ." 23:59:59'";
        }
        if ($output['model'] != '') {
            $itemQuery .= " AND op.`products_model` LIKE '%" . tep_db_input($output['model']) ."%'";
        }
        if ($output['product_name'] != '') {
            $itemQuery .= " AND op.`products_name` LIKE '%" . tep_db_input($output['product_name']). "%'";
        }
        if ($output['manufacturer'] != '') {
            $itemQuery .= " AND p.`manufacturers_id` = '{$output['manufacturer']}'";
        }
        if ($output['customer_name'] != '') {
            $itemQuery .= " AND (c.`customers_firstname` LIKE '%" . tep_db_input($output['customer_name']). "%' OR c.`customers_lastname` LIKE '%". tep_db_input($output['customer_name']) . "%')";
        }
        if ($output['email'] != '') {
            $itemQuery .= " AND c.`customers_email_address` LIKE '%" . tep_db_input($output['email']). "%'";
        }        
        $filter_by_platform = array();
        if ( isset($output['platform']) && is_array($output['platform']) ){
            foreach( $output['platform'] as $_platform_id ) if ( (int)$_platform_id>0 ) $filter_by_platform[] = (int)$_platform_id;
        }

        if ( count($filter_by_platform)>0 ) {
            $itemQuery .= " and o.platform_id IN ('" . implode("', '",$filter_by_platform). "') ";
        }
        if ($output['address'] != '') {
            $itemQuery .= " AND o.`delivery_street_address` LIKE '%" . tep_db_input($output['address']). "%'";
        }
        if ($output['suburb'] != '') {
            $itemQuery .= " AND o.`delivery_suburb` LIKE '%" . tep_db_input($output['suburb']). "%'";
        }
        if ($output['country'] != '') {
            $itemQuery .= " AND o.`delivery_country` LIKE '%" . tep_db_input($output['country']). "%'";
        }
        if ($output['city'] != '') {
            $itemQuery .= " AND o.`delivery_city` LIKE '%" . tep_db_input($output['city']). "%'";
        }
        if ($output['state'] != '') {
            $itemQuery .= " AND o.`delivery_state` LIKE '%" . tep_db_input($output['state']). "%'";
        }
        if ($output['postcode'] != '') {
            $itemQuery .= " AND o.`delivery_postcode` LIKE '%" . tep_db_input($output['postcode']). "%'";
        }
        //$itemQuery .= " GROUP BY o.`orders_id` ORDER BY o.`date_purchased` DESC, c.`customers_firstname` ASC, c.`customers_lastname` ASC";
        
        //echo $itemQuery;
        /*$itemQuery = tep_db_query($itemQuery);

        $ordersArray = array();
        while ($row = tep_db_fetch_array($itemQuery)) {
            $ordersArray[] = $row['orders_id'];
        }
        tep_db_free_result($itemQuery);
        unset($itemQuery);
        */
        
        if (isset($_GET['order'][0]['column']) && $_GET['order'][0]['dir']) {
            switch ($_GET['order'][0]['column']) {
                case 0:
                    $orderBy = " o.customers_name " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                    break;
                case 1:
                    $orderBy = "o.customers_email_address " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                    break;
                case 2:
                    $orderBy = "o.delivery_street_address " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                    break;
                case 3:
                    $orderBy = "o.delivery_suburb " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                    break;
                case 4:
                    $orderBy = "o.delivery_country " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                    break;
                case 5:
                    $orderBy = "o.delivery_city " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                    break;
                case 6:
                    $orderBy = "o.delivery_state " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                    break;
                case 7:
                    $orderBy = "o.delivery_postcode " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                    break;
                case 9:
                    $orderBy = "orderTotalValue " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir'])) . ", o.customers_name asc ";
                    break;
                default:
                    $orderBy = "o.customers_name";
                    break;
            }
        } else {
            $orderBy = "o.customers_name asc";
        }
        $currencies = Yii::$container->get('currencies');
        
        $iQuery = "select /*group_concat(o.orders_id separator ',') as `orders_id`,*/
                c.customers_id, o.`customers_lastname`, o.customers_firstname, o.`customers_email_address`,
                o.`delivery_street_address`, o.`delivery_suburb`, o.`delivery_country`, o.`delivery_city`, o.`delivery_state`, o.`delivery_postcode`,
                count(distinct o.`orders_id`) as `totalOrderCount`,
                SUM(ot.value_inc_tax) AS `orderTotalValue`
            from `" . TABLE_ORDERS . "` o
            left join `" . TABLE_CUSTOMERS . "` c on o.`customers_id` = c.`customers_id`
            left join `" . TABLE_ORDERS_TOTAL. "` ot on ot.`orders_id` = o.`orders_id` AND ot.class='ot_total'
            where o.`orders_id` in (" . $itemQuery . ")
            group by c.customers_id, o.`customers_lastname`, o.customers_firstname, o.`customers_email_address`,
                     o.`delivery_street_address`, o.`delivery_suburb`, o.`delivery_country`, o.`delivery_city`, o.`delivery_state`, o.`delivery_postcode`
            /*having 1*/
            order by " . $orderBy;

        $current_page_number = ($start / $length) + 1;
        $itemQuery_numrows = 0;
        if (!$full){
            $split = new \splitPageResults($current_page_number, $length, $iQuery, $itemQuery_numrows, ' c.customers_id, o.`customers_lastname`, o.customers_firstname, o.`customers_email_address`, o.`delivery_street_address`, o.`delivery_suburb`, o.`delivery_country`, o.`delivery_city`, o.`delivery_state`, o.`delivery_postcode`');
        }
//echo $iQuery;
        $report_query = tep_db_query($iQuery);
        $responseList = array();

        while ($row = tep_db_fetch_array($report_query)) {
            $responseList[] = array(
              $row['customers_firstname'] .  ' ' . $row['customers_lastname'],
              $row['customers_email_address'],
              $row['delivery_street_address'],
              $row['delivery_suburb'],
              $row['delivery_country'],
              $row['delivery_city'],
              $row['delivery_state'],
              $row['delivery_postcode'],
              $row['totalOrderCount'],
              $currencies->format($row['orderTotalValue'])
            );
        }
        if ($report_query) {
          tep_db_free_result($report_query);
          unset($report_query);
        }

        $iQuery = "SELECT SUM(`value`) AS `totalOrderAmount`, count(distinct orders_id) as ordersCount FROM `" . TABLE_ORDERS_TOTAL . "`
            WHERE `class` = 'ot_total' AND `orders_id` IN (" . $itemQuery . ")";
        if ($iQuery = tep_db_query($iQuery)) {
          $totalOrderAmount = tep_db_fetch_array($iQuery);
          tep_db_free_result($iQuery);
          unset($iQuery);
        }
        $responseList[] = array(
            TEXT_AMOUNT,
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            (!empty($totalOrderAmount['ordersCount'])) ? $totalOrderAmount['ordersCount'] : 0 ,
            number_format((!empty($totalOrderAmount['totalOrderAmount'])) ? $totalOrderAmount['totalOrderAmount'] : 0 , 2)
        );
    
       return ['responseList' => $responseList, 'count' =>$itemQuery_numrows,  'head' => $totalOrderAmount];
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

}
