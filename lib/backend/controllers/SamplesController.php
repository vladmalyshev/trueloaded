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
use common\classes\platform_config;
use common\classes\platform;
use common\classes\shopping_cart;
use common\classes\order_total;
use common\classes\shipping;
use common\classes\payment;
use common\components\Customer;
use backend\models\AdminCarts;
use common\helpers\Acl;
use common\helpers\Status;
use Yii;

/**
 * default controller to handle user requests.
 */
class SamplesController extends Sceleton {

    public $acl = ['BOX_HEADING_CUSTOMERS', 'BOX_CUSTOMERS_SAMPLES'];
    public $table_prefix = 'sample_';
    
    /**
     * Index action is the default action in a controller.
     */
	public function __construct($id, $module=''){
            if ($ext = \common\helpers\Acl::checkExtension('BusinessToBusiness', 'checkCustomerGroups')) {
                $ext::checkCustomerGroups();
            }
            define('GROUPS_IS_SHOW_PRICE', true);
            define('GROUPS_DISABLE_CHECKOUT', false);
            define('SHOW_OUT_OF_STOCK', 1);
            parent::__construct($id, $module);
	}
    public function actionIndex() {

        $this->selectedMenu = array('customers', 'samples');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('samples/index'), 'title' => HEADING_TITLE);
        $this->view->headingTitle = HEADING_TITLE;
        $this->view->ordersTable = array(
            array(
                'title' => '<input type="checkbox" class="uniform">',
                'not_important' => 2
            ),
            array(
                'title' => TABLE_HEADING_CUSTOMERS,
            ),
            array(
                'title' => TABLE_HEADING_ORDER_TOTAL,
                'not_important' => 0
            ),
            array(
                'title' => TABLE_HEADING_DETAILS,
                'not_important' => 0
            ),   
            array(
                'title' => TABLE_HEADING_DATE_PURCHASED,
                'not_important' => 0
            ),
            array(
                'title' => TABLE_HEADING_STATUS,
                'not_important' => 1
            ),
                /* array(
                  'title' => TABLE_HEADING_ACTION,
                  'not_important' => 0
                  ), */
        );		


        $this->view->filters = new \stdClass();
        
        $by = [
            [
                'name' => TEXT_ANY,
                'value' => '',
                'selected' => '',
            ],
            [
                'name' => TEXT_ORDER_ID,
                'value' => 'oID',
                'selected' => '',
            ],
            [
                'name' => TEXT_CUSTOMER_ID,
                'value' => 'cID',
                'selected' => '',
            ],
            [
                'name' => TEXT_MODEL,
                'value' => 'model',
                'selected' => '',
            ],
            [
                'name' => TEXT_PRODUCT_NAME,
                'value' => 'name',
                'selected' => '',
            ],
            /*[
                'name' => 'Brand',
                'value' => 'brand',
                'selected' => '',
            ],*/
            [
                'name' => TEXT_CLIENT_NAME,
                'value' => 'fullname',
                'selected' => '',
            ],
            [
                'name' => TEXT_CLIENT_EMAIL,
                'value' => 'email',
                'selected' => '',
            ],
        ];
        foreach ($by as $key => $value) {
            if (isset($_GET['by']) && $value['value'] == $_GET['by']) {
                $by[$key]['selected'] = 'selected';
            }
        }
        $this->view->filters->by = $by;
        
        $search = '';
        if (isset($_GET['search'])) {
            $search = $_GET['search'];
        }
        $this->view->filters->search = $search;
        
        if (isset($_GET['date']) && $_GET['date'] == 'exact') {
            $this->view->filters->presel = false;
            $this->view->filters->exact = true;
        } else {
            $this->view->filters->presel = true;
            $this->view->filters->exact = false;
        }
        
        $interval = [
            [
                'name' => TEXT_ALL,
                'value' => '',
                'selected' => '',
            ],
            [
                'name' => TEXT_TODAY,
                'value' => '1',
                'selected' => '',
            ],
            [
                'name' => TEXT_WEEK,
                'value' => 'week',
                'selected' => '',
            ],
            [
                'name' => TEXT_THIS_MONTH,
                'value' => 'month',
                'selected' => '',
            ],
            [
                'name' => TEXT_THIS_YEAR,
                'value' => 'year',
                'selected' => '',
            ],
            [
                'name' => TEXT_LAST_THREE_DAYS,
                'value' => '3',
                'selected' => '',
            ],
            [
                'name' => TEXT_LAST_SEVEN_DAYS,
                'value' => '7',
                'selected' => '',
            ],
            [
                'name' => TEXT_LAST_FOURTEEN_DAYS,
                'value' => '14',
                'selected' => '',
            ],
            [
                'name' => TEXT_LAST_THIRTY_DAYS,
                'value' => '30',
                'selected' => '',
            ],
        ];
        foreach ($interval as $key => $value) {
            if (isset($_GET['interval']) && $value['value'] == $_GET['interval']) {
                $interval[$key]['selected'] = 'selected';
            }
        }
        $this->view->filters->interval = $interval;

	    $status = Status::getStatusListByTypeName('Samples', $_GET['status'] ?? '');

        $this->view->filters->status = $status;
        
        $payments = [];
        $payments[] = [
                'name' => TEXT_ANY,
                'value' => '',
                'selected' => '',
            ];
        $payment_method_query = tep_db_query("select payment_method from " . $this->table_prefix . TABLE_ORDERS. " where 1 group by payment_method order by payment_method");
        while ($payment_method = tep_db_fetch_array($payment_method_query)) {
            $payments[] = [
                'name' => $payment_method['payment_method'],
                'value' => $payment_method['payment_method'],
                'selected' => '',
            ];
        }
        foreach ($payments as $key => $value) {
            if (isset($_GET['payments']) && $value['value'] == $_GET['payments']) {
                $payments[$key]['selected'] = 'selected';
            }
        }
        $this->view->filters->payments = $payments;
        
        $delivery_country = '';
        if (isset($_GET['delivery_country'])) {
            $delivery_country = $_GET['delivery_country'];
        }
        $this->view->filters->delivery_country = $delivery_country;
        
        $delivery_state = '';
        if (in_array(ACCOUNT_STATE, ['required', 'required_register', 'visible', 'visible_register'])) {
          $this->view->showState = true;
        } else {
            $this->view->showState = false;
        }
        if (isset($_GET['delivery_state'])) {
            $delivery_state = $_GET['delivery_state'];
        }
        $this->view->filters->delivery_state = $delivery_state;
        
        $from = '';
        if (isset($_GET['from'])) {
            $from = $_GET['from'];
        }
        $this->view->filters->from = $from;
        
        $to = '';
        if (isset($_GET['to'])) {
            $to = $_GET['to'];
        }
        $this->view->filters->to = $to;
        
        $this->view->filters->row = (int)$_GET['row'];

        $this->view->filters->platform = array();
        if ( isset($_GET['platform']) && is_array($_GET['platform']) ){
          foreach( $_GET['platform'] as $_platform_id ) if ( (int)$_platform_id>0 ) $this->view->filters->platform[] = (int)$_platform_id;
        }

        if ($ext = \common\helpers\Acl::checkExtension('Samples', 'adminActionIndex')) {
            return $ext::adminActionIndex();
        }

        return $this->render('index');
    }

    public function actionSamplesList() {
        $languages_id = \Yii::$app->settings->get('languages_id');
      
      \common\helpers\Translation::init('admin/samples');
		
        $draw = Yii::$app->request->get('draw');
        $start = Yii::$app->request->get('start');
        $length = Yii::$app->request->get('length');
        
        if( $length == -1 ) $length = 10000;
		
        $_session = Yii::$app->session;

        $search = '';
        if (isset($_GET['search']['value']) && tep_not_null($_GET['search']['value'])) {
            $keywords = tep_db_input(tep_db_prepare_input($_GET['search']['value']));
            $search_condition = " and (o.customers_lastname like '%" . $keywords . "%' or o.customers_firstname like '%" . $keywords . "%' or o.customers_email_address like '%" . $keywords . "%' or o.orders_id='" . $keywords . "' or op.products_model like '%" . tep_db_input($keywords) . "%' or op.products_name like '%" . tep_db_input($keywords) . "%') ";
        } else {
            $search_condition = "";
        }
        $_session->set('search_condition', $search_condition);
        
        $formFilter = Yii::$app->request->get('filter');
        parse_str($formFilter, $output);

        $filter = '';

        $filter_by_platform = array();
        if ( isset($output['platform']) && is_array($output['platform']) ){
          foreach( $output['platform'] as $_platform_id ) if ( (int)$_platform_id>0 ) $filter_by_platform[] = (int)$_platform_id;
        }

        if ( count($filter_by_platform)>0 ) {
          $filter .= " and o.platform_id IN ('" . implode("', '",$filter_by_platform). "') ";
        }

        if (tep_not_null($output['search']))
        {
            $search = tep_db_prepare_input($output['search']);
            switch ($output['by']) {
                case 'cID':
                  $filter .= " and o.customers_id = '" . (int)$search . "' ";
                  break;
                case 'oID':
                  $filter .= " and o.orders_id = '" . (int)$search . "' ";
                  break;
                case 'model': default:
                  $filter .= " and op.products_model like '%" . tep_db_input($search) . "%' ";
                  break;
                case 'name':
                  $filter .= " and op.products_name like '%" . tep_db_input($search) . "%' ";
                  break;
                case 'brand':
                  break;
                case 'fullname':
                  $filter .= " and o.customers_name like '%" . tep_db_input($search) . "%' ";
                  break;
                case 'email':
                  $filter .= " and o.customers_email_address like '%" . tep_db_input($search) . "%' ";
                  break;
                case '':
                case 'any':
                    $filter .= " and (";
                    $filter .= " o.orders_id = '" . tep_db_input($search) . "' ";
                    $filter .= " or op.products_model like '%" . tep_db_input($search) . "%' ";
                    $filter .= " or op.products_name like '%" . tep_db_input($search) . "%' ";
                    $filter .= " or o.customers_name like '%" . tep_db_input($search) . "%' ";
                    $filter .= " or o.customers_email_address like '%" . tep_db_input($search) . "%' ";
                    $filter .= ") ";
                  break;
            }
        }
        if (tep_not_null($output['delivery_country'])) {
          $filter .= " and o.delivery_country='".tep_db_input($output['delivery_country'])."'";
        }
        if (tep_not_null($output['delivery_state'])) {
          $filter .= " and o.delivery_state='".tep_db_input($output['delivery_state'])."'";
        }
        if (tep_not_null($output['status'])) {
            list($type, $itemId) = explode("_", $output['status']);
            switch ($type) {
                case 'group':
                    $filter .= " and s.orders_status_groups_id = '" . (int)$itemId . "' ";
                    break;
                case 'status':
                    $filter .= " and s.orders_status_id = '" . (int)$itemId . "' ";
                    break;

                default:
                    break;
            }
            
            
          
        }
        if (tep_not_null($output['date'])) {
          switch ($output['date']) {
          case 'exact':
            if (tep_not_null($output['from'])) {
              $from = tep_db_prepare_input($output['from']);
              $filter .= " and to_days(o.date_purchased) >= to_days('" . \common\helpers\Date::prepareInputDate($from) . "')";
            }
            if (tep_not_null($output['to'])) {
              $to = tep_db_prepare_input($output['to']);
              $filter .= " and to_days(o.date_purchased) <= to_days('" . \common\helpers\Date::prepareInputDate($to) . "')";
            }
            break;
          case 'presel':
            if (tep_not_null($output['interval'])) {
                switch ($output['interval']) {
                    case 'week':
                        $filter .= " and o.date_purchased >= '" . date('Y-m-d', strtotime('monday this week')) . "'";
                        break;
                    case 'month':
                        $filter .= " and o.date_purchased >= '" . date('Y-m-d', strtotime('first day of this month')) . "'";
                        break;
                    case 'year':
                        $filter .= " and o.date_purchased >= '" . date("Y")."-01-01" . "'";
                        break;
                    case '1':
                        $filter .= " and o.date_purchased >= '" . date('Y-m-d') . "'";
                        break;
                    case '3':
                    case '7':
                    case '14':
                    case '30':
                        $filter .= " and o.date_purchased >= date_sub(now(), interval " . (int)$output['interval'] . " day)";
                        break;
                }
            }
            break;
          }
        }
        
        if (tep_not_null($output['payments'])) {
            $filter .= " and o.payment_method='".tep_db_input($output['payments'])."'";
        }
        
        if (isset($_GET['order'][0]['column']) && $_GET['order'][0]['dir'] && $_GET['draw'] != 1) {
            switch ($_GET['order'][0]['column']) {
                case 0:
                    $orderBy = "o.customers_name " . tep_db_prepare_input($_GET['order'][0]['dir']);
                    break;
                case 1:
                    $orderBy = "ot.text " . tep_db_prepare_input($_GET['order'][0]['dir']);
                    break;
                case 2:
                    $orderBy = "o.date_purchased " . tep_db_prepare_input($_GET['order'][0]['dir']);
                    break;
                case 3:
                    $orderBy = "s.orders_status_name " . tep_db_prepare_input($_GET['order'][0]['dir']);
                    break;
                default:
                    $orderBy = "o.orders_id desc";
                    break;
            }
        } else {
            $orderBy = "o.orders_id desc";
        }
		
		$_session->set('filter', $filter);

        $orders_query_raw = "select o.*, c.customers_gender, s.orders_status_name, sg.orders_status_groups_name, sg.orders_status_groups_color " . ((tep_not_null($_GET['in_stock']) && $_GET['in_stock']!='') ? ", BIT_AND(" . (PRODUCTS_INVENTORY == 'True' ? "if(i.products_quantity is not null,if((i.products_quantity>=op.products_quantity),1,0),if((p.products_quantity>=op.products_quantity),1,0))" :"if((p.products_quantity>=op.products_quantity),1,0)") . ") as in_stock " : '') . " from " . TABLE_ORDERS_STATUS . " s, " . TABLE_ORDERS_STATUS_GROUPS  . " sg, " . $this->table_prefix . TABLE_ORDERS . " o left join " . $this->table_prefix . TABLE_ORDERS_PRODUCTS . " op on (op.orders_id = o.orders_id) " . ((tep_not_null($_GET['in_stock']) && $_GET['in_stock']!='') ? "left join " . TABLE_PRODUCTS . " p on (p.products_id = op.products_id) " . (PRODUCTS_INVENTORY == 'True' ? " left join " . TABLE_INVENTORY . " i on (i.prid = op.products_id and i.products_id = op.uprid) " : '') : '') . " LEFT JOIN  ".TABLE_CUSTOMERS." c on (o.customers_id = c.customers_id) where o.orders_status = s.orders_status_id " .  $search_condition . " and s.language_id = '" . (int)$languages_id . "' and s.orders_status_groups_id = sg.orders_status_groups_id and sg.language_id = '" . (int)$languages_id . "' " . $filter . " group by o.orders_id " . ((tep_not_null($_GET['in_stock']) && $_GET['in_stock']!='') ? " having in_stock " . ($_GET['in_stock']>0 ? " > 0" : " < 1") : '') . " order by " . $orderBy;

        $current_page_number = ($start / $length) + 1;
        $orders_split = new \splitPageResults($current_page_number, $length, $orders_query_raw, $orders_query_numrows, 'o.orders_id');
        $orders_query = tep_db_query($orders_query_raw);
        $responseList = array();
        $stack = [];
        while ($orders = tep_db_fetch_array($orders_query)) {
            $products_query = tep_db_query("select * from " . $this->table_prefix . TABLE_ORDERS_PRODUCTS . " where orders_id = '" . (int)$orders['orders_id'] . "'");
            $p_list = '';
            while ($products = tep_db_fetch_array($products_query)) {
              $p_list_tmp = '<div class="ord-desc-row"><div>' . $products['products_quantity'] . ' x ' . (strlen($products['products_name']) > 48 ? mb_substr(strip_tags($products['products_name']), 0, 48) . '...' : $products['products_name']) . '</div><div class="order_pr_model">' . 'SKU: ' . (strlen($products['products_model']) > 8 ? substr($products['products_model'], 0, 8) . '...' : $products['products_model']) . ($products['products_model'] ? '<span>' . $products['products_model'] . '</span>' : '') . '</div></div>';
              $p_list .= $p_list_tmp;
            }

            $customers_email_address = $orders['customers_email_address'];
            $w = preg_quote(trim($search));
            if (!empty($w)) {
                $regexp = "/($w)(?![^<]+>)/i";
                $replacement = '<b style="color:#ff0000">\\1</b>';
                $orders['customers_name'] = preg_replace ($regexp,$replacement ,$orders['customers_name']);
                $p_list = preg_replace ($regexp,$replacement ,$p_list);
                $customers_email_address = preg_replace ($regexp,$replacement ,$orders['customers_email_address']);
            }

            $responseList[] = array(
                '<input type="checkbox" class="uniform">' . '<input class="cell_identify" type="hidden" value="' . $orders['orders_id'] . '">',
                '<div class="ord-name ord-gender ord-gender-'.$orders['customers_gender'].' click_double" data-click-double="' . \Yii::$app->urlManager->createUrl(['samples/process-samples', 'orders_id' => $orders['orders_id']]) . '"><a href="' . \Yii::$app->urlManager->createUrl(['customers/customeredit', 'customers_id' => $orders['customers_id']]) . '">'.$orders['customers_name'] .'</a></div><a href="mailto:'.$orders['customers_email_address'] .'" class="ord-name-email">'  . $customers_email_address.'</a><div class="ord-location" style="margin-top: 5px;">'.$orders['customers_postcode'].'<div class="ord-total-info ord-location-info"><div class="ord-box-img"></div><b>'.$orders['customers_name'].'</b>'.$orders['customers_street_address'].'<br>'.$orders['customers_city'].', '.$orders['customers_state']. '&nbsp;' .$orders['customers_postcode'].'<br>'.$orders['customers_country'].'</div></div>',
                $orders['info'],
                '<div class="ord-desc-tab click_double" data-click-double="' . \Yii::$app->urlManager->createUrl(['samples/process-samples', 'orders_id' => $orders['orders_id']]) . '"><a href="' . \Yii::$app->urlManager->createUrl(['samples/process-samples', 'orders_id' => $orders['orders_id']]) . '"><span class="ord-id">' . TEXT_ORDER_NUM . $orders['orders_id'] . ($orders['admin_id'] > 0 ? '&nbsp;by admin' : (\common\classes\platform::isMulti() >= 0 ? '&nbsp;' . TEXT_FROM . ' ' . \common\classes\platform::name($orders['platform_id']) : '')) . (tep_not_null($orders['payment_method']) ? ' ' . TEXT_VIA . ' ' . strip_tags($orders['payment_method']) : '') . '</span></a>' . $p_list . '</div>',
                '<div class="ord-date-purch click_double" data-click-double="' . \Yii::$app->urlManager->createUrl(['samples/process-samples', 'orders_id' => $orders['orders_id']]) . '">'.\common\helpers\Date::datetime_short($orders['date_purchased']),
                '<div class="ord-status click_double" data-click-double="' . \Yii::$app->urlManager->createUrl(['samples/process-samples', 'orders_id' => $orders['orders_id']]) . '"><span><i style="background: '.$orders['orders_status_groups_color'].';"></i>'.$orders['orders_status_groups_name'].'</span><div>'.$orders['orders_status_name'].'</div></div>'
            );
        }

        $response = array(
            'draw' => $draw,
            'recordsTotal' => $orders_query_numrows,
            'recordsFiltered' => $orders_query_numrows,
            'data' => $responseList
        );
        echo json_encode($response, JSON_PARTIAL_OUTPUT_ON_ERROR);
        //die();
    }

    public function actionSamplesActions() {
        
        \common\helpers\Translation::init('admin/samples');
        \common\helpers\Translation::init('admin/orders');

        $this->layout = false;
        $orders_id = Yii::$app->request->post('orders_id');

        $orders_query = tep_db_query("select o.*, s.orders_status_name from " . TABLE_ORDERS_STATUS . " s, " . $this->table_prefix . TABLE_ORDERS . " o where o.orders_id = '" . (int) $orders_id . "'");
        $orders = tep_db_fetch_array($orders_query);
        
        if (!is_array($orders)) {
            die("Please select samples.");
        }
        
        $oInfo = new \objectInfo($orders);
        
        echo '<div class="or_box_head">'.TEXT_ORDER_NUM . $oInfo->orders_id . '</div>';
        echo '<div class="row_or"><div>' . TEXT_DATE_ORDER_CREATED . '</div><div>' . \common\helpers\Date::datetime_short($oInfo->date_purchased).'</div></div>';
        if (tep_not_null($oInfo->last_modified)) echo '<div class="row_or"><div>'. TEXT_DATE_ORDER_LAST_MODIFIED . '</div><div>' . \common\helpers\Date::date_short($oInfo->last_modified) . '</div></div>';
        //echo '<div class="row_or"><div>'.TEXT_INFO_PAYMENT_METHOD . '</div><div>'  . $oInfo->payment_method .'</div></div>';
        if ($oInfo->child_id > 0) echo '<div class="row_or"><div>'.TEXT_ORDER_ID . ':</div><div>'  . $oInfo->child_id .'</div></div>';
        //<a class="btn btn-no-margin btn-edit" href="' . \Yii::$app->urlManager->createUrl(['samples/order-edit', 'orders_id' => $oInfo->orders_id]) . '">' . IMAGE_EDIT . '</a>
        echo '<div class="btn-toolbar btn-toolbar-order"><a class="btn btn-primary btn-process-order" href="' . \Yii::$app->urlManager->createUrl(['samples/process-samples', 'orders_id' => $oInfo->orders_id]) . '">' . TEXT_PROCESS_SAMPLES_BUTTON . '</a><span class="disable_wr"><span class="dis_popup"><span class="dis_popup_img"></span><span class="dis_popup_content">' . TEXT_COMPLITED . '</span></span></span><button class="btn btn-delete btn-process-order" onclick="confirmDeleteOrder(' . $oInfo->orders_id . ')">' . IMAGE_DELETE . '</button></div>';
    }
        
    public function actionSubmitSamples() {
        global $login_id;
        $languages_id = \Yii::$app->settings->get('languages_id');

        $this->layout = false;

        \common\helpers\Translation::init('admin/samples');

        $oID = (int) Yii::$app->request->post('orders_id');
        $status = (int) Yii::$app->request->post('status');
        $comments = Yii::$app->request->post('comments');
        
        $order_updated = false;

        $orders_statuses = array();
        $orders_status_array = array();
        $orders_status_query = tep_db_query("select orders_status_id, orders_status_name from " . TABLE_ORDERS_STATUS . " where language_id = '" . (int) $languages_id . "'");
        while ($orders_status = tep_db_fetch_array($orders_status_query)) {
            $orders_statuses[] = array('id' => $orders_status['orders_status_id'],
                'text' => $orders_status['orders_status_name']);
            $orders_status_array[$orders_status['orders_status_id']] = $orders_status['orders_status_name'];
        }

        $check_status_query = tep_db_query("select * from " . $this->table_prefix . TABLE_ORDERS . " where orders_id = '" . (int) $oID . "'");
        if (!tep_db_num_rows($check_status_query)) {
            die("Wrong samples data.");
        }
        $check_status = tep_db_fetch_array($check_status_query);
        
        if (($check_status['orders_status'] != $status) || $comments != '') {
            
            if ($check_status['orders_status'] != $status) {
                
                if ($status == \common\helpers\Quote::getStatus('Processed')) {
                    $quote = new \common\extensions\Samples\Sample($oID);
                    $quote->createOrder();
                }
                
            }
            
            tep_db_query("update " . $this->table_prefix . TABLE_ORDERS . " set orders_status = '" . tep_db_input($status) . "', last_modified = now() where orders_id = '" . (int) $oID . "'");

            $customer_notified = '0';
            if (isset($_POST['notify']) && ($_POST['notify'] == 'on')) {

                $platform_config = Yii::$app->get('platform')->config($check_status['platform_id']);

                $notify_comments = '';
                if (isset($_POST['notify_comments']) && ($_POST['notify_comments'] == 'on')) {
                    $notify_comments = trim(sprintf(EMAIL_TEXT_COMMENTS_UPDATE, $comments)) . "\n\n";
                }

                $eMail_store = $platform_config->const_value('STORE_NAME');
                $eMail_address = $platform_config->const_value('STORE_OWNER_EMAIL_ADDRESS');
                $eMail_store_owner = $platform_config->const_value('STORE_OWNER');

                // {{
                $email_params = array();
                $email_params['STORE_NAME'] = $eMail_store;
                $email_params['ORDER_NUMBER'] = $oID;
                $email_params['ORDER_INVOICE_URL'] = \common\helpers\Output::get_clickable_link(tep_catalog_href_link('account/historyinfo', 'order_id=' . $oID, 'SSL'));
                $email_params['ORDER_DATE_LONG'] = \common\helpers\Date::date_long($check_status['date_purchased']);
                $email_params['ORDER_COMMENTS'] = $notify_comments;
                $email_params['NEW_ORDER_STATUS'] = $orders_status_array[$status];

                $emailTemplate = '';
                $ostatus = tep_db_fetch_array(tep_db_query("select orders_status_template from " . TABLE_ORDERS_STATUS . " where language_id = '" . (int) $languages_id . "' and orders_status_id='" . (int) $status . "'"));
                if (!empty($ostatus['orders_status_template'])) {
                    $get_template_r = tep_db_query("select * from " . TABLE_EMAIL_TEMPLATES . " where email_templates_key='" . $ostatus['orders_status_template'] . "'");
                    if (tep_db_num_rows($get_template_r) > 0) {
                        $emailTemplate = $ostatus['orders_status_template'];
                    }
                }
                if(!empty($emailTemplate)) {
                    list($email_subject, $email_text) = \common\helpers\Mail::get_parsed_email_template($emailTemplate, $email_params, -1, $check_status['platform_id']);
                    \common\helpers\Mail::send($check_status['customers_name'], $check_status['customers_email_address'], $email_subject, $email_text, $eMail_store_owner, $eMail_address);
                    $customer_notified = '1';
                }
            }
            
            tep_db_perform($this->table_prefix . TABLE_ORDERS_STATUS_HISTORY, array(
                'orders_id' => (int) $oID,
                'orders_status_id' => (int) $status,
                'date_added' => 'now()',
                'customer_notified' => $customer_notified,
                'comments' => $comments,
                'admin_id' => $login_id,
            ));
            
            $order_updated = true;
        }
        
        if ($order_updated == true) {
            $messageType = 'success';
            $message = SUCCESS_ORDER_UPDATED;
        } else {
            $messageType = 'warning';
            $message = WARNING_ORDER_NOT_UPDATED;
        }
        
        
?>
        <div class="popup-box-wrap pop-mess">
            <div class="around-pop-up"></div>
            <div class="popup-box">
                <div class="pop-up-close pop-up-close-alert"></div>
                <div class="pop-up-content">
                    <div class="popup-heading"><?php echo TEXT_NOTIFIC; ?></div>
                    <div class="popup-content pop-mess-cont pop-mess-cont-<?= $messageType?>">
                        <?= $message?>
                    </div>  
                </div>   
                 <div class="noti-btn">
                    <div></div>
                    <div><span class="btn btn-primary"><?php echo TEXT_BTN_OK;?></span></div>
                </div>
            </div>  
            <script>
                //$('body, html').scrollTop(0);
                $('.popup-box-wrap.pop-mess').css('top',(window.scrollY+200)+'px');
                $('.pop-mess .pop-up-close-alert, .noti-btn .btn').click(function(){
                $(this).parents('.pop-mess').remove();
            });
        </script>
        </div>
        
<?php
        return $this->actionProcessSamples();        
    }

    public function actionProcessSamples() {
        
        $languages_id = \Yii::$app->settings->get('languages_id');

        \common\helpers\Translation::init('admin/samples');
        \common\helpers\Translation::init('admin/orders');

        $this->selectedMenu = array('customers', 'samples');
        
        if (Yii::$app->request->isPost) {
            $oID = (int)Yii::$app->request->post('orders_id');
        } else {
            $oID = (int)Yii::$app->request->get('orders_id');
        }

        $orders_query = tep_db_query("select orders_id, orders_id from " . $this->table_prefix . TABLE_ORDERS . " where orders_id = '" . (int)$oID . "'");
        if (!tep_db_num_rows($orders_query)) {
            return $this->redirect(\Yii::$app->urlManager->createUrl(['samples/', 'by' => 'oID', 'search' => (int)$oID]));
        }

        ob_start();

        $orders_statuses = array();
        $orders_status_array = array();
        $orders_status_group_array = array();
        $orders_status_query = tep_db_query("select os.orders_status_id, os.orders_status_name, osg.orders_status_groups_name, osg.orders_status_groups_color, os.automated from " . TABLE_ORDERS_STATUS . " as os left join " . TABLE_ORDERS_STATUS_GROUPS . " as osg ON os.orders_status_groups_id = osg.orders_status_groups_id where os.language_id = '" . (int)$languages_id . "' and osg.language_id = '" . (int)$languages_id . "' and osg.orders_status_groups_id = '".\common\helpers\Sample::getStatusGroup()."'");
        while ($orders_status = tep_db_fetch_array($orders_status_query)){
          if ($orders_status['automated'] == 0) {
            $orders_statuses[] = array('id' => $orders_status['orders_status_id'],
                                     'text' => $orders_status['orders_status_name']);
          }
          $orders_status_array[$orders_status['orders_status_id']] = $orders_status['orders_status_name'];
          $orders_status_group_array[$orders_status['orders_status_id']] = '<i style="background: ' . $orders_status['orders_status_groups_color'] . ';"></i>' . $orders_status['orders_status_groups_name'];
        }

        $currencies = Yii::$container->get('currencies');

        $order = new \common\extensions\Samples\Sample($oID);

        $zoom_val = !isset($order->delivery['country']['zoom']) ? 8 : $order->delivery['country']['zoom'];
        $country_title = !isset($order->delivery['country']['title']) ? '' : $order->delivery['country']['title'];
?>
<?php echo tep_draw_form('status', FILENAME_ORDERS, \common\helpers\Output::get_all_get_params(array('action')) . 'action=update_order', 'post', 'id="status_edit" onSubmit="return check_form();"'); ?>

<?php /*if (($order->info['orders_status']==DEFAULT_ORDERS_STATUS_ID) && ($order->info['transaction_id']==0) && !tep_session_is_registered("login_affiliate")) echo '<a href="' . \Yii::$app->urlManager->createUrl(['orders/order-edit', 'orders_id' => $oID]) . '" class="btn btn-no-margin btn-edit">' . IMAGE_EDIT . '</a> &nbsp; ';*/ ?>

<div class="widget box box-no-shadow">
    <div class="widget-header widget-header-address">
        <h4><?php echo T_ADD_DET; ?></h4>
        <div class="toolbar no-padding">
            <div class="btn-group">
                <span id="orders_list_collapse" class="btn btn-xs widget-collapse"><i class="icon-angle-down"></i></span>
            </div>
        </div>
    </div>
    <div id="order_management_data" class="widget-content fields_style">
        <div class="pr-add-det-wrapp after <?php echo ($order->delivery['address_book_id'] == $order->billing['address_book_id'] ? 'pr-add-det-wrapp2' : '') ?>">
            <div class="pr-add-det-box pr-add-det-box01 after">
<?php
if ($order->delivery['address_book_id'] != $order->billing['address_book_id']) {
?>           
                    <div class="cr-ord-cust">
                        <span><?php echo T_CUSTOMER; ?></span>
                        <div><?php echo '<a href="'.Yii::$app->urlManager->createUrl(['customers/customeredit?customers_id='.$order->customer['customer_id']]).'">'.\common\helpers\Address::address_format($order->customer['format_id'], $order->customer, 1, '', '<br>').'</a>';?></div>
                    </div>
<?php
}
?>                
                
<?php
if ($order->delivery['address_book_id'] == $order->billing['address_book_id']) {
?>                
                    <div class="cr-ord-cust">
                        <span><?php echo T_CUSTOMER; ?></span>
                        <div><?php echo '<a href="'.Yii::$app->urlManager->createUrl(['customers/customeredit?customers_id='.$order->customer['customer_id']]).'">'.$order->customer['name'].'</a>';?></div>
                    </div>
<?php
}

$key = tep_db_fetch_array(tep_db_query("select info as setting_code from " . TABLE_GOOGLE_SETTINGS . " where module='mapskey'"));
?>                
                    <div class="cr-ord-cust cr-ord-cust-phone">
                        <span><?php echo ENTRY_TELEPHONE_NUMBER; ?></span>
                        <div><?php echo $order->customer['telephone']; ?></div>
                    </div>
                    <div class="cr-ord-cust cr-ord-cust-email">
                        <span><?php echo ENTRY_EMAIL_ADDRESS; ?></span>
                        <div><?php echo '<a href="mailto:' . $order->customer['email_address'] . '">' . $order->customer['email_address'] . '</a>'; ?></div>
                    </div>
            </div>
            <?php if($order->delivery['postcode'] != $order->billing['postcode']){ 
                $zoom_d = max((int)$zoom_val, 8);
                $zoom_b = max((int)$order->billing['country']['zoom'], 8);  
            ?>
            <div class="pr-add-det-box pr-add-det-box02 after">
                <div class="pra-sub-box after">
                    <div class="pra-sub-box-map">
                        <div class="cr-ord-cust cr-ord-cust-saddress">
                            <span><?php echo T_SHIP_ADDRESS; ?></span>
                            <div><?php echo \common\helpers\Address::address_format($order->delivery['format_id'], $order->delivery, 1, '', '<br>'); ?></div>
                        </div>
                        <div class="cr-ord-cust cr-ord-cust-smethod">
                            <span><?php echo T_SHIP_METH; ?></span>
                            <div><?php echo $order->info['shipping_method']; ?></div>
                            <div class="tracking_number"><a href="<?php echo \Yii::$app->urlManager->createUrl(['orders/gettracking?orders_id='.(int)$oID ])?>" class="edit-tracking"><i class="icon-pencil"></i></a><?php echo '<span class="tracknum">' . ($order->info['tracking_number'] ? '<a href="' . TRACKING_NUMBER_URL . $order->info['tracking_number'] . '" target="_blank">'.$order->info['tracking_number'].'</a>' : TEXT_TRACKING_NUMBER) . '</span>';?></div>
                        </div>
                        <div class="barcode">
                        <?php if (tep_not_null($order->info['tracking_number'])) { ?>
                        <a href="<?php echo TRACKING_NUMBER_URL . $order->info['tracking_number']; ?>" target="_blank"><img alt="<?php echo $order->info['tracking_number']; ?>" src="<?php echo HTTP_CATALOG_SERVER . DIR_WS_CATALOG . 'account/order-qrcode?oID=' . (int)$oID . '&cID=' . (int)$order->customer['customer_id'] . '&tracking=1'; ?>"></a>
                        <?php } ?>
                        </div>
                    </div>
                    <div class="pra-sub-box-map">
                        <div id="floating-panel">
                            <input id="hid-add1" type="hidden" value="<?php echo /*$order->delivery['postcode'] . ' ' . */$order->delivery['street_address'] . ' ' . $order->delivery['city'] . ' ' . $country_title; ?>">
                            <input id="hid-add1-zip" type="hidden" value="<?php echo $order->delivery['postcode']; ?>">
                        </div>
                        <div class="gmaps-wrap"><div id="gmap_markers1" class="gmaps"></div></div>
                    </div>                    
                </div>
                <div class="pra-sub-box after">
                    <div class="pra-sub-box-map">
                        <div class="cr-ord-cust cr-ord-cust-baddress">
                            <span><?php echo TEXT_BILLING_ADDRESS; ?></span>
                            <div><?php echo \common\helpers\Address::address_format($order->billing['format_id'], $order->billing, 1, '', '<br>'); ?></div>
                        </div>
                        <div class="cr-ord-cust cr-ord-cust-bmethod">
                            <span><?php echo T_BILL_METH; ?></span>
                            <div><?php echo $order->info['payment_method']; ?></div>
                        </div>
                    </div>
                    <div class="pra-sub-box-map">
                        <div id="floating-panel">
                            <input id="hid-add2" type="hidden" value="<?php echo /*$order->billing['postcode'] . ' ' .*/ $order->billing['street_address'] . ' ' . $order->billing['city'] . ' ' . $order->billing['country']['title']; ?>">
                            <input id="hid-add2-zip" type="hidden" value="<?php echo $order->billing['postcode']; ?>">
                        </div>
                        <div class="gmaps-wrap"><div id="gmap_markers2" class="gmaps"></div></div>
                        <script src="https://maps.googleapis.com/maps/api/js?key=<?=$key['setting_code'];?>&callback=initMap" async defer></script>
                        <script>
                            $(function(){
                                var click_map = false;
                                $('body').on('click', function(){
                                    setTimeout(function(){
                                        if (click_map ) {
                                            $('.map_dashboard-hide').remove()
                                        } else {
                                            if (!$('.map_dashboard-hide').hasClass('map_dashboard-hide')){
                                                $('.gmaps-wrap').append('<div class="map_dashboard-hide" style="position: absolute; left: 0; top: 0; right: 0; bottom: 0"></div>')
                                            }
                                        }
                                        click_map = false
                                    }, 200)
                                });
                                $('.gmaps-wrap')
                                  .css('position', 'relative')
                                  .append('<div class="map_dashboard-hide" style="position: absolute; left: 0; top: 0; right: 0; bottom: 0"></div>')
                                  .on('click', function(){
                                      setTimeout(function(){
                                          click_map = true
                                      }, 100)
                                  })
                            });

                            function initMap() {
                                var map1 = new google.maps.Map(document.getElementById('gmap_markers1'), {
                                  zoom: <?=$zoom_d?>,
                                  center: {lat: -34.397, lng: 150.644}
                                });
                                var map2 = new google.maps.Map(document.getElementById('gmap_markers2'), {
                                  zoom: <?=$zoom_b?>,
                                  center: {lat: -34.397, lng: 150.644}
                                });
                                var geocoder = new google.maps.Geocoder();

                                geocodeAddress1(geocoder, map1);
                                geocodeAddress2(geocoder, map2);
                              }

                              function geocodeAddress1(geocoder, resultsMap) {
                                var address1 = document.getElementById('hid-add1').value;
                                geocoder.geocode({'address': address1}, function(results, status) {
                                  if (status === google.maps.GeocoderStatus.OK) {
                                    resultsMap.setCenter(results[0].geometry.location);
                                    var marker = new google.maps.Marker({
                                      map: resultsMap,
                                      position: results[0].geometry.location
                                    });
                                  } else {
                                    address2 = document.getElementById('hid-add1-zip').value;
                                    geocoder.geocode({'address': address2}, function(results, status) {
                                      if (status === google.maps.GeocoderStatus.OK) {
                                        resultsMap.setCenter(results[0].geometry.location);
                                        var marker = new google.maps.Marker({
                                          map: resultsMap,
                                          position: results[0].geometry.location
                                        });
                                      }
                                    });
                                  }
                                });
                              }

                              function geocodeAddress2(geocoder, resultsMap) {
                                var address2 = document.getElementById('hid-add2').value;
                                geocoder.geocode({'address': address2}, function(results, status) {
                                  if (status === google.maps.GeocoderStatus.OK) {
                                    resultsMap.setCenter(results[0].geometry.location);
                                    var marker = new google.maps.Marker({
                                      map: resultsMap,
                                      position: results[0].geometry.location
                                    });
                                  } else {
                                    address2 = document.getElementById('hid-add2-zip').value;
                                    geocoder.geocode({'address': address2}, function(results, status) {
                                      if (status === google.maps.GeocoderStatus.OK) {
                                        resultsMap.setCenter(results[0].geometry.location);
                                        var marker = new google.maps.Marker({
                                          map: resultsMap,
                                          position: results[0].geometry.location
                                        });
                                      }
                                    });                                   
                                  }
                                });
                              }
                        </script>
                    </div>
                </div>
            </div>     
            <?php }else{
                $zoom = max((int)$zoom_val, 8);
            ?>
            <div class="pr-add-det-box pr-add-det-box02 pr-add-det-box03 after">
                <div class="pra-sub-box after">
                    <div class="pra-sub-box-map">
                        <div class="cr-ord-cust cr-ord-cust-saddress">
                            <span><?php echo T_SHIP_BILL_ADDRESS; ?></span>
                            <div><?php echo \common\helpers\Address::address_format($order->delivery['format_id'], $order->delivery, 1, '', '<br>'); ?></div>
                        </div>
                        <div class="cr-ord-cust cr-ord-cust-smethod">
                            <span><?php echo T_SHIP_METH; ?></span>
                            <div><?php echo $order->info['shipping_method']; ?></div>
                        </div>
                        <div class="cr-ord-cust cr-ord-cust-bmethod">
                            <span><?php echo T_BILL_METH; ?></span>
                            <div><?php echo $order->info['payment_method']; ?></div>
                        </div>
                        <div class="barcode">
                        <?php if (tep_not_null($order->info['tracking_number'])) { ?>
                        <a href="<?php echo TRACKING_NUMBER_URL . $order->info['tracking_number']; ?>" target="_blank"><img alt="<?php echo $order->info['tracking_number']; ?>" src="<?php echo HTTP_CATALOG_SERVER . DIR_WS_CATALOG . 'account/order-qrcode?oID=' . (int)$oID . '&cID=' . (int)$order->customer['customer_id']. '&tracking=1'; ?>"></a>
                        <?php } ?>
                        </div>
                    </div>
                    <div class="pra-sub-box-map">
                        <div id="floating-panel">
                            <input id="hid-add" type="hidden" value="<?php echo /*$order->delivery['postcode'] . ' ' .*/ $order->delivery['street_address'] . ' ' . $order->delivery['city'] . ' ' . $country_title; ?>">
                            <input id="hid-add-zip" type="hidden" value="<?php echo $order->delivery['postcode']; ?>">
                        </div>
                        <div class="gmaps-wrap"><div id="gmap_markers" class="gmaps"></div></div>
                        <script src="https://maps.googleapis.com/maps/api/js?key=<?=$key['setting_code'];?>&callback=initMap" async defer></script>
                        <script>
                            $(function(){
                                var click_map = false;
                                $('body').on('click', function(){
                                    setTimeout(function(){
                                        if (click_map ) {
                                            $('.map_dashboard-hide').remove()
                                        } else {
                                            if (!$('.map_dashboard-hide').hasClass('map_dashboard-hide')){
                                                $('.gmaps-wrap').append('<div class="map_dashboard-hide" style="position: absolute; left: 0; top: 0; right: 0; bottom: 0"></div>')
                                            }
                                        }
                                        click_map = false
                                    }, 200)
                                });
                                $('.gmaps-wrap')
                                  .css('position', 'relative')
                                  .append('<div class="map_dashboard-hide" style="position: absolute; left: 0; top: 0; right: 0; bottom: 0"></div>')
                                  .on('click', function(){
                                      setTimeout(function(){
                                          click_map = true
                                      }, 100)
                                  })
                            });
                            
                            function initMap() {
                                var map = new google.maps.Map(document.getElementById('gmap_markers'), {
                                  zoom: <?=$zoom?>,
                                  center: {lat: -34.397, lng: 150.644}
                                });
                                var geocoder = new google.maps.Geocoder();

                                geocodeAddress(geocoder, map);
                              }

                              function geocodeAddress(geocoder, resultsMap) {
                                var address = document.getElementById('hid-add').value;
                                geocoder.geocode({'address': address}, function(results, status) {
                                  if (status === google.maps.GeocoderStatus.OK) {
                                    resultsMap.setCenter(results[0].geometry.location);
                                    var marker = new google.maps.Marker({
                                      map: resultsMap,
                                      position: results[0].geometry.location
                                    });
                                  } else {
                                    address2 = document.getElementById('hid-add-zip').value;
                                    geocoder.geocode({'address': address2}, function(results, status) {
                                      if (status === google.maps.GeocoderStatus.OK) {
                                        resultsMap.setCenter(results[0].geometry.location);
                                        var marker = new google.maps.Marker({
                                          map: resultsMap,
                                          position: results[0].geometry.location
                                        });
                                      }
                                    });
                                  }
                                });
                              }
                        </script>
                    </div>                    
                </div>
            </div>    
            <?php } ?>
        </div>
    </div>
</div>
<div class="box-or-prod-wrap">
    <div class="widget box box-no-shadow">
        <div class="widget-header widget-header-prod">
            <h4><?php echo TEXT_PROD_DET; ?></h4>
            <div class="toolbar no-padding">
                <div class="btn-group">
                    <span id="orders_list_collapse" class="btn btn-xs widget-collapse"><i class="icon-angle-down"></i></span>
                </div>
            </div>
        </div>
        <div class="widget-content widget-content-prod">
                <table border="0" class="table table-process" width="100%" cellspacing="0" cellpadding="2">
                    <thead>
                        <tr class="dataTableHeadingRow">
                            <th class="dataTableHeadingContent" colspan="3"><?php echo TABLE_HEADING_PRODUCTS; ?></th>
                            <th class="dataTableHeadingContent"><?php echo TABLE_HEADING_PRODUCTS_MODEL; ?></th>
                            <th class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_TAX; ?></th>
                            <th class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_PRICE_EXCLUDING_TAX; ?></th>
                            <th class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_PRICE_INCLUDING_TAX; ?></th>
                            <th class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_TOTAL_EXCLUDING_TAX; ?></th>
                            <th class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_TOTAL_INCLUDING_TAX; ?></th>
                        </tr>
                    </thead>

                    <?php
                    for ($i = 0, $n = sizeof($order->products); $i < $n; $i++) {
						$image = \common\classes\Images::getImage($order->products[$i]['id']);
                        echo '          <tr class="dataTableRow">' . "\n" .
                        '            <td class="dataTableContent" valign="top" align="right">' . $order->products[$i]['qty'] . '&nbsp;x</td>' . "\n" .
						'            <td class="dataTableContent" valign="top" align="center"><div class="table-image-cell"><a href="'. \common\classes\Images::getImageUrl($order->products[$i]['id'], 'Large').'" class="fancybox">' . $image . '</a></div></td>' . "\n" .
                        '            <td class="dataTableContent" valign="top"><span style="cursor: pointer" onclick="window.open(\'' . tep_href_link(FILENAME_CATEGORIES . '/productedit', 'pID=' . $order->products[$i]['id']) . '\')">' . $order->products[$i]['name'] . '</span>';												
                        if ($ext = \common\helpers\Acl::checkExtension('PackUnits', 'queryOrderProcessAdmin')) {
                                echo $ext::queryOrderProcessAdmin($order->products, $i);
                        }
                        if (isset($order->products[$i]['attributes']) && (sizeof($order->products[$i]['attributes']) > 0)) {
                            for ($j = 0, $k = sizeof($order->products[$i]['attributes']); $j < $k; $j++) {
                                echo '<br><nobr><small>&nbsp;&nbsp;<i> - ' . str_replace(array('&amp;nbsp;', '&lt;b&gt;', '&lt;/b&gt;', '&lt;br&gt;'), array('&nbsp;', '<b>', '</b>', '<br>'), htmlspecialchars($order->products[$i]['attributes'][$j]['option'])) . ($order->products[$i]['attributes'][$j]['value'] ? ': ' . htmlspecialchars($order->products[$i]['attributes'][$j]['value']) : '');
                                if ($order->products[$i]['attributes'][$j]['price'] != '0')
                                    echo ' (' . $order->products[$i]['attributes'][$j]['prefix'] . $currencies->format($order->products[$i]['attributes'][$j]['price'] * $order->products[$i]['qty'], (USE_MARKET_PRICES == 'True' ? false : true), $order->info['currency'], $order->info['currency_value']) . ')';
                                echo '</i></small></nobr>';
                            }
                        }
                        $gv_state_label = '';
                        if ( $order->products[$i]['gv_state']!='none' ) {
                            $_inner_gv_state_label = (defined('TEXT_ORDERED_GV_STATE_'.strtoupper($order->products[$i]['gv_state']))?constant('TEXT_ORDERED_GV_STATE_'.strtoupper($order->products[$i]['gv_state'])):$order->products[$i]['gv_state']);
                            if ( $order->products[$i]['gv_state']=='pending' || $order->products[$i]['gv_state']=='canceled' ) {
                                $_inner_gv_state_label = '<a class="js_gv_state_popup" href="'.Yii::$app->urlManager->createUrl(['orders/gv-change-state','opID'=>$order->products[$i]['orders_products_id']]).'">'.$_inner_gv_state_label.'</a>';
                            }
                            $gv_state_label = '<span class="ordered_gv_state ordered_gv_state-'.$order->products[$i]['gv_state'].'">'.$_inner_gv_state_label.'</span>';
                        }
                        echo '            </td>' . "\n" .
                        '            <td class="dataTableContent" valign="top">' . $order->products[$i]['model'] . $gv_state_label . '</td>' . "\n" .
                        '            <td class="dataTableContent" align="right" valign="top">' . \common\helpers\Tax::display_tax_value($order->products[$i]['tax']) . '%</td>' . "\n" .
                        '            <td class="dataTableContent" align="right" valign="top"><b>' . $currencies->format($currencies->calculate_price_in_order($order->info, $order->products[$i]['final_price']), (USE_MARKET_PRICES == 'True' ? false : true), $order->info['currency'], $order->info['currency_value']) . '</b></td>' . "\n" .
                        '            <td class="dataTableContent" align="right" valign="top"><b>' . $currencies->format($currencies->calculate_price_in_order($order->info, $order->products[$i]['final_price'], $order->products[$i]['tax']), (USE_MARKET_PRICES == 'True' ? false : true), $order->info['currency'], $order->info['currency_value']) . '</b></td>' . "\n" .
                        '            <td class="dataTableContent" align="right" valign="top"><b>' . $currencies->format($currencies->calculate_price_in_order($order->info, $order->products[$i]['final_price'], 0, $order->products[$i]['qty']), (USE_MARKET_PRICES == 'True' ? false : true), $order->info['currency'], $order->info['currency_value'], true) . '</b></td>' . "\n" .
                        '            <td class="dataTableContent" align="right" valign="top"><b>' . $currencies->format($currencies->calculate_price_in_order($order->info, $order->products[$i]['final_price'], $order->products[$i]['tax'], $order->products[$i]['qty']), (USE_MARKET_PRICES == 'True' ? false : true), $order->info['currency'], $order->info['currency_value']) . '</b></td>' . "\n";
                        echo '          </tr>' . "\n";
                    }
                    ?>
                </table>
          <div class="tl-sub-wrap">
<?php
\common\helpers\Translation::init('payment');
if (file_exists(DIR_FS_CATALOG . DIR_WS_MODULES . 'payment/' . $order->info['payment_class'] . '.php')) {
    Yii::$app->get('platform')->config($order->info['platform_id'])->constant_up();
    include_once(DIR_FS_CATALOG . DIR_WS_MODULES . 'payment/' . $order->info['payment_class'] . '.php');
}

if (class_exists($order->info['payment_class'])) {
    $object = new $order->info['payment_class'];

    //$fullInfo = $object->get_samples_full_info($order->info['transaction_id']);
    //echo $fullInfo;
}
?>
          </div>
          <div class="order-sub-totals">
          <table>
            <?php
            for ($i = 0, $n = sizeof($order->totals); $i < $n; $i++) {
              echo '              <tr class="' . $order->totals[$i]['class'] . ($order->totals[$i]['show_line'] ? ' totals-line' : '') . '">' . "\n" .
                '                <td>' . $order->totals[$i]['title'] . '</td>' . "\n" .
                '                <td>' . $order->totals[$i]['text'] . '</td>' . "\n" .
                '              </tr>' . "\n";
            }
            ?>
          </table>
          </div>
          <div style="clear: both"></div>
            </div>
    </div>
    <div class="widget box box-no-shadow">
        <div class="widget-header widget-header-order-status">
            <h4><?php echo TEXT_ORDER_STATUS; ?></h4>
            <div class="toolbar no-padding">
                <div class="btn-group">
                    <span id="orders_list_collapse" class="btn btn-xs widget-collapse"><i class="icon-angle-down"></i></span>
                </div>
            </div>
        </div>
        <div class="widget-content">
            <table class="table table-st" border="0" cellspacing="0" cellpadding="0" width="100%">
                <thead>
                       <tr>
                        <th class="smallText" align="left"><?php echo TABLE_HEADING_DATE_ADDED; ?></th>
                        <th class="smallText" align="left"><?php echo TABLE_HEADING_CUSTOMER_NOTIFIED; ?></th>
                        <th class="smallText" align="left"><?php echo TABLE_HEADING_STATUS; ?></th>
                        <th class="smallText" align="left"><?php echo TABLE_HEADING_COMMENTS; ?></th>
                        <th class="smallText" align="left"><?php echo TABLE_HEADING_PROCESSED_BY; ?></th>
                      </tr>
                   </thead>
      <?php
          $orders_history_query = tep_db_query("select * from " . $this->table_prefix . TABLE_ORDERS_STATUS_HISTORY . " where orders_id = '" . (int)$oID . "' order by date_added");
          if (tep_db_num_rows($orders_history_query)) {
            while ($orders_history = tep_db_fetch_array($orders_history_query)) {
              echo '          <tr>' . "\n" .
                   '            <td>' . \common\helpers\Date::datetime_short($orders_history['date_added']) . '</td>' . "\n" .
                   '            <td>';
              if ($orders_history['customer_notified'] == '1') {
                echo '<span class="st-true"></span></td>';
        } else {
          echo '<span class="st-false"></span></td>';
              }
              echo '            <td><span class="or-st-color">'.$orders_status_group_array[$orders_history['orders_status_id']].'/&nbsp;</span>' . $orders_status_array[$orders_history['orders_status_id']] . '</td>' . "\n" .
                   '            <td>' . nl2br(tep_db_output($orders_history['comments'])) . '&nbsp;</td>' . "\n";
                if ($orders_history['admin_id'] > 0) {
                    $check_admin_query = tep_db_query( "select * from " . TABLE_ADMIN . " where admin_id = '" . (int)$orders_history['admin_id'] . "'" );
                    $check_admin = tep_db_fetch_array( $check_admin_query );
                    if (is_array($check_admin)) {
                        echo '<td>' . $check_admin['admin_firstname'] . ' ' . $check_admin['admin_lastname'] . '</td>';
                    } else {
                        echo '<td></td>';
                    }
                } else {
                    echo '<td></td>';
                }
                echo '          </tr>' . "\n";
            }
          } else {
              echo '          <tr>' . "\n" .
                   '            <td colspan="5">' . TEXT_NO_ORDER_HISTORY . '</td>' . "\n" .
                   '          </tr>' . "\n";
          }
      ?>
              </table>
            <div class="widget box box-wrapp-blue filter-wrapp">
            <div class="widget-header upd-sc-title">
                <h4><?php echo TABLE_HEADING_COMMENTS_STATUS; ?></h4>
            </div>
            <div class="widget-content usc-box usc-box2">
                <div class="f_tab">
                        <div class="f_row">
                            <div class="f_td">
                                <label><?php echo ENTRY_STATUS; ?></label>
                            </div>
                            <div class="f_td">
                            
                                <?php echo \yii\helpers\Html::dropDownList('status', (int)$order->info['order_status'] , \common\helpers\Sample::getStatusList(false), ['class'=>'form-control']);
                                $details = $order->getDetails();
                                if ($details['child_id'] > 0) {
                                    echo '<a href="'.Yii::$app->urlManager->createUrl('orders/process-order?orders_id='. $details['child_id']).'">' . TEXT_ORDER_ID . ': #' . $details['child_id'] . '</a>';
                                }
                                ?>                        
                            </div>
                        </div>
                    <?php if ( class_exists('\common\helpers\CommentTemplate') ){ ?>
                        <?php echo \common\helpers\CommentTemplate::renderFor('samples', $order); ?>
                    <?php } ?>
                        <div class="f_row">
                            <div class="f_td">
                                <label><?php echo  TABLE_HEADING_COMMENTS; ?>:</label>
                            </div>
                            <div class="f_td">
                                <?php echo tep_draw_textarea_field('comments', 'soft', '60', '5', '', 'class="form-control"'); ?>
                            </div>
                        </div>
                        <div class="f_row">
                            <div class="f_td"></div>
                            <div class="f_td">
                                <?php echo tep_draw_checkbox_field('notify', '', true); ?><b><?php echo ENTRY_NOTIFY_CUSTOMER; ?></b><?php echo tep_draw_checkbox_field('notify_comments', '', true, '', 'class="m_ch_b"'); ?><b><?php echo ENTRY_NOTIFY_COMMENTS; ?></b>                           
                                <?php if (!tep_session_is_registered('login_affiliate')){ ?>
                                <?php echo '<input type="submit" style="float: right; margin-right: -9px;" class="btn btn-confirm" value="' . IMAGE_UPDATE . '" >'; ?>
                                <?php
                                    //echo '<input type="submit" class="btn btn-primary" value="' . IMAGE_INSERT . '" >';
                                    echo tep_draw_hidden_field('orders_id', $oID);
                                    ?>
                                <?php
                                                }
                                              ?>  
                            </div>
                        </div> 
                    </div>
            </div>
    </div>
        </div>
    </div>
<?php
    $orders_not_processed = tep_db_fetch_array(tep_db_query("select orders_id from " . $this->table_prefix . TABLE_ORDERS . " where orders_id != '" . (int)$oID . "' and orders_status = '" . (int) DEFAULT_ORDERS_STATUS_ID . "' order by orders_id DESC limit 1"));
    echo '<div class="btn-bar" style="padding: 0; text-align: center;">' . '<div class="btn-left"><a href="javascript:void(0)" onclick="return resetStatement();" class="btn btn-back">' . IMAGE_BACK . '</a></div><div class="btn-right">' . (isset($orders_not_processed['orders_id']) ? '<a href="' . \Yii::$app->urlManager->createUrl(['samples/process-samples', 'orders_id' => $orders_not_processed['orders_id']]) . '" class="btn btn-next-unprocess">'.TEXT_BUTTON_NEXT_ORDER.'</a>' : '') . '</div></div>';
?>
</div>
  
</form>
<script type="text/javascript">
    $(document).ready(function(){
            $("a.js_gv_state_popup").popUp({
                box: "<div class='popup-box-wrap'><div class='around-pop-up'></div><div class='popup-box'><div class='pop-up-close'></div><div class='popup-heading pup-head'><?php echo POPUP_TITLE_GV_STATE_SWITCH;?></div><div class='pop-up-content'><div class='preloader'></div></div></div></div>"
            });

    });
</script>
            
            <?php //echo '<a href="javascript:popupWindow(\'' .  (HTTP_SERVER . DIR_WS_ADMIN . FILENAME_ORDERS_INVOICE) . '?' . (\common\helpers\Output::get_all_get_params(array('oID')) . 'oID=' . $_GET['oID']) . '\')">' . tep_image_button('button_invoice.gif', TEXT_INVOICE) . '</a><a href="javascript:popupWindow(\'' .  (HTTP_SERVER . DIR_WS_ADMIN . FILENAME_ORDERS_PACKINGSLIP) . '?' . (\common\helpers\Output::get_all_get_params(array('oID')) . 'oID=' . $_GET['oID']) . '\')">' . tep_image_button('button_packingslip.gif', IMAGE_ORDERS_PACKINGSLIP) . '</a>';
        //echo '<input type="button" class="btn btn-primary" value="' . IMAGE_BACK . '" onClick="return resetStatement()">'; ?>
<?php
        $content = ob_get_clean();
        if (Yii::$app->request->isPost) {
            return $content;
        }
		$_session = Yii::$app->session;
		$filter = $search_condition = '';
		if ($_session->has('filter')){
			$filter = $_session->get('filter');
		}
		if ($_session->has('search_condition')){
			$search_condition = $_session->get('search_condition');
		}

        $order_next = tep_db_fetch_array(tep_db_query("select o.orders_id from " . $this->table_prefix . TABLE_ORDERS . " o " . (strlen($filter) > 0 ? "left join " . $this->table_prefix . TABLE_ORDERS_PRODUCTS . " op on o.orders_id = op.orders_id left join " . TABLE_ORDERS_STATUS. " s on o.orders_status=s.orders_status_id " : '') . " where o.orders_id > '" . (int)$oID . "' " . $search_condition . " " . $filter . " order by orders_id ASC limit 1"));
        $order_prev = tep_db_fetch_array(tep_db_query("select o.orders_id from " . $this->table_prefix . TABLE_ORDERS . " o " . (strlen($filter) > 0 ? "left join " . $this->table_prefix . TABLE_ORDERS_PRODUCTS . " op on o.orders_id = op.orders_id left join " . TABLE_ORDERS_STATUS. " s on o.orders_status=s.orders_status_id " : '') . " where o.orders_id < '" . (int)$oID . "' " . $search_condition . " " . $filter . " order by orders_id DESC limit 1"));
        $this->view->order_next = ( isset($order_next['orders_id']) ? $order_next['orders_id'] : 0);
        $this->view->order_prev = ( isset($order_prev['orders_id']) ? $order_prev['orders_id'] : 0);

        $order_platform_id = $order->info['platform_id'];
        $order_language = \common\classes\language::get_code($order->info['language_id']);
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('samples/process-samples?orders_id='. $oID), 'title' => TEXT_PROCESS_SAMPLES_BUTTON . ' #'. $oID . ' <span class="head-or-time">' . TEXT_DATE_AND_TIME . $order->info['date_purchased'].'</span>');
        return $this->render('update', ['content' => $content, 'orders_id' => $oID, 'customer_id'=>(int)$order->customer["customer_id"] ,'qr_img_url'=>HTTP_CATALOG_SERVER . DIR_WS_CATALOG . "account/order-qrcode?oID=" . (int)$oID . "&cID=" . (int)$order->customer["customer_id"] . "&tracking=1", 'order_platform_id' => $order_platform_id, 'order_language'=> $order_language]);
    }

    private function saveText( $thetext )
    {
      if( !tep_not_null( $thetext ) ) return '';
      $thetext = str_replace( "\r", '\r', $thetext );
      $thetext = str_replace( "\n", '\n', $thetext );
      $thetext = str_replace( "\t", '\t', $thetext );
      $thetext = str_replace( '\"', '"', $thetext );
      $thetext = str_replace( '"', '""', $thetext );

      return $thetext;
    }
    
    public function actionOrdersexport() {
        if (tep_not_null($_POST['orders'])) {
            $separator = "\t";
            $filename = 'samples' . strftime('%Y%b%d_%H%M') . '.csv';

            header('Content-Type: application/vnd.ms-excel');
            header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
            header('Content-Disposition: attachment; filename="' . $filename . '"');

            if (preg_match('@MSIE ([0-9].[0-9]{1,2})@', $_SERVER['HTTP_USER_AGENT'], $log_version)) {
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
            } else {
                header('Pragma: no-cache');
            }

            echo chr(0xff) . chr(0xfe);

            $csv_str = '"Samples ID"' . $separator . '"Ship Method"' . $separator . '"Shipping Company"' . $separator . '"Shipping Street 1"' . $separator . '"Shipping Street 2"' . $separator . '"Shipping Suburb"' . $separator . '"Shipping State"' . $separator . '"Shipping Zip"' . $separator . '"Shipping Country"' . $separator . '"Shipping Name"' . "\r\n";

            $orders_query = tep_db_query("select orders_id from " . $this->table_prefix . TABLE_ORDERS . " where orders_id in ('" . implode("','", array_map('intval', explode(',', $_POST['orders']))) . "')");
            while ($orders = tep_db_fetch_array($orders_query)) {
                $order = new \common\extensions\Samples\Sample($orders['orders_id']);
                $csv_str .= '"' . $this->saveText($orders['orders_id']) . '"' . $separator . '"' . $this->saveText($order->info['shipping_method']) . '"' . $separator . '"' . $this->saveText($order->delivery['company']) . '"' . $separator . '"' . $this->saveText($order->delivery['street_address']) . '"' . $separator . '"' . $this->saveText($order->delivery['suburb']) . '"' . $separator . '"' . $this->saveText($order->delivery['city']) . '"' . $separator . '"' . $this->saveText($order->delivery['state']) . '"' . $separator . '"' . $this->saveText($order->delivery['postcode']) . '"' . $separator . '"' . $this->saveText($order->delivery['country']['title']) . '"' . $separator . '"' . $this->saveText($order->delivery['name']) . '"' . "\r\n";
            }

            $csv_str = mb_convert_encoding($csv_str, 'UTF-16LE', 'UTF-8');
            echo $csv_str;
        }
        exit;
    }
    
    public function actionOrdersdelete() {
        
        $this->layout = false;
        
        $selected_ids = Yii::$app->request->post('selected_ids');
        
        foreach ($selected_ids as $order_id) {
            tep_db_query("delete from " . $this->table_prefix . TABLE_ORDERS . " where orders_id = '" . (int) $order_id . "'");
            tep_db_query("delete from " . $this->table_prefix . TABLE_ORDERS_PRODUCTS . " where orders_id = '" . (int) $order_id . "'");
            tep_db_query("delete from " . $this->table_prefix . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . " where orders_id = '" . (int) $order_id . "'");
            tep_db_query("delete from " . $this->table_prefix . TABLE_ORDERS_PRODUCTS_DOWNLOAD . " where orders_id = '" . (int) $order_id . "'");
            tep_db_query("delete from " . $this->table_prefix . TABLE_ORDERS_HISTORY . " where orders_id = '" . (int) $order_id . "'");
            tep_db_query("delete from " . $this->table_prefix . TABLE_ORDERS_STATUS_HISTORY . " where orders_id = '" . (int) $order_id . "'");
            tep_db_query("delete from " . $this->table_prefix . TABLE_ORDERS_TOTAL . " where orders_id = '" . (int) $order_id . "'");
        }
    }
    
    public function actionConfirmorderdelete() {

        \common\helpers\Translation::init('admin/orders');

        $this->layout = false;

        $orders_id = Yii::$app->request->post('orders_id');

        $orders_query = tep_db_query("select o.settlement_date, o.approval_code, o.last_xml_export, o.transaction_id, o.orders_id, o.customers_name, o.payment_method, o.date_purchased, o.last_modified, o.currency, o.currency_value, s.orders_status_name, ot.text as order_total from " . TABLE_ORDERS_STATUS . " s, " . $this->table_prefix . TABLE_ORDERS . " o left join " . $this->table_prefix . TABLE_ORDERS_TOTAL . " ot on (o.orders_id = ot.orders_id) where o.orders_id = '" . (int) $orders_id . "'");
        $orders = tep_db_fetch_array($orders_query);

        if (!is_array($orders)) {
            die("Wrong order data.");
        }

        $oInfo = new \objectInfo($orders);

        echo tep_draw_form('orders', FILENAME_ORDERS, \common\helpers\Output::get_all_get_params(array('action')) . 'action=deleteconfirm', 'post', 'id="orders_edit" onSubmit="return deleteOrder();"');
        echo '<div class="or_box_head">' . TEXT_INFO_HEADING_DELETE_ORDER . '</div>';
        echo '<div class="col_desc">' . TEXT_INFO_DELETE_INTRO . '</div>';
        echo '<div class="row_or_wrapp">';
        echo '<div class="row_or"><div>' . TEXT_INFO_DELETE_DATA . ':</div><div>' . $oInfo->customers_name . '</div></div>';
        echo '<div class="row_or"><div>' . TEXT_INFO_DELETE_DATA_OID . ':</div><div>' . $oInfo->orders_id . '</div></div>';

        echo '</div>';
        ?>
        <div class="btn-toolbar btn-toolbar-order">
            <?php
            echo '<button class="btn btn-delete btn-no-margin">' . IMAGE_DELETE . '</button><input type="button" class="btn btn-cancel" value="' . IMAGE_CANCEL . '" onClick="return cancelStatement()">';
            echo tep_draw_hidden_field('orders_id', $oInfo->orders_id);
            ?>
        </div>
        </form>
        <?php
    }
    
    public function actionOrderdelete() {
        
        $this->layout = false;
        
        $order_id = Yii::$app->request->post('orders_id');
        
        tep_db_query("delete from " . $this->table_prefix . TABLE_ORDERS . " where orders_id = '" . (int) $order_id . "'");
        tep_db_query("delete from " . $this->table_prefix . TABLE_ORDERS_PRODUCTS . " where orders_id = '" . (int) $order_id . "'");
        tep_db_query("delete from " . $this->table_prefix . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . " where orders_id = '" . (int) $order_id . "'");
        tep_db_query("delete from " . $this->table_prefix . TABLE_ORDERS_PRODUCTS_DOWNLOAD . " where orders_id = '" . (int) $order_id . "'");
        tep_db_query("delete from " . $this->table_prefix . TABLE_ORDERS_HISTORY . " where orders_id = '" . (int) $order_id . "'");
        tep_db_query("delete from " . $this->table_prefix . TABLE_ORDERS_STATUS_HISTORY . " where orders_id = '" . (int) $order_id . "'");
        tep_db_query("delete from " . $this->table_prefix . TABLE_ORDERS_TOTAL . " where orders_id = '" . (int) $order_id . "'");

    }
    
    public function actionDownloadInvoice($set, $module, $id) {
        $this->layout = false;
        
        if (file_exists(DIR_FS_CATALOG_MODULES . $set . '/' . $module . '.php')) {
            require_once(DIR_FS_CATALOG_MODULES . $set . '/' . $module . '.php');
            Yii::$app->get('platform')->config(1)->constant_up();
            $payment = new $module();
            $payment->download_invoice($id);
        }
        exit();
    }
    
    public function actionOrderEdit() {
        global $login_id;
        global $quote, $cart, $order, $sendto, $billto, $cart_address_id, $select_shipping;
        global $shipping_modules, $shipping, $payment, $order_total_modules, $total_weight, $total_count;
        global $cot_gv, $cc_id;
        global $update_totals_custom, $adress_details, $admin_notified, $currentCart;

        $languages_id = \Yii::$app->settings->get('languages_id');
        \common\helpers\Translation::init('admin/orders');
        \common\helpers\Translation::init('admin/orders/create');
        \common\helpers\Translation::init('admin/orders/order-edit');
        $messageStack = \Yii::$container->get('message_stack');
        $messageStack->initFlash();
        
        $messageType = '';
        $admin_id = $login_id;

        $admin = new AdminCarts();
        $admin->loadCustomersBaskets();
        $currentCart = Yii::$app->request->getBodyParam('currentCart', '');
          
        $admin_message = '';

        $this->selectedMenu = array('customers', 'samples');

        $currencies = Yii::$container->get('currencies');

        $this->topButtons[] = '';
        $this->view->headingTitle = HEADING_TITLE;
        if (isset($_GET['new'])) {
            $this->view->newOrder = true;
        } else {
            $this->view->newOrder = false;
        }
        if (isset($_GET['back'])) {
            $this->view->backOption = $_GET['back'];
        } else {
            $this->view->backOption = 'orders';
        }

        if (!tep_session_is_registered('adress_details')) {
            tep_session_register('adress_details');
        }
        if (!tep_session_is_registered('select_shipping')) {
            tep_session_register('select_shipping');
        }

        if (!tep_session_is_registered('shipping')) {
            tep_session_register('shipping');
        }

        if (!tep_session_is_registered('payment')) {
            tep_session_register('payment');
        }

        if (!tep_session_is_registered('admin_notified')) {
            $admin_notified = false;
            tep_session_register('admin_notified');
        }

        $ids = $admin->getVirtualCartIDs();

        $oID = Yii::$app->request->get('orders_id');
        if (tep_not_null($currentCart)) {
            $admin->setCurrentCartID($currentCart, (tep_not_null($oID)?false:true));
        }        
        
        if (tep_not_null($oID)) { //existed order
            $info = tep_db_fetch_array(tep_db_query("select customers_id, language_id, platform_id, currency, basket_id, delivery_address_book_id, billing_address_book_id, shipping_class, payment_class, date_purchased, delivery_date from " . $this->table_prefix . TABLE_ORDERS . " where orders_id = '" . (int) $oID . "'"));
            if (!$info) {
                return $this->redirect(['orders/']);
            }

            $customer_id = $info['customers_id'];
            $currency = $info['currency'];
            $language_id = $info['language_id'];
            $basket_id = $info['basket_id'];
            $platform_id = $info['platform_id'];

            if (tep_session_is_registered('quote')) {
                $quote = &$_SESSION['quote'];

                if ($quote->order_id != $oID) {
                    $quote = new \common\extensions\Samples\QuoteCart($oID);
                    //tep_session_unregister('cot_gv');
                    //tep_session_unregister('cc_id');
                    tep_session_unregister('update_totals_custom');
                    // tep_session_unregister('shipping');
                    //tep_session_unregister('payment');
                    //tep_session_unregister('billto');
                    //tep_session_unregister('sendto');
                    // tep_session_unregister('select_shipping');!!
                    tep_session_unregister('admin_notified');
                    //unset($select_shipping);
                    //unset($payment);
                    //unset($shipping);
                    //unset($sendto);
                    //unset($billto);
                    //$adress_details = $this->checkDetails();
                    //unset($adress_details['data']['shipto']);
                    //unset($adress_details['data']['billto']);
                }
            } else {
                tep_session_register('quote');
                //tep_session_unregister('cot_gv');
                $quote = new \common\extensions\Samples\QuoteCart($oID);
                //tep_session_unregister('cc_id');
                tep_session_unregister('update_totals_custom');
                //tep_session_unregister('shipping');
                //tep_session_unregister('payment');
                //tep_session_unregister('billto');
                //tep_session_unregister('sendto');
                //tep_session_unregister('select_shipping');!!
                //tep_session_unregister('adress_details');
                tep_session_unregister('admin_notified');
                //unset($adress_details);
                //unset($select_shipping);
                //unset($payment);
                //unset($shipping);
                //unset($sendto);
                //unset($billto);
                //$adress_details = $this->checkDetails();
                //unset($adress_details['data']['shipto']);
                //unset($adress_details['data']['billto']);
            }
            $adress_details = $this->checkDetails();
            unset($adress_details['data']['shipto']);
            unset($adress_details['data']['billto']);
            $quote->setPlatform($platform_id)
                    ->setCurrency($currency)
                    ->setLanguage($language_id)
                    ->setAdmin($admin_id)
                    ->setBasketID($basket_id)
                    ->setCustomer($customer_id);
            $sendto = $info['delivery_address_book_id'];
            $billto = $info['billing_address_book_id'];
            if (($status = $admin->updateCustomersBasket($quote)) === false) {
                $name = $admin->getAdminByCart($quote);
                $admin_message = 'This order is busy by ' . $name . '. Do you want to assign this order to your account?';
            }
        } else { //new order
            $quote = &$_SESSION['quote'];

            if (!is_object($quote) || !($quote instanceof \common\extensions\Samples\QuoteCart)) {
                $messageStack->add_session(TEXT_CREATE_NEW_OREDER, 'create', 'warning');
                return $this->redirect(['orders/create']);
            }

            if (is_null($quote->order_id)) {
                $quote->order_id = -1;
                //tep_session_unregister('shipping');!!
                //tep_session_unregister('select_shipping');!!
                //tep_session_unregister('payment');
                //tep_session_unregister('cc_id');
                //unset($payment);
                //unset($select_shipping);
                //unset($shipping);
                //unset($cc_id);
                $adress_details = $this->checkDetails();
                unset($adress_details['data']['shipto']);
                unset($adress_details['data']['billto']);
            }

            if ($ids != false) { //has virtual carts (with zero order)
                if (count($ids) == 1) {
                    $admin->setCurrentCartID($ids[0], true);
                } else {
                    $admin->getLastVirtualID(true);
                }
            }

            $admin->loadCurrentCart();

            if (is_null($quote)) {
                $messageStack->add('Please create order <a href="orders/create">click here</a>', 'one_page_checkout');
                return $this->render('message', ['messagestack' => $messageStack]);
            }
            $info['delivery_address_book_id'] = $quote->address['sendto'];
            $info['billing_address_book_id'] = $quote->address['billto'];
            $info['payment_class'] = '';
            $info['shipping_class'] = '';
            $customer_id = $quote->customer_id;
            $currency = $quote->currency;
            $language_id = $quote->language_id;
            $platform_id = $quote->platform_id;
        }
        $admin_choice = [];
        $currentCart = $admin->getCurrentCartID();
        if ($ids) {
            foreach ($ids as $_ids) {
                //if ($_ids == $currentCart)                    continue;
                $admin_choice[] = $this->renderAjax('mini', [
                    'ids' => $_ids,
                    'customer' => \common\helpers\Customer::getCustomerData($_ids),
                    'opened' => ($_ids == $currentCart),
                    ]                    
                );
            }
        }

        

        if (tep_not_null($info['shipping_class']) && is_null($select_shipping)) {
            $select_shipping = $info['shipping_class'];
        }
        if (isset($_POST['shipping'])) {
            $select_shipping = Yii::$app->request->post('shipping');
            $quote->clearTotalKey('ot_shipping');
            $quote->clearHiddenModule('ot_shipping');
            $quote->clearTotalKey('ot_shippingfee');
            $quote->clearHiddenModule('ot_shippingfee');
            if (!isset($_POST['action']))
                $quote->setAdjusted();
        }

        if (!tep_session_is_registered('sendto')) {
            tep_session_register('sendto');
        }
        if (!tep_session_is_registered('billto')) {
            tep_session_register('billto');
        }

        if (is_null($sendto) || !$sendto)
            $sendto = $info['delivery_address_book_id'];
        if (!tep_session_is_registered('cart_address_id')) {
            tep_session_register('cart_address_id');
            $cart_address_id = $sendto;
        }
        if (is_null($billto) || !$billto)
            $billto = $info['billing_address_book_id'];

        $customer = new Customer();
        $customer_loaded = true;

        $session = new \yii\web\Session;
        $session['platform_id'] = $platform_id;

        if ($customer->loadCustomer($customer_id)) {
            $session['customer_id'] = $customer_id;
            $customer->setParam('sendto', $sendto);
            $customer->setParam('billto', $billto);
            $customer->setParam('currency', $currency);
            $customer->setParam('languages_id', $language_id);
            $customer->setParam('currencies_id', $currencies->currencies[$currency]['id']);
            $customer->convertToSession();
            $customer->clearParam('sendto');
            $customer->clearParam('billto');
        } else { // customer doesn't exist
            if (!$admin_notified) {
                $messageStack->add(ERROR_INVALID_CUSTOMER_ACCOUNT, 'one_page_checkout', 'error');
                $admin_notified = true;
            }
            $customer_loaded = false;
        }
        //$customer_loaded = false;

        if (!$payment) {
            $payment = $_SESSION['payment'] = $info['payment_class'];
        }

        $platform_config = new platform_config($quote->platform_id);
        $platform_config->constant_up();

        global $order_totals;
        $update_has_errors = false;

        if (Yii::$app->request->isPost) {
            if (isset($_POST['saID']) || isset($_POST['aID'])) {
                $adress_details = $this->checkDetails();
                if ($adress_details['error']) {
                    $update_has_errors = true;
                }
            }
        }

        if (isset($_POST['action']) && $_POST['action'] == 'update' && $customer_loaded) {
            $company = tep_db_prepare_input($_POST['customers_company']);
            $company_vat = tep_db_prepare_input($_POST['customers_company_vat']);
            $sql_data_array = [];
            if (in_array(ACCOUNT_COMPANY, ['required', 'required_register', 'visible', 'visible_register']))
                $sql_data_array['customers_company'] = $company;
            if (false && in_array(ACCOUNT_COMPANY_VAT_ID, ['required', 'required_register', 'visible', 'visible_register']))
                $sql_data_array['customers_company_vat'] = $company_vat;
            $sql_data_array['customers_email_address'] = tep_db_prepare_input($_POST['update_customer_email_address']);
            $sql_data_array['customers_telephone'] = tep_db_prepare_input($_POST['update_customer_telephone']);
            $sql_data_array['customers_landline'] = tep_db_prepare_input($_POST['update_customer_landline']);

            tep_db_perform(TABLE_CUSTOMERS, $sql_data_array, 'update', 'customers_id="' . (int) $customer_id . '"');
            $adress_details = $this->checkDetails();

            if (!$adress_details['error']) {
                $sa = $this->updateAddress($adress_details);
                $sendto = $sa['saID'];
                $billto = $sa['aID'];

                if ($adress_details['data']['csa']) {
                    $billto = $adress_details['data']['shipto']['saID'];
                }
            } else {
                $update_has_errors = true;
                $messageStack->add('check address', 'one_page_checkout', 'error');
            }
        }
        global $order;
        if (!$customer_loaded) {
            $order = new \common\extensions\Samples\Sample($oID);
        } else {
            $order = new \common\extensions\Samples\Sample();
            if ($info && tep_not_null($info['date_purchased']))
                $order->info['date_purchased'] = $info['date_purchased'];
            if ($info && tep_not_null($info['delivery_date']))
                $order->info['delivery_date'] = $info['delivery_date'];
            $order->order_id = $oID;
        }

        $this->navigation[] = array('title' => (tep_not_null($oID) ? HEADING_TITLE : TEXT_CREATE_NEW_OREDER) . (tep_not_null($oID) ? ' #' . $oID . ' <div class="head-or-time">' . TEXT_DATE_AND_TIME . ' ' . $order->info['date_purchased'] . '</div>' : '') . '<div class="order-platform">' . TABLE_HEADING_PLATFORM . ':' . \common\classes\platform::name($order->info['platform_id']) . '</div>');

        $shipping_modules = new shipping();

        $address = [];

        $ads = [];
        $temp = [];
        $retrieve = [];
        $address = $order->delivery;
        if (is_array($adress_details['data']['shipto'])) {
            foreach ($adress_details['data']['shipto'] as $_k => $v) {
                $address[$_k] = $v;
            }
            if (tep_not_null($adress_details['data']['shipto']['saID']) && !tep_not_null($adress_details['data']['shipto']['address_book_id'])) {
                $adress_details['data']['shipto']['address_book_id'] = $adress_details['data']['shipto']['saID'];
            }
            $address['country'] = \common\helpers\Country::get_country_info_by_name(\common\helpers\Country::get_country_name($adress_details['data']['shipto']['country_id']), $language_id);
            $order->delivery = $address;
            if (is_array($address)) {
                array_walk($address, function($value, $key, $prefix) use (&$temp) {
                    $temp[$prefix . $key] = $value;
                }, 's_entry_');
            }
        } else {
            if (is_array($address)) {
                array_walk($address, function($value, $key, $prefix) use (&$temp) {
                    $temp[$prefix . $key] = $value;
                }, 's_entry_');
            }
            $temp['s_entry_country'] = $temp['s_entry_country']['title'];
            $retrieve = $temp;
            $retrieve['saID'] = $address['address_book_id'];
        }

        $ads = $temp;
        $temp = [];
        $address = $order->billing;

        if (is_array($adress_details['data']['billto'])) {
            if (!$adress_details['data']['csa']) {
                foreach ($adress_details['data']['billto'] as $_k => $v) {
                    $address[$_k] = $v;
                }
                if (tep_not_null($adress_details['data']['billto']['aID']) && !tep_not_null($adress_details['data']['billto']['address_book_id'])) {
                    $adress_details['data']['billto']['address_book_id'] = $adress_details['data']['billto']['aID'];
                }
                $address['country'] = \common\helpers\Country::get_country_info_by_name(\common\helpers\Country::get_country_name($adress_details['data']['billto']['country_id']), $language_id);
                $order->billing = $address;
            } else if (is_array($adress_details['data']['shipto'])) {
                foreach ($adress_details['data']['shipto'] as $_k => $v) {
                    $address[$_k] = $v;
                }
                if (tep_not_null($adress_details['data']['billto']['aID']) && !tep_not_null($adress_details['data']['billto']['address_book_id'])) {
                    $adress_details['data']['billto']['address_book_id'] = $adress_details['data']['billto']['aID'];
                }
                $address['country'] = \common\helpers\Country::get_country_info_by_name(\common\helpers\Country::get_country_name($adress_details['data']['shipto']['country_id']), $language_id);
                $order->billing = $order->delivery;
            }
            if (is_array($address)) {
                array_walk($address, function($value, $key, $prefix) use (&$temp) {
                    $temp[$prefix . $key] = $value;
                }, 'entry_');
            }
        } else {
            if (is_array($address)) {
                array_walk($address, function($value, $key, $prefix) use (&$temp) {
                    $temp[$prefix . $key] = $value;
                }, 'entry_');
            }
            $temp['entry_country'] = $temp['entry_country']['title'];
            $retrieve = array_merge($retrieve, $temp);
            //$retrieve['saID'] = $address['address_book_id'];
            $retrieve['aID'] = $address['address_book_id'];
            if ($order->billing['address_book_id'] == $order->delivery['address_book_id'])
                $retrieve['csa'] = 'on';
        }

        if (is_array($retrieve) && count($retrieve)) {
            foreach ($retrieve as $_k => $_v) {
                $_POST[$_k] = $_v;
            }
            $adress_details = $this->checkDetails();
        }


        $ads = array_merge($ads, $temp);

        $info_array = array_merge($ads, $order->customer);
        $cInfo = new \objectInfo($info_array);
        $cInfo->platform_id = $platform_id;
        if ($customer_loaded) {
            $result = $this->getAddresses($cInfo->customer_id);
        } else if (tep_not_null($oID)) {
            $result = $this->getOrderAddresses($oID);
        }
        $js_arrs = $result[0];
        $addresses = $result[1];

        $entry = new \stdClass;
        $entry->zones_array = null;
        $entry->countries = \common\helpers\Country::get_countries();
        $zones = \common\helpers\Zones::get_country_zones($order->billing['country']['id']);

        $entry->entry_state_has_zones = false;
        if (is_array($zones) && count($zones)) {
            $entry->entry_state_has_zones = true;
            $entry->zones_array = $zones;
            $entry->entry_state = $order->billing['zone_id'];
        } else {
            $entry->entry_state = $order->billing['state'];
        }
        $zones = \common\helpers\Zones::get_country_zones($order->delivery['country']['id']);

        $entry->s_entry_state_has_zones = false;
        if (is_array($zones) && count($zones)) {
            $entry->s_entry_state_has_zones = true;
            $entry->s_zones_array = $zones;
            $entry->s_entry_state = $order->delivery['zone_id'];
        } else {
            $entry->s_entry_state = $order->delivery['state'];
        }

        $payment_modules = new payment();

        $selection = $payment_modules->selection(false, false, ['admin','shop_sample']);
        if (is_array($selection) && !$payment) {
            $payment = $selection[0]['id'];
        }
        $order->info['payment_class'] = $payment;

        if (isset($_POST['action']) && $_POST['action'] == 'update_gv_amount') {
            if (strtolower($_POST['cot_gv']) == 'on') {
                tep_session_register('cot_gv');
                $quote->clearHiddenModule('ot_gv');
                $quote->clearTotalKey('ot_gv');
            } else {
                tep_session_unregister('cot_gv');
            }
        }

        if (is_null($select_shipping)) {
            $cheapest = $shipping_modules->cheapest();
            if (is_array($cheapest)) {
                $select_shipping = $cheapest[0]['id'];
            }
        }

        $_POST['estimate'] = ['country_id' => $order->delivery['country_id'], 'post_code' => $order->delivery['postcode'], 'shipping' => $select_shipping];
        
        global $cart;
        $cartBackup = $cart;
        $cart = $quote;
        $shipping_details = \frontend\controllers\ShoppingCartController::prepareEstimateData(['admin','shop_sample']);
        
        if ($select_shipping != $order->info['shipping_class'] && is_array($shipping)) {
            $order->change_shipping($shipping);
            $quote->setTotalKey('ot_shipping', ['ex' => $order->info['shipping_cost_exc_tax'], 'in' => $order->info['shipping_cost_inc_tax']]);
        }

        $order->order_id = $oID;


        $total_weight = $quote->show_weight();
        $total_count = $quote->count_contents();

        $tax_class_array = \common\helpers\Tax::get_complex_classes_list();

        $order_total_modules = new \common\classes\order_total(array(
            'ONE_PAGE_CHECKOUT' => 'True',
            'ONE_PAGE_SHOW_TOTALS' => 'false',
            'COUPON_SUCCESS_APPLY' => 'true',
            'GV_SOLO_APPLY' => 'true',
        ));

        if ((isset($_POST['action']) && $_POST['action'] == 'update_amount')) {
            $value = (float) $_POST['paid_amount'] * $currencies->get_market_price_rate($currency, DEFAULT_CURRENCY);
            $quote->setTotalPaid($value, $_POST['comment']);
        }

        if (!tep_session_is_registered('update_totals_custom'))
            tep_session_register('update_totals_custom');

        $reset_totals = (isset($_POST['reset_totals']) && strtolower($_POST['reset_totals']) == 'on');
        if ($reset_totals) {
            if (($_gv = $quote->getTotalKey('ot_gv')) != false && $cot_gv && $customer_loaded) {
                if (is_numeric($_gv)) {
                    $sql_data_array = [
                        'customers_id' => $customer_id,
                        'credit_prefix' => '+',
                        'credit_amount' => $_gv,
                        'currency' => $currency,
                        'currency_value' => $currencies->currencies[$currency]['value'],
                        'customer_notified' => '0',
                        'comments' => '',
                        'date_added' => 'now()',
                        'admin_id' => $login_id,
                    ];

                    tep_db_perform(TABLE_CUSTOMERS_CREDIT_HISTORY, $sql_data_array);
                    tep_db_query("update " . TABLE_CUSTOMERS . " set credit_amount = credit_amount + " . $_gv . " where customers_id =" . (int) $customer_id);
                }
                unset($_SESSION['cot_gv']);
            }

            $quote->clearTotals(false);
            $quote->clearHiddenModules();
            $quote->setAdjusted();
            if (tep_not_null($oID)) {
                $quote->restoreTotals();
            } else {
                $order->info['total_paid_inc_tax'] = 0;
                $order->info['total_paid_exc_tax'] = 0;
            }
            if (/* $select_shipping != $order->info['shipping_class'] && */ is_array($shipping)) {
                $order->change_shipping($shipping);
                $quote->setTotalKey('ot_shipping', ['ex' => $order->info['shipping_cost_exc_tax'], 'in' => $order->info['shipping_cost_inc_tax']]);
            }
            //$update_totals = [];
            unset($_SESSION['update_totals_custom']);
        }

        if ((isset($_POST['action']) && $_POST['action'] == 'adjust_tax')) {
            $prefix = $_POST['adjust_prefix'];
            $quote->setTotalTax('ot_tax', ['in' => 0.01, 'ex' => 0.01], $prefix);
        }

        $update_totals = [];
        if (!$update_has_errors) {
            $_update_totals = $quote->getAllTotals();
            if (is_array($_update_totals)) {
                foreach ($_update_totals as $_k => $_v) {
                    if (is_array($_v['value'])) {
                        $update_totals[$_k]['value']['in'] = $_v['value']['in'] * $currencies->get_market_price_rate(DEFAULT_CURRENCY, $currency);
                        $update_totals[$_k]['value']['ex'] = $_v['value']['ex'] * $currencies->get_market_price_rate(DEFAULT_CURRENCY, $currency);
                    } else {
                        $update_totals[$_k] = $_v['value'] * $currencies->get_market_price_rate(DEFAULT_CURRENCY, $currency);
                    }
                }
            }
        }

        if ((isset($_POST['action']) && $_POST['action'] == 'remove_module')) {
            $_module = $_POST['module'];
            if (tep_not_null($_module)) {
                $quote->addHiddenModule($_module);
                $quote->clearTotalKey($_module);
                $quote->setAdjusted();
            }
        }

        $update_totals_custom = [];

        if (!$update_has_errors && ((isset($_POST['action']) && $_POST['action'] == 'update') || (isset($_POST['action']) && $_POST['action'] == 'new_module'))) {

            foreach ($_POST['update_totals'] as $_k => $v) {
                if (in_array($_k, ['ot_paid', 'ot_due']))
                    continue;
                if (isset($_POST['action']) && $_POST['action'] == 'new_module') {
                    $quote->clearHiddenModule($_k);
                }
                if (!is_array($update_totals[$_k]))
                    $update_totals[$_k] = [];
                if ($_k != 'ot_tax')
                    $update_totals[$_k]['value'] = $v;
            }

            $update_totals_custom = $_POST['update_totals_custom'];
        }

        if ($ext = Acl::checkExtension('CouponsAndVauchers', 'orderEditCouponVoucher')) {
            $ext::orderEditCouponVoucher();
        }

        $order_total_modules->pre_confirmation_check();

        $cart = $quote;
        $order_totals = $order_total_modules->processInAdmin($update_totals);

        if (Yii::$app->request->isPost) {

            if ($ext = \common\helpers\Acl::checkExtension('DelayedDespatch', 'prepareDeliveryDate')){
                    global $order_delivery_date;
                    $dd_result = $ext::prepareDeliveryDate(true);
                    if ($dd_result){
                        $update_has_errors = true;
                    }
            }
                
            if (!$update_has_errors && isset($_POST['action']) && $_POST['action'] == 'update') {

                $order->info['customers_email_address'] = tep_db_prepare_input($_POST['update_customer_email_address']);
                $order->info['customers_telephone'] = tep_db_prepare_input($_POST['update_customer_telephone']);
                $order->info['customers_landline'] = tep_db_prepare_input($_POST['update_customer_landline']);

                //echo '<pre>';print_r($quote);die;
                $order->info['comments'] = TEXT_MESSEAGE_SUCCESS;
                if (isset($_POST['comment']) && !empty($_POST['comment'])) {
                    $order->info['comments'] = tep_db_prepare_input($_POST['comment']);
                }

                if (number_format($order->info['total_inc_tax'], 2) > number_format($order->info['total_paid_inc_tax'], 2)) {
                    if (defined('ORDER_STATUS_PART_AMOUNT') && (int) ORDER_STATUS_PART_AMOUNT > 0)
                        $quote->setOrderStatus(ORDER_STATUS_PART_AMOUNT);
                }
                if (number_format($order->info['total_inc_tax'], 2) == number_format($order->info['total_paid_inc_tax'], 2)) {
                    if (defined('ORDER_STATUS_FULL_AMOUNT') && (int) ORDER_STATUS_FULL_AMOUNT > 0)
                        $quote->setOrderStatus(ORDER_STATUS_FULL_AMOUNT);
                }

                if ($customer_loaded) {
                    if ($ext = \common\helpers\Acl::checkExtension('UpdateAndPay', 'checkStatus')) {
                        $ext::checkStatus();
                    }
                }
                if (empty($order->info['order_status']) || !$order->info['order_status']) {
                    $order->info['order_status'] = \common\helpers\Quote::getStatus('Active');
                }

                if (tep_not_null($oID)) {
                    $order->save_order($oID);
                } else {
                    $order->save_order();
                }

                if ($customer_loaded) {
                    if ($ext = \common\helpers\Acl::checkExtension('UpdateAndPay', 'saveOrder')) {
                        $ext::saveOrder();
                    }
                }

                $order->save_details();

                $notify = (strtolower($_POST['notify']) == 'on' ? true : false);

                $order->save_products($notify);

                $order_total_modules->apply_credit(); //ICW ADDED FOR CREDIT CLASS SYSTEM
                $quote->order_id = $order->order_id;
                $admin->saveCustomerBasket($quote);
                                
                
                //unset($_SESSION['cot_gv']);				
                $subaction = Yii::$app->request->post('subaction', '');
                $quote->restoreTotals();

                if (!tep_not_null($oID)) {
                    $oID = $order->order_id;
                    $messageStack->add_session(SUCCESS_ORDER_UPDATED, 'one_page_checkout', 'success');
                    if ($subaction == 'return') {
                        echo json_encode(['reload' => \yii\helpers\Url::to(['orders/process-order', 'orders_id' => $oID])]);
                    } else {
                        echo json_encode(['reload' => \yii\helpers\Url::to(['orders/order-edit', 'orders_id' => $oID])]);
                    }
                    exit();
                } else {
                    $messageStack->add(SUCCESS_ORDER_UPDATED, 'one_page_checkout', 'success');
                    if ($subaction == 'return') {
                        echo json_encode(['reload' => \yii\helpers\Url::to(['orders/process-order', 'orders_id' => $oID])]);
                        exit();
                    }
                }
            }
        }
        if ($admin->checkCartOwnerClear($quote)){
            $admin->saveCustomerBasket($quote);
        }

        $gv_amount_current = 0;
        $gv_query = tep_db_query("select credit_amount as amount from " . TABLE_CUSTOMERS . " where customers_id = '" . (int) $order->customer['customer_id'] . "'");
        if ($gv_result = tep_db_fetch_array($gv_query)) {
            $gv_amount_current = $currencies->format($gv_result['amount'], true, $order->info['currency'], $order->info['currency_value']);
        }

        $CommentsWithStatus = tep_db_field_exists($this->table_prefix . TABLE_ORDERS_STATUS_HISTORY, "comments");
        $_history = [];
        $orders_history_query = tep_db_query("select * from " . $this->table_prefix . TABLE_ORDERS_STATUS_HISTORY . " where orders_id = '" . tep_db_input($oID) . "' order by date_added");
        if (tep_db_num_rows($orders_history_query)) {
            while ($orders_history = tep_db_fetch_array($orders_history_query)) {
                if ($orders_history['admin_id'] > 0) {
                    $check_admin_query = tep_db_query("select * from " . TABLE_ADMIN . " where admin_id = '" . (int) $orders_history['admin_id'] . "'");
                    $check_admin = tep_db_fetch_array($check_admin_query);
                    if (is_array($check_admin)) {
                        $orders_history['admin'] = $check_admin['admin_firstname'] . ' ' . $check_admin['admin_lastname'];
                    } else {
                        $orders_history['admin'] = '';
                    }
                }
                $_history[] = $orders_history;
            }
        }
        $orders_statuses = array();
        $orders_status_array = array();
        $orders_status_group_array = array();
        $orders_status_query = tep_db_query("select os.orders_status_id, os.orders_status_name, osg.orders_status_groups_name, osg.orders_status_groups_color from " . TABLE_ORDERS_STATUS . " as os left join " . TABLE_ORDERS_STATUS_GROUPS . " as osg ON os.orders_status_groups_id = osg.orders_status_groups_id where os.language_id = '" . (int) $languages_id . "' and osg.language_id = '" . (int) $languages_id . "'");
        while ($orders_status = tep_db_fetch_array($orders_status_query)) {
            $orders_statuses[] = array('id' => $orders_status['orders_status_id'],
                'text' => $orders_status['orders_status_name']);
            $orders_status_array[$orders_status['orders_status_id']] = $orders_status['orders_status_name'];
            $orders_status_group_array[$orders_status['orders_status_id']] = '<i style="background: ' . $orders_status['orders_status_groups_color'] . ';"></i>' . $orders_status['orders_status_groups_name'];
        }

        $response = \common\helpers\Gifts::getGiveAwaysQuery();
        $giveaway_query = $response['giveaway_query'];
        $products = $order->products;
        if (is_array($products)) {
            foreach ($products as $kp => $vp) {
                $products[$kp]['stock_limits'] = \common\helpers\Product::get_product_order_quantity($vp['id']);
                $product_qty = \common\helpers\Product::get_products_stock($vp['id']);
                $stock_indicator = \common\classes\StockIndication::product_info(array(
                            'products_id' => $vp['id'],
                            'products_quantity' => $product_qty,
                ));
                
                $stock_indicator['max_qty'] = MAX_CART_QTY;
                $products[$kp]['stock_info'] = $stock_indicator;
            }
        }

        if (Yii::$app->request->isAjax) {
            echo json_encode([
                'admin_message' => $admin_message,
                'admin_choice' => $admin_choice,
                'cart' => $quote,
                'currentCart' => $currentCart,
                'address_details' => $this->renderAjax('address_details', [
                    'js_arrs' => $js_arrs,
                    'cInfo' => $cInfo,
                    'addresses' => $addresses,
                    'customer_loaded' => $customer_loaded,
                    'aID' => $order->billing['address_book_id'],
                    'saID' => $order->delivery['address_book_id'],
                    'error' => $adress_details['error'],
                    'errors' => $adress_details['errors'],
                    'csa' => (isset($adress_details['data']['csa']) ? $adress_details['data']['csa'] : $order->billing['address_book_id'] == $order->delivery['address_book_id']),
                    'entry_state' => \common\helpers\Zones::get_zone_name($cInfo->entry_country_id, $cInfo->entry_zone_id, $cInfo->entry_state),
                    'entry' => $entry,
                ]),
                'shipping_details' => $this->renderAjax('shipping', ['quotes' => $shipping_details['quotes'], 'quotes_radio_buttons' => $shipping_details['quotes_radio_buttons'], 'order' => $order]),
                'payment_details' => $this->renderAjax('payment', ['selection' => $selection,
                    'order' => $order,
                    'gv_amount_current' => $gv_amount_current,
                    'payment' => $payment,
                    'oID' => $oID,
                    'cot_gv_active' => isset($_SESSION['cot_gv']),
                    'custom_gv_amount' => (isset($_SESSION['cot_gv']) && is_numeric($_SESSION['cot_gv'])) ? round($_SESSION['cot_gv'] * $currencies->get_market_price_rate(DEFAULT_CURRENCY, $currency), 2) : '',
                    'gv_redeem_code' => ($cc_id ? \common\helpers\Coupon::get_coupon_name($cc_id) : ''),
                ]),
                'products_details' => $this->renderAjax('product_listing', [
                    'products' => $products,
                    'tax_class_array' => $tax_class_array,
                    'currencies' => $currencies,
                    'recalculate' => (USE_MARKET_PRICES == 'True' ? false : true),
                    'oID' => $oID,
                    'cart' => $quote,
                    'giveaway' => [
                        'count' => $giveaway_query->count()
                    ],
                    'giftWrapExist' => $quote->cart_allow_giftwrap(),
                    'order' => $order,
                ]),
                'oID' => $oID,
                'order_total_details' => $this->renderAjax('order_totals', ['inputs' => $order_total_modules->get_all_totals_list(), 'oID' => $oID, 'orders_statuses' => $orders_statuses, 'current_status' => $order->info['order_status'], 'currency' => $currency]),
                'order_statuses' => $this->renderAjax('order_statuses', ['CommentsWithStatus' => $CommentsWithStatus, 'orders_history_items' => $_history, 'orders_statuses' => $orders_statuses, 'orders_status_array' => $orders_status_array, 'orders_status_group_array' => $orders_status_group_array]),
                'message' => (count($messageStack->messages) ? $this->renderAjax('message', ['messagestack' => $messageStack]) : ''),
                'gv_redeem_code' => ($cc_id ? \common\helpers\Coupon::get_coupon_name($cc_id) : ''),
            ]);
            $cart = $cartBackup;
            $customer->convertBackSession();
            exit();
        } else {
            $rendering = $this->render('edit', [
                'admin_message' => $admin_message,
                'admin_choice' => $admin_choice,
                'cart' => $quote,
                'currentCart' => $currentCart,
                'address_details' => $this->renderAjax('address_details', [
                    'js_arrs' => $js_arrs,
                    'cInfo' => $cInfo,
                    'addresses' => $addresses,
                    'customer_loaded' => $customer_loaded,
                    'aID' => $order->billing['address_book_id'],
                    'saID' => $order->delivery['address_book_id'],
                    'error' => $adress_details['error'],
                    'errors' => $adress_details['errors'],
                    'csa' => (isset($adress_details['data']['csa']) ? $adress_details['data']['csa'] : $order->billing['address_book_id'] == $order->delivery['address_book_id']),
                    'entry_state' => \common\helpers\Zones::get_zone_name($cInfo->entry_country_id, $cInfo->entry_zone_id, $cInfo->entry_state),
                    'entry' => $entry,
                ]),
                'content' => '',
                'form_params' => \common\helpers\Output::get_all_get_params(array('action', 'paycc')) . 'action=update_order',
                'oID' => $oID,
                'order' => $order,
                'shipping_details' => $shipping_details,
                'selection' => $selection,
                'gv_amount_current' => $gv_amount_current,
                'shipping' => $shipping,
                'payment' => $payment,
                'products_details' => $this->renderAjax('product_listing', [
                    'products' => $products,
                    'tax_class_array' => $tax_class_array,
                    'currencies' => $currencies,
                    'recalculate' => (USE_MARKET_PRICES == 'True' ? false : true),
                    'oID' => $oID,
                    'cart' => $quote,
                    'giveaway' => [
                        'count' => $giveaway_query->count()
                    ],
                    'giftWrapExist' => $quote->cart_allow_giftwrap(),
                    'order' => $order,
                ]),
                'order_total_details' => $this->renderAjax('order_totals', ['inputs' => $order_total_modules->get_all_totals_list(), 'oID' => $oID, 'orders_statuses' => $orders_statuses, 'current_status' => $order->info['order_status'], 'currency' => $currency]),
                'order_statuses' => $this->renderAjax('order_statuses', ['CommentsWithStatus' => $CommentsWithStatus, 'orders_history_items' => $_history, 'orders_statuses' => $orders_statuses, 'orders_status_array' => $orders_status_array, 'orders_status_group_array' => $orders_status_group_array]),
                'message' => (is_array($messageStack->messages) && count($messageStack->messages) ? $this->renderAjax('message', ['messagestack' => $messageStack]) : ''),
                'gv_redeem_code' => ($cc_id ? \common\helpers\Coupon::get_coupon_name($cc_id) : ''),
                'cot_gv_active' => isset($_SESSION['cot_gv']),
                'custom_gv_amount' => (isset($_SESSION['cot_gv']) && is_numeric($_SESSION['cot_gv'])) ? round($_SESSION['cot_gv'] * $currencies->get_market_price_rate(DEFAULT_CURRENCY, $currency), 2) : ''
            ]);
            $cart = $cartBackup;
            $customer->convertBackSession();
            return $rendering;
        }
    }

    public function checkDetails() {

        $error = false;
        $saID = (int) $_POST['saID'];
        $aID = (int) $_POST['aID'];
        $csa = strtolower($_POST['csa']) == 'on' ? true : false;
        if ($csa)
            $aID = $saID;

        $company = tep_db_prepare_input($_POST['customers_company']);
        $company_vat = tep_db_prepare_input($_POST['customers_company_vat']);

        $errors = new \stdClass;
        $data = ['shipto' => ['saID' => $saID],
            'billto' => ['aID' => $aID],
            'csa' => $csa,
            'company' => $company,
            'company_vat' => $company_vat,
        ];

        if (in_array(ACCOUNT_GENDER, ['required', 'required_register', 'visible', 'visible_register'])) {
            $s_gender = tep_db_prepare_input($_POST['s_entry_gender']);
            if (in_array(ACCOUNT_GENDER, ['required', 'required_register'])) {
                if (($s_gender != 'm') && ($s_gender != 'f') && ($s_gender != 's')) {
                    $error = true;
                    $errors->s_entry_gender_error = true;
                }
            }
            $data['shipto']['gender'] = $s_gender;
        }

        //if (!$saID){
        if (in_array(ACCOUNT_FIRSTNAME, ['required', 'required_register', 'visible', 'visible_register'])) {
            $s_firstname = tep_db_prepare_input($_POST['s_entry_firstname']);
            if (in_array(ACCOUNT_FIRSTNAME, ['required', 'required_register'])) {
                if (strlen($s_firstname) < ENTRY_FIRST_NAME_MIN_LENGTH) {
                    $error = true;
                    $errors->s_entry_firstname_error = true;
                }
            }
            $data['shipto']['firstname'] = $s_firstname;
        }

        if (in_array(ACCOUNT_LASTNAME, ['required', 'required_register', 'visible', 'visible_register'])) {
            $s_lastname = tep_db_prepare_input($_POST['s_entry_lastname']);
            if (in_array(ACCOUNT_FIRSTNAME, ['required', 'required_register'])) {
                if (strlen($s_lastname) < ENTRY_LAST_NAME_MIN_LENGTH) {
                    $error = true;
                    $errors->s_entry_lastname_error = true;
                }
            }
            $data['shipto']['lastname'] = $s_lastname;
        }

        if (in_array(ACCOUNT_POSTCODE, ['required', 'required_register', 'visible', 'visible_register'])) {
            $s_postcode = tep_db_prepare_input($_POST['s_entry_postcode']);
            if (in_array(ACCOUNT_POSTCODE, ['required', 'required_register']) && strlen($s_postcode) < ENTRY_POSTCODE_MIN_LENGTH) {
                $error = true;
                $errors->s_entry_post_code_error = true;
            }
            $data['shipto']['postcode'] = $s_postcode;
        }

        if (in_array(ACCOUNT_STREET_ADDRESS, ['required', 'required_register', 'visible', 'visible_register'])) {
            $s_street_address = tep_db_prepare_input($_POST['s_entry_street_address']);
            if (in_array(ACCOUNT_STREET_ADDRESS, ['required', 'required_register']) && strlen($s_street_address) < ENTRY_STREET_ADDRESS_MIN_LENGTH) {
                $error = true;
                $errors->s_entry_street_address_error = true;
            }
            $data['shipto']['street_address'] = $s_street_address;
        }

        if (in_array(ACCOUNT_SUBURB, ['required', 'required_register', 'visible', 'visible_register'])) {
            $s_suburb = tep_db_prepare_input($_POST['s_entry_suburb']);
            if (in_array(ACCOUNT_SUBURB, ['required', 'required_register']) && empty($s_suburb)) {
                $error = true;
                $errors->s_entry_suburb_error = true;
            }
            $data['shipto']['suburb'] = $s_suburb;
        }

        if (in_array(ACCOUNT_CITY, ['required', 'required_register', 'visible', 'visible_register'])) {
            $s_city = tep_db_prepare_input($_POST['s_entry_city']);
            if (in_array(ACCOUNT_CITY, ['required', 'required_register']) && strlen($s_city) < ENTRY_CITY_MIN_LENGTH) {
                $error = true;
                $errors->s_entry_city_error = true;
            }
            $data['shipto']['city'] = $s_city;
        }

        $s_entry_country_id = tep_db_prepare_input($_POST['s_entry_country_id']);
        if (in_array(ACCOUNT_COUNTRY, ['required', 'required_register', 'visible', 'visible_register'])) {
            if (in_array(ACCOUNT_COUNTRY, ['required', 'required_register']) && (int) $s_entry_country_id == 0) {
                $error = true;
                $errors->s_entry_country_error = true;
            }
            $data['shipto']['country_id'] = $s_entry_country_id;
        }

        if (in_array(ACCOUNT_STATE, ['required', 'required_register', 'visible', 'visible_register'])) {
            $s_state = tep_db_prepare_input($_POST['s_entry_state']);
            $zones = \common\helpers\Zones::get_country_zones($s_entry_country_id);
            if (is_array($zones) && count($zones)) {
                if (isset($_POST['s_entry_zone_id'])) {
                    $s_state = tep_db_prepare_input($_POST['s_entry_zone_id']);
                }
                $zones = \yii\helpers\ArrayHelper::map($zones, 'id', 'id');
                if (in_array(ACCOUNT_STATE, ['required', 'required_register']) && !in_array($s_state, $zones)) {
                    $error = true;
                    $errors->s_entry_state_error = true;
                } else {
                    $data['shipto']['zone_id'] = $s_state;
                    $data['shipto']['state'] = \common\helpers\Zones::get_zone_name($s_entry_country_id, $s_state, '');
                }
            } else if (strlen($s_state) < ENTRY_STATE_MIN_LENGTH && in_array(ACCOUNT_STATE, ['required', 'required_register'])) {
                $error = true;
                $errors->s_entry_state_error = true;
            } else {
                $data['shipto']['zone_id'] = 0;
                $data['shipto']['state'] = $s_state;
            }
        }
        //}

        if (!$csa) {

            if (in_array(ACCOUNT_GENDER, ['required', 'required_register', 'visible', 'visible_register'])) {
                $gender = tep_db_prepare_input($_POST['entry_gender']);
                if (in_array(ACCOUNT_GENDER, ['required', 'required_register'])) {
                    if (($gender != 'm') && ($gender != 'f') && ($gender != 's')) {
                        $error = true;
                        $errors->entry_gender_error = true;
                    }
                }
                $data['billto']['gender'] = $gender;
            }

            //if (!$saID){
            if (in_array(ACCOUNT_FIRSTNAME, ['required', 'required_register', 'visible', 'visible_register'])) {
                $firstname = tep_db_prepare_input($_POST['entry_firstname']);
                if (in_array(ACCOUNT_FIRSTNAME, ['required', 'required_register'])) {
                    if (strlen($firstname) < ENTRY_FIRST_NAME_MIN_LENGTH) {
                        $error = true;
                        $errors->entry_firstname_error = true;
                    }
                }
                $data['billto']['firstname'] = $firstname;
            }

            if (in_array(ACCOUNT_LASTNAME, ['required', 'required_register', 'visible', 'visible_register'])) {
                $lastname = tep_db_prepare_input($_POST['entry_lastname']);
                if (in_array(ACCOUNT_FIRSTNAME, ['required', 'required_register'])) {
                    if (strlen($lastname) < ENTRY_LAST_NAME_MIN_LENGTH) {
                        $error = true;
                        $errors->entry_lastname_error = true;
                    }
                }
                $data['billto']['lastname'] = $lastname;
            }

            if (in_array(ACCOUNT_POSTCODE, ['required', 'required_register', 'visible', 'visible_register'])) {
                $postcode = tep_db_prepare_input($_POST['entry_postcode']);
                if (in_array(ACCOUNT_POSTCODE, ['required', 'required_register']) && strlen($postcode) < ENTRY_POSTCODE_MIN_LENGTH) {
                    $error = true;
                    $errors->entry_post_code_error = true;
                }
                $data['billto']['postcode'] = $postcode;
            }

            if (in_array(ACCOUNT_STREET_ADDRESS, ['required', 'required_register', 'visible', 'visible_register'])) {
                $street_address = tep_db_prepare_input($_POST['entry_street_address']);
                if (in_array(ACCOUNT_STREET_ADDRESS, ['required', 'required_register']) && strlen($street_address) < ENTRY_STREET_ADDRESS_MIN_LENGTH) {
                    $error = true;
                    $errors->entry_street_address_error = true;
                }
                $data['billto']['street_address'] = $street_address;
            }

            if (in_array(ACCOUNT_SUBURB, ['required', 'required_register', 'visible', 'visible_register'])) {
                $suburb = tep_db_prepare_input($_POST['entry_suburb']);
                if (in_array(ACCOUNT_SUBURB, ['required', 'required_register']) && empty($suburb)) {
                    $error = true;
                    $errors->entry_suburb_error = true;
                }
                $data['billto']['suburb'] = $suburb;
            }

            if (in_array(ACCOUNT_CITY, ['required', 'required_register', 'visible', 'visible_register'])) {
                $city = tep_db_prepare_input($_POST['entry_city']);
                if (in_array(ACCOUNT_CITY, ['required', 'required_register']) && strlen($city) < ENTRY_CITY_MIN_LENGTH) {
                    $error = true;
                    $errors->entry_city_error = true;
                }
                $data['billto']['city'] = $city;
            }

            $entry_country_id = tep_db_prepare_input($_POST['entry_country_id']);
            if (in_array(ACCOUNT_COUNTRY, ['required', 'required_register', 'visible', 'visible_register'])) {
                if (in_array(ACCOUNT_COUNTRY, ['required', 'required_register']) && (int) $entry_country_id == 0) {
                    $error = true;
                    $errors->entry_country_error = true;
                }
                $data['billto']['country_id'] = $entry_country_id;
            }

            if (in_array(ACCOUNT_STATE, ['required', 'required_register', 'visible', 'visible_register'])) {
                $state = tep_db_prepare_input($_POST['entry_state']);
                $zones = \common\helpers\Zones::get_country_zones($entry_country_id);
                if (is_array($zones) && count($zones)) {
                    if (isset($_POST['entry_zone_id'])) {
                        $state = tep_db_prepare_input($_POST['entry_zone_id']);
                    }
                    $zones = \yii\helpers\ArrayHelper::map($zones, 'id', 'id');
                    if (in_array(ACCOUNT_STATE, ['required', 'required_register']) && !in_array($state, $zones)) {
                        $error = true;
                        $errors->entry_state_error = true;
                    } else {
                        $data['billto']['zone_id'] = $state;
                        $data['billto']['state'] = \common\helpers\Zones::get_zone_name($entry_country_id, $state, '');
                    }
                } else if (strlen($state) < ENTRY_STATE_MIN_LENGTH && in_array(ACCOUNT_STATE, ['required', 'required_register'])) {
                    $error = true;
                    $errors->entry_state_error = true;
                } else {
                    $data['billto']['zone_id'] = 0;
                    $data['billto']['state'] = $state;
                }
            }
        }

        return ['error' => $error, 'errors' => $errors, 'data' => $data];
    }
    
    public function getAddresses($customer) {
        $languages_id = \Yii::$app->settings->get('languages_id');
        $fields = array("entry_street_address", "entry_firstname", "entry_lastname", "entry_city", "entry_postcode", "entry_country_id", "entry_country");
        if (in_array(ACCOUNT_GENDER, ['required', 'required_register', 'visible', 'visible_register'])) {
            $fields[] = "entry_gender";
        }
        /* if (ACCOUNT_COMPANY == 'true') {
          $fields[] = "entry_company";
          } */
        if (in_array(ACCOUNT_STATE, ['required', 'required_register', 'visible', 'visible_register'])) {
            $fields[] = "entry_state";
        }
        if (in_array(ACCOUNT_SUBURB, ['required', 'required_register', 'visible', 'visible_register'])) {
            $fields[] = "entry_suburb";
        }

        $js_arrs = 'var fields = new Array("' . implode('", "', $fields) . '");' . "\n";

        foreach ($fields as $field) {
            $js_arrs .= 'var ' . $field . ' = new Array();' . "\n";
        }

        $address_query = tep_db_query("select ab.*, c.countries_name  from " . TABLE_ADDRESS_BOOK . " ab left join " . TABLE_COUNTRIES . " c on ab.entry_country_id=c.countries_id  and c.language_id = '" . (int) $languages_id . "' left join " . TABLE_ZONES . " z on z.zone_country_id=c.countries_id and ab.entry_zone_id=z.zone_id where customers_id = '" . (int)$customer . "' ");
        $addresses = array();
        foreach ($fields as $field) {
            $js_arrs .= '' . $field . '[0] = "";' . "\n";
        }
        while ($d = tep_db_fetch_array($address_query)) {
            $state = $d['entry_state'];
            foreach ($fields as $field) {
                if ($field == "entry_state" && !tep_not_null($d['entry_state']) && $d['entry_zone_id']) {
                    $d[$field] = $d['entry_zone_id'];
                    $state = \common\helpers\Zones::get_zone_name($d['entry_country_id'], $d['entry_zone_id'], '');
                }
                if ($field == "entry_country")
                    $d[$field] = \common\helpers\Country::get_country_name($d['entry_country_id']);
                $js_arrs .= '' . $field . '[' . $d['address_book_id'] . '] = "' . $d[$field] . '";' . "\n";
            }
            $addresses[] = array(
                'id' => $d['address_book_id'],
                'text' => $d['entry_company'] . ' ' . $d['entry_firstname'] . ' ' . $d['entry_lastname'] . ' ' . $d['entry_suburb'] . ' ' . $d['entry_city'] . ' ' . $state . ' ' . $d['entry_postcode'] . ' ' . $d['countries_name'],
            );
        }
        return [$js_arrs, $addresses];
    }
    
    public function getOrderAddresses($oID) {
        $fields = array("entry_street_address", "entry_firstname", "entry_lastname", "entry_city", "entry_postcode", "entry_country_id", "entry_country");
        if (in_array(ACCOUNT_GENDER, ['required', 'required_register', 'visible', 'visible_register'])) {
            $fields[] = "entry_gender";
        }
        /* if (ACCOUNT_COMPANY == 'true') {
          $fields[] = "entry_company";
          } */
        if (in_array(ACCOUNT_STATE, ['required', 'required_register', 'visible', 'visible_register'])) {
            $fields[] = "entry_state";
        }
        if (in_array(ACCOUNT_SUBURB, ['required', 'required_register', 'visible', 'visible_register'])) {
            $fields[] = "entry_suburb";
        }

        $js_arrs = 'var fields = new Array("' . implode('", "', $fields) . '");' . "\n";

        foreach ($fields as $field) {
            //if ($field == 'entry_country_id') $field = "entry_country";
            $js_arrs .= 'var ' . $field . ' = new Array();' . "\n";
        }

        $address_query = tep_db_query("select * from " . $this->table_prefix . TABLE_ORDERS . "  where orders_id = '" . (int) $oID . "' ");
        $addresses = array();
        foreach ($fields as $field) {
            $js_arrs .= '' . $field . '[0] = "";' . "\n";
        }
        $d = $t = tep_db_fetch_array($address_query);
        foreach ($fields as $field) {
            if ($field == "entry_state" && !tep_not_null($d['delivery_state'])) {
                if ($zone_id = \common\helpers\Zones::get_zone_id(\common\helpers\Country::get_country_id($d['delivery_country']), $d['delivery_state'])) {
                    $d[$field] = $zone_id;
                }
            }
            //if ($field == 'entry_country_id') $field = "entry_country";
            $js_arrs .= '' . $field . '[' . $d['delivery_address_book_id'] . '] = "' . $d['delivery_' . substr($field, 6)] . '";' . "\n";
        }
        $addresses[] = array(
            'id' => $d['delivery_address_book_id'],
            'text' => $d['delivery_company'] . ' ' . $d['delivery_firstname'] . ' ' . $d['delivery_lastname'] . ' ' . $d['delivery_suburb'] . ' ' . $d['delivery_city'] . ' ' . $d['delivery_state'] . ' ' . $d['delivery_postcode'] . ' ' . $d['delivery_country'],
        );
        $d = $t;
        if ($d['delivery_address_book_id'] != $d['billing_address_book_id']) {
            foreach ($fields as $field) {
                if ($field == "entry_state" && !tep_not_null($d['billing_state'])) {
                    if ($zone_id = \common\helpers\Zones::get_zone_id(\common\helpers\Country::get_country_id($d['billing_country']), $d['billing_state'])) {
                        $d[$field] = $zone_id;
                    }
                }
                //if ($field == 'entry_country_id') $field = "entry_country";			  
                $js_arrs .= '' . $field . '[' . $d['billing_address_book_id'] . '] = "' . $d['billing_' . substr($field, 6)] . '";' . "\n";
            }

            $addresses[] = array(
                'id' => $d['billing_address_book_id'],
                'text' => $d['billing_company'] . ' ' . $d['billing_firstname'] . ' ' . $d['billing_lastname'] . ' ' . $d['billing_suburb'] . ' ' . $d['billing_city'] . ' ' . $d['billing_state'] . ' ' . $d['billing_postcode'] . ' ' . $d['billing_country'],
            );
        }


        return [$js_arrs, $addresses];
    }
    
    public function actionAddproduct() {
        global $order, $quote, $cart;
        $languages_id = \Yii::$app->settings->get('languages_id');
        $oID = Yii::$app->request->get('orders_id', '');
        $currentCart = isset($_GET['currentCart'])?$_GET['currentCart']:$_POST['currentCart'];

        \common\helpers\Translation::init('admin/orders');
        \common\helpers\Translation::init('admin/orders/order-edit');

        $currencies = Yii::$container->get('currencies');
        $currency = \Yii::$app->settings->get('currency');

        $admin = new AdminCarts();
        $admin->loadCustomersBaskets();
        if (tep_not_null($oID)) {
            $admin->setCurrentCartID($currentCart);
        } else{
            $admin->setCurrentCartID($currentCart, true);
        }        
        $admin->loadCurrentCart();

        $params['oID'] = $oID;
        $params['search'] = $_GET['search'];
        $params['action'] = $_GET['action'];

        /* if (tep_session_is_registered('cart')) {
          $quote = &$_SESSION['cart'];
          } else {
          //	$quote = new \common\classes\shopping_cart($oID);
          //	$_SESSION['cart'] = &$quote;
          } */

        if (tep_not_null($oID)) {
            $info = tep_db_fetch_array(tep_db_query("select customers_id, language_id, platform_id, currency, basket_id, delivery_address_book_id, billing_address_book_id from " . $this->table_prefix . TABLE_ORDERS . " where orders_id = '" . (int) $oID . "'"));
            $customer_id = $info['customers_id'];
            $currency = $info['currency'];
            $language_id = $info['language_id'];
            $paltform_id = $info['platform_id'];
            $basket_id = $info['basket_id'];
        } else {
            $customer_id = $quote->customer_id;
            $currency = $quote->currency;
            $language_id = $quote->language_id;
            $paltform_id = $quote->platform_id;
            $basket_id = $quote->basketID;
        }
        \Yii::$app->settings->set('currency', $currency);

        $platform_config = new platform_config($paltform_id);
        $platform_config->constant_up();
        //$order = new \common\classes\Order($oID);
        $customer_loaded = false;
        $customer = new Customer();
        if ($customer->loadCustomer($customer_id)) {
            $customer->setParam('languages_id', $language_id);
            $customer->setParam('currency', $currency);
            $customer->setParam('platform_id', $paltform_id);
            $customer->convertToSession();
            $customer_loaded = true;
        }

        if (!$customer_loaded) {
            $order = new \common\classes\Order($oID);
        } else {
            $order = new \common\classes\Order();
            $order->order_id = $oID;
        }
        //echo '<pre>';print_r($order);die;
        //$quote->clearTotals();
        //echo '<pre>';print_r($quote);die;
        if (is_object($quote))
            $quote->clearTotalKey('ot_shipping');
        if (isset($_POST['action']) && $_POST['action'] == 'add_product') {
            if (isset($_POST['products_id']) && is_numeric($_POST['products_id']) /* && tep_check_product((int)$_POST['products_id']) */) {
                $attributes = [];
                $uprid = urldecode($_POST['uprid']);
                $old_uprid = \common\helpers\Inventory::normalize_id(\common\helpers\Inventory::normalize_id($uprid), $attributes); // bundles sorting attributes need twice normalizing

                if (isset($_POST['id']) && is_array($_POST['id']) && count($_POST['id'])) {
                    $attributes = [];
                    foreach ($_POST['id'] as $_k => $_v) {
                        if (tep_not_null($_v) && $_v > 0) {
                            $attributes[$_k] = $_v;
                        }
                    }
                    $uprid = \common\helpers\Inventory::get_uprid($_POST['products_id'], $attributes);
                    $uprid = \common\helpers\Inventory::normalize_id($uprid, $attributes);
                }

                $_qty = (int) (is_array($_POST['qty']) ? array_sum($_POST['qty']) : $_POST['qty']);
                $_uprid = \common\helpers\Inventory::get_uprid($_POST['products_id'], $attributes);
                //$add_qty = /*$quote->get_quantity($_uprid)+*/$_qty;
                $reserved_qty = $quote->get_reserved_quantity($_uprid);
                /*if (defined('STOCK_CHECK') && STOCK_CHECK == 'true') {
                    $product_qty = \common\helpers\Product::get_products_stock($_uprid);
                    $stock_indicator = \common\classes\StockIndication::product_info(array(
                                'products_id' => $_uprid,
                                'products_quantity' => $product_qty,
                    ));
                    $stock_indicator['max_qty'] = MAX_CART_QTY;

                    if ($_qty > $reserved_qty) {
                        if ($_qty > $product_qty && !$stock_indicator['allow_out_of_stock_add_to_cart']) {
                            $_qty = $product_qty;
                        }
                        if ($_qty < 1) {
                            $customer->convertBackSession();
                            if (Yii::$app->request->isAjax) {
                                $messageStack->add(TEXT_PRODUCT_OUT_STOCK, 'one_page_checkout', 'error');
                                return $this->actionOrderEdit();
                            } else {
                                $messageStack->add_session(TEXT_PRODUCT_OUT_STOCK, 'one_page_checkout', 'error');
                                if (tep_not_null($oID)) {
                                    $url = \yii\helpers\Url::to(['orders/order-edit', 'orders_id' => $oID]) . '#products';
                                    return $this->redirect($url);
                                } else {
                                    $url = \yii\helpers\Url::to(['orders/order-edit']) . '#products';
                                    return $this->redirect($url);
                                }
                            }
                        }
                    }
                }*/

                $tax = (isset($_POST['tax']) ? $_POST['tax'] : null);
                $final_price = (isset($_POST['final_price']) ? (float) $_POST['final_price'] : null);
                $use_default_price = (strtolower($_POST['use_default_price']) == 'on' ? true : false);
                $name = (isset($_POST['name']) ? stripslashes($_POST['name']) : null);
                $use_default_name = (strtolower($_POST['use_default_name']) == 'on' ? true : false);

                if ($quote->in_cart($old_uprid) && strpos($old_uprid, '(GA)') === false) {
                    //$products_id = \common\helpers\Inventory::get_uprid((int) $_POST['products_id'], $attributes);
                    $products_id = $old_uprid; //\common\helpers\Inventory::normalize_id($old_uprid);
                    if ($products_id != $uprid) {
                        $quote->remove($old_uprid);
                    }
                }

                $gift_wrap = (isset($_POST['gift_wrap']) ? $_POST['gift_wrap'] : null);
                if (!is_null($gift_wrap)) {
                    $gift_wrap = (in_array(strtolower($gift_wrap), ['true', 'on']) ? true : false);
                } else {
                    $gift_wrap = false;
                }

                global $new_products_id_in_cart;
                if (is_array($_POST['qty'])) {
                    $packQty = [
                        'qty' => array_sum($_POST['qty']),
                        'unit' => (int) $_POST['qty'][0] / max(1, (int) $_POST['qty_'][0]),
                        'pack_unit' => (int) $_POST['qty'][1] / max(1, (int) $_POST['qty_'][1]),
                            //'packaging' => (int)$_POST['qty'][2] / max(1, (int)$_POST['qty_'][2]),
                    ];
                    $packQty['packaging'] = (int) $_POST['qty'][2] / ($_POST['qty_'][1] > 0 ? max(1, (int) $_POST['qty_'][2] * $_POST['qty_'][1]) : max(1, (int) $_POST['qty_'][2]) );
                } else {
                    $packQty = $_qty;
                }
                $quote->add_cart((int) $_POST['products_id'], $packQty, $attributes, true, 0, $gift_wrap);
                if (tep_not_null($new_products_id_in_cart)) {
                    $uprid = $new_products_id_in_cart;
                }

                if (!is_null($tax)) {
                    $ex = explode("_", $tax);
                    $tax_value = 0;
                    if (count($ex) == 2) {
                        $tax_value = \common\helpers\Tax::get_tax_rate_value_edit_order($ex[0], $ex[1]);
                        $quote->setOverwrite($uprid, 'tax_selected', $tax);
                        $quote->setOverwrite($uprid, 'tax', $tax_value);
                        $quote->setOverwrite($uprid, 'tax_class_id', $ex[0]);
                        $quote->setOverwrite($uprid, 'tax_description', \common\helpers\Tax::get_tax_description($ex[0], $order->tax_address['entry_country_id'], $ex[1]));
                    } else {
                        $quote->setOverwrite($uprid, 'tax_selected', 0);
                        $quote->setOverwrite($uprid, 'tax', 0);
                        $quote->setOverwrite($uprid, 'tax_class_id', 0);
                        $quote->setOverwrite($uprid, 'tax_description', '');
                    }
                }
                if (!is_null($final_price)) {
                    $final_price = $final_price * $currencies->get_market_price_rate($currency, DEFAULT_CURRENCY);
                    if (!is_null($tax)) {
                        //$final_price = \common\helpers\Tax::get_untaxed_value($final_price, $tax_value);
                    }
                    $quote->setOverwrite($uprid, 'final_price', $final_price);
                }
                if ($use_default_price) {
                    $quote->clearOverwritenKey($uprid, 'final_price');
                }
                if (is_array($packQty)) {
                    if ($ext = Acl::checkExtension('PackUnits', 'saveItemsIntoCart')) {
                        $ext::saveItemsIntoCart($uprid, $packQty);
                    }
                    if ($ext = Acl::checkExtension('PackUnits', 'getProductsCartFrontend')) {
                        $data = $ext::getProductsCartFrontend($uprid, $quote->contents);
                        if ($ext = Acl::checkExtension('PackUnits', 'savePriceIntoCart')) {
                            $ext::savePriceIntoCart($uprid, $data);
                        }
                    }
                }
                if (!is_null($name)) {
                    $quote->setOverwrite($uprid, 'name', $name);
                }
                if ($use_default_name) {
                    $quote->clearOverwritenKey($uprid, 'name');
                }
                $quote->setAdjusted();
            }
            $quote->clearTotalKey('ot_tax');
            $quote->clearTotalKey('ot_gift_wrap');
            //$quote->clearTotalKey('ot_coupon');
            
            $cart = $quote;

            $admin->saveCustomerBasket($quote);
            $customer->convertBackSession();

            if (Yii::$app->request->isAjax) {
                return $this->actionOrderEdit();
            } else {
                if (tep_not_null($oID)) {
                    $url = \yii\helpers\Url::to(['samples/order-edit', 'orders_id' => $oID]) . '#products';
                    return $this->redirect($url);
                } else {
                    $url = \yii\helpers\Url::to(['samples/order-edit']) . '#products';
                    return $this->redirect($url);
                }
            }
        } elseif (isset($_POST['action']) && $_POST['action'] == 'remove_product') {
            if (isset($_POST['products_id'])) {
                $uprid = urldecode($_POST['products_id']);
                $uprid = \common\helpers\Inventory::normalize_id($uprid);
                $quote->remove($uprid);
            }
            $admin->saveCustomerBasket($quote);
            $customer->convertBackSession();
            if (Yii::$app->request->isAjax) {
                return $this->actionOrderEdit();
            } else {
                return $this->redirect(['samples/order-edit', 'orders_id' => $oID]);
            }
        } elseif (isset($_GET['action']) && $_GET['action'] == 'show_giveaways') {
            $giveaways = \common\helpers\Gifts::getGiveAways();
            $admin->saveCustomerBasket($quote);
            $customer->convertBackSession();
            return $this->renderAjax('give-away', ['products' => $giveaways, 'oID' => $oID, 'nopopup' => false]);
        } elseif (isset($_POST['action']) && $_POST['action'] == 'add_giveaway') {
            if (isset($_POST['product_id']) && is_numeric($_POST['product_id'])) {
                if (isset($_POST['giveaway_switch'])) {
                    foreach ($_POST['giveaway_switch'] as $gaw_id => $data) {
                        //like radio if ( $data != 10 ) continue;
                        if (!isset($_POST['giveaways'][$gaw_id]) || !isset($_POST['giveaways'][$gaw_id]['products_id']))
                            continue;
                        if ($_POST['giveaways'][$gaw_id]['products_id'] != $_POST['product_id'])
                            continue;
                        $ga_data = $_POST['giveaways'][$gaw_id];
                        if ($quote->is_valid_product_data($ga_data['products_id'], isset($ga_data['id']) ? $ga_data['id'] : '')) {
                            $quote->add_cart($ga_data['products_id'], \common\helpers\Gifts::get_max_quantity($ga_data['products_id'], $gaw_id)['qty'], isset($ga_data['id']) ? $ga_data['id'] : '', false, $gaw_id);
                        }
                    }
                }
            }
            $admin->saveCustomerBasket($quote);
            $customer->convertBackSession();
            return $this->redirect(['samples/order-edit', 'orders_id' => $oID]);
        } else if (isset($_POST['action']) && $_POST['action'] == 'remove_giveaway') {
            if (isset($_POST['products_id'])) {
                $uprid = urldecode($_POST['products_id']);
                $quote->remove_giveaway($uprid);
            }
            $admin->saveCustomerBasket($quote);
            $customer->convertBackSession();
            if (Yii::$app->request->isAjax) {
                return $this->actionOrderEdit();
            } else {
                return $this->redirect(['samples/order-edit', 'orders_id' => $oID]);
            }
        } else if (strlen($_GET['search'])) {
            $products_array = array();
            $products2c_join = '';
            if ($paltform_id) {
                $products2c_join .= " inner join " . TABLE_PLATFORMS_PRODUCTS . " plp on p.products_id = plp.products_id  and plp.platform_id = '" . $paltform_id . "' " .
                        " inner join " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c ON p2c.products_id=p.products_id left join " . TABLE_CATEGORIES . " c on c.categories_id = p2c.categories_id and c.categories_status = 1 " .
                        " inner join " . TABLE_PLATFORMS_CATEGORIES . " plc ON plc.categories_id=p2c.categories_id AND plc.platform_id = '" . $paltform_id . "' ";
            }
            $products_query = tep_db_query("select distinct p.products_id, ".ProductNameDecorator::instance()->listingQueryExpression('pd','')." AS products_name, p.products_price, p.products_tax_class_id from " . TABLE_PRODUCTS . " p {$products2c_join}, " . TABLE_PRODUCTS_DESCRIPTION . " pd  where p.products_status =1 and p.products_id = pd.products_id and (pd.products_name like '%" . tep_db_input(tep_db_prepare_input($_GET['search'])) . "%' or p.products_model like '%" . tep_db_input(tep_db_prepare_input($_GET['search'])) . "%' or p.products_price like '%" . tep_db_input(tep_db_prepare_input($_GET['search'])) . "%' ) and pd.language_id = '" . (int) $languages_id . "' and pd.platform_id = '".intval(\common\classes\platform::defaultId())."' order by products_name");
            if (tep_db_num_rows($products_query)) {

                while ($products = tep_db_fetch_array($products_query)) {
                    // $products['products_price'] = tep_get_products_price_edit_order($products['products_id'], $currencies->currencies[$order->info['currency']]['id'], $group_id, 1, true);
                    $products_array[] = array('id' => $products['products_id'], 'text' => $products['products_name'], 'image' => \common\classes\Images::getImageUrl($products['products_id']), 'price' => $currencies->format(\common\helpers\Product::get_products_price($products['products_id'], 1), true, $currency), 'tax_class_id' => $products['products_tax_class_id']);
                }
            } else {
                $products_array[] = array('id' => '0', 'text' => TEXT_NO_PRODUCTS_FOUND, 'price' => '', 'image' => '', 'tax_class_id' => 0);
            }
            $admin->saveCustomerBasket($quote);
            $customer->convertBackSession();
            echo json_encode($products_array);
            exit();
        } else if (($_GET['products_id']) && $_GET['products_id'] > 0 && $_GET['details']) {
            $products_id = Yii::$app->request->get('products_id');
            $qty = Yii::$app->request->post('qty', 1);
            $has_inventory = \common\helpers\Inventory::product_has_inventory($products_id);
            $params['isAjax'] = true;
            $params['qty'] = $qty;
            $result['has_inventory'] = $has_inventory;
            $attributes = Yii::$app->request->post('id');
            $uprid = $products_id;
            if (is_array($attributes)) {
                $prepare = [];
                for ($i = 0; $i < count($attributes); $i++) {
                    if (tep_not_null($attributes[$i]))
                        $prepare[$i] = $attributes[$i];
                }
                $attributes = $prepare;
                $uprid = \common\helpers\Inventory::normalize_id($products_id, $attributes);
            } else {
                parse_str(Yii::$app->request->post('id', array()), $attributes);
                if (isset($attributes['id'])) {
                    $attributes = $attributes['id'];
                    $uprid = \common\helpers\Inventory::get_uprid((int) $products_id, $attributes);
                    $uprid = \common\helpers\Inventory::normalize_id($uprid, $attributes);
                } else {
                    $_uprid = Yii::$app->request->post('id');
                    if (strpos($_uprid, '{') !== false) {
                        $uprid = \common\helpers\Inventory::normalize_id($_uprid, $attributes);
                    } else {
                        $uprid = $products_id;
                        $uprid = \common\helpers\Inventory::normalize_id($uprid, $attributes);
                    }
                }
            }
            if (!is_array($attributes))
                $attributes = [];
            $_attributes = $attributes;

            $result = \common\helpers\Attributes::getDetails($products_id, $attributes, $params);
            if (isset($result['stock_indicator']) && is_array($result['stock_indicator'])) {
                $result['stock_indicator']['quantity_max'] += $quote->get_reserved_quantity($uprid);
            }

            $result['product_attributes'] = $this->renderAjax('attributes', ['attributes' => $result['attributes_array']]);
            $result['current_attributes'] = $attributes;

            $result['order_quantity'] = \common\helpers\Product::get_product_order_quantity($products_id);

            $bundles = \common\helpers\Bundles::getDetails(['products_id' => $products_id, 'id' => $_attributes]);
            $result['bundles_block'] = '';
            $result['bundles'] = array();
            if ($bundles) {
                $result['bundles'] = $bundles;
                $result['bundles_block'] = $this->renderAjax('bundle', ['products' => $bundles]);
            }

            $discounts = array();
            $dt = \common\helpers\Product::get_products_discount_table($products_id);
            if ($dt && is_array($dt)) {
                $discounts[] = array(
                    'count' => 1,
                    'price' => \common\helpers\Product::get_products_price($products_id),
                    'price_with_tax' => $currencies->display_price($dt[$i + 1], \common\helpers\Tax::get_tax_rate(\common\helpers\Product::get_products_info($products_id, 'products_tax_class_id')))
                );
                for ($i = 0, $n = sizeof($dt); $i < $n; $i = $i + 2) {
                    if ($dt[$i] > 0) {
                        $discounts[] = array(
                            'count' => $dt[$i],
                            'price' => $dt[$i + 1],
                            'price_with_tax' => $currencies->display_price($dt[$i + 1], \common\helpers\Tax::get_tax_rate(\common\helpers\Product::get_products_info($products_id, 'products_tax_class_id')))
                        );
                    }
                }
            }
            $result['discount_table_data'] = $discounts;
            $result['discount_table_view'] = (count($discounts) ? $this->renderAjax('quantity-discounts', ['discounts' => $discounts]) : '');
            if ($ext = Acl::checkExtension('PackUnits', 'quantityBoxFrontend')) {
                $result['product_details'] = $ext::quantityBoxFrontend($params, ['products_id' => $products_id]);
                if ($ext = Acl::checkExtension('PackUnits', 'getPricePack')) {
                    $data = $ext::getPricePack($products_id, true);
                    $result['product_details']['single_price_data'] = $data;
                }
            }
            $admin->saveCustomerBasket($quote);
            $customer->convertBackSession();
            echo json_encode($result);
            exit();
        } else if (isset($_GET['products_id']) && strlen($_GET['products_id']) > 0) {
            $params['products_id'] = $_GET['products_id'];
            $params['tax_class_id'] = \common\helpers\Product::get_products_info($params['products_id'], 'products_tax_class_id');
            $rate = \common\helpers\Tax::get_tax_rate($params['tax_class_id'], $order->tax_address['entry_country_id'], $order->tax_address['entry_zone_id']);
            if (!$rate) {
                $params['tax_class_id'] = 0;
            }
            $pa_options = [];
            $params['options'] = $pa_options;
            $tax_class_array = \common\helpers\Tax::get_complex_classes_list();
            $rates_query = tep_db_query("select tr.tax_class_id, tr.tax_zone_id, tr.tax_rate from " . TABLE_TAX_RATES . " tr inner join " . TABLE_TAX_CLASS . " tc on tc.tax_class_id = tr.tax_class_id where 1 group by tr.tax_class_id, tr.tax_zone_id");
            $rates = [];
            if (tep_db_num_rows($rates_query)) {
                while ($row = tep_db_fetch_array($rates_query)) {
                    $rates[$row['tax_class_id'] . '_' . $row['tax_zone_id']] = $row['tax_rate'];
                }
            }
            $params['has_inventory'] = false; //\common\helpers\Inventory::product_has_inventory($params['products_id']) > 0;
            $params['rates'] = $rates;
            $params['image'] = \common\classes\Images::getImage((int) $params['products_id'], 'Small');
            //check giveaway
            $result['ga'] = \common\helpers\Gifts::getGiveAways($params['products_id']);

            /* if (!is_null($result['ga'])) {
              $result['ga'] = $result['ga'][0]['price_b']; //$this->renderAjax('give-away', ['products' => $result['ga'], 'nopopup' => true]);
              } else {
              $result['ga'] = '';
              } */
            $params['ga'] = $result['ga'];
            $params['gift_wrap_allowed'] = \common\helpers\Gifts::allow_gift_wrap($params['products_id']);
            $params['gift_wrap_price'] = 0;
            if ($params['gift_wrap_allowed']) {
                $params['gift_wrap_price'] = \common\helpers\Gifts::get_gift_wrap_price($params['products_id']) * $currencies->get_market_price_rate(DEFAULT_CURRENCY, $currency); //$currencies->format_clear(\common\helpers\Gifts::get_gift_wrap_price($params['products_id']), true, $currency);
            }

            $params['product_name'] = \common\helpers\Product::get_backend_products_name($params['products_id'], $language_id);
            $params['currency'] = $currency;
            $params['is_editing'] = false;
            $params['product']['qty'] = 1;
            $params['product']['units'] = 0;
            $params['product']['packs'] = 0;
            $params['product']['packagings'] = 0;
            $render = 'product_details';
            if (isset($_GET['action']) && $_GET['action'] == 'edit_product') {
                $uprid = urldecode($params['products_id']);
                $uprid = \common\helpers\Inventory::normalize_id($uprid);
                $params['product'] = null;
                if ($quote->in_cart($uprid) /* && strpos($uprid, '(GA)') === false */) {
                    $products = $quote->get_products();

                    if (count($products)) {
                        foreach ($products as $_p) {
                            if ($_p['id'] == $uprid && !$_p['ga']) {
                                $_p['products_id'] = (int) $_p['id'];
                                $_p['final_price'] = $_p['final_price'] * $currencies->get_market_price_rate(DEFAULT_CURRENCY, $currency);
                                $_p['old_name'] = addslashes($_p['name']);//addslashes(\common\helpers\Product::get_backend_products_name($_p['id'], $language_id));
                                $_p['name'] = addslashes($_p['name']);
                                $_p['qty'] = (int) $_p['quantity'];
                                $ov = $quote->getOwerwritten($uprid);
                                $_p['selected_rate'] = 0;
                                if (isset($ov['tax_selected']))
                                    $_p['selected_rate'] = $ov['tax_selected'];

                                $_p['price_manualy_modified'] = ($quote->getOwerwrittenKey($_p['id'], 'final_price') ? 'true' : 'false');
                                $params['product'] = $_p;
                                break;
                            }
                        }
                    }
                }
                $render = 'edit_product';
                $params['is_editing'] = true;
            }
            if ($ext = Acl::checkExtension('PackUnits', 'quantityBoxFrontend')) {
                $params['product_details'] = $ext::quantityBoxFrontend($params['product'], $params);
            }
            $currentCart = $admin->getCurrentCartID();
            $admin->saveCustomerBasket($quote);
            $customer->convertBackSession();
            return $this->renderAjax($render, ['params' => $params, 'cart' => $quote, 'tax_class_array' => $tax_class_array, 'currentCart' => $currentCart]);
        }
        $admin->saveCustomerBasket($quote);
        $currentCart = $admin->getCurrentCartID();
        $customer->convertBackSession();

        \common\helpers\Translation::init('admin/platforms');

        $platform_id = (int) $quote->platform_id;

        $category_tree_array = [];
        $category_tree_array = \common\helpers\Categories::get_full_category_tree(0, '', '', $category_tree_array, false, $quote->platform_id, true);

        $params['category_tree_array'] = $category_tree_array;
        $params['searchsuggest'] = (count($category_tree_array) > 5000);

        return $this->renderAjax('product', ['params' => $params, 'currentCart' => $currentCart]);
    }
    
    public function actionUpdatepay() {
        $currencies = Yii::$container->get('currencies');
        $session = new \yii\web\Session;

        \common\helpers\Translation::init('admin/main');
        \common\helpers\Translation::init('admin/orders/order-edit');
        $this->view->headingTitle = HEADING_TITLE;
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('orders/index'), 'title' => HEADING_TITLE);
        $this->layout = false;

        $orders_id = Yii::$app->request->post('orders_id', 0);

        if ($orders_id) {
            $result = tep_db_fetch_array(tep_db_query("select customers_id, platform_id from " . $this->table_prefix . TABLE_ORDERS . " where orders_id='" . (int) $orders_id . "'"));
            $customers_id = $result['customers_id'];
            $platform_id = $result['platform_id'];
        } else {
            $platform_id = $session['platform_id'];
            $customers_id = $session['customer_id'];
        }

        $platform_config = new platform_config($platform_id);
        $platform_config->constant_up();

        $result = tep_db_fetch_array(tep_db_query("select credit_amount from " . TABLE_CUSTOMERS . " where customers_id = '" . (int) $customers_id . "'"));
        $credit_amount = $result['credit_amount'];

        //$new_ot_total = 0;
        $_c = tep_db_fetch_array(tep_db_query("select code, value from " . TABLE_CURRENCIES . " where currencies_id = '" . (int)$_POST['currency_id'] . "'"));
        $new_ot_total = Yii::$app->request->post('ot_total') * $currencies->get_market_price_rate($_c['code'], DEFAULT_CURRENCY);
        $ot_paid = (float) Yii::$app->request->post('ot_paid') * $currencies->get_market_price_rate($_c['code'], DEFAULT_CURRENCY);
        /* foreach ($update_totals as $key => $value) {
          if ($value['class'] == 'ot_total') {
          $new_ot_total = $value['value'];
          break;
          }
          } */

        //$result = tep_db_fetch_array(tep_db_query("select value_inc_tax from " . $this->table_prefix . TABLE_ORDERS_TOTAL . " where orders_id='" . $orders_id . "' and class ='ot_total'"));
        $old_ot_total = $ot_paid;

        /*if ($ext = \common\helpers\Acl::checkExtension('UpdateAndPay', 'getActions')) {
            return $ext::getActions($old_ot_total, $new_ot_total);
        }*/
        
        $difference_ot_total = $old_ot_total - $new_ot_total;
        $difference = ($difference_ot_total >= 0 ? true : false);

        return $this->render('updatepay', [
                    'new_ot_total' => $currencies->format($new_ot_total, true, $_c['code'], $_c['value']),
                    'old_ot_total' => $currencies->format($old_ot_total, true, $_c['code'], $_c['value']),
                    'difference_ot_total' => $currencies->format($difference_ot_total, true, $_c['code'], $_c['value']),
                    'pay_difference' => $difference_ot_total,
                    'difference' => $difference,
                    'difference_desc' => $difference ? CREDIT_AMOUNT : TEXT_AMOUNT_DUE,
        ]);
    }
    
    public function updateAddress($result) {
        global $customer_id;
        if (is_array($result) && $customer_id) {
//shipping addres
            $company = $result['data']['customers_company'];
            $company_vat = $result['data']['customers_company_vat'];
            $sql_data_array = [
                'entry_firstname' => $result['data']['shipto']['firstname'],
                'entry_lastname' => $result['data']['shipto']['lastname'],
                'entry_street_address' => $result['data']['shipto']['street_address'],
                'entry_postcode' => $result['data']['shipto']['postcode'],
                'entry_city' => $result['data']['shipto']['city'],
                'entry_country_id' => $result['data']['shipto']['country_id'],
                'entry_state' => $result['data']['shipto']['state'],
                'entry_zone_id' => $result['data']['shipto']['zone_id'],
            ];
            if (in_array(ACCOUNT_GENDER, ['required', 'required_register', 'visible', 'visible_register']))
                $sql_data_array['entry_gender'] = $result['data']['shipto']['gender'];
            if (in_array(ACCOUNT_SUBURB, ['required', 'required_register', 'visible', 'visible_register']))
                $sql_data_array['entry_suburb'] = $result['data']['shipto']['suburb'];
            if (in_array(ACCOUNT_COMPANY, ['required', 'required_register', 'visible', 'visible_register']))
                $sql_data_array['entry_company'] = $company;
            if (in_array(ACCOUNT_COMPANY_VAT_ID, ['required', 'required_register', 'visible', 'visible_register']))
                $sql_data_array['entry_company_vat'] = $company_vat;
            if ($result['data']['shipto']['saID'] > 0) {
                //update

                tep_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array, 'update', "address_book_id = '" . (int) $result['data']['shipto']['saID'] . "'");
                $saID = $result['data']['shipto']['saID'];
            } else {
                //insert
                $sql_data_array['customers_id'] = $customer_id;
                tep_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array);
                $saID = tep_db_insert_id();
            }

            if (!$result['data']['csa']) {
                $sql_data_array = [
                    'entry_firstname' => $result['data']['billto']['firstname'],
                    'entry_lastname' => $result['data']['billto']['lastname'],
                    'entry_street_address' => $result['data']['billto']['street_address'],
                    'entry_postcode' => $result['data']['billto']['postcode'],
                    'entry_city' => $result['data']['billto']['city'],
                    'entry_country_id' => $result['data']['billto']['country_id'],
                    'entry_state' => $result['data']['billto']['state'],
                    'entry_zone_id' => $result['data']['billto']['zone_id'],
                ];
                if (in_array(ACCOUNT_GENDER, ['required', 'required_register', 'visible', 'visible_register']))
                    $sql_data_array['entry_gender'] = $result['data']['billto']['gender'];
                if (in_array(ACCOUNT_SUBURB, ['required', 'required_register', 'visible', 'visible_register']))
                    $sql_data_array['entry_suburb'] = $result['data']['billto']['suburb'];
                if (in_array(ACCOUNT_COMPANY, ['required', 'required_register', 'visible', 'visible_register']))
                    $sql_data_array['entry_company'] = $company;
                if (in_array(ACCOUNT_COMPANY_VAT_ID, ['required', 'required_register', 'visible', 'visible_register']))
                    $sql_data_array['entry_company_vat'] = $company_vat;
                if ($result['data']['billto']['aID'] > 0) {
                    //update
                    tep_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array, 'update', "address_book_id = '" . (int) $result['data']['billto']['aID'] . "'");
                    $aID = $result['data']['billto']['aID'];
                } else {
                    //insert
                    $sql_data_array['customers_id'] = $customer_id;
                    tep_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array);
                    $aID = tep_db_insert_id();
                }
            } else {
                $aID = $saID;
            }
        }
        return ['aID' => $aID, 'saID' => $saID];
    }
}

