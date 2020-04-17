<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\product;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;

class Groups extends Widget {

    public $file;
    public $params;
    public $settings;

    public function init() {
        parent::init();
    }

    public function run() {
        $languages_id = \Yii::$app->settings->get('languages_id');
        $params = Yii::$app->request->get();
        $currencies = \Yii::$container->get('currencies');
        if ($params['products_id']) {
            $listing_sql_array = \frontend\design\ListingSql::get_listing_sql_array(FILENAME_ALL_PRODUCTS);
            $product = tep_db_fetch_array(tep_db_query("select p.products_groups_id, count(p.products_id) as products_groups_count from " . $listing_sql_array['from'] . " " . TABLE_PRODUCTS . " p1, " . TABLE_PRODUCTS . " p " . $listing_sql_array['left_join'] . " where p1.products_id = '" . (int) $params['products_id'] . "' and p1.products_groups_id > 0 and p1.products_groups_id = p.products_groups_id " . $listing_sql_array['where'] . " group by p.products_groups_id"));

            if ($product['products_groups_count'] > 1) {
                $products_array = array();
                $properties_array = array();
                $products_query = tep_db_query("select p.products_id, p.products_price, products_tax_class_id, if(length(pd1.products_name), pd1.products_name, pd.products_name) as products_name from " . $listing_sql_array['from'] . " " . TABLE_PRODUCTS . " p left join " . TABLE_PRODUCTS_DESCRIPTION . " pd on pd.products_id = p.products_id and pd.language_id = '" . (int) $languages_id . "' and pd.platform_id = '".intval(\common\classes\platform::defaultId())."' left join " . TABLE_PRODUCTS_DESCRIPTION . " pd1 on pd1.products_id = p.products_id and pd1.language_id = '" . (int) $languages_id . "' and pd1.platform_id = '" . (int)Yii::$app->get('platform')->config()->getPlatformToDescription() . "' " . $listing_sql_array['left_join'] . " where p.products_groups_id = '" . (int) $product['products_groups_id'] . "' " . $listing_sql_array['where'] . " order by products_name");
                while ($products = tep_db_fetch_array($products_query)) {
                    $products_array[$products['products_id']] = array(
                        'id' => $products['products_id'],
                        'name' => $products['products_name'],
                        'image' => \common\classes\Images::getImageUrl($products['products_id'], 'Small'),
                        'link' => tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $products['products_id']),
                        'price' => $currencies->display_price(\common\helpers\Product::get_products_price($products['products_id'], 1, $products['products_price']), \common\helpers\Tax::get_tax_rate($products['products_tax_class_id'])),
                        'selected' => ($products['products_id'] == $params['products_id']),
                    );
                }

                $properties_query = tep_db_query("select pr.properties_id, pr.properties_type, prd.properties_name, count(distinct p.products_id) as products_count, group_concat(distinct pr2p.values_id separator ',') as values_id from " . $listing_sql_array['from'] . " " . TABLE_PROPERTIES . " pr, " . TABLE_PROPERTIES_DESCRIPTION . " prd, " . TABLE_PROPERTIES_TO_PRODUCTS . " pr2p, " . TABLE_PRODUCTS . " p " . $listing_sql_array['left_join'] . " where pr.properties_id = pr2p.properties_id and pr.products_groups = '1' and pr.properties_id = prd.properties_id and prd.language_id = '" . (int) $languages_id . "' and pr2p.products_id = p.products_id and p.products_groups_id = '" . (int) $product['products_groups_id'] . "' " . $listing_sql_array['where'] . " group by pr.properties_id order by pr.sort_order, prd.properties_name");
                if (tep_db_num_rows($properties_query) > 0) {
                    while ($properties = tep_db_fetch_array($properties_query)) {
                        $current = tep_db_fetch_array(tep_db_query("select values_id, values_flag from " . TABLE_PROPERTIES_TO_PRODUCTS . " where properties_id = '" . (int) $properties['properties_id'] . "' and products_id = '" . (int) $params['products_id'] . "'"));
                        $properties['current_value'] = $current['values_id'];
                        $properties_array[$properties['properties_id']] = $properties;
                    }

                    foreach ($properties_array as $id1 => $prop1) {
                        $values_query = tep_db_query("select count(distinct p.products_id) as products_count, pv.values_id, pv.values_text, values_alt, pv.values_number, pv.values_number_upto, pr2p.values_flag from " . $listing_sql_array['from'] . " " . TABLE_PROPERTIES_TO_PRODUCTS . " pr2p left join " . TABLE_PROPERTIES_VALUES . " pv on pv.properties_id = pr2p.properties_id and pv.values_id = pr2p.values_id and pv.language_id = '" . (int) $languages_id . "', " . TABLE_PRODUCTS . " p " . $listing_sql_array['left_join'] . " where pr2p.properties_id = '" . (int) $prop1['properties_id'] . "' and pr2p.products_id = p.products_id and p.products_groups_id = '" . (int) $product['products_groups_id'] . "' " . $listing_sql_array['where'] . " group by pv.values_id order by pv.values_text");
                        while ($values = tep_db_fetch_array($values_query)) {
                            if ($prop1['properties_type'] == 'file') {
                                $values['image'] = $values['values_text'];
                                $values['text'] = $values['values_alt'];
                            } else {
                                $values['text'] = $values['values_text'];
                            }
                            $product_sql_array = array('from' => '', 'where' => '');
                            foreach ($properties_array as $id2 => $prop2) {
                                $product_sql_array['from'] .= TABLE_PROPERTIES_TO_PRODUCTS . " pr2p" . $id2 . ", ";
                                $product_sql_array['where'] .= " and p.products_id = pr2p" . $id2 . ".products_id and pr2p" . $id2 . ".properties_id = '" . (int) $prop2['properties_id'] . "' and pr2p" . $id2 . ".values_id = '" . (int) ($prop1['properties_id'] == $prop2['properties_id'] ? $values['values_id'] : $prop2['current_value']) . "'";
                            }
                            $prod = tep_db_fetch_array(tep_db_query("select p.products_id from " . $listing_sql_array['from'] . " " . $product_sql_array['from'] . " " . TABLE_PRODUCTS . " p " . $listing_sql_array['left_join'] . " where p.products_groups_id = '" . (int) $product['products_groups_id'] . "' " . $product_sql_array['where'] . $listing_sql_array['where']));
                            if ($prod['products_id'] > 0) {
                                $values['product'] = array(
                                    'id' => $prod['products_id'],
                                    'name' => $products_array[$prod['products_id']]['name'],
                                    'image' => $products_array[$prod['products_id']]['image'],
                                    'link' => $products_array[$prod['products_id']]['link'],
                                    'price' => $products_array[$prod['products_id']]['price'],
                                    'selected' => ($values['values_id'] == $prop1['current_value']),
                                );
                                $properties_array[$id1]['values'][$values['values_id']] = $values;
                            }
                        }
                    }
                }
                return IncludeTpl::widget(['file' => 'boxes/product/groups.tpl', 'params' => ['products' => $products_array, 'properties' => $properties_array, 'params' => $this->params]]);
            }
        }
    }

}
