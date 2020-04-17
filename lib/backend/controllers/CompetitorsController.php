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
use common\models\Competitors;

class CompetitorsController extends Sceleton {

    public $acl = ['BOX_HEADING_CATALOG', 'BOX_CATALOG_COMPETITORS'];
    
    public function __construct($id, $module = null) {
        \common\helpers\Translation::init('admin/competitors');
        \common\helpers\Translation::init('admin/categories');
        parent::__construct($id, $module);
    }


    public function actionIndex() {

        $this->selectedMenu = array('catalog', 'competitors');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('competitors/index'), 'title' => HEADING_TITLE);
        $this->view->headingTitle = HEADING_TITLE;
        $this->topButtons[] = '<a href="' .Yii::$app->urlManager->createUrl(['competitors/edit', 'competitors_id' => 0]). '" class="create_item"><i class="icon-file-text"></i>' . TEXT_CREATE_NEW_COMPETITOR . '</a>';

        $this->view->competitorsTable = array(
            array(
                'title' => TABLE_HEADING_COMPETITOR_NAME,
                'not_important' => 1
            ),
            array(
                'title' => TABLE_HEADING_COMPETITOR_SITE,
                'not_important' => 1
            ),
            array(
                'title' => TABLE_HEADING_COMPETITOR_CURRENCY,
                'not_important' => 1
            ),
        );
        
        $row = Yii::$app->request->get('row', 0);
        $messages = Yii::$app->session->getAllFlashes();
        return $this->render('index', ['messages' => $messages]);
    }

    public function actionList() {
        $draw = Yii::$app->request->get('draw', 1);
        $start = Yii::$app->request->get('start', 0);
        $length = Yii::$app->request->get('length', 10);

    
        $competitors = Competitors::find()->where('1');

        if (isset($_GET['search']['value']) && tep_not_null($_GET['search']['value'])) {
          $keywords = tep_db_input(tep_db_prepare_input($_GET['search']['value']));
          $competitors->andWhere(['like', 'competitors_name', $keywords]);
        }

        $responseList = array();

        if (isset($_GET['order'][0]['column']) && $_GET['order'][0]['dir']) {
          switch ($_GET['order'][0]['column']) {
            case 0:
              $competitors->orderBy("competitors_name " . tep_db_prepare_input($_GET['order'][0]['dir']));
              break;
            case 1:
                $competitors->orderBy("competitors_site " . tep_db_prepare_input($_GET['order'][0]['dir']));
              break;
            case 2:
                $competitors->orderBy("competitors_currency " . tep_db_prepare_input($_GET['order'][0]['dir']));
              break;
            default:
                $competitors->orderBy("competitors_currency " . tep_db_prepare_input($_GET['order'][0]['dir']));
              break;
          }
        } else {
          $competitors->orderBy("competitors");
        }

        $query = clone $competitors;
        $list = $query->limit($length)->offset($start)->all();

        if ($list){
            foreach($list as $competitor){
                $responseList[] = array(
                    $competitor['competitors_name'] . tep_draw_hidden_field('id', $competitor['competitors_id'], 'class="cell_identify"'),
                    $competitor['competitors_site'],                
                    $competitor['competitors_currency'],
                );
            }
        }    

        $response = array(
            'draw' => $draw,
            'recordsTotal' => $competitors->count(),
            'recordsFiltered' => $competitors->count(),
            'data' => $responseList
        );
        echo json_encode($response);
    }


    public function actionEdit() {

        $competitors_id = Yii::$app->request->get('competitors_id', 0);
        $this->selectedMenu = array('catalog', 'competitors');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('competitors/index'), 'title' => HEADING_TITLE);
        $this->view->headingTitle = HEADING_TITLE;

        if ($competitors_id){
            $competitor = Competitors::find()->where('competitors_id =:id', [':id' => $competitors_id])->one();
        }

        if (!$competitor){
            $competitor = new Competitors();
        }

        $curr = \common\helpers\Currencies::get_currencies();    
        $curr = \yii\helpers\ArrayHelper::map($curr, 'id', 'text');            

        $messages = Yii::$app->session->getAllFlashes();
        if (Yii::$app->request->isAjax){
            return $this->renderAjax('edit', [
                'competitor' => $competitor,
                'curr' => $curr,
                'messages' => $messages,
            ]);
        }
        return $this->render('edit', [
            'competitor' => $competitor,
            'curr' => $curr,
            'messages' => $messages,
        ]);
    
    }

  public function actionSave() {
    
    $competitors_id = Yii::$app->request->post('competitors_id', 0);
    
    $competitors_name = tep_db_prepare_input(Yii::$app->request->post('competitors_name'));
    $competitors_site = tep_db_prepare_input(Yii::$app->request->post('competitors_site'));
    $competitors_currency = tep_db_prepare_input(Yii::$app->request->post('competitors_currency', ''));
    $competitors_mask = tep_db_prepare_input(Yii::$app->request->post('competitors_mask', ''));
    
    if ($competitors_id){
        $competitor = Competitors::find()->where('competitors_id =:id', [':id' => $competitors_id])->one();
    }
    
    if (!$competitor){
        $competitor = new Competitors();
    }
    
    if ($competitor){
        $competitor->setAttributes([
            'competitors_name' => $competitors_name,
            'competitors_site' => $competitors_site,
            'competitors_currency' => $competitors_currency,
            'competitors_mask' => $competitors_mask,
        ], false);
        
        if ($competitor->isNewRecord) {      
            $action = 'added';
        } else {      
            $action = 'updated';
        }
        
        $competitor->save(false);
    }
    
    $message = 'Competitor ' . $action;
    
    if (Yii::$app->request->isAjax){
        $type = 'success';
        if ($competitor->hasErrors()){
            $type = 'error';
            $message = 'Error';
        }        
        echo json_encode([
            'message' => $message,
            'type' => $type
        ]);
        exit();
    }
    
    
    if ($competitor->hasErrors()){
        Yii::$app->session->addFlash('alert-danger', 'Error');
        return $this->redirect('edit');
    } else {
        Yii::$app->session->addFlash('alert-success', $message);
        return $this->redirect('index');
    }
    
  }
  
  
  public function actionStatusactions() {
    
    $competitors_id = Yii::$app->request->post('competitors_id', 0);
    $this->layout = false;

    if ($competitors_id) {
        
        $competitor = Competitors::find()->where('competitors_id =:id', [':id' => $competitors_id])->one();

        if ($competitor) {
          echo '<div class="or_box_head">' . $competitor->competitors_name . '</div>';
          echo '<div class=""><a href="' . $competitor->competitors_site . '" target="_blank">' . $competitor->competitors_site . '</a></div>';
          echo '<div class="btn-toolbar btn-toolbar-order">';
          echo '<a href="' .Yii::$app->urlManager->createUrl(['competitors/edit', 'competitors_id' => $competitor->competitors_id]). '" class="btn btn-edit btn-no-margin">' . IMAGE_EDIT . '</a>';          
          echo '<button class="btn btn-delete" onclick="competitorDeleteConfirm(' . $competitor->competitors_id . ')">' . IMAGE_DELETE . '</button>';
          echo '</div>';
        }
    }
  }
  
  public function actionGetList(){
    $competitors = Competitors::find()->asArray()->all();
    echo json_encode(['competitors' => $competitors]);
    exit();
  }
  
  public function actionConfirmdelete(){
    $competitors_id = Yii::$app->request->post('competitors_id', 0);
    if ($competitors_id){
        $competitor = Competitors::find()->where('competitors_id =:id', [':id' => $competitors_id])->one();
        if ($competitor){
            $competitor->delete();
        }
    }
    echo 'ok';
  }

}
