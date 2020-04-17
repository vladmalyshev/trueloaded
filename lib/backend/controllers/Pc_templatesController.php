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

class Pc_templatesController extends Sceleton {

    public $acl = ['BOX_HEADING_CATALOG', 'BOX_CATALOG_CONFIGURATOR', 'BOX_CATALOG_CATEGORIES_PC_TEMPLATES'];

    public function __construct($id, $module=null) {
      Translation::init('admin/pc-templates');
      parent::__construct($id, $module);
    }    

    public function actionIndex() {
        $this->selectedMenu = array('catalog', 'configurator', 'pc_templates');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('pc_templates/index'), 'title' => HEADING_TITLE);
        $this->view->headingTitle = HEADING_TITLE;
        $this->topButtons[] = '<a href="#" class="create_item" onclick="return pc_templateEdit(0)">' . IMAGE_NEW_PC_TEMPLATE . '</a>';

        $this->view->pc_templateTable = array(
            array(
                'title' => TABLE_HEADING_PC_TEMPLATE,
                'not_important' => 0,
            ),
        );

        $messages = $_SESSION['messages'];
        unset($_SESSION['messages']);
        if (!is_array($messages)) $messages = [];

        $tID = Yii::$app->request->get('tID', 0);
        return $this->render('index', array('messages' => $messages, 'tID' => $tID));
    }

    public function actionList() {
        $draw = Yii::$app->request->get('draw', 1);
        $start = Yii::$app->request->get('start', 0);
        $length = Yii::$app->request->get('length', 10);

        $search = '';
        if (isset($_GET['search']['value']) && tep_not_null($_GET['search']['value'])) {
            $keywords = tep_db_input(tep_db_prepare_input($_GET['search']['value']));
            $search .= " and (pctemplates_name like '%" . $keywords . "%')";
        }

        $current_page_number = ($start / $length) + 1;
        $responseList = array();

        if (isset($_GET['order'][0]['column']) && $_GET['order'][0]['dir']) {
            switch ($_GET['order'][0]['column']) {
                case 0:
                    $orderBy = "pctemplates_name " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                    break;
                default:
                    $orderBy = "pctemplates_id";
                    break;
            }
        } else {
            $orderBy = "pctemplates_id";
        }

        $pc_templates_query_raw = "select pctemplates_id, pctemplates_name, pctemplates_image, date_added, last_modified from " . TABLE_PCTEMPLATES . " where 1 " . $search . " order by " . $orderBy;
        $pc_templates_split = new \splitPageResults($current_page_number, $length, $pc_templates_query_raw, $pc_templates_query_numrows);
        $pc_templates_query = tep_db_query($pc_templates_query_raw);

        while ($pc_templates = tep_db_fetch_array($pc_templates_query)) {

            $responseList[] = array(
                $pc_templates['pctemplates_name'] . tep_draw_hidden_field('id', $pc_templates['pctemplates_id'], 'class="cell_identify"'),
            );
        }

        $response = array(
            'draw' => $draw,
            'recordsTotal' => $pc_templates_query_numrows,
            'recordsFiltered' => $pc_templates_query_numrows,
            'data' => $responseList
        );
        echo json_encode($response);
    }

    public function actionStatusactions() {
        \common\helpers\Translation::init('admin/pc-templates');

        $pctemplates_id = Yii::$app->request->post('pctemplates_id', 0);
        $this->layout = false;
        if ($pctemplates_id) {
            $pc_templates = tep_db_fetch_array(tep_db_query("select pctemplates_id, pctemplates_name from " . TABLE_PCTEMPLATES . " where pctemplates_id = '" . (int) $pctemplates_id . "'"));
            $tInfo = new \objectInfo($pc_templates, false);
            $heading = array();
            $contents = array();

            if (is_object($tInfo)) {
                echo '<div class="or_box_head">' . $tInfo->pctemplates_name . '</div>';

                $pc_template_inputs_string = '';
                $languages = \common\helpers\Language::get_languages();
                for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
                  $pc_template_inputs_string .= '<div class="col_desc">' . $languages[$i]['image'] . '&nbsp;' . \common\helpers\Configurator::pctemplates_description($tInfo->pctemplates_id, $languages[$i]['id']) . '</div>';
                }
                echo $pc_template_inputs_string;

                echo '<div class="btn-toolbar btn-toolbar-order">';
                echo '<a class="btn btn-primary btn-process-order btn-edit" href="' . Yii::$app->urlManager->createUrl(['pc_templates/parts', 'pctemplates_id' => $pctemplates_id]) . '">' . IMAGE_TEMPLATES_PARTS . '</a>';
                echo '<button class="btn btn-edit btn-no-margin" onclick="pc_templateEdit(' . $pctemplates_id . ')">' . IMAGE_EDIT . '</button><button class="btn btn-delete" onclick="pc_templateDeleteConfirm(' . $pctemplates_id . ')">' . IMAGE_DELETE . '</button>';
                echo '</div>';
            }

        }
    }

    public function actionEdit() {
        \common\helpers\Translation::init('admin/pc-templates');

        $pctemplates_id = Yii::$app->request->get('pctemplates_id', 0);
        $pc_templates = tep_db_fetch_array(tep_db_query("select pctemplates_id, pctemplates_name from " . TABLE_PCTEMPLATES . " where pctemplates_id = '" . (int) $pctemplates_id . "'"));
        $tInfo = new \objectInfo($pc_templates, false);

        echo tep_draw_form('pc_template', 'pc_templates' . '/save', 'pctemplates_id=' . $tInfo->pctemplates_id, 'post', 'onsubmit="return pc_templateSave(' . ($tInfo->pctemplates_id ? $tInfo->pctemplates_id : 0) . ');"');

        if ($pctemplates_id) {
            echo '<div class="or_box_head">' . TEXT_EDIT_INTRO . '</div>';
        } else {
            echo '<div class="or_box_head">' . TEXT_NEW_INTRO . '</div>';
        }

        echo '<div class="main_row"><div class="main_title">' . TEXT_INFO_PCTEMPLATES_NAME . '</div><div class="main_value">' . tep_draw_input_field('pctemplates_name', $tInfo->pctemplates_name) . '</div></div>';

        $pc_template_inputs_string = '';
        $languages = \common\helpers\Language::get_languages();
        for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
          $pc_template_inputs_string .= '<div class="langInput">' . $languages[$i]['image'] . tep_draw_textarea_field('pctemplates_description[' . $languages[$i]['id'] . ']', 'auto', 24, 2, \common\helpers\Configurator::pctemplates_description($tInfo->pctemplates_id, $languages[$i]['id'])) . '</div>';
        }
        echo '<div class="main_row"><div class="main_title">' . TEXT_INFO_PCTEMPLATES_DESCRIPTION . '</div><div class="main_value">' . $pc_template_inputs_string . '</div></div>';

        echo '<div class="btn-toolbar btn-toolbar-order">';
        if ($pctemplates_id) {
            echo '<input type="submit" value="' . IMAGE_UPDATE . '" class="btn btn-no-margin"><input type="button" value="' . IMAGE_CANCEL . '" class="btn btn-cancel" onclick="resetStatement(' . (int)$tInfo->pctemplates_id . ')">';
        } else {
            echo '<input type="submit" value="' . IMAGE_NEW . '" class="btn btn-no-margin"><input type="button" value="' . IMAGE_CANCEL . '" class="btn btn-cancel" onclick="resetStatement(' . (int)$tInfo->pctemplates_id . ')">';
        }

        echo '</div>';
        echo '</form>';
    }

    public function actionSave() {
        \common\helpers\Translation::init('admin/pc-templates');
        $pctemplates_id = intval(Yii::$app->request->get('pctemplates_id', 0));
        $pctemplates_name = tep_db_prepare_input(Yii::$app->request->post('pctemplates_name', ''));
        $pctemplates_description = tep_db_prepare_input(Yii::$app->request->post('pctemplates_description', array()));

        $sql_data_array = array('pctemplates_name' => $pctemplates_name);

        if ($pctemplates_id > 0) {
            $update_sql_data = array('last_modified' => 'now()');
            $sql_data_array = array_merge($sql_data_array, $update_sql_data);
            tep_db_perform(TABLE_PCTEMPLATES, $sql_data_array, 'update', "pctemplates_id = '" . tep_db_input($pctemplates_id) . "'");
            $action = 'updated';
        } else {
            $insert_sql_data = array('date_added' => 'now()');
            $sql_data_array = array_merge($sql_data_array, $insert_sql_data);
            tep_db_perform(TABLE_PCTEMPLATES, $sql_data_array);
            $pctemplates_id = tep_db_insert_id();
            $action = 'added';
        }

        $languages = \common\helpers\Language::get_languages();
        for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
            $language_id = $languages[$i]['id'];

            $sql_data_array = array('pctemplates_description' => $pctemplates_description[$language_id]);

            $check = tep_db_fetch_array(tep_db_query("select count(pctemplates_id) as pc_templates_exists from " . TABLE_PCTEMPLATES_INFO . " where pctemplates_id = '" . (int)$pctemplates_id . "' and languages_id = '" . (int)$language_id . "'"));
            if (!$check['pc_templates_exists']) {
                $insert_sql_data = array('pctemplates_id' => $pctemplates_id,
                                         'languages_id' => $language_id);
                $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

                tep_db_perform(TABLE_PCTEMPLATES_INFO, $sql_data_array);
            } else {
                tep_db_perform(TABLE_PCTEMPLATES_INFO, $sql_data_array, 'update', "pctemplates_id = '" . (int) $pctemplates_id . "' and languages_id = '" . (int)$language_id . "'");
            }
        }

        echo json_encode(array('message' => 'Template ' . $action, 'messageType' => 'alert-success'));
    }

    public function actionConfirmdelete() {
        $this->layout = false;

        $pctemplates_id = Yii::$app->request->post('pctemplates_id');

        if ($pctemplates_id > 0) {
            $pc_templates = tep_db_fetch_array(tep_db_query("select pctemplates_id, pctemplates_name from " . TABLE_PCTEMPLATES . " where pctemplates_id = '" . (int)$pctemplates_id . "'"));
            $tInfo = new \objectInfo($pc_templates, false);

            echo tep_draw_form('pc_templates', 'pc_templates', \common\helpers\Output::get_all_get_params(array('tID', 'action')) . 'dID=' . $tInfo->pctemplates_id . '&action=deleteconfirm', 'post', 'id="item_delete" onSubmit="return pc_templateDelete();"');

            echo '<div class="or_box_head">' . $tInfo->pctemplates_name . '</div>';
            echo TEXT_DELETE_INTRO . '<br>';
            echo '<div class="btn-toolbar btn-toolbar-order">';
            echo '<button type="submit" class="btn btn-primary btn-no-margin">' . IMAGE_CONFIRM . '</button>';
            echo '<button class="btn btn-cancel" onClick="return resetStatement(' . (int)$pctemplates_id . ')">' . IMAGE_CANCEL . '</button>';      

            echo tep_draw_hidden_field('pctemplates_id', $pctemplates_id);
            echo '</div></form>';
        }
    }

    public function actionDelete() {
        global $language;
        \common\helpers\Translation::init('admin/pc-templates');

        $pctemplates_id = Yii::$app->request->post('pctemplates_id', 0);

        if ($pctemplates_id) {
            $remove_pc_template = true;
            $error = array();
            $pc_template_query = tep_db_query("select count(*) as count from " . TABLE_PRODUCTS . " where pctemplates_id = '" . (int)$pctemplates_id . "'");
            $pc_template = tep_db_fetch_array($pc_template_query);
            if ($pc_template['count'] > 0) {
                $remove_pc_template = false;
                $error = array('message' => ERROR_PC_TEMPLATE_USED_IN_PRODUCTS, 'messageType' => 'alert-danger');
            }
            if (!$remove_pc_template) {
                ?>
                <div class="alert fade in <?= $error['messageType'] ?>">
                    <i data-dismiss="alert" class="icon-remove close"></i>
                    <span id="message_plce"><?= $error['message'] ?></span>
                </div>       
                <?php
            } else {
                tep_db_query("delete from " . TABLE_PCTEMPLATES . " where pctemplates_id = '" . tep_db_input($pctemplates_id) . "'");
                tep_db_query("delete from " . TABLE_PCTEMPLATES_INFO . " where pctemplates_id = '" . tep_db_input($pctemplates_id) . "'");
                tep_db_query("delete from " . TABLE_PRODUCTS_TO_PCTEMPLATES_TO_ELEMENTS . " where pctemplates_id = '" . tep_db_input($pctemplates_id) . "'");
                echo 'reset';
            }
        }
    }

    public function actionParts() {
        $languages_id = \Yii::$app->settings->get('languages_id');
        \common\helpers\Translation::init('admin/categories');

        $currencies = Yii::$container->get('currencies');

        $this->selectedMenu = array('catalog', 'configurator', 'pc_templates');

        $pctemplates_id = Yii::$app->request->get('pctemplates_id', 0);
        $pctemplates = tep_db_fetch_array(tep_db_query("select pctemplates_id, pctemplates_name from " . TABLE_PCTEMPLATES . " where pctemplates_id = '" . (int) $pctemplates_id . "'"));
        $tInfo = new \objectInfo($pctemplates, false);

        $this->navigation[]       = array('link' => Yii::$app->urlManager->createUrl('pc_templates/parts'), 'title' => sprintf(HEADING_TITLE_EDIT_PRODUCTS, $tInfo->pctemplates_name));
        $this->view->headingTitle = sprintf(HEADING_TITLE_EDIT_PRODUCTS, $tInfo->pctemplates_name);

        $this->view->usePopupMode = false;
        if (Yii::$app->request->isAjax) {
          $this->layout = false;
          $this->view->usePopupMode = true;
        }

        $elementsArray = [];
        $elements_query = tep_db_query("select e.elements_id, e.elements_name, e.elements_type, e.is_mandatory, max(ppe.def) as def from " . TABLE_ELEMENTS . " e left join " . TABLE_PRODUCTS_TO_PCTEMPLATES_TO_ELEMENTS . " ppe on ppe.pctemplates_id = '" . (int)$tInfo->pctemplates_id . "' and ppe.elements_id = e.elements_id where e.language_id = '" . (int)$languages_id . "' group by e.elements_id order by e.elements_sortorder, e.elements_name");
        while ($elements = tep_db_fetch_array($elements_query)) {
            $elementsArray[$elements['elements_id']] = $elements;
            $elementsArray[$elements['elements_id']]['products'] = [];
            $r_template_elements = tep_db_query("select ppe.*,  p.products_model, p.products_price, p.products_status, p.products_price_configurator, p.products_discount_configurator, ".ProductNameDecorator::instance()->listingQueryExpression('pd','')." AS products_name from " . TABLE_PRODUCTS_TO_PCTEMPLATES_TO_ELEMENTS . " ppe, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS . " p where p.products_id = ppe.products_id and pd.products_id = p.products_id and pd.language_id = '" . (int)$languages_id  . "' and ppe.pctemplates_id = '" . (int)$tInfo->pctemplates_id . "' and ppe.elements_id = " . (int)$elements['elements_id'] . " and pd.platform_id = '".intval(\common\classes\platform::defaultId())."' order by ppe.sort_order");
            while ($products = tep_db_fetch_array($r_template_elements)) {
                $products['image'] = \common\classes\Images::getImage($products['products_id'], 'Small');
                $products['price'] = $currencies->format($products['products_price_configurator'] > 0 ? $products['products_price_configurator'] : \common\helpers\Product::get_products_price($products['products_id']));
                $products['status_class'] = ($products['products_status'] == 0 ? 'dis_prod' : '');
                $elementsArray[$elements['elements_id']]['products'][$products['products_id']] = $products;
            }
        }
        $this->view->elementsArray = $elementsArray;

        return $this->render('parts.tpl', ['tInfo' => $tInfo]);
    }

    public function actionPartsUpdate() {
        $languages_id = \Yii::$app->settings->get('languages_id');
        $pctemplates_id = Yii::$app->request->post('pctemplates_id');

        $elements_query = tep_db_query("select elements_id from " . TABLE_ELEMENTS . " e where language_id = '" . (int)$languages_id . "' order by elements_sortorder, elements_name");
        while ($elements = tep_db_fetch_array($elements_query)) {
            $elements_id = $elements['elements_id'];
            $pctemplate_products_id = Yii::$app->request->post('pctemplate_' . $elements_id . '_products_id', array());
            $pctemplate_qty_min = Yii::$app->request->post('pctemplate_' . $elements_id . '_qty_min', array());
            $pctemplate_qty_max = Yii::$app->request->post('pctemplate_' . $elements_id . '_qty_max', array());
            $pctemplate_def = Yii::$app->request->post('pctemplate_' . $elements_id . '_def', 0);

            $all_products_array = array();
            foreach ($pctemplate_products_id as $sort_order => $products_id) {
                $sql_data_array = array('pctemplates_id' => (int)$pctemplates_id,
                                        'elements_id' => (int)$elements_id,
                                        'products_id' => (int)$products_id,
                                        'qty_min' => (int)($pctemplate_qty_min[$products_id] > 0 ? $pctemplate_qty_min[$products_id] : 1),
                                        'qty_max' => (int)$pctemplate_qty_max[$products_id],
                                        'def' => ($products_id == $pctemplate_def),
                                        'sort_order' => (int)$sort_order);
                $check = tep_db_fetch_array(tep_db_query("select count(*) as pctemplate_exists from " . TABLE_PRODUCTS_TO_PCTEMPLATES_TO_ELEMENTS . " where pctemplates_id = '" . (int)$pctemplates_id . "' and elements_id = '" . (int)$elements_id . "' and products_id = '" . (int)$products_id . "'"));
                if ($check['pctemplate_exists']) {
                    tep_db_perform(TABLE_PRODUCTS_TO_PCTEMPLATES_TO_ELEMENTS, $sql_data_array, 'update', "pctemplates_id = '" . (int)$pctemplates_id . "' and elements_id = '" . (int)$elements_id . "' and products_id = '" . (int)$products_id . "'");
                } else {
                    tep_db_perform(TABLE_PRODUCTS_TO_PCTEMPLATES_TO_ELEMENTS, $sql_data_array);
                }
                $all_products_array[] = (int) $products_id;
            }
            tep_db_query("delete from " . TABLE_PRODUCTS_TO_PCTEMPLATES_TO_ELEMENTS . " where pctemplates_id = '" . (int)$pctemplates_id . "' and elements_id = '" . (int)$elements_id . "' and products_id not in ('" . implode("','", $all_products_array) . "')");
        }

        if (Yii::$app->request->isAjax) {
//          $this->layout = false;
        } else {
            return $this->redirect(Yii::$app->urlManager->createUrl(['pc_templates/index', 'tID' => $pctemplates_id]));
        }
    }

    public function actionProductSearch() {
        $languages_id = \Yii::$app->settings->get('languages_id');

        $q = Yii::$app->request->get('q');
        $elements_id = (int) Yii::$app->request->get('elements_id');

        $products_string = '';

        $categories = \common\helpers\Categories::get_category_tree(0, '', '0', '', true);
        foreach ($categories as $category) {
            $products_query = tep_db_query("select distinct p.products_id, ".ProductNameDecorator::instance()->listingQueryExpression('pd','')." AS products_name, p.products_status from " . TABLE_PRODUCTS_TO_ELEMENTS . " pte, " . TABLE_PRODUCTS . " p left join " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c on p.products_id = p2c.products_id left join " . TABLE_PRODUCTS_DESCRIPTION . " pd on p.products_id = pd.products_id where pte.elements_id = '" . (int)$elements_id . "' and pte.products_id = p.products_id and p2c.categories_id = '" . (int)$category['id'] . "' and p.products_id != '" . (int) $products_id . "' and pd.language_id = '" . (int) $languages_id . "' and p.products_pctemplates_id = '0' and (p.products_model like '%" . tep_db_input($q) . "%' or pd.products_name like '%" . tep_db_input($q) . "%' or pd.products_internal_name like '%" . tep_db_input($q) . "%') group by p.products_id order by p.sort_order, pd.products_name limit 0, 100");
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

        $elements_id = (int) Yii::$app->request->post('elements_id');
        $ids = array_map('intval', explode(',', trim(Yii::$app->request->post('products_ids', ''), ',')));



        $query = tep_db_query("select p.products_id, ".ProductNameDecorator::instance()->listingQueryExpression('pd','')." AS products_name, p.products_status, p.products_discount_configurator, p.products_price_configurator from " . TABLE_PRODUCTS_TO_ELEMENTS . " pte, " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where pte.elements_id = '" . (int)$elements_id . "' and pte.products_id = p.products_id and p.products_id in ('" . implode("','", $ids) . "') and p.products_id =  pd.products_id and pd.language_id = '" . (int)$languages_id . "' and pd.platform_id = '".intval(\common\classes\platform::defaultId())."'");
        if (tep_db_num_rows($query) > 0) {
            while ($data = tep_db_fetch_array($query)) {
              $product = [
                  'products_id' => $data['products_id'],
                  'products_name' => $data['products_name'],
                  'image' => \common\classes\Images::getImage($data['products_id'], 'Small'),
                  'price' => $currencies->format($data['products_price_configurator'] > 0 ? $product['products_price_configurator'] : \common\helpers\Product::get_products_price($data['products_id'])),
                  'discount_configurator' => $data['products_discount_configurator'],
                  'status_class' => ($data['products_status'] == 0 ? 'dis_prod' : ''),
              ];

              $ret .= $this->render('new-product.tpl', [
                          'elements_id' => $elements_id,
                          'product' => $product,
              ]);
            }
        } 

        return $ret;

    }

}
