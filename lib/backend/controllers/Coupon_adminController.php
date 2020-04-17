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
use yii\helpers\FormatConverter;
use common\helpers\Html;

/**
 * Coupon admin controller to handle user requests.
 */
class Coupon_adminController extends Sceleton {

  public $acl = ['BOX_HEADING_MARKETING_TOOLS', 'BOX_HEADING_GV_ADMIN', 'BOX_COUPON_ADMIN'];
  private static $dateOptions = ['active_on', 'start_between', 'end_between'];
  private static $by = [
    [
      'name' => 'TEXT_ANY',
      'value' => '',
      'selected' => '',
    ],
    [
      'name' => 'COUPON_CODE',
      'value' => 'coupon_code',
      'selected' => '',
    ],
    [
      'name' => 'TEXT_COUPON',
      'value' => 'coupon_name',
      'selected' => '',
    ],
    [
      'name' => 'COUPON_DESC',
      'value' => 'coupon_description',
      'selected' => '',
    ],
  ];
  private static $filterFields = ['search' => '', 'date' => '',
    'inactive' => 'intval',
    'pfrom' => 'floatval', 'pto' => 'floatval',
    'dfrom' => ['list' => ['\common\helpers\Date', 'prepareInputDate']],
    'dto' => ['list' => ['\common\helpers\Date', 'prepareInputDate']]
    ];

  public function beforeAction($action) {
    if (false === \common\helpers\Acl::checkExtension('CouponsAndVauchers', 'allowed')) {
      $this->redirect(array('/'));
      return false;
    }
    return parent::beforeAction($action);
  }

  public function actionIndex() {

    \common\helpers\Translation::init('admin/coupon_admin');
    $this->selectedMenu = array('marketing', 'gv_admin', 'coupon_admin');
    $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('coupon_admin/index'), 'title' => HEADING_TITLE);
    $this->topButtons[] = '<a href="' . Yii::$app->urlManager->createUrl('coupon_admin/voucheredit') . '" class="create_item">' . IMAGE_INSERT . '</a>';
    $this->view->headingTitle = HEADING_TITLE;
    $this->view->couponTable = array(
      array(
        'title' => Html::checkbox('select_all', false, ['id' => 'select_all']),
        'not_important' => 2
      ),
      array(
        'title' => DATE_CREATED,
        'not_important' => 0
      ),
      array(
        'title' => COUPON_CODE,
        'not_important' => 0
      ),
      array(
        'title' => COUPON_NAME,
        'not_important' => 0
      ),
      array(
        'title' => TEXT_START_DATE,
        'not_important' => 0
      ),
      array(
        'title' => TEXT_END_DATE,
        'not_important' => 0
      ),
      array(
        'title' => COUPON_AMOUNT,
        'not_important' => 0
      ),
    );
    $this->view->sortColumns = '1,2,3,4,5,6';

    $this->view->filters = new \stdClass();
    $this->view->filters->row = (int) Yii::$app->request->get('row', 0);
    $gets = Yii::$app->request->get();

    $by = self::$by;
    foreach ($by as $key => $value) {
      $by[$key]['name'] = defined($by[$key]['name'] )? constant($by[$key]['name']):strtolower(str_replace('_', ' ', $by[$key]['name']));
      if (isset($gets['by']) && $value['value'] == $gets['by']) {
        $by[$key]['selected'] = 'selected';
      }
    }
    $this->view->filters->by = $by;
    foreach (self::$dateOptions as $opt) {
      $this->view->filters->dateOptions[$opt] = defined('TEXT_' . strtoupper($opt)) ? constant('TEXT_' . strtoupper($opt)) : strtoupper($opt);
    }

    foreach (self::$filterFields as $v => $f) {
      if (!empty($gets[$v])) {
        if (is_callable($f)) {
          $this->view->filters->{$v} = call_user_func($f, $gets[$v]);
        } elseif (is_array($f) && !empty($f['filter']) && is_callable($f['filter'])) {
          $this->view->filters->{$v} = call_user_func($f['filter'], $gets[$v]);
        } else {
          $this->view->filters->{$v} = $gets[$v];
        }
      } else {
        $this->view->filters->{$v} = '';
      }
    }
    return $this->render('index');
  }

  public function actionList() {
    $languages_id = \Yii::$app->settings->get('languages_id');
    $currencies = Yii::$container->get('currencies');

    \common\helpers\Translation::init('admin/coupon_admin');

    $draw = (int) Yii::$app->request->get('draw', 1);
    $start = (int) Yii::$app->request->get('start', 0);
    $length = (int) Yii::$app->request->get('length', 10);

    $formFilter = Yii::$app->request->get('filter');
    $gets = [];
    parse_str($formFilter, $gets);

    if (isset($gets['date']) && in_array($gets['date'], self::$dateOptions)) {
      $date = $gets['date'];
    } else {
      $date = 'active_on';
    }
    if (isset($gets['by']) && in_array($gets['by'], \yii\helpers\ArrayHelper::getColumn(self::$by, 'value'))) {
      $by = $gets['by'];
    } else {
      $by = '';
    }

    $listQuery = \common\models\Coupons::find()->joinWith(['description'])->select(\common\models\Coupons::tableName() . '.*');
    $inactive = false;

    foreach (self::$filterFields as $v => $f) {
      if (!empty($gets[$v])) {
        if (is_callable($f)) {
          if (is_array($gets[$v])) {
            foreach ($gets[$v] as $k => $vv) {
              $gets[$v][$k] = call_user_func($f, $vv);
            }
            $val = $gets[$v];
          } else {
            $val = call_user_func($f, $gets[$v]);
          }
        } elseif (is_array($f) && !empty($f['list']) && is_callable($f['list'])) {
          $val = call_user_func($f['list'], $gets[$v]);
        } else {
          $val = $gets[$v];
        }

        switch ($v) {
          case 'inactive':
            $inactive = true;
            break;
          case 'pfrom':
            $listQuery->andWhere(['>=', 'coupon_amount', $val]);
            break;
          case 'pto':
            $listQuery->andWhere(['<=', 'coupon_amount', $val]);
            break;
          case 'dfrom':
            if (in_array($date, ['start_between'])) {
              $listQuery->andWhere(['>=', 'coupon_start_date', $val]);
            } elseif (in_array($date, ['active_on'])) {
              $listQuery->andWhere([
                'or',
                ['>=', 'coupon_expire_date', $val],
                ['<', 'coupon_expire_date', '1980-01-01']
              ]);
            } else {
              $listQuery->andWhere(['>=', 'coupon_expire_date', $val]);
            }
            break;
          case 'dto':
            if (in_array($date, ['start_between'])) {
              $listQuery->andWhere(['<=', 'coupon_start_date', $val]);
            } elseif (in_array($date, ['active_on'])) {
              $listQuery->andWhere(['<=', 'coupon_start_date', $val]);
            } else {
              $listQuery->andWhere(['<=', 'coupon_expire_date', $val]);
            }
            break;
          case 'search':
            if ($by == '') { //all
              $tmp = [];
              foreach (\yii\helpers\ArrayHelper::getColumn(self::$by, 'value') as $field) {
                if (!empty($field) && is_string($field)) {
                  $tmp[] = ['like', $field, $val];
                }
              }
              if (!empty($tmp)) {
                $listQuery->andWhere(array_merge(['or'], $tmp));
              }
            } else {
              $listQuery->andWhere(['like', $by, $val]);
            }
            break;
        }
      }
    }
    if (!$inactive) {
      $listQuery->active();
    }

    $gets = Yii::$app->request->get();
    if (!empty($gets['search']['value'])) {
      $val = $gets['search']['value'];
      $tmp = [];
      foreach (\yii\helpers\ArrayHelper::getColumn(self::$by, 'value') as $field) {
        if (!empty($field) && is_string($field)) {
          $tmp[] = ['like', $field, $val];
        }
      }
      if (!empty($tmp)) {
        $listQuery->andWhere(array_merge(['or'], $tmp));
      }
    }

    if (!empty($gets['order'][0]['column'])) {
      $dir = 'asc';
      if (!empty($gets['order'][0]['dir']) && $gets['order'][0]['dir'] == 'desc') {
        $dir = 'desc';
      }
      switch ($gets['order'][0]['column']) {
        case 1:
          $listQuery->addOrderBy(" date_created " . $dir);
          $listQuery->addOrderBy(" coupon_code ");
          break;
        case 2:
          $listQuery->addOrderBy(" coupon_code " . $dir);
          break;
        case 3:
          $listQuery->addOrderBy(" coupon_name " . $dir);
          break;
        case 4:
          $listQuery->addOrderBy(" coupon_start_date " . $dir);
          break;
        case 5:
          $listQuery->addOrderBy(" coupon_expire_date " . $dir);
          break;
        case 6:
          $listQuery->addOrderBy(" coupon_amount " . $dir);
          break;
        default:
          $listQuery->addOrderBy(" date_created desc ");
          break;
      }
    } else {
      $listQuery->addOrderBy(" date_created desc ");
    }

    $responseList = array();
    if ($length == -1)
      $length = 10000;
    $current_page_number = ( $start / $length ) + 1;
    $query_numrows = $listQuery->count();

    $listQuery->offset($start)->limit($length);
    $listQuery->addSelect('coupon_name, coupon_description');

    $coupons = $listQuery->asArray()->all();

    foreach ($coupons as $coupon) {
      $row = [];
      $row[] = Html::checkbox('bulkProcess[]', false, ['value' => $coupon['coupon_id']])
          . Html::hiddenInput('coupons_' . $coupon['coupon_id'], $coupon['coupon_id'], ['class' => "cell_identify"])
          . ($coupon['coupon_active'] != 'Y' ? Html::hiddenInput('coupons_st' . $coupon['coupon_id'], 'dis_module', ['class' => "tr-status-class"]):'')
          ;

      if ($coupon['date_created'] > '1980-01-01') {
        $row[] = \common\helpers\Date::date_short($coupon['date_created']);
      } else {
        $row[] = '';
      }
       $row[] = $coupon['coupon_code'];
       $row[] = $coupon['coupon_name'];

      if ($coupon['coupon_start_date'] > '1980-01-01') {
        $row[] = \common\helpers\Date::date_short($coupon['coupon_start_date']);
      } else {
        $row[] = '';
      }
      if ($coupon['coupon_expire_date'] > '1980-01-01') {
        $row[] = \common\helpers\Date::date_short($coupon['coupon_expire_date']);
      } else {
        $row[] = '';
      }

      $coupon_amount = '';
      if ($coupon['coupon_type'] == 'P') {
        $coupon_amount = number_format($coupon['coupon_amount'], 2) . '%';
      } elseif ($coupon['coupon_type'] == 'S') {
        $coupon_amount = TEXT_FREE_SHIPPING;
      } else {
        $coupon_amount = $currencies->format($coupon['coupon_amount'], false, $coupon['coupon_currency']);
      }
      $row[] = $coupon_amount;

      $responseList[] = $row;
    }
    
    $response = array(
      'draw' => $draw,
      'recordsTotal' => $query_numrows,
      'recordsFiltered' => $query_numrows,
      'data' => $responseList
    );
    echo json_encode($response);
  }

  public function actionItempreedit() {
    $languages_id = \Yii::$app->settings->get('languages_id');

    \common\helpers\Translation::init('admin/coupon_admin');

    $currencies = Yii::$container->get('currencies');

    $this->layout = false;

    $item_id = (int) Yii::$app->request->post('item_id', 0);

    $cInfo = \common\models\Coupons::find()->andWhere(['coupon_id' => (int) $item_id])->one();
    if (!$cInfo) {
      die();
    }

    echo '<div class="or_box_head">[' . $cInfo->coupon_id . ']  ' . $cInfo->coupon_code . '</div>';

    if ($cInfo->coupon_type == 'P') {
      $amount = number_format($cInfo->coupon_amount, 2) . '%';
    } else {
      $amount = $currencies->format($cInfo->coupon_amount, false, $cInfo->coupon_currency);
    }

    $prod_details = TEXT_NONE;
    $cat_details = TEXT_NONE;
    $prodExDetails = TEXT_NONE;
    $catExDetails = TEXT_NONE;
    if ($cInfo->exclude_products) {
      $prodExDetails = '<a href="#exclude_products" class="popUp" id="excProducts">' . IMAGE_VIEW . '</a>' .
          '<div id="exclude_products" style="display: none">' . (\common\helpers\Product::getAdminDetailsList($cInfo->exclude_products)) .
            '<script type="text/javascript">(function($){$(function(){$(\'#excProducts\').popUp();})})(jQuery)</script>' .
          '</div>'
          ;
    }
    if ($cInfo->restrict_to_products) {
      $prod_details = '<a href="#include_products" class="popUp" id="incProducts">' . IMAGE_VIEW . '</a>' .
          '<div id="include_products" style="display: none">' . (\common\helpers\Product::getAdminDetailsList($cInfo->restrict_to_products)) .
            '<script type="text/javascript">(function($){$(function(){$(\'#incProducts\').popUp();})})(jQuery)</script>' .
          '</div>'
          ;
    }
    if ($cInfo->exclude_categories) {
      $catExDetails = '<a href="#exclude_cats" class="popUp" id="excCats">' . IMAGE_VIEW . '</a>' .
          '<div id="exclude_cats" style="display: none">' . (\common\helpers\Categories::getAdminDetailsList($cInfo->exclude_categories)) .
            '<script type="text/javascript">(function($){$(function(){$(\'#excCats\').popUp();})})(jQuery)</script>' .
          '</div>';
    }
    if ($cInfo->restrict_to_categories) {
      $cat_details = '<a href="#include_cats" class="popUp" id="incCats">' . IMAGE_VIEW . '</a>' .
          '<div id="include_cats" style="display: none">' . (\common\helpers\Categories::getAdminDetailsList($cInfo->restrict_to_categories)) .
            '<script type="text/javascript">(function($){$(function(){$(\'#incCats\').popUp();})})(jQuery)</script>' .
          '</div>';
    }
    $coupon_name_query = tep_db_query("select coupon_description from " . TABLE_COUPONS_DESCRIPTION . " where coupon_id = '" . $cInfo->coupon_id . "' and language_id = '" . $languages_id . "'");
    $coupon_name = tep_db_fetch_array($coupon_name_query);
    echo '<div class="row_or_wrapp">';
    echo '<div class="row_or"><div>' . COUPON_DESC . ':</div><div>' . $coupon_name['coupon_description'] . '</div></div>';
    echo '<div class="row_or"><div>' . COUPON_AMOUNT . ':</div><div>' . $amount . '</div></div>';
    echo '<div class="row_or"><div>' . COUPON_STARTDATE . ':</div><div>' . \common\helpers\Date::date_short($cInfo->coupon_start_date) . '</div></div>';
    echo '<div class="row_or"><div>' . COUPON_FINISHDATE . ':</div><div>' . \common\helpers\Date::date_short($cInfo->coupon_expire_date) . '</div></div>';
    echo '<div class="row_or"><div>' . TEXT_RESTRICT_TO_CUSTOMERS . ':</div><div>' . $cInfo->restrict_to_customers . '</div></div>';
    echo '<div class="row_or"><div>' . COUPON_USES_COUPON . ':</div><div>' . $cInfo->uses_per_coupon . '</div></div>';
    echo '<div class="row_or"><div>' . COUPON_USES_USER . ':</div><div>' . $cInfo->uses_per_user . '</div></div>';
    echo '<div class="row_or"><div>' . COUPON_PRODUCTS . ':</div><div>' . $prod_details . '</div></div>';
    echo '<div class="row_or"><div>' . COUPON_CATEGORIES . ':</div><div>' . $cat_details . '</div></div>';
    echo '<div class="row_or"><div>' . TEXT_EXCLUDE_PRODUCTS . ':</div><div>' . $prodExDetails . '</div></div>';
    echo '<div class="row_or"><div>' . TEXT_EXCLUDE_CATEGORIES . ':</div><div>' . $catExDetails . '</div></div>';
    echo '<div class="row_or"><div>' . COUPON_USES_SHIPPING . ':</div><div>' . ($cInfo->uses_per_shipping ? TEXT_BTN_YES : TEXT_BTN_NO) . '</div></div>';
    echo '<div class="row_or"><div>' . TEXT_PRODUCTS_TAX_CLASS . ':</div><div>' . ($cInfo->tax_class_id ? \common\helpers\Tax::get_tax_class_title($cInfo->tax_class_id) : TEXT_NONE) . '</div></div>';
    echo '<div class="row_or"><div>' . DATE_CREATED . ':</div><div>' . \common\helpers\Date::date_short($cInfo->date_created) . '</div></div>';
    echo '<div class="row_or"><div>' . DATE_MODIFIED . ':</div><div>' . \common\helpers\Date::date_short($cInfo->date_modified) . '</div></div>';
    echo '</div>';
    echo '<div class="btn-toolbar btn-toolbar-order">';
    echo '<a href="' . tep_href_link('coupon_admin/couponemail', 'cid=' . $cInfo->coupon_id, 'NONSSL') . '" class="btn btn-email-cus btn-no-margin">' . TEXT_EMAIL . '</a>';
    echo '<a href="' . Yii::$app->urlManager->createUrl(['coupon_admin/voucheredit', 'cid' => $cInfo->coupon_id]) . '" class="btn btn-edit">' . TEXT_EDIT . '</a>';
    echo '<a href="javascript:void(0)" onclick="deleteItemConfirm(' . $cInfo->coupon_id . ')" class="btn btn-delete btn-no-margin">' . TEXT_DELETE . '</a>';
    echo '<a href="' . tep_href_link('coupon_admin/voucherreport', 'cid=' . $cInfo->coupon_id, 'NONSSL') . '" class="btn btn-ord-cus">' . TEXT_REPORT . '</a>';
    echo '</div>';
  }

  public function actionVoucheredit() {
    \common\helpers\Translation::init('admin/coupon_admin');

    $this->selectedMenu = array('marketing', 'gv_admin', 'coupon_admin');
    $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('coupon_admin/index'), 'title' => HEADING_TITLE);

    $cid = (int) Yii::$app->request->get('cid');
    $coupon_name = [];
    $coupon_desc = [];

    $languages = \common\helpers\Language::get_languages();
    for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
      $languages[$i]['logo'] = $languages[$i]['image'];

      $language_id = $languages[$i]['id'];
      $coupon_query = tep_db_query("select coupon_name,coupon_description from " . TABLE_COUPONS_DESCRIPTION . " where coupon_id = '" . $cid . "' and language_id = '" . $language_id . "'");
      $coupon = tep_db_fetch_array($coupon_query);
      if (isset($coupon['coupon_name'])) {
        $coupon_name[$language_id] = $coupon['coupon_name'];
      } else {
        $coupon_name[$language_id] = '';
      }
      if (isset($coupon['coupon_description'])) {
        $coupon_desc[$language_id] = $coupon['coupon_description'];
      } else {
        $coupon_desc[$language_id] = '';
      }
    }

    $coupon_free_ship = false;
    $coupon = \common\models\Coupons::findOne(['coupon_id' => $cid]);
    if ($coupon) {
      if ($coupon['coupon_type'] == 'P') {
        $coupon['coupon_amount'] = number_format($coupon['coupon_amount'], 2) . '%';
      }
      if (isset($coupon['coupon_type']) && $coupon['coupon_type'] == 'S') {
        $coupon_free_ship = true;
      }
    } else {
      $coupon = [
        'coupon_amount' => '',
        'coupon_currency' => DEFAULT_CURRENCY,
        'coupon_minimum_order' => '',
        'coupon_code' => '',
        'coupon_for_recovery_email' => 0,
        'uses_per_coupon' => '',
        'uses_per_user' => '',
        'uses_per_shipping' => '',
        'flag_with_tax' => 0,
        'restrict_to_products' => '',
        'restrict_to_categories' => '',
        'restrict_to_countries' => '',
        'tax_class_id' => 0,
        'coupon_start_date' => date('Y-m-d'),
        'coupon_expire_date' => date('Y-m-d', strtotime('+ 1 month')),
      ];
    }
    $coupon_currency = tep_draw_pull_down_menu('coupon_currency', \common\helpers\Currencies::get_currencies(1), $coupon['coupon_currency'], 'class="form-control"');

    return $this->render('voucheredit', [
          'cid' => $cid,
          'languages' => $languages,
          'coupon_name' => $coupon_name,
          'coupon_desc' => $coupon_desc,
          'coupon_free_ship' => $coupon_free_ship,
          'coupon_for_recovery_email' => $coupon['coupon_for_recovery_email'],
          'coupon_currency' => $coupon_currency,
          'coupon' => $coupon,
          'coupon_start_date' => ($coupon['coupon_start_date'] > 0 ? \common\helpers\Date::date_short($coupon['coupon_start_date']) : ''),
          'coupon_expire_date' => ($coupon['coupon_expire_date'] > 0 ? \common\helpers\Date::date_short($coupon['coupon_expire_date']) : ''),
    ]);
  }

  public function actionVoucherSubmit() {

    \common\helpers\Translation::init('admin/coupon_admin');

    $cid = (int) Yii::$app->request->post('coupon_id');


    $coupon_startdate = '0';
    if (!empty($_POST['coupon_startdate'])) {
      $coupon_startdate = \common\helpers\Date::prepareInputDate($_POST['coupon_startdate']);
    }
    $coupon_finishdate = '0';
    if (!empty($_POST['coupon_finishdate'])) {
      $coupon_finishdate = \common\helpers\Date::prepareInputDate($_POST['coupon_finishdate']);
    }

    $coupon_code = tep_db_prepare_input($_POST['coupon_code']);
    if (empty($coupon_code)) {
      $coupon_code = \common\helpers\Coupon::create_coupon_code();
    }

    $coupon_type = "F";
    if (substr($_POST['coupon_amount'], -1) == '%') {
      $coupon_type = 'P';
    }
      
    if ($_POST['coupon_free_ship']) {
      $coupon_type = 'S';
    }
    
    $sql_data_array = array('coupon_code' => $coupon_code,
      'coupon_amount' => tep_db_prepare_input($_POST['coupon_amount']),
      'coupon_currency' => tep_db_prepare_input($_POST['coupon_currency']),
      'coupon_type' => $coupon_type,
      'uses_per_coupon' => tep_db_prepare_input($_POST['uses_per_coupon']),
      'uses_per_user' => tep_db_prepare_input($_POST['uses_per_user']),
      'uses_per_shipping' => tep_db_prepare_input($_POST['uses_per_shipping']),
      'coupon_minimum_order' => tep_db_prepare_input($_POST['coupon_minimum_order']),
      'restrict_to_products' => tep_db_prepare_input($_POST['restrict_to_products']),
      'restrict_to_categories' => tep_db_prepare_input($_POST['restrict_to_categories']),
      'restrict_to_customers' => tep_db_prepare_input($_POST['restrict_to_customers']),
      'exclude_products' => tep_db_prepare_input($_POST['exclude_products']),
      'exclude_categories' => tep_db_prepare_input($_POST['exclude_categories']),
      'disable_for_special' => intval($_POST['disable_for_special']),
      'coupon_for_recovery_email' => intval($_POST['coupon_for_recovery_email']),
      'coupon_start_date' => $coupon_startdate,
      'coupon_expire_date' => $coupon_finishdate,
      'date_created' => 'now()',
      'date_modified' => 'now()',
      'restrict_to_countries' => implode(",", tep_db_prepare_input($_POST['restrict_to_countries'] ?? [])),
      'tax_class_id' => tep_db_prepare_input($_POST['configuration_value']),
      'flag_with_tax' => tep_db_prepare_input($_POST['flag_with_tax']),);
    $languages = \common\helpers\Language::get_languages();
    for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
      $language_id = $languages[$i]['id'];
      $sql_data_marray[$i] = array('coupon_name' => tep_db_prepare_input($_POST['coupon_name'][$language_id]),
        'coupon_description' => tep_db_prepare_input($_POST['coupon_description'][$language_id])
      );
    }

    if ($cid > 0) {
      unset($sql_data_array['date_created']);
      tep_db_perform(TABLE_COUPONS, $sql_data_array, 'update', "coupon_id='" . $cid . "'");
      for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
        $language_id = $languages[$i]['id'];
        $check_lang = tep_db_fetch_array(tep_db_query(
                "SELECT COUNT(*) AS c FROM " . TABLE_COUPONS_DESCRIPTION . " WHERE coupon_id = '" . (int) $cid . "' AND language_id = '" . (int) $language_id . "'"
        ));
        if ($check_lang['c'] > 0) {
          tep_db_perform(TABLE_COUPONS_DESCRIPTION, $sql_data_marray[$i], 'update', "coupon_id = '" . (int) $cid . "' AND language_id = '" . (int) $language_id . "'");
        } else {
//            $update = tep_db_query("insert into " . TABLE_COUPONS_DESCRIPTION . " set coupon_name = '" . tep_db_prepare_input($_POST['coupon_name'][$language_id]) . "', coupon_description = '" . tep_db_prepare_input($_POST['coupon_desc'][$language_id]) . "', coupon_id = '" . $cid . "', language_id = '" . $language_id . "'");
          $sql_data_marray[$i]['coupon_id'] = $cid;
          $sql_data_marray[$i]['language_id'] = $language_id;

          tep_db_perform(TABLE_COUPONS_DESCRIPTION, $sql_data_marray[$i]);
        }
      }
    } else {
      tep_db_perform(TABLE_COUPONS, $sql_data_array);
      $cid = tep_db_insert_id();

      for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
        $language_id = $languages[$i]['id'];
        $sql_data_marray[$i]['coupon_id'] = $cid;
        $sql_data_marray[$i]['language_id'] = $language_id;
        tep_db_perform(TABLE_COUPONS_DESCRIPTION, $sql_data_marray[$i]);
      }
    }


    $message = TEXT_COUPON_UPDATED_NOTICE;
    $messageType = 'success';
    ?>
    <div class="popup-box-wrap pop-mess">
      <div class="around-pop-up"></div>
      <div class="popup-box">
        <div class="pop-up-close pop-up-close-alert"></div>
        <div class="pop-up-content">
          <div class="popup-heading"><?php echo TEXT_NOTIFIC; ?></div>
          <div class="popup-content pop-mess-cont pop-mess-cont-<?= $messageType ?>">
    <?= $message ?>
          </div>
        </div>
        <div class="noti-btn">
          <div></div>
          <div><span class="btn btn-primary"><?php echo TEXT_BTN_OK; ?></span></div>
        </div>
      </div>
      <script>
        $('body').scrollTop(0);
        $('.pop-mess .pop-up-close-alert, .noti-btn .btn').click(function () {
          $(this).parents('.pop-mess').remove();
        });
      </script>
    </div>
    <?php
    echo '<script> window.location.href="' . Yii::$app->urlManager->createUrl(['coupon_admin/voucheredit', 'cid' => $cid]) . '";</script>';
  }

  public function actionConfirmitemdelete() {
    \common\helpers\Translation::init('admin/coupon_admin');

    $this->layout = false;

    $item_id = (int) Yii::$app->request->post('item_id');

    $cc_query = tep_db_query("select coupon_id, coupon_code, coupon_amount, coupon_currency, coupon_type, coupon_start_date,coupon_expire_date,uses_per_user,uses_per_coupon,restrict_to_products, restrict_to_categories, date_created,date_modified from " . TABLE_COUPONS . " where coupon_id = '" . (int) $item_id . "'");
    $cc_list = tep_db_fetch_array($cc_query);
    $cInfo = new \objectInfo($cc_list);

    echo tep_draw_form('item_delete', 'coupon_admin', \common\helpers\Output::get_all_get_params(array('action')) . 'action=update', 'post', 'id="item_delete" onSubmit="return deleteItem();"');
    echo '<div class="or_box_head">' . '[' . $cInfo->coupon_id . ']  ' . $cInfo->coupon_code . '</div>';
    echo '<div class="col_desc">' . TEXT_CONFIRM_DELETE . '</div>';
    echo '<div class="btn-toolbar btn-toolbar-order">';
    echo '<button class="btn btn-no-margin btn-delete">' . IMAGE_DELETE . '</button>';
    echo '<input type="button" class="btn btn-cancel" value="' . IMAGE_CANCEL . '" onClick="return resetStatement()">';
    echo '</div>';
    echo tep_draw_hidden_field('item_id', $item_id);
    echo '</form>';
  }

  public function actionItemdelete() {
    $item_id = (int) Yii::$app->request->post('item_id');
    tep_db_query("update " . TABLE_COUPONS . " set coupon_active = 'N' where coupon_id='" . $item_id . "'");
  }

  public function actionVoucherreport() {
    // $this->view->headingTitle = HEADING_TITLE1;
    $this->selectedMenu = array('marketing', 'gv_admin', 'coupon_admin');

    \common\helpers\Translation::init('admin/coupon_admin');
    //$this->layout = false;
    $coupon_id = intval(Yii::$app->request->get('cid', 0));
    $this->view->catalogTable = array(
      array(
        'title' => CUSTOMER_NAME,
        'not_important' => 0
      ),
      array(
        'title' => TEXT_ORDER_ID,
        'not_important' => 0
      ),
      array(
        'title' => IP_ADDRESS,
        'not_important' => 0
      ),
      array(
        'title' => REDEEM_DATE,
        'not_important' => 0
      ),
    );
    $this->view->filters = new \stdClass();
    $this->view->filters->coupon_id = (int) Yii::$app->request->get('cid');
    $this->view->row_id = (int) Yii::$app->request->get('row');

    return $this->render('voucherreport', array(
          'coupon_id' => $coupon_id,
    ));
  }

  public function actionReportUsageList() {

    $this->layout = false;

    \common\helpers\Translation::init('admin/coupon_admin');

    $formFilter = Yii::$app->request->get('filter');
    parse_str($formFilter, $output);

    $filter = '';

    $coupon_id = intval($output['cid']);

    $draw = Yii::$app->request->get('draw', 1);
    $start = Yii::$app->request->get('start', 0);
    $length = Yii::$app->request->get('length', 10);

    if ($length == -1)
      $length = 10000;
    $query_numrows = 0;
    $responseList = array();

    if (isset($_GET['search']['value']) && tep_not_null($_GET['search']['value'])) {
      $keywords = tep_db_input(tep_db_prepare_input($_GET['search']['value']));
      $filter .= "AND (crt.redeem_ip LIKE '%{$keywords}%' OR c.customers_firstname LIKE '%{$keywords}%' OR c.customers_lastname LIKE '%{$keywords}%') ";
    }

    if (isset($_GET['order'][0]['column']) && $_GET['order'][0]['dir']) {
      $_dir = $_GET['order'][0]['dir'] == 'asc' ? 'asc' : 'desc';
      switch ($_GET['order'][0]['column']) {
        case 0:
          $orderBy = "c.customers_firstname {$_dir}, c.customers_lastname {$_dir} ";
          break;
        case 1:
          $orderBy = "crt.order_id {$_dir} ";
          break;
        case 2:
          $orderBy = "redeem_ip {$_dir} ";
          break;
        default:
          $orderBy = "redeem_date {$_dir}";
          break;
      }
    } else {
      $orderBy = "redeem_date desc";
    }

    $cc_query_raw = "select crt.*, " .
        " c.customers_id, c.customers_firstname, c.customers_lastname, " .
        " o.orders_id " .
        "from " . TABLE_COUPON_REDEEM_TRACK . " crt " .
        " left join " . TABLE_CUSTOMERS . " c ON c.customers_id=crt.customer_id " .
        " left join " . TABLE_ORDERS . " o ON o.orders_id=crt.order_id " .
        "where crt.coupon_id = '" . (int) $coupon_id . "' " .
        "{$filter} " .
        "order by {$orderBy}";
    //"select coupon_id, coupon_code, coupon_amount, coupon_currency, coupon_type, coupon_start_date,coupon_expire_date,uses_per_user,uses_per_coupon,restrict_to_products, restrict_to_categories, date_created,date_modified from " . TABLE_COUPONS . " $search_condition order by $orderBy ";


    $current_page_number = ( $start / $length ) + 1;
    $_split = new \splitPageResults($current_page_number, $length, $cc_query_raw, $query_numrows, 'unique_id');
    $cc_query = tep_db_query($cc_query_raw);
    while ($cc_list = tep_db_fetch_array($cc_query)) {

      $responseList[] = array(
        ($cc_list['customers_id'] ? '<a target="_blank" href="' . Yii::$app->urlManager->createUrl(['customers/customeredit', 'customers_id' => $cc_list['customers_id']]) . '">' . $cc_list['customers_firstname'] . ' ' . $cc_list['customers_lastname'] . '</a>' : $cc_list['customer_id']) .
        '<input class="cell_identify" type="hidden" value="' . $cc_list['unique_id'] . '">',
        ($cc_list['orders_id'] ? '<a target="_blank" href="' . Yii::$app->urlManager->createUrl(['orders/process-order', 'orders_id' => $cc_list['orders_id']]) . '">' . $cc_list['order_id'] . '</a>' : $cc_list['order_id']),
        $cc_list['redeem_ip'],
        \common\helpers\Date::date_short($cc_list['redeem_date']),
      );
    }

    $response = array(
      'draw' => $draw,
      'recordsTotal' => $query_numrows,
      'recordsFiltered' => $query_numrows,
      'data' => $responseList
    );
    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    Yii::$app->response->data = $response;
  }

  public function actionReportUsageInfo() {
    \common\helpers\Translation::init('admin/coupon_admin');
    $this->layout = false;

    $item_id = Yii::$app->request->post('item_id');
    $redeem_info = tep_db_fetch_array(tep_db_query(
            "SELECT crt.*, cd.coupon_name " .
            "FROM " . TABLE_COUPON_REDEEM_TRACK . " crt " .
            " LEFT JOIN " . TABLE_COUPONS_DESCRIPTION . " cd ON cd.coupon_id=crt.coupon_id AND cd.language_id='" . \Yii::$app->settings->get('languages_id') . "' " .
            "WHERE crt.unique_id='" . (int) $item_id . "'"
    ));

    $count_redemptions = tep_db_fetch_array(tep_db_query(
            "select count(*) as cnt from " . TABLE_COUPON_REDEEM_TRACK . " where coupon_id = '" . $redeem_info['coupon_id'] . "'"
    ));
    $redemptions_total = $count_redemptions['cnt'];

    $count_customers = tep_db_fetch_array(tep_db_query(
            "select count(*) as cnt from " . TABLE_COUPON_REDEEM_TRACK . " where coupon_id = '" . $redeem_info['coupon_id'] . "' and customer_id = '" . $redeem_info['customer_id'] . "'"
    ));
    $redemptions_customer = $count_customers['cnt'];

    echo '<div class="or_box_head">' . '[' . $redeem_info['coupon_id'] . ']' . COUPON_NAME . ' ' . $redeem_info['coupon_name'] . '</div>';
    echo '<div class="row_or_wrapp">';
    echo '<div class="row_or">' . '<b>' . TEXT_REDEMPTIONS . '</b>' . '</div>';
    echo '<div class="row_or"><div>' . TEXT_REDEMPTIONS_TOTAL . '</div><div>' . $redemptions_total . '</div></div>';
    echo '<div class="row_or"><div>' . TEXT_REDEMPTIONS_CUSTOMER . '=</div><div>' . $redemptions_customer . '</div></div>';
    echo '</div>';
  }

  public function actionCouponemail() {
    $messageStack = \Yii::$container->get('message_stack');
    $this->selectedMenu = array('marketing', 'gv_admin', 'coupon_admin');
    \common\helpers\Translation::init('admin/coupon_admin');
    $this->view->headingTitle = HEADING_TITLE_SEND;
    $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('coupon_admin/couponemail'), 'title' => $this->view->headingTitle);
    $msg = '';

    $send_coupon_id = intval(Yii::$app->request->get('cid', 0));

    if (Yii::$app->request->isPost) {
      $this->layout = false;
      $customers_email_address = Yii::$app->request->post('customers_email_address', '');
      $email_subject = Yii::$app->request->post('email_subject', '');
      $email_content = Yii::$app->request->post('email_content', '');
      $confirmed = Yii::$app->request->post('confirm_mul', 0);
      $mail_sent_to = TEXT_NONE;
      $send_status = 'success';
      if (empty($customers_email_address)) {
        $messageStack->add(ERROR_NO_CUSTOMER_SELECTED);
        $send_status = 'error';
      } else {

        switch ($customers_email_address) {
          case '***':
            if ($confirmed) {
              $mail_query = tep_db_query("select customers_firstname, customers_lastname, customers_email_address from " . TABLE_CUSTOMERS);
              $mail_sent_to = TEXT_ALL_CUSTOMERS;
            }
            break;
          case '**D':
            if ($confirmed) {
              $mail_query = tep_db_query("select customers_firstname, customers_lastname, customers_email_address from " . TABLE_CUSTOMERS . " where customers_newsletter = '1'");
              $mail_sent_to = TEXT_NEWSLETTER_CUSTOMERS;
            }
            break;
          default:
            $mail_query = tep_db_query("select customers_firstname, customers_lastname, customers_email_address from " . TABLE_CUSTOMERS . " where customers_email_address = '" . tep_db_input($customers_email_address) . "'");
            $mail_sent_to = $customers_email_address;
            break;
        }
        $send_counter = 0;
        $currentPlatformId = \Yii::$app->get('platform')->config()->getId();
        $platform_config = \Yii::$app->get('platform')->config($currentPlatformId);

        $STORE_OWNER_EMAIL_ADDRESS = $platform_config->const_value('STORE_OWNER_EMAIL_ADDRESS');
        $STORE_OWNER = $platform_config->const_value('STORE_OWNER');
        while ($mail = tep_db_fetch_array($mail_query)) {
          //Let's build a message object using the email class
          \common\helpers\Mail::send(
              $mail['customers_firstname'] . ' ' . $mail['customers_lastname'], $mail['customers_email_address'],
              $email_subject, $email_content,
              $STORE_OWNER, $STORE_OWNER_EMAIL_ADDRESS
          );
          $send_counter++;
        }
        $msg = sprintf(NOTICE_EMAIL_SENT_TO, $mail_sent_to);
        $messageStack->add($msg , 'header', 'success');
      }

      return '<div class="pop-up-content">
                        <div class="popup-content pop-mess-cont pop-mess-cont-' . $send_status . '">
                        ' . $msg . '
                        </div>
                  </div>
                  <div class="noti-btn">
                            <div></div>
                            <div><span class="btn btn-primary" onclick="$(\'.popup-box-wrap:last\').remove();return false">' . TEXT_BTN_OK . '</span></div>
                        </div>';
    }

    $customers = array();
    $customers[] = array('id' => '', 'text' => TEXT_SELECT_CUSTOMER);
    $customers[] = array('id' => '***', 'text' => TEXT_ALL_CUSTOMERS);
    $customers[] = array('id' => '**D', 'text' => TEXT_NEWSLETTER_CUSTOMERS);
    $mail_query = tep_db_query("select customers_email_address, customers_firstname, customers_lastname from " . TABLE_CUSTOMERS . " where 1 order by customers_lastname");
    while ($customers_values = tep_db_fetch_array($mail_query)) {
      $customers[] = array(
        'id' => $customers_values['customers_email_address'],
        'text' => $customers_values['customers_lastname'] . ', ' . $customers_values['customers_firstname'] . ' (' . $customers_values['customers_email_address'] . ')',
      );
    }

    $coupon_query = tep_db_query(
        "select c.coupon_code, cd.coupon_name, cd.coupon_description from " . TABLE_COUPONS . " c " .
        " left join " . TABLE_COUPONS_DESCRIPTION . " cd ON cd.coupon_id=c.coupon_id and cd.language_id = '" . \Yii::$app->settings->get('languages_id') . "' " .
        "where c.coupon_id = '" . $send_coupon_id . "'"
    );
    if (tep_db_num_rows($coupon_query) > 0) {
      $coupon_data = tep_db_fetch_array($coupon_query);
    } else {
      $coupon_data = array();
    }

    $email_params = array();
    $email_params['STORE_NAME'] = STORE_NAME;
    $email_params['STORE_URL'] = \common\helpers\Output::get_clickable_link(tep_catalog_href_link('', '', 'NONSSL'/* , $store['store_url'] */));

    $email_params['COUPON_CODE'] = $coupon_data['coupon_code'];
    $email_params['COUPON_NAME'] = $coupon_data['coupon_name'];
    $email_params['COUPON_DESCRIPTION'] = $coupon_data['coupon_description'];

    list($email_subject, $email_text) = \common\helpers\Mail::get_parsed_email_template('Send coupon', $email_params);

    return $this->render('couponemail', array(
          'customers_variants' => $customers,
          'customers_selected' => Yii::$app->request->get('customers', ''),
          'email_from' => EMAIL_FROM,
          'email_subject' => $email_subject,
          'email_text' => $email_text,
          'send_coupon_action' => tep_href_link('coupon_admin/couponemail', \common\helpers\Output::get_all_get_params(array('action'))),
    ));
  }

  public function actionTreeview() {

    \common\helpers\Translation::init('admin/coupon_admin');
    $this->layout = false;
    ob_start();
    ?>
    <link rel="stylesheet" type="text/css" href="<?= DIR_WS_ADMIN . DIR_WS_INCLUDES ?>javascript/dtree/dtree.css" />
    <script language="javascript" type="text/javascript" src="<?= DIR_WS_ADMIN . DIR_WS_INCLUDES ?>/javascript/dtree/dtree.js"></script>
    <div class="dtree" style="padding: 10px;"><form>
        <p><a href="javascript: d.openAll();"><?= TEXT_OPEN_ALL ?></a> | <a href="javascript: d.closeAll();"><?= TEXT_CLOSE_ALL ?></a></p>
        <div class="holder" style="overflow-y: scroll;"></div>
    <?php
    echo "<script type='text/javascript'>

		var d = new dTree('d'); \n
      d.add(0,-1,'Catalog','','');\n";

    $defLId = \common\helpers\Language::get_default_language_id();
    $categories_query_raw = "SELECT c.categories_id, cd.categories_name, c.parent_id FROM " . TABLE_CATEGORIES_DESCRIPTION . " AS cd INNER JOIN " . TABLE_CATEGORIES . " as c ON cd.categories_id = c.categories_id WHERE cd.language_id =" . (int) $defLId . " ORDER BY c.sort_order";
    $categories_query = tep_db_query($categories_query_raw);
    while ($categories = tep_db_fetch_array($categories_query)) {
      echo "d.add(" . $categories['categories_id'] . "," . $categories['parent_id'] . ",'" . addslashes($categories['categories_name']) . "','', '<input type=checkbox name=categories value=" . $categories['categories_id'] . ">');\n"; //,," . $categories['categories_id'] . ",,,); \n";
    } //end while

    $products_query_raw = "SELECT distinct pc.categories_id, pd.products_id, " . ProductNameDecorator::instance()->listingQueryExpression('pd', '') . " AS products_name FROM " . TABLE_PRODUCTS_TO_CATEGORIES . " as pc INNER JOIN " . TABLE_PRODUCTS_DESCRIPTION . " as pd ON pc.products_id = pd.products_id where pd.language_id = '" . (int) $defLId . "'";
    $products_query = tep_db_query($products_query_raw);

    while ($products = tep_db_fetch_array($products_query)) {
      echo "d.add(" . $products['products_id'] . "0000," . $products['categories_id'] . ",'" . addslashes($products['products_name']) . "','', '<input type=checkbox name=products value=" . $products['products_id'] . ">');\n"; //,," . $products['products_id'] . ",,,); \n";
    }//end while
    if (\Yii::$app->request->get('input', '') == 'exclude') {
      $catJsEl = 'document.new_voucher.exclude_categories.value';
      $prodJsEl = 'document.new_voucher.exclude_products.value';
    } else {
      $catJsEl = 'document.new_voucher.restrict_to_categories.value';
      $prodJsEl = 'document.new_voucher.restrict_to_products.value';
    }
    ?>
        $('.dtree .holder').append(d.toString());

        </script>
        <INPUT TYPE="BUTTON" onClick="cycleCheckboxes(this.form)" VALUE="<?= TEXT_APPLY ?>" class="btn btn-primary">
        <INPUT TYPE="BUTTON" onClick="return closePopup();" VALUE="<?= IMAGE_CANCEL ?>" class="btn btn-cancle" style="float:right;">
      </form>
      <script type='text/javascript'>

        $('.holder').css('max-height', document.body.clientHeight - 200);
        function cycleCheckboxes(what) {
           <?php echo $prodJsEl;?> = "";
           <?php echo $catJsEl;?> = "";
          for (var i = 0; i < what.elements.length; i++) {
            if ((what.elements[i].name.indexOf('products') > -1)) {
              if (what.elements[i].checked) {
                <?php echo $prodJsEl;?> += what.elements[i].value + ',';
              }
            }
          }

          for (var i = 0; i < what.elements.length; i++) {
            if ((what.elements[i].name.indexOf('categories') > -1)) {
              if (what.elements[i].checked) {
                <?php echo $catJsEl;?>  += what.elements[i].value + ',';
              }
            }
          }
          closePopup();
        }
      </script>
    <?php
    $buf = ob_get_contents();
    ob_end_clean();
    return $this->render('treeview', ['content' => $buf]);
  }

}
