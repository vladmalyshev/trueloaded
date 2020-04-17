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

use yii\helpers\ArrayHelper;
use common\helpers\Product;
use common\helpers\Inventory as InventoryHelper;
use common\extensions\ProductDesigner\models as ProductDesignerORM;

class Configurator {
    use SqlTrait;
    public static function elements_name($elements_id, $language_id = '') {
        global $languages_id;

        if (!$language_id) {
            $language_id = $languages_id;
        }

        $elements_query = tep_db_query("select elements_name from " . TABLE_ELEMENTS . " where elements_id = '" . (int)$elements_id . "' and language_id = '" . (int)$language_id . "'");
        $elements = tep_db_fetch_array($elements_query);

        return $elements['elements_name'];
    }

    public static function pctemplates_description($pctemplates_id, $language_id = '') {
        global $languages_id;

        if (!$language_id) {
            $language_id = $languages_id;
        }

        $pctemplates_query = tep_db_query("select pctemplates_description from " . TABLE_PCTEMPLATES_INFO . " where pctemplates_id = '" . (int)$pctemplates_id . "' and languages_id = '" . (int)$language_id . "'");
        $pctemplates = tep_db_fetch_array($pctemplates_query);

        return $pctemplates['pctemplates_description'];
    }

    public static function get_pctemplates() {
        $pctemplates_array = array(array('id' => '0', 'text' => TEXT_NONE));
        $pctemplates_query = tep_db_query("select pctemplates_id, pctemplates_name from " . TABLE_PCTEMPLATES . " order by pctemplates_name");
        while ($pctemplates = tep_db_fetch_array($pctemplates_query)) {
            $pctemplates_array[] = array('id' => $pctemplates['pctemplates_id'],
                'text' => $pctemplates['pctemplates_name']);
        }
        return $pctemplates_array;
    }
    
    /**
     * build select options to product designer field
     * @return array
     */
    public static function get_product_designer_templates() {
        $pctemplates_array = array(array('id' => '0', 'text' => TEXT_NONE));
        
        $aProductDesignerTemplates = ProductDesignerORM\ProductDesignerTemplate::find()->all();
        
        foreach($aProductDesignerTemplates as $aProductDesignerTemplate)
        {
            $pctemplates_array[] = [
                'id' => $aProductDesignerTemplate->id,
                'text' => $aProductDesignerTemplate->name
            ];
        }

        return $pctemplates_array;
    }

    public static function getDetails($params, $attributes_details = array()) {
        global $languages_id;
        $customer_groups_id = (int) \Yii::$app->storage->get('customer_groups_id');

        if (!$params['products_id'])
            return '';
        $cart = \Yii::$app->settings->get('cart');
        $currencies = \Yii::$container->get('currencies');
        $configurator_elements = array();
        $configurator_price = $currencies->calculate_price($attributes_details['special_unit_price'] > 0 ? $attributes_details['special_unit_price'] : $attributes_details['product_unit_price'], $attributes_details['tax']);
        $configurator_price_unit = $currencies->calculate_price($attributes_details['special_unit_price'] > 0 ? $attributes_details['special_unit_price'] : $attributes_details['product_unit_price'], 0);

        $product_template_query = tep_db_query("select ppe.pctemplates_id, pti.pctemplates_description, pt.pctemplates_image, count(p.products_id) as count_products from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS . " p1, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PCTEMPLATES_INFO . " pti, " . TABLE_PCTEMPLATES . " pt, " . TABLE_PRODUCTS_TO_PCTEMPLATES_TO_ELEMENTS . " ppe where pt.pctemplates_id = ppe.pctemplates_id and pti.pctemplates_id = ppe.pctemplates_id and pti.languages_id = '" . (int)$languages_id . "' and p1.products_pctemplates_id > 0 and p1.products_pctemplates_id = ppe.pctemplates_id and p.products_id = ppe.products_id and p.products_status = '1' and p.products_id = pd.products_id and pd.language_id = " . (int)$languages_id . " and pd.platform_id = '".intval(\common\classes\platform::defaultId())."' and p1.products_id = '" . (int)$params['products_id'] . "' group by ppe.pctemplates_id having count(p.products_id) > 0");
        if (tep_db_num_rows($product_template_query)) {
            $product_template = tep_db_fetch_array($product_template_query);

            $all_filled_array = array(true);
            $all_mandatory_elements_selected = true;
//            $product_qty_array = array();
            $stock_indicators_ids = array();
            $stock_indicators_array = array();
            if (isset($attributes_details['stock_indicator'])) {
                $stock_indicator_public = $attributes_details['stock_indicator'];
                if ($stock_indicator_public['id'] > 0) {
                    $stock_indicators_ids[] = $stock_indicator_public['id'];
                    $stock_indicators_array[$stock_indicator_public['id']] = $stock_indicator_public;
                }
            }
            $elements_query = tep_db_query("select ppe.elements_id, e.elements_name, e.elements_image, e.elements_type, e.is_mandatory, count(p.products_id) as count_products from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_TO_PCTEMPLATES_TO_ELEMENTS . " ppe, " . TABLE_ELEMENTS . " e where p.products_id = ppe.products_id and p.products_status = '1' and e.elements_id = ppe.elements_id and e.language_id = '" . (int)$languages_id . "' and ppe.pctemplates_id = '" . (int)$product_template['pctemplates_id'] . "' group by ppe.elements_id having count(p.products_id) > 0 order by e.elements_sortorder");
            while ($elements_data = tep_db_fetch_array($elements_query)) {
                if (!file_exists(DIR_WS_IMAGES . $elements_data['elements_image'])) {
                    $elements_data['elements_image'] = '';
                }
                $configurator_elements[$elements_data['elements_id']] = $elements_data;
                $configurator_elements[$elements_data['elements_id']]['elements_qty'] = 1;
                $configurator_elements[$elements_data['elements_id']]['selected_id'] = 0;
                $configurator_elements[$elements_data['elements_id']]['selected_name'] = '';
                $configurator_elements[$elements_data['elements_id']]['selected_price'] = '';
                $configurator_elements[$elements_data['elements_id']]['selected_image'] = '';
                $configurator_elements[$elements_data['elements_id']]['selected_min'] = 1;
                $configurator_elements[$elements_data['elements_id']]['selected_max'] = 1;
                $configurator_elements[$elements_data['elements_id']]['products_array'] = array();
                $default_check = tep_db_fetch_array(tep_db_query("select count(*) as total from " . TABLE_PRODUCTS_TO_PCTEMPLATES_TO_ELEMENTS . " where pctemplates_id = '" . (int)$product_template['pctemplates_id'] . "' and elements_id = '" . (int)$elements_data['elements_id'] . "' and def = 1"));
                if ($default_check['total'] < 1) {
                    $configurator_elements[$elements_data['elements_id']]['products_array'][] = array('id' => 0, 'text' => TEXT_NONE);
                }

                $products_join = '';
                if ( \common\classes\platform::activeId() ) {
                    $products_join .= self::sqlProductsToPlatformCategories();
                }
                $elements_products_query = tep_db_query(
                  "select p.products_id, p.products_model, p.products_image, p.products_price, p.products_tax_class_id, ".
                    "if(length(pd1.products_name), pd1.products_name, pd.products_name) as products_name, ppe.def, ppe.optional, ppe.qty_min, ppe.qty_max ".
                  "from " . TABLE_PRODUCTS_TO_PCTEMPLATES_TO_ELEMENTS . " ppe, " . TABLE_PRODUCTS . " p {$products_join} " .
                  " left join " . TABLE_PRODUCTS_DESCRIPTION . " pd1 on pd1.products_id = p.products_id and pd1.language_id = '" . (int)$languages_id ."' and pd1.platform_id = '" . intval(\Yii::$app->get('platform')->config()->getPlatformToDescription()) . "' ".
                  " left join " . TABLE_PRODUCTS_PRICES . " pgp on p.products_id = pgp.products_id and pgp.groups_id = '" . (int)$customer_groups_id . "' and pgp.currencies_id = '" . (int)(USE_MARKET_PRICES == 'True' ? \Yii::$app->settings->get('currency_id') : 0) . "', "  . TABLE_PRODUCTS_DESCRIPTION . " pd ".
                  "where p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "' and pd.platform_id = '".intval(\common\classes\platform::defaultId())."' ".
                    " and p.products_status = '1' and if(pgp.products_group_price is null, 1, pgp.products_group_price != -1 ) ".
                    " and p.products_id = ppe.products_id and ppe.pctemplates_id = '" . (int)$product_template['pctemplates_id'] . "' and ppe.elements_id = '" . (int)$elements_data['elements_id'] . "'".
                  "group by p.products_id ".
                  "order by ppe.sort_order, products_name"
                );
                while ($elements_products = tep_db_fetch_array($elements_products_query)) {

                    if ($params['elements'][$elements_data['elements_id']] == $elements_products['products_id']) {
                        $configurator_elements[$elements_data['elements_id']]['selected_id'] = $elements_products['products_id'];
                        if ($params['elements_qty'][$elements_data['elements_id']] > 0) {
                            $configurator_elements[$elements_data['elements_id']]['elements_qty'] = $params['elements_qty'][$elements_data['elements_id']];
                        }
                    } elseif (strpos($params['products_id'], $elements_data['elements_id'] . '|' . $elements_products['products_id']) !== false) {
                        $configurator_elements[$elements_data['elements_id']]['selected_id'] = $elements_products['products_id'];
                        list($prid,) = explode('{tpl}', $params['products_id']);
                        if ($cart->contents[$params['products_id']]['qty'] > 0) {
                            preg_match("/" . $elements_data['elements_id'] . '\|(' . $elements_products['products_id'] . ".*?\{sub\}" . preg_quote($prid) . ")\|/i", $params['products_id'], $regs);
                            foreach ($cart->contents as $prod_id => $val) {
                                if ($regs[1] == substr($prod_id, 0, strlen($regs[1])) && $cart->contents[$prod_id]['parent'] == $params['products_id']) {
                                    $configurator_elements[$elements_data['elements_id']]['elements_qty'] = $cart->contents[$prod_id]['qty'];// / $cart->contents[$params['products_id']]['qty'];
                                }
                            }
                        }
                    } elseif ($params['elements'][$elements_data['elements_id']] == 0 && $configurator_elements[$elements_data['elements_id']]['selected_id'] == 0 && $elements_products['def']) {
                        $configurator_elements[$elements_data['elements_id']]['selected_id'] = $elements_products['products_id'];
                    }

                    if ($configurator_elements[$elements_data['elements_id']]['selected_id'] == $elements_products['products_id']) {
// {{
                        $products_id = $elements_products['products_id'];
                        if (is_array($params['elements_attr'][$elements_data['elements_id']][$products_id])) {
                            $attributes = $params['elements_attr'][$elements_data['elements_id']][$products_id];
                        } else {
                            $attributes = array();
                        }

                        $product_query = tep_db_query("select products_id, products_price, products_tax_class_id, products_quantity, products_price_full from " . TABLE_PRODUCTS . " where products_id = '" . (int)$products_id . "'");
                        if ($product = tep_db_fetch_array($product_query)) {
                            $configurator_koeff = 1;
                            
                            $uprid = InventoryHelper::normalize_id(InventoryHelper::get_uprid($product['products_id'], $attributes));
                            
                            $priceInstance = \common\models\Product\ConfiguratorPrice::getInstance($uprid);
                            
                            //$product_price = self::get_products_price_configurator($uprid, $configurator_elements[$elements_data['elements_id']]['elements_qty']); //price in configurator                            
                            $product_price = $priceInstance->getConfiguratorPrice($params);
                            
                            //$product_price = $priceInstance->getInventoryPrice($params);//full price with inventory
                            $special_price = $priceInstance->getConfiguratorSpecialPrice($params);
                            if ($special_price !== false){
                                $product_price = $special_price;
                            }
                            
                            /*if (($ext = \common\helpers\Acl::checkExtension('Inventory', 'getDetails')) && PRODUCTS_INVENTORY == 'True' && $attributes) {
                                $uprid = InventoryHelper::normalize_id(InventoryHelper::get_uprid($product['products_id'], $attributes));
                                $priceInstance = \common\models\Product\Price::getInstance($uprid);
                                $product_price = $priceInstance->getInventoryPrice($params);
                                $special_price = $priceInstance->getInventorySpecialPrice($params);
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
                                    $check_inventory['inventory_price'] = InventoryHelper::get_inventory_price_by_uprid($mask, $configurator_elements[$elements_data['elements_id']]['elements_qty'], $check_inventory['inventory_price']);
                                    $check_inventory['inventory_full_price'] = InventoryHelper::get_inventory_full_price_by_uprid($mask, $configurator_elements[$elements_data['elements_id']]['elements_qty'], $check_inventory['inventory_full_price']);
                                    if ($product['products_price_full'] && $check_inventory['inventory_full_price'] != -1) {
                                        $product_price = $check_inventory['inventory_full_price'] * $configurator_koeff;
                                        if ($special_price !== false) {
                                            // if special - add difference
                                            $special_price += $product_price - $product_price_old;
                                        }
                                    } elseif ($check_inventory['inventory_price'] != -1) {
                                        $product_price += $check_inventory['inventory_price'] * $configurator_koeff;
                                        if ($special_price !== false) {
                                            $special_price += $check_inventory['inventory_price'] * $configurator_koeff;
                                        }
                                    }
                                }
                            } else if ($attributes) {
                                foreach ($attributes as $opt_id => $val_id) {
                                    $attribute_price_query = tep_db_query("select products_attributes_id, options_values_price, price_prefix from " . TABLE_PRODUCTS_ATTRIBUTES . " where products_id = '" . (int) $product['products_id'] . "' and options_id = '" . (int) $opt_id . "' and options_values_id = '" . (int) $val_id . "'");
                                    $attribute_price = tep_db_fetch_array($attribute_price_query);
                                    $attribute_price['options_values_price'] = \common\helpers\Attributes::get_options_values_price($attribute_price['products_attributes_id'], 1);
                                    if ($attribute_price['price_prefix'] == '+' || $attribute_price['price_prefix'] == '') {
                                        $product_price += $attribute_price['options_values_price'] * $configurator_koeff;
                                        if ($special_price !== false) {
                                            $special_price += $attribute_price['options_values_price'] * $configurator_koeff;
                                        }
                                    } else {
                                        $product_price -= $attribute_price['options_values_price'] * $configurator_koeff;
                                        if ($special_price !== false) {
                                            $special_price -= $attribute_price['options_values_price'] * $configurator_koeff;
                                        }
                                    }
                                }                                
                            }*/
                            /*if ( ($regular_price = Product::get_products_price($product['products_id'], $configurator_elements[$elements_data['elements_id']]['elements_qty'])) > 0) {
                                $configurator_koeff = $product_price / $regular_price;
                            }*/
                            //$configurator_koeff = $configurator_product_price / $product_price;
                            
                            //$special_price = Product::get_products_special_price($product['products_id'], $configurator_elements[$elements_data['elements_id']]['elements_qty']);
                            //$product_price_old = $product_price;

                            $actual_product_price = $product_price;//selected configurator price

                            $products_options_name_query = tep_db_query("select distinct p.products_id, p.products_tax_class_id, popt.products_options_id, popt.products_options_name from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_ATTRIBUTES . " patrib where p.products_id = '" . (int) $products_id . "' and patrib.products_id = p.products_id and patrib.options_id = popt.products_options_id and popt.language_id = '" . (int) $languages_id . "' order by popt.products_options_sort_order, popt.products_options_name");
                            while ($products_options_name = tep_db_fetch_array($products_options_name_query)) {
                                if (!isset($attributes[$products_options_name['products_options_id']])) {
                                    $check = tep_db_fetch_array(tep_db_query("select max(pov.products_options_values_id) as values_id, count(pov.products_options_values_id) as values_count from " . TABLE_PRODUCTS_ATTRIBUTES . " pa, " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov where pa.products_id = '" . (int) $products_id . "' and pa.options_id = '" . (int) $products_options_name['products_options_id'] . "' and pa.options_values_id = pov.products_options_values_id and pov.language_id = '" . (int) $languages_id . "'"));
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
                                foreach ($attributes as $value) {
                                    $all_filled = $all_filled && (bool)$value;
                                }
*/

                            $attributes_array = array();
                            tep_db_data_seek($products_options_name_query, 0);
                            while ($products_options_name = tep_db_fetch_array($products_options_name_query)) {
                                $selected_attribute = false;
                                $products_options_array = array();
                                $products_options_query = tep_db_query(
                                        "select pa.products_attributes_id, pov.products_options_values_id, pov.products_options_values_name, pa.options_values_price, pa.price_prefix " .
                                        "from " . TABLE_PRODUCTS_ATTRIBUTES . " pa, " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov " .
                                        "where pa.products_id = '" . (int) $products_id . "' and pa.options_id = '" . (int) $products_options_name['products_options_id'] . "' and pa.options_values_id = pov.products_options_values_id and pov.language_id = '" . (int) $languages_id . "' " .
                                        "order by pa.products_options_sort_order, pov.products_options_values_sort_order, pov.products_options_values_name");
                                while ($products_options = tep_db_fetch_array($products_options_query)) {
                                    if (($ext = \common\helpers\Acl::checkExtension('Inventory', 'getDetails')) && PRODUCTS_INVENTORY == 'True' && !\common\helpers\Inventory::disabledOnProduct($products_id)) {
                                        $comb_arr = $attributes;
                                        $comb_arr[$products_options_name['products_options_id']] = $products_options['products_options_values_id'];
                                        foreach ($comb_arr as $opt_id => $val_id) {
                                            if (!($val_id > 0)) {
                                                $comb_arr[$opt_id] = '0000000';
                                            }
                                        }
                                        reset($comb_arr);
                                        $mask = str_replace('0000000', '%', InventoryHelper::normalizeInventoryId(InventoryHelper::get_uprid($products_id, $comb_arr), $vids, $virtual_vids));
                                        $check_inventory = tep_db_fetch_array(tep_db_query(
                                                        "select inventory_id, max(products_quantity) as products_quantity, stock_indication_id, stock_delivery_terms_id " .
                                                        //" min(if(price_prefix = '-', -inventory_price, inventory_price)) as inventory_price, min(inventory_full_price) as inventory_full_price " .
                                                        "from " . TABLE_INVENTORY . " i " .
                                                        "where products_id like '" . tep_db_input($mask) . "' and non_existent = '0' " . InventoryHelper::get_sql_inventory_restrictions(array('i', 'ip')) . " " .
                                                        "order by products_quantity desc " .
                                                        "limit 1"
                                        ));
                                        if (!$check_inventory['inventory_id'])
                                            continue;
                                        $priceInstance = \common\models\Product\ConfiguratorPrice::getInstance($mask);
                                        $product_price = $priceInstance->getConfiguratorPrice($params);//other inventoryPrice
                                        
                                        /*
                                        $check_inventory['inventory_price'] = InventoryHelper::get_inventory_price_by_uprid($mask, $configurator_elements[$elements_data['elements_id']]['elements_qty'], $check_inventory['inventory_price']);
                                        $check_inventory['inventory_full_price'] = InventoryHelper::get_inventory_full_price_by_uprid($mask, $configurator_elements[$elements_data['elements_id']]['elements_qty'], $check_inventory['inventory_full_price']);
                                         */
                                        if ($priceInstance->calculate_full_price && $product_price == -1) {
                                            continue; // Disabled for specific group
                                        } elseif ($product_price == -1) {
                                            continue; // Disabled for specific group
                                        }

                                        if ($virtual_vids) {
                                            $virtual_attribute_price = \common\helpers\Attributes::get_virtual_attribute_price($products_id, $virtual_vids, $configurator_elements[$elements_data['elements_id']]['elements_qty'], $product_price);
                                            if ($virtual_attribute_price === false) {
                                                continue; // Disabled for specific group
                                            } else {
                                                if ( ($regular_price = Product::get_products_price($products_id, $configurator_elements[$elements_data['elements_id']]['elements_qty'])) > 0) {
                                                    $configurator_koeff = self::get_products_price_configurator($products_id, $configurator_elements[$elements_data['elements_id']]['elements_qty']) / $regular_price;
                                                }
                                                $product_price += $virtual_attribute_price * $configurator_koeff;
                                            }
                                        }

                                        $products_options_array[] = array('id' => $products_options['products_options_values_id'], 'text' => $products_options['products_options_values_name'], 'price_diff' => 0);
                                        /*
                                        if ($product['products_price_full']) {
                                            $price_diff = $check_inventory['inventory_full_price'] * $configurator_koeff - $actual_product_price;
                                        } else {
                                            $price_diff = $product_price_old + $check_inventory['inventory_price'] * $configurator_koeff - $actual_product_price;
                                        }
                                         */
                                        
                                        $price_diff = ($product_price - $actual_product_price);
                                        if (abs($price_diff) > 0.005) {
                                            $products_options_array[sizeof($products_options_array) - 1]['text'] .= ' (' . ($price_diff < 0 ? '-' : '+') . $currencies->display_price(abs($price_diff), \common\helpers\Tax::get_tax_rate($products_options_name['products_tax_class_id']), 1, false) . ') ';
                                        }
                                        $products_options_array[sizeof($products_options_array) - 1]['price_diff'] = $price_diff;

                                        $stock_indicator = \common\classes\StockIndication::product_info(array(
                                                    'products_id' => $check_inventory['products_id'],
                                                    'products_quantity' => ($check_inventory['inventory_id'] ? $check_inventory['products_quantity'] : '0'),
                                                    'stock_indication_id' => (isset($check_inventory['stock_indication_id']) ? $check_inventory['stock_indication_id'] : null),
                                                    'stock_delivery_terms_id' => (isset($check_inventory['stock_delivery_terms_id']) ? $check_inventory['stock_delivery_terms_id'] : null),
                                        ));

                                        if (!($check_inventory['products_quantity'] > 0)) {
                                            $products_options_array[sizeof($products_options_array) - 1]['params'] = ' class="outstock" data-max-qty="' . (int) $stock_indicator['max_qty'] . '"';
                                            $products_options_array[sizeof($products_options_array) - 1]['text'] .= ' - ' . strip_tags($stock_indicator['stock_indicator_text_short']);
                                        } else {
                                            $products_options_array[sizeof($products_options_array) - 1]['params'] = ' class="outstock" data-max-qty="' . (int) $stock_indicator['max_qty'] . '"';
                                        }
                                    } else {
                                        $products_options['options_values_price'] = \common\helpers\Attributes::get_options_values_price($products_options['products_attributes_id']);
                                        $products_options_array[] = array('id' => $products_options['products_options_values_id'], 'text' => $products_options['products_options_values_name']);
                                        if ($products_options['options_values_price'] != '0') {
                                            if ( strpos($products_options['price_prefix'],'%')!==false ) {
                                                $products_options_array[sizeof($products_options_array) - 1]['text'] .= ' (' . substr($products_options['price_prefix'],0,1).''.\common\helpers\Output::percent($products_options['options_values_price']) . ') ';
                                            }else {
                                                $products_options_array[sizeof($products_options_array) - 1]['text'] .= ' (' . $products_options['price_prefix'] . $currencies->display_price($products_options['options_values_price'] * $configurator_koeff, \common\helpers\Tax::get_tax_rate($products_options_name['products_tax_class_id']), 1, false) . ') ';
                                            }
                                        }
                                    }

                                    if ($attributes[$products_options_name['products_options_id']] > 0) {
                                        $selected_attribute = $attributes[$products_options_name['products_options_id']];
                                    } elseif (($selected_element_pos = strpos($params['products_id'], $elements_data['elements_id'] . '|' . $elements_products['products_id'])) !== false) {
                                        if (strpos(substr($params['products_id'], $selected_element_pos, strpos($params['products_id'], '{sub}', $selected_element_pos) - $selected_element_pos),  '{' . $products_options_name['products_options_id'] . '}' . $products_options['products_options_values_id']) !== false) {
                                            $selected_attribute = $products_options['products_options_values_id'];
                                        }
                                    }
                                }

                                if (count($products_options_array) > 0) {
                                    $all_filled = $all_filled && (bool) $attributes[$products_options_name['products_options_id']];
                                    $attributes_array[] = array(
                                        'title' => htmlspecialchars($products_options_name['products_options_name']),
                                        'id' => '[elements_attr][' . $elements_data['elements_id'] . '][' . $products_id . '][' . $products_options_name['products_options_id'] . ']',
                                        'name' => 'elements_attr[' . $elements_data['elements_id'] . '][' . $products_id . '][' . $products_options_name['products_options_id'] . ']',
                                        'options' => $products_options_array,
                                        'selected' => $selected_attribute,
                                    );
                                }
                            }

                            $product_query = tep_db_query("select products_id, products_price, products_tax_class_id, stock_indication_id, stock_delivery_terms_id, products_quantity from " . TABLE_PRODUCTS . " where products_id = '" . (int) $products_id . "'");
                            $_backup_products_quantity = 0;
                            $_backup_stock_indication_id = $_backup_stock_delivery_terms_id = 0;
                            if ($product = tep_db_fetch_array($product_query)) {
                                $_backup_products_quantity = $product['products_quantity'];
                                $_backup_stock_indication_id = $product['stock_indication_id'];
                                $_backup_stock_delivery_terms_id = $product['stock_delivery_terms_id'];
                            }
                            $current_uprid = InventoryHelper::normalize_id(InventoryHelper::get_uprid($products_id, $attributes));
                            $configurator_elements[$elements_data['elements_id']]['selected_uprid'] = $current_uprid;

                            if (($ext = \common\helpers\Acl::checkExtension('Inventory', 'getDetails')) && PRODUCTS_INVENTORY == 'True' && !\common\helpers\Inventory::disabledOnProduct($current_uprid)) {
                                $check_inventory = tep_db_fetch_array(tep_db_query(
                                                "select inventory_id, products_quantity, stock_indication_id, stock_delivery_terms_id " .
                                                "from " . TABLE_INVENTORY . " " .
                                                "where products_id like '" . tep_db_input(InventoryHelper::normalizeInventoryId($current_uprid)) . "' " .
                                                "limit 1"
                                ));

                                $stock_indicator = \common\classes\StockIndication::product_info(array(
                                            'products_id' => $current_uprid,
                                            'products_quantity' => ($check_inventory['inventory_id'] ? $check_inventory['products_quantity'] : $_backup_products_quantity),
                                            'stock_indication_id' => (isset($check_inventory['stock_indication_id']) ? $check_inventory['stock_indication_id'] : $_backup_stock_indication_id),
                                            'stock_delivery_terms_id' => (isset($check_inventory['stock_delivery_terms_id']) ? $check_inventory['stock_delivery_terms_id'] : $_backup_stock_delivery_terms_id),
                                ));
                            } else {
                                $stock_indicator = \common\classes\StockIndication::product_info(array(
                                            'products_id' => $products_id,
                                            'products_quantity' => $_backup_products_quantity,
                                            'stock_indication_id' => $_backup_stock_indication_id,
                                            'stock_delivery_terms_id' => $_backup_stock_delivery_terms_id,
                                ));
                            }
                            $stock_indicator_public = $stock_indicator['flags'];
                            $stock_indicator_public['id'] = $stock_indicator['id'];
                            $stock_indicator_public['quantity_max'] = Product::filter_product_order_quantity($current_uprid, $stock_indicator['max_qty'], true);
                            $stock_indicator_public['stock_code'] = $stock_indicator['stock_code'];
                            $stock_indicator_public['text_stock_code'] = $stock_indicator['text_stock_code'];
                            $stock_indicator_public['stock_indicator_text'] = $stock_indicator['stock_indicator_text'];
                            if ($stock_indicator_public['request_for_quote']) {
                                $special_price = false;
                            }
                            $configurator_elements[$elements_data['elements_id']]['selected_stock_indicator'] = $stock_indicator_public;

                            if ($stock_indicator['id'] > 0) {
                                $stock_indicators_ids[] = $stock_indicator['id'];
                                $stock_indicators_array[$stock_indicator['id']] = $stock_indicator_public;
                            }

                            $configurator_elements[$elements_data['elements_id']]['all_filled'] = $all_filled;
                            $configurator_elements[$elements_data['elements_id']]['product_qty'] = ($check_inventory['inventory_id'] ? $check_inventory['products_quantity'] : ($product['products_quantity'] ? $product['products_quantity'] : '0'));

                            $all_filled_array[] = $configurator_elements[$elements_data['elements_id']]['all_filled'];

                            /*
                              $product_qty_array[] = floor($bundle_sets['num_product'] > 0 ? $bundle_sets['product_qty'] / $bundle_sets['num_product'] : $bundle_sets['product_qty']);
                              $quantity_max_array[] = floor($bundle_sets['num_product'] > 0 ? $stock_indicator_public['quantity_max'] / $bundle_sets['num_product'] : $stock_indicator_public['quantity_max']);
                             */
                            $configurator_elements[$elements_data['elements_id']]['attributes_array'] = $attributes_array;
    // }}
                            $configurator_elements[$elements_data['elements_id']]['selected_name'] = $elements_products['products_name'];
                            if ($special_price !== false) {
                                $configurator_elements[$elements_data['elements_id']]['selected_price_old'] = $currencies->display_price($actual_product_price, \common\helpers\Tax::get_tax_rate($elements_products['products_tax_class_id']));
                                $configurator_elements[$elements_data['elements_id']]['selected_price_special'] = $currencies->display_price($special_price, \common\helpers\Tax::get_tax_rate($elements_products['products_tax_class_id']));
                                $configurator_elements[$elements_data['elements_id']]['selected_actual_price'] = $special_price;
                                $configurator_price += $currencies->display_price_clear($special_price, \common\helpers\Tax::get_tax_rate($elements_products['products_tax_class_id']), $configurator_elements[$elements_data['elements_id']]['elements_qty']);
                                $configurator_price_unit += $currencies->display_price_clear($special_price, 0, $configurator_elements[$elements_data['elements_id']]['elements_qty']);
                            } else {
                                $configurator_elements[$elements_data['elements_id']]['selected_price'] = $currencies->display_price($actual_product_price, \common\helpers\Tax::get_tax_rate($elements_products['products_tax_class_id']));
                                $configurator_elements[$elements_data['elements_id']]['selected_actual_price'] = $actual_product_price;
                                $configurator_price += $currencies->display_price_clear($actual_product_price, \common\helpers\Tax::get_tax_rate($elements_products['products_tax_class_id']), $configurator_elements[$elements_data['elements_id']]['elements_qty']);
                                $configurator_price_unit += $currencies->display_price_clear($actual_product_price, 0, $configurator_elements[$elements_data['elements_id']]['elements_qty']);
                            }
                            $configurator_elements[$elements_data['elements_id']]['selected_image'] = \common\classes\Images::getImageUrl($elements_products['products_id']);
                            $configurator_elements[$elements_data['elements_id']]['selected_link'] = (\frontend\design\Info::isTotallyAdmin()?'':tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $elements_products['products_id']));
                            $configurator_elements[$elements_data['elements_id']]['selected_min'] = $elements_products['qty_min'];
                            $configurator_elements[$elements_data['elements_id']]['selected_max'] = ($elements_products['qty_max'] > 0 ? min($elements_products['qty_max'], $stock_indicator_public['quantity_max']) : $stock_indicator_public['quantity_max']);
                            $configurator_elements[$elements_data['elements_id']]['products_tax_class_id'] = $elements_products['products_tax_class_id'];
                        }                        
                    } else {
                        $priceInstance = \common\models\Product\ConfiguratorPrice::getInstance($elements_products['products_id']);
                        $actual_product_price = $priceInstance->getConfiguratorPrice(['qty' => 1]);
                        $special_price = $priceInstance->getConfiguratorSpecialPrice(['qty' => 1]);
                        if ($special_price !== false){
                            $actual_product_price = $special_price;
                        }
                        /*$actual_product_price = Product::get_products_price($elements_products['products_id'], 1, $elements_products['products_price']);
                        $special_price = Product::get_products_special_price($elements_products['products_id'], 1);
                        if ($special_price !== false && $special_price < $product_price) {
                            $actual_product_price = $special_price;
                        }*/
                    }
                    $configurator_elements[$elements_data['elements_id']]['products_array'][] = array('id' => $elements_products['products_id'], 'text' => $elements_products['products_name'] . ' (' . $currencies->display_price($actual_product_price, \common\helpers\Tax::get_tax_rate($elements_products['products_tax_class_id']), 1, false) . ')');
                }

                if ($elements_data['is_mandatory'] && !($configurator_elements[$elements_data['elements_id']]['selected_id'] > 0)) {
                    $all_mandatory_elements_selected = false;
                }
            }

            if (count($stock_indicators_ids) > 0) {
                $stock_indicators_sorted = \common\classes\StockIndication::sortStockIndicators($stock_indicators_ids);
                $configurator_stock_indicator = $stock_indicators_array[$stock_indicators_sorted[count($stock_indicators_sorted)-1]];
            } else {
                $configurator_stock_indicator = $stock_indicator_public;
            }

            $return_data = [
                'pctemplates_id' => $product_template['pctemplates_id'],
                'product_valid' => (min($all_filled_array) && $all_mandatory_elements_selected && (isset($attributes_details['product_valid']) ? $attributes_details['product_valid'] : true) ? '1' : '0'),
                'product_price' => $attributes_details['product_price'],
                'special_price' => $attributes_details['special_price'],
//                'product_qty' => min($product_qty_array),
                'configurator_elements' => $configurator_elements,
                'stock_indicator' => $configurator_stock_indicator,
                'configurator_price' => $currencies->format($configurator_price, false),
                'configurator_price_unit' => $configurator_price_unit,
            ];
            return $return_data;
        }
    }

    public static function get_products_price_configurator($products_id, $qty = 1) {
        $configuratorInstance = \common\models\Product\ConfiguratorPrice::getInstance($products_id);
        return $configuratorInstance->getConfiguratorPrice([
            'qty' => $qty,
        ]);
    }

}
