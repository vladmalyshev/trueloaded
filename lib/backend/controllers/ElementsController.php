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

class ElementsController extends Sceleton {

    public $acl = ['BOX_HEADING_CATALOG', 'BOX_CATALOG_CONFIGURATOR', 'BOX_CATALOG_CATEGORIES_ELEMENTS'];
    
    public function __construct($id, $module=null) {
      Translation::init('admin/elements');
      parent::__construct($id, $module);
    }    

    public function actionIndex() {
        $this->selectedMenu = array('catalog', 'configurator', 'elements');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('elements/index'), 'title' => HEADING_TITLE);
        $this->view->headingTitle = HEADING_TITLE;
        $this->topButtons[] = '<a href="#" class="create_item" onclick="return elementEdit(0)">' . IMAGE_NEW_ELEMENT . '</a>';

        $this->view->elementTable = array(
            array(
                'title' => TABLE_HEADING_ELEMENTS_NAME,
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

        $eID = Yii::$app->request->get('eID', 0);
        return $this->render('index', array('messages' => $messages, 'eID' => $eID));
    }

    public function actionList() {
        $languages_id = \Yii::$app->settings->get('languages_id');
        $draw = Yii::$app->request->get('draw', 1);
        $start = Yii::$app->request->get('start', 0);
        $length = Yii::$app->request->get('length', 10);

        $search = '';
        if (isset($_GET['search']['value']) && tep_not_null($_GET['search']['value'])) {
            $keywords = tep_db_input(tep_db_prepare_input($_GET['search']['value']));
            $search .= " and (e.elements_name like '%" . $keywords . "%')";
        }

        $current_page_number = ($start / $length) + 1;
        $responseList = array();

        if (isset($_GET['order'][0]['column']) && $_GET['order'][0]['dir']) {
            switch ($_GET['order'][0]['column']) {
                case 0:
                    $orderBy = "e.elements_name " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                    break;
                case 1:
                    $orderBy = "products_count " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                    break;
                default:
                    $orderBy = "e.elements_sortorder";
                    break;
            }
        } else {
            $orderBy = "e.elements_sortorder";
        }

        $elements_query_raw = "select e.elements_id, e.elements_name, elements_image, count(p2e.products_id) as products_count from " . TABLE_ELEMENTS . " e left join " . TABLE_PRODUCTS_TO_ELEMENTS . " p2e on e.elements_id = p2e.elements_id where e.language_id = '" . (int)$languages_id . "' " . $search . " group by e.elements_id order by " . $orderBy;
        $elements_split = new \splitPageResults($current_page_number, $length, $elements_query_raw, $elements_query_numrows, 'e.elements_id');
        $elements_query = tep_db_query($elements_query_raw);

        while ($elements = tep_db_fetch_array($elements_query)) {
            $image = \common\helpers\Image::info_image($elements['elements_image'], $elements['elements_name'], 50, 50);
            $responseList[] = array(
                '<div class="handle_cat_list state-disabled"><span class="handle"><i class="icon-hand-paper-o"></i></span><div class="prod_name">' . (tep_not_null($image) && $image != TEXT_IMAGE_NONEXISTENT ? '<span class="prodImgC">' . $image . '</span>' : '<span class="cubic"></span>')  . '<table class="wrapper"><tr><td><span class="prodNameC">' . $elements['elements_name'] . '</span></td></tr></table>' . tep_draw_hidden_field('id', $elements['elements_id'], 'class="cell_identify"') . '<input class="cell_type" type="hidden" value="element"></div></div>',
                $elements['products_count'],
            );
        }

        $response = array(
            'draw' => $draw,
            'recordsTotal' => $elements_query_numrows,
            'recordsFiltered' => $elements_query_numrows,
            'data' => $responseList
        );
        echo json_encode($response);
    }

    public function actionStatusactions() {
        $languages_id = \Yii::$app->settings->get('languages_id');
        \common\helpers\Translation::init('admin/elements');

        $elements_id = Yii::$app->request->post('elements_id', 0);
        $this->layout = false;
        if ($elements_id) {
            $elements = tep_db_fetch_array(tep_db_query("select elements_id, elements_name from " . TABLE_ELEMENTS . " where elements_id = '" . (int) $elements_id . "' and language_id = '" . (int)$languages_id . "'"));
            $eInfo = new \objectInfo($elements, false);
            $heading = array();
            $contents = array();

            if (is_object($eInfo)) {
                echo '<div class="or_box_head">' . $eInfo->elements_name . '</div>';

                $element_inputs_string = '';
                $languages = \common\helpers\Language::get_languages();
                for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
                  $element_inputs_string .= '<div class="col_desc">' . $languages[$i]['image'] . '&nbsp;' . \common\helpers\Configurator::elements_name($eInfo->elements_id, $languages[$i]['id']) . '</div>';
                }
                echo $element_inputs_string;

                echo '<div class="btn-toolbar btn-toolbar-order">';
                echo '<a class="btn btn-primary btn-process-order btn-edit" href="' . Yii::$app->urlManager->createUrl(['elements/products', 'elements_id' => $elements_id]) . '">' . FIELDSET_ASSIGNED_PRODUCTS . '</a>';
                echo '<button class="btn btn-edit btn-no-margin" onclick="elementEdit(' . $elements_id . ')">' . IMAGE_EDIT . '</button><button class="btn btn-delete" onclick="elementDeleteConfirm(' . $elements_id . ')">' . IMAGE_DELETE . '</button>';
                echo '</div>';
            }

        }
    }

    public function actionEdit() {
        $languages_id = \Yii::$app->settings->get('languages_id');
        \common\helpers\Translation::init('admin/elements');

        $elements_id = Yii::$app->request->get('elements_id', 0);
        $elements = tep_db_fetch_array(tep_db_query("select elements_id, elements_name, elements_image, is_mandatory from " . TABLE_ELEMENTS . " where elements_id = '" . (int) $elements_id . "' and language_id = '" . (int)$languages_id . "'"));
        $eInfo = new \objectInfo($elements, false);

        echo tep_draw_form('element', 'elements' . '/save', 'elements_id=' . $eInfo->elements_id, 'post', 'onsubmit="return elementSave(' . ($eInfo->elements_id ? $eInfo->elements_id : 0) . ');"');

        if ($elements_id) {
            echo '<div class="or_box_head">' . TEXT_INFO_HEADING_EDIT_ELEMENT . '</div>';
        } else {
            echo '<div class="or_box_head">' . TEXT_INFO_HEADING_NEW_ELEMENT . '</div>';
        }

        $element_inputs_string = '';
        $languages = \common\helpers\Language::get_languages();
        for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
          $element_inputs_string .= '<div class="langInput">' . $languages[$i]['image'] . tep_draw_input_field('elements_name[' . $languages[$i]['id'] . ']', \common\helpers\Configurator::elements_name($eInfo->elements_id, $languages[$i]['id'])) . '</div>';
        }
        echo '<div class="main_row"><div class="main_title">' . TEXT_INFO_ELEMENTS_NAME . '</div><div class="main_value">' . $element_inputs_string . '</div></div>';

        echo '<div class="main_row"><div class="main_title">' . TEXT_INFO_ELEMENTS_IMAGE . '</div><div class="main_value"><div class="elements_image" data-name="elements_image" data-value="' . $eInfo->elements_image . '"></div></div></div>
<script type="text/javascript">
$(".elements_image").image_uploads();
</script>';

        echo '<div class="main_row"><div class="main_title">' . TEXT_INFO_IS_MANDATORY . '</div><div class="main_value">' . tep_draw_checkbox_field('is_mandatory', '1', $eInfo->is_mandatory) . '</div></div>';

        echo '<div class="btn-toolbar btn-toolbar-order">';
        if ($elements_id) {
            echo '<input type="submit" value="' . IMAGE_UPDATE . '" class="btn btn-no-margin"><input type="button" value="' . IMAGE_CANCEL . '" class="btn btn-cancel" onclick="resetStatement(' . (int)$eInfo->elements_id . ')">';
        } else {
            echo '<input type="submit" value="' . IMAGE_NEW . '" class="btn btn-no-margin"><input type="button" value="' . IMAGE_CANCEL . '" class="btn btn-cancel" onclick="resetStatement(' . (int)$eInfo->elements_id . ')">';
        }

        echo '</div>';
        echo '</form>';
    }

    public function actionSave() {
        \common\helpers\Translation::init('admin/elements');
        $elements_id = intval(Yii::$app->request->get('elements_id', 0));
        $elements_name = tep_db_prepare_input(Yii::$app->request->post('elements_name', array()));
        $elements_image = tep_db_prepare_input(Yii::$app->request->post('elements_image', ''));
        $is_mandatory = tep_db_prepare_input(Yii::$app->request->post('is_mandatory', 0));

        if ($elements_id == 0) {
            $next_id_query = tep_db_query("select max(elements_id) as elements_id from " . TABLE_ELEMENTS . " where 1");
            $next_id = tep_db_fetch_array($next_id_query);
            $insert_id = $next_id['elements_id'] + 1;
        }

        if ($elements_image == 'del') {
            $elements_image = '';
        } elseif ($elements_image != '') {
          $path = \Yii::getAlias('@webroot');
          $path .= DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
          $tmp_name = $path . $elements_image;
          $new_name = DIR_FS_CATALOG_IMAGES . $elements_id . '-' . $elements_image;
          @copy($tmp_name, $new_name);
          @unlink($tmp_name);
          $elements_image = $elements_id . '-' . $elements_image;
        }

        $languages = \common\helpers\Language::get_languages();
        for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
            $language_id = $languages[$i]['id'];

            $sql_data_array = array('elements_name' => $elements_name[$language_id],
                                    'elements_image' => $elements_image,
                                    'is_mandatory' => $is_mandatory);

            if ($elements_id == 0) {
                $insert_sql_data = array('elements_id' => $insert_id,
                                         'language_id' => $language_id,
                                         'date_added' => 'now()');
                $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

                tep_db_perform(TABLE_ELEMENTS, $sql_data_array);
                $action = 'added';
            } else {
                $update_sql_data = array('date_last_modified' => 'now()');
                $sql_data_array = array_merge($sql_data_array, $update_sql_data);

                $check = tep_db_fetch_array(tep_db_query("select count(elements_id) as elements_exists from " . TABLE_ELEMENTS . " where elements_id = '" . (int)$elements_id . "' and language_id = '" . (int)$language_id . "'"));
                if (!$check['elements_exists']) {
                    $insert_sql_data = array('elements_id' => $elements_id,
                                             'language_id' => $language_id,
                                             'date_added' => 'now()');
                    $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

                    tep_db_perform(TABLE_ELEMENTS, $sql_data_array);
                } else {
                    tep_db_perform(TABLE_ELEMENTS, $sql_data_array, 'update', "elements_id = '" . (int) $elements_id . "' and language_id = '" . (int)$language_id . "'");
                }
                $action = 'updated';
            }
        }

        echo json_encode(array('message' => 'Element ' . $action, 'messageType' => 'alert-success'));
    }

    public function actionSortOrder() {
        $elements_sorted = Yii::$app->request->post('element', array());
        foreach ($elements_sorted as $sort_order => $elements_id) {
            tep_db_query("update " . TABLE_ELEMENTS . " set elements_sortorder = '" . (int)($sort_order) . "' where elements_id = '" . (int)$elements_id . "'");
        }
    }

    public function actionConfirmdelete() {
        $languages_id = \Yii::$app->settings->get('languages_id');

        $this->layout = false;

        $elements_id = Yii::$app->request->post('elements_id', 0);

        if ($elements_id > 0) {
            $elements = tep_db_fetch_array(tep_db_query("select elements_id, elements_name from " . TABLE_ELEMENTS . " where language_id = '" . (int)$languages_id . "' and elements_id = '" . (int)$elements_id . "'"));
            $eInfo = new \objectInfo($elements, false);

            echo tep_draw_form('elements', 'elements', \common\helpers\Output::get_all_get_params(array('eID', 'action')) . 'dID=' . $eInfo->elements_id . '&action=deleteconfirm', 'post', 'id="item_delete" onSubmit="return elementDelete();"');

            echo '<div class="or_box_head">' . $eInfo->elements_name . '</div>';
            echo TEXT_DELETE_INTRO . '<br>';
            echo '<div class="btn-toolbar btn-toolbar-order">';
            echo '<button type="submit" class="btn btn-primary btn-no-margin">' . IMAGE_CONFIRM . '</button>';
            echo '<button class="btn btn-cancel" onClick="return resetStatement(' . (int)$elements_id . ')">' . IMAGE_CANCEL . '</button>';      

            echo tep_draw_hidden_field('elements_id', $elements_id);
            echo '</div></form>';
        }
    }

    public function actionDelete() {
        \common\helpers\Translation::init('admin/elements');

        $elements_id = Yii::$app->request->post('elements_id', 0);

        if ($elements_id) {
            $remove_element = true;
            $error = array();
            $element_query = tep_db_query("select count(*) as count from " . TABLE_PRODUCTS_TO_PCTEMPLATES_TO_ELEMENTS . " where elements_id = '" . (int)$elements_id . "'");
            $element = tep_db_fetch_array($element_query);
            if ($element['count'] > 0) {
                $remove_element = false;
                $error = array('message' => ERROR_ELEMENT_USED_IN_PCTEMPLATES, 'messageType' => 'alert-danger');
            }
            if (!$remove_element) {
                ?>
                <div class="alert fade in <?= $error['messageType'] ?>">
                    <i data-dismiss="alert" class="icon-remove close"></i>
                    <span id="message_plce"><?= $error['message'] ?></span>
                </div>       
                <?php
            } else {
                tep_db_query("delete from " . TABLE_ELEMENTS . " where elements_id = '" . (int)$elements_id . "'");
                tep_db_query("delete from " . TABLE_PRODUCTS_TO_ELEMENTS . " where elements_id = '" . (int)$elements_id . "'");
                tep_db_query("delete from " . TABLE_PRODUCTS_TO_PCTEMPLATES_TO_ELEMENTS . " where elements_id = '" . (int)$elements_id . "'");
                echo 'reset';
            }
        }
    }

    public function actionProducts() {
        $languages_id = \Yii::$app->settings->get('languages_id');
        \common\helpers\Translation::init('admin/categories');

        $currencies = Yii::$container->get('currencies');

        $this->selectedMenu = array('catalog', 'configurator', 'elements');

        $elements_id = Yii::$app->request->get('elements_id', 0);
        $elements = tep_db_fetch_array(tep_db_query("select elements_id, elements_name from " . TABLE_ELEMENTS . " where elements_id = '" . (int) $elements_id . "' and language_id = '" . (int)$languages_id . "'"));
        $eInfo = new \objectInfo($elements, false);

        $this->navigation[]       = array('link' => Yii::$app->urlManager->createUrl('elements/products'), 'title' => sprintf(HEADING_TITLE_EDIT_PRODUCTS, $eInfo->elements_name));
        $this->view->headingTitle = sprintf(HEADING_TITLE_EDIT_PRODUCTS, $eInfo->elements_name);

        $this->view->usePopupMode = false;
        if (Yii::$app->request->isAjax) {
          $this->layout = false;
          $this->view->usePopupMode = true;
        }

        $elementProducts = [];
        $query = tep_db_query("select p.products_id, p.products_status, ".ProductNameDecorator::instance()->listingQueryExpression('pd','')." AS products_name from  " . TABLE_PRODUCTS_TO_ELEMENTS . " p2e, " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p2e.products_id = p.products_id and p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "' and pd.platform_id = '".intval(\common\classes\platform::defaultId())."' and p2e.elements_id = '" . (int)$eInfo->elements_id . "' order by p2e.sort_order, pd.products_name");
        while ($data = tep_db_fetch_array($query)) {
            $product = tep_db_fetch_array(tep_db_query("select products_discount_configurator, products_price_configurator from " . TABLE_PRODUCTS . " where products_id = '" . (int)$data['products_id'] . "'"));
            $elementProducts[] = [
                'products_id' => $data['products_id'],
                'products_name' => $data['products_name'],
                'image' => \common\classes\Images::getImage($data['products_id'], 'Small'),
                'price' => $currencies->format(\common\helpers\Product::get_products_price($data['products_id'])),
                'discount_configurator' => $product['products_discount_configurator'],
                'price_configurator' => ($product['products_price_configurator'] > 0 ? $product['products_price_configurator'] : ''),
                'status_class' => ($data['products_status'] == 0 ? 'dis_prod' : ''),
            ];
        }
        $this->view->elementProducts = $elementProducts;

        return $this->render('products.tpl', ['eInfo' => $eInfo]);
    }

    public function actionProductsUpdate() {
        $elements_id = Yii::$app->request->post('elements_id');
        $element_products_id = Yii::$app->request->post('element_products_id', array());
        $element_products_discount = Yii::$app->request->post('element_products_discount', array());
        $element_products_price = Yii::$app->request->post('element_products_price', array());

        $all_products_array = array();
        foreach ($element_products_id as $sort_order => $products_id) {
            $sql_data_array = array('elements_id' => (int)$elements_id,
                                    'products_id' => (int)$products_id,
                                    'sort_order' => (int)$sort_order);
            $check = tep_db_fetch_array(tep_db_query("select count(*) as element_exists from " . TABLE_PRODUCTS_TO_ELEMENTS . " where elements_id = '" . (int)$elements_id . "' and products_id = '" . (int)$products_id . "'"));
            if ($check['element_exists']) {
                tep_db_perform(TABLE_PRODUCTS_TO_ELEMENTS, $sql_data_array, 'update', "elements_id = '" . (int)$elements_id . "' and products_id = '" . (int)$products_id . "'");
            } else {
                tep_db_perform(TABLE_PRODUCTS_TO_ELEMENTS, $sql_data_array);
            }
            tep_db_query("update " . TABLE_PRODUCTS . " set products_discount_configurator = '" . (float)$element_products_discount[$products_id] . "', products_price_configurator = '" . (float)$element_products_price[$products_id] . "' where products_id = '" . (int)$products_id . "'");
            $all_products_array[] = (int) $products_id;
        }
        tep_db_query("delete from " . TABLE_PRODUCTS_TO_ELEMENTS . " where elements_id = '" . (int)$elements_id . "' and products_id not in ('" . implode("','", $all_products_array) . "')");
        tep_db_query("delete from " . TABLE_PRODUCTS_TO_PCTEMPLATES_TO_ELEMENTS . " where elements_id = '" . (int)$elements_id . "' and products_id not in ('" . implode("','", $all_products_array) . "')");

        if (Yii::$app->request->isAjax) {
//          $this->layout = false;
        } else {
            return $this->redirect(Yii::$app->urlManager->createUrl(['elements/index', 'eID' => $elements_id]));
        }
    }

    public function actionProductSearch() {
        $languages_id = \Yii::$app->settings->get('languages_id');

        $q = Yii::$app->request->get('q');

        $products_string = '';

        $categories = \common\helpers\Categories::get_category_tree(0, '', '0', '', true);
        foreach ($categories as $category) {
            $products_query = tep_db_query("select distinct p.products_id, ".ProductNameDecorator::instance()->listingQueryExpression('pd','')." AS products_name, p.products_status from " . TABLE_PRODUCTS . " p LEFT JOIN " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c ON p.products_id = p2c.products_id LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON p.products_id = pd.products_id where p2c.categories_id = '" . $category['id'] . "' and pd.language_id = '" . (int) $languages_id . "' and p.products_pctemplates_id = '0' and (p.products_model like '%" . tep_db_input($q) . "%' or pd.products_name like '%" . tep_db_input($q) . "%' or pd.products_internal_name like '%" . tep_db_input($q) . "%') group by p.products_id order by p.sort_order, ".ProductNameDecorator::instance()->listingQueryExpression('pd','')." limit 0, 100");
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
        $ret = '';

        $currencies = Yii::$container->get('currencies');

         $ids = array_map('intval', explode(',', trim(Yii::$app->request->post('products_ids', ''), ',')));

        $query = tep_db_query("select p.products_id, ".ProductNameDecorator::instance()->listingQueryExpression('pd','')." AS products_name, p.products_status from " . TABLE_PRODUCTS_DESCRIPTION . " pd," . TABLE_PRODUCTS . " p where language_id = '" . (int)$languages_id . "' and platform_id = '".intval(\common\classes\platform::defaultId())."' and p.products_id in ('" . implode("','", $ids) . "') and p.products_id =  pd.products_id ");
        if (tep_db_num_rows($query) > 0) {
            while ($data = tep_db_fetch_array($query)) {
              if (count($data) > 0) {
                $elementProduct = [
                    'products_id' => $data['products_id'],
                    'products_name' => $data['products_name'],
                    'image' => \common\classes\Images::getImage($data['products_id'], 'Small'),
                    'price' => $currencies->format(\common\helpers\Product::get_products_price($data['products_id'])),
                    'status_class' => ($data['products_status'] == 0 ? 'dis_prod' : ''),
                ];

                $ret .= $this->render('new-product.tpl', [
                            'element' => $elementProduct,
                ]);
              }
            }
        }

        return $ret;

    }

}
