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
use common\extensions\SeoRedirects\SeoRedirects;
use common\extensions\SeoRedirects\models\SeoRedirect;
use common\classes\platform;

class Seo_redirectsController extends Sceleton {

    public $acl = ['BOX_HEADING_SEO', 'BOX_HEADING_REDIRECTS'];

    public function __construct($id, $module = null) {
        \common\helpers\Translation::init('admin/seo_redirects');
        parent::__construct($id, $module);
    }

    public function beforeAction($action)
    {
        if (false === \common\helpers\Acl::checkExtension('SeoRedirects', 'allowed')) {
            $this->redirect(array('/'));
            return false;
        }
        return parent::beforeAction($action);
    }


    public function actionIndex() {

        $this->selectedMenu = array('seo_cms', 'seo_redirects');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('seo_redirects/index'), 'title' => HEADING_TITLE);
        $this->topButtons[] = '<a href="javascript:void(0)" onClick="edit(0);" class="create_item btn-file-text">' . TEXT_NEW . '</a>';
        $this->topButtons[] = '<a href="javascript:void(0)" onClick="trunk();" class="btn btn-delete">' . IMAGE_DELETE . '</a>';
        $this->topButtons[] = '<a href="javascript:void(0)" onClick="validateAll();" class="btn btn-import">' . IMAGE_VALIDATE . '</a>';
        $this->topButtons[] = '<a href="javascript:void(0)" onClick="exportRedirects();" class="btn btn-import">' . TEXT_EXPORT . '</a>';
        $this->topButtons[] = '<a href="' . (Yii::$app->urlManager->createUrl(['easypopulate', 'directory_id' => 2])) . '" class="btn btn-import">' . TEXT_IMPORT . '</a>';
        $this->view->headingTitle = HEADING_TITLE;

        $this->view->RedirectsTable = array(
            array(
                'title' => TABLE_HEADING_OLD_URL,
                'not_important' => 0,
            ),
            array(
                'title' => TABLE_HEADING_NEW_URL,
                'not_important' => 0,
            ),
            array(
                'title' => TABLE_HEADING_CODE,
                'not_important' => 0,
            ),
            array(
                'title' => TABLE_HEADING_STATUS,
                'not_important' => 0,
            ),
        );

        return $this->render('index', [
          'platforms' => platform::getList(false),
          'first_platform_id' => platform::firstId(),
          'default_platform_id' => platform::defaultId(),
          'isMultiPlatforms' => platform::isMulti(),
          'messages' => \Yii::$app->session->getAllFlashes(),
        ]);
    }

    public function actionTrunk(){
        $platform_id = Yii::$app->request->post('platform_id', 0);
        if($platform_id) {
          SeoRedirect::deleteAll(['platform_id' => (int)$platform_id]);
        }
        echo '';
        exit;
    }

    public function actionList() {

        $draw = Yii::$app->request->get('draw', 1);
        $start = Yii::$app->request->get('start', 0);
        $length = Yii::$app->request->get('length', 10);
        $platform_id = Yii::$app->request->get('platform_id');

        $searchWord = '';
        if (isset($_GET['search']['value']) && tep_not_null($_GET['search']['value'])) {
            $searchWord = tep_db_input(tep_db_prepare_input($_GET['search']['value']));
        }
        $orderBy = '';
        if (isset($_GET['order'][0]['column']) && $_GET['order'][0]['dir']) {
            switch ($_GET['order'][0]['column']) {
                case 0:
                    $orderBy = "old_url " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                    break;
                case 1:
                    $orderBy = "new_url " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                    break;
                case 2:
                    $orderBy = "redirect_code " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                    break;
                case 3:
                    $orderBy = "status " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                    break;
                default:
                    $orderBy = "seo_redirect_id desc";
                    break;
            }
        }
        $total = 0;
        $responseList = SeoRedirects::getAllItems($platform_id, ['total' => true, 'limit' => $length, 'offset' => $start, 'orderBy' => $orderBy], $total, $searchWord);

        $response = array(
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $responseList
        );
        echo json_encode($response);
    }

    public function actionItempreedit() {

        $item_id = (int) Yii::$app->request->post('item_id', 0);

        $cInfo = SeoRedirects::getItem($item_id);

        return $this->renderAjax('view', ['cInfo' => $cInfo]);
    }

    public function actionEdit() {
        $item_id = (int) Yii::$app->request->get('item_id', 0);
        $platform_id = (int) Yii::$app->request->get('platform_id', 0);

        $cInfo = SeoRedirects::getItem($item_id, $platform_id);

        return SeoRedirects::renderForm($cInfo);
    }

    public function actionSubmit(){
        $response = [];
        $item_id = Yii::$app->request->post('item_id', 0);
        try {
          if (SeoRedirects::saveItem($_POST)){
              $response['message'] = ($item_id?TEXT_MESSEAGE_SUCCESS:TEXT_MESSEAGE_SUCCESS_ADDED);
              $response['messageType'] = 'alert-success';
          } else {
              $response['message'] = TEXT_MESSAGE_ERROR;
              $response['messageType'] = 'alert-danger';
          }
        } catch (\Exception $e) {
            $response['message'] = TEXT_MESSAGE_ERROR . print_r($e,1);
            $response['messageType'] = 'alert-danger';
        }

        echo json_encode($response);
        exit();
    }

    public function actionDelete(){
        $this->layout = false;

        $item_id = Yii::$app->request->post('item_id', 0);
        if ($item_id){
            SeoRedirects::deleteItem($item_id);
        }
        echo '1';
        exit();
    }

    public function actionValidate(){
        $this->layout = false;

        $item_id = Yii::$app->request->post('item_id', 0);
        $update30x = Yii::$app->request->post('update30x', 0);
        $limit = Yii::$app->request->post('limit', 0);
        $platform_id = Yii::$app->request->post('platform_id', 0);

        try {
          if ($item_id || $limit){
              SeoRedirects::checkNewUrls(['id' => $item_id, 'update30x' => $update30x, 'limit' => $limit, 'platform_id' => $platform_id]);
          }
          $response['message'] = TEXT_MESSAGE_FINISHED;
          $response['messageType'] = 'alert-success';
        } catch (\Exception $e) {
            $response['message'] = TEXT_MESSAGE_ERROR . print_r($e,1);
            $response['messageType'] = 'alert-danger';
        }

        echo json_encode($response);
        exit();
    }

}
