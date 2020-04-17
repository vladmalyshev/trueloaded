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
use common\classes\payment;
use common\components\Customer;
use common\helpers\Status;
use Yii;

/**
 * default controller to handle user requests.
 */
class SubscriptionController extends Sceleton {

    public $acl = ['BOX_HEADING_CUSTOMERS', 'BOX_CUSTOMERS_SUBSCRIPTION'];
    
    /**
     * Index action is the default action in a controller.
     */
	public function __construct($id, $module=''){
		parent::__construct($id, $module);
	}
    public function actionIndex() {

        $this->selectedMenu = array('customers', 'subscription');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('subscription/index'), 'title' => HEADING_TITLE);
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

	    $status = Status::getStatusListByTypeName('Subscriptions', $_GET['status'] ?? '');

        $this->view->filters->status = $status;
        
        $payments = [];
        $payments[] = [
                'name' => TEXT_ANY,
                'value' => '',
                'selected' => '',
            ];
        $payment_method_query = tep_db_query("select payment_method from " . TABLE_SUBSCRIPTION. " where 1 group by payment_method order by payment_method");
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

        if ($ext = \common\helpers\Acl::checkExtension('Subscriptions', 'adminActionIndex')) {
            return $ext::adminActionIndex();
        }

        return $this->render('index');
    }

    public function actionSubscriptionsList() {
        $languages_id = \Yii::$app->settings->get('languages_id');
      
        \common\helpers\Translation::init('admin/subscription');
		
        $draw = Yii::$app->request->get('draw');
        $start = Yii::$app->request->get('start');
        $length = Yii::$app->request->get('length');
        
        if( $length == -1 ) $length = 10000;
		
        $_session = Yii::$app->session;

        $search = '';
        if (isset($_GET['search']['value']) && tep_not_null($_GET['search']['value'])) {
            $keywords = tep_db_input(tep_db_prepare_input($_GET['search']['value']));
            $search_condition = " and (o.customers_lastname like '%" . $keywords . "%' or o.customers_firstname like '%" . $keywords . "%' or o.customers_email_address like '%" . $keywords . "%' or o.subscription_id='" . $keywords . "' or op.products_model like '%" . tep_db_input($keywords) . "%' or op.products_name like '%" . tep_db_input($keywords) . "%') ";
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
                  $filter .= " and o.subscription_id = '" . (int)$search . "' ";
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
                    $filter .= " o.subscription_id = '" . tep_db_input($search) . "' ";
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
                    $orderBy = "o.subscription_id desc";
                    break;
            }
        } else {
            $orderBy = "o.subscription_id desc";
        }
		
		$_session->set('filter', $filter);

        $orders_query_raw = "select o.*, c.customers_gender, s.orders_status_name, sg.orders_status_groups_name, sg.orders_status_groups_color " . ((tep_not_null($_GET['in_stock']) && $_GET['in_stock']!='') ? ", BIT_AND(" . (PRODUCTS_INVENTORY == 'True' ? "if(i.products_quantity is not null,if((i.products_quantity>=op.products_quantity),1,0),if((p.products_quantity>=op.products_quantity),1,0))" :"if((p.products_quantity>=op.products_quantity),1,0)") . ") as in_stock " : '') . " from " . TABLE_ORDERS_STATUS . " s, " . TABLE_ORDERS_STATUS_GROUPS  . " sg, " . TABLE_SUBSCRIPTION . " o left join " . TABLE_SUBSCRIPTION_PRODUCTS . " op on (op.subscription_id = o.subscription_id) " . ((tep_not_null($_GET['in_stock']) && $_GET['in_stock']!='') ? "left join " . TABLE_PRODUCTS . " p on (p.products_id = op.products_id) " . (PRODUCTS_INVENTORY == 'True' ? " left join " . TABLE_INVENTORY . " i on (i.prid = op.products_id and i.products_id = op.uprid) " : '') : '') . " LEFT JOIN  ".TABLE_CUSTOMERS." c on (o.customers_id = c.customers_id) where o.subscription_status = s.orders_status_id " .  $search_condition . " and s.language_id = '" . (int)$languages_id . "' and s.orders_status_groups_id = sg.orders_status_groups_id and sg.language_id = '" . (int)$languages_id . "' " . $filter . " group by o.subscription_id " . ((tep_not_null($_GET['in_stock']) && $_GET['in_stock']!='') ? " having in_stock " . ($_GET['in_stock']>0 ? " > 0" : " < 1") : '') . " order by " . $orderBy;

        $current_page_number = ($start / $length) + 1;
        $orders_split = new \splitPageResults($current_page_number, $length, $orders_query_raw, $orders_query_numrows, 'o.subscription_id');
        $orders_query = tep_db_query($orders_query_raw);
        $responseList = array();
		$stack = [];
        while ($orders = tep_db_fetch_array($orders_query)) {
            $products_query = tep_db_query("select * from " . TABLE_SUBSCRIPTION_PRODUCTS . " where subscription_id = '" . (int)$orders['subscription_id'] . "'");
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

            $responseList[] = array(
                '<input type="checkbox" class="uniform">' . '<input class="cell_identify" type="hidden" value="' . $orders['subscription_id'] . '">',
                '<div class="ord-name ord-gender ord-gender-'.$orders['customers_gender'].' click_double" data-click-double="' . \Yii::$app->urlManager->createUrl(['subscription/process-subscription', 'subscription_id' => $orders['subscription_id']]) . '"><a href="' . \Yii::$app->urlManager->createUrl(['customers/customeredit', 'customers_id' => $orders['customers_id']]) . '">'.$orders['customers_name'] .'</a></div><a href="mailto:'.$orders['customers_email_address'] .'" class="ord-name-email">'  . $customers_email_address.'</a><div class="ord-location" style="margin-top: 5px;">'.$orders['customers_postcode'].'<div class="ord-total-info ord-location-info"><div class="ord-box-img"></div><b>'.$orders['customers_name'].'</b>'.$orders['customers_street_address'].'<br>'.$orders['customers_city'].', '.$orders['customers_state']. '&nbsp;' .$orders['customers_postcode'].'<br>'.$orders['customers_country'].'</div></div>',
                $orders['info'],
                '<div class="ord-desc-tab click_double" data-click-double="' . \Yii::$app->urlManager->createUrl(['subscription/process-subscription', 'subscription_id' => $orders['subscription_id']]) . '"><a href="' . \Yii::$app->urlManager->createUrl(['subscription/process-subscription', 'subscription_id' => $orders['subscription_id']]) . '"><span class="ord-id">' . TEXT_ORDER_NUM . $orders['subscription_id'] . ($orders['admin_id'] > 0 ? '&nbsp;by admin' : (\common\classes\platform::isMulti() >= 0 ? '&nbsp;' . TEXT_FROM . ' ' . \common\classes\platform::name($orders['platform_id']) : '')) . (tep_not_null($orders['payment_method']) ? ' ' . TEXT_VIA . ' ' . strip_tags($orders['payment_method']) : '') . '</span></a>' . $p_list . '</div>',
                '<div class="ord-date-purch click_double" data-click-double="' . \Yii::$app->urlManager->createUrl(['subscription/process-subscription', 'subscription_id' => $orders['subscription_id']]) . '">'.\common\helpers\Date::datetime_short($orders['date_purchased']),
                '<div class="ord-status click_double" data-click-double="' . \Yii::$app->urlManager->createUrl(['subscription/process-subscription', 'subscription_id' => $orders['subscription_id']]) . '"><span><i style="background: '.$orders['orders_status_groups_color'].';"></i>'.$orders['orders_status_groups_name'].'</span><div>'.$orders['orders_status_name'].'</div></div>'
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

    public function actionSubscriptionActions() {
        $this->layout = false;
        if ($ext = \common\helpers\Acl::checkExtension('Subscriptions', 'adminActionSubscriptionActions')) {
            return $ext::adminActionSubscriptionActions();
        }
    }
        
    public function actionSubmitSubscription() {
        global $login_id;

        $this->layout = false;

        \common\helpers\Translation::init('admin/subscription');

        $oID = (int) Yii::$app->request->post('subscription_id');
        $status = (int) Yii::$app->request->post('status');
        $comments = Yii::$app->request->post('comments');
        
        $order_updated = false;

        $check_status_query = tep_db_query("select * from " . TABLE_SUBSCRIPTION . " where subscription_id = '" . (int) $oID . "'");
        if (!tep_db_num_rows($check_status_query)) {
            die("Wrong subscription data.");
        }
        $check_status = tep_db_fetch_array($check_status_query);

        if (($check_status['subscription_status'] != $status) || $comments != '') {
            
            if ($check_status['subscription_status'] != $status) {
                
                \common\helpers\Translation::init('payment');
                Yii::$app->get('platform')->config($check_status['platform_id'])->constant_up();
                $manager = \common\services\OrderManager::loadManager();
                $collection = $manager->getPaymentCollection();
                $object = $collection->getModule($check_status['payment_class']);

                if ($object){
                
                    switch ($status) {
                        case \common\helpers\Subscription::getStatus('Canceled'):
                            if (is_object($object)) {
                                $object->cancel_subscription($check_status['transaction_id']);
                            }
                            break;
                        case \common\helpers\Subscription::getStatus('Active'):
                            if (is_object($object)) {
                                $object->reactivate_subscription($check_status['transaction_id']);
                            }
                            break;
                        case \common\helpers\Subscription::getStatus('Future'):
                            if (is_object($object)) {
                                $object->postpone_subscription($check_status['transaction_id'], date('Y-m-d'));
                            }
                            break;
                        case \common\helpers\Subscription::getStatus('Expired'):
                            if (is_object($object)) {
                                $object->terminate_subscription($check_status['transaction_id']);
                            }
                            break;
                        default:
                            break;
                    }
                }
            }
            
            tep_db_query("update " . TABLE_SUBSCRIPTION . " set subscription_status = '" . tep_db_input($status) . "', last_modified = now() where subscription_id = '" . (int) $oID . "'");

            $customer_notified = '0';
            if (isset($_POST['notify']) && ($_POST['notify'] == 'on')) {
/*
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
                    $get_template_r = tep_db_query("select * from " . TABLE_EMAIL_TEMPLATES . " where email_templates_key='" . tep_db_input($ostatus['orders_status_template']) . "'");
                    if (tep_db_num_rows($get_template_r) > 0) {
                        $emailTemplate = $ostatus['orders_status_template'];
                    }
                }
                if(!empty($emailTemplate))
                    list($email_subject, $email_text) = \common\helpers\Mail::get_parsed_email_template($emailTemplate, $email_params, -1, $check_status['platform_id']);
                    // }}

                    \common\helpers\Mail::send($check_status['customers_name'], $check_status['customers_email_address'], $email_subject, $email_text, $eMail_store_owner, $eMail_address);
                }
*/
                $customer_notified = '1';
            }
            
            tep_db_perform(TABLE_SUBSCRIPTION_STATUS_HISTORY, array(
                'subscription_id' => (int) $oID,
                'subscription_status_id' => (int) $status,
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
        return $this->actionProcessSubscription();        
    }

    public function actionProcessSubscription() {
        
        $languages_id = \Yii::$app->settings->get('languages_id');

        \common\helpers\Translation::init('admin/subscription');
        \common\helpers\Translation::init('admin/orders');

        $this->selectedMenu = array('customers', 'subscription');
        
        if (Yii::$app->request->isPost) {
            $oID = (int)Yii::$app->request->post('subscription_id');
        } else {
            $oID = (int)Yii::$app->request->get('subscription_id');
        }

        $orders_query = tep_db_query("select subscription_id, orders_id from " . TABLE_SUBSCRIPTION . " where subscription_id = '" . (int)$oID . "'");
        if (!tep_db_num_rows($orders_query)) {
            return $this->redirect(\Yii::$app->urlManager->createUrl(['subscription/', 'by' => 'oID', 'search' => (int)$oID]));
        }

        ob_start();

        $orders_statuses = array();
        $orders_status_array = array();
        $orders_status_group_array = array();
        $orders_status_query = tep_db_query("select os.orders_status_id, os.orders_status_name, osg.orders_status_groups_name, osg.orders_status_groups_color, os.automated from " . TABLE_ORDERS_STATUS . " as os left join " . TABLE_ORDERS_STATUS_GROUPS . " as osg ON os.orders_status_groups_id = osg.orders_status_groups_id where os.language_id = '" . (int)$languages_id . "' and osg.language_id = '" . (int)$languages_id . "' and osg.orders_status_groups_id = '".\common\helpers\Subscription::getStatusGroup()."'");
        while ($orders_status = tep_db_fetch_array($orders_status_query)){
          if ($orders_status['automated'] == 0) {
            $orders_statuses[] = array('id' => $orders_status['orders_status_id'],
                                     'text' => $orders_status['orders_status_name']);
          }
          $orders_status_array[$orders_status['orders_status_id']] = $orders_status['orders_status_name'];
          $orders_status_group_array[$orders_status['orders_status_id']] = '<i style="background: ' . $orders_status['orders_status_groups_color'] . ';"></i>' . $orders_status['orders_status_groups_name'];
        }

        $currencies = \Yii::$container->get('currencies');

        $order = new \common\extensions\Subscriptions\Subscription($oID);
        
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
                $zoom_d = max((int)$order->delivery['country']['zoom'], 8);
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
                            <input id="hid-add1" type="hidden" value="<?php echo /*$order->delivery['postcode'] . ' ' . */$order->delivery['street_address'] . ' ' . $order->delivery['city'] . ' ' . $order->delivery['country']['title']; ?>">
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
                $zoom = max((int)$order->delivery['country']['zoom'], 8);            
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
                            <input id="hid-add" type="hidden" value="<?php echo /*$order->delivery['postcode'] . ' ' .*/ $order->delivery['street_address'] . ' ' . $order->delivery['city'] . ' ' . $order->delivery['country']['title']; ?>">
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
$manager = \common\services\OrderManager::loadManager();
Yii::$app->get('platform')->config($order->info['platform_id'])->constant_up();
$collection = $manager->getPaymentCollection();
$object = $collection->getModule($order->info['payment_class']);

if (is_object($object)) {
    $fullInfo = $object->get_subscription_full_info($order->info['transaction_id']);
    echo $fullInfo;
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
          $orders_history_query = tep_db_query("select * from " . TABLE_SUBSCRIPTION_STATUS_HISTORY . " where subscription_id = '" . (int)$oID . "' order by date_added");
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
              echo '            <td><span class="or-st-color">'.$orders_status_group_array[$orders_history['subscription_status_id']].'/&nbsp;</span>' . $orders_status_array[$orders_history['subscription_status_id']] . '</td>' . "\n" .
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
                                <?php echo tep_draw_pull_down_menu('status', $orders_statuses, $order->info['orders_status_id'], 'class="form-control"'); ?>                        
                            </div>
                        </div>
                    <?php if ( class_exists('\common\helpers\CommentTemplate') ){ ?>
                        <?php echo \common\helpers\CommentTemplate::renderFor('subscription', $order); ?>
                    <?php } ?>
                        <div class="f_row">
                            <div class="f_td">
                                <label><?php echo  TABLE_HEADING_COMMENTS; ?>:</label>
                            </div>
                            <div class="f_td">
                                <?php echo tep_draw_textarea_field('comments', 'soft', '60', '5', '', 'class="form-control"'); ?>\
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
                                    echo tep_draw_hidden_field('subscription_id', $oID);
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
    $orders_not_processed = tep_db_fetch_array(tep_db_query("select subscription_id from " . TABLE_SUBSCRIPTION . " where subscription_id != '" . (int)$oID . "' and orders_status = '" . (int) DEFAULT_ORDERS_STATUS_ID . "' order by subscription_id DESC limit 1"));
    echo '<div class="btn-bar" style="padding: 0; text-align: center;">' . '<div class="btn-left"><a href="javascript:void(0)" onclick="return resetStatement();" class="btn btn-back">' . IMAGE_BACK . '</a></div><div class="btn-right">' . (isset($orders_not_processed['orders_id']) ? '<a href="' . \Yii::$app->urlManager->createUrl(['orders/process-order', 'orders_id' => $orders_not_processed['orders_id']]) . '" class="btn btn-next-unprocess">'.TEXT_BUTTON_NEXT_ORDER.'</a>' : '') . '</div></div>';
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

        $order_next = tep_db_fetch_array(tep_db_query("select o.subscription_id from " . TABLE_SUBSCRIPTION . " o " . (strlen($filter) > 0 ? "left join " . TABLE_SUBSCRIPTION_PRODUCTS . " op on o.subscription_id = op.subscription_id left join " . TABLE_ORDERS_STATUS. " s on o.orders_status=s.orders_status_id " : '') . " where o.orders_id > '" . (int)$oID . "' " . $search_condition . " " . $filter . " order by orders_id ASC limit 1"));
        $order_prev = tep_db_fetch_array(tep_db_query("select o.subscription_id from " . TABLE_SUBSCRIPTION . " o " . (strlen($filter) > 0 ? "left join " . TABLE_SUBSCRIPTION_PRODUCTS . " op on o.subscription_id = op.subscription_id left join " . TABLE_ORDERS_STATUS. " s on o.orders_status=s.orders_status_id " : '') . " where o.orders_id < '" . (int)$oID . "' " . $search_condition . " " . $filter . " order by orders_id DESC limit 1"));
        $this->view->order_next = ( isset($order_next['subscription_id']) ? $order_next['subscription_id'] : 0);
        $this->view->order_prev = ( isset($order_prev['subscription_id']) ? $order_prev['subscription_id'] : 0);

        $order_platform_id = $order->info['platform_id'];
        $order_language = \common\classes\language::get_code($order->info['language_id']);
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('subscription/process-subscription?subscription_id='. $oID), 'title' => TEXT_PROCESS_SUBSCRIPTION_BUTTON . ' #'. $oID . ' <span class="head-or-time">' . TEXT_DATE_AND_TIME . $order->info['date_purchased'].'</span>');
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
            $filename = 'subscription' . strftime('%Y%b%d_%H%M') . '.csv';

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

            $csv_str = '"Subscription ID"' . $separator . '"Ship Method"' . $separator . '"Shipping Company"' . $separator . '"Shipping Street 1"' . $separator . '"Shipping Street 2"' . $separator . '"Shipping Suburb"' . $separator . '"Shipping State"' . $separator . '"Shipping Zip"' . $separator . '"Shipping Country"' . $separator . '"Shipping Name"' . "\r\n";

            $orders_query = tep_db_query("select subscription_id from " . TABLE_SUBSCRIPTION . " where subscription_id in ('" . implode("','", array_map('intval', explode(',', $_POST['orders']))) . "')");
            while ($orders = tep_db_fetch_array($orders_query)) {
                $order = new \common\extensions\Subscriptions\Subscription($orders['subscription_id']);
                $csv_str .= '"' . $this->saveText($orders['subscription_id']) . '"' . $separator . '"' . $this->saveText($order->info['shipping_method']) . '"' . $separator . '"' . $this->saveText($order->delivery['company']) . '"' . $separator . '"' . $this->saveText($order->delivery['street_address']) . '"' . $separator . '"' . $this->saveText($order->delivery['suburb']) . '"' . $separator . '"' . $this->saveText($order->delivery['city']) . '"' . $separator . '"' . $this->saveText($order->delivery['state']) . '"' . $separator . '"' . $this->saveText($order->delivery['postcode']) . '"' . $separator . '"' . $this->saveText($order->delivery['country']['title']) . '"' . $separator . '"' . $this->saveText($order->delivery['name']) . '"' . "\r\n";
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
            tep_db_query("delete from " . TABLE_SUBSCRIPTION . " where subscription_id = '" . (int) $order_id . "'");
            tep_db_query("delete from " . TABLE_SUBSCRIPTION_PRODUCTS . " where subscription_id = '" . (int) $order_id . "'");
            tep_db_query("delete from " . TABLE_SUBSCRIPTION_STATUS_HISTORY . " where subscription_id = '" . (int) $order_id . "'");
            tep_db_query("delete from " . TABLE_SUBSCRIPTION_TOTAL . " where subscription_id = '" . (int) $order_id . "'");
        }
    }
    
    public function actionOrderdelete() {
        
        $this->layout = false;
        
        $order_id = Yii::$app->request->post('orders_id');
        
        tep_db_query("delete from " . TABLE_SUBSCRIPTION . " where subscription_id = '" . (int) $order_id . "'");
        tep_db_query("delete from " . TABLE_SUBSCRIPTION_PRODUCTS . " where subscription_id = '" . (int) $order_id . "'");
        tep_db_query("delete from " . TABLE_SUBSCRIPTION_STATUS_HISTORY . " where subscription_id = '" . (int) $order_id . "'");
        tep_db_query("delete from " . TABLE_SUBSCRIPTION_TOTAL . " where subscription_id = '" . (int) $order_id . "'");

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

}

