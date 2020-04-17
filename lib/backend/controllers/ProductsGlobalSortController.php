<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */
/// 2do clean up on product delete, platform delete. Fill in by cron.NTH reorder to consequent numbers (by cron)

namespace backend\controllers;

use backend\models\ProductNameDecorator;
use Yii;

class ProductsGlobalSortController extends Sceleton {

  public $acl = ['BOX_HEADING_CATALOG', 'BOX_CATALOG_PRODUCTS_GLOBAL_SORT'];
  protected $selected_platform_id;

  public function __construct($id, $module=null) {
      parent::__construct($id, $module);

      $this->selected_platform_id = \common\classes\platform::firstId();
      $try_set_platform = (int)Yii::$app->request->get('platform_id', 0);
      if (Yii::$app->request->isPost) {
        $try_set_platform = (int)Yii::$app->request->post('platform_id', $try_set_platform);
      }
      $this->selected_platform_id = \common\classes\platform::validId($try_set_platform);
  }

  public function actionIndex() {
    $languages_id = \Yii::$app->settings->get('languages_id');
    
    $this->selectedMenu = array('catalog', 'products-global-sort');
    $this->navigation[] = array('link' => \Yii::$app->urlManager->createUrl('products-global-sort/index'), 'title' => BOX_CATALOG_PRODUCTS_GLOBAL_SORT);
    $this->topButtons[] = '<a href="' . \Yii::$app->urlManager->createUrl('products-global-sort/fill') . '" class="create_item"><i class="icon-file-text"></i>' . TEXT_FILL_ALL . '</a>';

    $this->view->headingTitle = BOX_CATALOG_PRODUCTS_GLOBAL_SORT;

    $this->view->MainTable = array(
      array(
        'title' => TABLE_HEADING_PRODUCTS,
        'not_important' => 0,
      ),
      array(
        'title' => TABLE_HEADING_ACTION,
        'not_important' => 0,
      ),
    );
    
    $_platforms = \common\classes\platform::getList(false);
    foreach( $_platforms as $_idx => $_platform ) {
        $_platforms[$_idx]['link'] = Yii::$app->urlManager->createUrl(['products-global-sort/index', 'platform_id' => $_platform['id']]);
    }

    $messages = $_SESSION['messages'];
    unset($_SESSION['messages']);
    if (!is_array($messages)) {
      $messages = [];
    }
    $top_categories = (new \yii\db\Query())
        ->select([
          'text' => 'cd.categories_name',
          'id' => 'cd.categories_id'
        ])
        ->from(['c' => TABLE_CATEGORIES])
        ->innerJoin(['cd' => TABLE_CATEGORIES_DESCRIPTION],
            'cd.categories_id=c.categories_id and cd.language_id=:language_id /*and cd.platform_id=:platform_id*/',
            [':language_id' => (int) $languages_id /*, ':platform_id' => (int)  $this->selected_platform_id*/])
        ->where(['c.parent_id' => 0])
        ->orderBy('c.sort_order')
        ->all();
    array_unshift($top_categories, ['id'=>'', 'text' => TEXT_ALL]);

    return $this->render('index', array('messages' => $messages,
              'platforms' => $_platforms,
              'top_categories' => $top_categories,
              'isMultiPlatforms' => \common\classes\platform::isMulti(false),
              'selected_platform_id' => $this->selected_platform_id,
      ));
  }

  public function actionList() {
    $languages_id = \Yii::$app->settings->get('languages_id');
    $draw = Yii::$app->request->get('draw', 1);
    $start = Yii::$app->request->get('start', 0);
    $length = Yii::$app->request->get('length', 10);
    $output = [];
    $category_id = 0;

    $formFilter = Yii::$app->request->get('filter');
    parse_str($formFilter, $output);

    if (isset($output['platform_id'])) {
      $platform_id = $this->selected_platform_id = (int)$output['platform_id'];
    }

    if (isset($output['category_id'])) {
      $category_id = (int)$output['category_id'];
    }
    
    $ps = new \common\classes\platform_settings($platform_id);
    $platform_id = $ps->getPlatformToDescription();

    $sQuery = (new \yii\db\Query())
        ->select([
          'sort_order' => 'gs.sort_order',
          'name' => ProductNameDecorator::instance()->listingQueryExpression('pd',''),
          'id' => 'pd.products_id'
        ])
        ->from(['gs' => TABLE_PRODUCTS_GLOBAL_SORT])
        ->innerJoin(['pd' => TABLE_PRODUCTS_DESCRIPTION], 
            'pd.products_id=gs.products_id and pd.language_id=:language_id and pd.platform_id=:platform_id',
            [':language_id' => (int) $languages_id, ':platform_id' => (int) $platform_id])
        ->where(['gs.platform_id' => (int) $this->selected_platform_id])
    ;
    //->andWhere('exists', (new \yii\db\Query())->select('*')->from(['p2p' => TABLE_PLATFORMS_PRODUCTS])->where('pd.products_id=p2p.products_id'))

    if (isset($_GET['search']['value']) && tep_not_null($_GET['search']['value'])) {
      $keywords = (tep_db_prepare_input($_GET['search']['value'])); //tep_db_input
      $sQuery->andWhere(['OR', ['like', 'products_name', "{$keywords}"],['like', 'products_internal_name', "{$keywords}"]]);
    }

    if ($category_id >0 ) {
      $sQuery->innerJoin(['p2c' => TABLE_PRODUCTS_TO_CATEGORIES], 'p2c.products_id=gs.products_id')
             ->innerJoin(['cc' => TABLE_CATEGORIES], 'cc.categories_id=p2c.categories_id')
             ->innerJoin(['cp' => TABLE_CATEGORIES], 'cc.categories_left>=cp.categories_left and cp.categories_right>=cc.categories_right')
             ->andWhere(['cp.categories_id' => (int)$category_id]);
      //echo $sQuery->createCommand()->getRawSql();    die;
    }

    $sQuery->orderBy("gs.sort_order desc");
    //echo "<PRE>";    var_dump($sQuery);    echo "</PRE>";
//echo $sQuery->createCommand()->getRawSql();    die;
    $responseList = array();

    $numRows = $sQuery->count();
    if ($start>$numRows) {
      $start = max(0, $numRows-$length);
    }
    $rows = $sQuery->offset($start)->limit($length)->all();

    if ($rows) {
      foreach ($rows as $row) {
        $responseList[] = array(
          '<div class="handle_cat_list"><span class="handle"><i class="icon-hand-paper-o"></i></span>'
          . '<div class="cat_name cat_name_attr cat_no_folder">' .
          $row['name'] . tep_draw_hidden_field('id', $row['id'], 'class="cell_identify"') . tep_draw_hidden_field('sort_order', $row['sort_order'], 'class="gso"') .
          '</div></div>',
          '<div>'
          . '<a class="gso-first" href="' .  \Yii::$app->urlManager->createUrl(['products-global-sort/edge', 'dir' => 'f', 'platform_id' => (int)$platform_id, 'id' => $row['id'], 'so' => $row['sort_order']]) . '"><span class="gso-first-text">' . TEXT_TO_FIRST . '</span></a>'
          . ' <a class="gso-last" href="' .  \Yii::$app->urlManager->createUrl(['products-global-sort/edge', 'dir' => 'l', 'platform_id' => (int)$platform_id, 'id' => $row['id'], 'so' => $row['sort_order']]) . '"><span class="gso-last-text">' . TEXT_TO_LAST . '</span></a>'
          . '</div>'
        );
      }
    }

    $response = array(
      'draw' => $draw,
      'recordsTotal' => $numRows,
      'recordsFiltered' => $numRows,
      'data' => $responseList
    );
    echo json_encode($response);
  }

  public function actionEdge() {
    $id = (int)Yii::$app->request->get('id', 0);
    $so = (int)Yii::$app->request->get('so', 0);
    $dir = Yii::$app->request->get('dir', 'f');
    if (!in_array($dir, ['f', 'l'])){
      $dir = 'f';
    }
    if ($dir == 'f') {
      $sd = 'DESC';
    } else {
      $sd = '';
    }
    $platform_id = (int)Yii::$app->request->get('platform_id', $this->selected_platform_id);
    $d = (new \yii\db\Query())
        ->select(['id' => 'products_id', 'so' => 'sort_order'])
        ->from(TABLE_PRODUCTS_GLOBAL_SORT)
        ->where(['platform_id' => $platform_id])
        ->orderBy("sort_order {$sd}")->limit(1)
        ->one();

    if (isset($d['so']) && (($dir == 'f' && $d['so']>$so) || ($dir == 'l' && $d['so']<$so))) {
      $q = (new \yii\db\Query())->createCommand()
          ->update(TABLE_PRODUCTS_GLOBAL_SORT,
              ["sort_order" => $d['so']+($dir == 'f'?1:-1)],
              "platform_id=:platform_id and products_id=:id",
              [':platform_id' => $platform_id, ':id' => $id]);
      $q->execute();
    }
    return $this->redirect(['products-global-sort/index', 'platform_id' => $platform_id]);
  }
  
  public function actionSortOrder() {
    $platform_id = (int)Yii::$app->request->get('platform_id', $this->selected_platform_id);
    $id = (int)Yii::$app->request->post('id', 0);
    $so = (int)Yii::$app->request->post('so', 0);
    // not used $tid = (int)Yii::$app->request->post('tid', 0);
    $tso = (int)Yii::$app->request->post('tso', 0);
    $dir = Yii::$app->request->post('dir', 'u');
    if (!in_array($dir, ['u', 'd'])){
      $dir = 'u';
    }
    if ($dir == 'u') {
      $sd = '-1';
    } else {
      $sd = '+1';
    }
    if ($so != $tso ) {
      $q = (new \yii\db\Query())->createCommand()
          ->update(TABLE_PRODUCTS_GLOBAL_SORT,
              ["sort_order" => new \yii\db\Expression("sort_order {$sd}")],
              " platform_id=:platform_id and sort_order between :mnso and :mxso ",
              [':platform_id' => $platform_id, ':mnso' => min($so, $tso), ':mxso' => max($so, $tso)]);
      $q->execute();
      $q = (new \yii\db\Query())->createCommand()
          ->update(TABLE_PRODUCTS_GLOBAL_SORT,
              ["sort_order" => $tso],
              "platform_id=:platform_id and products_id=:id",
              [':platform_id' => $platform_id, ':id' => $id]);
      $q->execute();
    }
    
  }

  public function actionFill() {
    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    $r = \common\helpers\Product::fillGlobalSort();
    //return ['message' => ($r === true ? TEXT_FILLED : $r), 'type' => ($r === true ? 'success' : 'danger')];
    $_SESSION['messages'][] = ['message' => ($r === true ? TEXT_FILLED : $r), 'messageType' => ($r === true ? 'alert-success' : 'alert-danger')];
    return $this->redirect(['products-global-sort/index', 'plaform_id' => $this->selected_platform_id]);
  }

  public function actionCopy() {
    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    $fromPlatformId = Yii::$app->request->post('from_id', 0);
    $toPlatformId = Yii::$app->request->post('to_id', 0);
    $r = \common\helpers\Product::copyGlobalSort($fromPlatformId, $toPlatformId);
    $_SESSION['messages'][] = ['message' => ($r === true ? TEXT_COPIED_OUT: $r), 'messageType' => ($r === true ? 'alert-success' : 'alert-danger')];
    return ['status' => 'OK'];
    //return $this->redirect(['products-global-sort/index', 'plaform_id' => $this->selected_platform_id]);
  }

}
