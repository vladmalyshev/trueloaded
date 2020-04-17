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
use common\classes\order;
use common\classes\opc_order;
use common\classes\shopping_cart;
use common\components\Customer;
use Yii;

/**
 * default controller to handle user requests.
 */
class SubscribersController extends Sceleton {

    public $acl = ['BOX_HEADING_CUSTOMERS', 'BOX_CUSTOMERS_SUBSCRIBERS'];
    
    /**
     * Index action is the default action in a controller.
     */
	public function __construct($id, $module=''){
		parent::__construct($id, $module);
	}
    public function actionIndex() {

        $this->selectedMenu = array('customers', 'subscribers');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('subscribers/index'), 'title' => HEADING_TITLE);
        $this->view->headingTitle = HEADING_TITLE;
        $this->view->ordersTable = array(
            array(
                'title' => '<input type="checkbox" class="uniform">',
                'not_important' => 2
            ),
            array(
                'title' => TABLE_HEADING_SUBSCRIBERS_EMAIL_ADDRESS,
            ),
            array(
                'title' => TABLE_HEADING_SUBSCRIBERS_FIRSTNAME,
                'not_important' => 0
            ),
            array(
                'title' => TABLE_HEADING_SUBSCRIBERS_LASTNAME,
                'not_important' => 0
            ),   
            array(
                'title' => TABLE_HEADING_SUBSCRIBERS_DATETIME,
                'not_important' => 0
            ),
            array(
                'title' => TABLE_HEADING_SUBSCRIBERS_STATUS,
                'not_important' => 1
            ),
            /*array(
                'title' => TABLE_HEADING_SALES1,
                'not_important' => 1
            ),
            array(
                'title' => TABLE_HEADING_SALES2,
                'not_important' => 1
            ),
            array(
                'title' => TABLE_HEADING_SALES3,
                'not_important' => 1
            ),*/
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
                'name' => TEXT_SUBSCRIBERS_EMAIL_ADDRESS,
                'value' => 'subscribers_email_address',
                'selected' => '',
            ],
            [
                'name' => TEXT_SUBSCRIBERS_FIRSTNAME,
                'value' => 'subscribers_firstname',
                'selected' => '',
            ],
            [
                'name' => TEXT_SUBSCRIBERS_LASTNAME,
                'value' => 'subscribers_lastname',
                'selected' => '',
            ],
            [
                'name' => TEXT_SUBSCRIBERS_ID,
                'value' => 'subscribers_id',
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
                
        
        $status = [];
        $status[] = [
                'name' => TEXT_ALL_STATUSES,
                'value' => '',
                'selected' => '',
        ];
        $status[] = [
            'name' => TEXT_CONFIRMED,
            'value' => '1',
            'selected' => '',
        ];
        $status[] = [
            'name' => TEXT_UNCONFIRMED,
            'value' => '0',
            'selected' => '',
        ];
        foreach ($status as $key => $value) {
            if (isset($_GET['status']) && $value['value'] == $_GET['status']) {
                $status[$key]['selected'] = 'selected';
            }
        }
        $this->view->filters->status = $status;
        
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
			

        return $this->render('index',[
          'isMultiPlatform' => \common\classes\platform::isMulti(),
          'platforms' => \common\classes\platform::getList(),
        ]);
    }

    public function actionSubscribersList() {
      
        \common\helpers\Translation::init('admin/subscribers');
		
        $draw = Yii::$app->request->get('draw');
        $start = Yii::$app->request->get('start');
        $length = Yii::$app->request->get('length');
        
        if( $length == -1 ) $length = 10000;
		
        $_session = Yii::$app->session;

        $search = '';
        if (isset($_GET['search']['value']) && tep_not_null($_GET['search']['value'])) {
            $keywords = tep_db_input(tep_db_prepare_input($_GET['search']['value']));
            $search_condition = " and (s.subscribers_id like '%" . $keywords . "%' or s.subscribers_lastname like '%" . $keywords . "%' or s.subscribers_firstname like '%" . $keywords . "%' or s.subscribers_email_address like '%" . $keywords . "%') ";
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
          $filter .= " and s.platform_id IN ('" . implode("', '",$filter_by_platform). "') ";
        }

        if (tep_not_null($output['search']))
        {
            $search = tep_db_prepare_input($output['search']);
            switch ($output['by']) {
                case 'subscribers_email_address':
                  $filter .= " and s.subscribers_email_address like '%" . tep_db_input($search) . "%' ";
                  break;
                case 'subscribers_id':
                  $filter .= " and s.subscribers_id like '%" . tep_db_input($search) . "%' ";
                  break;
                case 'subscribers_firstname':
                  $filter .= " and s.subscribers_firstname like '%" . tep_db_input($search) . "%' ";
                  break;
                case 'subscribers_lastname':
                  $filter .= " and s.subscribers_lastname like '%" . tep_db_input($search) . "%' ";
                  break;
                case 'any':
                    $filter .= " and (";
                    $filter .= " s.subscribers_email_address like '%" . tep_db_input($search) . "%'";
                    $filter .= " or s.subscribers_firstname like '%" . tep_db_input($search) . "%' ";
                    $filter .= " or s.subscribers_lastname like '%" . tep_db_input($search) . "%' ";
                    $filter .= ") ";
                  break;
            }
        }
        
        if (tep_not_null($output['status'])) {
            $status = tep_db_prepare_input($output['status']);
            switch ($output['status']) {
                case '0':
                case '1':
                    $filter .= " and s.subscribers_status = '" . (int)$output['status'] . "' ";
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
              $filter .= " and to_days(s.subscribers_datetime) >= to_days('" . \common\helpers\Date::prepareInputDate($from) . "')";
            }
            if (tep_not_null($output['to'])) {
              $to = tep_db_prepare_input($output['to']);
              $filter .= " and to_days(s.subscribers_datetime) <= to_days('" . \common\helpers\Date::prepareInputDate($to) . "')";
            }
            break;
          case 'presel':
            if (tep_not_null($output['interval'])) {
                switch ($output['interval']) {
                    case 'week':
                        $filter .= " and s.subscribers_datetime >= '" . date('Y-m-d', strtotime('monday this week')) . "'";
                        break;
                    case 'month':
                        $filter .= " and s.subscribers_datetime >= '" . date('Y-m-d', strtotime('first day of this month')) . "'";
                        break;
                    case 'year':
                        $filter .= " and s.subscribers_datetime >= '" . date("Y")."-01-01" . "'";
                        break;
                    case '1':
                        $filter .= " and s.subscribers_datetime >= '" . date('Y-m-d') . "'";
                        break;
                    case '3':
                    case '7':
                    case '14':
                    case '30':
                        $filter .= " and s.subscribers_datetime >= date_sub(now(), interval " . (int)$output['interval'] . " day)";
                        break;
                }
            }
            break;
          }
        }
        
        
        
        if (isset($_GET['order'][0]['column']) && $_GET['order'][0]['dir'] && $_GET['draw'] != 1) {
            switch ($_GET['order'][0]['column']) {
                case 0:
                    $orderBy = "s.subscribers_email_address " . tep_db_prepare_input($_GET['order'][0]['dir']);
                    break;
                case 1:
                    $orderBy = "s.subscribers_firstname " . tep_db_prepare_input($_GET['order'][0]['dir']);
                    break;
                case 2:
                    $orderBy = "s.subscribers_lastname " . tep_db_prepare_input($_GET['order'][0]['dir']);
                    break;
                case 3:
                    $orderBy = "s.subscribers_status " . tep_db_prepare_input($_GET['order'][0]['dir']);
                    break;
                default:
                    $orderBy = "s.subscribers_id desc";
                    break;
            }
        } else {
            $orderBy = "s.subscribers_id desc";
        }
		
		$_session->set('filter', $filter);

        $subscribers_query_raw = "select s.* from " . TABLE_SUBSCRIBERS . " s where 1=1 " . $filter . " order by " . $orderBy;

        $current_page_number = ($start / $length) + 1;
        $subscribers_split = new \splitPageResults($current_page_number, $length, $subscribers_query_raw, $subscribers_query_numrows, 's.subscribers_id');
        $subscribers_query = tep_db_query($subscribers_query_raw);
        $responseList = array();
		$stack = [];
        while ($subscribers = tep_db_fetch_array($subscribers_query)) {

            $subscribers_email_address = $subscribers['subscribers_email_address'];
            $w = preg_quote(trim($search));
            if (!empty($w)) {
                $regexp = "/($w)(?![^<]+>)/i";
                $replacement = '<b style="color:#ff0000">\\1</b>';
                $subscribers['subscribers_firstname'] = preg_replace ($regexp,$replacement ,$subscribers['subscribers_firstname']);
                $p_list = preg_replace ($regexp,$replacement ,$p_list);
                $subscribers_email_address = preg_replace ($regexp,$replacement ,$subscribers['subscribers_email_address']);
            }

            $responseList[] = array(
                '<input type="checkbox" class="uniform">' . '<input class="cell_identify" type="hidden" value="' . $subscribers['subscribers_id'] . '">',
              
                '<div class="ord-desc-tab click_double" data-click-double="' . \Yii::$app->urlManager->createUrl(['subscribers/process-subscribers', 'subscribers_id' => $subscribers['subscribers_id']]) . '"><span class="ord-id">'.$subscribers['subscribers_email_address']. '</span></div>',
              
              '<div class="ord-desc-tab click_double" data-click-double="' . \Yii::$app->urlManager->createUrl(['subscribers/process-subscribers', 'subscribers_id' => $subscribers['subscribers_id']]) . '"><span class="ord-id">'.$subscribers['subscribers_firstname']. '</span></div>',
              
              '<div class="ord-desc-tab click_double" data-click-double="' . \Yii::$app->urlManager->createUrl(['subscribers/process-subscribers', 'subscribers_id' => $subscribers['subscribers_id']]) . '"><span class="ord-id">'.$subscribers['subscribers_lastname']. '</span></div>',
              
                '<div class="ord-date-purch click_double" data-click-double="' . \Yii::$app->urlManager->createUrl(['subscribers/process-subscribers', 'subscribers_id' => $subscribers['subscribers_id']]) . '"><span class="ord-id">'.\common\helpers\Date::datetime_short($subscribers['subscribers_datetime']).'</span></div>',
              
                '<div class="ord-status click_double" data-click-double="' . \Yii::$app->urlManager->createUrl(['subscribers/process-subscribers', 'subscribers_id' => $subscribers['subscribers_id']]) . '"><span class="ord-id">'.$subscribers['subscribers_status'].'</span><div>',
                
                //'<div class="ord-status click_double" data-click-double="' . \Yii::$app->urlManager->createUrl(['subscribers/process-subscribers', 'subscribers_id' => $subscribers['subscribers_id']]) . '"><span class="ord-id">'.$subscribers['subscribers_sales1'].'</span><div>',
                
                //'<div class="ord-status click_double" data-click-double="' . \Yii::$app->urlManager->createUrl(['subscribers/process-subscribers', 'subscribers_id' => $subscribers['subscribers_id']]) . '"><span class="ord-id">'.$subscribers['subscribers_sales2'].'</span><div>',
                
                //'<div class="ord-status click_double" data-click-double="' . \Yii::$app->urlManager->createUrl(['subscribers/process-subscribers', 'subscribers_id' => $subscribers['subscribers_id']]) . '"><span class="ord-id">'.$subscribers['subscribers_sales3'].'</span><div>'
            );
        }

        $response = array(
            'draw' => $draw,
            'recordsTotal' => $subscribers_query_numrows,
            'recordsFiltered' => $subscribers_query_numrows,
            'data' => $responseList
        );
        echo json_encode($response);
        //die();
    }

    public function actionSubscribersActions() {

        \common\helpers\Translation::init('admin/subscribers');

        $this->layout = false;
        $subscribers_id = Yii::$app->request->post('subscribers_id');

        $subscribers_query = tep_db_query("select s.* from " . TABLE_SUBSCRIBERS . " s where s.subscribers_id = '" . (int) $subscribers_id . "'");
        $subscribers = tep_db_fetch_array($subscribers_query);
        
        if (!is_array($subscribers)) {
            die("Please select subscriber");
        }
        
        $oInfo = new \objectInfo($subscribers);
        
        echo '<div class="or_box_head">'.TEXT_SUBSCRIBERS_ID . " " .$oInfo->subscribers_id . '</div>';
        echo '<div class="row_or"><div>' . TEXT_SUBSCRIBERS_DATETIME . '</div><div>' . \common\helpers\Date::datetime_short($oInfo->subscribers_datetime).'</div></div>';
        echo '<div class="row_or"><div>'.TEXT_SUBSCRIBERS_FIRSTNAME . ':</div><div>'  . $oInfo->subscribers_firstname .'</div></div>';
        echo '<div class="row_or"><div>'.TEXT_SUBSCRIBERS_LASTNAME . ':</div><div>'  . $oInfo->subscribers_lastname .'</div></div>';
        echo '<div class="row_or"><div>'.TEXT_SUBSCRIBERS_EMAIL_ADDRESS . ':</div><div>'  . $oInfo->subscribers_email_address .'</div></div>';
        
        echo '<div class="btn-toolbar btn-toolbar-order"><a class="btn btn-primary btn-process-order" href="' . \Yii::$app->urlManager->createUrl(['subscribers/process-subscribers', 'subscribers_id' => $oInfo->subscribers_id]) . '">' . TEXT_PROCESS_SUBSCRIBERS_BUTTON . '</a></div>';
    }
        
    public function actionSubmitSubscribers() {

        $this->layout = false;

        \common\helpers\Translation::init('admin/subscribers');

        $subscribers_id = tep_db_prepare_input(Yii::$app->request->post('subscribers_id'));
        $subscribers_status = tep_db_prepare_input(Yii::$app->request->post('subscribers_status'));
        $subscribers_email_address = tep_db_prepare_input(Yii::$app->request->post('subscribers_email_address'));
        $subscribers_firstname = tep_db_prepare_input(Yii::$app->request->post('subscribers_firstname'));
        $subscribers_lastname = tep_db_prepare_input(Yii::$app->request->post('subscribers_lastname'));


        $query = "update " . TABLE_SUBSCRIBERS . " set subscribers_email_address = '" . tep_db_input($subscribers_email_address) . "', subscribers_firstname = '".tep_db_input($subscribers_firstname)."', subscribers_lastname = '".tep_db_input($subscribers_lastname)."' where subscribers_id = '" . (int) $subscribers_id . "'";
        tep_db_query($query);
                
?>
        
<?php
        return $this->redirect(\Yii::$app->urlManager->createUrl(['subscribers/', 'by' => 'subscribers_id', 'search' => (int)$subscribers_id]));
        //return $this->actionProcessSubscribers();        
    }

    public function actionProcessSubscribers() {
        

        \common\helpers\Translation::init('admin/subscribers');

        $this->selectedMenu = array('customers', 'subscribers');
        
        if (Yii::$app->request->isPost) {
            $subscribers_id = (int)Yii::$app->request->post('subscribers_id');
        } else {
            $subscribers_id = (int)Yii::$app->request->get('subscribers_id');
        }

        $query = "select * from " . TABLE_SUBSCRIBERS . " where subscribers_id = '" . (int)$subscribers_id . "'";
        $result = tep_db_query($query);
        if (!tep_db_num_rows($result)) {
            return $this->redirect(\Yii::$app->urlManager->createUrl(['subscribers/', 'by' => 'subscribers_id', 'search' => (int)$subscribers_id]));
        } else {
            $array = tep_db_fetch_array($result);
            $subscribers_email_address = $array['subscribers_email_address'];
            $subscribers_firstname = $array['subscribers_firstname'];
            $subscribers_lastname = $array['subscribers_lastname'];
            $subscribers_md5hash = $array['subscribers_md5hash'];
        }
            
        
        $link = Yii::$app->urlManager->createUrl('subscribers/submit-subscribers');
        
        return $this->render('update.tpl', [
                'subscribers_id' => $subscribers_id,
                'link' => $link,
                'subscribers_md5hash' => $subscribers_md5hash,
                'subscribers_email_address' => $subscribers_email_address,
                'subscribers_firstname' => $subscribers_firstname,
                'subscribers_lastname' => $subscribers_lastname,
            ]
        );
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
    
    public function actionSubscribersexport() {
        if (tep_not_null($_POST['subscribers'])) {
            $separator = "\t";
            $filename = 'subscribers' . strftime('%Y%b%d_%H%M') . '.csv';

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
            $csv_str = '"Subscribers ID"' . $separator . '"email"' . $separator . '"first name"' . $separator . '"last name"' . $separator . '"datetime"' . $separator . '"Status"' . "\r\n";

            $subscribers_query = tep_db_query("select * from " . TABLE_SUBSCRIBERS . " where subscribers_id in ('" . implode("','", array_map('intval', explode(',', $_POST['subscribers']))) . "')");
            while ($subscribers = tep_db_fetch_array($subscribers_query)) {
                $csv_str .= '"' . $this->saveText($subscribers['subscribers_id']) . '"' . $separator . '"' . $this->saveText($subscribers['subscribers_email_address']) . '"' . $separator . '"' . $this->saveText($subscribers['subscribers_firstname']) . '"' . $separator . '"' . $this->saveText($subscribers['subscribers_lastname']) . '"' . $separator . '"' . $this->saveText($subscribers['subscribers_datetime']) . '"' . $separator . '"' . $this->saveText($subscribers['subscribers_status']) . '"' . "\r\n";
            }
            $csv_str = mb_convert_encoding($csv_str, 'UTF-16LE', 'UTF-8');
            echo $csv_str;
        }
        exit;
    }
    
    public function actionSubscribersdelete() {
        $this->layout = false;
        $selected_ids = Yii::$app->request->post('selected_ids');
        foreach ($selected_ids as $subscribers_id) {
            tep_db_query("delete from " . TABLE_SUBSCRIBERS . " where subscribers_id = '" . (int) $subscribers_id . "'");
        }
    }
    
    public function actionSubscriberdelete() {
        $this->layout = false;
        $subscribers_id = Yii::$app->request->post('subscribers_id');
        tep_db_query("delete from " . TABLE_SUBSCRIBERS . " where subscribers_id = '" . (int) $subscribers_id . "'");
    }
}

