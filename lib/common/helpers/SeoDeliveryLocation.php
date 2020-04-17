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


use common\classes\Images;

class SeoDeliveryLocation
{

    static public function imagesLocation()
    {
        return 'delivery-location/';
    }

    static public function getId($platformId, $name, $parentId=false)
    {
        $id = false;

        $getId_r = tep_db_query(
            "SELECT fd.id ".
            "FROM ".TABLE_SEO_DELIVERY_LOCATION." fd ".
            " INNER JOIN ".TABLE_SEO_DELIVERY_LOCATION_TEXT." fdt ON fdt.id=fd.id ".
            "WHERE fd.platform_id='".(int)$platformId."' AND fdt.location_name='".tep_db_input($name)."' ".
            ($parentId!==false?" AND fd.parent_id='".(int)$parentId."' ":'')
        );
        if ( tep_db_num_rows($getId_r)>0 ) {
            $getId = tep_db_fetch_array($getId_r);
            $id = $getId['id'];
        }
        return $id;
    }

    static public function getIdBySeoName($platformId, $name, $parentId=false)
    {
        if ( is_array($name) ) {
            $walk_id = 0;
            foreach ( $name as $locationPathName ){
                $walk_id = static::getIdBySeoName($platformId, $locationPathName, $walk_id);
                if ( $walk_id==false ) break;
            }
            if ( false && empty($walk_id) ) {
                $name = $name[count($name)-1];
            }else{
                return $walk_id;
            }
        }
        $id = false;

        $getId_r = tep_db_query(
            "SELECT fd.id ".
            "FROM ".TABLE_SEO_DELIVERY_LOCATION." fd ".
            " INNER JOIN ".TABLE_SEO_DELIVERY_LOCATION_TEXT." fdt ON fdt.id=fd.id ".
            "WHERE fd.platform_id='".(int)$platformId."' AND IF(LENGTH(fdt.seo_page_name)>0,fdt.seo_page_name,fdt.location_name)='".tep_db_input($name)."' ".
            ($parentId!==false?" AND fd.parent_id='".(int)$parentId."' ":'')
        );
        if ( tep_db_num_rows($getId_r)>0 ) {
            $getId = tep_db_fetch_array($getId_r);
            $id = $getId['id'];
        }
        return $id;
    }

    static public function getParents($platformId, $id, $withSelf=true)
    {
        global $languages_id;
        $leafInfo = [];
        do {
            $getId_r = tep_db_query(
                "SELECT fd.id, fd.parent_id, IF(LENGTH(fdt.location_name)>0,fdt.location_name,fdtd.location_name) AS location_name " .
                "FROM " . TABLE_SEO_DELIVERY_LOCATION . " fd " .
                " INNER JOIN " . TABLE_SEO_DELIVERY_LOCATION_TEXT . " fdt ON fdt.id=fd.id AND fdt.language_id='" . (int)$languages_id . "' " .
                " LEFT JOIN " . TABLE_SEO_DELIVERY_LOCATION_TEXT . " fdtd ON fdtd.id=fd.id AND fdtd.language_id='" . intval(\common\helpers\Language::get_default_language_id()) . "' " .
                "WHERE fd.platform_id='" . (int)$platformId . "' ".
                " AND fd.id='{$id}'"
            );
            if (tep_db_num_rows($getId_r) > 0) {
                $getId = tep_db_fetch_array($getId_r);
                $leafInfo[] = $getId;
                $id = $getId['parent_id'];
            }else{
                break;
            }
        }while(true);
        $leafInfo = array_reverse($leafInfo);
        if ( !$withSelf ) {
            unset($leafInfo[count($leafInfo)-1]);
        }
        return $leafInfo;
    }

    public static function getItem($id, $platformId=false, $queryCondition='', $allowDefaultLanguage=false)
    {
        global $languages_id;

        $getId_r = tep_db_query(
            "SELECT fd.show_product_group_id, fd.old_seo_page_name, ".
            " fd.id, fd.platform_id, ".
            " fd.featured, fd.show_on_index, ".
            " fd.product_set_rule, ".
            " fd.image_headline, fd.image_listing, ".
            " fd.status, fd.date_added, fd.date_modified, fd.parent_id, " .
            " IF(LENGTH(fdt.location_name)>0, fdt.location_name, fdtd.location_name) AS location_name, ".
            " IF(LENGTH(fdt.location_description)>0, fdt.location_description, fdtd.location_description) AS location_description, ".
            " IF(LENGTH(fdt.location_description_long)>0, fdt.location_description_long, fdtd.location_description_long) AS location_description_long, ".
            " IF(LENGTH(fdt.meta_title)>0, fdt.meta_title, fdtd.meta_title) AS meta_title, ".
            " IF(LENGTH(fdt.meta_keyword)>0, fdt.meta_keyword, fdtd.meta_keyword) AS meta_keyword, ".
            " IF(LENGTH(fdt.meta_description)>0, fdt.meta_description, fdtd.meta_description) AS meta_description, ".
            " IF(LENGTH(fdt.seo_page_name)>0, fdt.seo_page_name, fdtd.seo_page_name) AS seo_page_name, ".
            " IF(LENGTH(fdt.location_description_short)>0, fdt.location_description_short, fdtd.location_description_short) AS location_description_short, ".
            " IF(LENGTH(fdt.meta_title)>0, fdt.overwrite_head_title_tag, fdtd.overwrite_head_title_tag) AS overwrite_head_title_tag, ".
            " IF(LENGTH(fdt.meta_description)>0, fdt.overwrite_head_desc_tag, fdtd.overwrite_head_desc_tag) AS overwrite_head_desc_tag, ".
            " fdt.language_id ".
            "FROM " . TABLE_SEO_DELIVERY_LOCATION . " fd " .
            " INNER JOIN " . TABLE_SEO_DELIVERY_LOCATION_TEXT . " fdt ON fdt.id=fd.id AND fdt.language_id='" . (int)$languages_id . "' " .
            " LEFT JOIN " . TABLE_SEO_DELIVERY_LOCATION_TEXT . " fdtd ON fdtd.id=fd.id AND fdtd.language_id='" . ($allowDefaultLanguage?intval(\common\helpers\Language::get_default_language_id()):(int)$languages_id) . "' " .
            "WHERE 1 ".
            " AND fd.id='{$id}' ".
            ($platformId!==false?"AND fd.platform_id='" . (int)$platformId . "'":'').
            " {$queryCondition} "
        );
        $getId = false;
        if (tep_db_num_rows($getId_r) > 0) {
            $getId = tep_db_fetch_array($getId_r);
            if ( !empty($getId['image_headline']) && is_file(Images::getFSCatalogImagesPath().self::imagesLocation().$getId['image_headline']) ) {
                $getId['image_headline_src'] = \Yii::getAlias('@webCatalogImages/'.self::imagesLocation().$getId['image_headline']);
                $getId['image_headline_src_admin'] = \Yii::getAlias(self::imagesLocation().$getId['image_headline']);
                $_tmp = getimagesize(Images::getFSCatalogImagesPath().self::imagesLocation().$getId['image_headline']);
                $getId['image_headline_width'] = $_tmp[0];
                $getId['image_headline_height'] = $_tmp[1];
            }
            if ( !empty($getId['image_listing']) && is_file(Images::getFSCatalogImagesPath().self::imagesLocation().$getId['image_listing']) ) {
                $getId['image_listing_src'] = \Yii::getAlias('@webCatalogImages/'.self::imagesLocation().$getId['image_listing']);
                $getId['image_listing_src_admin'] = \Yii::getAlias(self::imagesLocation().$getId['image_listing']);
                $_tmp = getimagesize(Images::getFSCatalogImagesPath().self::imagesLocation().$getId['image_listing']);
                $getId['image_listing_width'] = $_tmp[0];
                $getId['image_listing_height'] = $_tmp[1];
            }
            $getId['name'] = $getId['location_name'];
            $getId['location_meta_title'] = $getId['meta_title'];
            $getId['location_meta_description'] = $getId['meta_description'];
        }
        return $getId;
    }

    public static function getTree($platformId, $parentId=0, $_level=0, $_path=[], $lang_id = 0)
    {
        $tree = [];
        global $languages_id;
        $lang_id = $lang_id ? $lang_id : $languages_id;

        $get_level_r = tep_db_query(
            "SELECT ".
            " IFNULL(fd.date_modified,fd.date_added) AS last_modified, fd.status, fd.parent_id, ".
            " IF(LENGTH(fdt.location_name)>0,fdt.location_name,fdtd.location_name) AS location_name, ".
            " fd.id " .
            "FROM " . TABLE_SEO_DELIVERY_LOCATION . " fd " .
            " INNER JOIN " . TABLE_SEO_DELIVERY_LOCATION_TEXT . " fdt ON fdt.id=fd.id AND fdt.language_id='" . (int)$lang_id . "' " .
            " LEFT JOIN " . TABLE_SEO_DELIVERY_LOCATION_TEXT . " fdtd ON fdtd.id=fd.id AND fdtd.language_id='" . intval(\common\helpers\Language::get_default_language_id()) . "' " .
            "WHERE 1 ".
            " AND fd.parent_id='".(int)$parentId."' ".
            " AND fd.status=1 ".
            ($platformId!==false?"AND fd.platform_id='" . (int)$platformId . "' ":'').
            ((int)$parentId>0?"ORDER BY IF(LENGTH(fdt.location_name)>0,fdt.location_name,fdtd.location_name) ":"")
        );
        while($get_level = tep_db_fetch_array($get_level_r)){
            $_item_path = array_merge($_path, [$get_level['location_name']]);
            $tree[] = [
                'id' => $get_level['id'],
                'parent_id' => $get_level['parent_id'],
                'level' => $_level,
                'text' => str_repeat('&nbsp;&nbsp;',$_level).$get_level['location_name'],
                'name' => $get_level['location_name'],
                'path' => implode(" &gt; ",$_item_path),
            ];
            $sub_tree = self::getTree($platformId,$get_level['id'],$_level+1, $_item_path);
            $tree = array_merge($tree, $sub_tree);
        }
        return $tree;
    }

    public static function getSeoPageName($platformId, $id, $defaultRoute='delivery-location')
    {
        $seoNameArray = [$defaultRoute];
        global $languages_id;
        do {
            $get_data_r = tep_db_query(
                "SELECT fd.id, fd.parent_id, fdt.seo_page_name " .
                "FROM " . TABLE_SEO_DELIVERY_LOCATION . " fd " .
                " INNER JOIN " . TABLE_SEO_DELIVERY_LOCATION_TEXT . " fdt ON fdt.id=fd.id " .
                "WHERE fd.platform_id='" . (int)$platformId . "' AND fdt.language_id='" . (int)$languages_id . "' " .
                " AND fd.id='" . (int)$id . "' " .
                " AND fd.status=1 ".
                ""
            );

            if (tep_db_num_rows($get_data_r) > 0) {
                $get_data = tep_db_fetch_array($get_data_r);
                $id = $get_data['parent_id'];
                if (!isset($seoNameArray[1])) {
                    $seoNameArray[1] = $get_data['seo_page_name'];
                } else {
                    array_splice($seoNameArray, 1, 0, $get_data['seo_page_name']);
                }
            }else{
                break;
            }
        }while($id>0);

        return implode('/',$seoNameArray);
    }

    public static function getProductSetGroupId($id)
    {
        $set_id = (int)$id;
        do {
            $get_info_r = tep_db_query(
                "SELECT parent_id, product_set_rule " .
                "FROM " . TABLE_SEO_DELIVERY_LOCATION . " " .
                "WHERE id='" . (int)$id . "'"
            );
            if (tep_db_num_rows($get_info_r) == 1) {
                $get_info = tep_db_fetch_array($get_info_r);
                if ($get_info['product_set_rule']==0) {
                    $set_id = (int)$id;
                    break;
                }else{
                    if ( $get_info['parent_id']==0 ) break;
                    $id = $get_info['parent_id'];
                    $set_id = (int)$id;
                }
            }else{
                break;
            }
        }while(true);

        return $set_id;
    }

    public static function getAllItems($platformId, $params = [], &$total = 0) {
        global $languages_id;
        $search = '';
        if ( isset($params['search']) && !empty($params['search']) ) {
            $search .= " AND IF(LENGTH(fdt.location_name)>0,fdt.location_name,fdtd.location_name) LIKE '%".tep_db_input($params['search'])."%' ";
        }
        $query_raw = "SELECT fd.id, fd.parent_id, IF(LENGTH(fdt.location_name)>0,fdt.location_name,fdtd.location_name) AS location_name " .
        "FROM " . TABLE_SEO_DELIVERY_LOCATION . " fd " .
        " INNER JOIN " . TABLE_SEO_DELIVERY_LOCATION_TEXT . " fdt ON fdt.id=fd.id AND fdt.language_id='" . (int)$languages_id . "' " .
        " LEFT JOIN " . TABLE_SEO_DELIVERY_LOCATION_TEXT . " fdtd ON fdtd.id=fd.id AND fdtd.language_id='" . intval(\common\helpers\Language::get_default_language_id()) . "' " .
        "WHERE fd.platform_id='" . (int)$platformId . "' ".
        " AND fd.parent_id='{$params['parent_id']}' ".
        " {$search} ".
        "ORDER BY IF(LENGTH(fdt.location_name)>0,fdt.location_name,fdtd.location_name)";
        if (isset($params['total']) && $params['total']){
            $total = tep_db_num_rows(tep_db_query($query_raw));
        }
        if (isset($params['limit'])){
            $query_raw .= " limit " .$params['limit'];
        }
        $redirect_query = tep_db_query($query_raw);
        $list = [];
        if ( $params['parent_id'] ) {
            $parents = self::getParents($platformId, $params['parent_id'],true);
            if ( count($parents)>0 ) {
                $list[] = array(
                    '<span class="parent_cats"><i class="icon-circle"></i><i class="icon-circle"></i><i class="icon-circle"></i></span>' .
                    '<input class="cell_type" type="hidden" value="folder"><input class="cell_identify" type="hidden" value="' . $parents[count($parents)-1]['parent_id'] . '">',
                );
            }
        }

        if (tep_db_num_rows($redirect_query)) {
            while ($redirect = tep_db_fetch_array($redirect_query)) {
                //$check_nest = tep_db_fetch_array(tep_db_query("SELECT COUNT(*) AS c FROM ".TABLE_SEO_DELIVERY_LOCATION." WHERE parent_id='".$redirect['id']."' "));
                //$redirect['have_children'] = $check_nest['c']>0;
                $redirect['have_children'] = true;
                if ( $redirect['have_children'] ) {
                    $list[] = array(
                        '<div class="cat_name cat_name_attr">'.$redirect['location_name'].'</div>' .
                        '<input class="cell_type" type="hidden" value="folder"><input class="cell_identify" type="hidden" value="' . $redirect['id'] . '">',
                        //$redirect['location_name']
                    );
                }else{
                    $list[] = array(
                        $redirect['location_name'] .
                        '<input class="cell_type" type="hidden" value="item"><input class="cell_identify" type="hidden" value="' . $redirect['id'] . '">',
                        //$redirect['location_name']
                    );
                }
            }
        }
        return $list;
    }

    public static function deleteItem($id)
    {
        $get_children_r = tep_db_query("SELECT id FROM ".TABLE_SEO_DELIVERY_LOCATION." WHERE parent_id='".$id."'");
        if ( tep_db_num_rows($get_children_r) ) {
            while($_child = tep_db_fetch_array($get_children_r) ){
                self::deleteItem($_child['id']);
            }
        }
        tep_db_query("DELETE FROM ".TABLE_SEO_DELIVERY_LOCATION_TEXT." WHERE id='".$id."'");
        tep_db_query("DELETE FROM ".TABLE_SEO_DELIVERY_LOCATION." WHERE id='".$id."'");
    }

    public static function loadProductSet($id)
    {
        global $languages_id;
        $currencies = \Yii::$container->get('currencies');
        $products = [];

        $query = tep_db_query(
            "select p.products_id, pd.products_name, p.products_status ".
            "from " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS . " p ".
            " inner join ".TABLE_SEO_DELIVERY_LOCATION_PRODUCTS." pset ON pset.products_id=p.products_id ".
            "where pset.delivery_product_group_id = '" . (int)$id . "' and p.products_id =  pd.products_id and pd.language_id = '" . (int)$languages_id . "' and pd.platform_id = '".intval(\common\classes\platform::defaultId())."' ".
            "ORDER BY pset.sort_order, pd.products_name"
        );
        if (tep_db_num_rows($query) > 0) {
            while($data = tep_db_fetch_array($query)) {
                $products[] = [
                    'products_id' => $data['products_id'],
                    'products_name' => $data['products_name'],
                    'image' => \common\classes\Images::getImage($data['products_id'], 'Small'),
                    'price' => $currencies->format(\common\helpers\Product::get_products_price($data['products_id'])),
                    'status_class' => ($data['products_status'] == 0 ? 'dis_prod' : ''),
                ];
            }
        }
        return $products;
    }

    public static function applyTemplate($platform_id, $language_id, $level, $item_parent_id, $item_data)
    {
        if ( !is_array($item_data) ) return $item_data;
        $location_stack = [];
        while($item_parent_id>0) {
            $level_item_data = self::getItem($item_parent_id,$platform_id);
            if ( $level_item_data ) {
                $item_parent_id = $level_item_data['parent_id'];
                $location_stack[] = $level_item_data['location_name'];
            }else{
                break;
            }
        }
        foreach (array_reverse($location_stack) as $stack_level=>$location){
            $item_data['location_name_level_'.($stack_level+1)] = $location;
        }

        $template_data = [
            'location_name' => '',
            'location_description' => '',
            'meta_title' => '',
            'meta_keyword' => '',
            'meta_description' => '',
        ];

//        'overwrite_head_title_tag', 'overwrite_head_desc_tag',
        do {
            $get_template_data_r = tep_db_query(
                "SELECT * " .
                "FROM " . TABLE_SEO_DELIVERY_LOCATION_TEXT_TEMPLATE . " " .
                "WHERE platform_id='{$platform_id}' AND level='{$level}' and language_id='{$language_id}'"
            );
            if ( tep_db_num_rows($get_template_data_r)>0 ) {
                $_template_data = tep_db_fetch_array($get_template_data_r);
                $all_filled = true;
                foreach( $template_data as $key=>$current_value ) {
                    if ( in_array($key, ['overwrite_head_title_tag', 'overwrite_head_desc_tag',] ) ) continue;
                    if ( empty($current_value) && trim(strip_tags($_template_data[$key]))!='' ) {
                        $template_data[$key] = $_template_data[$key];
                    }
                    if ( empty($template_data[$key]) ) $all_filled = false;
                }
                if ( $_template_data['overwrite_head_title_tag'] && trim(strip_tags($_template_data['meta_title']))!='' ) {
                    $template_data['overwrite_head_title_tag'] = $_template_data['overwrite_head_title_tag'];
                }
                if ( $_template_data['overwrite_head_desc_tag'] && trim(strip_tags($_template_data['meta_description']))!='' ) {
                    $template_data['overwrite_head_desc_tag'] = $_template_data['overwrite_head_desc_tag'];
                }
                if ( $all_filled ) break;
            }
            $level--;
        } while ($level>0);

        $replace_keys = array_map(function($name){ return '#'.$name.'#'; },array_keys($item_data));
        $replace_values = array_values($item_data);
        foreach( $item_data as $key=>$value ){
            if ( !isset($template_data[$key]) ) {
                $template_data[$key] = $value;
                continue;
            }
            $template_data[$key] = str_ireplace($replace_keys, $replace_values, $template_data[$key]);
            if ( empty($template_data[$key]) || trim(strip_tags($template_data[$key]))=='' ) {
                $template_data[$key] = $value;
            }
        }

        if ($template_data['overwrite_head_title_tag']){
            $template_data['location_meta_title'] = $template_data['meta_title'];
        }
        if ($template_data['overwrite_head_desc_tag']){
            $template_data['location_meta_description'] = $template_data['meta_description'];
        }

        return $template_data;
    }
}