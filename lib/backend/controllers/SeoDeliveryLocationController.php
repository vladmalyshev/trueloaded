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

use common\classes\Images;
use common\classes\platform;
use common\classes\platform_config;
use common\helpers\SeoDeliveryLocation;
use common\helpers\Translation;
use Yii;
use yii\helpers\Html;

class SeoDeliveryLocationController extends Sceleton  {

    public $acl = ['BOX_HEADING_SEO', 'BOX_HEADING_SEO_DELIVERY_LOCATION'];

    public function actionIndex() {

        $this->selectedMenu = array('seo_cms', 'seo-delivery-location');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('seo-delivery-location/index'), 'title' => HEADING_TITLE);

        $row = intval(Yii::$app->request->get('row',0));
        $platform_id = Yii::$app->request->get('platform_id', 0);
        if (!$platform_id) $platform_id = platform::defaultId();
        $item_id = Yii::$app->request->get('item_id', 0);
        $parent_id = Yii::$app->request->get('parent_id', 0);
        if ( $item_id ) {
            $itemInfo = SeoDeliveryLocation::getItem($item_id);
            if ($itemInfo) {
                $platform_id = $itemInfo['platform_id'];
                $parent_id = $itemInfo['parent_id'];
            } else {
                $item_id = 0;
                $row = 0;
            }
        }
        $this->topButtons[] = '<a href="javascript:;" class="create_item js_create_item"><i class="icon-file-text"></i>' . TEXT_CREATE_NEW_LOCATION . '</a>';
        $this->topButtons[] = '<a href="javascript:;"  class="create_item js_create_batch"><i class="icon-file-text"></i>' . TEXT_CREATE_NEW_LOCATION_BATCH . '</a>';

        $this->view->headingTitle = HEADING_TITLE;

        $platforms = platform::getList(false);

        $this->view->RedirectsTable = array(
            array(
                'title' => TABLE_HEADING_DELIVERY_LOCATION,
                'not_important' => 0,
            ),
            /*array(
                'title' => TABLE_HEADING_NEW_URL,
                'not_important' => 0,
            ),*/
        );
        return $this->render('index', [
            'platforms' => $platforms,
            'first_platform_id' => platform::firstId(),
            'default_platform_id' => platform::defaultId(),
            'isMultiPlatforms' => platform::isMulti(),
            'platform_id' => $platform_id,
            'item_id' => $item_id,
            'parent_id' => $parent_id,
            'row' => $row,
        ]);
    }

    public function actionList() {

        $draw = Yii::$app->request->get('draw', 1);
        $start = Yii::$app->request->get('start', 0);
        $length = Yii::$app->request->get('length', 10);
        $current_page_number = ($start / $length) + 1;
        $platform_id = Yii::$app->request->get('platform_id');
        $parent_id = Yii::$app->request->get('parent_id');

        $search_words = '';
        if (isset($_GET['search']) && tep_not_null($_GET['search'])) {
            $search_words = tep_db_prepare_input($_GET['search']['value']);
        }

        $formFilter = Yii::$app->request->get('filter');
        parse_str($formFilter, $output);
        if ( !isset($output['parent_id']) ) {
            $output['parent_id'] = $parent_id;
        }

        if ($length == -1)
            $length = 10000;

        $responseList = SeoDeliveryLocation::getAllItems(
            $platform_id,
            [
                'total' => true,
                'limit' => $start.','.$length,
                'parent_id' => ($output['parent_id']?(int)$output['parent_id']:0),
                'search' => $search_words,
            ],
            $total
        );

        $list_bread_crumb = [
            '<a href="'.Yii::$app->urlManager->createUrl(['seo-delivery-location/','platform_id'=>$platform_id, 'parent_id'=> 0]).'" class="js-list-load" data-param-platform_id="'.$platform_id.'" data-param-parent_id="0">'.TEXT_TOP.'</a>',
        ];
        $pathArray = SeoDeliveryLocation::getParents($platform_id,($output['parent_id']?(int)$output['parent_id']:0));
        foreach ($pathArray as $pathItem) {
            //$list_bread_crumb[] = $pathItem['location_name'];
            $list_bread_crumb[] = '<a href="'.Yii::$app->urlManager->createUrl(['seo-delivery-location/','platform_id'=>$platform_id, 'parent_id'=> $pathItem['id']]).'" class="js-list-load" data-param-platform_id="'.$platform_id.'" data-param-parent_id="'.$pathItem['id'].'">'.$pathItem['location_name'].'</a>';
        }

        $response = array(
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $responseList,
            'breadcrumb' => implode(' &gt; ',$list_bread_crumb),
        );
        echo json_encode($response);
    }

    public function actionItempreedit() {

        $platform_id = Yii::$app->request->post('platform_id');
        $item_id = (int) Yii::$app->request->post('item_id', 0);

        $cInfo = SeoDeliveryLocation::getItem($item_id);

        return $this->renderAjax('view', [
            'cInfo' => $cInfo,
            'editLink' => Yii::$app->urlManager->createUrl(['seo-delivery-location/location-edit','item_id'=>$item_id,'platform_id'=>$platform_id]),
            ]);
    }

    public function actionDelete(){
        $this->layout = false;

        $item_id = Yii::$app->request->post('item_id', 0);
        if ($item_id){
            SeoDeliveryLocation::deleteItem($item_id);
        }
        echo '1';
        exit();
    }

    public function actionEdit() {
        $this->layout = false;
        $item_id = (int) Yii::$app->request->get('item_id', 0);
        $platform_id = (int) Yii::$app->request->get('platform_id', 0);

        $list = SeoDeliveryLocation::getItem($item_id, $platform_id);
        if ( !is_array($list) ) {
            $list = [
                'platform_id' => $platform_id,
            ];
        }
        $cInfo = new \objectInfo($list);

        return $this->render('edit.tpl', [
            'cInfo' => $cInfo,
        ]);
    }

    public function actionLocationEdit() {
        $this->selectedMenu = array('seo_cms', 'seo-delivery-location');

        $item_id = (int) Yii::$app->request->get('item_id', 0);
        $parent_id = intval(Yii::$app->request->get('parent_id',0));
        $platform_id = (int) Yii::$app->request->get('platform_id', 0);

        Translation::init('admin/seo-delivery-location');
        Translation::init('admin/categories');

        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('seo-delivery-location/index'), 'title' => HEADING_TITLE);
        $this->view->headingTitle = HEADING_TITLE;

        $list = SeoDeliveryLocation::getItem($item_id, $platform_id);
        if ( is_array($list) ) {
            $parent_id = $list['parent_id'];
        }
        $edit_bread_crumb = [
            TEXT_TOP,
        ];
        $pathArray = SeoDeliveryLocation::getParents($platform_id, $parent_id);
        foreach ($pathArray as $pathItem) {
            $edit_bread_crumb[] = $pathItem['location_name'];
        }


        $languages = \common\helpers\Language::get_languages();
        for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
            $languages[$i]['logo'] = $languages[$i]['image'];
        }

        $list = SeoDeliveryLocation::getItem($item_id, $platform_id);
        if ( !is_array($list) ) {
            $list = [
                'platform_id' => $platform_id,
                'text' => [],
                'product_set_rule' => ($parent_id==0?-1:1),
                'create' => true,
            ];
            $item_product_set_id = SeoDeliveryLocation::getProductSetGroupId($parent_id);
            $editBreadCrumbInfo = 'Create location in : '.implode('&gt;',$edit_bread_crumb);
        }else{
            $editBreadCrumbInfo = 'Edit location in : '.implode('&gt;',$edit_bread_crumb);
            $parent_id = $list['parent_id'];
            if ( $list['parent_id']==0 ) {
                $list['product_set_rule'] = -1;
            }
            $texts_r = tep_db_query("SELECT * FROM ".TABLE_SEO_DELIVERY_LOCATION_TEXT." WHERE id='".(int)$item_id."'");
            if ( tep_db_num_rows($texts_r) ) {
                while ($text = tep_db_fetch_array($texts_r)) {
                    $list['text'][ $text['language_id'] ] = $text;
                }
            }
            $item_product_set_id = SeoDeliveryLocation::getProductSetGroupId($item_id);
        }

        $catalogBaseHref = Yii::$app->get('platform')->getConfig($platform_id)->getCatalogBaseUrl();

        $seoDeliveryLocationCrosspages = \common\models\SeoDeliveryLocationCrosspages::find()->where([
            'id' => $item_id
        ])->all();
        $pages = [];
        foreach ($seoDeliveryLocationCrosspages as $page) {
            $pages[] = $page['crosspage_id'];
        }
        $seoDeliveryLocation = \common\helpers\SeoDeliveryLocation::getTree($platform_id);
        $crossPages = [];
        foreach ($seoDeliveryLocation as $location) {
            if (in_array($location['id'], $pages)) {
                $crossPages[] = [
                    'id' => $location['id'],
                    'text' => $location['path'],
                ];
            }
        }

        $seoDeliveryLocationCategories = \common\models\SeoDeliveryLocationCategories::find()->where([
            'id' => $item_id
        ])->asArray()->all();
        $categories = [];
        foreach ($seoDeliveryLocationCategories as $page) {
            $categories[] = $page['categories_id'];
        }
        $seoDeliveryLocation = \common\helpers\Categories::get_category_tree('0', '', '', '', false, true);
        $addedCategories = [];
        foreach ($seoDeliveryLocation as $category) {
            if (in_array($category['id'], $categories)) {
                $addedCategories[] = $category;
            }
        }

        return $this->render('location-edit.tpl', [
            'platform_id' => $platform_id,
            'parent_id' => $parent_id,
            'location_data' => $list,
            'languages' => $languages,
            'default_language' => DEFAULT_LANGUAGE,//\common\classes\language::defaultId(),
            'href_back' => Yii::$app->urlManager->createUrl(['seo-delivery-location/index','platform_id'=>$platform_id, 'parent_id'=>$parent_id]),
            'set_products' => SeoDeliveryLocation::loadProductSet( $item_product_set_id ),
            'catalogBaseHref' => $catalogBaseHref,
            'editBreadCrumbInfo' => $editBreadCrumbInfo,
            'crossPages' => $crossPages,
            'addedCategories' => $addedCategories,
        ]);
    }

    public function actionLocationSave()
    {
        Translation::init('admin/seo-delivery-location');

        $result_message = TEXT_MESSEAGE_SUCCESS;
        $item_id = intval(Yii::$app->request->post('item_id',0));
        $parent_id = intval(Yii::$app->request->post('parent_id',0));
        $platform_id = Yii::$app->request->get('platform_id');
        $prev_data = SeoDeliveryLocation::getItem($item_id);

        $show_product_group_id = intval(Yii::$app->request->post('show_product_group_id',0));
        $old_seo_page_name = tep_db_prepare_input(Yii::$app->request->post('old_seo_page_name',''));

        $show_on_index = intval(Yii::$app->request->post('show_on_index',0));
        $featured = intval(Yii::$app->request->post('featured',0));
        $image_listing = tep_db_prepare_input(Yii::$app->request->post('image_listing',''));
        $image_headline = tep_db_prepare_input(Yii::$app->request->post('image_headline',''));
        if ( !empty($image_listing) ) {
            $image_listing = str_replace(SeoDeliveryLocation::imagesLocation(),'',$image_listing);
        }
        if ( !empty($image_headline) ) {
            $image_headline = str_replace(SeoDeliveryLocation::imagesLocation(),'',$image_headline);
        }
        $image_listing_loaded = tep_db_prepare_input(Yii::$app->request->post('image_listing_loaded',''));
        $image_headline_loaded = tep_db_prepare_input(Yii::$app->request->post('image_headline_loaded',''));
        $image_listing_delete = tep_db_prepare_input(Yii::$app->request->post('image_listing_delete'));
        $image_headline_delete = tep_db_prepare_input(Yii::$app->request->post('image_headline_delete'));

        $product_set_rule = intval(Yii::$app->request->post('product_set_rule',0));
        $location_name_array = tep_db_prepare_input(Yii::$app->request->post('location_name',[]));
        $location_description_short_array = tep_db_prepare_input(Yii::$app->request->post('location_description_short',[]));
        $location_description_array = tep_db_prepare_input(Yii::$app->request->post('location_description',[]));
        $location_description_long_array = tep_db_prepare_input(Yii::$app->request->post('location_description_long',[]));
        $meta_title_array = tep_db_prepare_input(Yii::$app->request->post('meta_title',[]));
        $overwrite_head_title_tag_array = tep_db_prepare_input(Yii::$app->request->post('overwrite_head_title_tag',[]));
        $meta_keyword_array = tep_db_prepare_input(Yii::$app->request->post('meta_keyword',[]));
        $meta_description_array = tep_db_prepare_input(Yii::$app->request->post('meta_description',[]));
        $overwrite_head_desc_tag_array = tep_db_prepare_input(Yii::$app->request->post('overwrite_head_desc_tag',[]));
        $seo_page_name_array = tep_db_prepare_input(Yii::$app->request->post('seo_page_name',[]));
        $date_added = tep_db_prepare_input(Yii::$app->request->post('date_added'));

        $status = false;
        if ( Yii::$app->request->post('status_present','0') ) {
            $status = tep_db_prepare_input(Yii::$app->request->post('status', '0'));
        }
        if ( $image_listing_delete && is_array($prev_data) ) {
            $image_listing = '';
            if (!empty($prev_data['image_listing']) && is_file(Images::getFSCatalogImagesPath().SeoDeliveryLocation::imagesLocation().$prev_data['image_listing'])) {
                @unlink(Images::getFSCatalogImagesPath().SeoDeliveryLocation::imagesLocation().$prev_data['image_listing']);
            }
        }
        if ( $image_headline_delete && is_array($prev_data) ) {
            $image_headline = '';
            if (!empty($prev_data['image_headline']) && is_file(Images::getFSCatalogImagesPath().SeoDeliveryLocation::imagesLocation().$prev_data['image_headline'])) {
                @unlink(Images::getFSCatalogImagesPath().SeoDeliveryLocation::imagesLocation().$prev_data['image_headline']);
            }
        }

        $item_data = [
            //'id' => '',
            //'parent_id' => '',
            //'platform_id' => '',
            'show_on_index' => $show_on_index,
            'featured' => $featured,
            'image_listing' => $image_listing,
            'image_headline' => $image_headline,
            'product_set_rule' => $product_set_rule,
            'show_product_group_id' => $show_product_group_id,
            'old_seo_page_name' => $old_seo_page_name,
        ];
        if ($image_listing_loaded != '') {
            $val = \backend\design\Uploads::move($image_listing_loaded, 'images/'.SeoDeliveryLocation::imagesLocation(), false);
            $item_data['image_listing'] = $val;
        }
        if ($image_headline_loaded != '') {
            $val = \backend\design\Uploads::move($image_headline_loaded, 'images/'.SeoDeliveryLocation::imagesLocation(), false);
            $item_data['image_headline'] = $val;
        }

        if ( $status!==false ) {
            $item_data['status'] = $status?1:0;
        }
        if ( $item_id ) {
            $checkId = tep_db_fetch_array(tep_db_query(
                "SELECT COUNT(*) AS c ".
                "FROM ".TABLE_SEO_DELIVERY_LOCATION." ".
                "WHERE id='{$item_id}'"
            ));
            if ( $checkId['c']==0 ) $item_id = 0;
        }
        if ( $item_id ) {
            $item_data['date_added'] = \common\helpers\Date::prepareInputDate($date_added, true);
            $item_data['date_modified'] = 'now()';
            tep_db_perform(TABLE_SEO_DELIVERY_LOCATION, $item_data, 'update', "id='".$item_id."'");
        }else{
            $item_data['parent_id'] = $parent_id;
            $item_data['platform_id'] = $platform_id;
            $item_data['date_added'] = $date_added ? \common\helpers\Date::prepareInputDate($date_added, true) : 'now()';
            tep_db_perform(TABLE_SEO_DELIVERY_LOCATION, $item_data);
            $item_id = tep_db_insert_id();
            $result_message = TEXT_MESSEAGE_SUCCESS_ADDED;
        }

        foreach(\common\helpers\Language::get_languages() as $language){
            $language_id = $language['id'];

            $seo_page_name = $seo_page_name_array[$language_id];
            if ( empty($seo_page_name) ) {
                $seo_page_name = \common\helpers\Seo::makeSlug($location_name_array[$language_id]);
            }
            if ( empty($seo_page_name) ) {
                $seo_page_name = \common\helpers\Seo::makeSlug($location_name_array[\common\helpers\Language::get_default_language_id()]);
            }
            $item_text_data = [
                //'id' => '',
                'language_id' => $language_id,
                'location_name' => $location_name_array[$language_id],
                'location_description_short' => $location_description_short_array[$language_id],
                'location_description' => $location_description_array[$language_id],
                'location_description_long' => $location_description_long_array[$language_id],
                'meta_title' => $meta_title_array[$language_id],
                'overwrite_head_title_tag' => (isset($overwrite_head_title_tag_array[$language_id]) && $overwrite_head_title_tag_array[$language_id])?1:0,
                'meta_keyword' => $meta_keyword_array[$language_id],
                'meta_description' => $meta_description_array[$language_id],
                'overwrite_head_desc_tag' => (isset($overwrite_head_desc_tag_array[$language_id]) && $overwrite_head_desc_tag_array[$language_id])?1:0,
                'seo_page_name' => $seo_page_name,
            ];

            $checkText = tep_db_fetch_array(tep_db_query(
                "SELECT COUNT(*) AS c ".
                "FROM ".TABLE_SEO_DELIVERY_LOCATION_TEXT." ".
                "WHERE id='{$item_id}' AND language_id='{$language_id}'"
            ));
            if ( $checkText['c']==0 ) {
                $item_text_data['id'] = $item_id;
                tep_db_perform(TABLE_SEO_DELIVERY_LOCATION_TEXT, $item_text_data);
            }else{
                tep_db_perform(TABLE_SEO_DELIVERY_LOCATION_TEXT, $item_text_data, 'update', "id='{$item_id}' AND language_id='{$language_id}'");
            }
        }

        // products
        $set_products_array = tep_db_prepare_input(Yii::$app->request->post('set_products_id',[]));
        $set_products_sort_order_raw = tep_db_prepare_input(Yii::$app->request->post('products_set_sort_order',''));
        $set_sort_order = [];
        if ( !empty($set_products_sort_order_raw) ) {
            parse_str($set_products_sort_order_raw, $set_sort_order);
            $set_sort_order = isset($set_sort_order['set-product'])?array_flip($set_sort_order['set-product']):[];
        }

        if ( count($set_products_array)>0 && $product_set_rule!=1 ) {
            tep_db_query(
                "DELETE FROM ".TABLE_SEO_DELIVERY_LOCATION_PRODUCTS." ".
                "WHERE delivery_product_group_id='".$item_id."' ".
                " AND products_id NOT IN('".implode("','",array_map('intval',$set_products_array))."')"
            );

            foreach ($set_products_array as $idx => $set_product_id) {
                $sort_order = ((count($set_sort_order) > 0 ? $set_sort_order[$set_product_id] : $idx) + 1);
                tep_db_query(
                    "INSERT INTO " . TABLE_SEO_DELIVERY_LOCATION_PRODUCTS . " (delivery_product_group_id, products_id, sort_order) " .
                    "VALUES( '{$item_id}', '" . (int)$set_product_id . "', '" . $sort_order . "' ) " .
                    "ON DUPLICATE KEY UPDATE sort_order='" . $sort_order . "'"
                );
            }
        }else{
            tep_db_query("DELETE FROM ".TABLE_SEO_DELIVERY_LOCATION_PRODUCTS." WHERE delivery_product_group_id='".$item_id."' ");
        }

        $selectedPages = Yii::$app->request->post('selectedPages',[]);

        \common\models\SeoDeliveryLocationCrosspages::deleteAll(['id' => $item_id]);
        if (is_array($selectedPages) && count($selectedPages) > 0){
            foreach ($selectedPages as $page) {
                $crosspage = new \common\models\SeoDeliveryLocationCrosspages();
                $crosspage->id = (int)$item_id;
                $crosspage->crosspage_id = (int)$page;
                $crosspage->save();
            }
        }

        $selectedCategories = Yii::$app->request->post('selectedCategories',[]);

        \common\models\SeoDeliveryLocationCategories::deleteAll(['id' => $item_id]);
        if (is_array($selectedCategories) && count($selectedCategories) > 0){
            foreach ($selectedCategories as $page) {
                $crosspage = new \common\models\SeoDeliveryLocationCategories();
                $crosspage->id = (int)$item_id;
                $crosspage->categories_id = (int)$page;
                $crosspage->save();
            }
        }

        $this->layout = false;
        return '<div class="popup-box-wrap pop-mess">
    <div class="around-pop-up"></div>
    <div class="popup-box">
        <div class="pop-up-close pop-up-close-alert"></div>
        <div class="pop-up-content">
            <div class="popup-content pop-mess-cont pop-mess-cont-success">
                '.$result_message.'
            </div>
        </div>
            <div class="noti-btn">
                    <div></div>
                    <div><span class="btn btn-primary">' . TEXT_BTN_OK . '</span></div>
                </div>
    </div>
<script>
    //$(\'body\').scrollTop(0);
    $(\'#frmDeliveryLocation [name="item_id"]\').val(\''.(int)$item_id.'\');
    $(\'.popup-box-wrap.pop-mess\').css(\'top\',(window.scrollY+200)+\'px\');
    $(\'.pop-mess .pop-up-close-alert, .noti-btn .btn\').click(function () {
        $(this).parents(\'.pop-mess\').remove();
    });
</script>
</div>
';
        //$this->redirect(Yii::$app->urlManager->createUrl(['seo-delivery-location/index','platform_id'=>$platform_id, 'parent_id' => $parent_id]));
    }

    public function actionBatchCreateLocation()
    {
        Translation::init('admin/seo-delivery-location');

        $platform_id = intval(Yii::$app->request->get('platform_id',0));
        $parent_id = intval(Yii::$app->request->get('parent_id',0));
        if ( Yii::$app->request->isPost ) {
            $location_string = tep_db_prepare_input(Yii::$app->request->post('location_string', ''));
            $locations = preg_split('/[,|;|\n|\r]/', trim($location_string),-1, PREG_SPLIT_NO_EMPTY);
            foreach( $locations as $location ){
                $location = trim($location);
                if ( empty($location) ) continue;
                $checkLevelId = SeoDeliveryLocation::getId($platform_id, $location, $parent_id);
                if ( !$checkLevelId ) {
                    $seo_name = \common\helpers\Seo::makeSlug($location);
                    $counter = 1;
                    do {
                        if (false === SeoDeliveryLocation::getIdBySeoName($platform_id, $seo_name)) {
                            break;
                        }else{
                            $seo_name = \common\helpers\Seo::makeSlug($location).'-'.$counter;
                            $counter++;
                        }
                    }while(true);
                }else{
                    continue;
                }
                tep_db_perform(TABLE_SEO_DELIVERY_LOCATION,[
                    'parent_id' => $parent_id,
                    'platform_id' => $platform_id,
                    'date_added' => 'now()',
                ]);
                $location_id = tep_db_insert_id();
                if ($location_id) {
                    tep_db_query(
                        "INSERT INTO " . TABLE_SEO_DELIVERY_LOCATION_TEXT . " (id, language_id, location_name, seo_page_name) " .
                        "SELECT '{$location_id}', languages_id, '".tep_db_input($location)."', '".tep_db_input($seo_name)."' FROM ".TABLE_LANGUAGES
                    );
                }
            }
            return '1';
        }

        $this->layout = 'popup.tpl';

        return $this->render('batch-create-location.tpl',[
            'page_name' => TEXT_CREATE_NEW_LOCATION_BATCH,
            'platform_id' => $platform_id,
            'parent_id' => $parent_id,
            'action' => Yii::$app->urlManager->createUrl(['seo-delivery-location/batch-create-location','platform_id' => $platform_id, 'parent_id' => $parent_id]),
        ]);
    }

    public function actionSetProductSearch()
    {
        $this->layout = false;
        $languages_id = \Yii::$app->settings->get('languages_id');

        $q = Yii::$app->request->get('q');
        $platform_id = (int) Yii::$app->request->get('platform_id', 0/*platform::defaultId()*/);

        $products_string = '';

        $categories = \common\helpers\Categories::get_category_tree(0, '', '0', '', true, false, $platform_id);
        foreach ($categories as $category) {
            $products_query = tep_db_query(
                "select distinct p.products_id, pd.products_name, p.products_status ".
                "from " . TABLE_PRODUCTS . " p ".
                " left join " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c on p.products_id = p2c.products_id ".
                " left join " . TABLE_PRODUCTS_DESCRIPTION . " pd on p.products_id = pd.products_id ".
                ($platform_id>0?" inner join ".TABLE_PLATFORMS_PRODUCTS." pp ON pp.products_id=p.products_id AND pp.platform_id='{$platform_id}' ":'').
                "where p2c.categories_id = '" . (int)$category['id'] . "' and pd.language_id = '" . (int) $languages_id . "' AND pd.platform_id = '".intval(\common\classes\platform::defaultId())."' ".
                " and (p.products_model like '%" . tep_db_input($q) . "%' or pd.products_name like '%" . tep_db_input($q) . "%') ".
                "group by p.products_id ".
                "order by p.sort_order, pd.products_name ".
                "limit 0, 100"
            );
            if (tep_db_num_rows($products_query) > 0) {
                $products_string .= '<optgroup label="' . $category['text'] . '">';
                while ($products = tep_db_fetch_array($products_query)) {
                    $products_string .= '<option value="' . $products['products_id'] . '" ' . ($products['products_status'] == 0 ? ' class="dis_prod"' : '') . '>' . $products['products_name'] . '</option>';
                }
                $products_string .= '</optgroup>';
            }
        }

        return $products_string;
    }

    public function actionSetNewProduct()
    {
        $languages_id = \Yii::$app->settings->get('languages_id');

        $this->layout = false;

        $currencies = \Yii::$container->get('currencies');

        $products_id = (int) Yii::$app->request->post('products_id');
        $query = tep_db_query(
            "select p.products_id, pd.products_name, p.products_status ".
            "from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd ".
            "where p.products_id = '" . (int)$products_id . "' and p.products_id =  pd.products_id and pd.language_id = '" . (int)$languages_id . "' and pd.platform_id = '".intval(\common\classes\platform::defaultId())."' ".
            "limit 1"
        );
        if (tep_db_num_rows($query) > 0) {
            $data = tep_db_fetch_array($query);
        } else {
            $data = array();
        }

        if (count($data) > 0) {
            $product = [
                'products_id' => $data['products_id'],
                'products_name' => $data['products_name'],
                'image' => \common\classes\Images::getImage($data['products_id'], 'Small'),
                'price' => $currencies->format( \common\helpers\Product::get_products_price($data['products_id'])),
                'status_class' => ($data['products_status'] == 0 ? 'dis_prod' : ''),
            ];

            return $this->render('product-set-item.tpl', [
                'product' => $product,
            ]);
        }
    }

    public function actionTemplateEdit()
    {
        $this->layout = 'popup.tpl';
        Translation::init('admin/seo-delivery-location');
        Translation::init('admin/categories');

        $platform_id = intval(Yii::$app->request->get('platform_id',platform::defaultId()));
        $parent_id = intval(Yii::$app->request->get('parent_id',0));
        $language_id = intval(Yii::$app->request->get('language_id', $_SESSION['language_id']));

        $parentArray = SeoDeliveryLocation::getParents($platform_id, $parent_id, true);

        $level = count($parentArray)+1;

        if ( Yii::$app->request->isPost ) {
            $location_name_array = tep_db_prepare_input(Yii::$app->request->post('location_name',[]));
            $location_description_array = tep_db_prepare_input(Yii::$app->request->post('template_location_description',[]));
            $meta_title_array = tep_db_prepare_input(Yii::$app->request->post('meta_title',[]));
            $overwrite_head_title_tag_array = tep_db_prepare_input(Yii::$app->request->post('overwrite_head_title_tag',[]));
            $meta_keyword_array = tep_db_prepare_input(Yii::$app->request->post('meta_keyword',[]));
            $meta_description_array = tep_db_prepare_input(Yii::$app->request->post('meta_description',[]));
            $overwrite_head_desc_tag_array = tep_db_prepare_input(Yii::$app->request->post('overwrite_head_desc_tag',[]));
            foreach( \common\helpers\Language::get_languages() as $_lang ) {
                $update_language_id = $_lang['id'];

                $item_text_data = [
                    'language_id' => $update_language_id,
                    'location_name' => $location_name_array[$update_language_id],
                    'location_description' => $location_description_array[$update_language_id],
                    'meta_title' => $meta_title_array[$update_language_id],
                    'overwrite_head_title_tag' => (isset($overwrite_head_title_tag_array[$update_language_id]) && $overwrite_head_title_tag_array[$update_language_id])?1:0,
                    'meta_keyword' => $meta_keyword_array[$update_language_id],
                    'meta_description' => $meta_description_array[$update_language_id],
                    'overwrite_head_desc_tag' => (isset($overwrite_head_desc_tag_array[$update_language_id]) && $overwrite_head_desc_tag_array[$update_language_id])?1:0,
                ];

                $checkText = tep_db_fetch_array(tep_db_query(
                    "SELECT COUNT(*) AS c ".
                    "FROM ".TABLE_SEO_DELIVERY_LOCATION_TEXT_TEMPLATE." ".
                    "WHERE platform_id='{$platform_id}' AND language_id='{$update_language_id}' AND level='{$level}'"
                ));
                if ( $checkText['c']==0 ) {
                    $item_text_data['platform_id'] = $platform_id;
                    $item_text_data['level'] = $level;
                    tep_db_perform(TABLE_SEO_DELIVERY_LOCATION_TEXT_TEMPLATE, $item_text_data);
                }else{
                    tep_db_perform(TABLE_SEO_DELIVERY_LOCATION_TEXT_TEMPLATE, $item_text_data, 'update', "platform_id='{$platform_id}' AND language_id='{$update_language_id}' AND level='{$level}'");
                }

            }

            return '1';
        }
        $template_data = [];
        $languages = \common\helpers\Language::get_languages();
        for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
            $languages[$i]['logo'] = $languages[$i]['image'];
            $template_data[$languages[$i]['id']] = [
                'overwrite_head_title' => 0,
                'overwrite_head_desc_tag' => 0,
            ];
        }

        $get_template_data_r = tep_db_query(
            "SELECT * ".
            "FROM ".TABLE_SEO_DELIVERY_LOCATION_TEXT_TEMPLATE." ".
            "WHERE platform_id='{$platform_id}' AND level='{$level}'"
        );
        if ( tep_db_num_rows($get_template_data_r)>0 ) {
            while($_template_data = tep_db_fetch_array($get_template_data_r)){
                $template_data[$_template_data['language_id']] = $_template_data;
            }
        }
        $page_name = 'Edit template ';
        return $this->render('template-edit.tpl',[
            'languages' => $languages,
            'platform_id' => $platform_id,
            'active_language_id' => $language_id,
            'template_data' => $template_data,
            'level' => $level,
            'page_name' => $page_name,
            'action' => Yii::$app->urlManager->createUrl(['seo-delivery-location/template-edit']+Yii::$app->request->get()),
        ]);
    }

    public function actionTemplatePreview()
    {
        $this->layout = 'popup.tpl';
        Translation::init('admin/seo-delivery-location');

        $platform_id = intval(Yii::$app->request->post('platform_id',platform::defaultId()));
        $parent_id = intval(Yii::$app->request->post('parent_id',0));
        $language_id = intval(Yii::$app->request->get('language_id', $_SESSION['language_id']));

        $parentArray = SeoDeliveryLocation::getParents($platform_id, $parent_id, true);

        $location_name_array = tep_db_prepare_input(Yii::$app->request->post('location_name',[]));
        $location_description_array = tep_db_prepare_input(Yii::$app->request->post('location_description',[]));
        $meta_title_array = tep_db_prepare_input(Yii::$app->request->post('meta_title',[]));
        $meta_keyword_array = tep_db_prepare_input(Yii::$app->request->post('meta_keyword',[]));
        $meta_description_array = tep_db_prepare_input(Yii::$app->request->post('meta_description',[]));

        $data = [];
        foreach( \common\helpers\Language::get_languages() as $_lang ) {
            $__language_id = $_lang['id'];

            $data[$__language_id] = [
                'language_id' => $__language_id,
                'location_name' => $location_name_array[$__language_id],
                'location_description' => $location_description_array[$__language_id],
                'meta_title' => $meta_title_array[$__language_id],
                'meta_keyword' => $meta_keyword_array[$__language_id],
                'meta_description' => $meta_description_array[$__language_id],
            ];
        }

        $level = count($parentArray)+1;
        $template_data = [];
        $languages = \common\helpers\Language::get_languages();
        for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
            $languages[$i]['logo'] = $languages[$i]['image'];
            $template_data[$languages[$i]['id']] = SeoDeliveryLocation::applyTemplate(
                $platform_id,
                $languages[$i]['id'],
                $level,
                $parent_id,
                $data[$languages[$i]['id']]
            );
        }

        $page_name = 'Preview template ';
        return $this->render('template-preview.tpl',[
            'languages' => $languages,
            'active_language_id' => $language_id,
            'template_data' => $template_data,
            'page_name' => $page_name,
        ]);

    }

    public function actionAllList() {

        $deliveryLocation = \common\models\SeoDeliveryLocation::find()
            ->from(['dl' => \common\models\SeoDeliveryLocation::tableName()])
            ->select([
                'id' => 'dl.id',
                'text' => 't.location_name',
                'parent_id' => 'dl.parent_id',
            ])
            ->joinWith([
                'locationText t' => function ($query) {
                    $query->onCondition(['t.language_id' => \Yii::$app->settings->get('languages_id')])
                        ->select([
                            'text' => 't.location_name',
                        ]);
                }
            ])
            ->asArray()
            ->all();

        return json_encode($deliveryLocation);
    }

    public function actionGetPages()
    {
        global $login_id;
        $login_id = (int)$login_id;
        $platformId = (int)Yii::$app->request->post('platform_id', 0);
        $term = Yii::$app->request->post('term', '');
        if ($platformId < 1) {
            throw new NotFoundException('Platform not find.');
        }
        $information = \common\helpers\SeoDeliveryLocation::getTree($platformId);
        if ($term){
            foreach ($information as $key => $item) {
                if (stripos($item['path'], $term) === false) {
                    unset($information[$key]);
                }
            }
        }
        return $this->renderAjax('./generate-info-select.tpl', [
            'information' => $information,
        ]);
    }

    public function actionGetCategories()
    {
        $term = Yii::$app->request->post('term', '');
        $categories = \common\helpers\Categories::get_category_tree('0', '', '', '', false, true);
        if ($term){
            foreach ($categories as $key => $item) {
                if (stripos($item['text'], $term) === false) {
                    unset($categories[$key]);
                }
            }
        }
        return $this->renderAjax('./generate-categories.tpl', [
            'categories' => $categories,
        ]);
    }
}
