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
use common\classes\platform;

class GoogleCategories {

    use SqlTrait;

    public static function output_generated_category_path($id, $from = 'category', $format = '%2$s', $line_separator = '<br>') {
        $calculated_category_path_string = '';
        $calculated_category_path = self::generate_category_path($id, $from);       
        for ($i = 0, $n = sizeof($calculated_category_path); $i < $n; $i++) {
            for ($j = 0, $k = sizeof($calculated_category_path[$i]); $j < $k; $j++) {
                $variant = $calculated_category_path[$i][$j];
                if ($from == 'category' && $variant['id'] == 0 && count($calculated_category_path[$i]) == 1) {
                    $variant['text'] = TEXT_TOP;
                }
                $calculated_category_path_string .= (empty($format) ? $variant['text'] : sprintf($format, $variant['id'], $variant['text'])) . '&nbsp;&gt;&nbsp;';
            }
            $calculated_category_path_string = substr($calculated_category_path_string, 0, -16) . $line_separator;
        }
        $calculated_category_path_string = substr($calculated_category_path_string, 0, -(strlen($line_separator)));

        if (strlen($calculated_category_path_string) < 1) {
            $calculated_category_path_string = (empty($format) ? TEXT_TOP : sprintf($format, '0', TEXT_TOP));
        }

        return $calculated_category_path_string;
    }

    public static function generate_category_path($id, $from = 'category', $categories_array = '', $index = 0)
    {
        global $languages_id;

        if (!is_array($categories_array)) {
            $categories_array = array();
        }

        if (!is_array($categories_array[$index]))
            $categories_array[$index] = array();
        $category_query = tep_db_query(
            "select c.category_name, c.parent_id "
            ."from " . TABLE_GOOGLE_CATEGORIES . " c "
            ."WHERE c.categories_id = '" . (int) $id . "' and c.language_id = '" . (int) $languages_id . "'"
        );
        
        $category = tep_db_fetch_array($category_query);
        array_unshift($categories_array[$index], array('id' => $id, 'text' => $category['category_name']));
        if ((tep_not_null($category['parent_id'])) && ($category['parent_id'] != '0')) {
            $categories_array = self::generate_category_path($category['parent_id'], 'category', $categories_array, $index);
        }            

        return $categories_array;
    }
    
    public static function getCategoryHierarchy($id, $CategoryHierarchy, $languages_id, $wrapIntoLink = false)
    {        
        $check_query = tep_db_query("select parent_id, category_name ".
            "from " . TABLE_GOOGLE_CATEGORIES . " "."where categories_id = '".$id."' AND language_id='".$languages_id."'");
        $check = tep_db_fetch_array($check_query);
        if (isset($check['parent_id'])) {
            if ($wrapIntoLink) {
                $CategoryHierarchy = self::wrapLink($id, $check['category_name']).(!empty($CategoryHierarchy)?' > '.$CategoryHierarchy:'');
            } else {
                $CategoryHierarchy = $check['category_name'].(!empty($CategoryHierarchy)?' > '.$CategoryHierarchy:'');
            }            
            if (!empty($check['parent_id'])) {
                return self::getCategoryHierarchy($check['parent_id'], $CategoryHierarchy, $languages_id, $wrapIntoLink);
            }
        }
        
        return $CategoryHierarchy;
    }
    
    public static function wrapLink($id, $name)
    {
        return '<span data-id="'.$id.'" onclick="changeCategory(this)" class="underline-on-hover">'.$name.'</span>';
    }
    
    public static function getCategoryHierarchyDropDownData($id, $currentCatId, $CategoryHierarchy, $languages_id)
    {        
        $check_query = tep_db_query("select parent_id, category_name, categories_id ".
            "from " . TABLE_GOOGLE_CATEGORIES . " "."where categories_id = '".$id."' AND language_id='".$languages_id."' AND categories_status=1");
        $check = tep_db_fetch_array($check_query);

        //get siblings
        $check_query = tep_db_query("select parent_id, category_name, categories_id ".
            "from " . TABLE_GOOGLE_CATEGORIES . " "."where parent_id = '".$id."' AND language_id='".$languages_id."'  AND categories_status=1 order by category_name");        
        $cats = [];
        while ($res = tep_db_fetch_array($check_query)) {
            $cats[] = $res;
        }
        
        $CategoryHierarchy[$currentCatId] = $cats;        
        if (isset($check['parent_id'])) {
            //if (!empty($check['parent_id'])) {
                $currentCatId = $id;               
                return self::getCategoryHierarchyDropDownData($check['parent_id'], $currentCatId, $CategoryHierarchy, $languages_id);
            //}                        
        }
        return $CategoryHierarchy;
    }
    
    public static function drawProductCategoryInputs($language_id, $categoryDescription, $category)
    {
        $inputs = '<span style="display:block;max-width:25%;float:left;clear:left;">'.
            tep_draw_input_field('google_product_type[' . $language_id . ']', $category['google_product_type'], 'class="form-control"')
            . tep_draw_hidden_field('google_product_type_id', $category['google_product_type']).'</span>';
       
        [$dropdowns,$hierachyRow] = self::getGoogleCategoriesDropdowns($category['google_product_type'], $language_id, $category['parent_id']);
        
        $inputs = $dropdowns.$inputs.'<div id="catHierachy" style="float:left;margin-left:10px">'.$hierachyRow.'</div>';
               
        return $inputs;
    }
    
    public static function getGoogleCategoriesDropdowns($categoryId, $language_id, $realCategoryParentId = 0)
    {    
        if ($categoryId == 0 && $realCategoryParentId!=0) {
            //find parent categories data
            return self::getParentCategoryInfo($language_id, $realCategoryParentId);
        }        
        
        //get parent id of selected category
        $check_query = tep_db_query("select parent_id, category_name, categories_id ".
            "from " . TABLE_GOOGLE_CATEGORIES . " "."where categories_id = '".$categoryId."' AND language_id='".$language_id."'  AND categories_status=1");
        $check = tep_db_fetch_array($check_query);      
        $parent_id = $check['parent_id'];
            
        $categoryHierarchy = self::getCategoryHierarchyDropDownData($parent_id, $categoryId, [], $language_id);        
        $categoryHierarchy = array_reverse($categoryHierarchy, true);
            
        //get child dropdown data
        if ($categoryId) {
            $check_query = tep_db_query("select parent_id, category_name, categories_id "
                ."from " . TABLE_GOOGLE_CATEGORIES . " "
                ."where parent_id = '".$categoryId."' AND language_id='".$language_id."'  AND categories_status=1 order by category_name"); 
            $children = [];
            if (tep_db_num_rows($check_query) > 0) {
                while ($res = tep_db_fetch_array($check_query)) {
                    $children[] = $res;
                }
                $categoryHierarchy['child'] = $children;
            }
        }
        
        $dropDownsContainer = self::getDropDownsChain($categoryHierarchy);
        $selectEl = $dropDownsContainer[0];
        $categoriesHierarchy = $dropDownsContainer[1];
        
        $categoriesHierarchy = implode(' > ', $categoriesHierarchy);
        return [$selectEl,$categoriesHierarchy];
    }
    
    public static function getDropDownsChain($categoryHierarchy)
    {
        $selectEl = '<div id="dropDownChain" style="width:100%">';
        $categoriesHierarchy = [];
        foreach ($categoryHierarchy as $selectedValue => $options) {
      
            $selectEl .= '<span class="row" style="margin:0;max-width:14%;">';

            $selectEl .= '<select name="google_category_dropdown_'.$selectedValue.'[' . $language_id . ']" class="form-control form-control-small">';
            $selectEl .= '<option value=""> - please select - </option>';            
            foreach ($options as $key => $value) { 
                $selected = '';
                if ($selectedValue == $value['categories_id']) {
                    $categoriesHierarchy[] = $value['category_name'];
                    $selected = ' selected = "selected" ';
                }
                $selectEl .= 
                '<option value="'.$value['categories_id'].'" '.$selected.'>'
                    .$value['category_name']
                .'</option>';
            }
            $selectEl .= '</select>';          
            $selectEl .= '</span>';            
        }        
        $selectEl .= '</div>';
        
        return [$selectEl,$categoriesHierarchy];
    }
    
    public static function getParentCategoryInfo($language_id, $realCategoryParentId)
    {
        $googleCategoryId = self::getParentCategoryGoogleCategory($realCategoryParentId);
        $categoryHierarchy = self::getCategoryHierarchyDropDownData('', 0, [], $language_id);
        $dropDownsContainer = self::getDropDownsChain($categoryHierarchy);
        $selectEl = $dropDownsContainer[0];
        
        $categoriesHierarchy = self::getCategoryHierarchy($googleCategoryId, '', $language_id, false);
        $categoriesHierarchy = rtrim($categoriesHierarchy, ' >');
        $categoriesHierarchy = '<strong>From parent categories:</strong> '.$categoriesHierarchy;
        
        return [$selectEl,$categoriesHierarchy];
    }        
    
    public static function getParentCategoryGoogleCategory($realCategoryParentId)
    {        
        $catId = 0;
        $check_query = tep_db_query("select parent_id, google_product_type ".
            "from " . TABLE_CATEGORIES . " "
            ."where categories_id = '".$realCategoryParentId."'");
        $check = tep_db_fetch_array($check_query);
        if (isset($check['google_product_type']) && !empty($check['google_product_type'])) {            
            return $check['google_product_type'];
        } else if (isset($check['google_product_type']) && empty($check['google_product_type'])) {                        
            if (!empty($check['parent_id'])) {
                return self::getParentCategoryGoogleCategory($check['parent_id']);
            }
        }        
        return $catId;
    }
}
