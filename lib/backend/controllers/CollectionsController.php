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
use \common\helpers\Translation;

class CollectionsController extends Sceleton {

    public $acl = ['BOX_HEADING_MARKETING_TOOLS', 'BOX_MARKETING_COLLECTIONS'];
    
    public function __construct($id, $module=null) {
      Translation::init('admin/collections');
      parent::__construct($id, $module);
    }    

    public function actionIndex() {

        $this->selectedMenu = array('marketing', 'collections');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('collections/index'), 'title' => HEADING_TITLE);
        $this->view->headingTitle = HEADING_TITLE;
        $this->topButtons[] = '<a href="#" class="create_item" onclick="return collectionEdit(0)">' . IMAGE_NEW_COLLECTION . '</a>';

        $this->view->collectionTable = array(
            array(
                'title' => TABLE_HEADING_COLLECTIONS_NAME,
                'not_important' => 0,
            ),
            array(
                'title' => TABLE_HEADING_PRODUCTS_COUNT,
                'not_important' => 0,
            ),
        );

        $messages = $_SESSION['messages'];
        unset($_SESSION['messages']);
        if (!is_array($messages)) $messages = [];

        $cID = Yii::$app->request->get('cID', 0);
        return $this->render('index', array('messages' => $messages, 'cID' => $cID));
    }

    public function actionList() {
        $languages_id = \Yii::$app->settings->get('languages_id');
        $draw = Yii::$app->request->get('draw', 1);
        $start = Yii::$app->request->get('start', 0);
        $length = Yii::$app->request->get('length', 10);

        $search = '';
        if (isset($_GET['search']['value']) && tep_not_null($_GET['search']['value'])) {
            $keywords = tep_db_input(tep_db_prepare_input($_GET['search']['value']));
            $search .= " and (c.collections_name like '%" . $keywords . "%')";
        }

        $current_page_number = ($start / $length) + 1;
        $responseList = array();

        if (isset($_GET['order'][0]['column']) && $_GET['order'][0]['dir']) {
            switch ($_GET['order'][0]['column']) {
                case 0:
                    $orderBy = "c.collections_name " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                    break;
                case 1:
                    $orderBy = "products_count " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                    break;
                default:
                    $orderBy = "c.collections_sortorder";
                    break;
            }
        } else {
            $orderBy = "c.collections_sortorder";
        }

        $collections_query_raw = "select c.collections_id, c.collections_name, collections_image, count(c2p.products_id) as products_count from " . TABLE_COLLECTIONS . " c left join " . TABLE_COLLECTIONS_TO_PRODUCTS . " c2p on c.collections_id = c2p.collections_id where c.language_id = '" . (int)$languages_id . "' " . $search . " group by c.collections_id order by " . $orderBy;
        $collections_split = new \splitPageResults($current_page_number, $length, $collections_query_raw, $collections_query_numrows, 'c.collections_id');
        $collections_query = tep_db_query($collections_query_raw);

        while ($collections = tep_db_fetch_array($collections_query)) {
            $image = \common\helpers\Image::info_image($collections['collections_image'], $collections['collections_name'], 50, 50);
            $responseList[] = array(
                '<div class="handle_cat_list state-disabled"><span class="handle"><i class="icon-hand-paper-o"></i></span><div class="prod_name">' . (tep_not_null($image) && $image != TEXT_IMAGE_NONEXISTENT ? '<span class="prodImgC">' . $image . '</span>' : '<span class="cubic"></span>')  . '<table class="wrapper"><tr><td><span class="prodNameC">' . $collections['collections_name'] . '</span></td></tr></table>' . tep_draw_hidden_field('id', $collections['collections_id'], 'class="cell_identify"') . '<input class="cell_type" type="hidden" value="collection"></div></div>',
                $collections['products_count'],
            );
        }

        $response = array(
            'draw' => $draw,
            'recordsTotal' => $collections_query_numrows,
            'recordsFiltered' => $collections_query_numrows,
            'data' => $responseList
        );
        echo json_encode($response);
    }

    public function actionStatusactions() {
        $languages_id = \Yii::$app->settings->get('languages_id');
        \common\helpers\Translation::init('admin/collections');

        $collections_id = Yii::$app->request->post('collections_id', 0);
        $this->layout = false;
        if ($collections_id) {
            $collections = tep_db_fetch_array(tep_db_query("select collections_id, collections_name from " . TABLE_COLLECTIONS . " where collections_id = '" . (int) $collections_id . "' and language_id = '" . (int)$languages_id . "'"));
            $cInfo = new \objectInfo($collections, false);
            $heading = array();
            $contents = array();

            if (is_object($cInfo)) {
                echo '<div class="or_box_head">' . $cInfo->collections_name . '</div>';

                $collection_inputs_string = '';
                $languages = \common\helpers\Language::get_languages();
                for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
                  $collection_inputs_string .= '<div class="col_desc">' . $languages[$i]['image'] . '&nbsp;' . \common\helpers\Collections::collections_name($cInfo->collections_id, $languages[$i]['id']) . '</div>';
                }
                echo $collection_inputs_string;

                echo '<div class="btn-toolbar btn-toolbar-order">';
                echo '<a class="btn btn-primary btn-process-order btn-edit" href="' . Yii::$app->urlManager->createUrl(['collections/products', 'collections_id' => $collections_id]) . '">' . FIELDSET_ASSIGNED_PRODUCTS . '</a>';
                echo '<button class="btn btn-edit btn-no-margin" onclick="collectionEdit(' . $collections_id . ')">' . IMAGE_EDIT . '</button><button class="btn btn-delete" onclick="collectionDeleteConfirm(' . $collections_id . ')">' . IMAGE_DELETE . '</button>';
                echo '</div>';
            }

        }
    }

    public function actionEdit() {
        $languages_id = \Yii::$app->settings->get('languages_id');
        \common\helpers\Translation::init('admin/collections');

        $collections_id = Yii::$app->request->get('collections_id', 0);
        $collections = tep_db_fetch_array(tep_db_query("select collections_id, collections_name, collections_image from " . TABLE_COLLECTIONS . " where collections_id = '" . (int) $collections_id . "' and language_id = '" . (int)$languages_id . "'"));
        $cInfo = new \objectInfo($collections, false);

        echo tep_draw_form('collection', FILENAME_COLLECTIONS . '/save', 'collections_id=' . $cInfo->collections_id, 'post', 'onsubmit="return collectionSave(' . ($cInfo->collections_id ? $cInfo->collections_id : 0) . ');"');

        if ($collections_id) {
            echo '<div class="or_box_head">' . TEXT_INFO_HEADING_EDIT_COLLECTION . '</div>';
        } else {
            echo '<div class="or_box_head">' . TEXT_INFO_HEADING_NEW_COLLECTION . '</div>';
        }

        $collection_inputs_string = '';
        $languages = \common\helpers\Language::get_languages();
        for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
          $collection_inputs_string .= '<div class="langInput">' . $languages[$i]['image'] . tep_draw_input_field('collections_name[' . $languages[$i]['id'] . ']', \common\helpers\Collections::collections_name($cInfo->collections_id, $languages[$i]['id'])) . '</div>';
        }
        echo '<div class="main_row"><div class="main_title">' . TEXT_INFO_COLLECTIONS_NAME . '</div><div class="main_value">' . $collection_inputs_string . '</div></div>';

        echo '<div class="main_row"><div class="main_title">' . TEXT_INFO_COLLECTIONS_IMAGE . '</div><div class="main_value"><div class="collections_image" data-name="collections_image" data-value="' . $cInfo->collections_image . '"></div></div></div>
<script type="text/javascript">
$(".collections_image").image_uploads();
</script>';

        echo '<div class="btn-toolbar btn-toolbar-order">';
        if ($collections_id) {
            echo '<input type="submit" value="' . IMAGE_UPDATE . '" class="btn btn-no-margin"><input type="button" value="' . IMAGE_CANCEL . '" class="btn btn-cancel" onclick="resetStatement(' . (int)$cInfo->collections_id . ')">';
        } else {
            echo '<input type="submit" value="' . IMAGE_NEW . '" class="btn btn-no-margin"><input type="button" value="' . IMAGE_CANCEL . '" class="btn btn-cancel" onclick="resetStatement(' . (int)$cInfo->collections_id . ')">';
        }

        echo '</div>';
        echo '</form>';
    }

    public function actionSave() {
        \common\helpers\Translation::init('admin/collections');
        $collections_id = intval(Yii::$app->request->get('collections_id', 0));
        $collections_name = tep_db_prepare_input(Yii::$app->request->post('collections_name', array()));
        $collections_image = tep_db_prepare_input(Yii::$app->request->post('collections_image', ''));

        if ($collections_id == 0) {
            $next_id_query = tep_db_query("select max(collections_id) as collections_id from " . TABLE_COLLECTIONS . " where 1");
            $next_id = tep_db_fetch_array($next_id_query);
            $insert_id = $next_id['collections_id'] + 1;
        }

        if ($collections_image == 'del') {
            //$collections_image = '';
        } elseif ($collections_image != '') {
          $path = \Yii::getAlias('@webroot');
          $path .= DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
          $tmp_name = $path . $collections_image;
          $new_name = DIR_FS_CATALOG_IMAGES . $collections_id . '-' . $collections_image;
          @copy($tmp_name, $new_name);
          @unlink($tmp_name);
          $collections_image = $collections_id . '-' . $collections_image;
        }

        $languages = \common\helpers\Language::get_languages();
        for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
            $language_id = $languages[$i]['id'];

            $sql_data_array = array('collections_name' => $collections_name[$language_id]);
            
            if ($collections_image == 'del') {
                $sql_data_array['collections_image'] = '';
            } elseif ($collections_image != '') {
                $sql_data_array['collections_image'] = $collections_image;
            }

            if ($collections_id == 0) {
                $insert_sql_data = array('collections_id' => $insert_id,
                                         'language_id' => $language_id,
                                         'date_added' => 'now()');
                $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

                tep_db_perform(TABLE_COLLECTIONS, $sql_data_array);
                $action = 'added';
            } else {
                $update_sql_data = array('date_last_modified' => 'now()');
                $sql_data_array = array_merge($sql_data_array, $update_sql_data);

                $check = tep_db_fetch_array(tep_db_query("select count(collections_id) as collections_exists from " . TABLE_COLLECTIONS . " where collections_id = '" . (int)$collections_id . "' and language_id = '" . (int)$language_id . "'"));
                if (!$check['collections_exists']) {
                    $insert_sql_data = array('collections_id' => $collections_id,
                                             'language_id' => $language_id,
                                             'date_added' => 'now()');
                    $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

                    tep_db_perform(TABLE_COLLECTIONS, $sql_data_array);
                } else {
                    tep_db_perform(TABLE_COLLECTIONS, $sql_data_array, 'update', "collections_id = '" . (int) $collections_id . "' and language_id = '" . (int)$language_id . "'");
                }
                $action = 'updated';
            }
        }

        echo json_encode(array('message' => 'Collection ' . $action, 'messageType' => 'alert-success'));
    }

    public function actionSortOrder() {
        $collections_sorted = Yii::$app->request->post('collection', array());
        foreach ($collections_sorted as $sort_order => $collections_id) {
            tep_db_query("update " . TABLE_COLLECTIONS . " set collections_sortorder = '" . (int)($sort_order) . "' where collections_id = '" . (int)$collections_id . "'");
        }
    }

    public function actionConfirmdelete() {
        $languages_id = \Yii::$app->settings->get('languages_id');

        $this->layout = false;

        $collections_id = Yii::$app->request->post('collections_id', 0);

        if ($collections_id > 0) {
            $collections = tep_db_fetch_array(tep_db_query("select collections_id, collections_name from " . TABLE_COLLECTIONS . " where language_id = '" . (int)$languages_id . "' and collections_id = '" . (int)$collections_id . "'"));
            $cInfo = new \objectInfo($collections, false);

            echo tep_draw_form('collections', FILENAME_COLLECTIONS, \common\helpers\Output::get_all_get_params(array('cID', 'action')) . 'dID=' . $cInfo->collections_id . '&action=deleteconfirm', 'post', 'id="item_delete" onSubmit="return collectionDelete();"');

            echo '<div class="or_box_head">' . $cInfo->collections_name . '</div>';
            echo TEXT_DELETE_INTRO . '<br>';
            echo '<div class="btn-toolbar btn-toolbar-order">';
            echo '<button type="submit" class="btn btn-primary btn-no-margin">' . IMAGE_CONFIRM . '</button>';
            echo '<button class="btn btn-cancel" onClick="return resetStatement(' . (int)$collections_id . ')">' . IMAGE_CANCEL . '</button>';      

            echo tep_draw_hidden_field('collections_id', $collections_id);
            echo '</div></form>';
        }
    }

    public function actionDelete() {
        \common\helpers\Translation::init('admin/collections');

        $collections_id = Yii::$app->request->post('collections_id', 0);

        if ($collections_id) {
            tep_db_query("delete from " . TABLE_COLLECTIONS . " where collections_id = '" . (int)$collections_id . "'");
            tep_db_query("delete from " . TABLE_COLLECTIONS_TO_PRODUCTS . " where collections_id = '" . (int)$collections_id . "'");
            tep_db_query("delete from " . TABLE_COLLECTIONS_DISCOUNT_PRICES. " where collections_id = '" . (int)$collections_id . "'");
            echo 'reset';
        }
    }

    public function actionProducts() {
        $languages_id = \Yii::$app->settings->get('languages_id');
        \common\helpers\Translation::init('admin/categories');

        $currencies = Yii::$container->get('currencies');

        $this->selectedMenu = array('marketing', 'collections');

        $collections_id = Yii::$app->request->get('collections_id', 0);
        $collections = tep_db_fetch_array(tep_db_query("select collections_id, collections_name, collections_type from " . TABLE_COLLECTIONS . " where collections_id = '" . (int) $collections_id . "' and language_id = '" . (int)$languages_id . "'"));
        $cInfo = new \objectInfo($collections, false);

        $this->navigation[]       = array('link' => Yii::$app->urlManager->createUrl('collections/products'), 'title' => sprintf(HEADING_TITLE_EDIT_PRODUCTS, $cInfo->collections_name));
        $this->view->headingTitle = sprintf(HEADING_TITLE_EDIT_PRODUCTS, $cInfo->collections_name);

        $this->view->usePopupMode = false;
        if (Yii::$app->request->isAjax) {
          $this->layout = false;
          $this->view->usePopupMode = true;
        }

        $collectionProducts = [];
        $query = tep_db_query("select p.products_id, p.products_status, ".ProductNameDecorator::instance()->listingQueryExpression('pd','')." AS products_name from  " . TABLE_COLLECTIONS_TO_PRODUCTS . " c2p, " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where c2p.products_id = p.products_id and p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "' and pd.platform_id = '".intval(\common\classes\platform::defaultId())."' and c2p.collections_id = '" . (int)$cInfo->collections_id . "' order by c2p.sort_order, pd.products_name");
        while ($data = tep_db_fetch_array($query)) {
            $collectionProducts[] = [
                'products_id' => $data['products_id'],
                'products_name' => $data['products_name'],
                'image' => \common\classes\Images::getImage($data['products_id'], 'Small'),
                'price' => $currencies->format(\common\helpers\Product::get_products_price($data['products_id'])),
                //'discount_configurator' => $product['products_discount_configurator'],
                //'price_configurator' => ($product['products_price_configurator'] > 0 ? $product['products_price_configurator'] : ''),
                'status_class' => ($data['products_status'] == 0 ? 'dis_prod' : ''),
            ];
        }
        $this->view->collectionProducts = $collectionProducts;

        return $this->render('products.tpl', ['cInfo' => $cInfo]);
    }

    public function actionProductsUpdate() {
        $collections_id = Yii::$app->request->post('collections_id');
        $collection_products_id = Yii::$app->request->post('collection_products_id', array());
        //$collection_products_discount = Yii::$app->request->post('collection_products_discount', array());
        //$collection_products_price = Yii::$app->request->post('collection_products_price', array());
        $collections_type = Yii::$app->request->post('collections_type');

        tep_db_query("update " . TABLE_COLLECTIONS . " set collections_type = '" . (int)$collections_type . "' where collections_id = '" . (int)$collections_id . "'");

        $all_products_array = array();
        foreach ($collection_products_id as $sort_order => $products_id) {
            $sql_data_array = array('collections_id' => (int)$collections_id,
                                    'products_id' => (int)$products_id,
                                    //'products_discount' => (float)$collection_products_discount[$products_id],
                                    //'products_price' => (float)$collection_products_price[$products_id],
                                    'sort_order' => (int)$sort_order);
            $check = tep_db_fetch_array(tep_db_query("select count(*) as collection_exists from " . TABLE_COLLECTIONS_TO_PRODUCTS . " where collections_id = '" . (int)$collections_id . "' and products_id = '" . (int)$products_id . "'"));
            if ($check['collection_exists']) {
                tep_db_perform(TABLE_COLLECTIONS_TO_PRODUCTS, $sql_data_array, 'update', "collections_id = '" . (int)$collections_id . "' and products_id = '" . (int)$products_id . "'");
            } else {
                tep_db_perform(TABLE_COLLECTIONS_TO_PRODUCTS, $sql_data_array);
            }
            $all_products_array[] = (int) $products_id;
        }
        tep_db_query("delete from " . TABLE_COLLECTIONS_TO_PRODUCTS . " where collections_id = '" . (int)$collections_id . "' and products_id not in ('" . implode("','", $all_products_array) . "')");

        $all_collections_discount_prices_array = array();
        // Discount Collection
        $collections_discount_array = Yii::$app->request->post('collections_discount', array());
        foreach ($collections_discount_array as $collections_products_count => $collections_discount) {
            $sql_data_array = array('collections_id' => (int)$collections_id,
                                    'collections_type' => (int)0,
                                    'collections_products_count' => (int)$collections_products_count,
                                    'collections_discount' => (float)$collections_discount);
            $check = tep_db_fetch_array(tep_db_query("select collections_discount_prices_id from " . TABLE_COLLECTIONS_DISCOUNT_PRICES . " where collections_type = '0' and collections_id = '" . (int)$collections_id . "' and collections_products_count = '" . (int)$collections_products_count . "'"));
            if ($check['collections_discount_prices_id'] > 0) {
                $collections_discount_prices_id = $check['collections_discount_prices_id'];
                tep_db_perform(TABLE_COLLECTIONS_DISCOUNT_PRICES, $sql_data_array, 'update', "collections_discount_prices_id = '" . (int)$collections_discount_prices_id . "'");
            } else {
                tep_db_perform(TABLE_COLLECTIONS_DISCOUNT_PRICES, $sql_data_array);
                $collections_discount_prices_id = tep_db_insert_id();
            }
            $all_collections_discount_prices_array[] = (int) $collections_discount_prices_id;
        }
        // Price Collection
        $collections_price_array = Yii::$app->request->post('collections_price', array());
        foreach ($collections_price_array as $collections_products_set => $collections_price) {
            $sql_data_array = array('collections_id' => (int)$collections_id,
                                    'collections_type' => (int)1,
                                    'collections_products_set' => $collections_products_set,
                                    'collections_price' => (float)$collections_price);
            $check = tep_db_fetch_array(tep_db_query("select collections_discount_prices_id from " . TABLE_COLLECTIONS_DISCOUNT_PRICES . " where collections_type = '1' and collections_id = '" . (int)$collections_id . "' and collections_products_set = '" . tep_db_input($collections_products_set) . "'"));
            if ($check['collections_discount_prices_id'] > 0) {
                $collections_discount_prices_id = $check['collections_discount_prices_id'];
                tep_db_perform(TABLE_COLLECTIONS_DISCOUNT_PRICES, $sql_data_array, 'update', "collections_discount_prices_id = '" . (int)$collections_discount_prices_id . "'");
            } else {
                tep_db_perform(TABLE_COLLECTIONS_DISCOUNT_PRICES, $sql_data_array);
                $collections_discount_prices_id = tep_db_insert_id();
            }
            $all_collections_discount_prices_array[] = (int) $collections_discount_prices_id;
        }
        tep_db_query("delete from " . TABLE_COLLECTIONS_DISCOUNT_PRICES . " where collections_id = '" . (int)$collections_id . "' and collections_discount_prices_id not in ('" . implode("','", $all_collections_discount_prices_array) . "')");

        if (Yii::$app->request->isAjax) {
//          $this->layout = false;
        } else {
            return $this->redirect(Yii::$app->urlManager->createUrl(['collections/index', 'cID' => $collections_id]));
        }
    }

    public function actionProductSearch() {
        $languages_id = \Yii::$app->settings->get('languages_id');

        $q = Yii::$app->request->get('q');

        $products_string = '';

        $categories = \common\helpers\Categories::get_category_tree(0, '', '0', '', true);
        foreach ($categories as $category) {
            $products_query = tep_db_query("select distinct p.products_id, ".ProductNameDecorator::instance()->listingQueryExpression('pd','')." AS products_name, p.products_status from " . TABLE_PRODUCTS . " p left join " . TABLE_PRODUCTS_TO_CATEGORIES . " c2p on p.products_id = c2p.products_id left join " . TABLE_PRODUCTS_DESCRIPTION . " pd on p.products_id = pd.products_id where c2p.categories_id = '" . $category['id'] . "' and pd.language_id = '" . (int) $languages_id . "' and (p.products_model like '%" . tep_db_input($q) . "%' or pd.products_name like '%" . tep_db_input($q) . "%' or pd.products_internal_name like '%" . tep_db_input($q) . "%') group by p.products_id order by p.sort_order, pd.products_name limit 0, 100");
            if (tep_db_num_rows($products_query) > 0) {
                $products_string .= '<optgroup label="' . $category['text'] . '">';
                while ($products = tep_db_fetch_array($products_query)) {
                    $products_string .= '<option value="' . $products['products_id'] . '" ' . ($products['products_status'] == 0 ? ' class="dis_prod"' : '') . '>' . $products['products_name'] . '</option>';
                }
                $products_string .= '</optgroup>';
            }
        }

        echo $products_string;
    }

    public function actionNewProduct() {
        $languages_id = \Yii::$app->settings->get('languages_id');

        $this->layout = false;

        $currencies = Yii::$container->get('currencies');

        $products_id = (int) Yii::$app->request->post('products_id');
        $query = tep_db_query("select p.products_id, ".ProductNameDecorator::instance()->listingQueryExpression('pd','')." AS products_name, p.products_status from " . TABLE_PRODUCTS_DESCRIPTION . " pd," . TABLE_PRODUCTS . " p where language_id = '" . (int)$languages_id . "' and platform_id = '".intval(\common\classes\platform::defaultId())."' and p.products_id = '" . (int)$products_id . "' and p.products_id =  pd.products_id limit 1");
        if (tep_db_num_rows($query) > 0) {
            $data = tep_db_fetch_array($query);
        } else {
            $data = array();
        }

        if (count($data) > 0) {
            $collectionProduct = [
                'products_id' => $data['products_id'],
                'products_name' => $data['products_name'],
                'image' => \common\classes\Images::getImage($data['products_id'], 'Small'),
                'price' => $currencies->format(\common\helpers\Product::get_products_price($data['products_id'])),
                'status_class' => ($data['products_status'] == 0 ? 'dis_prod' : ''),
            ];

            return $this->render('new-product.tpl', [
                'collection' => $collectionProduct,
            ]);
        }
    }

    public function actionDiscountBox() {
        $languages_id = \Yii::$app->settings->get('languages_id');

        $this->layout = false;

        $currencies = Yii::$container->get('currencies');

        $collections_id = Yii::$app->request->post('collections_id');
        $collection_products_id = Yii::$app->request->post('collection_products_id', array());

        $collections = tep_db_fetch_array(tep_db_query("select collections_id, collections_name, collections_type from " . TABLE_COLLECTIONS . " where collections_id = '" . (int) $collections_id . "' and language_id = '" . (int)$languages_id . "'"));
        $cInfo = new \objectInfo($collections, false);
        $collections_type = Yii::$app->request->post('collections_type', $cInfo->collections_type);

        $all_products_data = array();
        $all_products_array = array();
        foreach ($collection_products_id as $sort_order => $products_id) {
            $data = tep_db_fetch_array(tep_db_query("select p.products_id, ".ProductNameDecorator::instance()->listingQueryExpression('pd','')." AS name, p.products_status from " . TABLE_PRODUCTS_DESCRIPTION . " pd," . TABLE_PRODUCTS . " p where language_id = '" . (int) $languages_id . "' and platform_id = '".intval(\common\classes\platform::defaultId())."' and p.products_id = '" . (int) $products_id . "' and p.products_id =  pd.products_id limit 1"));
            $data['image'] = \common\classes\Images::getImage($data['products_id'], 'Small');
            $data['price'] = \common\helpers\Product::get_products_price($data['products_id']);
            $data['price_format'] = $currencies->format($data['price']);
            $all_products_data[(int) $products_id] = $data;
            $all_products_array[] = (int) $products_id;
        }
        //sort($all_products_array);

        $price_collection_array = array();
        $discount_collection_array = array();
        for ($i = count($all_products_array); $i > 1; $i--) {
            // Discount Collection
            $discount_collection = array();
            $discount_collection['products_count'] = $i;
            $check = tep_db_fetch_array(tep_db_query("select collections_discount from " . TABLE_COLLECTIONS_DISCOUNT_PRICES . " where collections_type = '0' and collections_id = '" . (int) $collections_id . "' and collections_products_count = '" . (int) $discount_collection['products_count'] . "'"));
            if ($check['collections_discount'] > 0) $discount_collection['discount'] = $check['collections_discount'];
            $discount_collection_array[] = $discount_collection;
            // Price Collection
            foreach (self::getCombinations($all_products_array, $i) as $combination) {
                $price_collection = array('products' => array(), 'price' => 0);
                foreach ($combination as $products_id) {
                    $price_collection['products'][$products_id] = $all_products_data[$products_id];
                    $price_collection['price'] += $all_products_data[$products_id]['price'];
                }
                sort($combination);
                $price_collection['products_set'] = implode(',', $combination);
                $price_collection['price_format'] = $currencies->format($price_collection['price']);
                $check = tep_db_fetch_array(tep_db_query("select collections_price from " . TABLE_COLLECTIONS_DISCOUNT_PRICES . " where collections_type = '1' and collections_id = '" . (int) $collections_id . "' and collections_products_set = '" . tep_db_input($price_collection['products_set']) . "'"));
                if ($check['collections_price'] > 0) $price_collection['price'] = $check['collections_price'];
                $price_collection_array[] = $price_collection;
            }
        }

        return $this->render('discount-box.tpl', [
                    'cInfo' => $cInfo,
                    'collections_type' => $collections_type,
                    'price_collection_array' => $price_collection_array,
                    'discount_collection_array' => $discount_collection_array,
                    'collections_products_count' => count($all_products_array)
        ]);
    }

    public static function getCombinations($base, $n) {
        $baselen = count($base);
        if ($baselen == 0) {
            return;
        }
        if ($n == 1) {
            $return = array();
            foreach ($base as $b) {
                $return[] = array($b);
            }
            return $return;
        } else {
            //get one level lower combinations
            $oneLevelLower = self::getCombinations($base, $n - 1);

            //for every one level lower combinations add one element to them that the last element of a combination is preceeded by the element which follows it in base array if there is none, does not add
            $newCombs = array();

            foreach ($oneLevelLower as $oll) {
                $lastEl = $oll[$n - 2];
                $found = false;
                foreach ($base as $key => $b) {
                    if ($b == $lastEl) {
                        $found = true;
                        continue;
                        //last element found
                    }
                    if ($found == true) {
                        //add to combinations with last element
                        if ($key < $baselen) {

                            $tmp = $oll;
                            $newCombination = array_slice($tmp, 0);
                            $newCombination[] = $b;
                            $newCombs[] = array_slice($newCombination, 0);
                        }
                    }
                }
            }
        }
        return $newCombs;
    }

}
