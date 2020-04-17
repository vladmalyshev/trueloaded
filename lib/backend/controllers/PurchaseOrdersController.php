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

use common\classes\platform_config;
use common\classes\platform;
use common\classes\shopping_cart;
use common\components\Customer;
use backend\models\AdminCarts;
use common\helpers\Acl;
use common\helpers\Status;
use Yii;

/**
 * default controller to handle user requests.
 */
class PurchaseOrdersController extends Sceleton {

    public $acl = ['BOX_HEADING_CUSTOMERS', 'BOX_PURCHASE_ORDERS'];
    public $table_prefix = 'purchase_';

    /**
     * Index action is the default action in a controller.
     */
    public function __construct($id, $module=''){
        \common\helpers\Translation::init('admin/purchase-orders');
        \common\helpers\Translation::init('admin/orders');

        if ($ext = \common\helpers\Acl::checkExtension('BusinessToBusiness', 'checkCustomerGroups')) {
            $ext::checkCustomerGroups();
        }
        define('GROUPS_IS_SHOW_PRICE', true);
        define('GROUPS_DISABLE_CHECKOUT', false);
        define('SHOW_OUT_OF_STOCK', 1);
        parent::__construct($id, $module);
    }

    public function actionIndex() {

        $this->selectedMenu = array('customers', 'purchase-orders');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('purchase-orders/index'), 'title' => HEADING_TITLE);
        if ($ext = \common\helpers\Acl::checkExtension('PurchaseOrders', 'adminActionCreate')) {
            $this->topButtons[] = '<a href="' . Yii::$app->urlManager->createUrl(['purchase-orders/create']) . '" class="create_item"><i class="icon-file-text"></i>' . TEXT_CREATE_NEW_OREDER . '</a>';
        }
        $this->view->headingTitle = HEADING_TITLE;
        $this->view->ordersTable = array(
            array(
                'title' => '<input type="checkbox" class="uniform">',
                'not_important' => 2
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
                'title' => TABLE_HEADING_ORDER_TOTAL,
                'not_important' => 0
            ),
            array(
                'title' => TEXT_ESTIMATED_DELIVERY,
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
            /*[
                'name' => TEXT_CUSTOMER_ID,
                'value' => 'cID',
                'selected' => '',
            ],*/
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
            /*[
                'name' => TEXT_WAREHOUSE,
                'value' => 'fullname',
                'selected' => '',
            ],*/
            /*[
                'name' => TEXT_CLIENT_EMAIL,
                'value' => 'email',
                'selected' => '',
            ],*/
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

	    $status = Status::getStatusListByTypeName('Purchase Orders', $_GET['status'] ?? '');

        $this->view->filters->status = $status;

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

        $this->view->filters->supplier = array();
        if ( isset($_GET['supplier']) && is_array($_GET['supplier']) ){
          foreach( $_GET['supplier'] as $_supplier_id ) if ( (int)$_supplier_id>0 ) $this->view->filters->supplier[] = (int)$_supplier_id;
        }

        if ($ext = \common\helpers\Acl::checkExtension('PurchaseOrders', 'adminActionIndex')) {
            return $ext::adminActionIndex();
        }

        return $this->render('index');
    }

    public function actionPurchaseOrdersList() {
        $languages_id = \Yii::$app->settings->get('languages_id');

        \common\helpers\Translation::init('admin/purchase-orders');

        $draw = Yii::$app->request->get('draw');
        $start = Yii::$app->request->get('start', 0);
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
        $output = [];
        parse_str($formFilter, $output);

        $filter = '';

        $filter_by_supplier = array();
        if ( isset($output['supplier']) && is_array($output['supplier']) ){
          foreach( $output['supplier'] as $_supplier_id ) if ( (int)$_supplier_id>0 ) $filter_by_supplier[] = (int)$_supplier_id;
        }

        if ( count($filter_by_supplier)>0 ) {
          $filter .= " and o.suppliers_id IN ('" . implode("','", $filter_by_supplier). "') ";
        }

        if (tep_not_null($output['search']))
        {
            $search = tep_db_prepare_input($output['search']);
            switch ($output['by']) {
                case 'cID':
                  $filter .= " and o.customers_id = '" . (int)$search . "' ";
                  break;
                case 'oID':
                  $filter .= " and (o.orders_id = '" . (int)$search . "' or o.orders_number = '" . tep_db_input($search) . "' )";
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
                  $filter .= " and o.delivery_name like '%" . tep_db_input($search) . "%' ";
                  break;
                case 'email':
                  $filter .= " and o.send_email like '%" . tep_db_input($search) . "%' ";
                  break;
                case '':
                case 'any':
                    $filter .= " and (";
                    $filter .= " o.orders_id = '" . tep_db_input($search) . "' ";
                    $filter .= " or o.orders_number like '%" . tep_db_input($search) . "%' ";
                    $filter .= " or op.products_model like '%" . tep_db_input($search) . "%' ";
                    $filter .= " or op.products_name like '%" . tep_db_input($search) . "%' ";
                    $filter .= " or o.delivery_name like '%" . tep_db_input($search) . "%' ";
                    $filter .= " or o.send_email like '%" . tep_db_input($search) . "%' ";
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
                    $orderBy = "o.date_purchased desc, o.orders_id desc";
                    break;
            }
        } else {
            $orderBy = "o.date_purchased desc, o.orders_id desc";
        }

		$_session->set('filter', $filter);

      $orders_query_raw = "select o.*, s.orders_status_name, sg.orders_status_groups_name, sg.orders_status_groups_color, ot.text_inc_tax as order_total " . ((tep_not_null($_GET['in_stock']) && $_GET['in_stock']!='') ? ", BIT_AND(" . (PRODUCTS_INVENTORY == 'True' ? "if(i.products_quantity is not null,if((i.products_quantity>=op.products_quantity),1,0),if((p.products_quantity>=op.products_quantity),1,0))" :"if((p.products_quantity>=op.products_quantity),1,0)") . ") as in_stock " : '') . " from " . TABLE_ORDERS_STATUS . " s, " . TABLE_ORDERS_STATUS_GROUPS  . " sg, " . $this->table_prefix . TABLE_ORDERS . " o left join " . $this->table_prefix . TABLE_ORDERS_TOTAL . " ot on (o.orders_id = ot.orders_id and ot.class = 'ot_total') left join " . $this->table_prefix . TABLE_ORDERS_PRODUCTS . " op on (op.orders_id = o.orders_id) " . ((tep_not_null($_GET['in_stock']) && $_GET['in_stock']!='') ? "left join " . TABLE_PRODUCTS . " p on (p.products_id = op.products_id) " . (PRODUCTS_INVENTORY == 'True' ? " left join " . TABLE_INVENTORY . " i on (i.prid = op.products_id and i.products_id = op.uprid) " : '') : '') . " where o.orders_status = s.orders_status_id " .  $search_condition . " and s.language_id = '" . (int)$languages_id . "' and s.orders_status_groups_id = sg.orders_status_groups_id and sg.language_id = '" . (int)$languages_id . "' " . $filter . " group by o.orders_id " . ((tep_not_null($_GET['in_stock']) && $_GET['in_stock']!='') ? " having in_stock " . ($_GET['in_stock']>0 ? " > 0" : " < 1") : '') . " order by " . $orderBy;

        $current_page_number = ($start / $length) + 1;
        $orders_split = new \splitPageResults($current_page_number, $length, $orders_query_raw, $orders_query_numrows, 'o.orders_id');
        $orders_query = tep_db_query($orders_query_raw);
        $responseList = array();
		$stack = [];
        while ($orders = tep_db_fetch_array($orders_query)) {
            $products_query = tep_db_query("select * from " . $this->table_prefix . TABLE_ORDERS_PRODUCTS . " where orders_id = '" . (int)$orders['orders_id'] . "'");
            $p_list = '';
            while ($products = tep_db_fetch_array($products_query)) {
              $p_list_tmp = '<div class="ord-desc-row"><div>' . $products['products_quantity'] . ' x ' . (strlen($products['products_name']) > 48 ? substr($products['products_name'], 0, 48) . '...' : $products['products_name']) . '</div><div class="order_pr_model">' . 'SKU: ' . (strlen($products['products_model']) > 8 ? substr($products['products_model'], 0, 8) . '...' : $products['products_model']) . ($products['products_model'] ? '<span>' . $products['products_model'] . '</span>' : '') . '</div></div>';
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

            $orderTotals = '';
            $totals_query = tep_db_query("select * from " . $this->table_prefix . TABLE_ORDERS_TOTAL . " where orders_id = '" . (int) $orders['orders_id'] . "' order by sort_order");
            while ($totals = tep_db_fetch_array($totals_query)) {
                $orderTotals .= '<div><span>' . $totals['title'] . '</span><span>' . $totals['text'] . '</span></div>';
            }
            
            $statusRow = '<span><i style="background: '.$orders['orders_status_groups_color'].';"></i>'.$orders['orders_status_groups_name'].'</span><div>'.$orders['orders_status_name'].'</div>';
            if ($ext = \common\helpers\Acl::checkExtension('PurchaseOrders', 'getCronActiveOrderId')) {
                $activeOrderId = $ext::getCronActiveOrderId($orders['suppliers_id']);
                if ($activeOrderId == $orders['orders_id']) {
                    $statusRow = '<span>'.TEXT_CURRENT_AUTO_REORDER.'</span>';
                }
            }

            $_ddate = \common\helpers\Date::date_short($orders['delivery_date']);
            if ($orders['delivery_date'] < '1980-01-01') {
              $_ddate = '';
            }
            $responseList[] = array(
                '<input type="checkbox" class="uniform">' . '<input class="cell_identify" type="hidden" value="' . $orders['orders_id'] . '">',
                '<div class="ord-desc-tab click_double" data-click-double="' . \Yii::$app->urlManager->createUrl(['purchase-orders/process-purchase-orders', 'orders_id' => $orders['orders_id']]) . '"><a href="' . \Yii::$app->urlManager->createUrl(['purchase-orders/process-purchase-orders', 'orders_id' => $orders['orders_id']]) . '"><span class="ord-id">' . TEXT_ORDER_NUM . (!empty($orders['orders_number'])?$orders['orders_number']:$orders['orders_id']) . ($orders['admin_id'] > 0 ? '&nbsp;by admin' : ($orders['suppliers_id'] > 0 ? '&nbsp;' . TEXT_FROM . ' ' . \common\helpers\Suppliers::getSupplierName($orders['suppliers_id']) : '')) . ($orders['warehouse_id'] > 0 ? ' ' . TEXT_TO . ' ' . strip_tags($orders['delivery_name']) : '') . '</span></a>' . $p_list . '</div>',
                '<div class="ord-date-purch click_double" data-click-double="' . \Yii::$app->urlManager->createUrl(['purchase-orders/process-purchase-orders', 'orders_id' => $orders['orders_id']]) . '">'.\common\helpers\Date::datetime_short($orders['date_purchased']) . '</div>',
                '<div class="ord-total click_double" data-click-double="' . \Yii::$app->urlManager->createUrl(['orders/process-order', 'orders_id' => $orders['orders_id']]) . '">' . $orders['order_total'] . '<div class="ord-total-info"><div class="ord-box-img"></div>' . $orderTotals . '</div></div>',
                '<div class="ord-date-purch click_double" data-click-double="' . \Yii::$app->urlManager->createUrl(['purchase-orders/process-purchase-orders', 'orders_id' => $orders['orders_id']]) . '">'.$_ddate . '</div>',
                '<div class="ord-status click_double" data-click-double="' . \Yii::$app->urlManager->createUrl(['purchase-orders/process-purchase-orders', 'orders_id' => $orders['orders_id']]) . '">' . $statusRow . '</div>'
            );
        }

        $response = array(
            'draw' => $draw,
            'recordsTotal' => $orders_query_numrows,
            'recordsFiltered' => $orders_query_numrows,
            'data' => $responseList
        );
        echo json_encode($response);
        //die();
    }

    public function actionPurchaseOrdersActions() {

        \common\helpers\Translation::init('admin/purchase-orders');
        \common\helpers\Translation::init('admin/orders');

        $this->layout = false;
        $orders_id = Yii::$app->request->post('orders_id');

        $orders_query = tep_db_query("select o.*, s.orders_status_name from " . TABLE_ORDERS_STATUS . " s, " . $this->table_prefix . TABLE_ORDERS . " o where o.orders_id = '" . (int) $orders_id . "'");
        $orders = tep_db_fetch_array($orders_query);

        if (!is_array($orders)) {
            die("Please select purchase orders.");
        }

        $orders_status = 0;
        $osRecord = \common\models\OrdersStatus::find()
            ->where(['order_evaluation_state_id' => \common\helpers\PurchaseOrder::POES_PENDING])
            ->one();
        if ($osRecord instanceof \common\models\OrdersStatus) {
            $orders_status = $osRecord->orders_status_id;
        }
        unset($osRecord);

            
        $oInfo = new \objectInfo($orders);

        echo '<div class="or_box_head">'.TEXT_ORDER_NUM . $oInfo->orders_id . '</div>';
        echo '<div class="row_or"><div>' . TEXT_DATE_ORDER_CREATED . '</div><div>' . \common\helpers\Date::datetime_short($oInfo->date_purchased).'</div></div>';
        if (tep_not_null($oInfo->last_modified)) echo '<div class="row_or"><div>'. TEXT_DATE_ORDER_LAST_MODIFIED . '</div><div>' . \common\helpers\Date::date_short($oInfo->last_modified) . '</div></div>';
        echo '<div class="btn-toolbar btn-toolbar-order">';

        echo '<span class="disable_wr"><span class="dis_popup"><span class="dis_popup_img"></span><span class="dis_popup_content">' . TEXT_COMPLITED . '</span></span></span>';
        echo '<button class="btn btn-delete btn-process-order" onclick="confirmDeleteOrder(' . $oInfo->orders_id . ')">' . IMAGE_DELETE . '</button>';
        
        echo '<a class="btn btn-primary btn-process-order" href="' . \Yii::$app->urlManager->createUrl(['purchase-orders/process-purchase-orders', 'orders_id' => $oInfo->orders_id]) . '">' . TEXT_PROCESS_PURCHASE_ORDERS_BUTTON . '</a>';
        if ($oInfo->orders_status == $orders_status) {
            echo '<a class="btn btn-no-margin btn-edit" href="' . \Yii::$app->urlManager->createUrl(['purchase-orders/edit', 'orders_id' => $oInfo->orders_id]) . '">' . IMAGE_EDIT . '</a>';
        }
        echo '<a class="btn" href="' . \Yii::$app->urlManager->createUrl(['purchase-orders/print-order', 'orders_id' => $oInfo->orders_id]) . '" target="_blank">' . TEXT_PRINT_ORDER . '</a>';
        echo '</div>';
    }

    public function actionPrintOrder()
    {


        $languages_id = \Yii::$app->settings->get('languages_id');

        \common\helpers\Translation::init('admin/purchase-orders');
        \common\helpers\Translation::init('admin/orders');

        $this->selectedMenu = array('customers', 'purchase-orders');

        if (Yii::$app->request->isPost) {
            $oID = (int)Yii::$app->request->post('orders_id');
        } else {
            $oID = (int)Yii::$app->request->get('orders_id');
        }

        $currencies = Yii::$container->get('currencies');

        $order = new \common\extensions\PurchaseOrders\classes\PurchaseOrder($oID);

        $platform_id = $order->info['platform_id'] ?? \common\classes\platform::defaultId();
        $__platform = Yii::$app->get('platform');
        $platform_config = $__platform->config($platform_id);
        if ($platform_config->isVirtual()) {
            $detected = false;
            if ($ext = \common\helpers\Acl::checkExtension('AdditionalPlatforms', 'allowed')) {
                $_plid = $ext::getVirtualSattelitId($platform_id);
                if($_plid){
                    $platform_id = $_plid;
                    $detected = true;
                }
            }
            if (!$detected){
                $platform_id = \common\classes\platform::defaultId();
            }
        }

        $theme_id = \common\models\PlatformsToThemes::findOne($platform_id)->theme_id;
        $theme_name = \common\models\Themes::findOne($theme_id)->theme_name;

        $pages = [['name' => 'purchase', 'params' => [
            'orders_id' => $oID,
            'platform_id' => $platform_id,
            'language_id' => $languages_id,
            'order' => $order,
            'currencies' => $currencies,
            'oID' => $oID,
        ]]];

        return  \backend\design\PDFBlock::widget([
            'pages' => $pages,
            'params' => [
                'theme_name' => $theme_name,
                'document_name' => str_replace(' ', '_', TEXT_CREDITNOTE) . '.pdf',
            ]
        ]);
    }

    public function actionSubmitPurchaseOrders() {
        global $login_id;
        $languages_id = \Yii::$app->settings->get('languages_id');
        $this->layout = false;
        \common\helpers\Translation::init('admin/purchase-orders');

        $oRecord = (int)Yii::$app->request->post('orders_id');
        $oRecord = \common\helpers\PurchaseOrder::getRecord($oRecord);
        $messageType = 'warning';
        $message = WARNING_ORDER_NOT_UPDATED;
        if ($oRecord instanceof \common\models\PurchaseOrders) {
            $status = (int)Yii::$app->request->post('status');
            $comments = trim(Yii::$app->request->post('comments'));
            $osRecord = \common\models\OrdersStatus::findOne(['orders_status_id' => $status]);
            if ($osRecord instanceof \common\models\OrdersStatus
                AND $osRecord->order_evaluation_state_id == \common\helpers\PurchaseOrder::POES_CANCELLED
            ) {
                foreach (\common\models\PurchaseOrdersProducts::find()->where(['orders_id' => $oRecord->orders_id])->asArray(false)->all() as $opRecrod) {
                    $opRecrod->qty_cnld = ((int)$opRecrod->products_quantity - (int)$opRecrod->qty_rcvd);
                    \common\helpers\PurchaseOrder::evaluateProduct($opRecrod);
                }
                unset($opRecrod);
            }
            unset($osRecord);
            $status = $oRecord->orders_status;
            \common\helpers\PurchaseOrder::evaluate($oRecord);
            if ($status != $oRecord->orders_status OR $comments != '') {
                $messageType = 'success';
                $message = SUCCESS_ORDER_UPDATED;

                $poshRecord = new \common\models\PurchaseOrdersStatusHistory();
                $poshRecord->orders_id = $oRecord->orders_id;
                $poshRecord->orders_status_id = $oRecord->orders_status;
                $poshRecord->customer_notified = (int)Yii::$app->request->post('notify');
                $poshRecord->comments = $comments;
                $poshRecord->admin_id = (int)$login_id;
                $poshRecord->date_added = date('Y-m-d H:i:s');
                try {
                    $poshRecord->save();
                } catch (\Exception $exc) {
                  \Yii::error($exc->getMessage() . ' ' . $exc->getTraceAsString());
                }
            }
        } else {
            return $this->redirect(\Yii::$app->urlManager->createUrl(['purchase-orders/']));
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
        return $this->actionProcessPurchaseOrders();
    }

    public function actionProcessPurchaseOrders() {

        $languages_id = \Yii::$app->settings->get('languages_id');

        \common\helpers\Translation::init('admin/purchase-orders');
        \common\helpers\Translation::init('admin/orders');

        $this->selectedMenu = array('customers', 'purchase-orders');

        if (Yii::$app->request->isPost) {
            $oID = (int)Yii::$app->request->post('orders_id');
        } else {
            $oID = (int)Yii::$app->request->get('orders_id');
        }

        $orders_query = tep_db_query("select orders_id, orders_id from " . $this->table_prefix . TABLE_ORDERS . " where orders_id = '" . (int)$oID . "'");
        if (!tep_db_num_rows($orders_query)) {
            return $this->redirect(\Yii::$app->urlManager->createUrl(['purchase-orders/', 'by' => 'oID', 'search' => (int)$oID]));
        }


        $orders_statuses = array();
        $orders_status_array = array();
        $orders_status_group_array = array();
        $orders_status_query = tep_db_query("select os.orders_status_id, os.orders_status_name, osg.orders_status_groups_name, osg.orders_status_groups_color, os.automated from " . TABLE_ORDERS_STATUS . " as os left join " . TABLE_ORDERS_STATUS_GROUPS . " as osg ON os.orders_status_groups_id = osg.orders_status_groups_id where os.language_id = '" . (int)$languages_id . "' and osg.language_id = '" . (int)$languages_id . "' and osg.orders_status_type_id = '".\common\helpers\PurchaseOrder::getStatusTypeId()."'");
        while ($orders_status = tep_db_fetch_array($orders_status_query)){
          if ($orders_status['automated'] == 0) {
            $orders_statuses[] = array('id' => $orders_status['orders_status_id'],
                                     'text' => $orders_status['orders_status_name']);
          }
          $orders_status_array[$orders_status['orders_status_id']] = $orders_status['orders_status_name'];
          $orders_status_group_array[$orders_status['orders_status_id']] = '<i style="background: ' . $orders_status['orders_status_groups_color'] . ';"></i>' . $orders_status['orders_status_groups_name'];
        }

        
        $currencies = Yii::$container->get('currencies');

        $order = new \common\extensions\PurchaseOrders\classes\PurchaseOrder($oID);
        
        /*echo "<pre>";
        print_r($order);
        echo "</pre>";
        die();*/
        
        $opsArray = array();
        foreach (\common\models\OrdersProductsStatus::findAll(['language_id' => (int)$languages_id]) as $opsRecord) {
            $opsArray[$opsRecord->orders_products_status_id] = $opsRecord;
        }
        unset($opsRecord);

        $products = [];
        foreach ($order->products as $key => $value) {
            $image = \common\classes\Images::getImage($value['products_id']);
            $attributes = '';
            if (!empty($value['products_attribute'])) {
                $attributes = '<br><nobr><small>&nbsp;&nbsp;<i> - ' . $value['products_attribute'] . '</i></small></nobr>';
            }
            $product = [];
            $product[] = '<td class="dataTableContent" valign="top" align="right">' . $value['products_quantity'] . '&nbsp;x</td>';
            $product[] = '<td class="dataTableContent" valign="top" align="center" width="100px"><div class="table-image-cell">' . $image . '</div></td>';
            $product[] = '<td class="dataTableContent" valign="top"><span style="cursor: pointer" onclick="window.open(\'' . tep_href_link(FILENAME_CATEGORIES . '/productedit', 'pID=' . $value['products_id']) . '\')">' . $value['products_name'] . '</span>' . $attributes . '</td>';
            $product[] = '<td class="dataTableContent" valign="top">' . $value['products_model'] . '</td>';
            $product[] = '<td class="dataTableContent" valign="top"><a href="' . Yii::$app->urlManager->createUrl(['purchase-orders/products-status-history', 'opID' => $value['orders_products_id']]) . '" class="right-link"><i class="icon-pencil"></i></a> <span id="products-status-' . $value['orders_products_id'] . '" style="color:' . (isset($opsArray[$value['orders_products_status']]) ? $opsArray[$value['orders_products_status']]->getColour() : '#000000') . '">' . (isset($opsArray[$value['orders_products_status']]) ? $opsArray[$value['orders_products_status']]->orders_products_status_name : '') . '</span></td>';
            $product[] = '<td class="dataTableContent" valign="top"><span id="products-qty-cnld-' . $value['orders_products_id'] . '">' . $value['qty_cnld'] . '</span></td>';
            $product[] = '<td class="dataTableContent" valign="top"><span id="products-qty-rcvd-' . $value['orders_products_id'] . '">' . $value['qty_rcvd'] . '</span></td>';
            $product[] = '<td class="dataTableContent" align="right" valign="top">' . \common\helpers\Tax::display_tax_value($value['products_tax']) . '%</td>' . "\n";
            $product[] = '<td class="dataTableContent" align="right" valign="top"><b>' . $currencies->format($currencies->calculate_price_in_order($order->info, $value['products_price']), (USE_MARKET_PRICES == 'True' ? false : true), $order->info['currency'], $order->info['currency_value']) . '</b></td>' . "\n";
            $product[] = '<td class="dataTableContent" align="right" valign="top"><b>' . $currencies->format($currencies->calculate_price_in_order($order->info, $value['products_price'], $value['products_tax']), (USE_MARKET_PRICES == 'True' ? false : true), $order->info['currency'], $order->info['currency_value']) . '</b></td>' . "\n";
            $product[] = '<td class="dataTableContent" align="right" valign="top"><b>' . $currencies->format($currencies->calculate_price_in_order($order->info, $value['products_price'], 0, ($value['products_quantity'] - $value['qty_cnld'])), (USE_MARKET_PRICES == 'True' ? false : true), $order->info['currency'], $order->info['currency_value'], true) . '</b></td>' . "\n";
            $product[] = '<td class="dataTableContent" align="right" valign="top"><b>' . $currencies->format($currencies->calculate_price_in_order($order->info, $value['products_price'], $value['products_tax'], ($value['products_quantity'] - $value['qty_cnld'])), (USE_MARKET_PRICES == 'True' ? false : true), $order->info['currency'], $order->info['currency_value']) . '</b></td>' . "\n";
            $products[] = $product;
        }
       
        $histories = [];
        $orders_history_query = tep_db_query("select * from purchase_orders_status_history where orders_id = '" . (int)$oID . "' order by date_added");
        if (tep_db_num_rows($orders_history_query)) {
            while ($orders_history = tep_db_fetch_array($orders_history_query)) {
                $history = [];
                $history[] = '<td>' . \common\helpers\Date::datetime_short($orders_history['date_added']) . '</td>';
                if ($orders_history['customer_notified'] == '1') {
                    $history[] = '<td><span class="st-true"></span></td>';
                } else {
                    $history[] = '<td><span class="st-false"></span></td>';
                }
                $history[] = '<td><span class="or-st-color">'.$orders_status_group_array[$orders_history['orders_status_id']].'/&nbsp;</span>' . $orders_status_array[$orders_history['orders_status_id']] . '</td>';
                $history[] = '<td>' . nl2br(tep_db_output($orders_history['comments'])) . '&nbsp;</td>';
                if ($orders_history['admin_id'] > 0) {
                    $check_admin_query = tep_db_query("select * from " . TABLE_ADMIN . " where admin_id = '" . (int) $orders_history['admin_id'] . "'");
                    $check_admin = tep_db_fetch_array($check_admin_query);
                    if (is_array($check_admin)) {
                        $history[] = '<td>' . $check_admin['admin_firstname'] . ' ' . $check_admin['admin_lastname'] . '</td>';
                    } else {
                        $history[] = '<td></td>';
                    }
                } else {
                    $history[] = '<td></td>';
                }
                $histories[] = $history;
            }
        } else {
            $histories[] = [
                '<td colspan="5">' . TEXT_NO_ORDER_HISTORY . '</td>'
            ];
        }
        
        $delivery_address = \common\helpers\Address::address_format($order->info['delivery_address_format_id'], [
            'street_address' => $order->info['delivery_street_address'],
            'suburb' => $order->info['delivery_suburb'],
            'city' => $order->info['delivery_city'],
            'state' => $order->info['delivery_state'],
            'country' => $order->info['delivery_country'],
            'postcode' => $order->info['delivery_postcode'],
        ], 1, '', '<br>');

        $_ddate = \common\helpers\Date::date_short($order->info['delivery_date']);
        if ($order->info['delivery_date'] < '1980-01-01') {
          $_ddate = TEXT_NOT_SET;
        }
        return $this->render('update', [
            'order' => $order,
          'delivery_date' => $_ddate,
            'orders_id' => $oID,
            'products' => $products,
            'histories' => $histories,
            'delivery_address' => $delivery_address,
        ]);
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
            $filename = 'purchase-orders' . strftime('%Y%b%d_%H%M') . '.csv';

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

            $csv_str = '"PurchaseOrders ID"' . $separator . '"Ship Method"' . $separator . '"Shipping Company"' . $separator . '"Shipping Street 1"' . $separator . '"Shipping Street 2"' . $separator . '"Shipping Suburb"' . $separator . '"Shipping State"' . $separator . '"Shipping Zip"' . $separator . '"Shipping Country"' . $separator . '"Shipping Name"' . "\r\n";

            $orders_query = tep_db_query("select orders_id from " . $this->table_prefix . TABLE_ORDERS . " where orders_id in ('" . implode("','", array_map('intval', explode(',', $_POST['orders']))) . "')");
            while ($orders = tep_db_fetch_array($orders_query)) {
                $order = new \common\extensions\PurchaseOrders\classes\PurchaseOrder($orders['orders_id']);
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
            tep_db_query("update " . TABLE_WAREHOUSES_ORDERS_PRODUCTS . " set purchase_orders_id = '0', purchase_orders_quantity = '0' where purchase_orders_id = '" . (int) $order_id . "'");
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

        $orders_query = tep_db_query("select o.*, s.orders_status_name, ot.text as order_total from " . TABLE_ORDERS_STATUS . " s, " . $this->table_prefix . TABLE_ORDERS . " o left join " . $this->table_prefix . TABLE_ORDERS_TOTAL . " ot on (o.orders_id = ot.orders_id) where o.orders_id = '" . (int) $orders_id . "'");
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

        tep_db_query("update " . TABLE_WAREHOUSES_ORDERS_PRODUCTS . " set purchase_orders_id = '0', purchase_orders_quantity = '0' where purchase_orders_id = '" . (int) $order_id . "'");

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
        $manager = \common\services\OrderManager::loadManager();
        Yii::$app->get('platform')->config(1)->constant_up();
        if ($set == 'payment'){
            $collection = $manager->getPaymentCollection();
        } else if ($set == 'shipping'){
            $collection = $manager->getShippingCollection();
        } else if ($set == 'order_total'){
            $collection = $manager->getTotalCollection();
        }
        $_module = $collection->getModule($module);
        if ($_module){
            if (method_exists($_module, 'download_invoice')){
                $_module->download_invoice($id);
            }
        }

        exit();
    }

    public function actionCreate() {
      /** @var \common\extensions\PurchaseOrders\PurchaseOrders $ext */
        if ($ext = \common\helpers\Acl::checkExtension('PurchaseOrders', 'adminActionCreate')) {
            $this->selectedMenu = array('customers', 'orders/createorder');
            $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('orders/index'), 'title' => HEADING_TITLE);
            $this->view->headingTitle = HEADING_TITLE;

            return $ext::adminActionCreate();
        } else {
            $this->redirect(\Yii::$app->urlManager->createUrl(['purchase-orders']));
        }
    }

    public function actionEdit() {
      /** @var \common\extensions\PurchaseOrders\PurchaseOrders $ext */
        if ($ext = \common\helpers\Acl::checkExtension('PurchaseOrders', 'adminActionEdit')) {
            $this->selectedMenu = array('customers', 'purchase-orders');
            $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('purchase-orders/index'), 'title' => HEADING_TITLE);
            $this->view->headingTitle = HEADING_TITLE;
            return $ext::adminActionEdit();
        } else {
            $this->redirect(\Yii::$app->urlManager->createUrl(['purchase-orders']));
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

    public function actionProductsStatusHistory()
    {
        \common\helpers\Translation::init('admin/orders');
        $opID = Yii::$app->request->get('opID');

        $orders_products_statuses = [
            ['id' => 0, 'text' => ''],
            ['id' => \common\helpers\OrderProduct::OPS_RECEIVED, 'text' => TEXT_STATUS_LONG_OPS_RECEIVED],
            ['id' => \common\helpers\OrderProduct::OPS_CANCELLED, 'text' => TEXT_STATUS_LONG_OPS_CANCELLED]
        ];

        $orderProductArray = [];
        $orderProductRecord = \common\helpers\PurchaseOrder::getRecordProduct($opID);
        if ($orderProductRecord instanceof \common\models\PurchaseOrdersProducts) {
            $orderRecord = \common\helpers\PurchaseOrder::getRecord($orderProductRecord->orders_id);
            if ($orderRecord instanceof \common\models\PurchaseOrders) {
                $supplierId = (int)$orderRecord->suppliers_id;
                $supplierName = 'N/A';
                foreach (\common\models\Suppliers::find()->asArray(true)->where(['suppliers_id' => $supplierId])->all() as $supplierRecord) {
                    $supplierName = $supplierRecord['suppliers_name'];
                }
                unset($supplierRecord);
                $warehouseId = (int)$orderRecord->warehouse_id;
                $warehouseName = 'N/A';
                foreach (\common\models\Warehouses::find()->asArray(true)->where(['warehouse_id' => $warehouseId])->all() as $warehouseRecord) {
                    $warehouseName = $warehouseRecord['warehouse_name'];
                }
                unset($warehouseRecord);
                $locationList = [
                    0 => 0
                ];
                foreach (\common\models\Locations::find()->asArray(true)->where(['warehouse_id' => $warehouseId])->all() as $locationRecord) {
                    $locationList[(int)$locationRecord['location_id']] = (int)$locationRecord['location_id'];
                }
                unset($locationRecord);

                $locationBlockList = [];
                foreach (\common\models\LocationBlocks::find()->asArray(true)->all() as $locationBlockRecord) {
                    $locationBlockList[$locationBlockRecord['block_id']] = $locationBlockRecord['block_name'];
                }
                unset($locationBlockRecord);

                $quantityAwaiting = ((int)$orderProductRecord->products_quantity - ((int)$orderProductRecord->qty_cnld + (int)$orderProductRecord->qty_rcvd));
                $receivedArray = [];
                if ($quantityAwaiting > 0) {
                    foreach ($locationList as $locationId) {
                        $locationName = trim(\common\helpers\Warehouses::getLocationPath($locationId, $warehouseId, $locationBlockList));
                        $locationName = (($locationName != '') ? $locationName : 'N/A');
                        $min = 0;
                        $max = $quantityAwaiting;
                        if ($min != $max) {
                            $receivedArray[$locationId] = [
                                'value' => 0,
                                'min' => $min,
                                'max' => $max,
                                'awaiting' => $max,
                                'locationName' => $locationName
                            ];
                        }
                        unset($locationName);
                        unset($max);
                        unset($min);
                    }
                    unset($locationList);
                    unset($locationId);
                }
                unset($locationBlockList);
                unset($quantityAwaiting);

                // CANCELLED
                $min = 0;
                $max = ((int)$orderProductRecord->products_quantity - (int)$orderProductRecord->qty_rcvd);
                if ($min != $max) {
                    $cancelledArray[0] = [
                        'value' => (int)$orderProductRecord->qty_cnld,
                        'min' => $min,
                        'max' => $max
                    ];
                }
                unset($max);
                unset($min);
                // EOF CANCELLED

                $orderProductArray = [
                    \common\helpers\OrderProduct::OPS_RECEIVED => $receivedArray,
                    \common\helpers\OrderProduct::OPS_CANCELLED => $cancelledArray
                ];
                unset($cancelledArray);
                unset($receivedArray);
            }
            unset($orderRecord);
        }
        unset($opID);

        return $this->renderAjax('products-status-history', [
            'supplierId' => $supplierId,
            'warehouseId' => $warehouseId,
            'supplierName' => $supplierName,
            'warehouseName' => $warehouseName,
            'product' => $orderProductRecord->toArray(),
            'statuses_array' => $orders_products_statuses,
            'orderProductArray' => $orderProductArray
        ]);
    }

    public function actionProductsStatusUpdate()
    {
        global $login_id;
        $languages_id = \Yii::$app->settings->get('languages_id');
        $opID = (int)Yii::$app->request->post('opID');
        $comments = trim(Yii::$app->request->post('comments'));
        $opStatus = (int)Yii::$app->request->post('status', 0);
        $popRecord = \common\helpers\PurchaseOrder::getRecordProduct($opID);
        if ($popRecord instanceof \common\models\PurchaseOrdersProducts) {
            $poRecord = \common\helpers\PurchaseOrder::getRecord($popRecord->orders_id);
            if ($poRecord instanceof \common\models\PurchaseOrders) {
                $oStatus = (int)$poRecord->orders_status;
                $uProductId = \common\helpers\Inventory::getInventoryId($popRecord->uprid);
                $supplierId = (int)$poRecord->suppliers_id;
                $warehouseId = (int)$poRecord->warehouse_id;
                $locationList = [
                    0 => 0
                ];
                foreach (\common\models\Locations::find()->asArray(true)->where(['warehouse_id' => $warehouseId])->all() as $locationRecord) {
                    $locationList[(int)$locationRecord['location_id']] = (int)$locationRecord['location_id'];
                }
                unset($locationRecord);
                $opUpdateArray = Yii::$app->request->post('update_order_product_' . $opStatus, false);
                if ($opStatus == \common\helpers\OrderProduct::OPS_RECEIVED) {
                    if (is_array($opUpdateArray) AND isset($opUpdateArray[$warehouseId][$supplierId])) {
                        $isCache = false;
                        $quantityReal = ((int)$popRecord->products_quantity - (int)$popRecord->qty_cnld);
                        foreach ($opUpdateArray[$warehouseId][$supplierId] as $locationId => $quantityUpdate) {
                            $quantityUpdate = (int)$quantityUpdate;
                            if ($quantityUpdate > ($quantityReal - (int)$popRecord->qty_rcvd)) {
                                $quantityUpdate = ($quantityReal - (int)$popRecord->qty_rcvd);
                            }
                            if ($quantityUpdate > 0 AND isset($locationList[$locationId])) {
                                $quantityWarehouse = \common\helpers\Warehouses::update_products_quantity($uProductId, 0, 0, '+');
                                $quantityWarehouseNew = \common\helpers\Warehouses::update_products_quantity($uProductId, $warehouseId, $quantityUpdate, '+', $supplierId, $locationId, [
                                    'admin_id' => $login_id,
                                    'comments' => (TEXT_PURCHASE_ORDER_RECEIVED . ' #' . $poRecord->orders_id)
                                ]);
                                if ($quantityWarehouse == $quantityWarehouseNew) {
                                    $quantityUpdate = 0;
                                }
                                unset($quantityWarehouseNew);
                                unset($quantityWarehouse);
                                if ($quantityUpdate > 0) {
                                    $isCache = true;
                                    $popRecord->qty_rcvd += $quantityUpdate;
                                }
                            }
                        }
                        if ($isCache == true) {
                            try {
                                $popRecord->save();
                            } catch (\Exception $exc) {
                              \Yii::error($exc->getMessage() . ' ' . $exc->getTraceAsString());
                            }
                            $orderIdArray = [];
                            foreach (\common\models\OrdersProducts::find()
                                ->where(['orders_products_status' => \common\helpers\OrderProduct::OPS_STOCK_ORDERED])
                                ->andWhere(['OR', ['uprid' => $popRecord->uprid], ['uprid' => $uProductId]])
                                ->asArray(false)->all() as $opRecord
                            ) {
                                $orderIdArray[$opRecord->orders_id] = $opRecord->orders_id;
                                \common\helpers\OrderProduct::doAllocateAutomatic($opRecord, false);
                            }
                            unset($opRecord);
                            foreach ($orderIdArray as $orderId) {
                                \common\helpers\Order::evaluate($orderId);
                            }
                            unset($orderIdArray);
                            unset($orderId);
                            \common\helpers\Product::doCache($uProductId);
                        }
                    }
                } elseif ($opStatus == \common\helpers\OrderProduct::OPS_CANCELLED) {
                    if (is_array($opUpdateArray) AND isset($opUpdateArray[$warehouseId][$supplierId])) {
                        foreach ($opUpdateArray[$warehouseId][$supplierId] as $locationId => $quantityUpdate) {
                            $quantityReceived = $popRecord->qty_rcvd;
                            if ($quantityUpdate < 0) {
                                $quantityUpdate = 0;
                            }
                            if ($quantityUpdate > ($popRecord->products_quantity - $quantityReceived)) {
                                $quantityUpdate = ($popRecord->products_quantity - $quantityReceived);
                            }
                            $popRecord->qty_cnld = $quantityUpdate;
                            try {
                                $popRecord->save();
                            } catch (\Exception $exc) {
                              \Yii::error($exc->getMessage() . ' ' . $exc->getTraceAsString());
                            }
                        }
                    }
                }
                \common\helpers\PurchaseOrder::evaluateProduct($popRecord);
                \common\helpers\PurchaseOrder::evaluate($poRecord);
                if (($comments != '') OR ($oStatus != (int)$poRecord->orders_status)) {
                    $poshRecord = new \common\models\PurchaseOrdersStatusHistory();
                    $poshRecord->orders_id = (int)$poRecord->orders_id;
                    $poshRecord->orders_status_id = (int)$poRecord->orders_status;
                    $poshRecord->date_added = date('Y-m-d H:i:s');
                    $poshRecord->comments = $comments;
                    $poshRecord->admin_id = (int)$login_id;
                    try {
                        $poshRecord->save();
                    } catch (\Exception $exc) {
                      \Yii::error($exc->getMessage() . ' ' . $exc->getTraceAsString());
                    }
                    unset($poshRecord);
                }
                if (Yii::$app->request->isAjax) {
                    $opsStatus = '';
                    $opsColour = '#000000';
                    $qty_cnld = $popRecord->qty_cnld;
                    $qty_rcvd = $popRecord->qty_rcvd;

                    $opsRecord = \common\models\OrdersProductsStatus::findOne([
                        'orders_products_status_id' => $popRecord->orders_products_status,
                        'language_id' => (int)$languages_id
                    ]);
                    if ($opsRecord instanceof \common\models\OrdersProductsStatus) {
                        $opsStatus = $opsRecord->orders_products_status_name;
                        $opsColour = $opsRecord->getColour();
                    }
                    unset($opsRecord);

                    echo json_encode([
                        'status' => 'ok',
                        'op' => [
                            'qty_cnld' => $qty_cnld,
                            'qty_rcvd' => $qty_rcvd
                        ],
                        'os' => [
                            'status' => (int)$poRecord->orders_status
                        ],
                        'ops' => [
                            'status' => $opsStatus,
                            'colour' => $opsColour
                        ]
                    ]);
                } else {
                    $url = Url::to(['purchase-orders/process-purchase-orders', 'orders_id' => $poRecord->orders_id]);
                    return $this->redirect($url);
                }
            }
        }
        return false;
    }

    public function actionProductAdd()
    {
        $languages_id = \Yii::$app->settings->get('languages_id');
        $this->layout = false;
        \common\helpers\Translation::init('admin/orders/order-edit');

        $languageId = (int)$languages_id;
        $supplierId = (int)Yii::$app->request->get('suppliers_id');
        $categoryId = trim(Yii::$app->request->post('category_id', Yii::$app->request->get('category_id', 0)));
        if (substr($categoryId, 0, 1) == 'c') {
            $categoryId = intval(substr($categoryId, 1));
        }
        if ((int)\common\classes\platform::defaultId() > 0) {
            $platformId = (int)\common\classes\platform::defaultId();
        } else {
            $platformId = (int)\common\classes\platform::currentId();
        }
        if (Yii::$app->request->isPost) {
            $response = [];
            $action = Yii::$app->request->post('action', '');
            switch ($action) {
                case 'read':
                    $productId = (int)Yii::$app->request->post('productId');
                    $productQuery = $this->getProductQuery($platformId, $supplierId, $languageId);
                    foreach ($productQuery
                        ->select(['IFNULL(i.products_id, p.products_id) AS uprid',
                            'IF(LENGTH(sp.suppliers_model) > 0, sp.suppliers_model, IF(LENGTH(i.products_model) > 0, i.products_model, p.products_model)) AS model',
                            'IF(LENGTH(sp.suppliers_product_name) > 0, sp.suppliers_product_name, IF(LENGTH(pdr.products_name) > 0, pdr.products_name, pdl.products_name)) AS name',
                            'sp.suppliers_price AS price', 'p.stock_reorder_quantity AS qty'
                        ])
                        ->andWhere(['p.products_id' => $productId])
                        ->orderBy(['name' => SORT_ASC, 'uprid' => SORT_ASC])
                        ->all() as $productData
                    ) {
                        $productData['qty'] = (int)((int)$productData['qty'] < 0 ? STOCK_REORDER_QUANTITY : $productData['qty']);
                        $productData['attribute'] = '';
                        foreach (\common\helpers\Inventory::getInventoryAttributeNameList($productData['uprid']) as $attributeName) {
                            $productData['attribute'] .= ($attributeName . ', ');
                        }
                        $productData['attribute'] = trim($productData['attribute'], ', ');
                        $productData['image'] = \common\classes\Images::getImage($productData['uprid']);
                        $response[] = $productData;
                    }
                break;
                case 'search':
                    $search = trim(Yii::$app->request->post('search'));
                    if (strlen($search) > 1) {
                        $productArray = [];
                        $categoryArray = [];
                        $productQuery = $this->getProductQuery($platformId, $supplierId, $languageId);
                        foreach ($productQuery
                            ->select(['p.products_id', 'p2c.categories_id',
                                'IF(LENGTH(pdr.products_name) > 0, pdr.products_name, pdl.products_name) AS title'
                            ])
                            ->leftJoin(\common\models\Products2Categories::tableName() . ' AS p2c', 'p.products_id = p2c.products_id')
                            ->andWhere('p2c.categories_id > 0')
                            ->having(['LIKE', 'title', $search])
                            ->orderBy(['p.sort_order' => SORT_ASC, 'title' => SORT_ASC])
                            ->groupBy('p.products_id')
                            ->all() as $product
                        ) {
                            $categoryArray[] = $product['categories_id'];
                            \common\helpers\Categories::get_parent_categories($categoryArray, $product['categories_id'], false);
                            $productArray[$product['products_id']] = (int)$product['products_id'];
                        }
                        $categoryArray = array_unique($categoryArray);
                        $response = $this->getCategoryTree(0, $platformId, $supplierId, $languageId, $categoryArray, $productArray);
                    }
                break;
                case 'category':
                    $response = $this->getCategoryTree($categoryId, $platformId, $supplierId, $languageId);
                break;
            }
            echo json_encode($response);
            die();
        }
        $params['category_tree_array'] = $this->getCategoryTree($categoryId, $platformId, $supplierId, $languageId);
        $params['queryParams'] = array_merge(['purchase-orders/product-add'], Yii::$app->request->getQueryParams());
        return $this->render('products-box', $params);
    }

    private function getCategoryTree($categoryId, $platformId, $supplierId, $languageId, $categoryArray = false, $productArray = false)
    {
        $return = [];
        $catgoryQuery = new \yii\db\Query();
        $catgoryQuery
            ->select(['c.categories_id as `key`', 'cd.categories_name AS `title`'])
            ->from(\common\models\Categories::tableName() . ' AS c')
            ->leftJoin(\common\models\CategoriesDescription::tableName() . ' AS cd', 'c.categories_id = cd.categories_id')
            ->where(['cd.language_id' => $languageId])
            ->andWhere(['c.parent_id' => $categoryId])
            ->orderBy(['c.sort_order' => SORT_ASC, 'cd.categories_name' => SORT_ASC]);
        if (is_array($categoryArray)) {
            $catgoryQuery->andWhere(['IN', 'c.categories_id', $categoryArray]);
        }
        foreach ($catgoryQuery->all() as $category) {
            $category['lazy'] = true;
            $category['folder'] = true;
            if (is_array($categoryArray)) {
                $childArray = $this->getCategoryTree((int)$category['key'], $platformId, $supplierId, $languageId, $categoryArray, $productArray);
                if (count($childArray) > 0) {
                    $category['lazy'] = false;
                    $category['expanded'] = true;
                    $category['children'] = $childArray;
                }
            }
            $category['key'] = ('c' . $category['key']);
            $return[] = $category;
        }
        $productQuery = $this->getProductQuery($platformId, $supplierId, $languageId);
        $productQuery
            ->select(["CONCAT('p', p.products_id, '_', p2c.categories_id) AS `key`",
                'IF(LENGTH(pdr.products_name) > 0, pdr.products_name, pdl.products_name) AS title'
            ])
            ->leftJoin(\common\models\Products2Categories::tableName() . ' AS p2c', 'p.products_id = p2c.products_id')
            ->andWhere(['p2c.categories_id' => $categoryId])
            ->andWhere('p2c.categories_id > 0')
            ->orderBy(['p.sort_order' => SORT_ASC, 'title' => SORT_ASC])
            ->groupBy('p.products_id');
        if (is_array($productArray)) {
            $productQuery->andWhere(['IN', 'p.products_id', $productArray]);
        }
        foreach ($productQuery->all() as $product) {
            $return[] = $product;
        }
        return $return;
    }

    private function getProductQuery($platformId, $supplierId, $languageId)
    {
        $return = new \yii\db\Query();
        $return
            ->from(\common\models\Products::tableName() . ' AS p')
            ->leftJoin(\common\models\ProductsDescription::tableName() . ' AS pdl', 'p.products_id = pdl.products_id')
            ->leftJoin(\common\models\ProductsDescription::tableName() . ' AS pdr', 'pdl.products_id = pdr.products_id'
                . " AND pdr.platform_id = '{$platformId}' AND pdr.language_id = '{$languageId}'"
            )
            ->leftJoin(\common\models\Inventory::tableName() . ' AS i', 'p.products_id = i.prid')
            ->leftJoin(\common\models\SuppliersProducts::tableName() . ' AS sp', 'p.products_id = sp.products_id'
                . ' AND IFNULL(i.products_id, p.products_id) = sp.uprid'
            )
            ->where(['p.is_bundle' => 0])
            ->andWhere(['sp.status' => 1])
            ->andWhere(['sp.suppliers_id' => $supplierId])
            ->andWhere(['pdl.language_id' => $languageId])
            ->andWhere(['pdl.platform_id' => $platformId]);
        return $return;
    }
}