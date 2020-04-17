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

use backend\models\ProductNameDecorator;
use Yii;

    class ProductsattributesController extends Sceleton {
        
        public $acl = ['BOX_HEADING_CATALOG', 'BOX_CATALOG_CATEGORIES_PRODUCTS_ATTRIBUTES', 'TEXT_PRODUCTS_ATTRIBUTES'];
        
        public function actionIndex()
        {
            $this->selectedMenu = array( 'catalog', 'product_attributes', 'productsattributes' );

            $this->navigation[]       = array( 'link' => Yii::$app->urlManager->createUrl( 'productsattributes/index' ), 'title' => HEADING_TITLE );
            $this->topButtons[] = '<a href="#" class="create_item" onClick="return editAttribute(0)">'.IMAGE_INSERT.'</a>';
            $this->view->headingTitle = HEADING_TITLE;

            $this->view->attributesTable = array(
                array(
                    'title'         => TABLE_HEADING_OPT_NAME,
                    'not_important' => 0
                ),
//                array(
//                    'title'         => TABLE_HEADING_OPT_SORT_ORDER,
//                    'not_important' => 1
//                ),
            );
            
            $row = (int)Yii::$app->request->get('row');
            $global_id = (int)Yii::$app->request->get('global_id');
            $global_type_code= Yii::$app->request->get('global_type_code', 'option');

            return $this->render( 'index', [
                'row' => $row,
                'global_id' => $global_id,
                'global_type_code' => $global_type_code
            ] );
        }

        public function actionList()
        {
            $languages_id = \Yii::$app->settings->get('languages_id');
            $draw              = Yii::$app->request->get( 'draw', 1 );
            $start             = Yii::$app->request->get( 'start', 0 );
            $length            = Yii::$app->request->get( 'length', 10 );
            $current_option_id = (int) Yii::$app->request->get( 'id', 0 );

            //$cell_type = 'option'; // option|suboption

            if( $length == -1 ) $length = 1000;

            if( isset( $_GET['search']['value'] ) && tep_not_null( $_GET['search']['value'] ) ) {
                $keywords = tep_db_input( tep_db_prepare_input( $_GET['search']['value'] ) );

                if( $current_option_id > 0 ) {
                    $search_condition = " where pov.language_id = '$languages_id' and pov.products_options_values_name like '%" . $keywords . "%' ";
                } else {
                    $search_condition = " where language_id = '" . (int) $languages_id . "' and products_options_name like '%" . $keywords . "%' ";
                }
            } else {
                if( $current_option_id > 0 ) {
                    $search_condition = " where pov.language_id = '" . (int) $languages_id . "' ";
                } else {
                    $search_condition = " where language_id = '" . (int) $languages_id . "' ";
                }
            }

            if( isset( $_GET['order'][0]['column'] ) && $_GET['order'][0]['dir'] ) {
                switch( $_GET['order'][0]['column'] ) {
                    case 0:
                        $orderBy = "products_options_name " . tep_db_prepare_input( $_GET['order'][0]['dir'] );
                        break;
                    default:
                        $orderBy = "products_options_name";
                        break;
                }
            } else {
                $orderBy = "products_options_sort_order, products_options_name";
            }

            if( $current_option_id > 0 ) {
                $_query = "  select pov.products_options_values_id, pov.products_options_values_name, pov2po.products_options_id
                         from ".TABLE_PRODUCTS_OPTIONS_VALUES." pov
                         left join ".TABLE_PRODUCTS_OPTIONS_VALUES_TO_PRODUCTS_OPTIONS." pov2po on pov.products_options_values_id = pov2po.products_options_values_id
                         $search_condition
                         and pov2po.products_options_id = '$current_option_id'
                         order by pov.products_options_values_sort_order, pov.products_options_values_name";

                $current_page_number = ( $start / $length ) + 1;
                $_split              = new \splitPageResults( $current_page_number, $length, $_query, $options_query_numrows, 'pov.products_options_values_id' );
                $Qgroups             = tep_db_query( $_query );


                $responseList = array();
                $cell_type    = 'suboption';

                $responseList[] = array(
                    '<span class="parent_cats"><i class="icon-circle"></i><i class="icon-circle"></i><i class="icon-circle"></i></span>' .
                    '<input class="cell_type" type="hidden" value="root" >' .
                    '<input class="cell_identify" type="hidden" value="0" data-option_id="'.$current_option_id.'">'
                );

                while( $Dgroups = tep_db_fetch_array( $Qgroups ) ) {
                    $responseList[] = array(
                       '<div class="handle_cat_list"><span class="handle"><i class="icon-hand-paper-o"></i></span><div class="optval_name cat_name_attr">' . $Dgroups['products_options_values_name']  .
                        //$Dgroups['products_options_values_name'] .
                        '<input class="cell_identify" type="hidden" value="' . $Dgroups['products_options_values_id'] . '">' .
                        '<input class="cell_type" type="hidden" value="' . $cell_type . '" >'.
                        '</div></div>'
                    );
                }

            } else {
                $_query = "select * from " . TABLE_PRODUCTS_OPTIONS . " $search_condition order by $orderBy ";

                $current_page_number = ( $start / $length ) + 1;
                $_split              = new \splitPageResults( $current_page_number, $length, $_query, $options_query_numrows, 'products_options_id' );
                $Qgroups             = tep_db_query( $_query );

                $responseList = array();
                $cell_type    = 'option';

                while( $Dgroups = tep_db_fetch_array( $Qgroups ) ) {
                    $responseList[] = array(
                        //'<div class="cat_name cat_name_attr">' . $Dgroups['products_options_name'] . '</div><input class="cell_identify" type="hidden" value="' . $Dgroups['products_options_id'] . '">' .
                      '<div class="handle_cat_list"><span class="handle"><i class="icon-hand-paper-o"></i></span><div class="cat_name cat_name_attr">' . $Dgroups['products_options_name']  .
                        '<input class="cell_identify" type="hidden" value="' . $Dgroups['products_options_id'] . '">'.
                        '<input class="cell_type" type="hidden" value="' . $cell_type . '" >'.
                      '</div></div>'
                    );
                }
            }


            $response = array(
                'draw'            => $draw,
                'recordsTotal'    => $options_query_numrows,
                'recordsFiltered' => $options_query_numrows,
                'data'            => $responseList
            );
            echo json_encode( $response );
        }

        function actionItempreedit()
        {
            $this->layout = FALSE;

            $languages_id = \Yii::$app->settings->get('languages_id');

            \common\helpers\Translation::init('admin/productsattributes');

            $item_id   = (int) Yii::$app->request->post( 'item_id' );
            $type_code   =  Yii::$app->request->post( 'type_code' );
            $global_id   =  Yii::$app->request->post( 'global_id' );

            $products_num = $values_num = 0;
            $notice = '';
            if ($type_code=='suboption'){
                $_query = "select *, products_options_values_name as name from " . TABLE_PRODUCTS_OPTIONS_VALUES . " where products_options_values_id = '$item_id' and language_id = '$languages_id' ";
                $Ovalue = tep_db_query( $_query );
                $Dvalue = tep_db_fetch_array( $Ovalue );

                $checkData    = tep_db_fetch_array( tep_db_query( 'select count(*) as total from ' . TABLE_PRODUCTS_ATTRIBUTES . " where options_values_id = '" . $item_id . "'" ) );
                $products_num = $checkData['total'];

                if( $values_num > 0 OR $products_num > 0 ) {
                    $notice     =sprintf( TEXT_OPTION_VALUE_NOTICE, $products_num);
                }
                //TEXT_OPTION_VALUE_DELETE_NOTICE;

                $option = tep_db_fetch_array(tep_db_query("select o.products_options_name from " . TABLE_PRODUCTS_OPTIONS_VALUES_TO_PRODUCTS_OPTIONS . " v2o, " . TABLE_PRODUCTS_OPTIONS . " o where v2o.products_options_values_id = '" . $item_id . "' and v2o.products_options_id = o.products_options_id and o.language_id = '" . $languages_id . "'"))
                ?>
              <script type="text/javascript">
                $('.attribute-name-heading').remove();
                $('.top_bead h1').append('<span class="attribute-name-heading">: <?php echo $option['products_options_name']; ?></span>')
              </script>
                <?php

            }else {
                $_query = "select *, products_options_name as name from " . TABLE_PRODUCTS_OPTIONS . " where products_options_id = '$item_id' and language_id = '$languages_id' ";
                $Ovalue = tep_db_query( $_query );
                $Dvalue = tep_db_fetch_array( $Ovalue );

                $products_num = tep_db_num_rows( tep_db_query( 'select count(*) as total from ' . TABLE_PRODUCTS_ATTRIBUTES . " where options_id = '" . $item_id . "' group by products_id" ) );

                $checkData  = tep_db_fetch_array( tep_db_query( 'select count(*) as total from ' . TABLE_PRODUCTS_OPTIONS_VALUES_TO_PRODUCTS_OPTIONS . " where products_options_id = '" . $item_id . "'" ) );
                $values_num = $checkData['total'];
                if( $values_num > 0 OR $products_num > 0 ) {
                    ob_start();
                    printf( TEXT_OPTION_NOTICE, $products_num, $values_num );
                    $notice     = ob_get_clean();
                }
                ?>
              <script type="text/javascript">
                $('.attribute-name-heading').remove()
              </script>
                <?php
            }
            return $this->renderAjax('preedit', ['Dvalue' => $Dvalue, 'notice' => $notice, 'item_id' => $item_id, 'type_code' => $type_code, 'global_id' => $global_id, 'products_num' => $products_num]);
        }
        
        public function actionViewProducts(){
          $languages_id = \Yii::$app->settings->get('languages_id');
          $item_id   = (int) Yii::$app->request->get( 'item_id' );
          $type_code   =  Yii::$app->request->get( 'type_code' );
          $response = [];
            if ($type_code=='suboption'){
              $checkData = tep_db_query( 'select distinct '.ProductNameDecorator::instance()->listingQueryExpression('pd','').' AS products_name, p.products_model, p.products_id from ' . TABLE_PRODUCTS_ATTRIBUTES . " pa inner join " . TABLE_PRODUCTS . " p on p.products_id = pa.products_id left join " . TABLE_PRODUCTS_DESCRIPTION . " pd on pd.products_id = p.products_id and pd.language_id = $languages_id and pd.platform_id = '".intval(\common\classes\platform::defaultId())."' where pa.options_values_id = '" . $item_id . "'" );
            }else {
              $checkData = tep_db_query( 'select distinct '.ProductNameDecorator::instance()->listingQueryExpression('pd','').' AS products_name, p.products_model, p.products_id from ' . TABLE_PRODUCTS_ATTRIBUTES . " pa inner join " . TABLE_PRODUCTS . " p on p.products_id = pa.products_id left join " . TABLE_PRODUCTS_DESCRIPTION . " pd on pd.products_id = p.products_id and pd.language_id = $languages_id and pd.platform_id = '".intval(\common\classes\platform::defaultId())."' where pa.options_id = '" . $item_id . "'" );
            } 
          if (tep_db_num_rows($checkData)){
            while($row = tep_db_fetch_array($checkData)){
               $response[] = ['name' => $row['products_name'], 'model' => (tep_not_null($row['products_model']) ? ' ( ' . $row['products_model'] . ' )' : ''), 'url' => \yii\helpers\Url::to([FILENAME_CATEGORIES . '/productedit', 'pID' => $row['products_id']])];
            }
          }            
          return $this->renderAjax('list', ['content' => $response]);
        }

        public function actionAttributeedit() {

            \common\helpers\Translation::init('admin/productsattributes');
            \common\helpers\Translation::init('admin/properties');

            $type_code = Yii::$app->request->get('type_code', NULL);
            $products_options_id = (int) Yii::$app->request->get('products_options_id');
            $global_id = (int) Yii::$app->request->get('global_id', 0);

            $default_language_id = $languages_id;
            $languages = \common\helpers\Language::get_languages();
            for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
              $languages[$i]['logo'] = $languages[$i]['image'];
              if ($languages[$i]['code'] == DEFAULT_LANGUAGE) {
                $default_language_id = $languages[$i]['id'];
              }
            }
            
            $options = [];
            $process_type = 'option';
            $type = '';
            if (!$products_options_id) {
                $header = TEXT_INSERT_ATTRIBUTE;
                if ($global_id > 0) {
                    $header = TEXT_OPTION_VALUE_NEW_HEADING;
                    $process_type = 'value';
                }
                foreach ($languages as $languages_data) {
                    $lang_id = $languages_data['id'];
                    $options[$lang_id] = array('option_name' => '', 'option_image' => '', 'option_color' => '');
                }
            } elseif ($products_options_id > 0) {
                $header = TEXT_EDIT_ATTRIBUTE;

                if ($type_code == 'option') {

                    foreach ($languages as $languages_data) {
                        $_query = "select * from " . TABLE_PRODUCTS_OPTIONS . " where products_options_id = '$products_options_id' and language_id='" . $languages_data['id'] . "' ";
                        $Ovalue = tep_db_query($_query);
                        if ($Dvalue = tep_db_fetch_array($Ovalue)) {
                            $type = $Dvalue['type'];
                            $is_virtual = $Dvalue['is_virtual'];
                            $display_filter = $Dvalue['display_filter'];
                            $display_search = $Dvalue['display_search'];
                            $options[$languages_data['id']] = [
                                'option_name' => $Dvalue['products_options_name'],
                                'option_image' => $Dvalue['products_options_image'],
                                'option_color' => $Dvalue['products_options_color'],
                            ];
                        } else {
                            $options[$languages_data['id']] = [
                                'option_name' => '',
                                'option_image' => '',
                                'option_color' => '',
                            ];
                        }
                    }
                } else {
                    $header = TEXT_OPTION_VALUE_EDIT_HEADING;
                    $process_type = 'value';
                    foreach ($languages as $languages_data) {
                        $Ovalue = tep_db_query("select * from " . TABLE_PRODUCTS_OPTIONS_VALUES . " where products_options_values_id = '$products_options_id' and language_id='" . $languages_data['id'] . "' ");
                        if ($Dvalue = tep_db_fetch_array($Ovalue)) {
                            $options[$languages_data['id']] = [
                                'option_name' => $Dvalue['products_options_values_name'],
                                'option_image' => $Dvalue['products_options_values_image'],
                                'option_color' => $Dvalue['products_options_values_color'],
                            ];
                        } else {
                            $options[$languages_data['id']] = [
                                'option_name' => '',
                                'option_image' => '',
                                'option_color' => '',
                            ];
                        }
                    }
                }
            }

        $this->navigation[] = array( 'link' => Yii::$app->urlManager->createUrl('productsattributes/attributesubmit'), 'title' => $header );
        $this->view->headingTitle = $header;
        
        return $this->render('edit.tpl', [
            'languages' => $languages, 
            'default_language' => DEFAULT_LANGUAGE, 
            'products_options_id' => $products_options_id,
            'options' => $options,
            'process_type' =>$process_type,
            'type_code' => $type_code,
            'type' => $type,
            'is_virtual' => $is_virtual,
            'display_filter' => $display_filter,
            'display_search' => $display_search,
            'global_id' => $global_id,
        ]);
    }

    function actionAttributesubmit() {
        
            \common\helpers\Translation::init('admin/productsattributes');
                    
            $same_all_languages = (int) Yii::$app->request->post('same_all_languages');
            $products_options_id = (int) Yii::$app->request->post('products_options_id');
            $global_id = (int) Yii::$app->request->post('global_id', 0);
            $option_name = Yii::$app->request->post('option_name');
            $option_image = Yii::$app->request->post('option_image');
            $option_image_loaded = Yii::$app->request->post('option_image_loaded');
            $option_color = Yii::$app->request->post('option_color');
            $option_sort_order = Yii::$app->request->post( 'option_sort_order_eng', array());
            $type_code = Yii::$app->request->post('type_code', 'option');
            $type = Yii::$app->request->post('type', '');
            $is_virtual = Yii::$app->request->post('is_virtual', 0);
            $display_filter = Yii::$app->request->post('display_filter', 0);
            $display_search = Yii::$app->request->post('display_search', 0);

            $error = false;
            $message = '';
            $messageType = 'success';
            
            $_l = \common\helpers\Language::get_languages(true);
            $existed_l = [];
            $_def_l = \common\helpers\Language::get_default_language_id();
            
            $o_data = [];

            if( $type_code == 'suboption' ) {
                if( $products_options_id === 0 ) {
                    // Insert

                    $max_values_id_query  = tep_db_query( "select max(products_options_values_id) + 1 as next_id from " . TABLE_PRODUCTS_OPTIONS_VALUES );
                    $max_values_id_values = tep_db_fetch_array( $max_values_id_query );
                    $next_id              = (int) $max_values_id_values['next_id'];
                    if( !( $next_id > 0 ) ) $next_id = 1;
                    
                    foreach( $option_name as $_language_id => $option ) {
                        $existed_l[] = $_language_id;
                        $data_array = [
                            'products_options_values_id' => $next_id,
                            'products_options_values_name' => $option,
                            'language_id' => (int)$_language_id,
                        ];
                        if ($_def_l == $_language_id) {
                            $o_data = ['products_options_values_name' => $option, 'products_options_values_id' => $next_id, 'language_id' => (int)$_language_id];
                            
                        }
                        if ($same_all_languages) {
                            $data_array['products_options_values_name'] = $option_name[$_def_l];
                            $data_array['products_options_values_color'] = $option_color[$_def_l];
                            if (tep_not_null($option_image_loaded[$_def_l])) {
                                $data_array['products_options_values_image'] = 'subopt-' . $products_options_id . '-' . $option_image_loaded[$_def_l];
                            }
                        } else {
                            if ( isset($option_sort_order[$_language_id]) ) {
                                $data_array['products_options_values_sort_order'] = $option_sort_order[$_language_id];
                            }
                            if ( isset($option_color[$_language_id]) ) {
                                $data_array['products_options_values_color'] = $option_color[$_language_id];
                            }
                        }
                        if (tep_not_null($option_image_loaded[$_language_id])) {
                            $path = \Yii::getAlias('@webroot');
                            $path .= DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
                            $tmp_name = $path . $option_image_loaded[$_language_id];
                            $new_name = DIR_FS_CATALOG_IMAGES . 'subopt-' . $products_options_id . '-' . $option_image_loaded[$_language_id];
                            @copy($tmp_name, $new_name);
                            @unlink($tmp_name);
                            $data_array['products_options_values_image'] = 'subopt-' . $products_options_id . '-' . $option_image_loaded[$_language_id];
                        }
                        tep_db_perform(TABLE_PRODUCTS_OPTIONS_VALUES, $data_array);
                        $message = "SubOption inserted";
                    }
                    
                    $existed_l = array_unique($existed_l);
                    $all = [];
                    foreach($_l as $_v){
                      $all[] = $_v['id'];
                    }
                    $all = array_diff($all, $existed_l);
                    if (count($all) && count($o_data)>0){
                      foreach($all as $_language_id){
                        tep_db_query( "insert into " . TABLE_PRODUCTS_OPTIONS_VALUES . " set products_options_values_name = '" . tep_db_input( $o_data['products_options_values_name'] ) . "', language_id = '" . (int)$_language_id . "', products_options_values_id = '" . (int)$o_data['products_options_values_id'] ."', products_options_values_sort_order = '" . (int)$o_data['sort_order']. "'" );
                      }
                    }
                        
                    tep_db_query( "insert into " . TABLE_PRODUCTS_OPTIONS_VALUES_TO_PRODUCTS_OPTIONS . " set products_options_id = '$global_id', products_options_values_id = '$next_id'  " );

                } else {
                    // Update Suboption
                    foreach( $option_name as $_language_id => $option ) {
                        $existed_l[] = $_language_id;
                        $option     = tep_db_prepare_input( $option );
                        $data_array = array(
                          'products_options_values_name' => $option,
                        );
                        if ($_def_l == $_language_id){
                            $o_data = ['products_options_values_name' => $option, 'products_options_values_id' => $products_options_id];
                            if ( isset($option_sort_order[$_language_id]) ) {
                              $o_data['sort_order'] = $option_sort_order[$_language_id];
                            }   
                        }
                        if ($same_all_languages) {
                            $data_array['products_options_values_name'] = $option_name[$_def_l];
                            $data_array['products_options_values_color'] = $option_color[$_def_l];
                            if (tep_not_null($option_image_loaded[$_def_l])) {
                                $data_array['products_options_values_image'] = 'subopt-' . $products_options_id . '-' . $option_image_loaded[$_def_l];
                            }
                        } else {
                            if ( isset($option_sort_order[$_language_id]) ) {
                                $data_array['products_options_values_sort_order'] = $option_sort_order[$_language_id];
                            }
                            if ( isset($option_color[$_language_id]) ) {
                                $data_array['products_options_values_color'] = $option_color[$_language_id];
                            }
                        }
                        if (tep_not_null($option_image_loaded[$_language_id])) {
                            $path = \Yii::getAlias('@webroot');
                            $path .= DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
                            $tmp_name = $path . $option_image_loaded[$_language_id];
                            $new_name = DIR_FS_CATALOG_IMAGES . 'subopt-' . $products_options_id . '-' . $option_image_loaded[$_language_id];
                            @copy($tmp_name, $new_name);
                            @unlink($tmp_name);
                            $data_array['products_options_values_image'] = 'subopt-' . $products_options_id . '-' . $option_image_loaded[$_language_id];
                        }
                        
                        $check = tep_db_query( 'select * from ' . TABLE_PRODUCTS_OPTIONS_VALUES . " where language_id = '" . (int)$_language_id . "' and products_options_values_id = '" . (int)$products_options_id . "'" );
                        if( tep_db_num_rows( $check ) ) {
                            tep_db_perform(TABLE_PRODUCTS_OPTIONS_VALUES, $data_array, 'update', "language_id = '" . (int)$_language_id . "' and products_options_values_id = '" . (int)$products_options_id . "'");
                        }else{
                            $data_array['language_id'] = (int)$_language_id;
                            $data_array['products_options_values_id'] = (int)$products_options_id;
                            tep_db_perform(TABLE_PRODUCTS_OPTIONS_VALUES, $data_array);
                        }
                    }
                    
                    $existed_l = array_unique($existed_l);
                    $all = [];
                    foreach($_l as $_v){
                      $all[] = $_v['id'];
                    }
                    $all = array_diff($all, $existed_l);
                    if (count($all) && count($o_data)>0){
                      foreach($all as $_language_id){
                        $data_array = array(
                          'products_options_values_name' => $o_data['products_options_values_name'],
                          'products_options_values_sort_order' => (int)$o_data['sort_order'],
                        );
                        $check = tep_db_query( 'select * from ' . TABLE_PRODUCTS_OPTIONS_VALUES . " where language_id = '" . (int)$_language_id . "' and products_options_values_id = '" . (int)$products_options_id . "'" );
                        if( tep_db_num_rows( $check ) ) {
                            tep_db_perform(TABLE_PRODUCTS_OPTIONS_VALUES, $data_array, 'update', "language_id = '" . (int)$_language_id . "' and products_options_values_id = '" . (int)$products_options_id . "'");
                        }else{
                            $data_array['language_id'] = (int)$_language_id;
                            $data_array['products_options_values_id'] = (int)$products_options_id;
                            tep_db_perform(TABLE_PRODUCTS_OPTIONS_VALUES, $data_array);
                        }
                      }
                    }                    

                    $message = "Suboption updated";
                }

            } else {
                if( $products_options_id === 0 ) {
                    // Insert

                    $max_values_id_query  = tep_db_query( "select max(products_options_id) + 1 as next_id from " . TABLE_PRODUCTS_OPTIONS );
                    $max_values_id_values = tep_db_fetch_array( $max_values_id_query );
                    $next_id              = (int) $max_values_id_values['next_id'];
                    if( !( $next_id > 0 ) ) $next_id = 1;

                    foreach( $option_name as $_language_id => $option ) {
                        $existed_l[] = $_language_id;
                        $data_array = array(
                            'products_options_id' => $next_id,
                            'products_options_name' => $option,
                            'type' => $type,
                            'is_virtual' => $is_virtual,
                            'display_filter' => $display_filter,
                            'display_search' => $display_search,
                            'language_id' => (int)$_language_id,
                        );
                        if ($_def_l == $_language_id) {
                            $o_data = [
                                'products_options_name' => $option, 
                                'products_options_id' => $next_id,
                            ];
                            if ( isset($option_sort_order[$_language_id]) ) {
                              $o_data['sort_order'] = $option_sort_order[$_language_id];
                            } 
                        }
                        if ($same_all_languages) {
                            $data_array['products_options_name'] = $option_name[$_def_l];
                            $data_array['products_options_color'] = $option_color[$_def_l];
                            if (tep_not_null($option_image_loaded[$_def_l])) {
                                $data_array['products_options_image'] = 'opt-' . $products_options_id . '-' . $option_image_loaded[$_def_l];
                            }
                        } else {
                            if ( isset($option_sort_order[$_language_id]) ) {
                                $data_array['products_options_sort_order'] = $option_sort_order[$_language_id];
                            }
                            if ( isset($option_color[$_language_id]) ) {
                                $data_array['products_options_color'] = $option_color[$_language_id];
                            }
                        }
                        if (tep_not_null($option_image_loaded[$_language_id])) {
                            $path = \Yii::getAlias('@webroot');
                            $path .= DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
                            $tmp_name = $path . $option_image_loaded[$_language_id];
                            $new_name = DIR_FS_CATALOG_IMAGES . 'opt-' . $products_options_id . '-' . $option_image_loaded[$_language_id];
                            @copy($tmp_name, $new_name);
                            @unlink($tmp_name);
                            $data_array['products_options_image'] = 'opt-' . $products_options_id . '-' . $option_image_loaded[$_language_id];
                        }
                        tep_db_perform(TABLE_PRODUCTS_OPTIONS, $data_array);
                        $message = "Option inserted";
                    }
                    $existed_l = array_unique($existed_l);
                    $all = [];
                    foreach($_l as $_v){
                      $all[] = $_v['id'];
                    }
                    
                    $all = array_diff($all, $existed_l);
                    if (count($all) && count($o_data)>0){
                      foreach($all as $_language_id){
                        tep_db_query( "insert into " . TABLE_PRODUCTS_OPTIONS . " set products_options_name = '" . tep_db_input( $o_data['products_options_name'] ) . "', language_id = '" . (int)$_language_id . "', products_options_sort_order ='" . tep_db_input( $o_data['sort_order'] ) . "', type ='" . tep_db_input( $type ) . "', is_virtual = '" . (int)$is_virtual . "', display_filter = '" . (int)$display_filter . "', display_search = '" . (int)$display_search . "', products_options_id = '" . (int)$o_data['products_options_id']. "'" );
                      }
                    }                        

                } else {
                    // Update
                    foreach( $option_name as $_language_id => $option ) {
                        $existed_l[] = $_language_id;
                        $data_array = array(
                            'products_options_name' => $option,
                            'type' => $type,
                            'is_virtual' => $is_virtual,
                            'display_filter' => $display_filter,
                            'display_search' => $display_search,
                        );
                        if ($_def_l == $_language_id){
                            $o_data = ['products_options_name' => $option, 'products_options_id' => $products_options_id];
                            if ( isset($option_sort_order[$_language_id]) ) {
                              $o_data['sort_order'] = $option_sort_order[$_language_id];
                            }   
                        }      
                        if ($same_all_languages) {
                            $data_array['products_options_name'] = $option_name[$_def_l];
                            $data_array['products_options_color'] = $option_color[$_def_l];
                            if (tep_not_null($option_image_loaded[$_def_l])) {
                                $data_array['products_options_image'] = 'opt-' . $products_options_id . '-' . $option_image_loaded[$_def_l];
                            }
                        } else {
                            if ( isset($option_sort_order[$_language_id]) ) {
                                $data_array['products_options_sort_order'] = $option_sort_order[$_language_id];
                            }
                            if ( isset($option_color[$_language_id]) ) {
                                $data_array['products_options_color'] = $option_color[$_language_id];
                            }
                        }
                        if (tep_not_null($option_image_loaded[$_language_id])) {
                            $path = \Yii::getAlias('@webroot');
                            $path .= DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
                            $tmp_name = $path . $option_image_loaded[$_language_id];
                            $new_name = DIR_FS_CATALOG_IMAGES . 'opt-' . $products_options_id . '-' . $option_image_loaded[$_language_id];
                            @copy($tmp_name, $new_name);
                            @unlink($tmp_name);
                            $data_array['products_options_image'] = 'opt-' . $products_options_id . '-' . $option_image_loaded[$_language_id];
                        }
                   
                        $check = tep_db_query( 'select * from ' . TABLE_PRODUCTS_OPTIONS . " where language_id = '" . (int)$_language_id . "' and products_options_id = '" . (int)$products_options_id . "'" );
                        if( tep_db_num_rows( $check ) ) {
                            tep_db_perform(TABLE_PRODUCTS_OPTIONS,$data_array,'update', "language_id = '" . (int)$_language_id . "' and products_options_id = '" . (int)$products_options_id . "'");
                        } else {
                            $data_array['language_id'] = (int)$_language_id;
                            $data_array['products_options_id'] = (int)$products_options_id;
                            tep_db_perform(TABLE_PRODUCTS_OPTIONS,$data_array);
                        }
                    }
                    
                    $existed_l = array_unique($existed_l);
                    $all = [];
                    foreach($_l as $_v){
                      $all[] = $_v['id'];
                    }
                    $all = array_diff($all, $existed_l);
                    if (count($all) && count($o_data)>0){
                      foreach($all as $_language_id){
                        $data_array = array(
                          'products_options_name' => $o_data['products_options_name'],
                          'products_options_sort_order' => (int)$o_data['sort_order'],
                        );
                        $check      = tep_db_query( 'select * from ' . TABLE_PRODUCTS_OPTIONS . " where language_id = '" . (int)$_language_id . "' and products_options_id = '" . $products_options_id . "'" );
                        if( tep_db_num_rows( $check ) ) {
                            tep_db_perform(TABLE_PRODUCTS_OPTIONS,$data_array,'update', "language_id = '" . (int)$_language_id . "' and products_options_id = '" . $products_options_id . "'");
                        }else{
                            $data_array['language_id'] = (int)$_language_id;
                            $data_array['products_options_id'] = (int)$products_options_id;
                            tep_db_perform(TABLE_PRODUCTS_OPTIONS,$data_array);
                        }
                      }
                    }                    

                    $message = "Option updated";
                }
            }

            if( $error === TRUE ) {
                $messageType = 'warning';
            }

            return $this->redirect(Yii::$app->urlManager->createUrl(['productsattributes/', 'type_code' => $type_code, 'global_id' => $global_id]));
            
        }

        function actionConfirmadeleteoption()
        {
            $languages_id = \Yii::$app->settings->get('languages_id');

            \common\helpers\Translation::init('admin/productsattributes');
            \common\helpers\Translation::init('admin/faqdesk');

            $this->layout = FALSE;

            $products_options_id = Yii::$app->request->post( 'products_options_id' );
            $cell_type           = Yii::$app->request->post( 'cell_type' );

            if( $cell_type == 'root' ) $cell_type = 'suboption';

            $products_num = $values_num = 0;

            $process_item_name = '';
            if ( $cell_type == 'suboption' ) {
                $checkData    = tep_db_fetch_array( tep_db_query( 'select count(*) as total from ' . TABLE_PRODUCTS_ATTRIBUTES . " where options_values_id = '" . $products_options_id . "' " ) );
                $products_num = $checkData['total'];

                $check_name      = tep_db_fetch_array(tep_db_query( 'select products_options_values_name from ' . TABLE_PRODUCTS_OPTIONS_VALUES . " where language_id = '" . (int)$languages_id . "' and products_options_values_id = '" . $products_options_id . "'" ));

                //$process_item_name =$check_name['products_options_values_name'];
                $TEXT_INFO_HEADING = TEXT_OPTION_VALUE_DELETE_HEADING . ' ' . $check_name['products_options_values_name'];
                $TEXT_INTRO = TEXT_OPTION_VALUE_DELETE_INTRO;
            }else {
                $products_num = tep_db_num_rows(tep_db_query('select count(*) as total from ' . TABLE_PRODUCTS_ATTRIBUTES . " where options_id = '" . $products_options_id . "' group by products_id"));

                $checkData = tep_db_fetch_array(tep_db_query('select count(*) as total from ' . TABLE_PRODUCTS_OPTIONS_VALUES_TO_PRODUCTS_OPTIONS . " where products_options_id = '" . $products_options_id . "'"));
                $values_num = $checkData['total'];

                $_query = "select * from " . TABLE_PRODUCTS_OPTIONS . " where language_id = '" . (int)$languages_id . "' and products_options_id = '$products_options_id' ";
                $Ovalue = tep_db_fetch_array(tep_db_query($_query));
                $process_item_name = $Ovalue['products_options_name'];
                $TEXT_INFO_HEADING = TEXT_INFO_HEADING_DELETE_ITEM;
                $TEXT_INTRO = TEXT_DELETE_ITEM_INTRO;
            }

            echo tep_draw_form( 'option_delete', 'catalog/product', \common\helpers\Output::get_all_get_params( array( 'action' ) ) . 'action=delete', 'post', 'id="option_delete" onSubmit="return deleteOption();"' );
            echo '<div class="or_box_head">' . $TEXT_INFO_HEADING . '</div>';
            echo '<div class="col_desc">' . $TEXT_INTRO . '<br><br><b>' . $process_item_name . '</div>';
            if( $values_num > 0 OR $products_num > 0 ) {
                if ( $cell_type == 'suboption' ) {
                    $notice     =sprintf( TEXT_OPTION_VALUE_DELETE_NOTICE, $products_num);
                }else {
                    ob_start();
                    printf(TEXT_OPTION_DELETE_NOTICE, $products_num, $values_num);
                    $notice = ob_get_clean();
                }
                echo '<div class="col_desc">' . $notice . '</div>';
            }
            ?>
            <div class="btn-toolbar btn-toolbar-order">
                <?php

                    echo '<input type="hidden" name="cell_type" value="' . $cell_type . '"  >';
                    echo '<button class="btn btn-delete btn-no-margin">' . IMAGE_DELETE . '</button>';
                    echo '<input type="button" class="btn btn-cancel" value="' . IMAGE_CANCEL . '" onClick="return resetStatement()">';

                    echo tep_draw_hidden_field( 'products_options_id', $products_options_id );
                ?>
            </div>
            </form>
        <?php
        }

        function actionOptiondelete()
        {
            $this->layout = FALSE;

            $products_options_id = Yii::$app->request->post( 'products_options_id' );
            $cell_type           = Yii::$app->request->post( 'cell_type' );

            //TODO rebuild inventory
            if ($cell_type == 'option') {
                $checkQuery = tep_db_query("select * from " . TABLE_PRODUCTS_ATTRIBUTES . " where options_id = '" . (int)$products_options_id . "'");
                while ($checkData = tep_db_fetch_array($checkQuery)) {
                  if (USE_MARKET_PRICES == 'True') {
                    tep_db_query("delete from " . TABLE_PRODUCTS_ATTRIBUTES_PRICES . " where products_attributes_id = '" . (int)$checkData['products_attributes_id'] . "'");
                  }
                }
                tep_db_query("delete from " . TABLE_PRODUCTS_ATTRIBUTES . " where options_id = '" . (int)$products_options_id . "'");

                $checkQuery = tep_db_query("select * from " . TABLE_PRODUCTS_OPTIONS_VALUES_TO_PRODUCTS_OPTIONS . " where products_options_id = '" . (int)$products_options_id . "'");
                while($checkData = tep_db_fetch_array($checkQuery)) {
                  tep_db_query("delete from " . TABLE_PRODUCTS_OPTIONS_VALUES . " where products_options_values_id = '" . $checkData['products_options_values_id'] . "'");
                }
                tep_db_query("delete from " . TABLE_PRODUCTS_OPTIONS_VALUES_TO_PRODUCTS_OPTIONS . " where products_options_id = '" . (int)$products_options_id . "'");
                tep_db_query("delete from " . TABLE_PRODUCTS_OPTIONS . " where products_options_id = '" . (int)$products_options_id . "'");
            } else {       // suboption
                $checkQuery = tep_db_query("select * from " . TABLE_PRODUCTS_ATTRIBUTES . " where options_values_id = '" . (int)$products_options_id . "'");
                while ($checkData = tep_db_fetch_array($checkQuery)) {
                  if (USE_MARKET_PRICES == 'True') {
                    tep_db_query("delete from " . TABLE_PRODUCTS_ATTRIBUTES_PRICES . " where products_attributes_id = '" . (int)$checkData['products_attributes_id'] . "'");
                  }
                }
                tep_db_query("delete from " . TABLE_PRODUCTS_ATTRIBUTES . " where options_values_id = '" . (int)$products_options_id . "'");
                tep_db_query("delete from " . TABLE_PRODUCTS_OPTIONS_VALUES_TO_PRODUCTS_OPTIONS . " where products_options_values_id = '" . (int)$products_options_id . "'");
                tep_db_query("delete from " . TABLE_PRODUCTS_OPTIONS_VALUES . " where products_options_values_id = '" . (int)$products_options_id . "'");
            }
        }

        public function actionSortOrder()
        {
            $languages_id = \Yii::$app->settings->get('languages_id');
            if ( isset($_POST['sort_suboption']) ) {
                $moved_id = (int)$_POST['sort_suboption'];
                $ref_array = (isset($_POST['suboption']) && is_array($_POST['suboption']))?array_map('intval',$_POST['suboption']):array();
                if ( $moved_id && in_array($moved_id,$ref_array) ) {
                  $option_id = 0;
                  $_get_option_id = tep_db_fetch_array(tep_db_query(
                    "SELECT products_options_id FROM ".TABLE_PRODUCTS_OPTIONS_VALUES_TO_PRODUCTS_OPTIONS." WHERE products_options_values_id='{$moved_id}' "
                  ));
                  $option_id = $_get_option_id['products_options_id'];
                  // {{ normalize
                  $order_counter = 0;
                  $order_list_r = tep_db_query(
                    "SELECT pv.products_options_values_id, pv.products_options_values_sort_order ".
                    "FROM ". TABLE_PRODUCTS_OPTIONS_VALUES ." pv ".
                    " INNER JOIN ".TABLE_PRODUCTS_OPTIONS_VALUES_TO_PRODUCTS_OPTIONS." po2v ON po2v.products_options_values_id=pv.products_options_values_id AND po2v.products_options_id='{$option_id}' ".
                    "WHERE pv.language_id='".$languages_id."' ".
                    "ORDER BY pv.products_options_values_sort_order, pv.products_options_values_name"
                  );
                  while( $order_list = tep_db_fetch_array($order_list_r) ){
                    $order_counter++;
                    tep_db_query("UPDATE ".TABLE_PRODUCTS_OPTIONS_VALUES." SET products_options_values_sort_order='{$order_counter}' WHERE products_options_values_id='{$order_list['products_options_values_id']}' ");
                  }
                  // }} normalize
                  $get_current_order_r = tep_db_query(
                    "SELECT products_options_values_id, products_options_values_sort_order ".
                    "FROM ".TABLE_PRODUCTS_OPTIONS_VALUES." ".
                    "WHERE products_options_values_id IN('".implode("','",$ref_array)."') AND language_id='".$languages_id."' ".
                    "ORDER BY products_options_values_sort_order"
                  );
                  $ref_ids = array();
                  $ref_so = array();
                  while($_current_order = tep_db_fetch_array($get_current_order_r)){
                    $ref_ids[] = (int)$_current_order['products_options_values_id'];
                    $ref_so[] = (int)$_current_order['products_options_values_sort_order'];
                  }

                  foreach( $ref_array as $_idx=>$id ) {
                    tep_db_query("UPDATE ".TABLE_PRODUCTS_OPTIONS_VALUES." SET products_options_values_sort_order='{$ref_so[$_idx]}' WHERE products_options_values_id='{$id}' ");
                  }
                }
            }elseif ( $_POST['sort_option'] ) {
                $moved_id = (int)$_POST['sort_option'];
                $ref_array = (isset($_POST['option']) && is_array($_POST['option']))?array_map('intval',$_POST['option']):array();
                if ( $moved_id && in_array($moved_id,$ref_array) ) {
                    // {{ normalize
                  $order_counter = 0;
                  $order_list_r = tep_db_query(
                    "SELECT products_options_id, products_options_sort_order ".
                    "FROM ". TABLE_PRODUCTS_OPTIONS ." ".
                    "WHERE language_id='".$languages_id."' ".
                    "ORDER BY products_options_sort_order, products_options_name"
                  );
                  while( $order_list = tep_db_fetch_array($order_list_r) ){
                    $order_counter++;
                    tep_db_query("UPDATE ".TABLE_PRODUCTS_OPTIONS." SET products_options_sort_order='{$order_counter}' WHERE products_options_id='{$order_list['products_options_id']}' ");
                  }
                  // }} normalize
                  $get_current_order_r = tep_db_query(
                    "SELECT products_options_id, products_options_sort_order ".
                    "FROM ".TABLE_PRODUCTS_OPTIONS." ".
                    "WHERE products_options_id IN('".implode("','",$ref_array)."') AND language_id='".$languages_id."' ".
                    "ORDER BY products_options_sort_order"
                  );
                  $ref_ids = array();
                  $ref_so = array();
                  while($_current_order = tep_db_fetch_array($get_current_order_r)){
                    $ref_ids[] = (int)$_current_order['products_options_id'];
                    $ref_so[] = (int)$_current_order['products_options_sort_order'];
                  }

                  foreach( $ref_array as $_idx=>$id ) {
                    tep_db_query("UPDATE ".TABLE_PRODUCTS_OPTIONS." SET products_options_sort_order='{$ref_so[$_idx]}' WHERE products_options_id='{$id}' ");
                  }

                }
            }
        }
        
}