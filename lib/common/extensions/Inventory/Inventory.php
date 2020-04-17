<?php

/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\extensions\Inventory;

use Yii;
use yii\base\Widget;
use common\helpers\Inventory as InventoryHelper;
use common\models\SuppliersProducts;

// Inventory Attributes
class Inventory extends Widget {

    public static function allowed() {
      if (PRODUCTS_INVENTORY == 'True') {
        return true;
      } else {
        return false;
      }
    }

    public static function getDetails($products_id, &$attributes, $params = array()) {
        global $languages_id, $cart;
        $currencies = \Yii::$container->get('currencies');
        if (!isset($params['qty']))
            $params['qty'] = 1;
        if(is_array($params['qty_'])){
            $params['qty'] = ['unit' => (int) $params['qty_'][0]];
            $packItem = [
                'unit' => (int) $params['qty_'][0],
                'pack_unit' => (int) $params['qty_'][1],
                'packaging' => (int) $params['qty_'][2],
            ];
        }


        $bundle_attributes = array();
        if (\common\helpers\Acl::checkExtension('ProductBundles', 'allowed')) {
            if (defined('PRODUCTS_BUNDLE_SETS') && PRODUCTS_BUNDLE_SETS == 'True' && is_array($attributes) && count($attributes) > 0) {
                $_attribute_options = array_keys($attributes);
                foreach ($_attribute_options as $_attribute_option) {
                    if (strpos($_attribute_option, '-') === false)
                        continue;
                    $bundle_attributes[$_attribute_option] = $attributes[$_attribute_option];
                    unset($attributes[$_attribute_option]);
                }
            }
        }

        /*$bundle_attributes_valid = true;
        $bundle_attributes_price = 0;
        if (count($bundle_attributes) > 0) {
            foreach ($bundle_attributes as $opt_pid_id => $val_id) {
                list( $opt_id, $bundle_product_id ) = explode('-', $opt_pid_id);
                $attribute_price_query = tep_db_query("select products_attributes_id, options_values_price, price_prefix from " . TABLE_PRODUCTS_ATTRIBUTES . " where products_id = '" . (int) $bundle_product_id . "' and options_id = '" . (int) $opt_id . "' and options_values_id = '" . (int) $val_id . "'");
                if (tep_db_num_rows($attribute_price_query) == 0) {
                    $bundle_attributes_valid = false;
                    continue;
                }
                $attribute_price = tep_db_fetch_array($attribute_price_query);
                $attribute_price['options_values_price'] = self::get_options_values_price($attribute_price['products_attributes_id'], 1);
                if ($attribute_price['price_prefix'] == '-') {
                    $bundle_attributes_price -= $attribute_price['options_values_price'];
                } else {
                    $bundle_attributes_price += $attribute_price['options_values_price'];
                }
            }
        }*/
        $origin_products_id = InventoryHelper::get_prid($products_id);
        $productItem = \common\helpers\Product::itemInstance($products_id);
        $products_id = $productItem[\common\helpers\Product::priceProductIdColumn()];
        //$products_id = InventoryHelper::get_prid($products_id);

        $origin_uprid = InventoryHelper::normalize_id(InventoryHelper::get_uprid($origin_products_id, $attributes));
        $uprid = $origin_uprid;
        $real_uprid = InventoryHelper::normalize_id(InventoryHelper::get_uprid($products_id, $attributes));
        unset($params['products_id']);
        $priceInstance = \common\models\Product\Price::getInstance($origin_uprid);
        $products_price = $priceInstance->getInventoryPrice($params);
        $special_price = $priceInstance->getInventorySpecialPrice($params);
        /*if($special_price !== false){
            $products_price = $special_price;
        }*/

        /*
        $product_query = tep_db_query("select products_id, products_price, products_tax_class_id, products_quantity, products_price_full from " . TABLE_PRODUCTS . " where products_id = '" . (int) $products_id . "'");
        if ($product = tep_db_fetch_array($product_query)) {
            $product_price = \common\helpers\Product::get_products_price($product['products_id'], $params['qty'], $product['products_price']);
            $special_price = \common\helpers\Product::get_products_special_price($product['products_id'], $params['qty']);
            $product_price_old = $product_price;

            $comb_arr = $attributes;
            foreach ($comb_arr as $opt_id => $val_id) {
                if (!($val_id > 0)) {
                    $comb_arr[$opt_id] = '0000000';
                }
            }
            reset($comb_arr);
            $mask = str_replace('0000000', '%', InventoryHelper::normalize_id(InventoryHelper::get_uprid($products_id, $comb_arr)));
            $check_inventory = tep_db_fetch_array(tep_db_query("select inventory_id, min(if(price_prefix = '-', -inventory_price, inventory_price)) as inventory_price, min(inventory_full_price) as inventory_full_price from " . TABLE_INVENTORY . " i where products_id like '" . tep_db_input($mask) . "' and non_existent = '0' " . InventoryHelper::get_sql_inventory_restrictions(array('i', 'ip')) . " limit 1"));
            if ($check_inventory['inventory_id']) {

                $check_inventory['inventory_price'] = InventoryHelper::get_inventory_price_by_uprid($mask, $params['qty'], $check_inventory['inventory_price']);

                $check_inventory['inventory_full_price'] = InventoryHelper::get_inventory_full_price_by_uprid($mask, $params['qty'], $check_inventory['inventory_full_price']);
                if ($product['products_price_full'] && $check_inventory['inventory_full_price'] != -1) {
                    $product_price = $check_inventory['inventory_full_price'];
                    if ($special_price !== false) {
                        // if special - add difference
                        $special_price += $product_price - $product_price_old;
                    }
                } elseif ($check_inventory['inventory_price'] != -1) {
                    $product_price += $check_inventory['inventory_price'];
                    if ($special_price !== false) {
                        $special_price += $check_inventory['inventory_price'];
                    }
                }
            }
        }*/
        $actual_product_price = $products_price;

        $products_options_name_query = tep_db_query(
            "select distinct p.products_id, p.products_tax_class_id, ".
            " popt.products_options_id, popt.products_options_name, ".
            " popt.type, popt.products_options_image, popt.products_options_color ".
            "from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_ATTRIBUTES . " patrib ".
            "where p.products_id = '" . (int) $origin_products_id . "' and patrib.products_id = p.products_id ".
            " and patrib.options_id = popt.products_options_id and popt.language_id = '" . (int) $languages_id . "' ".
            "order by popt.products_options_sort_order, popt.products_options_name"
        );
        while ($products_options_name = tep_db_fetch_array($products_options_name_query)) {
            if (!isset($attributes[$products_options_name['products_options_id']])) {
                $check = tep_db_fetch_array(tep_db_query(
                    "select max(pov.products_options_values_id) as values_id, count(pov.products_options_values_id) as values_count, ".
                    " max(IF(pa.default_option_value>0,pa.options_values_id, 0)) AS default_option_value ".
                    "from " . TABLE_PRODUCTS_ATTRIBUTES . " pa, " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov ".
                    "where pa.products_id = '" . (int) $origin_products_id . "' and pa.options_id = '" . (int) $products_options_name['products_options_id'] . "' ".
                    " and pa.options_values_id = pov.products_options_values_id and pov.language_id = '" . (int) $languages_id . "'"
                ));
                if ($check['values_count'] == 1) { // if only one option value - it should be selected
                    $attributes[$products_options_name['products_options_id']] = $check['values_id'];
                } else {
                    $attributes[$products_options_name['products_options_id']] = $check['default_option_value'];
                }
            }
        }

        $all_filled = true;
/*
        if (isset($attributes) && is_array($attributes))
            foreach ($attributes as $value) {
                $all_filled = $all_filled && (bool) $value;
            }
*/

        $attributes_array = array();
        $attributes_stock = [];
        tep_db_data_seek($products_options_name_query, 0);
        while ($products_options_name = tep_db_fetch_array($products_options_name_query)) {
            $products_options_array = array();
            $products_options_query = tep_db_query(
                    "select pa.products_attributes_id, pov.products_options_values_id, pov.products_options_values_name, pa.options_values_price, pa.price_prefix, pov.products_options_values_color, pov.products_options_values_image " .
                    "from " . TABLE_PRODUCTS_ATTRIBUTES . " pa, " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov " .
                    "where pa.products_id = '" . (int) $origin_products_id . "' and pa.options_id = '" . (int) $products_options_name['products_options_id'] . "' and pa.options_values_id = pov.products_options_values_id and pov.language_id = '" . (int) $languages_id . "' " .
                    "order by pa.products_options_sort_order, pov.products_options_values_sort_order, pov.products_options_values_name");
            while ($products_options = tep_db_fetch_array($products_options_query)) {
                $comb_arr = $attributes;
                $comb_arr[$products_options_name['products_options_id']] = $products_options['products_options_values_id'];
                foreach ($comb_arr as $opt_id => $val_id) {
                    if (!($val_id > 0)) {
                        $comb_arr[$opt_id] = '0000000';
                    }
                }
                reset($comb_arr);
                $mask = str_replace('0000000', '%', InventoryHelper::normalizeInventoryId(InventoryHelper::get_uprid($products_id, $comb_arr), $vids, $virtual_vids));
                $price_mask = str_replace('0000000', '%', InventoryHelper::normalizeInventoryPriceId(InventoryHelper::get_uprid($products_id, $comb_arr), $_p_vids, $_p_virtual_vids));
                $check_inventory = tep_db_fetch_array(tep_db_query(
                                "select inventory_id, max(products_quantity) as products_quantity, stock_indication_id, stock_delivery_terms_id, stock_control, products_id " .
//                                " min(if(price_prefix = '-', -inventory_price, inventory_price)) as inventory_price, min(inventory_full_price) as inventory_full_price " .
                                "from " . TABLE_INVENTORY . " i " .
                                "where products_id like '" . tep_db_input($mask) . "' and non_existent = '0' " . InventoryHelper::get_sql_inventory_restrictions(array('i', 'ip')) . " " .
                                "order by products_quantity desc " .
                                "limit 1"
                ));
                if (!$check_inventory['inventory_id'] && strstr('{', $mask))
                    continue;

                if ($check_inventory['stock_control'] == 1) {
                    $platformInventoryControl = \common\models\PlatformInventoryControl::findOne(['products_id' => $check_inventory['products_id'], 'platform_id' => \common\classes\platform::currentId()]);
                    if (is_object($platformInventoryControl)) {
                        $check_inventory['products_quantity'] = $platformInventoryControl->current_quantity;
                    }
                }
                if ($check_inventory['stock_control'] == 2) {
                    $warehouseInventoryControl = \common\models\WarehouseInventoryControl::findOne(['products_id' => $check_inventory['products_id'], 'platform_id' => \common\classes\platform::currentId()]);
                    if (is_object($warehouseInventoryControl)) {
                        $warehouse_id = $warehouseInventoryControl->warehouse_id;
                        $suppliers_id = 0;
                        $warehouses_stock_query = tep_db_query("select w.warehouse_id, w.warehouse_name, sum(wp.products_quantity) as products_quantity, sum(wp.allocated_stock_quantity) as allocated_stock_quantity, sum(wp.temporary_stock_quantity) as temporary_stock_quantity, sum(wp.warehouse_stock_quantity) as warehouse_stock_quantity, sum(wp.ordered_stock_quantity) as ordered_stock_quantity from  " . TABLE_WAREHOUSES . " w left join " . TABLE_WAREHOUSES_PRODUCTS . " wp on wp.warehouse_id = w.warehouse_id " . ($suppliers_id > 0 ? " and wp.suppliers_id = '" . (int) $suppliers_id . "'" : '') . " and products_id = '" . (int) $products_id . "' and wp.prid = '" . (int) $products_id . "' where w.status = '1' and w.warehouse_id = '" . $warehouse_id . "'");
                        if (tep_db_num_rows($warehouses_stock_query) > 0) {
                            $warehouses_stock = tep_db_fetch_array($warehouses_stock_query);
                            $check_inventory['products_quantity'] = $warehouses_stock['products_quantity'];
                        }
                    }

                }

                $priceInstance = \common\models\Product\Price::getInstance($price_mask);
                $products_price = $priceInstance->getInventoryPrice($params);
                //var_dump($products_price, $actual_product_price);
                /*$check_inventory['inventory_price'] = InventoryHelper::get_inventory_price_by_uprid($mask, $params['qty'], $check_inventory['inventory_price']);
                $check_inventory['inventory_full_price'] = InventoryHelper::get_inventory_full_price_by_uprid($mask, $params['qty'], $check_inventory['inventory_full_price']);
                 */
                if ($priceInstance->calculate_full_price && $products_price == -1) {
                    continue; // Disabled for specific group
                } elseif ($products_price == -1) {
                    continue; // Disabled for specific group
                }

                if ($virtual_vids) {
                    $virtual_attribute_price = \common\helpers\Attributes::get_virtual_attribute_price($origin_products_id, $virtual_vids, $params['qty'], $products_price);
                    if ($virtual_attribute_price === false) {
                        continue; // Disabled for specific group
                    } else {
                        $products_price += $virtual_attribute_price;
                    }
                }

                $products_options_array[] = [
                    'id' => $products_options['products_options_values_id'],
                    'text' => $products_options['products_options_values_name'],
                    'price_diff' => 0,
                    'color' => $products_options['products_options_values_color'],
                    'image' => $products_options['products_options_values_image'],
                ];
                $price_diff = $products_price - $actual_product_price;
                /*if ($priceInstance->calculate_full_price) {
                    $price_diff = $products_price - $actual_product_price;
                } else {
                    $price_diff = $product_price_old + $products_price - $actual_product_price;
                }*/
                if ($price_diff != '0') {
                    $products_options_array[sizeof($products_options_array) - 1]['text'] .= ' (' . ($price_diff < 0 ? '-' : '+') . $currencies->display_price(abs($price_diff), \common\helpers\Tax::get_tax_rate($products_options_name['products_tax_class_id']), 1, false) . ') ';
                }
                $products_options_array[sizeof($products_options_array) - 1]['price_diff'] = $price_diff;

                $stock_indicator = \common\classes\StockIndication::product_info(array(
                            'products_id' => $check_inventory['products_id'],
                            'products_quantity' => ($check_inventory['inventory_id'] ? $check_inventory['products_quantity'] : '0'),
                            'stock_indication_id' => (isset($check_inventory['stock_indication_id']) ? $check_inventory['stock_indication_id'] : null),
                            'stock_delivery_terms_id' => (isset($check_inventory['stock_delivery_terms_id']) ? $check_inventory['stock_delivery_terms_id'] : null),
                ));
                $attributes_stock[$products_options['products_options_values_id']] = $stock_indicator;

                if (!($check_inventory['products_quantity'] > 0) && strstr('{', $mask)) {
                    $products_options_array[sizeof($products_options_array) - 1]['params'] = ' class="outstock" data-max-qty="' . (int) $stock_indicator['max_qty'] . '"';
                    $products_options_array[sizeof($products_options_array) - 1]['text'] .= ' - ' . strip_tags($stock_indicator['stock_indicator_text_short']);
                } else {
                    $products_options_array[sizeof($products_options_array) - 1]['params'] = ' class="outstock" data-max-qty="' . (int) $stock_indicator['max_qty'] . '"';
                }
            }

            if ($attributes[$products_options_name['products_options_id']] > 0) {
                $selected_attribute = $attributes[$products_options_name['products_options_id']];
            } elseif ($cart->contents[$params['products_id']]['attributes'][$products_options_name['products_options_id']] > 0) {
                $selected_attribute = $cart->contents[$params['products_id']]['attributes'][$products_options_name['products_options_id']];
            } else {
                if (is_array($products_options_array) && count($products_options_array)){
                    foreach ($products_options_array as $option){
                        if (isset($attributes_stock[$option['id']]) && isset($attributes_stock[$option['id']]['flags']) && $attributes_stock[$option['id']]['flags']['can_add_to_cart']){
                            $selected_attribute = $option['id'];
                            $attributes[$products_options_name['products_options_id']] = $selected_attribute;
                            break;
                        }
                    }
                } else
                $selected_attribute = false;
            }

            if (count($products_options_array) > 0) {
                $all_filled = $all_filled && (bool) ($attributes[$products_options_name['products_options_id']] || self::checkMultiAtts($products_id, $params, $products_options_name['products_options_id']) );
                $attributes_array[] = array(
                    'title' => htmlspecialchars($products_options_name['products_options_name']),
                    'id' => $products_options_name['products_options_id'],
                    'type' => $products_options_name['type'],
                    'name' => 'id[' . $products_options_name['products_options_id'] . ']', ///deprecated
                    'options' => $products_options_array,
                    'selected' => $selected_attribute,
                    'color' => $products_options_name['products_options_color'],
                    'image' => $products_options_name['products_options_image'],
                );
            }
        }

        $product_query = tep_db_query("select products_id, products_price, products_tax_class_id, stock_indication_id, stock_delivery_terms_id, products_quantity, stock_control from " . TABLE_PRODUCTS . " where products_id = '" . (int) InventoryHelper::normalizeInventoryId($products_id) . "'");
        $_backup_products_quantity = 0;
        $_backup_stock_indication_id = $_backup_stock_delivery_terms_id = 0;
        if ($product = tep_db_fetch_array($product_query)) {
            if ($product['stock_control'] == 1) {
                $platformStockControl = \common\models\PlatformStockControl::findOne(['products_id' => (int)$products_id, 'platform_id' => \common\classes\platform::currentId()]);
                if (is_object($platformStockControl)) {
                    $product['products_quantity'] = $platformStockControl->current_quantity;
                }
            }
            if ($product['stock_control'] == 2) {
                $warehouseStockControl = \common\models\WarehouseStockControl::findOne(['products_id' => $products_id, 'platform_id' => \common\classes\platform::currentId()]);
                if (is_object($warehouseStockControl)) {
                    $warehouse_id = $warehouseStockControl->warehouse_id;
                    $suppliers_id = 0;
                    $warehouses_stock_query = tep_db_query("select w.warehouse_id, w.warehouse_name, sum(wp.products_quantity) as products_quantity, sum(wp.allocated_stock_quantity) as allocated_stock_quantity, sum(wp.temporary_stock_quantity) as temporary_stock_quantity, sum(wp.warehouse_stock_quantity) as warehouse_stock_quantity, sum(wp.ordered_stock_quantity) as ordered_stock_quantity from  " . TABLE_WAREHOUSES . " w left join " . TABLE_WAREHOUSES_PRODUCTS . " wp on wp.warehouse_id = w.warehouse_id " . ($suppliers_id > 0 ? " and wp.suppliers_id = '" . (int) $suppliers_id . "'" : '') . " and products_id = '" . (int) $products_id . "' and wp.prid = '" . (int) $products_id . "' where w.status = '1' and w.warehouse_id = '" . $warehouse_id . "'");
                    if (tep_db_num_rows($warehouses_stock_query) > 0) {
                        $warehouses_stock = tep_db_fetch_array($warehouses_stock_query);
                        $product['products_quantity'] = $warehouses_stock['products_quantity'];
                    }
                }

            }
            $_backup_products_quantity = $product['products_quantity'];
            $_backup_stock_indication_id = $product['stock_indication_id'];
            $_backup_stock_delivery_terms_id = $product['stock_delivery_terms_id'];
        }
        //$current_uprid = InventoryHelper::normalize_id(InventoryHelper::get_uprid($products_id, $attributes));

        /*$check_inventory = tep_db_fetch_array(tep_db_query(
                        "select inventory_id, products_quantity, stock_indication_id, stock_delivery_terms_id " .
                        "from " . TABLE_INVENTORY . " " .
                        "where products_id like '" . tep_db_input($uprid) . "' " .
                        "limit 1"
        ));*/
        $inventoryInfoUprid = InventoryHelper::normalizeInventoryId($uprid);
        if ( \common\helpers\Product::isSubProduct($uprid) ){
            $check_inventory1 = tep_db_fetch_array(tep_db_query(
                "SELECT " .
                "  i.products_quantity, i.stock_indication_id, i.stock_delivery_terms_id, i.stock_control " .
                "FROM " . TABLE_PRODUCTS . " p " .
                " LEFT JOIN " . TABLE_INVENTORY . " i ON i.prid=p.products_id AND i.products_id='" . tep_db_input($inventoryInfoUprid) . "' " .
                "WHERE p.products_id='" . intval($inventoryInfoUprid) . "' "
            ));
            $mainInfoUprid = InventoryHelper::normalize_id($uprid);
            $check_inventory2 = tep_db_fetch_array(tep_db_query(
                "SELECT " .
                "  inventory_id, ".
                "  IF(LENGTH(i.products_model)>0,i.products_model, p.products_model) AS products_model, " .
                "  IF(LENGTH(i.products_upc)>0,i.products_upc, p.products_upc) AS products_upc, " .
                "  IF(LENGTH(i.products_ean)>0,i.products_ean, p.products_ean) AS products_ean, " .
                "  IF(LENGTH(i.products_asin)>0,i.products_asin, p.products_asin) AS products_asin, " .
                "  IF(LENGTH(i.products_isbn)>0,i.products_isbn, p.products_isbn) AS products_isbn " .
                "FROM " . TABLE_PRODUCTS . " p " .
                " LEFT JOIN " . TABLE_INVENTORY . " i ON i.prid=p.products_id AND i.products_id='" . tep_db_input($mainInfoUprid) . "' " .
                "WHERE p.products_id='" . intval($mainInfoUprid) . "' "
            ));
            if (!is_array($check_inventory1)) $check_inventory1 = [];
            if (!is_array($check_inventory2)) $check_inventory2 = [];
            $check_inventory = array_merge($check_inventory1, $check_inventory2);
        }else {
            $check_inventory = tep_db_fetch_array(tep_db_query(
                "SELECT " .
                "  inventory_id, i.products_quantity, i.stock_indication_id, i.stock_delivery_terms_id, i.stock_control, " .
                "  i.inventory_tax_class_id, " .
                "  IF(LENGTH(i.products_model)>0,i.products_model, p.products_model) AS products_model, " .
                "  IF(LENGTH(i.products_upc)>0,i.products_upc, p.products_upc) AS products_upc, " .
                "  IF(LENGTH(i.products_ean)>0,i.products_ean, p.products_ean) AS products_ean, " .
                "  IF(LENGTH(i.products_asin)>0,i.products_asin, p.products_asin) AS products_asin, " .
                "  IF(LENGTH(i.products_isbn)>0,i.products_isbn, p.products_isbn) AS products_isbn " .
                "FROM " . TABLE_PRODUCTS . " p " .
                " LEFT JOIN " . TABLE_INVENTORY . " i ON i.prid=p.products_id AND i.products_id='" . tep_db_input($inventoryInfoUprid) . "' " .
                "WHERE p.products_id='" . intval($inventoryInfoUprid) . "' "
            ));
        }

        if ( !is_null($check_inventory['inventory_tax_class_id']) ) {
            $product['products_tax_class_id'] = $check_inventory['inventory_tax_class_id'];
        }

        if ($check_inventory['stock_control'] == 1) {
            $platformInventoryControl = \common\models\PlatformInventoryControl::findOne(['products_id' => tep_db_input(InventoryHelper::normalizeInventoryId($uprid)), 'platform_id' => \common\classes\platform::currentId()]);
            if (is_object($platformInventoryControl)) {
                $check_inventory['products_quantity'] = $platformInventoryControl->current_quantity;
            }
        }
        if ($check_inventory['stock_control'] == 2) {
            $warehouseInventoryControl = \common\models\WarehouseInventoryControl::findOne(['products_id' => tep_db_input(InventoryHelper::normalizeInventoryId($uprid)), 'platform_id' => \common\classes\platform::currentId()]);
            if (is_object($warehouseInventoryControl)) {
                $warehouse_id = $warehouseInventoryControl->warehouse_id;
                $suppliers_id = 0;
                $warehouses_stock_query = tep_db_query("select w.warehouse_id, w.warehouse_name, sum(wp.products_quantity) as products_quantity, sum(wp.allocated_stock_quantity) as allocated_stock_quantity, sum(wp.temporary_stock_quantity) as temporary_stock_quantity, sum(wp.warehouse_stock_quantity) as warehouse_stock_quantity, sum(wp.ordered_stock_quantity) as ordered_stock_quantity from  " . TABLE_WAREHOUSES . " w left join " . TABLE_WAREHOUSES_PRODUCTS . " wp on wp.warehouse_id = w.warehouse_id " . ($suppliers_id > 0 ? " and wp.suppliers_id = '" . (int) $suppliers_id . "'" : '') . " and products_id = '" . (int) $products_id . "' and wp.prid = '" . (int) $products_id . "' where w.status = '1' and w.warehouse_id = '" . $warehouse_id . "'");
                if (tep_db_num_rows($warehouses_stock_query) > 0) {
                    $warehouses_stock = tep_db_fetch_array($warehouses_stock_query);
                    $check_inventory['products_quantity'] = $warehouses_stock['products_quantity'];
                }
            }
        }

        $dynamic_prop = array();
        if ($check_inventory) {
            $dynamic_prop = $check_inventory;
        }

        ksort($attributes);

        $stock_indicator = \common\classes\StockIndication::product_info(array(
                    'products_id' => InventoryHelper::normalizeInventoryId($uprid),
                    'products_quantity' => ($check_inventory['inventory_id'] ? $check_inventory['products_quantity'] : $_backup_products_quantity),
                    'stock_indication_id' => (isset($check_inventory['stock_indication_id']) ? $check_inventory['stock_indication_id'] : $_backup_stock_indication_id),
                    'stock_delivery_terms_id' => (isset($check_inventory['stock_delivery_terms_id']) ? $check_inventory['stock_delivery_terms_id'] : $_backup_stock_delivery_terms_id),
        ));
        if ($stock_indicator['flags']['add_to_cart']>0 && $stock_indicator['flags']['display_price_options']==2 &&  $actual_product_price<0.01) {
          $stock_indicator['flags']['add_to_cart'] = 0;
        }

        $stock_indicator_public = $stock_indicator['flags'];
        $stock_indicator_public['id'] = $stock_indicator['id'];
        $stock_indicator_public['quantity_max'] = \common\helpers\Product::filter_product_order_quantity($uprid, $stock_indicator['max_qty'], true);
        $stock_indicator_public['stock_code'] = $stock_indicator['stock_code'];
        $stock_indicator_public['text_stock_code'] = $stock_indicator['text_stock_code'];
        $stock_indicator_public['stock_indicator_text'] = $stock_indicator['stock_indicator_text'];
        if ($stock_indicator_public['request_for_quote']) {
            $special_price = false;
        }

//        $image_set = \common\classes\Images::getImageList($current_uprid);
        $product['products_tax_class_id'] = $priceInstance->getTaxClassId();
        $tax_rate = \common\helpers\Tax::get_tax_rate($product['products_tax_class_id']);
        global $cart;
        $in_cart = \frontend\design\Info::checkProductInCart(InventoryHelper::get_uprid($products_id, $attributes));
        if ($ext = \common\helpers\Acl::checkExtension('SupplierPurchase', 'allowed')){
            if ($ext::allowed()){
                $in_cart = $ext::checnkInCart($products_id, $attributes);
            }
        }
        $currency = \Yii::$app->settings->get('currency');
        $return_data = [
            'product_valid' => ($all_filled /* && $bundle_attributes_valid */ ? '1' : '0'),
            'product_price' => (($stock_indicator['flags']['display_price_options']==1 || ($stock_indicator['flags']['display_price_options']==2 &&  $actual_product_price<0.01))?'':$currencies->display_price($actual_product_price /* +$bundle_attributes_price */, $tax_rate, 1, ($special_price === false ? true : ''))),
            'product_unit_price' => ($actual_product_price /* +$bundle_attributes_price */) * $currencies->get_market_price_rate(DEFAULT_CURRENCY, $currency),
            'tax' => $tax_rate,
            'special_price' => ($special_price !== false? $currencies->display_price($special_price, $tax_rate, 1, true) : ''),
            'special_unit_price' => ($special_price !== false ? ($special_price ) * $currencies->get_market_price_rate(DEFAULT_CURRENCY, $currency) : ''),
            'product_qty' => ($check_inventory['inventory_id'] ? $check_inventory['products_quantity'] : ($product['products_quantity'] ? $product['products_quantity'] : '0')),
            'product_in_cart' => $in_cart,
            //'images_active' => array_keys($image_set),
            'current_uprid' => $uprid,
            'weight' => $productItem->getProductWeight($uprid),
            'attributes_array' => $attributes_array,
            'stock_indicator' => $stock_indicator_public,
            'dynamic_prop' => $dynamic_prop,
        ];
        /*if ($stock_indicator_public['request_for_quote']) {
            $return_data['product_price'] = '';
            $return_data['product_unit_price'] = '';
        }*/
        if ($ext = \common\helpers\Acl::checkExtension('TypicalOperatingTemp', 'runAttributes')){
            $ext_data = $ext::runAttributes($uprid);
            if ( is_array($ext_data) ) $return_data = array_merge($return_data, $ext_data);
        }

        return $return_data;
    }
/* depricated
    public static function getAttributesPrice($products_id, $product, $products_price_old, $special_price, $products_price, $qty, $configurator_koeff = 1) {
        $attributes_price = 0;
        $products_id = InventoryHelper::normalize_id($products_id);
        if (InventoryHelper::get_inventory_id_by_uprid($products_id) > 0) {
            if ($product['products_price_full'] == 1) {
                // full inventory price
                $products_price = InventoryHelper::get_inventory_full_price_by_uprid($products_id, $qty) * $configurator_koeff;
                if ($products_price_old && $special_price !== false) {
                    // if special - subtract difference
                    $products_price -= $products_price_old - $special_price;
                }
            } else {
                // additional inventory price
                $attributes_price = InventoryHelper::get_inventory_price_by_uprid($products_id, $qty) * $configurator_koeff;
                if (InventoryHelper::get_inventory_price_prefix_by_uprid($products_id) == '-') {
                    $attributes_price = -$attributes_price;
                }
            }
        }
        return [$attributes_price, $products_price];
    }
*/
    public static function getInventorySettings($products_id, $uprid) {
        $products = [];
        $uprid = \common\helpers\Inventory::normalizeInventoryId($uprid);
        if (PRODUCTS_INVENTORY == 'True' && !\common\helpers\Inventory::disabledOnProduct($uprid)) {
            $r = tep_db_query("select products_model, inventory_tax_class_id, stock_indication_id from " . TABLE_INVENTORY . " where products_id = '" . tep_db_input($uprid) . "'");
            if ($inventory = tep_db_fetch_array($r)) {
                if ($inventory['products_model']) {
                    $products['products_model'] = $inventory['products_model'];
                }
                if (!empty($inventory['stock_indication_id'])) {
                    $products['stock_indication_id'] = $inventory['stock_indication_id'];
                }
                $products['inventory_tax_class_id'] = $inventory['inventory_tax_class_id'];
                if ( !is_null($inventory['inventory_tax_class_id']) ) {
                    $products['products_tax_class_id'] = $inventory['inventory_tax_class_id'];
                }
            }
            if (($inventoryWeight = InventoryHelper::get_inventory_weight_by_uprid($products_id)) > 0) {
                $products['products_weight'] = $inventoryWeight;
            }
        }
        return $products;
    }
    
    public static function checkMultiAtts($products_id, array $params, $products_options_id){
        $products_id = (int)$products_id;
        if (is_array($params['mix_attr']) && is_array($params['mix_attr'][$products_id])){
            $atts = array_pop($params['mix_attr'][$products_id]);
            if (is_array($atts) && in_array($products_options_id, array_keys($atts))) return true;
        }
        return false;
    }

    public static function getInventoryStock($uprid, $stockIndication, $cartQty) {
        $uprid = \common\helpers\Inventory::normalizeInventoryId($uprid);
        $stockInfo = \common\classes\StockIndication::product_info(array(
                    'products_id' => $uprid,
                    'stock_indication_id' => $stockIndication,
                    'cart_qty' => $cartQty,
                    'cart_class' => true,
                    'products_quantity' => \common\helpers\Product::get_products_stock($uprid),
        ));
        $stockInfo['quantity_max'] = \common\helpers\Product::filter_product_order_quantity($uprid, $stockInfo['max_qty'], true);
        return $stockInfo;
    }

    public static function productBlock($pInfo) {
        $currencies = Yii::$container->get('currencies');
        // {{
        $ProductEditTabAccess = new \backend\models\ProductEdit\TabAccess();
        $ProductEditTabAccess->setProduct($pInfo);
        // }}
        return self::begin()->render('attributes.tpl', [
                    'pInfo' => $pInfo,
                    'TabAccess' => $ProductEditTabAccess,
                    'currencies' => $currencies,
        ]);
    }

    public static function productAttributesBox($pInfo)
    {
        $currencies = Yii::$container->get('currencies');
        // {{
        $ProductEditTabAccess = new \backend\models\ProductEdit\TabAccess();
        $ProductEditTabAccess->setProduct($pInfo);
        // }}
        return self::begin()->render('product-new-option.tpl', [
            'pInfo' => $pInfo,
            'TabAccess' => $ProductEditTabAccess,
            'currencies' => $currencies,
            'attributes' => Yii::$app->controller->view->selectedAttributes,
            'products_id' => $pInfo->products_id,
        ]);
    }

    public static function productInventoryBox() {
        global $languages_id;

        $currencies = Yii::$container->get('currencies');

        $products_id = (int) Yii::$app->request->post('products_id');
        $parent_products_id = (int) Yii::$app->request->post('parent_products_id', 0);
        $products_id_price = (int) Yii::$app->request->post('products_id_price', $parent_products_id?$parent_products_id:$products_id);

        $isStockUnlimited = false;
        $productRecord = \common\helpers\Product::getRecord($products_id);
/*
        if (count(\common\helpers\Product::getChildArray($productRecord)) > 0) {
            return '';
        }
*/
        if ($productRecord instanceof \common\models\Products) {
            $isStockUnlimited = ((int)$productRecord->manual_stock_unlimited > 0);
        }
        unset($productRecord);

        $products_tax_class_id = (int) Yii::$app->request->post('products_tax_class_id');
        $is_virtual = (int)Yii::$app->request->post('is_virtual', 0);

        $products_attributes = Yii::$app->request->post('products_attributes_id');

        $image_path = DIR_WS_CATALOG_IMAGES . 'products' . '/' . $products_id . '/';
        $images = [];
        $imageHashes = [];
        $images_query = tep_db_query("select id.*, i.* from " . TABLE_PRODUCTS_IMAGES . " as i left join " . TABLE_PRODUCTS_IMAGES_DESCRIPTION . " as id on (i.products_images_id=id.products_images_id and id.language_id=0) where i.products_id = '" . (int) $products_id . "' order by i.sort_order");
        while ($images_data = tep_db_fetch_array($images_query)) {
            $images[] = [
                        'products_images_id' => $images_data['products_images_id'],
                        'image_name' => (empty($images_data['hash_file_name']) ? '' : $image_path . $images_data['products_images_id'] . '/' . $images_data['hash_file_name']),
                        ];
            $imageHashes[$images_data['products_images_id']] = (empty($images_data['hash_file_name']) ? '' : $image_path . $images_data['products_images_id'] . '/' . $images_data['hash_file_name']);
        }
        Yii::$app->controller->view->images = $images;

        $imagesInventory = [];
        if ($ext = \common\helpers\Acl::checkExtension('InventortyImages', 'getImagesInventoryQuery')) {
          $images_query = $ext::getImagesInventoryQuery((int) $products_id);
          while ($images_data = tep_db_fetch_array($images_query)) {
            $imagesInventory[$images_data['uprid']][] = $imageHashes[$images_data['products_images_id']];
          }
        }
        Yii::$app->controller->view->imagesInventory = $imagesInventory;
//tabs details
        Yii::$app->controller->view->groups = [];
        if ($ext = \common\helpers\Acl::checkExtension('UserGroups', 'getGroups')) {
            $ext::getGroups();
        }
        $_def_curr_id = $currencies->currencies[DEFAULT_CURRENCY]['id'];
        Yii::$app->controller->view->defaultCurrency = $_def_curr_id;
        $_use_market_prices = (USE_MARKET_PRICES == 'True');
        Yii::$app->controller->view->useMarketPrices = $_use_market_prices;

        $options = [];
        $val_ids = [];
        if (is_array($products_attributes)) {
          foreach ($products_attributes as $products_options_id => $products_options) {
            if (\common\helpers\Attributes::is_virtual_option($products_options_id)) {
                unset($products_attributes[$products_options_id]);
                continue;
            }
            $val_ids = array_merge($val_ids, array_keys($products_options));
            foreach ($products_options as $products_options_values_id => $products_attributes_id) {
              $options[$products_options_id][] = $products_options_values_id;
            }
          }
        }
/// fill in options names and values names
        $options_name_data = $options_values_name_data = [];
        if (is_array($products_attributes)) {
          $r = tep_db_query("select products_options_id as id, products_options_name as name from " . TABLE_PRODUCTS_OPTIONS . " where products_options_id in ('" . implode("', '", array_map('intval', array_keys($products_attributes))) . "') and language_id  = '" . (int) $languages_id . "'");
          while ($d = tep_db_fetch_array($r)) {
            $options_name_data[$d['id']] = $d['name'];
          }
        }
        if (count($val_ids)) {
          $r = tep_db_query("select products_options_values_id as id, products_options_values_name as name from " . TABLE_PRODUCTS_OPTIONS_VALUES . " where products_options_values_id in ('" . implode("', '", array_map('intval', $val_ids)) . "') and language_id  = '" . (int) $languages_id . "'");
          while ($d = tep_db_fetch_array($r)) {
            $options_values_name_data[$d['id']] = $d['name'];
          }
        }

        ksort($options);
        reset($options);
        $i = 0;
        $idx = 0;
        foreach ($options as $key => $value) {
            if ($i == 0) {
                $idx = $key;
                $i = 1;
            }
            asort($options[$key]);
        }
        $inventory_options = [];
        if (is_array($options) && count($options)){
            $inventory_options = InventoryHelper::get_inventory_uprid($options, $idx);
        }
        $price_full_flag = ((int) Yii::$app->request->post('products_price_full', 0)>0);/// full or additional price

        $idxs = [];
        //use 'eval' + $idx to process 0, 1, 2 dimentional arrays in the same way
        // fill in array of idxs
        if (USE_MARKET_PRICES == 'True' && CUSTOMERS_GROUPS_ENABLE == 'True') {
          //[curr][group]
          foreach ($currencies->currencies as $value) {
            foreach (array_merge(array(array('groups_id' => 0, 'groups_name' => TEXT_MAIN)), Yii::$app->controller->view->groups) as $group) {
              $idxs[] = ['i' => '[' . $value['id'] . '][' . $group['groups_id'] . ']',
                         'c' => $value['id'],
                         'g' => $group['groups_id'],
                        ];
            }
          }
        } elseif (USE_MARKET_PRICES == 'True') {
          //[curr]
          foreach ($currencies->currencies as $cur => $value) {
            $idxs[] = ['i' => '[' . $value['id'] . ']',
                       'c' => $value['id'],
                       'g' => 0,
                      ];
          }
        } elseif(CUSTOMERS_GROUPS_ENABLE == 'True') {
          //[group]
          foreach (array_merge(array(array('groups_id' => 0, 'groups_name' => TEXT_MAIN)), Yii::$app->controller->view->groups) as $group) {
            $idxs[] = ['i' => '[' . $group['groups_id'] . ']',
                       'c' => $_def_curr_id,
                       'g' => $group['groups_id'],
                      ];
          }
        } else {
          $idxs[] = ['i' => '',
                     'c' => $_def_curr_id,
                     'g' => 0,
                    ];
        }

        $options_list = $options;
        $inventory_filter_list = [];
        $inventory_filter = Yii::$app->request->post('inventory_filter', []);

        $_tax = $__tax =  \common\helpers\Tax::get_tax_rate_value($products_tax_class_id)/100;
        $_roundTo = $currencies->get_decimal_places(DEFAULT_CURRENCY);
        $inventories = [];
        if (is_array($inventory_options)) {
            foreach ($inventory_options as $inventory_code) {
                if (defined('MAX_INVENTORY_COUNT')) {
                    if (count($inventory_filter) > 0) {
                        $inventory_filter_found = [];
                        foreach ($inventory_filter as $option => $values) {
                            $inventory_filter_found[$option] = 0;
                            foreach ($values as $value) {
                                if (preg_match("/\{$option\}$value(\{|$)/", $inventory_code)) {
                                   $inventory_filter_found[$option] = $value;
                                   break;
                                }
                            }
                        }
                        if (min($inventory_filter_found) == 0) {
                            continue;
                        }
                    } elseif (count($inventory_options) > MAX_INVENTORY_COUNT) {
                        continue;
                    }
                }
                $_tax = $__tax;
                $arr = preg_split("/[{}]/", $inventory_code);
                $options = [];
                for ($i = 1, $n = sizeof($arr); $i < $n; $i = $i + 2) {
                    $options[] = [
                        'label' => $options_name_data[$arr[$i]],
                        'value' => $options_values_name_data[$arr[$i+1]],
                    ];
                }
                $inventory_query = tep_db_query("select * from " . TABLE_INVENTORY . " where products_id = '" . $products_id . $inventory_code . "'");
                if (tep_db_num_rows($inventory_query) > 0) {
                    $inventory_data = tep_db_fetch_array($inventory_query);
                    $inventory_data['products_id_stock'] = \common\helpers\Inventory::normalizeInventoryId($inventory_data['products_id']);
                } else {
                    $inventory_data = [
                        'inventory_id' => 0,
                        'products_id' => $products_id . $inventory_code,
                        'products_model' => '',
                        'products_quantity' => 0,
                        'products_upc' => '',
                        'products_ean' => '',
                        'products_asin' => '',
                        'products_isbn' => '',
                        'stock_indication_id' => '',
                        'stock_delivery_terms_id' => '',
                        'non_existent' => 0,
                        'stock_control' => 0,
                    ];
                    $inventory_data['products_id_stock'] = $inventory_data['products_id'];
                    if ( $parent_products_id>0 ) {
                        $inventory_data['products_id_stock'] = intval($parent_products_id).$inventory_code;
                    }
                }
                $inventory_data['price_inventory_id'] = $inventory_data['inventory_id'];

                if ( $parent_products_id>0 ) {
                    if ((int)$products_id != (int)$products_id_price) {
                        $priceProductData = \common\models\Inventory::find()
                            ->where(['products_id' => intval($products_id_price) . $inventory_code])
                            ->select(['price_inventory_id'=>'inventory_id', 'inventory_price', 'inventory_discount_price', 'price_prefix', 'inventory_full_price', 'inventory_discount_full_price', 'inventory_tax_class_id'])
                            ->asArray()
                            ->one();
                        if (is_array($priceProductData)) {
                            $inventory_data = array_merge($inventory_data, $priceProductData);
                        }
                    }
                    if ((int)$products_id != (int)$parent_products_id) {
                        $priceProductData = \common\models\Inventory::find()
                            ->where(['products_id' => intval($parent_products_id) . $inventory_code])
                            ->select(['stock_indication_id', 'stock_delivery_terms_id', 'stock_control', 'products_quantity'])
                            ->asArray()
                            ->one();
                        if (is_array($priceProductData)) {
                            $inventory_data = array_merge($inventory_data, $priceProductData);
                        }
                    }
                }

                if (isset($_POST['inventorymodel_' . $inventory_data['products_id']])) {
                    $inventory_data['products_model'] = $_POST['inventorymodel_' . $inventory_data['products_id']];
                }
                if (isset($_POST['inventoryqty_' . $inventory_data['products_id']])) {
                    $inventory_data['products_quantity'] = $_POST['inventoryqty_' . $inventory_data['products_id']];
                }
                if (isset($_POST['inventory_tax_class_id_' . $inventory_data['products_id']])) {
                    $inventory_data['inventory_tax_class_id'] = $_POST['inventory_tax_class_id_' . $inventory_data['products_id']];
                }
                if ( !is_null($inventory_data['inventory_tax_class_id']) ) {
                    $_tax = \common\helpers\Tax::get_tax_rate_value($inventory_data['inventory_tax_class_id']) / 100;
                }
                if (isset($_POST['inventoryupc_' . $inventory_data['products_id']])) {
                    $inventory_data['products_upc'] = $_POST['inventoryupc_' . $inventory_data['products_id']];
                }
                if (isset($_POST['inventoryean_' . $inventory_data['products_id']])) {
                    $inventory_data['products_ean'] = $_POST['inventoryean_' . $inventory_data['products_id']];
                }
                if (isset($_POST['inventoryasin_' . $inventory_data['products_id']])) {
                    $inventory_data['products_asin'] = $_POST['inventoryasin_' . $inventory_data['products_id']];
                }
                if (isset($_POST['inventoryisbn_' . $inventory_data['products_id']])) {
                    $inventory_data['products_isbn'] = $_POST['inventoryisbn_' . $inventory_data['products_id']];
                }
                if (isset($_POST['inventorystock_indication_' . $inventory_data['products_id']])) {
                    $inventory_data['stock_indication_id'] = $_POST['inventorystock_indication_' . $inventory_data['products_id']];
                }
                if (isset($_POST['inventorystock_delivery_terms_' . $inventory_data['products_id']])) {
                    $inventory_data['stock_delivery_terms_id'] = $_POST['inventorystock_delivery_terms_' . $inventory_data['products_id']];
                }
                if (isset($_POST['inventoryweight_' . $inventory_data['products_id']])) {
                    $inventory_data['inventory_weight'] = $_POST['inventoryweight_' . $inventory_data['products_id']];
                }
                if (isset($_POST['inventoryexistent_' . $inventory_data['products_id']])) {
                    $inventory_data['non_existent'] = (int) $_POST['inventoryexistent_' . $inventory_data['products_id']];
                }

                $variant_name = '';
                foreach ($options as $__option) {
                    if (!empty($variant_name))
                        $variant_name .= ', ';
                    $variant_name .= $__option['label'] . ': ' . $__option['value'];
                }
//
                $price_tabs_data = [];
///process pseudo group "Main"
                if (!$_use_market_prices) {
                    $qty_discounts = [];
                    //get from DB
                    if ($price_full_flag) {
                      $tmp = \common\helpers\Product::parseQtyDiscountArray($inventory_data['inventory_discount_full_price']);
                    } else {
                      $tmp = \common\helpers\Product::parseQtyDiscountArray($inventory_data['inventory_discount_price']);
                    }

                    if (count($tmp) > 0 ) {
                      foreach ($tmp as $qty => $price) {
                        $qty_discounts[$qty]['price'] = $price;
                        $qty_discounts[$qty]['price_gross'] = round($price + round($price * (double)$_tax, 6), $_roundTo);
                      }
                    }

                    //update inventory details from _post
                    // qty discount table
                    $_tmp_post = false;
                    if (CUSTOMERS_GROUPS_ENABLE == 'True' ) {
                      $idx = '[0]';
                    } else {
                      $idx = '';
                    }
                    $key = 'qty_discounts';
                    if (!isset($_POST['products_group_price_' . $inventory_data['products_id']])) { // inventory (not product's) data is not yet posted
                      $qty_discount_status = (count($qty_discounts)>0);
                    } else {
                      eval('$qty_discount_status = @$_POST["qty_discount_status' . $inventory_data['products_id'] . '"]' . $idx . ';');
                    }
                    $qty_discounts_main = false;
                    eval('$qty_discounts_main = @$_POST["discount_qty"]' . $idx . ';');

                    if (is_array($qty_discounts_main)) {
                      // allow qty discount steps from main product only
                      //1) reset old not used qty steps 2) add new
                      if (is_array($qty_discounts)) {
                        foreach ($qty_discounts as $qty => $tmp){
                          if (!in_array($qty, $qty_discounts_main)) {
                            unset($qty_discounts[$qty]);
                          }
                        }
                      }
                      //2) add new and update existing
                      $_tmp_post = false;
                      eval('$_tmp_post = @$_POST[\'discount_price' . $inventory_data['products_id'] . '\']' . $idx . ';');
                      foreach ($qty_discounts_main as $kk => $qty_discounts_qty) {
                        if(is_array($_tmp_post) && isset($_tmp_post[$qty_discounts_qty]) ){
                          $vv = $_tmp_post[$qty_discounts_qty];
                        } elseif (isset($_POST['products_group_price' . $inventory_data['products_id']])) { // discount is reset
                          $vv = 0;
                        } else { //from DB  - required as _main could have more threshholds
                          if (isset($qty_discounts[$qty_discounts_qty]['price'])) {
                            $vv = $qty_discounts[$qty_discounts_qty]['price'];
                          } else {
                            $vv = 0;
                          }
                        }
                        $qty_discounts[$qty_discounts_qty]['price'] = $vv;
                        $qty_discounts[$qty_discounts_qty]['price_gross'] = round($vv + round($vv * (double)$_tax, 6), $_roundTo);// gross should NOT be posted
                      }
                    } else { // product's data is always post
                      // reset DB's data if POST (removed on main product)
                      $qty_discounts = [];
                      $qty_discount_status = false;
                    }

                    // qty discount table EOF
                    //price
                    if (isset($_POST['inventorypriceprefix_' . $inventory_data['products_id']])) {
                      if (CUSTOMERS_GROUPS_ENABLE == 'True') {
                        $inventory_data['price_prefix'] = $_POST['inventorypriceprefix_' . $inventory_data['products_id']][0];
                      } else {
                        $inventory_data['price_prefix'] = $_POST['inventorypriceprefix_' . $inventory_data['products_id']];
                      }
                    }
                    if (isset($_POST['products_group_price_' . $inventory_data['products_id']])) {
                      if (CUSTOMERS_GROUPS_ENABLE == 'True') {
                        $inventory_data['products_group_price'] = (float)$_POST['products_group_price_' . $inventory_data['products_id']][0];
                      } else {
                        $inventory_data['products_group_price'] = (float)$_POST['products_group_price_' . $inventory_data['products_id']];
                      }
                    } else {
                      if ($price_full_flag) {
                        $inventory_data['products_group_price'] = (float)$inventory_data['inventory_full_price'];
                      } else {
                        $inventory_data['products_group_price'] = (float)$inventory_data['inventory_price'];
                      }
                    }

                    $tmp = [
                              'groups_id' => 0,
                              'currencies_id' => $_def_curr_id,
                              'supplier_price_manual' => $inventory_data['supplier_price_manual'],
                              'price_prefix' => $inventory_data['price_prefix'],
                              'products_group_price' => $inventory_data['products_group_price'],
                              'products_group_price_gross' => round($inventory_data['products_group_price'] + round($inventory_data['products_group_price']*$_tax, 6), $_roundTo),
                              'tax_rate' => (double)$_tax,
                              'round_to' => (int)$_roundTo,
                              'qty_discounts' => $qty_discounts,
                              'qty_discount_status' => $qty_discount_status,
                            ];
                    if (CUSTOMERS_GROUPS_ENABLE == 'True') {
                      $price_tabs_data[0] = $tmp;
                    } else {
                      $price_tabs_data = $tmp;
                    }
                    $net_price_formatted = ($price_full_flag?'':$inventory_data['price_prefix']) . $currencies->display_price($inventory_data['products_group_price'], 0, 1 ,false);
                    $gross_price_formatted = ($price_full_flag?'':$inventory_data['price_prefix']) . $currencies->display_price( round($inventory_data['products_group_price'] + round($inventory_data['products_group_price']*$_tax, 6), $_roundTo), 0, 1 ,false);
                }
                if ($_use_market_prices || CUSTOMERS_GROUPS_ENABLE == 'True') {
                  if ($price_full_flag) {
                    $fields = ", ip.inventory_full_price as products_group_price, round(ip.inventory_full_price +round(ip.inventory_full_price *" . (double)$_tax . ", 6), " . (int)$_roundTo. ") as products_group_price_gross,  inventory_discount_full_price as products_discount_price";
                  } else {
                    $fields = ", ip.inventory_group_price as products_group_price, round(ip.inventory_group_price +round(ip.inventory_group_price *" . (double)$_tax . ", 6), " . (int)$_roundTo. ") as products_group_price_gross, inventory_group_discount_price as products_discount_price";
                  }
                  $fields .= ", ip.supplier_price_manual ";
                  $products_price_query = tep_db_query("select ip.groups_id, ip.currencies_id, ip.price_prefix " . $fields . " from " . TABLE_INVENTORY_PRICES . " ip where ip.inventory_id = '" . (int) $inventory_data['price_inventory_id'] . "' " . ($_use_market_prices?'':" and ip.groups_id>0"). " order by ip.currencies_id, ip.groups_id");
                  $_tmp_keys = [
                    'price_tabs_data' => ['qty_discount_status', 'supplier_price_manual', 'price_prefix', 'qty_discounts', 'products_group_price', 'products_group_price_gross', 'tax_rate', 'round_to', 'base_price', 'base_price_gross']
                    ];
                  $_base_price = $inventory_data['products_group_price'];
                  $_base_price_gross = round($_base_price + round($_base_price*$_tax, 6), $_roundTo);
                  if ($_use_market_prices) {
                    $net_price_formatted = ($price_full_flag?'':$inventory_data['price_prefix']) . $currencies->display_price($_base_price, 0, 1 ,false);
                    $gross_price_formatted = ($price_full_flag?'':$inventory_data['price_prefix']) . $currencies->display_price( round($_base_price + round($_base_price*$_tax, 6), $_roundTo), 0, 1 ,false);
                  }
                  while ($products_price_data = tep_db_fetch_array($products_price_query)) {
                    $products_price_data['round_to'] = (int)$currencies->get_decimal_places_by_id($products_price_data['currencies_id']);
                    if ($_use_market_prices  && $products_price_data['groups_id'] == 0 ) {

                      $_base_price = $products_price_data['products_group_price'];
                      $_base_price_gross = $products_price_data['products_group_price_gross'];

                      if ( $products_price_data['currencies_id'] == $_def_curr_id ) {
                        if (isset($_POST['products_group_price_' . $inventory_data['products_id']][$_def_curr_id][0])) {
                          $tmp = $_POST['products_group_price_' . $inventory_data['products_id']][$_def_curr_id][0];
                          $net_price_formatted = ($price_full_flag?'':$products_price_data['price_prefix']) . $currencies->display_price($tmp, 0, 1 ,false);
                          $gross_price_formatted = ($price_full_flag?'':$products_price_data['price_prefix']) . $currencies->display_price( round($tmp + round($tmp*$_tax, 6), $_roundTo), 0, 1 ,false);
                        } else {
                          $net_price_formatted = ($price_full_flag?'':$products_price_data['price_prefix']) . $currencies->display_price($_base_price, 0, 1 ,false);
                          $gross_price_formatted = ($price_full_flag?'':$products_price_data['price_prefix']) . $currencies->display_price( round($_base_price + round($_base_price*$_tax, 6), $_roundTo), 0, 1 ,false);
                        }
                      }
                    }
                    $products_price_data['base_price'] = $_base_price;
                    $products_price_data['base_price_gross'] = $_base_price_gross;


                    if (USE_MARKET_PRICES == 'True' && CUSTOMERS_GROUPS_ENABLE == 'True') {
                      foreach ($_tmp_keys as $k => $v) {
                        $_tmp_keys[$k] = array_merge($v, ['groups_id', 'currencies_id']);
                      }
                      $idx = '[' . $products_price_data['currencies_id'] . '][' . $products_price_data['groups_id'] . ']';
                    } elseif (USE_MARKET_PRICES == 'True') {
                      foreach ($_tmp_keys as $k => $v) {
                        $_tmp_keys[$k] = array_merge($v, ['currencies_id']);
                      }
                      $idx = '[' . $products_price_data['currencies_id'] . ']';
                    } else {
                      foreach ($_tmp_keys as $k => $v) {
                        $_tmp_keys[$k] = array_merge($v, ['groups_id']);
                      }
                      $idx = '[' . $products_price_data['groups_id'] . ']';
                    }
                    $tmp_qty_discounts = false;

                    foreach ($_tmp_keys as $k => $v) {
                      // fill in q-ty discount from DB
                      if ($k == 'price_tabs_data') {
                        if($ext = \common\helpers\Acl::checkExtension('UserGroups', 'allowedGroupDiscounts') ) {
                          $tmp_qty_discounts = \common\helpers\Product::parseQtyDiscountArray($products_price_data['products_discount_price']);
                        }
                      }
                      if (is_array($tmp_qty_discounts)) {
                        foreach ($tmp_qty_discounts as $key => $value) {
                          $products_price_data['qty_discounts'][$key]['price'] = $value;
                          $products_price_data['qty_discounts'][$key]['price_gross'] = round($value + round($value * (double)$_tax, 6), $products_price_data['round_to']);
                        }
                      }

                      // update inventory details from _post
                      $qty_discounts_main = false;
                      eval('$qty_discounts_main = @$_POST["discount_qty"]' . $idx . ';');
                      if (!isset($_POST['products_group_price_' . $inventory_data['products_id']])) { // inventory (not product's) data is not yet posted
                        $products_price_data['qty_discount_status'] = (count($tmp_qty_discounts)>0);
                      } else {
                        eval('$products_price_data[\'qty_discount_status\'] = @$_POST["qty_discount_status' . $inventory_data['products_id'] . '"]' . $idx . ';');
                      }
                      unset($tmp_qty_discounts);
                      //$v includes ['qty_discount_status', 'price_prefix', 'qty_discounts', 'products_group_price',
                      //calculated/same so shouldn't updated from post: 'products_group_price_gross', 'tax_rate', 'round_to', 'base_price', 'base_price_gross'];
                      foreach ($v as $key) {
                        if (in_array($key, ['products_group_price_gross', 'tax_rate', 'round_to', 'base_price', 'base_price_gross'])) {
                          continue;
                        }
                        $_tmp_post = false;
                        if ($key == 'qty_discounts') {
                          if (is_array($qty_discounts_main)) {
                            // allow qty discount steps from main product only
                            //1) reset old not used qty steps 2) add new
                            if (is_array($products_price_data['qty_discounts'])) {
                              foreach ($products_price_data['qty_discounts'] as $qty => $tmp){
                                if (!in_array($qty, $qty_discounts_main)) {
                                  unset($products_price_data['qty_discounts'][$qty]);
                                }
                              }
                            }

                            eval('$_tmp_post = @$_POST[\'discount_price' . $inventory_data['products_id'] . '\']' . $idx . ';');
                            //2) add new and update existing
                            foreach ($qty_discounts_main as $kk => $qty_discounts_qty) {
                              if(is_array($_tmp_post) && isset($_tmp_post[$qty_discounts_qty]) ){
                                // from posts
                                $vv = (double)$_tmp_post[$qty_discounts_qty];
                              } elseif (isset($_POST['products_group_price' . $inventory_data['products_id']])) { // discount is reset
                                $vv = ''; // new step
                              } else {
                                if (isset($products_price_data['qty_discounts'][$qty_discounts_qty]['price'])) {
                                  $vv = $products_price_data['qty_discounts'][$qty_discounts_qty]['price'];
                                } else {
                                  $vv = 0;
                                }
                              }
                              $products_price_data['qty_discounts'][$qty_discounts_qty]['price'] = $vv;
                              $products_price_data['qty_discounts'][$qty_discounts_qty]['price_gross'] = round($vv + round($vv * (double)$_tax, 6), $products_price_data['round_to']);
                            }
                          } else {
                            //POST - main q-ty discount removed
                            $products_price_data['qty_discounts'] = [];
                            $products_price_data['qty_discount_status'] = false;
                          }

                        } else {
                          eval('$_tmp_post = isset($_POST[\''. $key . '_' . $inventory_data['products_id']. '\']' . $idx . ');');
                          if (!$_tmp_post) {
                            continue;
                          }
                          eval('$_tmp_post = $_POST[\''. $key . '_' . $inventory_data['products_id']. '\']' . $idx . ';');
                          $products_price_data[$key] = $_tmp_post;
                          if ($key == 'products_group_price') {
                            // gross should NOT be posted, so calculated from net
                            $products_price_data[$key . '_gross'] = round($_tmp_post + round($_tmp_post * (double)$_tax, 6), $products_price_data['round_to']);
                          }
                        }

                      }
                      $tmp = array_intersect_key($products_price_data, array_flip($v));
                      eval('$'. $k . $idx . ' = $tmp;');
                    }
                  }
                }
//new product: inventory init (discounts from main product)
//re-fill from post
                //[template, _post, default], ...
                $_tmp_keys = [['price_prefix', 'inventorypriceprefix_', '+'], ['products_group_price', 'products_group_price_', ''], /*['supplier_price_manual','supplier_price_manual', null]*/];
                foreach ($idxs as $idx) {
                  foreach ($_tmp_keys as $field) {
                    $_e = false;
                    eval('$_e = isset($price_tabs_data' . $idx['i'] . '[\''. $field[0] . '\']);');
                    if ($_e) { //don't update existing - already re-filled.
                      continue;
                    }
                    eval('$price_tabs_data' . $idx['i'] . '[\''. $field[0] . '\'] = (isset($_POST[\'' . $field[1] . $inventory_data['products_id'] . '\']' . $idx['i'] . ')? $_POST[\'' . $field[1] . $inventory_data['products_id'] . '\']' . $idx['i'] . ' : \'' . $field[2] . '\');');
                    /// extra for some fields
                    if ($field[0] == 'products_group_price') { // calculate gross price
                      $round_to = (int)$currencies->get_decimal_places_by_id($field['c']);
                      eval('$price_tabs_data' . $idx['i'] . '[\''. $field[0] . '_gross\'] = round((double)$price_tabs_data' . $idx['i'] . '[\''. $field[0] . '\'] + round((double)$price_tabs_data' . $idx['i'] . '[\''. $field[0] . '\'] * (double)$_tax, 6), $round_to);');
                      if ($idx['g']==0 && $idx['c']==$_def_curr_id) {
                        $_base_price = 0;
                        eval('$_base_price = (isset($_POST[\'' . $field[1] . $inventory_data['products_id'] . '\']' . $idx['i'] . ')? $_POST[\'' . $field[1] . $inventory_data['products_id'] . '\']' . $idx['i'] . ' : \'' . $field[2] . '\');');
                        if ($price_full_flag) {
                          $price_prefix = '';
                        } else {
                          eval('$price_prefix = (isset($_POST[\'inventorypriceprefix_' . $inventory_data['products_id'] . '\']' . $idx['i'] . ')? $_POST[\'inventorypriceprefix_' . $inventory_data['products_id'] . '\']' . $idx['i'] . ' : \'+\');');
                        }
                        $net_price_formatted = $price_prefix . $currencies->display_price($_base_price, 0, 1 ,false);
                        $gross_price_formatted = $price_prefix . $currencies->display_price( round((double)$_base_price + round((double)$_base_price*(double)$_tax, 6), $_roundTo), 0, 1 ,false);
                      }
                    }
                  }
                }
// new product
/// qty discount (probably move to general list above ...)
                if ($_use_market_prices && (int)$products_id==0 && isset($_POST['discount_qty']) && is_array($_POST['discount_qty'])) {
                  //main product qty duscount. 1, 2 or 3 dimentions (no market price and group; either of them, both of them)
                  foreach ($_POST['discount_qty'] as $k => $a) {
                    if (is_array($a)) {// $k = currency
                      foreach ($a as $k1 => $a1) {
                        if (is_array($a1)) {// $k1 = group
                          foreach ($a1 as $qty) {
                            if ($qty > 0 && !isset($price_tabs_data[$k][$k1]['qty_discounts'][$qty])) {
                              // from post or 0 price & inactive
                              if (isset($_POST['qty_discount_status' . $inventory_data['products_id']][$k][$k1])){
                                $_tmp_net_price = $_POST['discount_price' . $inventory_data['products_id']][$k][$k1][$qty];
                                $round_to = (int)$currencies->get_decimal_places_by_id($k);
                                $price_tabs_data[$k][$k1]['qty_discounts'][$qty]['price'] = $_tmp_net_price;
                                $price_tabs_data[$k][$k1]['qty_discounts'][$qty]['price_gross'] =
                                    round($_tmp_net_price + round($_tmp_net_price * (double)$_tax, 6), $round_to);
                                $price_tabs_data[$k][$k1]['qty_discount_status'] = $_POST['qty_discount_status' . $inventory_data['products_id']][$k][$k1];
                              } else {
                                $price_tabs_data[$k][$k1]['qty_discounts'][$qty]['price'] = '';
                                $price_tabs_data[$k][$k1]['qty_discounts'][$qty]['price_gross'] = '';
                                $price_tabs_data[$k][$k1]['qty_discount_status'] = false;
                              }
                            }
                          }
                        } else {
                          if ($a1 > 0 && !isset($price_tabs_data[$k]['qty_discounts'][$a1]['price'])) {
                            if (isset($_POST['qty_discount_status' . $inventory_data['products_id']][$k])) {
                              $_tmp_net_price = $_POST['discount_price' . $inventory_data['products_id']][$k][$a1];
                              if (USE_MARKET_PRICES == 'True') {
                                $round_to = (int)$currencies->get_decimal_places_by_id($k);
                              } else {
                                $round_to = (int)$currencies->get_decimal_places(DEFAULT_CURRENCY);
                              }
                              $price_tabs_data[$k]['qty_discounts'][$a1]['price'] = $_tmp_net_price;
                              $price_tabs_data[$k]['qty_discounts'][$a1]['price_gross'] =
                                  round($_tmp_net_price + round($_tmp_net_price * (double)$_tax, 6), $round_to);
                              $price_tabs_data[$k]['qty_discount_status'] = $_POST['qty_discount_status' . $inventory_data['products_id']][$k];
                            } else {
                              $price_tabs_data[$k]['qty_discounts'][$a1]['price'] = '';
                              $price_tabs_data[$k]['qty_discounts'][$a1]['price_gross'] = '';
                              $price_tabs_data[$k]['qty_discount_status'] = false;
                            }
                          }
                        }
                      }
                    } else {
                      if ($a > 0 && !isset($price_tabs_data['qty_discounts'][$a]['price'] )) {
                        if (isset($_POST['qty_discount_status' . $inventory_data['products_id']] )) {
                          $_tmp_net_price = $_POST['discount_price' . $inventory_data['products_id'] . ''][$a];
                          $round_to = (int)$currencies->get_decimal_places(DEFAULT_CURRENCY);
                          $price_tabs_data['qty_discounts'][$a]['price'] = $_tmp_net_price;
                          $price_tabs_data['qty_discounts'][$a]['price_gross'] =
                              round($_tmp_net_price + round($_tmp_net_price * (double)$_tax, 6), $round_to);
                          $price_tabs_data['qty_discount_status'] = $_POST['qty_discount_status' . $inventory_data['products_id']];
                        } else {
                          $price_tabs_data['qty_discounts'][$a]['price'] = '';
                          $price_tabs_data['qty_discounts'][$a]['price_gross'] = '';
                          $price_tabs_data['qty_discount_status'] = false;
                        }
                      }
                    }
                  }
                }
              /*  */

                $allocatedTemporary = 0;
                if (tep_not_null($inventory_data['products_id_stock'])) {
                    $inventory_data['warehouse_quantity'] = \common\helpers\Product::getQuantity($inventory_data['products_id_stock']);
                    $inventory_data['allocated_quantity'] = \common\helpers\Product::getAllocated($inventory_data['products_id_stock']);
                    $inventory_data['temporary_quantity'] = \common\helpers\Product::getAllocatedTemporary($inventory_data['products_id_stock']);
                    $inventory_data['products_quantity'] = ($inventory_data['warehouse_quantity'] - ($inventory_data['allocated_quantity'] + $inventory_data['temporary_quantity']));
                    $inventory_data['ordered_quantity'] = (int)$inventory_data['ordered_stock_quantity'];
                    $inventory_data['suppliers_quantity'] = 0;//$inventory_data['suppliers_stock_quantity'];
                    $allocatedTemporary = \common\helpers\Product::getAllocatedTemporary($inventory_data['products_id_stock'], true);
                }

                $inventory_data['suppliers'] = [];
                if (tep_not_null($inventory_data['products_id_stock']) && (int)$inventory_data['products_id_stock']!=0) {
                    $sProducts = SuppliersProducts::getSupplierUpridProducts($inventory_data['products_id_stock'])->all();

                    if (!$sProducts){
                        $sProduct = (new SuppliersProducts())
                                ->saveDefaultSupplierProduct(['products_id' => (int)$inventory_data['products_id_stock'], 'uprid' => $inventory_data['products_id_stock']]);
                        $sProducts = [$sProduct];
                    }

                    if ($sProducts){
                        foreach($sProducts as $sProduct){
                            $inventory_data['suppliers'][$sProduct->suppliers_id] = $sProduct;
                            $inventory_data['suppliers_quantity'] += $sProduct->suppliers_quantity;
                        }
                    }
                } else {
                    $inventory_data['suppliers'] = [];
                    $dSupplier = \common\models\Suppliers::findOne(['is_default' => 1]);
                    if ($dSupplier){
                        $sProduct = new \common\models\SuppliersProducts();
                        $sProduct->loadDefaultValues();
                        $sProduct->loadSupplierValues($dSupplier->suppliers_id);
                        $inventory_data['suppliers'][$dSupplier->suppliers_id] = $sProduct;
                    }
                }


                $warehouseStockControlList = \common\models\WarehouseInventoryControl::find()->andWhere(['products_id' => $inventory_data['products_id_stock']])
                    ->select('warehouse_id')->asArray()->indexBy('platform_id')->column();

                $platformStockControlList = \common\models\PlatformInventoryControl::find()->andWhere(['products_id' => $inventory_data['products_id_stock']])
                    ->select('current_quantity')->asArray()->indexBy('platform_id')->column();

                $platforWarehouseList = [];
                $platformStockList = [];
                $platformStock = \common\models\Platforms::find()->where(['status' => 1])->orderBy("sort_order")->all();
                foreach($platformStock as $platform){
                    $platformStockList[] = [
                        'id' => $platform->platform_id,
                        'name' => $platform->platform_name,
                        'qty' => (isset($platformStockControlList[$platform->platform_id]) ? $platformStockControlList[$platform->platform_id] : 0),
                    ];

                    $platforWarehouseList[] = [
                        'id' => $platform->platform_id,
                        'name' => $platform->platform_name,
                        'warehouse' => (isset($warehouseStockControlList[$platform->platform_id]) ? $warehouseStockControlList[$platform->platform_id] : \common\helpers\Warehouses::get_default_warehouse()),
                    ];
                }

                $inventory = [
                    'stock_control' => $inventory_data['stock_control'],
                    'platformStockList' => $platformStockList,
                    'platforWarehouseList' => $platforWarehouseList,
                    'variant_name' => $variant_name,
                    'uprid' => $inventory_data['products_id'],
                    'stock_uprid' => $inventory_data['products_id_stock'],
                    'options' => $options,
                    'price_tabs_data' => $price_tabs_data,
                    'products_tax_class_id' => $products_tax_class_id,
                    'inventory_tax_class_id' => $inventory_data['inventory_tax_class_id'],
                    'products_quantity' => $inventory_data['products_quantity'],
                    'stock_indication_id' => $inventory_data['stock_indication_id'],
                    'stock_delivery_terms_id' => $inventory_data['stock_delivery_terms_id'],
                    'product_type' => ($is_virtual == true ? 'physical':'virtual'),
//                    'inventoryqty' => tep_draw_input_field('inventoryqty_' . $inventory_data['products_id'], $inventory_data['products_quantity'], 'class="form-control form-control-small-qty" readonly disabled'),
//                    'inventoryqtyupdate' => tep_draw_pull_down_menu('inventoryqtyupdateprefix_' . $inventory_data['products_id'], [['id' => '+', 'text' => '+'], ['id' => '-', 'text' => '-']], $_POST['inventoryqtyupdateprefix_' . $inventory_data['products_id']], 'class="form-control form-control-small-qty"') . ' ' . tep_draw_input_field('inventoryqtyupdate_' . $inventory_data['products_id'], $_POST['inventoryqtyupdate_' . $inventory_data['products_id']], 'class="form-control form-control-small-qty"'),
//                    'inventorystock_indication' => tep_draw_pull_down_menu('inventorystock_indication_' . $inventory_data['products_id'], \common\classes\StockIndication::get_filtered_variants(true, ($is_virtual?'physical':'virtual')), $inventory_data['stock_indication_id'], 'class="form-control stock-indication-id"'),
//                    'inventorystock_delivery_terms' => tep_draw_pull_down_menu('inventorystock_delivery_terms_' . $inventory_data['products_id'], \common\classes\StockIndication::get_delivery_terms(), $inventory_data['stock_delivery_terms_id'], 'class="form-control"'),
                    'inventoryexistent' => tep_draw_checkbox_field('inventoryexistent_' . $inventory_data['products_id'], '1', $inventory_data['non_existent'], 'class="uniform"'),
                    'allocated_quantity' => (int)$inventory_data['allocated_quantity'],
                    'temporary_quantity' => (int)$inventory_data['temporary_quantity'] . (($allocatedTemporary > 0) ? (' / ' . $allocatedTemporary) : ''),
                    'warehouse_quantity' => (int)$inventory_data['warehouse_quantity'],
                    'ordered_quantity' => (int)$inventory_data['ordered_quantity'],
                    'suppliers_quantity' => (int)$inventory_data['suppliers_quantity'],
                    'suppliers' => $inventory_data['suppliers'],
                    'net_price_formatted' => $net_price_formatted,
                    'gross_price_formatted' => $gross_price_formatted,
                ];
                if ($ext = \common\helpers\Acl::checkExtension('AttributesDetails', 'getFields')) {
                    $inventory = array_merge($inventory, $ext::getFields($inventory_data));
                }
                $inventories[] = $inventory;
            }
            if (defined('MAX_INVENTORY_COUNT')) {
                foreach ($options_list as $option_id => $values) {
                    $values_list = [];
                    foreach ($values as $value) {
                        $values_list[$value] = $options_values_name_data[$value];
                    }
                    $inventory_filter_list[$option_id] = [
                        'id' => $option_id,
                        'name' => $options_name_data[$option_id],
                        'values' => $values_list,
                        'selected' => $inventory_filter[$option_id],
                    ];
                }
            }
        }
/// re-arrange data arrays for design templates
//
// init price tabs
        $price_tabs = [];
        $price_tabparams = [];
////currencies tabs and params
        if ($_use_market_prices) {
          Yii::$app->controller->view->currenciesTabs = [];
          foreach ($currencies->currencies as $cur => $value) {
            $value['def_data'] = ['currencies_id' => $value['id']];
            Yii::$app->controller->view->currenciesTabs[] = $value;
          }
          Yii::$app->controller->view->price_tabs[] = Yii::$app->controller->view->currenciesTabs;
          Yii::$app->controller->view->price_tabparams[] =  [
              'cssClass' => 'tabs-currencies',
              'tabsSkipState' => true,
              'tabs_type' => 'hTab',
              //'include' => 'test/test.tpl',
          ];
        }

        Yii::$app->controller->view->groups_m = array_merge(array(array('groups_id' => 0, 'groups_name' => TEXT_MAIN)), Yii::$app->controller->view->groups);

    //// groups tabs and params
        if (CUSTOMERS_GROUPS_ENABLE == 'True' && count(Yii::$app->controller->view->groups_m)>0) {
          $tmp = [];
          foreach (Yii::$app->controller->view->groups_m as $value) {
            $value['id'] = $value['groups_id'];
            $value['title'] = $value['groups_name'];
            $value['def_data'] = ['groups_id' => $value['id']];
            unset($value['groups_name']);
            unset($value['groups_id']);
            $tmp[] = $value;
          }
          Yii::$app->controller->view->price_tabs[] = $tmp;
          unset($tmp);
          Yii::$app->controller->view->price_tabparams[] = [
              'cssClass' => 'tabs-groups', // add to tabs and tab-pane
              'tabsSkipState' => true,
              //'callback' => 'productPriceBlock', // smarty function which will be called before children tabs , data passed as params params
              'callback_bottom' => '',
              'tabs_type' => 'lTab',
          ];
        }
        // {{
        $ProductEditTabAccess = new \backend\models\ProductEdit\TabAccess();
        $pInfo = new \objectInfo([
            'products_id' => Yii::$app->request->post('products_id', 0),
            'parent_products_id' => Yii::$app->request->post('parent_products_id', 0),
            'products_id_stock' => Yii::$app->request->post('parent_products_id', 0),
            'products_id_price' => Yii::$app->request->post('parent_products_id', 0),
        ]);
        $ProductEditTabAccess->setProduct($pInfo);
        // }}
        return self::begin()->render('product-inventory-box.tpl', [
            'TabAccess' => $ProductEditTabAccess,
            'inventories' => $inventories,
            'inventory_filter_list' => $inventory_filter_list,
            'is_virtual' => $is_virtual,
            'products_price_full' => ($price_full_flag?1:0),
            'default_currency' => $currencies->currencies[DEFAULT_CURRENCY],
            'currencies' => $currencies,
            'isStockUnlimited' => $isStockUnlimited
        ]);
    }

    /**
     * get inventory list - seems used in other inventory-related extensions (attr. inv. images, attr.? q-ty)
     */
    public static function getInventory($products_id, $languages_id) {
        Yii::$app->controller->view->showInventory = true;
        $selectedInventory = [];
        $inventory_query = tep_db_query("select * from " . TABLE_INVENTORY . " where prid = '" . $products_id . "'");
        while ($inventory_data = tep_db_fetch_array($inventory_query)) {
            $arr = preg_split("/[{}]/", $inventory_data['products_id']);
            $label = '';
            for ($i = 1, $n = sizeof($arr); $i < $n; $i = $i + 2) {
                $options_name_data = tep_db_fetch_array(tep_db_query("select products_options_name as name from " . TABLE_PRODUCTS_OPTIONS . " where products_options_id = '" . $arr[$i] . "' and language_id  = '" . (int) $languages_id . "'"));
                $options_values_name_data = tep_db_fetch_array(tep_db_query("select products_options_values_name as name from " . TABLE_PRODUCTS_OPTIONS_VALUES . " where products_options_values_id  = '" . $arr[$i + 1] . "' and language_id  = '" . (int) $languages_id . "'"));
                if ($label == '') {
                    $label = $options_name_data['name'] . ' : ' . $options_values_name_data['name'];
                } else {
                    $label .= ', ' . $options_name_data['name'] . ' : ' . $options_values_name_data['name'];
                }
            }

            $selectedInventory[] = [
                'id' => $inventory_data['inventory_id'],
                'uprid' => $inventory_data['products_id'],
                'label' => $label,
                'model' => $inventory_data['products_model'],
                /*'price_prefix' => $inventory_data['price_prefix'],
                'non_existent' => $inventory_data['non_existent'],
                'inventory_full_price' => $inventory_data['inventory_full_price'],*/
            ];
        }
        Yii::$app->controller->view->selectedInventory = $selectedInventory;
    }

    public static function getProductNewOption($products_id, $attributes) {
        return self::begin()->render('product-new-option.tpl', [
                    'products_id' => $products_id,
                    'attributes' => $attributes,
        ]);

    }

    public static function getProductNewAttribute($products_id, $option, $products_options_id) {
        return self::begin()->render('product-new-attribute.tpl', [
                    'options' => $option,
                    'products_id' => $products_id,
                    'products_options_id' => $products_options_id,
        ]);

    }

    public static function updateStock($prid, $uprid, $q, $warehouse_id = 0, $suppliers_id = 0, $platform_id = 0) {
        if ($warehouse_id == 0) {
            $warehouse_id = \common\helpers\Warehouses::get_default_warehouse();
        }
        if ($suppliers_id == 0) {
            $suppliers_id = \common\helpers\Suppliers::getDefaultSupplierId();
        }
        if ($platform_id == 0) {
            $platform_id = \common\classes\platform::currentId();
        }
        $uprid = InventoryHelper::normalizeInventoryId($uprid);
        $res = tep_db_query("select inventory_id, stock_control from " . TABLE_INVENTORY . " where products_id = '" . tep_db_input($uprid) . "'");
        if ($d = tep_db_fetch_array($res)) {
            tep_db_query("update " . TABLE_INVENTORY . " set products_quantity = products_quantity " . $q . " where inventory_id = '" . tep_db_input($d['inventory_id']) . "'");
            tep_db_query("update " . TABLE_WAREHOUSES_PRODUCTS . " set products_quantity = products_quantity " . $q . " where warehouse_id = '" . (int) $warehouse_id . "' and suppliers_id = '" . (int) $suppliers_id . "' and products_id = '" . tep_db_input($uprid) . "' and prid = '" . (int)\common\helpers\Inventory::get_prid($uprid) . "'");
            if ($d['stock_control'] == 1) {
                tep_db_query("update platform_inventory_control set current_quantity = current_quantity " . $q . " where products_id = '" . tep_db_input($uprid) . "' and platform_id='" . (int)$platform_id . "'");
            }
            if ($d['stock_control'] == 2) {

            }
        } else {
            $r = tep_db_query("select * from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id='" . (int)$prid . "' and pd.products_id=p.products_id and pd.language_id = '".intval(\common\helpers\Language::get_default_language_id())."' and pd.platform_id = '".intval(\common\classes\platform::defaultId())."'");
            if ($uprid != $prid) {
                if ($d = tep_db_fetch_array($r)) {
                    tep_db_query("insert into " . TABLE_INVENTORY . " set products_model='" . tep_db_input($d['products_model']) . "', products_name = '" . tep_db_input($d['products_name']) . "', products_id = '" . tep_db_input($uprid) . "', prid = '" . tep_db_input($prid) . "', products_quantity = '" . $q . "' ");
                }
            }
        }

        $switch_off_stock_ids = \common\classes\StockIndication::productDisableByStockIds();

        if ($uprid != $prid) {
            $inventory_quantity = tep_db_fetch_array(tep_db_query(
                            "SELECT SUM(products_quantity) AS left_quantity " .
                            "FROM " . TABLE_INVENTORY . " i " .
                            "WHERE prid = '" . $prid . "' AND IFNULL(non_existent,0)=0 " . InventoryHelper::get_sql_inventory_restrictions(array('i', 'ip')) . " " .
                            " AND products_quantity>0"
            ));
            if ($inventory_quantity['left_quantity'] < 1 && count($switch_off_stock_ids) > 0) {
                tep_db_query("update " . TABLE_PRODUCTS . " set products_status = IF( stock_indication_id IN ('" . implode("','", $switch_off_stock_ids) . "'), 0, products_status ), products_quantity='" . $inventory_quantity['left_quantity'] . "' where products_id = '" . $prid . "'");
            } else {
                tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity='" . $inventory_quantity['left_quantity'] . "' where products_id = '" . $prid . "'");
            }
        } else {
            tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity = products_quantity  " . $q . " where products_id = '" . $prid . "'");
            tep_db_query("update " . TABLE_WAREHOUSES_PRODUCTS . " set products_quantity = products_quantity  " . $q . " where warehouse_id = '" . (int) $warehouse_id . "' and suppliers_id = '" . (int) $suppliers_id . "' and products_id = '" . (int) $prid . "' and prid = '" . (int) $prid . "'");
            $stock_query = tep_db_query("select products_quantity as max_products_quantity, stock_control from " . TABLE_PRODUCTS . " where products_id = '" . $prid . "'");
            $d = tep_db_fetch_array($stock_query);
            if ($d['stock_control'] == 1) {
                tep_db_query("update platform_stock_control set current_quantity = current_quantity " . $q . " where products_id = '" . $prid . "' and platform_id='" . (int)$platform_id . "'");
            }

            if (($d['max_products_quantity'] < 1) && count($switch_off_stock_ids) > 0) {
                tep_db_query("update " . TABLE_PRODUCTS . " set products_status = 0 where products_id = '" . (int) $prid . "' AND stock_indication_id IN ('" . implode("','", $switch_off_stock_ids) . "')");
            }
        }
        $email_inventory = '';
        $res = tep_db_query("select * from " . TABLE_INVENTORY . " where send_notification=1 and  products_quantity <" . (int) STOCK_REORDER_LEVEL . " order by products_quantity  ");
        while ($d = tep_db_fetch_array($res)) {
            $email_inventory .= $d['products_name'] . ' (' . $d['products_model'] . ') - ' . $d['products_quantity'] . ' ' . "\n";
        }

        tep_db_query("update " . TABLE_INVENTORY . " set send_notification=0 where send_notification=1 and  products_quantity<" . (int) STOCK_REORDER_LEVEL);
        if (strlen(trim($email_inventory)) > 0 && STOCK_CHECK == 'true') {
            \common\helpers\Mail::send(STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, 'Inventory critical quantity notification', nl2br($email_inventory), STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, '');
        }
    }

}
