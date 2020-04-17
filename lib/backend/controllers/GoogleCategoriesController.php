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
class GoogleCategoriesController extends Sceleton
{
    
    public $acl = ['TEXT_SETTINGS', 'BOX_CATEGORIES_GOOGLE_PRODUCTTYPE'];

    public function __construct($id, $module)
    {
        parent::__construct($id, $module);
        \common\helpers\Translation::init('admin/google-categories');
    }

    public function actionIndex()
    {
      global $language;
     
      $this->selectedMenu = array('settings', 'google-categories');
      $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl(FILENAME_GOOGLECATEGORIES.'/index'), 'title' => HEADING_TITLE);
      
      $this->view->headingTitle = HEADING_TITLE;
      
        $this->view->ViewTable = array(
            /*array(
                'title' => '<div class="checker"><input class="uniform js-cat-batch js-cat-batch-master" type="checkbox"></div>',
                'not_important' => 2,
                'width' => '3%',
            ),*/
            array(
                'title' => TABLE_HEADING_CATEGORY_NAME,
                'not_important' => 0,
                'width' => '20%',
            ),
            array(
              'title' => TABLE_HEADING_CATEGORY_STATUS,
              'not_important' => 0,
                'width' => '7%',
            ),
            array(
              'title' => TABLE_HEADING_CATEGORY_HIERARCHY,
              'not_important' => 0,
                'width' => '73%',
            ),
        );

        $messages = $_SESSION['messages'];
        unset($_SESSION['messages']);
        if (!is_array($messages)) $messages = [];
        return $this->render('index', array('messages' => $messages));
      
    }

    public function actionList()
    {
        $languages_id = \Yii::$app->settings->get('languages_id');
        $draw = Yii::$app->request->get('draw', 1);
        $start = Yii::$app->request->get('start', 0);
        $length = Yii::$app->request->get('length', 10);
        $current_category_id = Yii::$app->request->get('id', 0);

        $search = '';
        if (isset($_GET['search']['value']) && tep_not_null($_GET['search']['value'])) {
            $keywords = tep_db_input(tep_db_prepare_input($_GET['search']['value']));
            $search .= " and (c.category_name like '%" . $keywords . "%')";
        }
      
        $formFilter = Yii::$app->request->get('filter','');
        $filter = [];
        parse_str($formFilter, $filter);
        if ($filter['osgID'] > 0) {
            $search .= " and c.parent_id = '0' ";         
        }
        
        if ($current_category_id) {            
            $childCategories =
                tep_db_fetch_array(tep_db_query(
                "select c.categories_id " .
                "from " . TABLE_GOOGLE_CATEGORIES . " c " .
                "WHERE  c.language_id='".(int)$languages_id . "' and c.parent_id='" . (int) $current_category_id . "' "
                ));               
            if(!isset($childCategories['categories_id'])) {//category has no parent
                $search .= " and c.categories_id='" . (int) $current_category_id . "'";
            } else {
                $search .= " and c.parent_id='" . (int) $current_category_id . "'";
            }
        }
        
        $current_page_number = ($start / $length) + 1;
        $responseList = array();
        if (isset($_GET['order'][0]['column']) && $_GET['order'][0]['dir']) {
            switch ($_GET['order'][0]['column']) {
                case 0:
                    $orderBy = "c.category_name " . tep_db_prepare_input($_GET['order'][0]['dir']);
                    break;
                case 1:
                    $orderBy = "c.categories_status " . tep_db_prepare_input($_GET['order'][0]['dir']);
                    break;
                default:
                    $orderBy = "c.categories_id";
                    break;
            }
        } else {
            $orderBy = "c.category_name";
        }    
        
        $orders_status_query_raw =
          "select * " .
          "from " . TABLE_GOOGLE_CATEGORIES . " c " .
          "WHERE  c.language_id='".(int)$languages_id . "' " . $search . " ".
          "order by {$orderBy}";

        $orders_status_split = new \splitPageResults($current_page_number, $length, $orders_status_query_raw, $orders_status_query_numrows);
        $orders_status_query = tep_db_query($orders_status_query_raw);
               
        $list_bread_crumb = CATEGORIES_GOOGLE_PRODUCTTYPE_LIST . ' ';
        $list_bread_crumb .= ' &gt; ' . \common\helpers\GoogleCategories::output_generated_category_path($current_category_id, 'category', '<span class="category_path__location clickable_element js-category-navigate" data-id="%1$s">%2$s</span>');
        
        $categoriesQty = $orders_status_query_numrows;
        while ($item_data = tep_db_fetch_array($orders_status_query)) {
    
            $categoryName = \common\helpers\GoogleCategories::wrapLink($item_data['categories_id'], $item_data['category_name'], !$item_data['parent_id']);

            $responseList[] = array(               
                '<div class=" ' . ($item_data['categories_status'] == 1 ? '' : ' dis_prod') . '">'
                .'<strong>' .
                  ($categoryName) .
                  tep_draw_hidden_field('id', $item_data['categories_id'], 'class="cell_identify"').
                  '<input class="cell_type" type="hidden" value="top">'.
                '</strong></div>',
                '<div>'.
                    ($item_data['categories_status'] == 1
                    ? '<input type="checkbox" value="' . $item_data['categories_id'] . '" name="categories_status" class="'. ($categoriesQty < CATALOG_SPEED_UP_DESIGN ? 'check_on_off' : 'check_on_off' ) .'" checked="checked">' 
                    : '<input type="checkbox" value="' . $item_data['categories_id'] . '" name="categories_status" class="'. ($categoriesQty < CATALOG_SPEED_UP_DESIGN ? 'check_on_off' : 'check_on_off' ) .'">')
                .'</div>',
                '<div class="' . ($item_data['categories_status'] == 1 ? '' : ' dis_prod') . '">'
                    .($item_data['parent_id'] ? \common\helpers\GoogleCategories::getCategoryHierarchy($item_data['parent_id'], $categoryName, $languages_id, true) : TABLE_GOOGLE_TOP_CATEGORY) 
                .'</div>',
            );
        }
        
        $response = array(
            'draw' => $draw,
            'recordsTotal' => $orders_status_query_numrows,
            'recordsFiltered' => $orders_status_query_numrows,
            'data' => $responseList,
            'breadcrumb' => $list_bread_crumb,
        );
        echo json_encode($response);          
        
    }
        
    public function actionSwitchStatusBatch()
    {
        $this->layout = false;
        $status = Yii::$app->request->post('state',0);
        $status = ( $status )?'true':'false';
        $items =  Yii::$app->request->post('batch',[]);
        if ( is_array($items) && count($items)>0 ) {
            foreach ($items as $item) {
                list($what, $id) = explode('_',$item,2);
                if ( $what=='p' ) {
                    \common\helpers\Product::set_status((int) $id, ($status == 'true' ? 1 : 0));
                }elseif( $what=='c' ) {
                    \common\helpers\Categories::set_categories_status((int) $id, ($status == 'true' ? 1 : 0));
                }
            }
        }
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->data = [
            'status' => 'ok',
        ];
    }

    public function actionSwitchStatus() {
        $type = Yii::$app->request->post('type');
        $categories_id = Yii::$app->request->post('id', 0);
        $status = Yii::$app->request->post('status');
        switch ($type) {
            /*case 'products_status':
                \common\helpers\Product::set_status((int) $id, ($status == 'true' ? 1 : 0));
                break;*/
            case 'categories_status':
                
                $languages_id = \Yii::$app->settings->get('languages_id');               
                $categories_status = ($status == 'true' ? 1 : 0);

                $children = $this->getChildren(array($categories_id), [], $languages_id);
                $children[] = $categories_id;

                $update_data = array(         
                    'categories_status' => $categories_status,        
                );                
                tep_db_perform(TABLE_GOOGLE_CATEGORIES, $update_data,'update', " categories_id IN (".implode(',',$children).") ");        
                break;
            default:
                break;
        }        
    }
    
    public function actionListActions() {
      $languages_id = \Yii::$app->settings->get('languages_id');

      $categories_id = Yii::$app->request->post('categories_id', 0);
      $this->layout = false;     
      if (!$categories_id) return;

      $odata = tep_db_fetch_array(tep_db_query("select * from " . TABLE_GOOGLE_CATEGORIES . 
          " where categories_id='" . (int)$categories_id . "' AND language_id='".$languages_id."'"));

        $odata['text'][ $languages_id ]['category_name'] = $odata['category_name'];

      $oInfo = new \objectInfo($odata, false);
      
        //get parent category status
        $check_query = tep_db_query("select categories_id, categories_status from " . TABLE_GOOGLE_CATEGORIES . " ".
            "where categories_id = '".$odata['parent_id']."'");
        $check = tep_db_fetch_array($check_query);
        $canEdit = true;
        if (isset($check['categories_status']) && $check['categories_status'] == '0') {
           $canEdit = false;
        }    

      echo '<div class="or_box_head">' . (isset($oInfo->text[$languages_id])?$oInfo->text[$languages_id]['category_name']:'&nbsp;') . '</div>';

      echo '<div class="row_or">' . TABLE_GOOGLE_CATEGORY_IS_ENABLED . ' <b>'.(!!$oInfo->categories_status?TABLE_GOOGLE_CATEGORY_YES:TABLE_GOOGLE_CATEGORY_NO).'</b></div>';
     
      echo '<div class="btn-toolbar btn-toolbar-order">';
        if ($canEdit) {
          echo '<button class="btn btn-edit btn-no-margin" onclick="itemEdit('.$categories_id.')">' . IMAGE_EDIT . '</button>'.
          '<!--<button class="btn btn-delete" onclick="itemDelete('.$categories_id.')">' . IMAGE_DELETE . '</button>-->';          
        } else {
            echo TEXT_GOOGLE_CATEGORIES_CANT_BE_EDITED;
        }
        echo '</div>';
    }
    
    public function actionEdit()
    {
        $languages_id = \Yii::$app->settings->get('languages_id');
        $categories_id = intval(Yii::$app->request->get('categories_id', 0));

        $odata = tep_db_fetch_array(tep_db_query("select * from " . TABLE_GOOGLE_CATEGORIES . 
          " where categories_id='" . (int)$categories_id . "' AND language_id='".$languages_id."'"));

        $odata['text'][ $languages_id ]['category_name'] = $odata['category_name'];
        $oInfo = new \objectInfo($odata, false);
                

        echo tep_draw_form('google_category', FILENAME_GOOGLECATEGORIES. '/save', 'categories_id=' . $oInfo->categories_id);

        echo '<div class="or_box_head">' . TEXT_INFO_HEADING_EDIT_GOOGLE_CATEGORY .((isset($oInfo->text[$languages_id])?' <br/>"'.$oInfo->text[$languages_id]['category_name'].'"':'&nbsp;')). '</div>';
        echo '<div class="col_desc">' . TEXT_INFO_EDIT_INTRO . '</div>';

      echo '<div class="check_linear"><label>' . tep_draw_checkbox_field('categories_status',1, !!$oInfo->categories_status) . '<span>' . TEXT_GOOGLE_CATEGORIES_STATUS . '</span></label></div>';
 
      echo '<div class="btn-toolbar btn-toolbar-order">';
      echo
        '<input type="button" value="' . IMAGE_UPDATE . '" class="btn btn-no-margin" onclick="itemSave('.($oInfo->categories_id?$oInfo->categories_id:0).')">'.
        '<input type="button" value="' . IMAGE_CANCEL . '" class="btn btn-cancel" onclick="resetStatement()">';
      echo '</div>';
      echo '</form>';
    }
    
    public function actionSave() {

        $languages_id = \Yii::$app->settings->get('languages_id');
        $categories_id = Yii::$app->request->get('categories_id', 0);
        $categories_status = intval(Yii::$app->request->post('categories_status',0));
     
        $children = $this->getChildren(array($categories_id), [], $languages_id);
        $children[] = $categories_id;

        $update_data = array(         
            'categories_status' => $categories_status,        
        );       
       
        tep_db_perform(TABLE_GOOGLE_CATEGORIES, $update_data,'update', " categories_id IN (".implode(',',$children).") ");

        $action = 'updated';    
        echo json_encode(array('message' => 'Google category has been ' . $action, 'messageType' => 'alert-success'));
    }
    
    public function getChildren($categories, $children = array(), $languages_id)
    {
        $check_query = tep_db_query("select categories_id "
            ."from " . TABLE_GOOGLE_CATEGORIES . " "
            ."where parent_id IN  (".implode(',',$categories).")  AND language_id='".$languages_id."'");
                
        $categories = [];
        while ($categoriesrow = tep_db_fetch_array($check_query)) {           
            $children[] = $categoriesrow['categories_id'];
            $categories[] = $categoriesrow['categories_id'];
        }
       
        if(count($categories)) {
            return $this->getChildren($categories, $children, $languages_id);
        }        
        return $children;
    }
    
    public function actionUploadfile()
    {
        //get file from google
        //$filename = "https://www.google.com/basepages/producttype/taxonomy-with-ids.en-GB.txt";
        
        //$filename = Yii::getAlias('@webroot')."/../taxonomy-with-ids.en-GB.txt";
        $filename = Yii::getAlias('@webroot')."/../taxonomy-with-ids.de-DE.txt";
        $myfile = fopen($filename, "r") or die("Unable to open file!");
        $index = 0;
       
        while (!feof($myfile)) {
            $row = fgets($myfile);
            $row  = trim($row);
            
            if(strpos($row, '#') === 0) {
                continue;
            }
            $row = explode('-', $row, 2);
            $id = trim($row[0] ?? '0');
            /*
            echo "<pre>";
            print_r("select categories_id from " . TABLE_GOOGLE_CATEGORIES . " ".
                    "where categories_id = '".$id."'");
            echo "</pre>";
            //die();
                $check_query = tep_db_query("select categories_id from " . TABLE_GOOGLE_CATEGORIES . " ".
                    "where categories_id = '".$id."'");
                $check = tep_db_fetch_array($check_query);
                if (!isset($check['categories_id'])) {
                    echo $id." !!!!<br/>";
                }            
            continue;
            */
            
            $categoryHierarchy = trim($row[1] ?? '');
            if (!$id || !$categoryHierarchy) {
                echo 'no id or category! '.$id.' '.$categoryHierarchy.'  <br>';
                continue;
            }
            $language  = (strpos($filename, 'en-GB') !== false) ? 1  /*English*/ : 0;
            if (!$language) {
                $language  = (strpos($filename, 'de-DE') !== false) ? 10 /*Deutsch*/ : 0;
            }            
            if(!$language) {
                die('misconfiguration!');
            }
           
            $categoriesContainer = explode('>', $categoryHierarchy);           
            foreach ($categoriesContainer as $key => $category) {
                $category = trim($category);
                $parentCategoryName = trim($categoriesContainer[$key-1] ?? '');              
                $this->saveCategory($id, $parentCategoryName, $category, $language, $key+1, $index);
            }
            //die('first row');
            $index++;
//            if($index>10) {
//                die();
//            }
        }
        echo 'done. '.$index.' records processed';
        fclose($myfile);
    }
    
    public function saveCategory($id, $parentCategoryName, $categoryName, $language, $level, $index)
    {
        
        $parent_id = 0;        
        if(!empty($parentCategoryName)) {
            $check_query = tep_db_query("select categories_id from " . TABLE_GOOGLE_CATEGORIES . " ".
                "where category_name like '".tep_db_input($parentCategoryName)."' AND language_id='".$language."'");
            $check = tep_db_fetch_array($check_query);
            if (isset($check['categories_id'])) {
                $parent_id = $check['categories_id'];
            }
        }        

        $check_query = tep_db_query("select categories_id from " . TABLE_GOOGLE_CATEGORIES . " ".
                "where category_name like '".tep_db_input($categoryName)."' AND language_id='".$language."'");
        $check = tep_db_fetch_array($check_query);
        if (isset($check['categories_id'])) {
            //echo $categoryName. ' exists <br>';
            return false;
        }
        
        //echo 'real row = '.($index+2).' id= '.$id.'<br/>';

        tep_db_query(
            "insert into " . TABLE_GOOGLE_CATEGORIES . " ".
                "(`categories_id`, `category_name`, `parent_id`, `categories_level`, `language_id`) values ".
                "('" . (int) $id . "','" . tep_db_input($categoryName) . "',".
            "'" . (int) $parent_id . "','" . (int) $level . "', '" . (int) $language . "')");
     
    }
 
}
