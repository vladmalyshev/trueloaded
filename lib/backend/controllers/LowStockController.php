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
use common\classes\platform;

class LowStockController extends Sceleton {

    public $acl = ['BOX_HEADING_REPORTS', 'BOX_REPORTS_LOW_STOCK'];
    public $pastMonths;
    public $start_date;
    public $end_date;

    public function __construct($id, $module = null) {
        \common\helpers\Translation::init('admin/low-stock');
        $this->pastMonths = 3; //edit: if this is zero, the script throws warnings

        $this->start_date = $this->httpGetVars('start_date', date('Y-m-d', time() - $this->pastMonths * 2592000));
        $this->end_date = $this->httpGetVars('end_date', date('Y-m-d'));
        parent::__construct($id, $module);
    }

    public function actionIndex() {

        $this->selectedMenu = array('reports', 'low-stock');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('low-stock/index'), 'title' => HEADING_TITLE);
        $this->view->headingTitle = HEADING_TITLE;

        $platforms = platform::getList(false);

        $this->view->StockTable = $this->getTable();

        return $this->render('index', [
                    'platforms' => $platforms,
                    'first_platform_id' => platform::firstId(),
                    'default_platform_id' => platform::defaultId(),
                    'isMultiPlatforms' => platform::isMulti(),
        ]);
    }

    public function getTable() {
        return array(
            array(
                'title' => TABLE_HEADING_DESIGNER,
                'not_important' => 0,
            ),
            array(
                'title' => TABLE_HEADING_PRODUCTS,
                'not_important' => 0,
            ),
            array(
                'title' => TABLE_HEADING_QTY_LEFT,
                'not_important' => 0,
            ),
            array(
                'title' => TABLE_HEADING_PRODUCTS_MODEL,
                'not_important' => 0,
            ),
            array(
                'title' => TABLE_HEADING_SALES,
                'not_important' => 0,
                'width' => '5%'
            ),
            array(
                'title' => TABLE_HEADING_DAYS,
                'not_important' => 0,
                'width' => '5%'
            ),
        );
    }

    public function actionList() {
        $languages_id = \Yii::$app->settings->get('languages_id');
        $draw = Yii::$app->request->get('draw', 1);
        $start = Yii::$app->request->get('start', 0);
        $length = Yii::$app->request->get('length', 10);
        $current_page_number = ($start / $length) + 1;

        if (isset($_GET['order'][0]['column']) && $_GET['order'][0]['dir']) {
            switch ($_GET['order'][0]['column']) {
                case 0:
                    $orderBy = "m.manufacturers_name " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                    break;
                case 1:
                    $orderBy = "products_name " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                    break;
                case 2:
                    $orderBy = "products_quantity " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                    break;
                case 3:
                    $orderBy = "products_model " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                    break;
                default:
                    $orderBy = "products_quantity";
                    break;
            }
        } else {
            $orderBy = "products_quantity";
        }

        $search = '';
        if (isset($_GET['search']['value']) && tep_not_null($_GET['search']['value'])) {
            $keywords = tep_db_input(tep_db_prepare_input($_GET['search']['value']));
            $search = " and (pd.products_name like '%" . tep_db_input($keywords) . "%' or p.products_model like '%" . tep_db_input($keywords) . "%' or m.manufacturers_name like '%" . tep_db_input($keywords) . "%') ";
        }


        $responseList = [];
        if (defined('PRODUCTS_INVENTORY') && PRODUCTS_INVENTORY == 'True') {
            $products_query_raw = "select distinct p.products_id, IFNULL(i.products_id,p.products_id) as uprid, " .
                    "i.products_quantity as products_quantity, " .
                    "IF(LENGTH(i.products_name)>0,i.products_name,".ProductNameDecorator::instance()->listingQueryExpression('pd','').") as products_name, " .
                    "IF(LENGTH(i.products_model)>0,i.products_model,p.products_model) as products_model, " .
                    "m.manufacturers_name " .
                    "from " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS . " p " .
                    "left join " . TABLE_MANUFACTURERS . " m on p.manufacturers_id=m.manufacturers_id " .
                    "left join " . TABLE_SETS_PRODUCTS . " bs on bs.sets_id=p.products_id " .
                    ", " . TABLE_INVENTORY . " i " .
                    "where p.products_status = '1' " .
                    "and i.prid=p.products_id " .
                    "and p.products_id = pd.products_id and pd.language_id = '" . $languages_id . "' and pd.platform_id='".intval(\common\classes\platform::defaultId())."' " .
                    "and i.products_quantity <= " . STOCK_REORDER_LEVEL . " " .
                    "and i.non_existent = 0 " .
                    "and bs.sets_id is null " .
                    $search .
                    //"group by pd.products_id ".
                    "order by {$orderBy}";
        } else {
            $products_query_raw = "select distinct p.products_id, p.products_quantity, ".ProductNameDecorator::instance()->listingQueryExpression('pd','')." AS products_name, p.products_model, m.manufacturers_name " .
                    "from " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS . " p " .
                    "left join " . TABLE_MANUFACTURERS . " m on p.manufacturers_id=m.manufacturers_id " .
                    "left join " . TABLE_SETS_PRODUCTS . " bs on bs.sets_id=p.products_id " .
                    "where p.products_status = '1' " .
                    "and p.products_id = pd.products_id and pd.language_id = '" . $languages_id . "' and pd.platform_id='".intval(\common\classes\platform::defaultId())."' " .
                    "and p.products_quantity <= " . STOCK_REORDER_LEVEL . " " .
                    "and bs.sets_id is null " .
                    $search .
                    "group by pd.products_id " .
                    "order by {$orderBy}";
        }

        $current_page_number = ($start / $length) + 1;
        $products_split = new \splitPageResults($current_page_number, $length, $products_query_raw, $products_query_numrows, 'p.products_id');
        $products_query = tep_db_query($products_query_raw);
        while ($products = tep_db_fetch_array($products_query)) {
            $products_id = $products['products_id'];

            /* get category path of item */

            // find the products category
            $last_category_query = tep_db_query("select categories_id from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = $products_id");
            $last_category = tep_db_fetch_array($last_category_query);
            $p_category = $last_category["categories_id"];

            // store and find the parent until reaching root
            $p_category_array = array();
            do {
                $p_category_array[] = $p_category;
                $last_category_query = tep_db_query("select parent_id from " . TABLE_CATEGORIES . " where categories_id = $p_category");
                $last_category = tep_db_fetch_array($last_category_query);
                $p_category = $last_category["parent_id"];
            } while ($p_category);
            $cPath_array = array_reverse($p_category_array);
            unset($p_category_array);

            /* done */

            // Sold in Last x Months Query
            if (defined('PRODUCTS_INVENTORY') && PRODUCTS_INVENTORY == 'True') {
                $productSold_query = tep_db_query(
                        "select sum(op.products_quantity) as quantitysum " .
                        "FROM " . TABLE_ORDERS . " as o, " . TABLE_ORDERS_PRODUCTS . " AS op " .
                        "WHERE o.date_purchased BETWEEN '" . $this->start_date . " 00:00:00' AND '" . $this->end_date . " 23:59:59' " .
                        "AND o.orders_id = op.orders_id " .
                        "AND op.products_id = '" . $products_id . "' " .
                        "AND IF(LENGTH(op.uprid)>0 AND op.uprid!=op.products_id,op.uprid = '" . tep_db_input($products['uprid']) . "',1) " .
                        "GROUP BY op.products_id " .
                        "ORDER BY quantitysum DESC, op.products_id"
                );
            } else {
                $productSold_query = tep_db_query(
                        "select sum(op.products_quantity) as quantitysum " .
                        "FROM " . TABLE_ORDERS . " as o, " . TABLE_ORDERS_PRODUCTS . " AS op " .
                        "WHERE o.date_purchased BETWEEN '" . $this->start_date . " 00:00:00' AND '" . $this->end_date . " 23:59:59' " .
                        "AND o.orders_id = op.orders_id " .
                        "AND op.products_id = {$products_id} " .
                        "GROUP BY op.products_id " .
                        "ORDER BY quantitysum DESC, op.products_id"
                );
            }
            $productSold = tep_db_fetch_array($productSold_query);
            if ($products['products_quantity'] > 0) {
                $StockOnHand = $products['products_quantity'];
                $SalesPerDay = $productSold['quantitysum'] / ($this->pastMonths * 30);

                round($SalesPerDay, 2);
                $daysSupply = 0;
                //$display = y;
                if ($SalesPerDay > 0) {
                    $daysSupply = $StockOnHand / $SalesPerDay;
                }

                round($daysSupply);
                if ($daysSupply <= '20') {
                    $daysSupply = '<font color=red><b>' . round($daysSupply) . ' ' . DAYS . '</b></font>';
                } else {
                    $daysSupply = round($daysSupply) . ' ' . DAYS;
                }
                if (($SalesPerDay == 0) && ($StockOnHand > 1)) {
                    //$display = n;
                    $daysSupply = '+60 ' . DAYS;
                }

                if ($daysSupply > ($this->pastMonths * 30)) {
                    //$display = n;
                }
            } else {
                $daysSupply = '<font color=red><b>NA</b></font>';
                //$display = y;
            }
            $display = 'y';
            if ($display == 'y') {

                $url_product = \Yii::$app->urlManager->createUrl(['categories/productedit', 'pID' => $products['products_id']]);

                // some tweaking to make the output just looking better
                $prodsold = ($productSold['quantitysum'] > 0) ? (int) $productSold['quantitysum'] : 0;
                $prodmodel = trim((string) $products['products_model']);
                $prodmodel = (strlen($prodmodel)) ? htmlspecialchars($prodmodel) : '&nbsp;';

                // make negative qtys red b/c people have backordered them
                $productsQty = (int) $products['products_quantity'];
                $productsQty = ($productsQty < 0) ? sprintf('<font color="red"><b>%d</b></font>', $productsQty) : (string) $productsQty;
                $doublie_0 = '<div class="c-list-name click_double"  data-click-double="' . $url_product . '">';
                $doublie_1 = '</div>';
                $responseList[] = array(
                    $doublie_0 . '<a href="' . $url_product . '" class="blacklink">' . $products['manufacturers_name'] . '</a>' . $doublie_1,
                    $doublie_0 . '<a href="' . $url_product . '" class="blacklink">' . $products['products_name'] . '</a>' . $doublie_1,
                    $doublie_0 . $productsQty . $doublie_1,
                    $doublie_0 . '<a href="' . $url_product . '">' . $prodmodel . '</a>' . $doublie_1,
                    $doublie_0 . $prodsold . $doublie_1,
                    $doublie_0 . $daysSupply . $doublie_1
                    );                
                unset($cPath_array);
            }

        }

        $response = array(
            'draw' => $draw,
            'recordsTotal' => $products_query_numrows,
            'recordsFiltered' => $products_query_numrows,
            'data' => $responseList,
        );
        echo json_encode($response);
    }

    public function httpGetVars($name, $default = '', $validsarray = false) {
        //edit: use tep function for this instead
        // get "Get" variable
        if (isset($_GET[$name]))
            $value = $_GET[$name];
        else
            $value = $default;

        // check against valid values
        if (is_array($validsarray))
            if (!in_array($value, $validsarray, true))
                $value = $default;

        return $value;
    }

}
