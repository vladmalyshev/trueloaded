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

class MessagesController extends Sceleton {

    public $acl = ['BOX_HEADING_CUSTOMERS', 'BOX_CUSTOMERS_MESSAGES'];

    public function __construct($id, $module)
    {
        \common\helpers\Translation::init('extensions/messages');
        define('TABLE_MESSAGES', 'messages');
        define('TABLE_MESSAGES_ATTACHMENTS', 'messages_attachments');
        return parent::__construct($id, $module);
    }
      
    public function actionIndex() {

        $this->selectedMenu = array('customers', 'messages');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('messages/index'), 'title' => BOX_CUSTOMERS_MESSAGES);
        $this->view->headingTitle = BOX_CUSTOMERS_MESSAGES;

        $this->view->messagesTable = array(
            array(
                'title' => '<input type="checkbox" class="uniform">',
                'not_important' => 2
            ),
          array(
            'title' => ENTRY_FIRST_NAME,
            'not_important' => 0
          ),
          array(
            'title' => ENTRY_LAST_NAME,
            'not_important' => 0
          ),
            array(
                'title' => TABLE_HEADING_EMAIL,
                'not_important' => 1
            ),
            array(
                'title' => TABLE_HEADING_SUBJECT,
                'not_important' => 1
            ),
            array(
                'title' => TABLE_HEADING_DATE,
                'not_important' => 1
            ),
        );
        if ($ext = \common\helpers\Acl::checkExtension('Messages', 'adminActionIndex')) {
            return $ext::adminActionIndex();
        }
        return $this->render('index');
    }

    public function actionList() {
        global $access_levels_id;
        $draw = Yii::$app->request->get('draw', 1);
        $start = Yii::$app->request->get('start', 0);
        $length = Yii::$app->request->get('length', 10);

        $responseList = array();
        if ($length == -1)
            $length = 10000;
        $query_numrows = 0;

        if (isset($_GET['search']['value']) && tep_not_null($_GET['search']['value'])) {
            $keywords = tep_db_input(tep_db_prepare_input($_GET['search']['value']));
            $search_condition = " where owner_id='g_" . $access_levels_id . "' and trash=0 and content like '%" . $keywords . "%' ";
        } else {
            $search_condition = " where owner_id='g_" . $access_levels_id . "' and trash=0";
        }

        if (isset($_GET['order'][0]['column']) && $_GET['order'][0]['dir']) {
            switch ($_GET['order'][0]['column']) {
                case 0:
                    $orderBy = "messages_id " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                    break;
                case 1:
                    $orderBy = "groups_discount " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                    break;
                default:
                    $orderBy = "messages_id DESC";
                    break;
            }
        } else {
            $orderBy = "messages_id DESC";
        }

        $groups_query_raw = "select messages_id, from_id, subject, unread, date_added from " . TABLE_MESSAGES . $search_condition . " order by " . $orderBy;
        $current_page_number = ( $start / $length ) + 1;
        $_split = new \splitPageResults($current_page_number, $length, $groups_query_raw, $query_numrows, 'groups_id');
        $groups_query = tep_db_query($groups_query_raw);
        while ($groups = tep_db_fetch_array($groups_query)) {
            $customers_query = tep_db_query("select customers_id, customers_lastname, customers_firstname, customers_email_address from " . TABLE_CUSTOMERS . " where customers_id = '" . trim($groups['from_id'], 'c_') . "'");
            $customers = tep_db_fetch_array($customers_query);
        
            $markStart = '';
            $markEnd = '';
            if ($groups['unread'] == 1) {
                $markStart = '<b>';
                $markEnd = '</b>';
            }
            
            $responseList[] = array(
                '<input type="checkbox" class="uniform">' . '<input class="cell_identify" type="hidden" value="' . $groups['messages_id'] . '">',
                $markStart . $customers['customers_firstname'] . $markEnd,
                $markStart . $customers['customers_lastname'] . $markEnd,
                $markStart . $customers['customers_email_address'] . $markEnd,
                $markStart . $groups['subject'] . $markEnd,
                $markStart . \common\helpers\Date::datetime_short($groups['date_added']) . $markEnd,
            );
        }

        $response = array(
            'draw' => $draw,
            'recordsTotal' => $query_numrows,
            'recordsFiltered' => $query_numrows,
            'data' => $responseList
        );
        echo json_encode($response);
    }

    public function actionItempreedit() {
        $this->layout = false;
        if ($ext = \common\helpers\Acl::checkExtension('Messages', 'adminActionItempreedit')) {
            return $ext::adminActionItempreedit();
        }
    }

    public function actionView() {
        if ($ext = \common\helpers\Acl::checkExtension('Messages', 'adminActionView')) {
            $this->selectedMenu = array('customers', 'messages');
            $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('messages/index'), 'title' => BOX_CUSTOMERS_MESSAGES);
            $this->view->headingTitle = BOX_CUSTOMERS_MESSAGES;
            return $ext::adminActionView();
        }
    }
    
    public function actionDelete() {
        $this->layout = false;
        if ($ext = \common\helpers\Acl::checkExtension('Messages', 'adminActionDelete')) {
            return $ext::adminActionDelete();
        }
    }

    public function actionBatch() {
        $this->layout = false;
        if ($ext = \common\helpers\Acl::checkExtension('Messages', 'adminActionBatch')) {
            return $ext::adminActionBatch();
        }
    }
    
    public function actionAttachment() {
        if ($ext = \common\helpers\Acl::checkExtension('Messages', 'adminActionAttachment')) {
            return $ext::adminActionAttachment();
        }
    }

    public function actionReply() {
        if ($ext = \common\helpers\Acl::checkExtension('Messages', 'adminActionReply')) {
            return $ext::adminActionReply();
        }
    }
    
    public function actionGroupSwitcher() {
        if ($ext = \common\helpers\Acl::checkExtension('Messages', 'adminActionGroupSwitcher')) {
            return $ext::adminActionGroupSwitcher();
        }
    }
    
}
