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

class PdfCataloguesController extends Sceleton
{

    public $acl = ['BOX_HEADING_CATALOG', 'BOX_CATALOG_PDF_CATALOGUES'];

    public function __construct($id, $module = NULL)
    {
        Translation::init('admin/pdf-catalogues');
        parent::__construct($id, $module);
    }

    public function actionIndex()
    {
        $this->selectedMenu = array('catalog', 'pdf-catalogues');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('pdf-catalogues/index'), 'title' => HEADING_TITLE);
        $this->view->headingTitle = HEADING_TITLE;
        $this->topButtons[] = '<a href="' . Yii::$app->urlManager->createUrl('pdf-catalogues/new') . '" class="create_item js-new-pdf-catalogue-popup">' . IMAGE_NEW_PDF_CATALOGUE . '</a>';

        $this->view->pdf_catalogueTable = array(
            array(
                'title' => TABLE_HEADING_PDF_CATALOGUE,
                'not_important' => 0,
            ),
            array(
                'title' => TABLE_HEADING_PRODUCTS_COUNT,
                'not_important' => 0,
            ),
        );

        $messages = $_SESSION['messages'];
        unset($_SESSION['messages']);

        $pcID = Yii::$app->request->get('pcID', 0);

        return $this->render('index', array('messages' => $messages, 'pcID' => $pcID));
    }

    public function actionList()
    {
        $draw = Yii::$app->request->get('draw', 1);
        $start = Yii::$app->request->get('start', 0);
        $length = Yii::$app->request->get('length', 10);

        $search = '';
        if (isset($_GET['search']['value']) && tep_not_null($_GET['search']['value']))
        {
            $keywords = tep_db_input(tep_db_prepare_input($_GET['search']['value']));
            $search .= " and (ot.pdf_catalogues_name like '%" . $keywords . "%')";
        }

        $current_page_number = ($start / $length) + 1;
        $responseList = array();

        if (isset($_GET['order'][0]['column']) && $_GET['order'][0]['dir'])
        {
            switch ($_GET['order'][0]['column'])
            {
                case 0:
                    $orderBy = "ot.pdf_catalogues_name " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                    break;
                default:
                    $orderBy = "ot.pdf_catalogues_id";
                    break;
            }
        }
        else
        {
            $orderBy = "ot.pdf_catalogues_id";
        }

        $pdf_catalogues_query_raw = "select ot.pdf_catalogues_id, ot.pdf_catalogues_name, ot.date_added, ot.last_modified, count(p2ot.products_id) as products_count from " . TABLE_PDF_CATALOGUES . " ot left join " . TABLE_PDF_CATALOGUES_TO_PRODUCTS . " p2ot on ot.pdf_catalogues_id = p2ot.pdf_catalogues_id where 1 " . $search . " group by ot.pdf_catalogues_id order by " . $orderBy;
        $pdf_catalogues_split = new \splitPageResults($current_page_number, $length, $pdf_catalogues_query_raw, $pdf_catalogues_query_numrows, 'ot.pdf_catalogues_id');
        $pdf_catalogues_query = tep_db_query($pdf_catalogues_query_raw);

        while ($pdf_catalogues = tep_db_fetch_array($pdf_catalogues_query))
        {

            $responseList[] = array(
                $pdf_catalogues['pdf_catalogues_name'] . tep_draw_hidden_field('id', $pdf_catalogues['pdf_catalogues_id'], 'class="cell_identify"'),
                $pdf_catalogues['products_count'],
            );
        }

        $response = array(
            'draw' => $draw,
            'recordsTotal' => $pdf_catalogues_query_numrows,
            'recordsFiltered' => $pdf_catalogues_query_numrows,
            'data' => $responseList,
        );
        echo json_encode($response);
    }

    public function actionStatusactions()
    {

        $pdf_catalogues_id = Yii::$app->request->post('pdf_catalogues_id', 0);
        $this->layout = FALSE;
        if ($pdf_catalogues_id)
        {
            $pdf_catalogues = tep_db_fetch_array(tep_db_query("select pdf_catalogues_id, pdf_catalogues_name from " . TABLE_PDF_CATALOGUES . " where pdf_catalogues_id = '" . (int)$pdf_catalogues_id . "'"));
            $pcInfo = new \objectInfo($pdf_catalogues, FALSE);
            $heading = array();
            $contents = array();

            if (is_object($pcInfo))
            {
                echo '<div class="or_box_head">' . $pcInfo->pdf_catalogues_name . '</div>';

                echo '<div class="btn-toolbar btn-toolbar-order">';

                echo '<a href="' . Yii::$app->urlManager->createUrl(['pdf-catalogues/edit-catalog', 'pdf_catalogues_id' => $pdf_catalogues_id]) . '" class="btn btn-edit btn-process-order js-open-tree-popup">' . BUTTON_ASSIGN_PRODUCTS . '</a>';
                echo '<a class="btn btn-primary btn-process-order btn-edit" href="' . Yii::$app->urlManager->createUrl(['pdf-catalogues/products', 'pdf_catalogues_id' => $pdf_catalogues_id]) . '">' . FIELDSET_ASSIGNED_PRODUCTS . '</a>';

                echo '<a class="btn btn-primary btn-process-order" target="_blank" href="' . Yii::$app->urlManager->createUrl(['pdf-catalogues/generate-pdf-catalogue', 'pdf_catalogues_id' => $pdf_catalogues_id]) . '">' . BUTTON_GENERATE_PDF_CATALOGUE . '</a>';
                $brochureName = 'brochure/' . ($pcInfo->pdf_catalogues_name ? $pcInfo->pdf_catalogues_name : date('F Y') . ' Catalogue') . '.pdf';
                if (file_exists(DIR_FS_CATALOG . $brochureName)) {
                    echo '<a class="btn btn-primary btn-process-order" target="_blank" href="' . tep_catalog_href_link($brochureName) . '">' . BUTTON_DOWNLOAD_PDF_CATALOGUE . '</a>';
                }
                echo '<button class="btn btn-edit btn-no-margin" onclick="pdf_catalogueEdit(' . $pdf_catalogues_id . ')">' . IMAGE_EDIT . '</button><button class="btn btn-delete" onclick="pdf_catalogueDeleteConfirm(' . $pdf_catalogues_id . ')">' . IMAGE_DELETE . '</button>';
                echo '</div>';
            }

        }
    }

    public function actionNew()
    {
        $this->layout = FALSE;

        return $this->render('new');
    }

    public function actionEdit()
    {

        $pdf_catalogues_id = Yii::$app->request->get('pdf_catalogues_id', 0);
        $pdf_catalogues = tep_db_fetch_array(tep_db_query("select pdf_catalogues_id, pdf_catalogues_name, show_out_of_stock, show_product_link from " . TABLE_PDF_CATALOGUES . " where pdf_catalogues_id = '" . (int)$pdf_catalogues_id . "'"));
        $pcInfo = new \objectInfo($pdf_catalogues, FALSE);

        echo tep_draw_form('pdf_catalogue', 'pdf-catalogues/save', 'pdf_catalogues_id=' . $pcInfo->pdf_catalogues_id, 'post', 'onsubmit="return pdf_catalogueSave(' . ($pcInfo->pdf_catalogues_id ? $pcInfo->pdf_catalogues_id : 0) . ');"');

        if ($pdf_catalogues_id)
        {
            echo '<div class="or_box_head">' . TEXT_EDIT_INTRO . '</div>';
        }
        else
        {
            echo '<div class="or_box_head">' . TEXT_NEW_INTRO . '</div>';
        }

        echo '<div class="main_row"><div class="main_title">' . TEXT_INFO_PDF_CATALOGUES_NAME . '</div><div class="main_value">' . tep_draw_input_field('pdf_catalogues_name', $pcInfo->pdf_catalogues_name) . '</div></div>';

        echo '<div class="check_linear"><label>' . tep_draw_checkbox_field('show_out_of_stock', '1', $pcInfo->show_out_of_stock) . '<span>' . TEXT_SHOW_OUT_OF_STOCK . '</span><label></div>';
        echo '<div class="check_linear"><label>' . tep_draw_checkbox_field('show_product_link', '1', $pcInfo->show_product_link) . '<span>' . TEXT_SHOW_PRODUCT_LINK . '</span><label></div>';

        echo '<div class="btn-toolbar btn-toolbar-order">';
        if ($pdf_catalogues_id)
        {
            echo '<input type="submit" value="' . IMAGE_UPDATE . '" class="btn btn-no-margin"><input type="button" value="' . IMAGE_CANCEL . '" class="btn btn-cancel" onclick="resetStatement(' . (int)$pcInfo->pdf_catalogues_id . ')">';
        }
        else
        {
            echo '<input type="submit" value="' . IMAGE_NEW . '" class="btn btn-no-margin"><input type="button" value="' . IMAGE_CANCEL . '" class="btn btn-cancel" onclick="resetStatement(' . (int)$pcInfo->pdf_catalogues_id . ')">';
        }

        echo '</div>';
        echo '</form>';
    }

    public function actionSave()
    {

        $pdf_catalogues_id = intval(Yii::$app->request->get('pdf_catalogues_id', 0));
        $pdf_catalogues_name = tep_db_prepare_input(Yii::$app->request->post('pdf_catalogues_name', ''));
        $show_out_of_stock = tep_db_prepare_input(Yii::$app->request->post('show_out_of_stock', 0));
        $show_product_link = tep_db_prepare_input(Yii::$app->request->post('show_product_link', 0));
        if (trim($pdf_catalogues_name) == '')
        {
            $pdf_catalogues_name = IMAGE_NEW_PDF_CATALOGUE;
        }

        $sql_data_array = array('pdf_catalogues_name' => $pdf_catalogues_name,
                                'show_out_of_stock' => $show_out_of_stock,
                                'show_product_link' => $show_product_link,
                                'is_generated' => 0);

        if ($pdf_catalogues_id > 0)
        {
            $update_sql_data = array('last_modified' => 'now()');
            $sql_data_array = array_merge($sql_data_array, $update_sql_data);
            tep_db_perform(TABLE_PDF_CATALOGUES, $sql_data_array, 'update', "pdf_catalogues_id = '" . tep_db_input($pdf_catalogues_id) . "'");
            $action = 'updated';
        }
        else
        {
            $insert_sql_data = array('date_added' => 'now()');
            $sql_data_array = array_merge($sql_data_array, $insert_sql_data);
            tep_db_perform(TABLE_PDF_CATALOGUES, $sql_data_array);
            $pdf_catalogues_id = tep_db_insert_id();
            $action = 'added';
        }

        echo json_encode(array('message' => 'Catalogue ' . $action, 'messageType' => 'alert-success', 'id' => $pdf_catalogues_id));
    }

    public function actionConfirmdelete()
    {

        $this->layout = FALSE;

        $pdf_catalogues_id = Yii::$app->request->post('pdf_catalogues_id');

        if ($pdf_catalogues_id > 0)
        {
            $pdf_catalogues = tep_db_fetch_array(tep_db_query("select pdf_catalogues_id, pdf_catalogues_name from " . TABLE_PDF_CATALOGUES . " where pdf_catalogues_id = '" . (int)$pdf_catalogues_id . "'"));
            $pcInfo = new \objectInfo($pdf_catalogues, FALSE);

            echo tep_draw_form('pdf_catalogues', 'pdf-catalogues', \common\helpers\Output::get_all_get_params(array('pcID', 'action')) . 'dID=' . $pcInfo->pdf_catalogues_id . '&action=deleteconfirm', 'post', 'id="item_delete" onSubmit="return pdf_catalogueDelete();"');

            echo '<div class="or_box_head">' . $pcInfo->pdf_catalogues_name . '</div>';
            echo TEXT_DELETE_INTRO . '<br>';
            echo '<div class="btn-toolbar btn-toolbar-order">';
            echo '<button type="submit" class="btn btn-primary btn-no-margin">' . IMAGE_CONFIRM . '</button>';
            echo '<button class="btn btn-cancel" onClick="return resetStatement(' . (int)$pdf_catalogues_id . ')">' . IMAGE_CANCEL . '</button>';

            echo tep_draw_hidden_field('pdf_catalogues_id', $pdf_catalogues_id);
            echo '</div></form>';
        }
    }

    public function actionDelete()
    {
        global $language;

        $pdf_catalogues_id = Yii::$app->request->post('pdf_catalogues_id', 0);

        if ($pdf_catalogues_id)
        {
            tep_db_query("delete from " . TABLE_PDF_CATALOGUES . " where pdf_catalogues_id = '" . tep_db_input($pdf_catalogues_id) . "'");
            tep_db_query("delete from " . TABLE_PDF_CATALOGUES_TO_PRODUCTS . " where pdf_catalogues_id = '" . tep_db_input($pdf_catalogues_id) . "'");

            echo 'reset';
        }
    }

    public function actionEditCatalog()
    {
        $this->layout = FALSE;

        $pdf_catalogues_id = (int)Yii::$app->request->get('pdf_catalogues_id');

        $assigned = $this->get_assigned_catalog($pdf_catalogues_id, TRUE);

        $tree_init_data = $this->load_tree_slice($pdf_catalogues_id, 0);
        foreach ($tree_init_data as $_idx => $_data)
        {
            if (isset($assigned[$_data['key']]))
            {
                $tree_init_data[$_idx]['selected'] = TRUE;
            }
        }

        $selected_data = json_encode($assigned);

        return $this->render('edit-catalog.tpl', [
            'pdf_catalogues_id' => $pdf_catalogues_id,
            'selected_data' => $selected_data,
            'tree_data' => $tree_init_data,
            'tree_server_url' => Yii::$app->urlManager->createUrl(['pdf-catalogues/load-tree', 'pdf_catalogues_id' => $pdf_catalogues_id]),
            'tree_server_save_url' => Yii::$app->urlManager->createUrl(['pdf-catalogues/update-catalog-selection', 'pdf_catalogues_id' => $pdf_catalogues_id]),
        ]);
    }

    private function get_assigned_catalog($pdf_catalogues_id, $validate = FALSE)
    {
        $languages_id = \Yii::$app->settings->get('languages_id');
        $assigned = array();
        if ($validate)
        {
            $get_assigned_r = tep_db_query(
                "SELECT p2ot.products_id AS id, p2c.categories_id as cid " .
                "FROM " . TABLE_PDF_CATALOGUES_TO_PRODUCTS . " p2ot, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c, " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd " .
                "WHERE p2ot.pdf_catalogues_id = '" . intval($pdf_catalogues_id) . "' and p2ot.products_id = p2c.products_id AND p.products_id = p2ot.products_id " .
                (TRUE ? " AND p.products_status = 1 " . \common\helpers\Product::get_sql_product_restrictions(array('p', 'pd', 's', 'sp', 'pp')) . " " : "") .
                " AND pd.products_id = p.products_id AND pd.language_id = '" . $languages_id . "' AND pd.platform_id = '".intval(\common\classes\platform::defaultId())."' "
            );
        }
        else
        {
            $get_assigned_r = tep_db_query(
                "SELECT p2ot.products_id AS id, p2c.categories_id as cid " .
                "FROM " . TABLE_PDF_CATALOGUES_TO_PRODUCTS . " p2ot, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c " .
                "WHERE p2ot.pdf_catalogues_id = '" . intval($pdf_catalogues_id) . "' and p2ot.products_id=p2c.products_id "
            );
        }
        if (tep_db_num_rows($get_assigned_r) > 0)
        {
            while ($_assigned = tep_db_fetch_array($get_assigned_r))
            {
                $_key = 'p' . (int)$_assigned['id'] . '_' . $_assigned['cid'];
                $assigned[$_key] = $_key;
            }
        }

        if ($validate)
        {
            $get_assigned_r = tep_db_query(
                "SELECT DISTINCT pc.categories_id AS id " .
                "FROM " . TABLE_PDF_CATALOGUES_TO_CATEGORIES . " pc, " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd " .
                "WHERE pc.pdf_catalogues_id = '" . intval($pdf_catalogues_id) . "' " .
                " AND c.categories_id = pc.categories_id " .
                " AND cd.categories_id = c.categories_id AND cd.language_id = '" . $languages_id . "' AND cd.affiliate_id = 0 "
            );
        }
        else
        {
            $get_assigned_r = tep_db_query(
                "SELECT categories_id AS id " .
                "FROM " . TABLE_PDF_CATALOGUES_TO_CATEGORIES . " " .
                "WHERE pdf_catalogues_id = '" . intval($pdf_catalogues_id) . "' "
            );
        }
        if (tep_db_num_rows($get_assigned_r) > 0)
        {
            while ($_assigned = tep_db_fetch_array($get_assigned_r))
            {
                $assigned['c' . (int)$_assigned['id']] = 'c' . (int)$_assigned['id'];
            }
        }

        return $assigned;
    }

    private function load_tree_slice($pdf_catalogues_id, $category_id)
    {
        $languages_id = \Yii::$app->settings->get('languages_id');
        $tree_init_data = array();

        $get_categories_r = tep_db_query(
            "SELECT CONCAT('c',c.categories_id) as `key`, cd.categories_name as title " .
            "FROM " . TABLE_CATEGORIES_DESCRIPTION . " cd, " . TABLE_CATEGORIES . " c " .
            "WHERE cd.categories_id = c.categories_id and cd.language_id = '" . $languages_id . "' AND cd.affiliate_id = 0 and c.parent_id='" . (int)$category_id . "' " .
            "order by c.sort_order, cd.categories_name"
        );
        while ($_categories = tep_db_fetch_array($get_categories_r))
        {
            //$_categories['parent'] = (int)$category_id;
            $_categories['folder'] = TRUE;
            $_categories['lazy'] = TRUE;
            $_categories['selected'] = 0;
            $tree_init_data[] = $_categories;
        }
        $get_products_r = tep_db_query(
            "SELECT concat('p',p.products_id,'_',p2c.categories_id) AS `key`, pd.products_name as title " .
            "from " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c, " . TABLE_PRODUCTS . " p " .
            "WHERE pd.products_id = p.products_id and pd.language_id = '" . $languages_id . "' and pd.platform_id = '".intval(\common\classes\platform::defaultId())."' and p2c.products_id = p.products_id and p2c.categories_id = '" . (int)$category_id . "' " .
            (TRUE ? " AND p.products_status = 1 " . \common\helpers\Product::get_sql_product_restrictions(array('p', 'pd', 's', 'sp', 'pp')) . " " : "") .
            (tep_not_null($search) ? " and (pd.products_name like '%{$search}%' or pd.products_internal_name like '%{$search}%') " : "") .
            "order by p.sort_order, pd.products_name"
        );
        if (tep_db_num_rows($get_products_r) > 0)
        {
            while ($_product = tep_db_fetch_array($get_products_r))
            {
                //$_product['parent'] = (int)$category_id;
                $_product['selected'] = !!$_product['selected'];
                $tree_init_data[] = $_product;
            }
        }

        return $tree_init_data;
    }

    private function get_category_children(&$children, $pdf_catalogues_id, $categories_id)
    {
        if (!is_array($children))
        {
            $children = array();
        }
        foreach ($this->load_tree_slice($pdf_catalogues_id, $categories_id) as $item)
        {
            $key = $item['key'];
            $children[] = $key;
            if ($item['folder'])
            {
                $this->get_category_children($children, $pdf_catalogues_id, intval(substr($item['key'], 1)));
            }
        }
    }

    public function actionLoadTree()
    {
        $this->layout = FALSE;

        $pdf_catalogues_id = (int)Yii::$app->request->get('pdf_catalogues_id');
        $do = Yii::$app->request->post('do', '');

        $response_data = array();

        if ($do == 'missing_lazy')
        {
            $category_id = Yii::$app->request->post('id');
            $selected = Yii::$app->request->post('selected');
            $req_selected_data = tep_db_prepare_input(Yii::$app->request->post('selected_data'));
            $selected_data = json_decode($req_selected_data, TRUE);
            if (!is_array($selected_data))
            {
                $selected_data = json_decode($selected_data, TRUE);
            }

            if (substr($category_id, 0, 1) == 'c')
            {
                $category_id = intval(substr($category_id, 1));
            }

            $response_data['tree_data'] = $this->load_tree_slice($pdf_catalogues_id, $category_id);
            foreach ($response_data['tree_data'] as $_idx => $_data)
            {
                $response_data['tree_data'][$_idx]['selected'] = isset($selected_data[$_data['key']]);
            }
            $response_data = $response_data['tree_data'];
        }

        if ($do == 'update_selected')
        {
            $id = Yii::$app->request->post('id');
            $selected = Yii::$app->request->post('selected');
            $select_children = Yii::$app->request->post('select_children');
            $req_selected_data = tep_db_prepare_input(Yii::$app->request->post('selected_data'));
            $selected_data = json_decode($req_selected_data, TRUE);
            if (!is_array($selected_data))
            {
                $selected_data = json_decode($selected_data, TRUE);
            }

            if (substr($id, 0, 1) == 'p')
            {
                list($ppid, $cat_id) = explode('_', $id, 2);
                if ($selected)
                {
                    // check parent categories
                    $parent_ids = array((int)$cat_id);
                    \common\helpers\Categories::get_parent_categories($parent_ids, $parent_ids[0], FALSE);
                    foreach ($parent_ids as $parent_id)
                    {
                        if (!isset($selected_data['c' . (int)$parent_id]))
                        {
                            $response_data['update_selection']['c' . (int)$parent_id] = TRUE;
                            $selected_data['c' . (int)$parent_id] = 'c' . (int)$parent_id;
                        }
                    }
                    if (!isset($selected_data[$id]))
                    {
                        $response_data['update_selection'][$id] = TRUE;
                        $selected_data[$id] = $id;
                    }
                }
                else
                {
                    if (isset($selected_data[$id]))
                    {
                        $response_data['update_selection'][$id] = FALSE;
                        unset($selected_data[$id]);
                    }
                }
            }
            elseif (substr($id, 0, 1) == 'c')
            {
                $cat_id = (int)substr($id, 1);
                if ($selected)
                {
                    $parent_ids = array((int)$cat_id);
                    \common\helpers\Categories::get_parent_categories($parent_ids, $parent_ids[0], FALSE);
                    foreach ($parent_ids as $parent_id)
                    {
                        if (!isset($selected_data['c' . (int)$parent_id]))
                        {
                            $response_data['update_selection']['c' . (int)$parent_id] = TRUE;
                            $selected_data['c' . (int)$parent_id] = 'c' . (int)$parent_id;
                        }
                    }
                    if ($select_children)
                    {
                        $children = array();
                        $this->get_category_children($children, $pdf_catalogues_id, $cat_id);
                        foreach ($children as $child_key)
                        {
                            if (!isset($selected_data[$child_key]))
                            {
                                $response_data['update_selection'][$child_key] = TRUE;
                                $selected_data[$child_key] = $child_key;
                            }
                        }
                    }
                    if (!isset($selected_data[$id]))
                    {
                        $response_data['update_selection'][$id] = TRUE;
                        $selected_data[$id] = $id;
                    }
                }
                else
                {
                    $children = array();
                    $this->get_category_children($children, $pdf_catalogues_id, $cat_id);
                    foreach ($children as $child_key)
                    {
                        if (isset($selected_data[$child_key]))
                        {
                            $response_data['update_selection'][$child_key] = FALSE;
                            unset($selected_data[$child_key]);
                        }
                    }
                    if (isset($selected_data[$id]))
                    {
                        $response_data['update_selection'][$id] = FALSE;
                        unset($selected_data[$id]);
                    }
                }
            }

            $response_data['selected_data'] = $selected_data;
        }

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->data = $response_data;
    }

    function actionUpdateCatalogSelection()
    {
        \common\helpers\Translation::init('admin/platforms');
        $this->layout = FALSE;

        $pdf_catalogues_id = Yii::$app->request->get('pdf_catalogues_id');
        $req_selected_data = tep_db_prepare_input(Yii::$app->request->post('selected_data'));
        $selected_data = json_decode($req_selected_data, TRUE);
        if (!is_array($selected_data))
        {
            $selected_data = json_decode($selected_data, TRUE);
        }
        if (!isset($selected_data['c0']))
        {
            $selected_data['c0'] = 'c0';
        }

        $assigned = $this->get_assigned_catalog($pdf_catalogues_id);
        $assigned_products = array();
        foreach ($assigned as $assigned_key)
        {
            if (substr($assigned_key, 0, 1) == 'p')
            {
                $pid = intval(substr($assigned_key, 1));
                $assigned_products[$pid] = $pid;
                unset($assigned[$assigned_key]);
            }
        }
        if (is_array($selected_data))
        {
            $selected_products = array();
            foreach ($selected_data as $selection)
            {
                if (substr($selection, 0, 1) == 'p')
                {
                    $pid = intval(substr($selection, 1));
                    $selected_products[$pid] = $pid;
                    continue;
                }

                if (isset($assigned[$selection]))
                {
                    unset($assigned[$selection]);
                }
                else
                {
                    if (substr($selection, 0, 1) == 'c')
                    {
                        $cat_id = (int)substr($selection, 1);
                        tep_db_perform(TABLE_PDF_CATALOGUES_TO_CATEGORIES, array(
                            'pdf_catalogues_id' => $pdf_catalogues_id,
                            'categories_id' => $cat_id,
                        ));
                        unset($assigned[$selection]);
                    }
                }

            }
            foreach ($selected_products as $pid)
            {
                if (isset($assigned_products[$pid]))
                {
                    unset($assigned_products[$pid]);
                }
                else
                {
                    tep_db_perform(TABLE_PDF_CATALOGUES_TO_PRODUCTS, array(
                        'pdf_catalogues_id' => $pdf_catalogues_id,
                        'products_id' => $pid,
                    ));
                }
            }
        }

        foreach ($assigned as $clean_key)
        {
            if (substr($clean_key, 0, 1) == 'c')
            {
                $cat_id = (int)substr($clean_key, 1);
                if ($cat_id == 0)
                {
                    continue;
                }
                tep_db_query(
                    "DELETE FROM " . TABLE_PDF_CATALOGUES_TO_CATEGORIES . " " .
                    "WHERE pdf_catalogues_id = '" . (int)$pdf_catalogues_id . "' AND categories_id = '" . (int)$cat_id . "' "
                );
                unset($assigned[$clean_key]);
            }
        }

        foreach ($assigned_products as $assigned_product_id)
        {
            tep_db_query("delete from " . TABLE_PDF_CATALOGUES_TO_PRODUCTS . " where pdf_catalogues_id = '" . (int)$pdf_catalogues_id . "' and products_id = '" . (int)$assigned_product_id . "'");
        }

        tep_db_query("update " . TABLE_PDF_CATALOGUES . " set is_generated = '0' where pdf_catalogues_id = '" . (int)$pdf_catalogues_id . "'");

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->data = array(
            'status' => 'ok',
        );
    }

    public function actionGeneratePdfCatalogue()
    {
        $pdf_catalogues_id = Yii::$app->request->get('pdf_catalogues_id', 0);
        $brochureName = \backend\models\EP\Provider\PdfCatalogues\PdfCatalogGen::generatePdfCatalogue($pdf_catalogues_id);
        if (tep_not_null($brochureName)) {
            tep_redirect(tep_catalog_href_link($brochureName, 't='. time()));
        }
    }

    public function actionProducts() {
        $languages_id = \Yii::$app->settings->get('languages_id');
        \common\helpers\Translation::init('admin/categories');

        $currencies = Yii::$container->get('currencies');

        $this->selectedMenu = array('catalog', 'pdf-catalogues');

        $pdf_catalogues_id = Yii::$app->request->get('pdf_catalogues_id', 0);
        $pdf_catalogues = tep_db_fetch_array(tep_db_query("select pdf_catalogues_id, pdf_catalogues_name from " . TABLE_PDF_CATALOGUES . " where pdf_catalogues_id = '" . (int) $pdf_catalogues_id . "'"));
        $pcInfo = new \objectInfo($pdf_catalogues, false);

        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('pdf-catalogues/products'), 'title' => sprintf(HEADING_TITLE_EDIT_PRODUCTS, $pcInfo->pdf_catalogues_name));
        $this->view->headingTitle = sprintf(HEADING_TITLE_EDIT_PRODUCTS, $pcInfo->pdf_catalogues_name);

        $this->view->usePopupMode = false;
        if (Yii::$app->request->isAjax) {
          $this->layout = false;
          $this->view->usePopupMode = true;
        }

        $pdf_catalogueProducts = [];
        $query = tep_db_query("select p.products_id, p.products_model, p.products_status, ".ProductNameDecorator::instance()->listingQueryExpression('pd','')." AS products_name from  " . TABLE_PDF_CATALOGUES_TO_PRODUCTS . " pc2p, " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where pc2p.products_id = p.products_id and p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "' and pd.platform_id = '".intval(\common\classes\platform::defaultId())."' and pc2p.pdf_catalogues_id = '" . (int)$pcInfo->pdf_catalogues_id . "' order by pd.products_name");
        while ($data = tep_db_fetch_array($query)) {
            $pdf_catalogueProducts[] = [
                'products_id' => $data['products_id'],
                'products_name' => $data['products_name'],
                'products_model' => $data['products_model'],
                'image' => \common\classes\Images::getImage($data['products_id'], 'Small'),
                'price' => $currencies->format(\common\helpers\Product::get_products_price($data['products_id'])),
                'status_class' => ($data['products_status'] == 0 ? 'dis_prod' : ''),
            ];
        }
        $this->view->pdf_catalogueProducts = $pdf_catalogueProducts;

        return $this->render('products.tpl', ['pcInfo' => $pcInfo]);
    }

    public function actionProductsUpdate() {
        $pdf_catalogues_id = Yii::$app->request->post('pdf_catalogues_id');
        $pdf_catalogue_products_id = Yii::$app->request->post('pdf_catalogue_products_id', array());

        $all_products_array = array();
        foreach ($pdf_catalogue_products_id as $products_id) {
            $sql_data_array = array('pdf_catalogues_id' => (int)$pdf_catalogues_id,
                                    'products_id' => (int)$products_id);
            $check = tep_db_fetch_array(tep_db_query("select count(*) as pdf_catalogue_exists from " . TABLE_PDF_CATALOGUES_TO_PRODUCTS . " where pdf_catalogues_id = '" . (int)$pdf_catalogues_id . "' and products_id = '" . (int)$products_id . "'"));
            if ($check['pdf_catalogue_exists']) {
                tep_db_perform(TABLE_PDF_CATALOGUES_TO_PRODUCTS, $sql_data_array, 'update', "pdf_catalogues_id = '" . (int)$pdf_catalogues_id . "' and products_id = '" . (int)$products_id . "'");
            } else {
                tep_db_perform(TABLE_PDF_CATALOGUES_TO_PRODUCTS, $sql_data_array);
            }
            $all_products_array[] = (int) $products_id;

            $product_category = tep_db_fetch_array(tep_db_query("select p2c.categories_id from " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c, " . TABLE_CATEGORIES . " c where p2c.products_id = '" . (int)$products_id . "' and c.categories_id = p2c.categories_id order by c.categories_status desc limit 1"));
            $categories_array = array($product_category['categories_id']);
            \common\helpers\Categories::get_parent_categories($categories_array, $product_category['categories_id'], false);
            foreach ($categories_array as $categories_id) {
                $sql_data_array = array('pdf_catalogues_id' => (int)$pdf_catalogues_id,
                                        'categories_id' => (int)$categories_id);
                $check = tep_db_fetch_array(tep_db_query("select count(*) as pdf_catalogue_exists from " . TABLE_PDF_CATALOGUES_TO_CATEGORIES . " where pdf_catalogues_id = '" . (int)$pdf_catalogues_id . "' and categories_id = '" . (int)$categories_id . "'"));
                if ($check['pdf_catalogue_exists']) {
                    tep_db_perform(TABLE_PDF_CATALOGUES_TO_CATEGORIES, $sql_data_array, 'update', "pdf_catalogues_id = '" . (int)$pdf_catalogues_id . "' and categories_id = '" . (int)$categories_id . "'");
                } else {
                    tep_db_perform(TABLE_PDF_CATALOGUES_TO_CATEGORIES, $sql_data_array);
                }
            }
        }
        tep_db_query("delete from " . TABLE_PDF_CATALOGUES_TO_PRODUCTS . " where pdf_catalogues_id = '" . (int)$pdf_catalogues_id . "' and products_id not in ('" . implode("','", $all_products_array) . "')");

        tep_db_query("update " . TABLE_PDF_CATALOGUES . " set is_generated = '0' where pdf_catalogues_id = '" . (int)$pdf_catalogues_id . "'");

        if (Yii::$app->request->isAjax) {
//          $this->layout = false;
        } else {
            return $this->redirect(Yii::$app->urlManager->createUrl(['pdf-catalogues/index', 'pcID' => $pdf_catalogues_id]));
        }
    }

    public function actionProductSearch() {
        $languages_id = \Yii::$app->settings->get('languages_id');

        $q = Yii::$app->request->get('q');

        $products_string = '';

        $categories = \common\helpers\Categories::get_category_tree(0, '', '0', '', true);
        foreach ($categories as $category) {
            $products_query = tep_db_query("select distinct p.products_id, p.products_model, ".ProductNameDecorator::instance()->listingQueryExpression('pd','')." AS products_name, p.products_status from " . TABLE_PRODUCTS . " p LEFT JOIN " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c ON p.products_id = p2c.products_id LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON p.products_id = pd.products_id where p2c.categories_id = '" . $category['id'] . "' and pd.language_id = '" . (int) $languages_id . "' and (p.products_model like '%" . tep_db_input($q) . "%' or pd.products_name like '%" . tep_db_input($q) . "%' or pd.products_internal_name like '%" . tep_db_input($q) . "%') group by p.products_id order by p.sort_order, ".ProductNameDecorator::instance()->listingQueryExpression('pd','')." limit 0, 100");
            if (tep_db_num_rows($products_query) > 0) {
                $products_string .= '<optgroup label="' . $category['text'] . '">';
                while ($products = tep_db_fetch_array($products_query)) {
                    $products_string .= '<option value="' . $products['products_id'] . '" ' . ($products['products_status'] == 0 ? ' class="dis_prod"' : '') . '>' . $products['products_name'] . ' [' . $products['products_model'] . ']</option>';
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

        $query = tep_db_query("select p.products_id, p.products_model, ".ProductNameDecorator::instance()->listingQueryExpression('pd','')." AS products_name, p.products_status from " . TABLE_PRODUCTS_DESCRIPTION . " pd," . TABLE_PRODUCTS . " p where language_id = '" . (int)$languages_id . "' and platform_id = '".intval(\common\classes\platform::defaultId())."' and p.products_id in ('" . implode("','", $ids) . "') and p.products_id =  pd.products_id ");
        if (tep_db_num_rows($query) > 0) {
            while ($data = tep_db_fetch_array($query)) {
              if (count($data) > 0) {
                $pdf_catalogueProduct = [
                    'products_id' => $data['products_id'],
                    'products_name' => $data['products_name'],
                    'products_model' => $data['products_model'],
                    'image' => \common\classes\Images::getImage($data['products_id'], 'Small'),
                    'price' => $currencies->format(\common\helpers\Product::get_products_price($data['products_id'])),
                    'status_class' => ($data['products_status'] == 0 ? 'dis_prod' : ''),
                ];

                $ret .= $this->render('new-product.tpl', [
                    'pdf_catalogue' => $pdf_catalogueProduct,
                ]);
              }
            }
        }

        return $ret;
    }

}
