<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\helpers;

class Collections {

    public static function collections_name($collections_id, $language_id = '') {
        global $languages_id;

        if (!$language_id) {
            $language_id = $languages_id;
        }

        $collections_query = tep_db_query("select collections_name from " . TABLE_COLLECTIONS . " where collections_id = '" . (int)$collections_id . "' and language_id = '" . (int)$language_id . "'");
        $collections = tep_db_fetch_array($collections_query);

        return $collections['collections_name'];
    }

    public static function getDetails($params) {
        global $languages_id;

        if ( !$params['products_id'] ) return '';
        $currencies = \Yii::$container->get('currencies');
        if ( !is_array($params['collections']) ) $params['collections'] = array($params['products_id']);

        $listing_sql_array = \frontend\design\ListingSql::get_listing_sql_array('catalog/all-products');
        $collection = tep_db_fetch_array(tep_db_query("select c.collections_id, c.collections_name, c.collections_image, c.collections_type, count(p.products_id) as collections_products_count from " . $listing_sql_array['from'] . " " . TABLE_COLLECTIONS_TO_PRODUCTS . " c2p1, " . TABLE_COLLECTIONS . " c, " . TABLE_COLLECTIONS_TO_PRODUCTS . " c2p2, " . TABLE_PRODUCTS . " p " . $listing_sql_array['left_join'] . " where c2p1.products_id = '" . (int) $params['products_id'] . "' and c2p1.collections_id = c.collections_id and c.language_id = '" . (int)$languages_id . "' and c2p2.collections_id = c.collections_id and c2p2.products_id = p.products_id " . $listing_sql_array['where'] . " group by c.collections_id order by c.collections_sortorder, c.collections_name"));

        if ( $collection['collections_id'] > 0 && $collection['collections_products_count'] > 1 )
        {
          $products_query = tep_db_query("select p.products_id, p.order_quantity_minimal, p.order_quantity_max, p.order_quantity_step, p.stock_indication_id,  p.is_virtual, p.products_image, if(length(pd1.products_name), pd1.products_name, pd.products_name) as products_name, if(length(pd1.products_description_short), pd1.products_description_short, pd.products_description_short) as products_description_short, p.products_tax_class_id, p.products_price, p.products_quantity, p.products_model from " . $listing_sql_array['from'] . TABLE_COLLECTIONS_TO_PRODUCTS . " c2p, " . TABLE_PRODUCTS . " p left join " . TABLE_PRODUCTS_DESCRIPTION . " pd on pd.products_id = p.products_id and pd.language_id = '" . (int) $languages_id . "' and pd.platform_id = '".intval(\common\classes\platform::defaultId())."' left join " . TABLE_PRODUCTS_DESCRIPTION . " pd1 on pd1.products_id = p.products_id and pd1.language_id = '" . (int) $languages_id . "' and pd1.platform_id = '" . intval(\Yii::$app->get('platform')->config()->getPlatformToDescription()) . "' " . $listing_sql_array['left_join'] . " where c2p.products_id not in ('" . implode("','", array_map('intval', $params['collections'])) . "') and c2p.products_id <> '" . (int) $params['products_id'] . "' and c2p.collections_id = '" . (int)$collection['collections_id'] . "' and c2p.products_id = p.products_id " . $listing_sql_array['where'] . " order by c2p.sort_order, products_name");
          $all_products = \frontend\design\Info::getProducts($products_query);

          $collection_base_price = $collection_full_price = 0;
          $collection_sets_query = tep_db_query("select p.products_id, p.order_quantity_minimal, p.order_quantity_max, p.order_quantity_step, p.stock_indication_id,  p.is_virtual, p.products_image, if(length(pd1.products_name), pd1.products_name, pd.products_name) as products_name, if(length(pd1.products_description_short), pd1.products_description_short, pd.products_description_short) as products_description_short, p.products_tax_class_id, p.products_price, p.products_quantity, p.products_model from " . $listing_sql_array['from'] . TABLE_COLLECTIONS_TO_PRODUCTS . " c2p, " . TABLE_PRODUCTS . " p left join " . TABLE_PRODUCTS_DESCRIPTION . " pd on pd.products_id = p.products_id and pd.language_id = '" . (int) $languages_id . "' and pd.platform_id = '".intval(\common\classes\platform::defaultId())."' left join " . TABLE_PRODUCTS_DESCRIPTION . " pd1 on pd1.products_id = p.products_id and pd1.language_id = '" . (int) $languages_id . "' and pd1.platform_id = '" . intval(\Yii::$app->get('platform')->config()->getPlatformToDescription()) . "' " . $listing_sql_array['left_join'] . " where c2p.products_id in ('" . implode("','", array_map('intval', $params['collections'])) . "') and c2p.collections_id = '" . (int)$collection['collections_id'] . "' and c2p.products_id = p.products_id " . $listing_sql_array['where'] . " order by (c2p.products_id = '" . (int) $params['products_id'] . "') desc, c2p.sort_order, products_name");
          $collection_set_products = \frontend\design\Info::getProducts($collection_sets_query);

          $all_filled_array = array();
          $stock_indicators_ids = array();
          $stock_indicators_array = array();
          $stock_indicator_public = array();
          $collection_products = self::obtainCollectionProducts($params, $collection_set_products, $all_filled_array, $stock_indicators_ids, $stock_indicators_array, $collection_base_price, $collection_full_price, $stock_indicator_public);
          

          if (count($stock_indicators_ids) > 0) {
            $stock_indicators_sorted = \common\classes\StockIndication::sortStockIndicators($stock_indicators_ids);
            $collection_stock_indicator = $stock_indicators_array[$stock_indicators_sorted[count($stock_indicators_sorted)-1]];
          } else {
            $collection_stock_indicator = $stock_indicator_public;
          }

          $curr_product = $collection_products[0];
          unset($collection_products[0]);

          switch ($collection['collections_type']) {
            case 1:
              $collection_discount_price = self::get_collections_price($collection['collections_id'], $params['collections']);
              if ($collection_discount_price > 0 && $collection_discount_price < $collection_base_price) {
                $collection_discount_percent = (1 - $collection_discount_price / $collection_base_price) * 100;
              } else {
                $collection_discount_percent = 0;
              }
              break;
            case 0: default:
              $collection_discount_percent = self::get_collections_discount($collection['collections_id'], $params['collections']);
          }

          $return_data = [
              'product_valid' => (count($all_filled_array) > 1 && min($all_filled_array) ? '1' : '0'),
              'curr_product' => $curr_product,
              'collection_products' => $collection_products,
              'all_products' => $all_products,
              'stock_indicator' => $collection_stock_indicator,
              'collection_full_price' => $currencies->format($collection_full_price, false),
              'collection_discount_price' => $currencies->format($collection_full_price - ($collection_full_price * $collection_discount_percent / 100), false),
              'collection_discount_percent' => round($collection_discount_percent, 1),
              'collection_save_price' => $currencies->format($collection_full_price * $collection_discount_percent / 100, false),
          ];
          return $return_data;
        }
    }
    
    public static function obtainCollectionProducts($params, $collection_set_products, &$all_filled_array, &$stock_indicators_ids, &$stock_indicators_array, &$collection_base_price, &$collection_full_price, &$stock_indicator_public){
        $collection_products = [];
        $currencies = \Yii::$container->get('currencies');
        $languages_id = \Yii::$app->settings->get('languages_id');
        foreach ($collection_set_products as $collection_sets)
          {
            $products_id = $collection_sets['products_id'];
            if (is_array($params['collections_attr'][$products_id])) {
              $attributes = $params['collections_attr'][$products_id];
            } else {
              $attributes = array();
            }

            $uprid = \common\helpers\Inventory::normalize_id(\common\helpers\Inventory::get_uprid($products_id, $attributes));
            unset($params['products_id']);
            $priceInstance = \common\models\Product\Price::getInstance($uprid);
            $product_price = $priceInstance->getInventoryPrice(['qty' => $params['collections_qty'][$products_id]]);

            $special_price = $priceInstance->getInventorySpecialPrice(['qty' => $params['collections_qty'][$products_id]]);
            $actual_product_price = $product_price;

            if ($special_price !== false) {
              $collection_base_price += \common\helpers\Product::get_products_special_price($products_id, 1);
            } else {
              $collection_base_price += \common\helpers\Product::get_products_price($products_id, 1);
            }

            $products_options_name_query = tep_db_query("select distinct p.products_id, p.products_tax_class_id, popt.products_options_id, popt.products_options_name from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_ATTRIBUTES . " patrib where p.products_id = '" . (int)$products_id . "' and patrib.products_id = p.products_id and patrib.options_id = popt.products_options_id and popt.language_id = '" . (int)$languages_id . "' order by popt.products_options_sort_order, popt.products_options_name");
            while ($products_options_name = tep_db_fetch_array($products_options_name_query)) {
                if (!isset($attributes[$products_options_name['products_options_id']])) {
                    $check = tep_db_fetch_array(tep_db_query("select max(pov.products_options_values_id) as values_id, count(pov.products_options_values_id) as values_count from " . TABLE_PRODUCTS_ATTRIBUTES . " pa, " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov where pa.products_id = '" . (int)$products_id . "' and pa.options_id = '" . (int)$products_options_name['products_options_id'] . "' and pa.options_values_id = pov.products_options_values_id and pov.language_id = '" . (int)$languages_id . "'"));
                    if ($check['values_count'] == 1) { // if only one option value - it should be selected
                        $attributes[$products_options_name['products_options_id']] = $check['values_id'];
                    } else {
                        $attributes[$products_options_name['products_options_id']] = 0;
                    }
                }
            }

            $all_filled = true;
/*
            if (isset($attributes) && is_array($attributes))
            foreach($attributes as $value) {
               $all_filled = $all_filled && (bool)$value;
            }
*/

            $attributes_array = array();
            tep_db_data_seek($products_options_name_query, 0);
            while ($products_options_name = tep_db_fetch_array($products_options_name_query)) {
                $products_options_array = array();
                $products_options_query = tep_db_query(
                  "select pa.products_attributes_id, pov.products_options_values_id, pov.products_options_values_name, pa.options_values_price, pa.price_prefix ".
                  "from " . TABLE_PRODUCTS_ATTRIBUTES . " pa, " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov ".
                  "where pa.products_id = '" . (int)$products_id . "' and pa.options_id = '" . (int)$products_options_name['products_options_id'] . "' and pa.options_values_id = pov.products_options_values_id and pov.language_id = '" . (int)$languages_id . "' ".
                  "order by pa.products_options_sort_order, pov.products_options_values_sort_order, pov.products_options_values_name");
                while ($products_options = tep_db_fetch_array($products_options_query)) {
                    $comb_arr = $attributes;
                    $comb_arr[$products_options_name['products_options_id']] = $products_options['products_options_values_id'];
                    foreach ($comb_arr as $opt_id => $val_id) {
                        if ( !($val_id > 0) ) {
                            $comb_arr[$opt_id] = '0000000';
                        }
                    }
                    reset($comb_arr);
                    $mask = str_replace('0000000', '%', \common\helpers\Inventory::normalizeInventoryId(\common\helpers\Inventory::get_uprid($products_id, $comb_arr), $vids, $virtual_vids));
                    $check_inventory = tep_db_fetch_array(tep_db_query(
                      "select inventory_id, max(products_quantity) as products_quantity, stock_indication_id, stock_delivery_terms_id, ".
                      " min(if(price_prefix = '-', -inventory_price, inventory_price)) as inventory_price, min(inventory_full_price) as inventory_full_price ".
                      "from " . TABLE_INVENTORY . " i ".
                      "where products_id like '" . tep_db_input($mask) . "' and non_existent = '0' " . \common\helpers\Inventory::get_sql_inventory_restrictions(array('i', 'ip'))  . " ".
                      "order by products_quantity desc ".
                      "limit 1"
                    ));
                    if (!$check_inventory['inventory_id']) continue;

                    $priceInstance = \common\models\Product\Price::getInstance($mask);
                    $products_price = $priceInstance->getInventoryPrice($params);
                    if ($priceInstance->calculate_full_price && $products_price == -1) {
                        continue; // Disabled for specific group
                    } elseif ($products_price == -1) {
                        continue; // Disabled for specific group
                    }

                    if ($virtual_vids) {
                        $virtual_attribute_price = \common\helpers\Attributes::get_virtual_attribute_price($products_id, $virtual_vids, $params['qty'], $products_price);
                        if ($virtual_attribute_price === false) {
                            continue; // Disabled for specific group
                        } else {
                            $products_price += $virtual_attribute_price;
                        }
                    }

                    $products_options_array[] = array('id' => $products_options['products_options_values_id'], 'text' => $products_options['products_options_values_name'], 'price_diff' => 0);
                    $price_diff = $products_price - $actual_product_price;

                    if ($price_diff != '0') {
                        $products_options_array[sizeof($products_options_array)-1]['text'] .= ' (' . ($price_diff < 0 ? '-' : '+') . $currencies->display_price(abs($price_diff), \common\helpers\Tax::get_tax_rate($products_options_name['products_tax_class_id']), 1, false) .') ';
                    }
                    $products_options_array[sizeof($products_options_array)-1]['price_diff'] = $price_diff;

                    $stock_indicator = \common\classes\StockIndication::product_info(array(
                      'products_id' => $check_inventory['products_id'],
                      'products_quantity' => ($check_inventory['inventory_id'] ? $check_inventory['products_quantity'] : '0'),
                      'stock_indication_id' => (isset($check_inventory['stock_indication_id'])?$check_inventory['stock_indication_id']:null),
                      'stock_delivery_terms_id' => (isset($check_inventory['stock_delivery_terms_id'])?$check_inventory['stock_delivery_terms_id']:null),
                    ));

                    if ( !($check_inventory['products_quantity'] > 0) ) {
                      $products_options_array[sizeof($products_options_array)-1]['params'] = ' class="outstock" data-max-qty="' . (int)$stock_indicator['max_qty'] . '"';
                      $products_options_array[sizeof($products_options_array)-1]['text'] .= ' - ' . strip_tags($stock_indicator['stock_indicator_text_short']);
                    } else {
                      $products_options_array[sizeof($products_options_array)-1]['params'] = ' class="outstock" data-max-qty="'.(int)$stock_indicator['max_qty'].'"';
                    }
                }

                if ($attributes[$products_options_name['products_options_id']] > 0) {
                    $selected_attribute = $attributes[$products_options_name['products_options_id']];
                } else {
                    $selected_attribute = false;
                }

                if (count($products_options_array) > 0) {
                    $all_filled = $all_filled && (bool) $attributes[$products_options_name['products_options_id']];
                    $attributes_array[] = array(
                        'title' => htmlspecialchars($products_options_name['products_options_name']),
                        'name' => 'collections_attr[' . $products_id . '][' . $products_options_name['products_options_id'] . ']',
                        'options' => $products_options_array,
                        'selected' => $selected_attribute,
                    );
                }

            }

            $product_query = tep_db_query("select products_id, products_price, products_tax_class_id, stock_indication_id, stock_delivery_terms_id, products_quantity from " . TABLE_PRODUCTS . " where products_id = '" . (int)$products_id . "'");
            $_backup_products_quantity = 0;
            $_backup_stock_indication_id = $_backup_stock_delivery_terms_id = 0;
            if ($product = tep_db_fetch_array($product_query)) {
                $_backup_products_quantity = $product['products_quantity'];
                $_backup_stock_indication_id = $product['stock_indication_id'];
                $_backup_stock_delivery_terms_id = $product['stock_delivery_terms_id'];
            }
            $current_uprid = \common\helpers\Inventory::normalize_id(\common\helpers\Inventory::get_uprid($products_id, $attributes));
            $collection_sets['current_uprid'] = $current_uprid;

            $check_inventory = tep_db_fetch_array(tep_db_query(
              "select inventory_id, products_quantity, stock_indication_id, stock_delivery_terms_id ".
              "from " . TABLE_INVENTORY . " ".
              "where products_id like '" . tep_db_input(\common\helpers\Inventory::normalizeInventoryId($current_uprid)) . "' ".
              "limit 1"
            ));

            $stock_indicator = \common\classes\StockIndication::product_info(array(
              'products_id' => $current_uprid,
              'products_quantity' => ($check_inventory['inventory_id'] ? $check_inventory['products_quantity'] : $_backup_products_quantity),
              'stock_indication_id' => (isset($check_inventory['stock_indication_id'])?$check_inventory['stock_indication_id']:$_backup_stock_indication_id),
              'stock_delivery_terms_id' => (isset($check_inventory['stock_delivery_terms_id'])?$check_inventory['stock_delivery_terms_id']:$_backup_stock_delivery_terms_id),
            ));
            $stock_indicator_public = $stock_indicator['flags'];
            $stock_indicator_public['quantity_max'] = \common\helpers\Product::filter_product_order_quantity($current_uprid, $stock_indicator['max_qty'], true);
            $stock_indicator_public['stock_code'] = $stock_indicator['stock_code'];
            $stock_indicator_public['text_stock_code'] = $stock_indicator['text_stock_code'];
            $stock_indicator_public['stock_indicator_text'] = $stock_indicator['stock_indicator_text'];
            if ($stock_indicator_public['request_for_quote']) {
              $special_price = false;
            }
            $collection_sets['stock_indicator'] = $stock_indicator_public;

            if ($stock_indicator['id'] > 0) {
              $stock_indicators_ids[] = $stock_indicator['id'];
              $stock_indicators_array[$stock_indicator['id']] = $stock_indicator_public;
            }

            $collection_sets['all_filled'] = $all_filled;
            $collection_sets['product_qty'] = ($check_inventory['inventory_id'] ? $check_inventory['products_quantity'] : ($product['products_quantity']?$product['products_quantity']:'0'));

            $all_filled_array[] = $collection_sets['all_filled'];

            $collection_sets['collections_qty'] = ($params['collections_qty'][$products_id] > 0 ? $params['collections_qty'][$products_id] : 1);

            if ($special_price !== false) {
              $collection_sets['price_old'] = $currencies->display_price($product_price, \common\helpers\Tax::get_tax_rate($collection_sets['products_tax_class_id']));
              $collection_sets['price_special'] = $currencies->display_price($special_price, \common\helpers\Tax::get_tax_rate($collection_sets['products_tax_class_id']));
              $collection_full_price += $currencies->display_price_clear($special_price, \common\helpers\Tax::get_tax_rate($collection_sets['products_tax_class_id']), $collection_sets['collections_qty']);
            } else {
              $collection_sets['price'] = $currencies->display_price($product_price, \common\helpers\Tax::get_tax_rate($collection_sets['products_tax_class_id'])); 
              $collection_full_price += $currencies->display_price_clear($product_price, \common\helpers\Tax::get_tax_rate($collection_sets['products_tax_class_id']), $collection_sets['collections_qty']);
            }
            $collection_sets['special_price'] = $special_price;
            $collection_sets['product_price'] = $product_price;

            $collection_sets['attributes_array'] = $attributes_array;

            $collection_products[] = $collection_sets;
          }
        return $collection_products;
    }

    public static function get_collections_discount($collections_id, $collection_products_ids) {
        global $languages_id;
        if (is_array($collection_products_ids) && count($collection_products_ids) > 1) {
            $listing_sql_array = \frontend\design\ListingSql::get_listing_sql_array('catalog/all-products');
            $collections = tep_db_fetch_array(tep_db_query("select c.collections_id, c.collections_name, group_concat(distinct c2p.products_id order by c2p.products_id) as collection_products_ids from " . $listing_sql_array['from'] . " " . TABLE_COLLECTIONS . " c, " . TABLE_COLLECTIONS_TO_PRODUCTS . " c2p, " . TABLE_PRODUCTS . " p " . $listing_sql_array['left_join'] . " where c.collections_id = '" . (int) $collections_id . "' and c.collections_type = '0' and c2p.products_id in ('" . implode("','", array_map('intval', $collection_products_ids)) . "') and c.collections_id = c2p.collections_id and c.language_id = '" . (int) $languages_id . "' " . $listing_sql_array['where'] . " group by c.collections_id"));
            if (count($collection_products_ids) == count(explode(',', $collections['collection_products_ids']))) {
                $check = tep_db_fetch_array(tep_db_query("select collections_discount from " . TABLE_COLLECTIONS_DISCOUNT_PRICES . " where collections_id = '" . (int) $collections_id . "' and collections_type = '0' and collections_products_count = '" . (int) count(explode(',', $collections['collection_products_ids'])) . "'"));
                return $check['collections_discount'];
            }
        }
    }

    public static function get_collections_price($collections_id, $collection_products_ids) {
        global $languages_id;
        if (is_array($collection_products_ids) && count($collection_products_ids) > 1) {
            $listing_sql_array = \frontend\design\ListingSql::get_listing_sql_array('catalog/all-products');
            $collections = tep_db_fetch_array(tep_db_query("select c.collections_id, c.collections_name, group_concat(distinct c2p.products_id order by c2p.products_id) as collection_products_ids from " . $listing_sql_array['from'] . " " . TABLE_COLLECTIONS . " c, " . TABLE_COLLECTIONS_TO_PRODUCTS . " c2p, " . TABLE_PRODUCTS . " p " . $listing_sql_array['left_join'] . " where c.collections_id = '" . (int) $collections_id . "' and c.collections_type = '1' and c2p.products_id in ('" . implode("','", array_map('intval', $collection_products_ids)) . "') and c.collections_id = c2p.collections_id and c.language_id = '" . (int) $languages_id . "' " . $listing_sql_array['where'] . " group by c.collections_id"));
            if (count($collection_products_ids) == count(explode(',', $collections['collection_products_ids']))) {
                $check = tep_db_fetch_array(tep_db_query("select collections_price from " . TABLE_COLLECTIONS_DISCOUNT_PRICES . " where collections_id = '" . (int) $collections_id . "' and collections_type = '1' and collections_products_set = '" . tep_db_input($collections['collection_products_ids']) . "'"));
                return $check['collections_price'];
            }
        }
    }

    public static function get_collections_from_shopping_cart($cart_contents) {
        global $languages_id;
        $collections_array = array();
        if (is_array($cart_contents)) foreach ($cart_contents as $products_id => $val) {
            $prid = \common\helpers\Inventory::get_prid($products_id);
            if ($cart_contents[$products_id]['parent'] == '') {
                $check = tep_db_fetch_array(tep_db_query("select collections_id from " . TABLE_COLLECTIONS_TO_PRODUCTS . " where products_id = '" . (int) $prid . "'"));
                if (is_array($collections_array[$check['collections_id']])) {
                    $collections_array[$check['collections_id']]['products'][] = $prid;
                } else {
                    $collections_array[$check['collections_id']] = array('products' => array($prid));
                }
            }
        }
        foreach ($collections_array as $collections_id => $collection) {
            if (is_array($collection['products']) && count($collection['products']) > 1) {
                $listing_sql_array = \frontend\design\ListingSql::get_listing_sql_array('catalog/all-products');
                $collections_query = tep_db_query("select c.collections_id, c.collections_name, c.collections_image, c.collections_type, c.collections_sortorder, group_concat(distinct c2p.products_id order by c2p.products_id) as collection_products_ids from " . $listing_sql_array['from'] . " " . TABLE_COLLECTIONS . " c, " . TABLE_COLLECTIONS_TO_PRODUCTS . " c2p, " . TABLE_PRODUCTS . " p " . $listing_sql_array['left_join'] . " where c.collections_id = '" . (int) $collections_id . "' and c2p.products_id in ('" . implode("','", array_map('intval', $collection['products'])) . "') and c.collections_id = c2p.collections_id and c.language_id = '" . (int) $languages_id . "' " . $listing_sql_array['where'] . " group by c.collections_id order by c.collections_sortorder, c.collections_name");
                while ($collections = tep_db_fetch_array($collections_query)) {
                    if (count($collection['products']) == count(explode(',', $collections['collection_products_ids']))) {
                        $collections_array[$collections_id]['id'] = $collections['collections_id'];
                        $collections_array[$collections_id]['name'] = $collections['collections_name'];
                        $collections_array[$collections_id]['image'] = $collections['collections_image'];
                        $collections_array[$collections_id]['sortorder'] = $collections['collections_sortorder'];
                        switch ($collections['collections_type']) {
                            case 1:
                                $collection_base_price = 0;
                                foreach ($collection['products'] as $products_id) {
                                    if (($special_price = \common\helpers\Product::get_products_special_price($products_id)) !== false) {
                                        $collection_base_price += $special_price;
                                    } else {
                                        $collection_base_price += \common\helpers\Product::get_products_price($products_id);
                                    }
                                }
                                $check = tep_db_fetch_array(tep_db_query("select collections_price from " . TABLE_COLLECTIONS_DISCOUNT_PRICES . " where collections_id = '" . (int) $collections_id . "' and collections_type = '1' and collections_products_set = '" . tep_db_input($collections['collection_products_ids']) . "'"));
                                $collection_discount_price = $check['collections_price'];
                                if ($collection_discount_price > 0 && $collection_discount_price < $collection_base_price) {
                                    $collection_discount_percent = (1 - $collection_discount_price / $collection_base_price) * 100;
                                } else {
                                    $collection_discount_percent = 0;
                                }
                                break;
                            case 0: default:
                                $check = tep_db_fetch_array(tep_db_query("select collections_discount from " . TABLE_COLLECTIONS_DISCOUNT_PRICES . " where collections_id = '" . (int) $collections_id . "' and collections_type = '0' and collections_products_count = '" . (int) count(explode(',', $collections['collection_products_ids'])) . "'"));
                                $collection_discount_percent = $check['collections_discount'];
                        }
                        $collections_array[$collections_id]['discount'] = $collection_discount_percent;
                    } else {
                        unset($collections_array[$collections_id]);
                    }
                }
            } else {
                unset($collections_array[$collections_id]);
            }
        }
        if (count($collections_array) > 0) {
            uasort($collections_array, array('self', 'collections_cmp'));
        }
        return $collections_array;
    }

    static function collections_cmp($a, $b) {
        if ($a['discount'] == $b['discount']) {
            return (($a['sortorder'] < $b['sortorder'])) ? -1 : 1;
        }
        return (($a['discount'] > $b['discount'])) ? -1 : 1;
    }
}
