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

use Yii;

class MenuHelper {

    public static function getUrlByLinkId($link_id, $link_type) {
        switch ($link_type) {
            case 'default':
                if ($link_id == '8888886') {
                    return tep_href_link('/');
                } elseif ($link_id == '8888885') {
                    return tep_href_link('contact/index');
                } elseif ($link_id == '8888887') {
                    if (!Yii::$app->user->isGuest) {
                        return tep_href_link('account/logoff', '', 'SSL');
                    } else {
                        return tep_href_link('account/login', '', 'SSL');
                    }
                } elseif ($link_id == '8888888') {
                    if (!Yii::$app->user->isGuest) {
                        return tep_href_link('account/index', '', 'SSL');
                    } else {
                        return tep_href_link('account/create', '', 'SSL');
                    }
                } elseif ($link_id == '8888884') {
                    return tep_href_link('checkout/index', '', 'SSL');
                } elseif ($link_id == '8888883') {
                    return tep_href_link('shopping-cart/index');
                } elseif ($link_id == '8888882') {
                    return tep_href_link('catalog/products-new');
                } elseif ($link_id == '8888881') {
                    return tep_href_link('catalog/featured-products');
                } elseif ($link_id == '8888880') {
                    return tep_href_link('catalog/sales');
                } elseif ($link_id == '8888879') {
                    return tep_href_link('catalog/gift-card');
                } elseif ($link_id == '8888878') {
                    return tep_href_link('catalog/all-products');
                }  elseif ($link_id == '8888877') {
                    return tep_href_link('sitemap');
                } elseif ($link_id == '8888876') {
                    return tep_href_link('promotions');
                } elseif ($link_id == '8888875') {
                    return tep_href_link('wedding-registry');
                } elseif ($link_id == '8888874') {
                    return tep_href_link('wedding-registry/manage');
                } elseif ($link_id == '8888873') {
                    return tep_href_link('quick-order');
                }
                break;
            case 'custom':
                $link = tep_db_fetch_array(tep_db_query("select link from " . TABLE_MENU_ITEMS . " where platform_id = '" . \common\classes\platform::currentId() . "' and link_id = '" . (int) $link_id . "'"));
                if ($link) {
                    return tep_href_link($link['link']);
                }
                break;
        }
        return false;
    }
    
    public static function getAllCustomPages($platform_id){
        $cusom_pages_query = tep_db_query("select ts.id, ts.setting_value from " . TABLE_THEMES_SETTINGS . " ts left join " . TABLE_THEMES . " t on ts.theme_name = t.theme_name inner join " . TABLE_PLATFORMS_TO_THEMES . " pt on pt.is_default = 1 and pt.theme_id = t.id where pt.platform_id = '" . (int)$platform_id . "' and ts.setting_group = 'added_page' and ts.setting_name='custom' order by ts.setting_value");
        $custom_pages = [];
        if (tep_db_num_rows($cusom_pages_query)){
            while($custom = tep_db_fetch_array($cusom_pages_query)){
                $custom_pages[$custom['id']] = $custom['setting_value'];
            }
        }
        return $custom_pages;
    }

    public static function getBrandsList() {

        $manufacturers_query = tep_db_query("select manufacturers_id, manufacturers_name, manufacturers_image from " . TABLE_MANUFACTURERS ." order by manufacturers_name asc");

        $brands = [];
        while ($item = tep_db_fetch_array($manufacturers_query)) {
            $brands[$item['manufacturers_id']] = $item;
        }

        return $brands;
    }

    public static function importAdminTree($data, $parent = 0) {
        foreach ($data->item as $item) {
            $object = new \common\models\AdminBoxes();
            $object->parent_id = $parent;
            $object->sort_order = (int)$item->sort_order;
            $object->box_type = (int)$item->box_type;
            $object->acl_check = (string)$item->acl_check;
            $object->config_check = (string)$item->config_check;
            $object->path = (string)$item->path;
            $object->title = (string)$item->title;
            $object->filename = (string)$item->filename;
            $object->save();
            //--- update acl
            $acl = \common\models\AccessControlList::findOne(['access_control_list_key' => $object->title]);
            if (!is_object($acl)) {
                $acl = new \common\models\AccessControlList();
                $acl->access_control_list_key = $object->title;
            }
            if ($parent == 0) {
                $acl->parent_id = $parent;
            } else {
                $parentObject = \common\models\AdminBoxes::findOne($parent);
                if (is_object($parentObject)) {
                    $parentAcl = \common\models\AccessControlList::findOne(['access_control_list_key' => $parentObject->title]);
                    if (is_object($parentAcl)) {
                        $acl->parent_id = $parentAcl->access_control_list_id;
                    }
                }
            }
            $acl->sort_order = $object->sort_order;
            $acl->save();
            //--- update acl
            if (isset($item->child) && (int)$item->box_type == 1) {
                self::importAdminTree($item->child, $object->box_id);
            }
        }
    }
}
