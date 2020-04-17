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

use backend\services\GroupsService;
use Yii;
use yii\web\Response;

class GroupsController extends Sceleton {

    public $acl = ['BOX_HEADING_CUSTOMERS', 'BOX_CUSTOMERS_GROUPS'];

    public function actionIndex() {
        $this->selectedMenu = array('customers', 'groups');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('groups/index'), 'title' => HEADING_TITLE);
        $this->view->headingTitle = HEADING_TITLE;

        $this->view->groupsTable = array(
            array(
                'title' => TABLE_HEADING_GROUPS,
                'not_important' => 1
            ),
            array(
                'title' => TABLE_HEADING_DISCOUNT,
                'not_important' => 1
            ),
        );

        /** @var \common\extensions\UserGroups\UserGroups $ext */
        if ($ext = \common\helpers\Acl::checkExtension('UserGroups', 'adminGroups')) {
            return $ext::adminGroups();
        }
        $row = Yii::$app->request->get('row', 0);
        $messages = Yii::$app->session->getAllFlashes();
        return $this->render('index', ['messages' => $messages]);
    }

    public function actionList() {
        $draw = Yii::$app->request->get('draw', 1);
        $start = Yii::$app->request->get('start', 0);
        $length = Yii::$app->request->get('length', 10);

        $responseList = array();
        if ($length == -1)
            $length = 10000;
        $query_numrows = 0;

        $q = \common\models\Groups::find()->select('groups_id, groups_name, groups_discount')->asArray();

        if (isset($_GET['search']['value']) && tep_not_null($_GET['search']['value'])) {
            $keywords = tep_db_input(tep_db_prepare_input($_GET['search']['value']));
            $q->andWhere(['like', 'groups_name', $keywords]);
        }

        $filters = [];
        $formFilter = Yii::$app->request->get('filter');
        parse_str($formFilter, $filters);

        /** @var \common\extensions\ExtraGroups\ExtraGroups $ext */
        if ($ext = \common\helpers\Acl::checkExtension('ExtraGroups', 'allowed')) {
          if ($ext::allowed() && isset($filters['groups_type_id'])) {
            $q->andWhere(['groups_type_id' => (int)$filters['groups_type_id']]);
          }
        }

        if (isset($_GET['order'][0]['column']) && !empty($_GET['order'][0]['dir'])) {
          if (strtolower($_GET['order'][0]['dir']) == 'desc') {
            $so = SORT_DESC;
          } else {
            $so = SORT_ASC;
          }

          switch ($_GET['order'][0]['column']) {
              default:
              case 0:
                $q->orderBy(['groups_name' => $so]);
                break;
              case 1:
                $q->orderBy(['groups_discount' => $so]);
                break;
          }
        } else {
            $q->orderBy(['groups_name' => SORT_ASC]);
        }
        
        $current_page_number = ( $start / $length ) + 1;
        new \splitPageResults($current_page_number, $length, $q, $query_numrows, 'groups_id');
        
        foreach ( $q->all() as $groups ) {
          $divDbl = '<div class="click_double" data-click-double="' . \yii\helpers\Url::to(['groups/itemedit', 'item_id' =>  $groups['groups_id'], 'row_id' => Yii::$app->request->post('row_id',0)]) . '">';
            $responseList[] = array(

                $divDbl. $groups['groups_name'] .
                '<input class="cell_identify" type="hidden" value="' . $groups['groups_id'] . '"></div>',

                $divDbl. trim(rtrim($groups['groups_discount'], '0'), '.') . '% </div>',
            );
        }

        $response = array(
            'draw' => $draw,
            'recordsTotal' => $query_numrows,
            'recordsFiltered' => $query_numrows,
            'data' => $responseList
        );
        return json_encode($response);
    }

    public function actionItempreedit() {
        $this->layout = false;
        $html = '';
        $restrictionsHtml = '';
        if ($ext = \common\helpers\Acl::checkExtension('UserGroupsRestrictions', 'allowed')) {
            if($ext::allowed()){
                $restrictionsHtml = $ext::adminShow();
            }
        }

        /** @var \common\extensions\ExtraGroups\ExtraGroups $ExtraGroups */
        if ($ExtraGroups = \common\helpers\Acl::checkExtension('ExtraGroups', 'allowed')) {
          if (!$ExtraGroups::allowedProductRestriction(\Yii::$app->request->post('item_id', 0))) {
            $restrictionsHtml = '';
          }
        }

        /** @var \common\extensions\UserGroups\UserGroups $ext */
        if ($ext = \common\helpers\Acl::checkExtension('UserGroups', 'adminPreeditGroups')) {
            $html = $ext::adminPreeditGroups();
        }
        return $html.$restrictionsHtml;
    }

    public function actionItemedit() {
        \common\helpers\Translation::init('admin/groups');
        $this->selectedMenu = array('customers', 'groups');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('groups/index'), 'title' => HEADING_TITLE);
        $this->view->headingTitle = TEXT_HEADING_EDIT_GROUP;
        $content = '';
        /** @var \common\extensions\UserGroups\UserGroups $ext */
        if ($ext = \common\helpers\Acl::checkExtension('UserGroups', 'adminEditGroups')) {
            $content = $ext::adminEditGroups();
        }
        if (Yii::$app->request->isAjax){
            return $this->renderAjax('edit', ['content' => $content]);
        } else {
            return $this->render('edit', ['content' => $content]);
        }
    }
    
    public function actionDropPromo(){
        $groups_id = Yii::$app->request->post('groups_id');
        $promo_id = Yii::$app->request->post('promo_id');
        if ($promo_id && $groups_id){
            \common\models\promotions\PromotionsAssignement::deletePromoOwners($promo_id, \common\models\promotions\PromotionsAssignement::OWNER_GROUP, [$groups_id]);
        }
        exit();
    }
    
    public function actionShowPromo(){
        $groups_id = Yii::$app->request->get('groups_id');
        if ($groups_id){
            /** @var \common\extensions\UserGroups\UserGroups $ext */
            if ($ext = \common\helpers\Acl::checkExtension('UserGroups', 'adminGroupsPromo')) {
                return $ext::adminGroupsPromo($groups_id);
            }
        }
        exit();
    }
    
    public function actionAddPromo(){
        $groups_id = Yii::$app->request->post('groups_id');
        $promo_id = Yii::$app->request->post('promo_id');
        if ($promo_id && $groups_id){
            \common\models\promotions\PromotionsAssignement::addPromoOwners($promo_id, \common\models\promotions\PromotionsAssignement::OWNER_GROUP, [$groups_id]);
            /** @var \common\extensions\UserGroups\UserGroups $ext */
            if ($ext = \common\helpers\Acl::checkExtension('UserGroups', 'getGroupsPromo')) {
                return $ext::getGroupsPromo($groups_id);
            }
        }
        exit();
    }

    public function actionConfirmitemdelete() {
        $this->layout = false;

        /** @var \common\extensions\UserGroups\UserGroups $ext */
        if ($ext = \common\helpers\Acl::checkExtension('UserGroups', 'adminConfirmDeleteGroups')) {
            return $ext::adminConfirmDeleteGroups();
        }
    }

    public function actionSubmit() {
        $this->layout = false;

        /** @var \common\extensions\UserGroups\UserGroups $ext */
        if ($ext = \common\helpers\Acl::checkExtension('UserGroups', 'adminSubmitGroups')) {
            $ext::adminSubmitGroups();
        }
        return $this->redirect(['groups/index', 
          'row' => Yii::$app->request->post('row_id', 0),
          'groups_type_id' => Yii::$app->request->post('groups_type_id', 0)
          ]);
    }

    public function actionItemdelete() {
        $this->layout = false;

        /** @var \common\extensions\UserGroups\UserGroups $ext */
        if ($ext = \common\helpers\Acl::checkExtension('UserGroups', 'adminDeleteGroups')) {
            return $ext::adminDeleteGroups();
        }
    }

    public function actionCustomers() {
        $this->layout = false;

        /** @var \common\extensions\UserGroups\UserGroups $ext */
        if ($ext = \common\helpers\Acl::checkExtension('UserGroups', 'adminCustomersGroups')) {
            return $ext::adminCustomersGroups();
        }
    }

    public function actionCustomersAdd() {
        $groups_id = Yii::$app->request->get('groups_id');
        $customers_id = Yii::$app->request->get('customers_id');
        tep_db_query("update " . TABLE_CUSTOMERS . " set groups_id = '" . (int) $groups_id . "' where customers_id = '" . (int) $customers_id . "'");
        return $this->actionCustomers();
    }

    public function actionCustomersDelete() {
        $customers_id = Yii::$app->request->get('customers_id');
        tep_db_query("update " . TABLE_CUSTOMERS . " set groups_id = '0' where customers_id = '" . (int) $customers_id . "'");
        return $this->actionCustomers();
    }

    public function actionRestrictions()
    {
        $languages_id = \Yii::$app->settings->get('languages_id');
        $html = '';
        if ($ext = \common\helpers\Acl::checkExtension('UserGroupsRestrictions', 'allowed')) {
            if($ext::allowed()){
                $groupId = (int)Yii::$app->request->get('groupId',0);
                $html = $ext::adminShowForm($groupId,$languages_id);
            }
        }
        return $html;
    }

    public function actionLoadTree() {
        $languages_id = \Yii::$app->settings->get('languages_id');
        $response_data = [];
        if ($ext = \common\helpers\Acl::checkExtension('UserGroupsRestrictions', 'allowed')) {
            if($ext::allowed()){
                $do = Yii::$app->request->post('do', '');
                $req_selected_data = tep_db_prepare_input(Yii::$app->request->post('selected_data'));
                $response_data = $ext::loadTree($do,$req_selected_data,$languages_id);
            }
        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        return $response_data;
    }



    function actionUpdateCatalogSelection() {
        $languages_id = \Yii::$app->settings->get('languages_id');
        if ($ext = \common\helpers\Acl::checkExtension('UserGroupsRestrictions', 'allowed')) {
            if($ext::allowed()){
                $req_selected_data = tep_db_prepare_input(Yii::$app->request->post('selected_data'));
                $groupId = (int)Yii::$app->request->get('groupId',0);
                $response_data = $ext::updateSelection($groupId,$req_selected_data,$languages_id);
            }
        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array(
            'status' => 'ok'
        );
    }
}
