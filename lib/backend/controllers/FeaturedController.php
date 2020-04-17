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
use common\models\FeaturedTypes;
use Yii;

class FeaturedController extends Sceleton {
    
    public $acl = ['BOX_HEADING_MARKETING_TOOLS', 'BOX_CATALOG_FEATURED'];

        public function actionIndex()
        {
            $this->selectedMenu        = array( 'marketing', 'featured' );
            $this->navigation[]        = array( 'link' => Yii::$app->urlManager->createUrl( 'featured/index' ), 'title' => HEADING_TITLE );
            $this->view->headingTitle  = HEADING_TITLE;
            $this->topButtons[] = '<a href="#" class="create_item" onclick="return editItem(0)">'.IMAGE_INSERT.'</a>';
            $this->view->featuredTable = array(
                array(
                    'title'         => TABLE_HEADING_PRODUCTS,
                    'not_important' => 0
                ),
                array(
                    'title'         => TABLE_HEADING_STATUS,
                    'not_important' => 1
                ),
            );

            $languages_id = \Yii::$app->settings->get('languages_id');
            $featuredTypesArr = [];
            $featuredTypesArr[0] = BOX_CATALOG_FEATURED;
            $featuredTypes = \common\models\FeaturedTypes::find()->where([
                    'language_id' => $languages_id
            ])->asArray()->all();
            foreach ($featuredTypes as $featuredType) {
                $featuredTypesArr[$featuredType['featured_type_id']] = $featuredType['featured_type_name'];
            }

            $featuredTypesDown = \yii\helpers\Html::dropDownList('featured_type_id', (int)$_GET['featured_type'], $featuredTypesArr, ['class'=>'form-control featured-type-id', 'onchange' => 'return resetStatement();']);

            return $this->render( 'index', ['featuredTypesDown' => $featuredTypesDown] );
        }

        public function actionList()
        {
            $languages_id = \Yii::$app->settings->get('languages_id');
            $draw   = Yii::$app->request->get( 'draw', 1 );
            $start  = Yii::$app->request->get( 'start', 0 );
            $length = Yii::$app->request->get( 'length', 10 );
            $filter = Yii::$app->request->get( 'filter', [] );
            $filterArr = [];
            parse_str($filter, $filterArr);
            $featuredTypeId = (int)$filterArr['featured_type_id'];

            $responseList = array();
            if( $length == -1 ) $length = 10000;
            $query_numrows = 0;

            if( isset( $_GET['search']['value'] ) && tep_not_null( $_GET['search']['value'] ) ) {
                $keywords         = tep_db_input( tep_db_prepare_input( $_GET['search']['value'] ) );
                $search_condition = "where p.products_id = pd.products_id and pd.language_id = '" . $languages_id . "' and p.products_id = s.products_id and (pd.products_name like '%" . $keywords . "%' or pd.products_internal_name like '%" . $keywords . "%') ";

            } else {
                $search_condition = " where p.products_id = pd.products_id and pd.language_id = '" . $languages_id . "' and p.products_id = s.products_id  ";
            }

            $search_condition .= " and s.featured_type_id = '" . $featuredTypeId . "'";

            if( isset( $_GET['order'][0]['column'] ) && $_GET['order'][0]['dir'] ) {
                switch( $_GET['order'][0]['column'] ) {
                    case 0:
                        $orderBy = "pd.products_name " . tep_db_input(tep_db_prepare_input( $_GET['order'][0]['dir'] ));
                        break;
                    default:
                        $orderBy = "pd.products_name";
                        break;
                }
            } else {
                $orderBy = "pd.products_name";
            }


            $featured_query_raw = "select p.products_id, ".ProductNameDecorator::instance()->listingQueryExpression('pd','')." AS products_name, s.featured_id, s.featured_date_added, s.featured_last_modified, s.expires_date, s.date_status_change, s.status, s.affiliate_id from " . TABLE_PRODUCTS . " p, " . TABLE_FEATURED . " s, " . TABLE_PRODUCTS_DESCRIPTION . " pd $search_condition  and pd.platform_id = '".intval(\common\classes\platform::defaultId())."' " . ( tep_session_is_registered( 'login_affiliate' ) ? " and s.affiliate_id = '" . $login_id . "'" : '' ) . " order by $orderBy";

            $current_page_number = ( $start / $length ) + 1;
            $_split              = new \splitPageResults( $current_page_number, $length, $featured_query_raw, $query_numrows, 'p.products_id' );
            $featured_query      = tep_db_query( $featured_query_raw );

            while( $featured = tep_db_fetch_array( $featured_query ) ) {
                
                $products_query = tep_db_query( "select products_image from " . TABLE_PRODUCTS . " where products_id = '" . (int) $featured['products_id'] . "'" );
                $products       = tep_db_fetch_array( $products_query );
                $sInfo_array    = array_merge( $featured, $products );
                $sInfo          = new \objectInfo( $sInfo_array );

                $image = \common\classes\Images::getImage($featured['products_id']);
                /*if( (int) $sInfo->status > 0 ) {
                    $status = '<span class="label label-success">Active</span>';
                } else {
                    $status = '<span class="label label-danger">Inactive</span>';
                }*/
                $status = '<input type="checkbox" value="'. $sInfo->featured_id . '" name="status" class="check_on_off" ' . ((int) $sInfo->status > 0 ? 'checked="checked"' : '') . '>';
                $responseList[] = array(
                    '<div class="">' .
                    '<div class="prod_name click_double" data-click-double="">'.
                    ($image ? '<span class="prodImgC">' . $image . '</span>' : '<span class="cubic"></span>') .
                    '<table class="wrapper"><tr><td><span class="prodNameC">' . $sInfo->products_name . '<input class="cell_identify" type="hidden" value="' . $sInfo->featured_id . '"></span></td></tr></table>'.
                    '</div>'.
                    '</div>',
                    $status,
                );
            }

            $response = array(
                'draw'            => $draw,
                'recordsTotal'    => $query_numrows,
                'recordsFiltered' => $query_numrows,
                'data'            => $responseList
            );
            echo json_encode( $response );
        }

        function actionItempreedit( $item_id = NULL )
        {
            $this->layout = FALSE;

            global $login_id;
            $languages_id = \Yii::$app->settings->get('languages_id');

            \common\helpers\Translation::init('admin/featured');

            if( $item_id === NULL )
                $item_id = (int) Yii::$app->request->post( 'item_id' );
                $featured_type_id = (int) Yii::$app->request->post( 'featured_type_id' );

            $product_query = tep_db_query( "select p.*, pd.*, s.* from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_FEATURED . " s where p.products_id = pd.products_id and pd.language_id = '" . $languages_id . "' and p.products_id = s.products_id and s.featured_id = '" . $item_id . "' and s.featured_type_id = '" . $featured_type_id . "' and pd.platform_id = '".intval(\common\classes\platform::defaultId())."' " . ( tep_session_is_registered( 'login_affiliate' ) ? " and s.affiliate_id = '" . $login_id . "'" : '' ) );
            $product       = tep_db_fetch_array( $product_query );

            $sInfo = new \objectInfo( $product );

            ?>
            <div class="row_or_img row_img_top"><?php echo \common\helpers\Image::info_image( $sInfo->products_image, $sInfo->products_name, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT ); ?></div>
            <div class="or_box_head or_box_head_no_margin"><?php echo $sInfo->products_name; ?></div>
            <div class="row_or_wrapp">
                <div class="row_or">
                    <div><?php echo TEXT_INFO_DATE_ADDED;?></div>
                    <div><?php echo \common\helpers\Date::date_format( $sInfo->featured_date_added, DATE_FORMAT_SHORT );?></div>
                </div>
                <div class="row_or">
                    <div><?php echo TEXT_INFO_LAST_MODIFIED;?></div>
                    <div><?php echo \common\helpers\Date::date_format( $sInfo->featured_last_modified, DATE_FORMAT_SHORT );?></div>
                </div>
                <div class="row_or">
                    <div><?php echo TEXT_INFO_EXPIRES_DATE;?></div>
                    <div><?php echo \common\helpers\Date::date_format( $sInfo->expires_date, DATE_FORMAT_SHORT );?></div>
                </div>
                <div class="row_or">
                    <div><?php echo TEXT_INFO_STATUS_CHANGE;?></div>
                    <div><?php echo \common\helpers\Date::date_format( $sInfo->date_status_change, DATE_FORMAT_SHORT );?></div>
                </div>
            </div>
            <div class="btn-toolbar btn-toolbar-order">
                <button class="btn btn-edit btn-no-margin" onclick="return editItem( <?php echo $item_id; ?>)"><?=IMAGE_EDIT?></button><button class="btn btn-delete" onclick="return deleteItemConfirm( <?php echo $item_id; ?>)"><?=IMAGE_DELETE?></button>
            </div>
        <?php
        }

        function actionItemedit()
        {
            global $login_id;
            $languages_id = \Yii::$app->settings->get('languages_id');

            \common\helpers\Translation::init('admin/featured');

            $item_id = (int) Yii::$app->request->post( 'item_id' );
            $featured_type_id = (int) Yii::$app->request->post( 'featured_type_id' );

            $expires_date = '';
            $status_checked_active = false;
            $products_name = '';

            if( $item_id === 0 ) {
                $header = IMAGE_INSERT;
            } else {
                $header = IMAGE_EDIT;

                $product_query = tep_db_query( "select pd.products_name, s.* from " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_FEATURED . " s where pd.language_id = '" . $languages_id . "' and pd.products_id = s.products_id and s.featured_id = '" . $item_id . "' and s.featured_type_id = '" . $featured_type_id . "' and pd.platform_id = '".intval(\common\classes\platform::defaultId())."' " . ( tep_session_is_registered( 'login_affiliate' ) ? " and s.affiliate_id = '" . $login_id . "'" : '' ) );
                $product = tep_db_fetch_array( $product_query );

                if( (int) $product['status'] > 0 ) {
                    $status_checked_active = true;
                }
                $products_name = $product['products_name'];

                $expires_date = \common\helpers\Date::date_short($product['expires_date']);
            }

            $this->layout = false;
            return $this->render('edit.tpl', [
                    'header' => $header,
                    'item_id' => $item_id,
                    'expires_date' => $expires_date,
                    'status_checked_active' => $status_checked_active,
                    'product' => $products_name,
            ]);
        }

        function actionSubmit()
        {
            global $login_id;

            \common\helpers\Translation::init('admin/featured');

            $item_id      = (int) Yii::$app->request->post( 'item_id' );
            $featured_type_id      = (int) Yii::$app->request->post( 'featured_type_id' );
            $products_id  = tep_db_prepare_input( Yii::$app->request->post( 'products_id', FALSE ) );
            $status       = tep_db_prepare_input( Yii::$app->request->post( 'status', 0 ) );
            $expires_date = Yii::$app->request->post( 'expires_date' );

            if ($expires_date) {
                $expires_date = \common\helpers\Date::prepareInputDate($expires_date);
            }

            $this->layout  = FALSE;
            $action_update = FALSE;

            $messageType = 'success';

            if( $item_id > 0 ) {
                // Update
                $action_update = TRUE;
                $featured_id = $item_id;

                tep_db_query( "update " . TABLE_FEATURED . " set status = '$status' , featured_last_modified = now(), expires_date = '" . $expires_date . "' where featured_id = '" . $featured_id . "' and featured_type_id = '" . $featured_type_id . "'" );


                $message = "Item updated";
            } else {
                // Insert
                $message = "Item inserted";

                tep_db_query( "insert into " . TABLE_FEATURED . " (products_id, featured_date_added, expires_date, status, affiliate_id, featured_type_id) values ('" . $products_id . "', now(), '" . $expires_date . "', '1', " . ( tep_session_is_registered( 'login_affiliate' ) ? $login_id : '0' ) . ", '" . $featured_type_id . "')" );

            }
            ?>
            <div class="popup-box-wrap pop-mess">
                <div class="around-pop-up"></div>
                <div class="popup-box">
                    <div class="pop-up-close pop-up-close-alert"></div>
                    <div class="pop-up-content">
                        <div class="popup-heading"><?php echo TEXT_NOTIFIC; ?></div>
                        <div class="popup-content pop-mess-cont pop-mess-cont-<?php echo $messageType; ?>">
                            <?php echo $message; ?>
                        </div> 
                    </div>   
                    <div class="noti-btn">
                    <div></div>
                    <div><span class="btn btn-primary"><?php echo TEXT_BTN_OK;?></span></div>
                </div>
                </div>
                <script>
                $('body').scrollTop(0);
                $('.pop-mess .pop-up-close-alert, .noti-btn .btn').click(function(){
                    $(this).parents('.pop-mess').remove();
                });
            </script>
            </div>
            <?php

            if( $action_update )
                $this->actionItemPreEdit( $item_id );
        }

        function actionConfirmitemdelete()
        {
            $languages_id = \Yii::$app->settings->get('languages_id');

            \common\helpers\Translation::init('admin/featured');
            \common\helpers\Translation::init('admin/faqdesk');

            $this->layout = FALSE;

            $item_id = (int) Yii::$app->request->post( 'item_id' );
            $featured_type_id = (int) Yii::$app->request->post( 'featured_type_id' );

            $message   = $name = $title = '';
            $parent_id = 0;

            $product_query = tep_db_query( "select p.*, pd.*, s.* from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_FEATURED . " s where p.products_id = pd.products_id and pd.language_id = '" . $languages_id . "' and p.products_id = s.products_id and s.featured_id = '" . $item_id . "' and s.featured_type_id = '" . $featured_type_id . "' and pd.platform_id = '".intval(\common\classes\platform::defaultId())."' " . ( tep_session_is_registered( 'login_affiliate' ) ? " and s.affiliate_id = '" . $login_id . "'" : '' ) );
            $product       = tep_db_fetch_array( $product_query );

            $sInfo = new \objectInfo( $product );

            echo tep_draw_form( 'item_delete', 'featured', \common\helpers\Output::get_all_get_params( array( 'action' ) ) . 'action=update', 'post', 'id="item_delete" onSubmit="return deleteItem();"' );
            echo '<div class="or_box_head">' . TEXT_INFO_HEADING_DELETE_FEATURED . '</div>';
            echo '<div class="col_desc">' . TEXT_INFO_DELETE_INTRO . '</div>';
            echo '<div class="col_desc"><strong>' . $sInfo->products_name . '</strong></div>';
            ?>
            <div class="btn-toolbar btn-toolbar-order">
                <?php
                    echo '<button class="btn btn-delete btn-no-margin">' . IMAGE_DELETE . '</button>';
                    echo '<input type="button" class="btn btn-cancel" value="' . IMAGE_CANCEL . '" onClick="return resetStatement()">';
                    echo tep_draw_hidden_field( 'item_id', $item_id );
                    echo tep_draw_hidden_field( 'featured_type_id', $featured_type_id );
                ?>
            </div>
            </form>
        <?php
        }

        function actionItemdelete()
        {
            $this->layout = FALSE;

            $featured_id = (int) Yii::$app->request->post( 'item_id' );

            $messageType = 'success';
            $message     = TEXT_INFO_DELETED;

            tep_db_query( "delete from " . TABLE_FEATURED . " where featured_id = '" . tep_db_input( $featured_id ) . "'" );

            ?>
            <div class="popup-box-wrap pop-mess">
                <div class="around-pop-up"></div>
                <div class="popup-box">
                    <div class="pop-up-close pop-up-close-alert"></div>
                    <div class="pop-up-content">
                        <div class="popup-heading"><?php echo TEXT_NOTIFIC; ?></div>
                        <div class="popup-content pop-mess-cont pop-mess-cont-<?php echo $messageType; ?>">
                            <?php echo $message; ?>
                        </div>   
                    </div>   
                    <div class="noti-btn">
                    <div></div>
                    <div><span class="btn btn-primary"><?php echo TEXT_BTN_OK;?></span></div>
                </div>
                </div>  
                <script>
                $('body').scrollTop(0);
                $('.pop-mess .pop-up-close-alert, .noti-btn .btn').click(function(){
                    $(this).parents('.pop-mess').remove();
                });
            </script>
            </div>
            

            <p class="btn-toolbar">
                <?php
                    echo '<input type="button" class="btn btn-primary" value="' . IMAGE_BACK . '" onClick="return resetStatement()">';
                ?>
            </p>
        <?php
        }

        function tep_set_featured_status( $featured_id, $featured_type_id, $status )
        {
            if( $status == '1' ) {
                return tep_db_query( "update " . TABLE_FEATURED . " set status = '1', expires_date = NULL, date_status_change = NULL where featured_id = '" . (int)$featured_id . "' and featured_type_id = '" . (int)$featured_type_id . "'" );
            } elseif( $status == '0' ) {
                return tep_db_query( "update " . TABLE_FEATURED . " set status = '0', date_status_change = now() where featured_id = '" . (int)$featured_id . "' and featured_type_id = '" . (int)$featured_type_id . "'" );
            } else {
                return -1;
            }
        }
        
        public function actionSwitchStatus()
        {
            $id = Yii::$app->request->post('id');
            $status = Yii::$app->request->post('status');
            $featured_type_id = Yii::$app->request->post('featured_type_id');
            $this->tep_set_featured_status($id, $featured_type_id, ($status == 'true' ? 1 : 0));
        }      
        
    }