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

/**
 * default controller to handle user requests.
 */
class CollectionPointsController extends Sceleton {

    public $acl = ['BOX_HEADING_MODULES', 'BOX_MODULES_COLLECTION_POINT'];

    public function __construct($id, $module) {
        parent::__construct($id, $module);
        \common\helpers\Translation::init('admin/collection-points');
    }

    public function actionIndex() {

        $this->selectedMenu = array('modules', 'collection-points');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('collection-points/'), 'title' => BOX_MODULES_COLLECTION_POINT);

        $this->view->headingTitle = BOX_MODULES_COLLECTION_POINT;

        $this->view->ViewTable = array(
            array(
                'title' => TABLE_COLLECTION_POINT,
                'not_important' => 0,
            ),
        );

        $messages = $_SESSION['messages'];
        unset($_SESSION['messages']);
        if (!is_array($messages)) $messages = [];
        return $this->render('index', array('messages' => $messages));
    }

    public function actionList() {
        $draw = Yii::$app->request->get('draw', 1);
        $start = Yii::$app->request->get('start', 0);
        $length = Yii::$app->request->get('length', 10);

        $search = '';
        if (isset($_GET['search']['value']) && tep_not_null($_GET['search']['value'])) {
            $keywords = tep_db_input(tep_db_prepare_input($_GET['search']['value']));
            $search .= " and collection_points_text like '%" . $keywords . "%'";
        }

        $current_page_number = ($start / $length) + 1;
        $responseList = array();

        if (isset($_GET['order'][0]['column']) && $_GET['order'][0]['dir']) {
            switch ($_GET['order'][0]['column']) {
                case 0:
                    $orderBy = "collection_points_text " . tep_db_prepare_input($_GET['order'][0]['dir']);
                    break;
                default:
                    $orderBy = "sort_order";
                    break;
            }
        } else {
            $orderBy = "sort_order, collection_points_text";
        }

        $collection_points_query_raw = "select * from " . TABLE_COLLECTION_POINTS . " where 1" . $search . " " . "order by {$orderBy}";
        $collection_points_split = new \splitPageResults($current_page_number, $length, $collection_points_query_raw, $collection_points_query_numrows);
        $collection_points_query = tep_db_query($collection_points_query_raw);

        while ($item_data = tep_db_fetch_array($collection_points_query)) {

            $responseList[] = array(
                '<div class="handle_cat_list"><span class="handle"><i class="icon-hand-paper-o"></i></span><div class="cat_name cat_name_attr cat_no_folder">' .
                $item_data['collection_points_text'] .
                tep_draw_hidden_field('id', $item_data['collection_points_id'], 'class="cell_identify"') .
                '<input class="cell_type" type="hidden" value="top">' .
                '</div></div>',
            );
        }

        $response = array(
            'draw' => $draw,
            'recordsTotal' => $collection_points_query_numrows,
            'recordsFiltered' => $collection_points_query_numrows,
            'data' => $responseList
        );
        echo json_encode($response);
    }

    public function actionListActions() {
        $collection_points_id = (int)Yii::$app->request->post('collection_points_id');
        if (!$collection_points_id)
            return;
        $odata = tep_db_fetch_array(tep_db_query("select * from " . TABLE_COLLECTION_POINTS . " where collection_points_id='" . (int) $collection_points_id . "'"));
        return $this->renderAjax('list-actions', ['odata' => $odata]);
    }

    public function actionEdit() {
        $collection_points_id = (int)Yii::$app->request->get('collection_points_id');
        $odata = \common\models\CollectionPoints::findOne($collection_points_id);
        $addresses =[];
        foreach(\common\models\Warehouses::find()->with('address')->orderBy('sort_order')->all() as $warehouse){
           $addresses[$warehouse->address->warehouses_address_book_id] = $warehouse->warehouse_name;
        }
        return $this->renderAjax('edit', ['collection_points_id' => $collection_points_id, 'odata' => $odata, 'addresses' => $addresses]);
    }

    public function actionSave() {

        $collection_points_id = (int)Yii::$app->request->get('collection_points_id');
        $update_data = [
            'collection_points_text' => tep_db_prepare_input(Yii::$app->request->post('collection_points_text', '')),
            'warehouses_address_book_id' => tep_db_prepare_input(Yii::$app->request->post('warehouses_address_book_id', 0)),
        ];
            
        if ($collection_points_id == 0) {
            tep_db_perform(TABLE_COLLECTION_POINTS, $update_data);
            $message = TEXT_COLLECTION_POINTS_ADDED;
        } else {
            tep_db_perform(TABLE_COLLECTION_POINTS, $update_data, 'update', "collection_points_id='" . (int)$collection_points_id . "'");
            $message = TEXT_COLLECTION_POINTS_UPDATED;
        }

        echo json_encode(array('message' => $message, 'messageType' => 'alert-success'));
    }

    public function actionDelete() {

        $collection_points_id = (int)Yii::$app->request->post('collection_points_id');
        if (!$collection_points_id)
            return;

        tep_db_query("DELETE FROM " . TABLE_COLLECTION_POINTS . " where collection_points_id = '{$collection_points_id}'");
        echo 'reset';
    }

    public function actionSortOrder() {
        $moved_id = (int) $_POST['sort_top'];
        $ref_array = (isset($_POST['top']) && is_array($_POST['top'])) ? array_map('intval', $_POST['top']) : array();
        if ($moved_id && in_array($moved_id, $ref_array)) {
            $order_counter = 0;
            $order_list_r = tep_db_query(
                    "SELECT collection_points_id, sort_order " .
                    "FROM " . TABLE_COLLECTION_POINTS . 
                    " WHERE 1" .
                    " ORDER BY sort_order, collection_points_text"
            );
            while ($order_list = tep_db_fetch_array($order_list_r)) {
                $order_counter++;
                tep_db_query("UPDATE " . TABLE_COLLECTION_POINTS . " SET sort_order='{$order_counter}' WHERE collection_points_id='{$order_list['collection_points_id']}' ");
            }
            $get_current_order_r = tep_db_query(
                    "SELECT collection_points_id, sort_order " .
                    "FROM " . TABLE_COLLECTION_POINTS . " " .
                    "WHERE collection_points_id IN('" . implode("','", $ref_array) . "') " .
                    "ORDER BY sort_order"
            );
            $ref_ids = array();
            $ref_so = array();
            while ($_current_order = tep_db_fetch_array($get_current_order_r)) {
                $ref_ids[] = (int) $_current_order['collection_points_id'];
                $ref_so[] = (int) $_current_order['sort_order'];
            }

            foreach ($ref_array as $_idx => $id) {
                tep_db_query("UPDATE " . TABLE_COLLECTION_POINTS . " SET sort_order='{$ref_so[$_idx]}' WHERE collection_points_id='{$id}' ");
            }
        }
    }

}
