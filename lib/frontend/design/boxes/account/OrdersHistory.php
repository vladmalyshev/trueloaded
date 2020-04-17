<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\account;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;
use frontend\design\SplitPageResults;
use common\helpers\Date as DateHelper;

class OrdersHistory extends Widget
{

    public $file;
    public $params;
    public $settings;

    public function init()
    {
        parent::init();
    }

    public function run()
    {
        global $cart, $languages_id, $language, $navigation, $breadcrumb;

        if (defined($this->settings[0]['text'])) {
            $text_link = constant($this->settings[0]['text']);
        }
        if (!$text_link) {
            $text_link = $this->settings[0]['link'];
            if (!$this->settings[0]['link']) {
                $text_link = SMALL_IMAGE_BUTTON_VIEW;
            }
        }
        $page = \common\classes\design::pageName($this->settings[0]['link']);

        if (defined($this->settings[0]['text_pay'])) {
            $text_link_pay = constant($this->settings[0]['text_pay']);
        }
        if (!$text_link_pay) {
            $text_link_pay = $this->settings[0]['link_pay'];
            if (!$this->settings[0]['link_pay']) {
                $text_link_pay = ORDER_PAY;
            }
        }
        $page_pay = \common\classes\design::pageName($this->settings[0]['link_pay']);

        $max_orders = $this->settings[0]['max_orders'] ? $this->settings[0]['max_orders'] : MAX_DISPLAY_ORDER_HISTORY;

        $orders_total = \common\helpers\Customer::count_customer_orders();
        $history_query_raw = "select o.orders_id, o.date_purchased, o.delivery_name, o.billing_name, ot.text as order_total, s.orders_status_name from " . TABLE_ORDERS . " o, " . TABLE_ORDERS_TOTAL . " ot, " . TABLE_ORDERS_STATUS . " s where o.customers_id = '" . (int)Yii::$app->user->getId() . "' and o.orders_id = ot.orders_id and ot.class = 'ot_total' and o.orders_status = s.orders_status_id and s.language_id = '" . (int) $languages_id . "' order by orders_id DESC";
        $history_split = new splitPageResults($history_query_raw, $max_orders);
        $history_query = tep_db_query($history_split->sql_query);
        $history_links = $history_split->display_links(MAX_DISPLAY_PAGE_LINKS, \common\helpers\Output::get_all_get_params(array('page', 'info', 'x', 'y')), 'account');
        $history_array = array();
        while ($history = tep_db_fetch_array($history_query)) {
            $products_query = tep_db_query("select count(*) as count from " . TABLE_ORDERS_PRODUCTS . " where orders_id = '" . (int) $history['orders_id'] . "'");
            $products = tep_db_fetch_array($products_query);

            if (tep_not_null($history['delivery_name'])) {
                $history['type'] = TEXT_ORDER_SHIPPED_TO;
                $history['name'] = $history['delivery_name'];
            } else {
                $history['type'] = TEXT_ORDER_BILLED_TO;
                $history['name'] = $history['billing_name'];
            }
            $history['count'] = $products['count'];
            $history['date'] = DateHelper::date_long($history['date_purchased']);
            $history['reorder_link'] = tep_href_link('checkout/reorder', 'order_id=' . (int) $history['orders_id'], 'SSL');
            $history['reorder_confirm'] = ($cart->count_contents() > 0 ? REORDER_CART_MERGE_WARN : '');

            if ($page) {
                $history['link'] = Yii::$app->urlManager->createUrl(['account', 'page_name' => $page, 'order_id' => $history['orders_id'], 'page' => (int)$_GET['page']]);
            } else {
                $history['link'] = Yii::$app->urlManager->createUrl(['account', 'order_id' => $history['orders_id'], 'page' => (int)$_GET['page']]);
            }

            $pay_link = false;
            if ($ext = \common\helpers\Acl::checkExtension('UpdateAndPay', 'payLink')) {
                $pay_link = $ext::payLink($history['orders_id']);
                if ($pay_link) {
                    /*if ($page_pay) {
                        $pay_link = Yii::$app->urlManager->createUrl(['account', 'page_name' => $page_pay, 'order_id' => $history['orders_id']]);
                    } else {
                        $pay_link = Yii::$app->urlManager->createUrl(['account', 'order_id' => $history['orders_id']]);
                    }*/
                }
            }
            $history['pay_link'] = $pay_link;

            $statusProgress = [];
            $lastStatusGroup = 0;
            $statuses_query = tep_db_query("select osg.orders_status_groups_id, osg.orders_status_groups_color from " . TABLE_ORDERS_STATUS_HISTORY . " AS osh LEFT JOIN " . TABLE_ORDERS_STATUS . " AS os ON (osh.orders_status_id=os.orders_status_id and os.language_id = '" . (int) $languages_id . "') LEFT JOIN " . TABLE_ORDERS_STATUS_GROUPS . " AS osg ON (os.orders_status_groups_id=osg.orders_status_groups_id and osg.language_id = '" . (int) $languages_id . "') where osh.orders_id = '" . (int)$history['orders_id'] . "' order by osh.date_added");
            while ($statuses = tep_db_fetch_array($statuses_query)) {
                if ($lastStatusGroup != $statuses['orders_status_groups_id']) {
                    $statusProgress[] = $statuses['orders_status_groups_color'];
                }
                $lastStatusGroup = $statuses['orders_status_groups_id'];
            }
            $history['progress'] = $statusProgress;
            
            $history_array[] = $history;
        }
        
        return IncludeTpl::widget(['file' => 'boxes/account/orders-history.tpl', 'params' => [
            'mainData' => $this->params['mainData'],
            'orders_total' => $orders_total,
            'history_array' => $history_array,
            'number_of_rows' => $history_split->number_of_rows,
            'links' => $history_links,
            'history_count' => $history_split->display_count(LISTING_PAGINATION),
            'text_link' => $text_link,
            'text_link_pay' => $text_link_pay,
            'settings' => $this->settings,
        ]]);
    }
}