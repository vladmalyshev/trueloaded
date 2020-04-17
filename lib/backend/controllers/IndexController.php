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

/**
 * default controller to handle user requests.
 */
class IndexController extends Sceleton {

    /**
     * Index action is the default action in a controller.
     */
    public function actionIndex() {
        $lang_var = '';
        $languages = \common\helpers\Language::get_languages();
        foreach ($languages as $lItem) {
            $lang_var .= '<a href="' . \Yii::$app->urlManager->createUrl(['index?language=']) . $lItem['code'] . '">' . $lItem['image_svg'] . '</a>';
        }
        $this->topButtons[] = '<div class="admin_top_lang">' . $lang_var . '</div>';
        return $this->render('index');
    }

    public function actionLocations() {
        $languages_id = \Yii::$app->settings->get('languages_id');

        $this->layout = false;

        if (\Yii::$app->request->isPost) {
            $order_id = \Yii::$app->request->post('order_id', 0);
            $lat = \Yii::$app->request->post('lat', 0);
            $lng = \Yii::$app->request->post('lng', 0);

            if ($order_id > 0) {
                tep_db_query("update " . TABLE_ORDERS . " set lat = '" . (float) $lat . "', lng = '" . (float) $lng . "' where orders_id = '" . (int) $order_id . "'");
            }
        } else {
            $date_from = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m') + 1, 1, date('Y') - 1));
            $orders_query = tep_db_query("select o.customers_postcode as pcode, c.countries_iso_code_2 as isocode, o.orders_id, concat(o.customers_postcode, ' ', o.customers_street_address, ' ', o.customers_city, ' ', o.customers_country) as address, concat(o.customers_street_address, ' ', o.customers_city, ' ', o.customers_country) as addressnocode from " . TABLE_ORDERS . " o left join " . TABLE_COUNTRIES . " c on o.customers_country = c.countries_name and c.language_id = '" . (int) $languages_id . "'  where o.date_purchased >= '" . tep_db_input($date_from) . "' and o.lat = 0 and o.lng = 0 limit 100");
            $to_search = [];
            $manager = \common\services\OrderManager::loadManager(new \common\classes\shopping_cart);
            while ($orders = tep_db_fetch_array($orders_query)) {
                $order = $manager->getOrderInstanceWithId('\common\classes\Order', (int)$orders['orders_id']);
                \Yii::$app->get('platform')->config($order->info['platform_id'])->constant_up();
                $manager->set('platform_id', $order->info['platform_id']);
                [$class, $method] = explode('_', $order->info['shipping_class']);
                $shipping = $manager->getShippingCollection()->get($class);
                if (is_object($shipping)) {
                    $collect = $shipping->toCollect($method);
                    if ($collect !== false) {
                        /** @var \common\classes\VO\CollectAddress $collect */
                        $orders['pcode'] = $collect->getPostcode();
                        $orders['isocode'] = $collect->getCountryISO2();
                        $orders['addressnocode'] = trim(trim(sprintf(
                            '%s, %s, %s, %s, %s',
                            $collect->getPostcode(),
                            $collect->getStreetAddress(),
                            $collect->getState(),
                            $collect->getCity(),
                            $collect->getCountryName()
                        ), ','));
                        $orders['address'] = empty($orders['pcode']) ? $orders['addressnocode'] : $collect->getStreetAddress();
                    }
                }
                $to_search[] = $orders;
            }

            $orders_query = tep_db_query("select o.lat, o.lng, o.customers_street_address, o.customers_suburb, o.customers_city, o.customers_postcode, o.customers_state, o.customers_country from " . TABLE_ORDERS . " o where o.date_purchased >= '" . tep_db_input($date_from) . "' and o.lat not in (0 , 9999) and o.lng not in (0 , 9999)");
            $founded = [];
            while ($orders = tep_db_fetch_array($orders_query)) {
                $orders['title'] = $orders['customers_street_address'] . "\n" . $orders['customers_city'] . "\n" . $orders['customers_postcode'] . "\n" . $orders['customers_state'] . "\n" . $orders['customers_country'];
                $founded[] = $orders;
            }

            echo json_encode(array(
                'to_search' => $to_search,
                'founded' => $founded,
                'orders_count' => count($founded),
            ));
        }
    }

    public function actionError() {

        if (($exception = \Yii::$app->getErrorHandler()->exception) === null) {
            $exception = new HttpException(404, \Yii::t('yii', 'Page not found.'));
        }

        if ($exception instanceof HttpException) {
            $code = $exception->statusCode;
        } else {
            $code = $exception->getCode();
        }
        if ($exception instanceof Exception) {
            $name = $exception->getName();
        } else {
            $name = \Yii::t('yii', 'Error');
        }
        if ($code) {
            $name .= " (#$code)";
        }

        if ($exception instanceof UserException) {
            $message = $exception->getMessage();
        } else {
            $message = \Yii::t('yii', 'An internal server error occurred.');
        }

        if (\Yii::$app->getRequest()->getIsAjax()) {
            return "$name: $message \n$exception";
        } else {
            $this->layout = 'error.tpl';
            return $this->render('error', [
                        'name' => $name,
                        'message' => $message,
                        'exception' => $exception,
            ]);
        }
    }

    public function actionOrder() {
        $languages_id = \Yii::$app->settings->get('languages_id');
        $responseList = array();
        if (defined('SUPERADMIN_ENABLED') && SUPERADMIN_ENABLED == True) {
            $currencies = \Yii::$container->get('currencies');
            $departments_query = tep_db_query("SELECT * FROM " . TABLE_DEPARTMENTS . " WHERE departments_status > 0");
            while ($department = tep_db_fetch_array($departments_query)) {
                $orders = tep_db_fetch_array(tep_db_query("select count(*) as count from " . TABLE_ORDERS . " where department_id=" . (int)$department['departments_id']));
                $customers = tep_db_fetch_array(tep_db_query("select count(*) as count from " . TABLE_CUSTOMERS . " c left join " . TABLE_CUSTOMERS_INFO . " ci on c.customers_id = ci.customers_info_id where customers_status = '1' and c.departments_id=" . (int)$department['departments_id']));
                $orders_amount = tep_db_fetch_array(tep_db_query("select sum(ot.value) as total_sum from " . TABLE_ORDERS . " o left join " . TABLE_ORDERS_TOTAL . " ot on (o.orders_id = ot.orders_id) where ot.class = 'ot_total' and o.department_id=" . (int)$department['departments_id']));

                $responseList[] = [
                    $department['departments_store_name'] . '<input class="cell_identify" type="hidden" value="' . $department['departments_id'] . '">',
                    number_format($customers['count']),
                    number_format($orders['count']),
                    $currencies->format($orders_amount['total_sum'])
                ];
            }
            $response = array(
                'data' => $responseList,
                'columns' => [
                    BOX_HEADING_DEPARTMENTS,
                    TEXT_CLIENTS,
                    BOX_CUSTOMERS_ORDERS,
                    TEXT_AMOUNT_FILTER
                ]
            );
        } else {
            $orders_query = tep_db_query(
                "select o.orders_id, o.customers_name, o.customers_email_address, o.delivery_postcode, ".
                " o.payment_method, o.date_purchased, o.last_modified, o.currency, o.currency_value, s.orders_status_name, ".
                " ot.text as order_total ".
                "from " . TABLE_ORDERS_STATUS . " s, " . TABLE_ORDERS . " o ".
                "  left join " . TABLE_ORDERS_TOTAL . " ot on (o.orders_id = ot.orders_id) ".
                "where o.orders_status = s.orders_status_id and s.language_id = '" . (int) $languages_id . "' and ot.class = 'ot_total' ".
                "/*group by o.orders_id*/ ".
                "order by o.date_purchased desc limit 6"
            );
            while ($orders = tep_db_fetch_array($orders_query)) {
                $responseList[] = array(
                    $orders['customers_name'] . '<input class="cell_identify" type="hidden" value="' . $orders['orders_id'] . '">',
                    strip_tags($orders['order_total']),
                    $orders['orders_id'],
                    $orders['delivery_postcode']
                );
            }
            $response = array(
                'data' => $responseList,
                'columns' => [
                    'Customers',
                    'Order Total',
                    'Order Id',
                    'Post Code'
                ]
            );
        }
        echo json_encode($response);
    }

    public function actionDashboardOrderStat()
    {
        $exclude_order_statuses_array = \common\helpers\Order::extractStatuses(DASHBOARD_EXCLUDE_ORDER_STATUSES);
        $currencies = \Yii::$container->get('currencies');

        $order_stats_query =
            "SELECT ".
            "  COUNT(o.orders_id) AS orders, " .
            "  SUM(IF(o.orders_status=1,1,0)) AS orders_new, ".
            "  SUM(ott.value) as total_sum, AVG(ots.value) as total_avg ".
            "FROM " . TABLE_ORDERS . " o ".
            "  LEFT JOIN " . TABLE_ORDERS_TOTAL . " ott ON (o.orders_id = ott.orders_id) AND ott.class = 'ot_total' ".
            "  LEFT JOIN " . TABLE_ORDERS_TOTAL . " ots ON (o.orders_id = ots.orders_id) and ots.class = 'ot_subtotal' ".
            "WHERE 1=1 ".
            "  AND o.orders_status not in ('" . implode("','", $exclude_order_statuses_array) . "') ";
        $range_stat = tep_db_fetch_array(tep_db_query($order_stats_query));
        $stats['all']['orders'] = number_format($range_stat['orders']);
        $stats['all']['orders_not_processed'] = number_format($range_stat['orders_new']);
        $stats['all']['orders_avg_amount'] = $currencies->format($range_stat['total_avg']);
        $stats['all']['orders_amount'] = $currencies->format($range_stat['total_sum']);
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        \Yii::$app->response->data = $stats;
    }

    private function getProduct($categories_id = '0') {
        $languages_id = \Yii::$app->settings->get('languages_id');
        $currencies = \Yii::$container->get('currencies');

        $productList = [];
        $products_query = tep_db_query("select p.products_id, ".ProductNameDecorator::instance()->listingQueryExpression('pd','')." AS products_name from " . TABLE_PRODUCTS . " p LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " as pd on p.products_id = pd.products_id LEFT JOIN " . TABLE_PRODUCTS_TO_CATEGORIES . " as p2c on p.products_id = p2c.products_id where pd.language_id = '" . (int) $languages_id . "' and pd.platform_id = '".intval(\common\classes\platform::defaultId())."' and p2c.categories_id=" . $categories_id . " group by p.products_id order by p2c.sort_order, pd.products_name");
        while ($products = tep_db_fetch_array($products_query)) {
            $productList[] = [
                'id' => $products['products_id'],
                'value' => $products['products_name'],
                'image' => \common\classes\Images::getImageUrl($products['products_id'], 'Small'),
                'title' => $products['products_name'],
                'price' => $currencies->format(\common\helpers\Product::get_products_price($products['products_id'], 1, 0, $currencies->currencies[DEFAULT_CURRENCY]['id'])),
            ];
        }
        return $productList;
    }

    private function getTree($parent_id = '0') {
        $languages_id = \Yii::$app->settings->get('languages_id');

        $categoriesTree = [];
        $categories_query = tep_db_query("select c.categories_id, cd.categories_name, c.parent_id from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where c.categories_id = cd.categories_id and cd.language_id = '" . (int) $languages_id . "' and c.parent_id = '" . (int) $parent_id . "' and affiliate_id = 0 order by c.sort_order, cd.categories_name");
        while ($categories = tep_db_fetch_array($categories_query)) {
            $products = tep_db_fetch_array(tep_db_query("select count(*) as total from " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c, " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES . " c1, " . TABLE_PRODUCTS . " p  where p.products_id = p2c.products_id and p2c.categories_id = c.categories_id and c1.categories_id = '" . (int) $categories['categories_id'] . "' and (c.categories_left >= c1.categories_left and c.categories_right <= c1.categories_right) "));
            if ($products['total'] > 0) {
                $categoriesTree[] = [
                    'id' => $categories['categories_id'],
                    'text' => $categories['categories_name'],
                    'child' => $this->getTree($categories['categories_id']),
                    'products' => $this->getProduct($categories['categories_id']),
                ];
            }
        }
        return $categoriesTree;
    }

    private function renderTree($response, $spacer = '') {
        $html = '';
        if (is_array($response)) {
            foreach ($response as $key => $value) {
                $html .= '<strong>' . $spacer . $value['text'] . '</strong>';
                if (isset($value['products'])) {
                    foreach ($value['products'] as $pkey => $pvalue) {
                        $html .= '<a href="javascript:void(0)" ' . ($_GET['no_click'] ? '' : ' onclick="return searchSuggestSelected(' . $pvalue['id'] . ', \'' . $pvalue['value'] . '\');" ') . ' class="item" data-id="' . $pvalue['id'] . '">
        <span class="suggest_table">
            <span class="td_image"><img src="' . $pvalue['image'] . '" alt=""></span>
            <span class="td_name">' . $pvalue['title'] . '</span>
            <span class="td_price">' . $pvalue['price'] . '</span>
        </span>
    </a>';
                    }
                }
                if (isset($value['child'])) {
                    $html .= $this->renderTree($value['child'], $spacer . ' ' . $value['text'] . ' > ');
                }
            }
        }
        return $html;
    }

    public function actionSearchSuggest() {
        $this->layout = false;
        $languages_id = \Yii::$app->settings->get('languages_id');

        $get = \Yii::$app->request->get();
        $lang_id = $get['languages_id'] ? $get['languages_id'] : $languages_id;
        $customer_groups_id = 0;
        $currency_id = 0;

        $currencies = \Yii::$container->get('currencies');

        $response = array();

        if (isset($_GET['keywords']) && $_GET['keywords'] != '') {
            $_SESSION['keywords'] = tep_db_input(tep_db_prepare_input($_GET['keywords']));
            //Add slashes to any quotes to avoid SQL problems.
            $search = preg_replace("/\//", '', tep_db_input(tep_db_prepare_input($_GET['keywords'])));
            $where_str_categories = "";
            $where_str_gapi = "";
            $where_str_products = "";
            $where_str_manufacturers = "";
            $where_str_information = "";
            $replace_keywords = array();

            if (\common\helpers\Output::parse_search_string($search, $search_keywords, false)) {
                $where_str_categories .= " and (";
                $where_str_gapi .= " and (";
                $where_str_products .= " and (";
                $where_str_manufacturers .= " (";
                $where_str_information .= " and (";
                for ($i = 0, $n = sizeof($search_keywords); $i < $n; $i++) {
                    switch ($search_keywords[$i]) {
                        case '(':
                        case ')':
                        case 'and':
                        case 'or':
                            $where_str_gapi .= " " . $search_keywords[$i] . " ";
                            $where_str_categories .= " " . $search_keywords[$i] . " ";
                            $where_str_products .= " " . $search_keywords[$i] . " ";
                            $where_str_manufacturers .= " " . $search_keywords[$i] . " ";
                            $where_str_information .= " " . $search_keywords[$i] . " ";
                            break;
                        default:

                            $keyword = tep_db_prepare_input($search_keywords[$i]);
                            $replace_keywords[] = $search_keywords[$i];
                            $where_str_gapi .= " gs.gapi_keyword like '%" . tep_db_input($keyword) . "%' or  gs.gapi_keyword like '%" . tep_db_input($keyword) . "%' ";

                            $where_str_products .= "(if(length(pd1.products_name), pd1.products_name, pd.products_name) like '%" . tep_db_input($keyword) . "%' or pd.products_internal_name like '%" . tep_db_input($keyword) . "%' or p.products_model like '%" . tep_db_input($keyword) . "%' or m.manufacturers_name like '%" . tep_db_input($keyword) . "%'  or if(length(pd1.products_head_keywords_tag), pd1.products_head_keywords_tag, pd.products_head_keywords_tag) like '%" . tep_db_input($keyword) . "%' or  gs.gapi_keyword like '%" . tep_db_input($keyword) . "%' )";
                            $where_str_categories .= "(if(length(cd1.categories_name), cd1.categories_name, cd.categories_name) like '%" . tep_db_input($keyword) . "%' or if(length(cd1.categories_description), cd1.categories_description, cd.categories_description) like '%" . tep_db_input($keyword) . "%')";

                            $where_str_manufacturers .= "(manufacturers_name like '%" . tep_db_input($keyword) . "%')";

                            $where_str_information .= "(if(length(i1.info_title), i1.info_title, i.info_title) like '%" . tep_db_input($keyword) . "%' or if(length(i1.description), i1.description, i.description) like '%" . tep_db_input($keyword) . "%' or if(length(i1.page_title), i1.page_title, i.page_title) like '%" . tep_db_input($keyword) . "%')";
                            break;
                    }
                }
                $where_str_categories .= ") ";
                $where_str_gapi .= ") ";
                $where_str_products .= ") ";
                $where_str_manufacturers .= ") ";
                $where_str_information .= ") ";
            } else {
                $replace_keywords[] = $search;
                $where_str_gapi .= "and gs.gapi_keyword like ('%" . $search . "%')))";
                $where_str_products .= "and (if(length(pd1.products_name), pd1.products_name like ('%" . $search . "%'), pd.products_name like ('%" . $search . "%'))  or if(length(pd1.products_head_keywords_tag), pd1.products_head_keywords_tag, pd.products_head_keywords_tag) like '%" . $search . "%'  or gs.gapi_keyword like ('%" . $search . "%'))";
                $where_str_categories .= "and (if(length(cd1.categories_name), cd1.categories_name like ('%" . $search . "%'), cd.categories_name like ('%" . $search . "%')) or if(length(cd1.categories_description), cd1.categories_description like ('%" . $search . "%'), cd.categories_description like ('%" . $search . "%'))  )";
                $where_str_manufacturers .= " (manufacturers_name like '%" . $search . "%')";
                $where_str_information .= "and (if(length(i1.info_title), i1.info_title, i.info_title) like '%" . $search . "%' or if(length(i1.description), i1.description, i.description) like '%" . $search . "%' or if(length(i1.page_title), i1.page_title, i.page_title) like '%" . $search . "%')";
            }

            $from_str = "select c.categories_id, if(length(cd1.categories_name), cd1.categories_name, cd.categories_name) as categories_name,  (if(length(cd1.categories_name), if(position('" . $search . "' IN cd1.categories_name), position('" . $search . "' IN cd1.categories_name), 100), if(position('" . $search . "' IN cd.categories_name), position('" . $search . "' IN cd.categories_name), 100))) as pos, 1 as is_category  from " . TABLE_CATEGORIES . " c " . ($_SESSION['affiliate_ref'] > 0 ? " LEFT join " . TABLE_CATEGORIES_TO_AFFILIATES . " c2a on c.categories_id = c2a.categories_id  and c2a.affiliate_id = '" . (int) $_SESSION['affiliate_ref'] . "' " : '') . " left join " . TABLE_CATEGORIES_DESCRIPTION . " cd1 on cd1.categories_id = c.categories_id and cd1.language_id='" . $lang_id . "' and cd1.affiliate_id = '" . (int) $_SESSION['affiliate_ref'] . "', " . TABLE_CATEGORIES_DESCRIPTION . " cd where c.categories_status = 1 " . ($_SESSION['affiliate_ref'] > 0 ? " and c2a.affiliate_id is not null " : '') . " and cd.affiliate_id = 0 and cd.categories_id = c.categories_id and cd.language_id = '" . $lang_id . "' " . $where_str_categories . " and c.quick_find = 1 order by pos limit 0, 3";

            $sql_gapi = "
      select   p.products_id, ".ProductNameDecorator::instance()->listingQueryExpression('pd','pd1')." AS products_name, m.manufacturers_name,
          (if(length(pd1.products_name),
            if(position('" . $search . "' IN pd1.products_name),
              position('" . $search . "' IN pd1.products_name),
              100
            ),
            if(position('" . $search . "' IN pd.products_name),
              position('" . $search . "' IN pd.products_name),
              100
            )
          )) as pos, 0 as is_category,
		  p.products_image
      from   " . TABLE_PRODUCTS . " p
          left join " . TABLE_PRODUCTS_DESCRIPTION . " pd1 on pd1.products_id = p.products_id and pd1.language_id='" . (int) $lang_id . "'
                                              and pd1.platform_id = '" . intval(\common\classes\platform::defaultId()) . "' 
          left join " . TABLE_PRODUCTS_PRICES . " pp on p.products_id = pp.products_id and pp.groups_id = '" . (int) $customer_groups_id . "'
                                          and pp.currencies_id = '" . (USE_MARKET_PRICES == 'True' ? (int) $currency_id : '0') . "'
		left join gapi_search_to_products gsp on p.products_id = gsp.products_id
		left join gapi_search gs on gsp.gapi_id = gs.gapi_id
        left join " . TABLE_MANUFACTURERS . " m on m.manufacturers_id = p.manufacturers_id ,
        " . TABLE_PRODUCTS_DESCRIPTION . " pd
            
    where   p.products_status = 1
      and   p.products_id = pd.products_id
      and   pd.language_id = '" . (int) $lang_id . "'
      and   if(pp.products_group_price is null, 1, pp.products_group_price != -1 ) ".                                                      
      "and   pd.platform_id = '".intval(\common\classes\platform::defaultId())."' " . $where_str_gapi . " order by gsp.sort, pos ";

            $sql = "
      select   p.products_id, ".ProductNameDecorator::instance()->listingQueryExpression('pd','')." AS products_name, m.manufacturers_name,
          (if(length(pd1.products_name),
            if(position('" . $search . "' IN pd1.products_name),
              position('" . $search . "' IN pd1.products_name),
              100
            ),
            if(position('" . $search . "' IN pd.products_name),
              position('" . $search . "' IN pd.products_name),
              100
            )
          )) as pos, 0 as is_category,
		  p.products_image,
		  s.specials_id, s.specials_new_products_price
      from   " . TABLE_PRODUCTS . " p
          left join " . TABLE_PRODUCTS_DESCRIPTION . " pd1 on pd1.products_id = p.products_id and pd1.language_id='" . (int) $lang_id . "'
                                              and pd1.platform_id = '" . intval(\common\classes\platform::defaultId()) . "'
          left join " . TABLE_PRODUCTS_PRICES . " pp on p.products_id = pp.products_id and pp.groups_id = '" . (int) $customer_groups_id . "'
                                          and pp.currencies_id = '" . (USE_MARKET_PRICES == 'True' ? (int) $currency_id : '0') . "'
		LEFT JOIN " . TABLE_INVENTORY . " i on p.products_id = i.prid
		left join gapi_search_to_products gsp on p.products_id = gsp.products_id
		left join gapi_search gs on gsp.gapi_id = gs.gapi_id
        left join " . TABLE_MANUFACTURERS . " m on m.manufacturers_id = p.manufacturers_id
        left join " . TABLE_SPECIALS . " s on s.products_id = p.products_id,
        " . TABLE_PRODUCTS_DESCRIPTION . " pd
    where   p.products_status = 1
    " . ($_SESSION['affiliate_ref'] > 0 ? " and p2a.affiliate_id is not null " : '') . "
      and   p.products_id = pd.products_id
      and   pd.language_id = '" . (int) $lang_id . "'
      and   if(pp.products_group_price is null, 1, pp.products_group_price != -1 )
      and   pd.platform_id = '" . intval(\common\classes\platform::defaultId()) . "'
    " . $where_str_products . "
	group by p.products_id
    order by gapi_keyword desc, gsp.sort, products_name, pos
    limit   0, 10
  ";

            /**
             * Set XML HTTP Header for ajax response
             */
            reset($replace_keywords);
            foreach ($replace_keywords as $k => $v) {
                $patterns[] = "/" . preg_quote($v) . "/i";
                $replace[] = str_replace('$', '/$/', '<span class="typed">' . $v . '</span>');
            }

            $re = array();
            foreach ($replace_keywords as $k => $v)
                $re[] = preg_quote($v);
            $re = "/(" . join("|", $re) . ")/i";
            $replace = '<span class="typed">\1</span>';

            $product_query = tep_db_query($sql);

            $json = \Yii::$app->request->get('json', 0);
            $platform_id = \Yii::$app->request->get('platform_id', intval(\common\classes\platform::defaultId()));
            while ($product_array = tep_db_fetch_array($product_query)) {
                if ($json) {
                    $link = tep_catalog_href_link('catalog/product', 'products_id=' . $product_array['products_id'], '', $platform_id);
                } else {
                    $link = \Yii::$app->urlManager->createUrl(['catalog/product', 'products_id' => $product_array['products_id']]);
                }
                $specials_product_price = '';
                if( USE_MARKET_PRICES == 'True' ) {
                    if ($product_array['specials_id']) {
                        $specials_product_price = $currencies->format(\common\helpers\Product::get_specials_price($product_array['specials_id'], $currencies->currencies[DEFAULT_CURRENCY]['id']));
                    }
                } else {
                    if ($product_array['specials_new_products_price']) {
                        $specials_product_price = $currencies->format($product_array['specials_new_products_price']);
                    }
                }
                $response[] = array(
                    'id' => $product_array['products_id'],
                    'value' => addslashes($product_array['products_name']),
                    'link' => $link,
                    'image' => \common\classes\Images::getImageUrl($product_array['products_id'], 'Small'),
                    'title' => preg_replace($re, $replace, strip_tags($product_array['products_name'])),
                    'price' => $currencies->format(\common\helpers\Product::get_products_price($product_array['products_id'], 1, 0, $currencies->currencies[DEFAULT_CURRENCY]['id'])),
                    'special_price' => $specials_product_price,
                );
            }

            if ($json) {
                return json_encode($response);
            }
            return $this->render('search.tpl', ['list' => $response, 'no_click' => $_GET['no_click']]);
        } else {
            $response = $this->getTree();
            return $this->renderTree($response);
        }
    }

    public function actionEnableMap() {
        $configuration_id = \Yii::$app->request->get('configuration_id', 0);
        $status = \Yii::$app->request->get('status', 'false');

        if ($configuration_id) {
            tep_db_query('update ' . TABLE_CONFIGURATION . ' set configuration_value = "' . tep_db_input($status) . '" where configuration_id = "' . (int) $configuration_id . '"');
            echo 'ok';
            exit();
        }
        return false;
    }

    public function actionLoadLanguagesJs() {
        $list = \common\helpers\Translation::loadJS('admin/js');
        return \common\widgets\JSLanguage::widget(['list' => $list]);
    }

}
