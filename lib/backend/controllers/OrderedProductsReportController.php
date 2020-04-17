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

class OrderedProductsReportController extends Sceleton {

    public $acl = ['BOX_HEADING_REPORTS', 'BOX_REPORTS_ORDERED_PRODUCT'];

    public function __construct($id, $module = null) {
        \common\helpers\Translation::init('admin/ordered-products');
        \common\helpers\Translation::init('admin/stocktaking-cost');        
        parent::__construct($id, $module);
    }

    public $start_date;
    public $end_date;
    public $selected_platforms;
    public $selected_categories;
    public $selected_countries;
    public $showcolumns;
    public $orders;
    public $keywords;
    public $selected_status;
    public $status;

    public function actionIndex() {

        $this->selectedMenu = array('reports', 'ordered-products');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('ordered-products/index'), 'title' => BOX_REPORTS_ORDERED_PRODUCT);
        $this->view->headingTitle = BOX_REPORTS_STOCK_COST;

        $platforms = platform::getList(false);

        $this->parseFilterOptions(Yii::$app->request->get());
        
        $this->view->reportTable = $this->getTable($this->showcolumns);

        $this->status = \common\helpers\Order::getStatusList();

        $categories = \common\helpers\Categories::get_category_tree(0, '&nbsp;&nbsp;', '0', '', true);
        foreach ($categories as $key => &$category) {
            if ($key == 0)
                unset($categories[$key]);
            $category['text'] = html_entity_decode($category['text']);
        }
        //echo '<pre>';print_r($categories);die;
        return $this->render('index', [
                    'platforms' => \yii\helpers\ArrayHelper::map($platforms, 'id', 'text'),
                    'first_platform_id' => platform::firstId(),
                    'default_platform_id' => platform::defaultId(),
                    'isMultiPlatforms' => platform::isMulti(),
                    'orders' => \yii\helpers\ArrayHelper::map($this->getOrders(), 'orders_id', 'option_text'),
                    'categories' => \yii\helpers\ArrayHelper::map($categories, 'id', 'text'),
                    'countries' => \yii\helpers\ArrayHelper::map(\common\helpers\Country::get_countries(null, false, '', 'bill'), 'countries_id', 'countries_name'),
        ]);
    }

    public function getTable($columns = false) { 
      $all =  array(
          'cat' =>
            array(
                'title' => TABLE_HEADING_CATEGORY,
                'not_important' => 0,
                'width' => '23%'
            ),
          '_1' =>
            array(
                'title' => TABLE_HEADING_PRODUCTS,
                'not_important' => 0,
                'width' => '33%',
            ),
          '_2' =>
            array(
                'title' => TABLE_HEADING_QUANTITY,
                'not_important' => 0,
                'width' => '5%'
            ),
          'sp' =>
            array(
                'title' => TABLE_HEADING_PRODUCTS_PRICE,
                'not_important' => 0,
                'width' => '5%'
            ),
          'sp_' =>
            array(
                'title' => TEXT_SALES_SUMMARY,
                'not_important' => 0,
                'width' => '5%'
            ),
          'pp_' =>
            array(
                'title' => TABLE_HEADING_PURCHASE_PRICE,
                'not_important' => 0,
                'width' => '5%'
            ),
          'pp' =>
            array(
                'title' => TABLE_HEADING_PURCHASE_SUMMARY,
                'not_important' => 0,
                'width' => '5%'
            ),
          'country' =>
            array(
                'title' => ENTRY_COUNTRY,
                'not_important' => 0,
                'width' => '5%'
            ),
        );

        $ret = [];

        if (!is_array($columns)) {
          $ret = $all;

        } else {
          foreach ($all as $k => $v) {
            if (substr($k,0,1)=='_' || in_array(trim($k, ' ._'), $columns)) {
              $ret[] = $v;
            }
          }
        }

        return $ret;
    }

    public function build() {
        $languages_id = \Yii::$app->settings->get('languages_id');
        $responseList = [];
        $head = new \stdClass();
        $categories = [];
        if ($this->selected_categories) {
            foreach ($this->selected_categories as $category) {
                $categories[] = $category;
                \common\helpers\Categories::get_subcategories($categories, $category);
            }
        } else {
            \common\helpers\Categories::get_subcategories($categories, 0);
        }

        $included_categories_query = tep_db_query("SELECT c.categories_id, c.parent_id, cd.categories_name
				FROM " . TABLE_CATEGORIES . " c  
				LEFT JOIN " . TABLE_CATEGORIES_DESCRIPTION . " cd ON c.categories_id = cd.categories_id
				WHERE cd.language_id = FLOOR($languages_id) AND cd.affiliate_id=0
				AND c.categories_id IN (" . implode(",", $categories) . ")");
        $inc_cat = array();
        while ($included_categories = tep_db_fetch_array($included_categories_query)) {
            $inc_cat[] = array(
                'id' => $included_categories['categories_id'],
                'parent' => $included_categories['parent_id'],
                'name' => $included_categories['categories_name']);
        }
        $cat_info = array();
        for ($i = 0; $i < sizeof($inc_cat); $i++)
            $cat_info[$inc_cat[$i]['id']] = array(
                'parent' => $inc_cat[$i]['parent'],
                'name' => $inc_cat[$i]['name'],
                'path' => $inc_cat[$i]['id'],
                'link' => '',
                'cleanlink' => ''
            );

        for ($i = 0; $i < sizeof($inc_cat); $i++) {
            $cat_id = $inc_cat[$i]['id'];
            while ($cat_info[$cat_id]['parent'] != 0) {
                $cat_info[$inc_cat[$i]['id']]['path'] = $cat_info[$cat_id]['parent'] . '_' . $cat_info[$inc_cat[$i]['id']]['path'];
                $cat_id = $cat_info[$cat_id]['parent'];
            }
            $link_array = explode('_', $cat_info[$inc_cat[$i]['id']] ['path']);
            for ($j = 0; $j < sizeof($link_array); $j++) {
                $cat_info[$inc_cat[$i]['id']]['link'] .= '&nbsp;<a target="_blank" href="' . tep_href_link('categories', 'listing_type=category&category_id=' . $link_array[$j]) . '"><nobr>' . $cat_info[$link_array[$j]]['name'] . '</nobr></a>&nbsp;&raquo;&nbsp;';
                $cat_info[$inc_cat[$i]['id']]['cleanlink'] .= $cat_info[$link_array[$j]]['name'] . '/';
            }
        }

        $selectColumns = [
          'p.products_id', 'op.products_tax', 'o.currency_value', 'o.currency', 'p.products_price',
          'order_products_name' => new \yii\db\Expression('op.products_name'),
          'backend_products_name' => new \yii\db\Expression(ProductNameDecorator::instance()->listingQueryExpression(TABLE_PRODUCTS_DESCRIPTION,'')),
          ];

        if (in_array('country', $this->showcolumns)) {
          $selectColumns[] = 'c.countries_name';
        }

        $pq = \common\models\OrdersProducts::find()->alias('op')
            ->joinWith(['order o', 'product p', 'backendDescription']) //, 'inventory'])
            ->addSelect($selectColumns)
            ->addSelect([
              'products_quantity' => new \yii\db\Expression('sum(op.products_quantity)'),
              ])
            ->groupBy($selectColumns)
            ;


        if (in_array('sp', $this->showcolumns)) {
            $pq->addSelect([
              'final_price' => new \yii\db\Expression('avg(op.final_price)'),
            ]);
        }
        if ($this->selected_categories || in_array('cat', $this->showcolumns)) {
          $pq->leftJoin(['p2c' => TABLE_PRODUCTS_TO_CATEGORIES], ' p.products_id = p2c.products_id ')
              ->addSelect('p2c.categories_id')
              ->addOrderBy('p2c.categories_id')
              ;
        }

        if ($this->selected_categories) {
          $pq->andWhere(['p2c.categories_id' => $categories])
              ;
        }

        if (!empty($this->start_date)) {
          $pq->andWhere(['>=', 'o.date_purchased', \common\helpers\Date::prepareInputDate($this->start_date) . ' 00:00:00']);
        }
        if (!empty($this->end_date)) {
          $pq->andWhere(['<=', 'o.date_purchased', \common\helpers\Date::prepareInputDate($this->end_date) . ' 23:59:59']);
        }

        if (!empty($this->selected_platforms)) {
          $pq->andWhere(['o.platform_id' => $this->selected_platforms]);
        }

        if (!empty($this->selected_countries) || in_array('country', $this->showcolumns)) {
          $pq->leftJoin(TABLE_COUNTRIES . ' c', 'c.countries_name = o.billing_country and o.language_id = c.language_id ');
          if (!empty($this->selected_countries)) {
            $pq->andWhere(['c.countries_id' => $this->selected_countries]);
          }
        }


        if (!empty($this->orders)) {
          $pq->andWhere(['o.orders_id' => $this->orders]);
          $pq->andWhere(['op.orders_id' => $this->orders]);
        }

        if (!empty($this->selected_status)) {
          $pq->andWhere(['o.orders_status' => $this->selected_status]);
        }

        $pq->addOrderBy('backend_products_name, order_products_name');

//echo $pq->createCommand()->rawSql; die;

/*        $products_query = tep_db_query("SELECT p.products_id, sum(op.products_quantity) as products_quantity, op.products_tax, o.currency_value, o.currency, p.products_price, p2c.categories_id, ".ProductNameDecorator::instance()->listingQueryExpression('pd','')." AS products_name, avg(op.final_price) as final_price, o.billing_country FROM " . TABLE_PRODUCTS . " p
                        INNER JOIN " . TABLE_ORDERS_PRODUCTS . " op on op.products_id = p.products_id " .
                " LEFT JOIN " . TABLE_ORDERS . " o on op.orders_id = o.orders_id " .
                " LEFT join " . TABLE_COUNTRIES ." c on c.countries_name = o.billing_country and o.language_id = c.language_id ".
                "LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON p.products_id = pd.products_id 
                        LEFT JOIN " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c ON p.products_id = p2c.products_id                          
                        WHERE pd.language_id = FLOOR($languages_id) AND pd.platform_id='" . intval(\common\classes\platform::defaultId()) . "'" .
                ($categories ? "AND p2c.categories_id in (" . implode(",", $categories) . ")" : '' ) .
                ( ($this->start_date ) ? " and o.date_purchased >= '" . \common\helpers\Date::prepareInputDate($this->start_date) . " 00:00:00' " : '' ) .
                ( ($this->end_date ) ? " and o.date_purchased <= '" . \common\helpers\Date::prepareInputDate($this->end_date) . " 23:59:59' " : '' ) .
                ($this->selected_platforms ? " and o.platform_id in (" . implode(",", $this->selected_platforms) . ")" : '') .
                ($this->selected_countries ? " and c.countries_id in (" . implode(",", $this->selected_countries) . ")" : '') .
                " group by p.products_id, c.countries_id 
                        ORDER BY p2c.categories_id, pd.products_name");
*/

        $currencies = Yii::$container->get('currencies');
        
        //totals per category
        $itemQty = $itemPrice = $itemPurchasePrice = 0;
        //grand totals
        $totalQty = $totalPrice = $totalPurchasePrice = 0;
        /** @var int $memory current set category id */
        $memory = -1;
        /** @var array $trigger calculate total product qty by countries */
        $trigger = [];
        /** @var int $prev current set product id */
        $prev = 0;
        //while ($products = tep_db_fetch_array($products_query)) {
        foreach ($pq->asArray()->all() as $products) {

          $trigger[$products['products_id']]['name'] = $products['backend_products_name'];
          $trigger[$products['products_id']]['qty'] += $products['products_quantity'];
          $check_id = $products['categories_id'];

          if (in_array('country', $this->showcolumns)) {
            $trigger[$products['products_id']]['countries'][] = $products['countries_name'];
          }
          if (in_array('pp', $this->showcolumns)) {
            $pPrice = $this->getPurchasePrice($products['products_id'], $products['products_quantity']);
            $totalItemCountryPurchasePrice = $pPrice * $products['products_quantity'];
          }
          if (in_array('sp', $this->showcolumns)) {
            $avgItemCountryPrice = \common\helpers\Tax::add_tax_always($products['final_price'], $products['products_tax']);
            /** @var int $check_id current row category id */
            $totalItemCountryPrice = $avgItemCountryPrice * $products['products_quantity'];
          }
            
          if (in_array('cat', $this->showcolumns) && $check_id != $memory && $memory != -1) {
            //category total line
              $_row = [];
              if (in_array('cat', $this->showcolumns)) {
                $_row[] = '<div class="orange"></div>';
              }
              $_row = array_merge($_row, [
                  '<div class="orange"></div>',
                  '<div class="orange"><b>' . $itemQty . '</b></div>',
              ]);
              if (in_array('sp', $this->showcolumns)) {
                $_row = array_merge($_row, ['',
                  '<b>'.$currencies->format($itemPrice, false).'</b>']);
              }
              if (in_array('pp', $this->showcolumns)) {
                $_row = array_merge($_row, ['',
                  '<b>'.$currencies->format($itemPurchasePrice, false).'</b>']);
              }
              if (in_array('country', $this->showcolumns)) {
                $_row[] = '';
              }

              $responseList[] = $_row;

              $itemQty =  $itemPrice = $itemPurchasePrice = 0;
          }
          if (in_array('country', $this->showcolumns) && $prev != $products['products_id'] &&  array_key_exists($prev, $trigger) && count($trigger[$prev]['countries']) > 1){
            // all countries total row
            $_row = [];
            if (in_array('cat', $this->showcolumns)) {
              $_row[] = '<div class="orange"></div>';
            }
            $_row = array_merge($_row, [
                '<div class="orange">' . $trigger[$prev]['name'] . '</div>',
                '<div class="orange"><b>' . $trigger[$prev]['qty'] . '</b></div>',
            ]);
            if (in_array('sp', $this->showcolumns)) {
              $_row = array_merge($_row, ['',
                '']);
            }
            if (in_array('pp', $this->showcolumns)) {
              $_row = array_merge($_row, ['',
                '']);
            }
            if (in_array('country', $this->showcolumns)) {
              $_row[] = '<b>'.TABLE_HEADING_TOTAL.'</b>';
            }

            $responseList[] = $_row;

          }

          $itemQty += $products['products_quantity'];
          $totalQty += $products['products_quantity'];

          if (in_array('sp', $this->showcolumns)) {
            $itemPrice += $currencies->format_clear($totalItemCountryPrice, true, $products['currency'], $products['currency_value']);
            $totalPrice += $currencies->format_clear($totalItemCountryPrice, true, $products['currency'], $products['currency_value']);
          }

          if (in_array('pp', $this->showcolumns)) {
            $itemPurchasePrice += $currencies->format_clear($totalItemCountryPurchasePrice, true, $products['currency'], $products['currency_value']);
            $totalPurchasePrice += $currencies->format_clear($totalItemCountryPurchasePrice, true, $products['currency'], $products['currency_value']);
          }

          $_row = [];
          if (in_array('cat', $this->showcolumns)) {
            $_row[] =
                (($memory == $products['categories_id']) ? '' : $cat_info[$products['categories_id']]['link']);

          }

          $_row = array_merge($_row, ['<a target="_blank" href="' . tep_href_link('categories/productedit', 'pID=' . $products['products_id'], 'NONSSL') . '">' . $products['backend_products_name'] . "</a>",
                $products['products_quantity']]);
          if (in_array('sp', $this->showcolumns)) {
            $_row = array_merge($_row, [
              $currencies->format($avgItemCountryPrice, true, $products['currency'], $products['currency_value']),
              $currencies->format($totalItemCountryPrice, true, $products['currency'], $products['currency_value'])
              ]);
          }
          if (in_array('pp', $this->showcolumns)) {
            $_row = array_merge($_row, [
              $currencies->format($pPrice, true, $products['currency'], $products['currency_value']),
              $currencies->format($totalItemCountryPurchasePrice, true, $products['currency'], $products['currency_value'])
              ]);
          }
          if (in_array('country', $this->showcolumns)) {
            $_row[] = $products['countries_name'];
          }

          $responseList[] = $_row;
          $memory = $products['categories_id']; 
          $prev = $products['products_id'];
        }
        
///final totals
        if (in_array('country', $this->showcolumns) && array_key_exists($prev, $trigger) && count($trigger[$prev]['countries']) > 1){
            //all countries total
            $_row = [];
            if (in_array('cat', $this->showcolumns)) {
              $_row[] = '<div class="orange"></div>';
            }
            $_row = array_merge($_row, [
                '<div class="orange">' . $trigger[$prev]['name'] . '</div>',
                '<div class="orange"><b>' . $trigger[$prev]['qty'] . '</b></div>',
            ]);
            if (in_array('sp', $this->showcolumns)) {
              $_row = array_merge($_row, ['',
                '']);
            }
            if (in_array('pp', $this->showcolumns)) {
              $_row = array_merge($_row, ['',
                '']);
            }
            if (in_array('country', $this->showcolumns)) {
              $_row[] = '<b>'.TABLE_HEADING_TOTAL.'</b>';
            }

            $responseList[] = $_row;
        }

        if (in_array('cat', $this->showcolumns) ) {

          //category total line
          $_row = [];
          if (in_array('cat', $this->showcolumns)) {
            $_row[] = '<div class="orange"></div>';
          }
          $_row = array_merge($_row, [
              '<div class="orange"></div>',
              '<div class="orange"><b>' . $itemQty . '</b></div>',
          ]);
          if (in_array('sp', $this->showcolumns)) {
            $_row = array_merge($_row, ['',
              '<b>'.$currencies->format($itemPrice, false).'</b>']);
          }
          if (in_array('pp', $this->showcolumns)) {
            $_row = array_merge($_row, ['',
              '<b>'.$currencies->format($itemPurchasePrice, false).'</b>']);
          }
          if (in_array('country', $this->showcolumns)) {
            $_row[] = '';
          }

          $responseList[] = $_row;
        }

        //grand total line
        $_row = [];
        $_row[] = '<b>'.TABLE_HEADING_TOTAL.'</b>';
        if (in_array('cat', $this->showcolumns)) {
          $_row[] = '<div class="orange"></div>';
        }
        $_row = array_merge($_row, [
            '<div class="orange"><b>' . $totalQty . '</b></div>',
        ]);
        if (in_array('sp', $this->showcolumns)) {
          $_row = array_merge($_row, ['',
            '<b>'.$currencies->format($totalPrice, false).'</b>']);
        }
        if (in_array('pp', $this->showcolumns)) {
          $_row = array_merge($_row, ['',
            '<b>'.$currencies->format($totalPurchasePrice, false).'</b>']);
        }
        if (in_array('country', $this->showcolumns)) {
          $_row[] = '';
        }

        $responseList[] = $_row;

//not used
        $head->list = [
            TABLE_HEADING_TQT_ITEM => $totalQty,
            TABLE_HEADING_TCOST_PURCHASE_PRICE => $totalPurchasePrice,
            TABLE_HEADING_TCOST_SALE_PRICE => $totalPrice
        ];
        
        return ['responseList' => $responseList, 'head' => $head];
    }

    public function actionList() {

        $draw = Yii::$app->request->get('draw', 1);
        $start = Yii::$app->request->get('start', 0);
        $length = Yii::$app->request->get('length', 10);
        $current_page_number = ($start / $length) + 1;

        $output= [];
        parse_str(Yii::$app->request->get('filter'), $output);

        $this->parseFilterOptions($output);

        $data = $this->build();
        $responseList = $data['responseList'];

        $response = array(
            'draw' => $draw,
            'recordsTotal' => count($responseList),
            'recordsFiltered' => count($responseList),
            'data' => $responseList,
            'head' => $data['head']
        );
        echo json_encode($response);
    }

    public function actionExport() {        

        $this->parseFilterOptions(Yii::$app->request->get());
        
        $data = $this->build();
        $head = $this->getTable($this->showcolumns);
        
        $writer = new \backend\models\EP\Formatter\CSV('write', array('column_separator' => ';'), 'expo.csv');
        $a = [];
        foreach ($head as $m) {
            $a[] = $m['title'];
        }
        $writer->write_array($a);

        foreach ($data['responseList'] as $row) {
            $newArray = array_map(function($v) {
                $vv = trim(strip_tags($v));
                $vv = str_replace(['&nbsp;&raquo;&nbsp;', '&nbsp;',], [' / ', '',], $vv);
                return $vv;
            }, $row);
            $writer->write_array($newArray);
        }
        exit();
    }

/**
 * returns orders list according params in $this: $start_date; $end_date; $selected_platforms;
 * @return array
 */
    public function getOrders() {
      $ret = [];
      try {
        $fields = ['orders_id', 'customers_id', 'customers_name', 'customers_firstname', 'customers_lastname', 'customers_company', 'customers_telephone', 'customers_email_address', 'delivery_name', 'delivery_firstname', 'delivery_lastname', 'delivery_company', 'delivery_email_address', 'delivery_telephone', 'billing_name', 'billing_firstname', 'billing_lastname', 'billing_company', 'billing_email_address', 'billing_telephone', 'date_purchased'];
        $q = \common\models\Orders::find()->select($fields);
        if (!empty($this->start_date)) {
          $q->andWhere(['>=', 'date_purchased', \common\helpers\Date::prepareInputDate($this->start_date) . ' 00:00:00']);
        }
        if (!empty($this->end_date)) {
          $q->andWhere(['<=', 'date_purchased', \common\helpers\Date::prepareInputDate($this->end_date) . ' 23:59:59']);
        }
        if (!empty($this->selected_platforms)) {
          $q->andWhere(['platform_id' => $this->selected_platforms]);
        }
        if (!empty($this->selected_status)) {
          $q->andWhere(['orders_status' => $this->selected_status]);
        }
        if (!empty($this->keywords)) {
          $kwd = $this->keywords;
          $orLike = array_map(function ($el) use($kwd) { return ['and like', $el, $kwd];} , $fields);
          $q->andWhere(['or', $orLike]);
        }
        //echo $q->createCommand()->rawSql;
        $ret = $q->asArray()->indexBy('orders_id')->all();
        if (!empty($ret)){
          $ret = array_map(function ($el) {
            $el['option_text'] = implode(' ', array_merge([$el['orders_id']],
                      self::firstNotEmpty($el, ['email_address', 'name','company'], ['customers_', 'delivery_', 'billing_'])
                ));
            return $el;
          }, $ret);
        }

      } catch (\Exception $ex) {
        \Yii::warning($ex->getMessage() . ' ' . $ex->getTraceAsString());
      }
      return $ret;
    }

    public function actionFilterList() {
        $ret = ['result' => 'ok'];
        $this->start_date = tep_db_input(trim(Yii::$app->request->post('start_date', false)));
        if (empty($this->start_date)) {
          $this->start_date =  date(DATE_FORMAT_DATEPICKER_PHP, strtotime((date('Y-m-01'))));
        }
        $this->end_date = tep_db_input(trim(Yii::$app->request->post('end_date', false)));
        if (empty($this->end_date)) {
          $this->end_date =  date(DATE_FORMAT_DATEPICKER_PHP, strtotime((date('Y-m-d'))));
        }

        $this->selected_platforms = Yii::$app->request->post('platforms') ?? [];
        $this->selected_categories = Yii::$app->request->post('categories') ?? [];
        $this->selected_countries = Yii::$app->request->post('countries') ?? [];
        $this->selected_status = Yii::$app->request->post('status') ?? [];
        $this->showcolumns = Yii::$app->request->post('showcolumns', array_keys($this->getTable(false)));
        $orders = Yii::$app->request->post('orders', []);
        $list = Yii::$app->request->post('list', '');
        switch ($list) {
          case 'orders':
            $options = implode("\n", array_map(function ($el) use($orders) {
              return '<option value="' . $el['orders_id'] . '"' .
                  (in_array($el['orders_id'], $orders)?' selected':'') . '>' .
                  $el['option_text'] .
                  '</option>';
            }, $this->getOrders()));
        }
        if (empty($options)) {
          $options = '<option value="">' . NOTHING_FOUND_TEXT . '</option>';
          $ret['result'] = 'warning';
        }
        $ret['options'] = $options;


        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return $ret;
    }
    public static function firstNotEmpty($a, $fields, $prefs) {
      $ret = [];
      if (is_array($a) && is_array($fields) && is_array($prefs)) {
        foreach ($fields as $v) {
          foreach ($prefs as $p) {
            if (!empty($a[$p.$v])) {
              $ret[] = $a[$p.$v];
              break;
            }
          }
        }
      }
      return $ret;
    }

    public function getPurchasePrice($productId, $qty) {
      return \common\helpers\Suppliers::getDefaultProductPrice($productId);
    }
    
    private function parseFilterOptions($filters) {
      if (is_array($filters)) {
          $this->start_date = !empty($filters['start_date'])?tep_db_input(trim($filters['start_date'])) : date(DATE_FORMAT_DATEPICKER_PHP, strtotime((date('Y-m-01'))));
          $this->end_date = !empty($filters['end_date'])?tep_db_input(trim($filters['end_date'])) : date(DATE_FORMAT_DATEPICKER_PHP, strtotime(date('Y-m-d')));
          $this->selected_categories = $filters['categories'] ?? [];
          $this->selected_platforms = $filters['platforms'] ?? [];
          $this->selected_countries = $filters['countries'] ?? [];
          $this->selected_status = $filters['status'] ?? [];
          $this->showcolumns = $filters['showcolumns']?? ['_1', '_2', 'sp', 'sp_', 'pp', 'pp_'];//array_keys($this->getTable(['_1', '_2', 'sp', 'sp_', 'pp', 'pp_']));
          $this->orders = $filters['orders']?? [];
      }
    }
}
