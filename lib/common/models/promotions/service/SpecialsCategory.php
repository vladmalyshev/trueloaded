<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\models\promotions\service;

use Yii;
use common\models\promotions\service\ServiceInterface;
use common\models\promotions\service\ServiceAbstract;
use common\api\models\AR\Group;
use common\models\promotions\PromotionService;
use common\models\promotions\PromotionsSets;
use common\models\promotions\PromotionsConditions;
use common\helpers\Categories;
use common\helpers\Product;
use common\helpers\Manufacturers;
use common\helpers\Media;

class SpecialsCategory extends ServiceAbstract implements ServiceInterface{
    
    public $category;
    protected $vars;
    public $enabledGroups;
    public $groups = false;
    public $conditions = [];

    
    public function __construct() {
        $this->enabledGroups = defined('CUSTOMERS_GROUPS_ENABLE') && CUSTOMERS_GROUPS_ENABLE == 'True'; 
        if ($this->enabledGroups){
            //$this->groups = Group::find()->all();
          $this->groups = array_map(function ($el) { return (object)$el; },  \common\helpers\Group::get_customer_groups(self::DEFAULT_GROUP_TYPE));
        }
    }

    public function rules(){
        return ['category'];
    }
    
    public function getDescription() {
        return SPECIAL_PRICE_ON_CATEGORIES;
    }
    
    public function getPromoFullDescription(){
        return (defined('TEXT_SALEMAKER_FULL_DESC')?TEXT_SALEMAKER_FULL_DESC:'');
    }
    
    public function useTranslation(){
        \common\helpers\Translation::init('admin/specials');
        \common\helpers\Translation::init('admin/categories');
    }
    
    public function getSettingsTemplate(){
        return 'salemaker/specials_category.tpl';
    }
    
    public function loadSettings($params) {
        
        $platform_id = (isset($params['platform_id']) ? $params['platform_id'] : 0);
        $this->settings['platform_id'] = $platform_id;
        
        if (!isset($params['promo_id']) || is_null($params['promo_id'])) $params['promo_id'] = 0;
        $this->settings['promo_id'] = @$params['promo_id'];
        
        $fCats = PromotionsSets::find()->where(['promo_id' => $params['promo_id'], 'promo_slave_type' => PromotionService::SLAVE_CATEGORY])->asArray()->all();
        $assigned = \yii\helpers\ArrayHelper::getColumn($fCats, 'promo_slave_id', false);
        $qty = 0;
        if (\frontend\design\Info::isTotallyAdmin()) {
            $categoree_tree = $this->loadTree(['platform_id' => $platform_id, 'category_id' => 0]);

            foreach($categoree_tree['categories_tree'] as $idx => $branch){
                if (in_array($branch['categories_id'], $assigned)){
                    $categoree_tree['categories_tree'][$idx]['selected'] = true;
                }
            }
        }
        $this->settings['categories_tree'] = $categoree_tree['categories_tree'];
        $this->settings['selected_categories'] = $assigned;
        $this->settings['selected_data'] = json_encode($assigned);
        if ($fCats && $fCats[0]){
            if ($fCats[0]['promo_quantity']){
                $qty = $fCats[0]['promo_quantity'];
            }
        }
        
        $this->settings['manufacturers_tree'] = \common\helpers\Manufacturers::get_manufacturers();
        $this->settings['selected_manufacturers'] = [];
        
        $this->settings['conditions'] = []; //saved conditions
        if (isset($params['promo_id'])) {
            $set = PromotionsSets::find()->where(['promo_id' => $params['promo_id'], 'promo_slave_type' => PromotionService::SLAVE_MANUFACTURER])->all();
            if (is_array($set)) {
                foreach ($set as $item) {
                    $this->settings['selected_manufacturers'][] = $item->promo_slave_id;
                    if ($item->promo_quantity){
                        $qty = $item->promo_quantity;
                    }
                }
            }
            
            $this->settings['conditions'] = PromotionsConditions::find()->where(['promo_id' => $params['promo_id']])->asArray()->all();
            //echo '<pre>';print_r($this->settings[conditions]);die;
            if (is_array($this->settings['conditions']) && count($this->settings['conditions'])){
                $this->settings['conditions'] = \yii\helpers\ArrayHelper::index($this->settings['conditions'], 'groups_id');
            } else if (is_array($this->groups)) {
                foreach ($this->groups as $group) {
                    $this->settings['conditions'][$group->groups_id] = [
                        'promo_type' => 1,
                        'promo_condition' => 0
                    ];
                }
            }
            
            if (!isset($this->settings['conditions'][0])){
                $this->settings['conditions'][0] = [
                    'promo_type' => 1,
                    'promo_condition' => 0
                ];
            }
        }
        
        if ($this->useProperties){
            $properties = \common\helpers\Properties::getProperties();
            $this->settings['selected_properties'] = [];
            if (isset($params['promo_id'])) {
                $set = PromotionsSets::find()->where(['promo_id' => $params['promo_id'], 'promo_slave_type' => PromotionService::SLAVE_PROPERTY])->all();
                if (is_array($set)) {
                    foreach ($set as $item) {
                        $this->settings['selected_properties'][] = 'pr_'.$item->promo_slave_id;
                        if ($item->promo_quantity){
                            $qty = $item->promo_quantity;
                        }
                    }
                }
                $set = PromotionsSets::find()->where(['promo_id' => $params['promo_id'], 'promo_slave_type' => PromotionService::SLAVE_PROPERTY_VALUE])->all();
                if (is_array($set)) {
                    foreach ($set as $item) {
                        $this->settings['selected_properties'][] = 'pv_'.$item->promo_slave_id;
                        if ($item->promo_quantity){
                            $qty = $item->promo_quantity;
                        }
                    }
                }
            }
            $this->settings['properties_tree'] = json_encode(\common\helpers\Properties::propertiesToFancy($properties, $this->settings['selected_properties']));
        }
        
        $this->settings['qty'] = $qty;
        
        $this->settings['type'] = ['0' => 'Discount', '1' => 'Percent', '2' => 'New Price'];
        $this->settings['condition'] = ['0' => 'Ignore special price', '1' => 'Ignore Sale condition ', '2' => 'Apply Sale Condition to Special Price'];
        
        $this->settings['groups'] = $this->groups;
    }
    
    public function load($details){
        if (is_array($details)){
            $this->vars = $details;
            return true;
        }
        return false;
    }
    
    public function loadTree($params){
        $this->layout = false;

      $platform_id = $params['platform_id'];
      $do = $params['do'];

      $response_data = array();
      
      if ( $do == 'missing_lazy' ) {
        $category_id = $params['id'];
        $selected = $params['selected'];
        $selected_data = tep_db_prepare_input($params['selected_data']);
        if (!is_array($selected_data)) $selected_data = [];
        if (substr($category_id, 0, 1) == 'c') $category_id = intval(substr($category_id, 1));

        $response_data = $this->loadTree(['platform_id' => $platform_id, 'category_id' => $category_id]);
        foreach( $response_data['categories_tree'] as $_idx=>$_data ) {
          $response_data['categories_tree'][$_idx]['selected'] = in_array($_data['categories_id'], $selected_data);
        }
        return $response_data['categories_tree'];
        
      } else if ( $do == 'update_selected' ) {
          
        $cat_id = (int)$params['id'];
        $selected = $params['selected'];
        $select_children = $params['select_children'];
        $selected_data = tep_db_prepare_input($params['selected_data']);        
        if (!is_array($selected_data)) $selected_data = [];
        
        if ( $selected ) {
          /*$parent_ids = array((int)$cat_id);
          Categories::get_parent_categories($parent_ids, $parent_ids[0], false);

          foreach( $parent_ids as $parent_id ) {
            if ( !in_array((int)$parent_id, $selected_data) ) {
             // $response_data['update_selection'][(int)$parent_id] = true;
              //$selected_data[] = (int)$parent_id;
            }
          }*/
          
          if ( $select_children ) {
            $children = array();
            $this->tep_get_category_children($children, $platform_id, $cat_id);            
            foreach($children as $child_key){
              if ( !in_array($child_key, $selected_data) ) {
                $response_data['update_selection'][$child_key] = true;
                $selected_data[] = $child_key;
              }
            }
            unset($children);
          }
          if ( $cat_id && !in_array($cat_id, $selected_data) ) {
            $response_data['update_selection'][$id] = true;
            $selected_data[] = $cat_id;
          }
        }else{
          $children = array();
          $this->tep_get_category_children($children, $platform_id, $cat_id);
          foreach($children as $child_key){
            if ( ($_idx = array_search($child_key, $selected_data)) !== false) {
              $response_data['update_selection'][$child_key] = false;
              unset($selected_data[$_idx]);
            }
          }
          unset($children);
          if ( ($_idx = array_search($cat_id, $selected_data)) !== false) {
            $response_data['update_selection'][$cat_id] = false;
            unset($selected_data[$_idx]);
          }
        }

        $response_data['selected_data'] = array_values($selected_data);
        
      } else { //init
          
          $get_categories_r = tep_db_query(
            "SELECT c.categories_id, cd.categories_name as title ".
            "FROM " . TABLE_CATEGORIES_DESCRIPTION . " cd, " . TABLE_CATEGORIES . " c ".
            " left join ".TABLE_PLATFORMS_CATEGORIES." pc on pc.categories_id=c.categories_id and pc.platform_id='" . (int)$platform_id ."' ".
            "WHERE cd.categories_id=c.categories_id and cd.language_id='" . \Yii::$app->settings->get('languages_id') . "' AND cd.affiliate_id=0 and c.parent_id='" . (int)$params['category_id'] . "' ".
            "order by c.sort_order, cd.categories_name"
          );

          $response_data['categories_tree'] = [];
          while ($_categories = tep_db_fetch_array($get_categories_r)) {
              $_categories['folder'] = true;
              $_categories['lazy'] = true;
              $_categories['selected'] = false;
              $_categories['key'] = $_categories['categories_id'];
              //$_categories['has_subfolder'] = Categories::has_category_subcategories($_categories['categories_id']);
              $response_data['categories_tree'][] = $_categories;
          }
          
      }
      
      return $response_data;
    }
    
    protected function tep_get_category_children(&$children, $platform_id, $categories_id) {
      if ( !is_array($children) ) $children = array();
        foreach($this->loadTree(['platform_id' => $platform_id, 'category_id' => $categories_id])['categories_tree'] as $item) {
          $key = $item['categories_id'];
          $children[] = $key;
          if ($item['folder']) {
            if (substr($item['categories_id'], 0, 1) == 'c') {
              $category_id = intval(substr($item['categories_id'], 1));
            } else {
              $category_id = $item['categories_id'];
            }
            $this->tep_get_category_children($children, $platform_id, $category_id);
          }
        }
      }
    
    public function savePromotions($promo_id = 0){
      
        $saved_ids = [];

        if (is_array($this->vars['cat_id'])){
            foreach($this->vars['cat_id'] as $cat){
                $saved_ids[PromotionService::SLAVE_CATEGORY][] = ['id' => $cat, 'quantity' => $this->vars['promo_quantity']];
            }
        }
        
        if (is_array($this->vars['man_id'])){
            foreach($this->vars['man_id'] as $man){
                $saved_ids[PromotionService::SLAVE_MANUFACTURER][] = ['id' => $man, 'quantity' => $this->vars['promo_quantity']];
            }
        }
        
        if (is_array($this->vars['properties'])){
            foreach ($this->vars['properties'] as $property){
                if (substr($property, 0, 2) == 'pr'){
                    $saved_ids[PromotionService::SLAVE_PROPERTY][] = ['id' => substr($property, 3), 'quantity' => $this->vars['promo_quantity']];
                } else if (substr($property, 0, 2) == 'pv'){
                    $saved_ids[PromotionService::SLAVE_PROPERTY_VALUE][] = ['id' => substr($property, 3), 'quantity' => $this->vars['promo_quantity']];
                }
            }
        }
        
        if (is_array($this->vars['condition'])){
            $this->conditions[0] = [
                        'promo_deduction' => (int)$this->vars['deduction'][0],
                        'promo_condition' => (int)$this->vars['condition'][0],
                        'promo_type' => (int)$this->vars['type'][0],
                        'groups_id' => 0,
                        'promo_limit' => (int)$this->vars['limit'][0],
                    ];
            if ($this->enabledGroups){
                foreach ($this->vars['condition'] as $groups_id => $value){
                    if ($this->vars['use_settings'][$groups_id] ){
                        $this->conditions[$groups_id] = [
                            'promo_deduction' => (int)$this->vars['deduction'][$groups_id],
                            'promo_condition' => (int)$value,
                            'promo_type' => (int)$this->vars['type'][$groups_id],
                            'groups_id' => (int)$groups_id,
                        ];
                    } else {
                        $this->conditions[$groups_id] = $this->conditions[0];
                        $this->conditions[$groups_id]['groups_id'] = $groups_id;
                    }
                    $this->conditions[$groups_id]['promo_limit'] = (int)$this->vars['limit'][$groups_id];
                    $this->conditions[$groups_id]['promo_limit_block'] = (int)$this->vars['limit_block'][$groups_id];
                }
            }
        }
        return $saved_ids;
    }
    
    public function hasConditions(){
        return count($this->conditions);
    }
    
    public function getConditions(){
        return $this->conditions;
    }
    
    private function _checkLimitation(){
        $cart = $this->getCart();
        static $cart_map = null;
        
        //if (!$cart->contents) return false;
        
        if (is_null($cart_map)) {
            $cart_map = [];
            array_map(function($value) use (&$cart_map) {
                $cart_map[intval($value)] = $value;
            }, is_array($cart->contents)?array_keys($cart->contents):[]);
        }
        //$existed = array_intersect(array_values($cart_map), [(int)$this->vars['products_id']]);

        static $_promotions_specials_category_array = array();
        if (!isset($_promotions_specials_category_array[$this->vars['promo_id']])) {
            $data = [];
            $details = $this->vars['details'];
            if (is_array($details['slave']['categories'])) {
                $tmp = array_pop($details['slave']['categories']);
                $data['categories'] = ['qty' => $tmp['qty']??0 ];
            }
            if (is_array($details['slave']['brands'])) {
                $tmp = array_pop($details['slave']['brands']);
                $data['brands'] = ['qty' => $tmp['qty']??0 ];
            }
            if ($this->useProperties) {
                if (is_array($details['slave']['properties'])) {
                    $tmp = array_pop($details['slave']['properties']);
                    $data['properties'] = ['qty' => $tmp['qty']??0 ];
                }

                if (is_array($details['slave']['properties_values'])) {
                    $tmp = array_pop($details['slave']['properties_values']);
                    $data['properties_values'] = ['qty' => $tmp['qty']??0 ];
                }
            }
            $_promotions_specials_category_array[$this->vars['promo_id']] = $data;
        } else {
            $data = $_promotions_specials_category_array[$this->vars['promo_id']];
        }
        
        
        $confirm = true;
        
        foreach($data as $key => $values){
            $cKey = $cart_map[(int)$this->vars['products_id']];
            if (isset($values['qty']) && $values['qty'] && $values['products']){
                $result = array_intersect(array_keys($cart_map), [(int)$this->vars['products_id']]);
                if (!$result) {
                    $confirm = false;
                    continue;
                }
                
                if ($cart->contents[$cKey]['qty'] < $values['qty']) {
                    $confirm = false;
                }
            }
            if ($this->isLimitExceeded($cart->contents[$cKey]['qty'])){
                $confirm = false;
            }
        }
        return $confirm;
    }
       
    public function calculate(){
        if (!$this->_checkLimitation()) {
            return false;
        }
        
        $product_price = $this->vars['product_price'];
        $special_price = $this->vars['special_price'];
        if (!$special_price) {
            $tmp_special_price = $product_price;
        } else {
            $tmp_special_price = $special_price;
        }

        switch ($this->vars['promo_type']) {
            case 0:
                $sale_product_price = $product_price - $this->vars['promo_deduction'];
                $sale_special_price = $tmp_special_price - $this->vars['promo_deduction'];
                break;
            case 1:
                $sale_product_price = $product_price - (($product_price * $this->vars['promo_deduction']) / 100);
                $sale_special_price = $tmp_special_price - (($tmp_special_price * $this->vars['promo_deduction']) / 100);
                break;
            case 2:
                $sale_product_price = $this->vars['promo_deduction'];
                $sale_special_price = $this->vars['promo_deduction'];
                break;
            default:
                return $this->vars['special_price'] >= $product_price ? false : $special_price;
        }
        if ($sale_product_price < 0) {
            $sale_product_price = 0;
        }

        if ($sale_special_price < 0) {
            $sale_special_price = 0;
        }
        if (!$special_price) {
            return $sale_product_price >= $product_price ? false : number_format($sale_product_price, 4, '.', '');
        } else {
            switch ($this->vars['promo_condition']) {
                case 0:
                    return $sale_product_price >= $product_price ? false : number_format($sale_product_price, 4, '.', '');
                    break;
                case 1:
                    return $special_price >= $product_price ? false : number_format($special_price, 4, '.', '');
                    break;
                case 2:
                    return $sale_special_price >= $product_price ? false : number_format($sale_special_price, 4, '.', '');
                    break;
                default:
                    return $special_price >= $product_price ? false : number_format($special_price, 4, '.', '');
            }
        }

    }
    
    private $identifiedrs = [ 'type' => null,  'id' => null,  ];
    public function setIdentifier($id, $type){
        $this->identifiedrs = [
            'type' => $type,
            'id' => $id,
        ];
    }
        
    public function getPromotionInfo($promo_id) {
        $salemaker_array = \common\components\Salemaker::init();
        $current_salemakers = \yii\helpers\ArrayHelper::index($salemaker_array, 'promo_id');
        if (!isset($current_salemakers[$promo_id]))
            return;
        $current_salemaker = $current_salemakers[$promo_id];
        $this->prepareInformation($current_salemaker);
        if ($this->settings['info']){
            return \common\models\promotions\widgets\SpecialsCategory::widget(['details' => $this->settings]);
        }
    }
    
    public function prepareInformation($current_salemaker){
        $languages_id = Yii::$app->settings->get('languages_id');
        $currenices = Yii::$container->get('currencies');
        $this->settings['info'] = [
            'categories' => [],
            'manufacturers' => [],
        ];
        $show_promotion = false;

        if (!$this->settings['selected_manufacturers'] && !$this->settings['selected_categories']) {
            return '';
        }

        if (isset($this->settings['selected_manufacturers']) && is_array($this->settings['selected_manufacturers']) && count($this->settings['selected_manufacturers'])){
            foreach($this->settings['selected_manufacturers'] as $item){
                if(is_null($this->identifiedrs['type']) || $this->identifiedrs['type'] == PromotionService::SLAVE_MANUFACTURER){
                    if ($this->identifiedrs['type'] != PromotionService::SLAVE_MANUFACTURER || $this->identifiedrs['id'] == $item) {
                        $show_promotion = true;
                    }
                }
                $image = Manufacturers::get_manufacturer_info('manufacturers_image', $item);
                if ($image){
                    $image = Media::getAlias('@webCatalogImages/') . $image;
                } else {
                    $image = '';
                }
                $collector = [
                  'name'  => Manufacturers::get_manufacturer_info('manufacturers_name', $item),
                  'image' => $image,
                  'link' => tep_href_link(Manufacturers::get_manufacturer_seo_name($item, $languages_id))
                ];
                $this->settings['info']['manufacturers'][] = $collector;
            }
        }

        if (isset($this->settings['selected_categories']) && is_array($this->settings['selected_categories']) && count($this->settings['selected_categories'])){
            foreach($this->settings['selected_categories'] as $item){
                if(is_null($this->identifiedrs['type']) || $this->identifiedrs['type'] == PromotionService::SLAVE_CATEGORY){
                    if (!($this->identifiedrs['type'] == PromotionService::SLAVE_CATEGORY && $this->identifiedrs['id'] != $item))  $show_promotion = true;
                }
                if (is_null($this->identifiedrs['type'])){
                    $parents = Categories::getCategoryParentsIds($item);
                    $parents = array_diff($parents, [$item]);
                    if (array_intersect($parents, $this->settings['selected_categories'])) continue;
                }
                $image = Categories::get_category_image($item);
                if ($image['categories_image']){
                    $image = Media::getAlias('@webCatalogImages/') . $image['categories_image'];
                } else {
                    $image = '';
                }
                $collector = [
                  'name'  => Categories::get_categories_name($item),
                  'image' => $image,
                  'link' => tep_href_link('catalog', Categories::get_path($item))
                ];

                $this->settings['info']['categories'][] = $collector;
            }
        }

        $this->settings['info']['condition'] = '';
        if (isset($current_salemaker['conditions']) && ($this->settings['info']['categories'] || $this->settings['selected_manufacturers']) ) {
            $condition = $current_salemaker['conditions'];
            $product_tax_class_id = Product::get_products_info($current_salemaker['products'][0], 'products_tax_class_id');
            switch ($condition['promo_type']) {
                case 0:
                    $this->settings['info']['condition'] = $currenices->display_price($condition['promo_deduction'], \common\helpers\Tax::get_tax_rate($product_tax_class_id));
                    break;
                case 1:
                    $this->settings['info']['condition'] = round($condition['promo_deduction']) . "%";
                    break;
                case 2:
                    $this->settings['info']['condition'] = $currenices->display_price($condition['promo_deduction'], \common\helpers\Tax::get_tax_rate($product_tax_class_id));
                    break;
            }
        }
        if (!$show_promotion) {
            $this->settings['info'] = [];
        }
    }
    
    public function getPromotionToProduct($promo, $products_id){
        if ($current_salemaker = \common\components\Salemaker::getConditions($promo)){
            if (isset($current_salemaker['products']) && in_array($products_id, $current_salemaker['products'])){
                $this->prepareInformation($current_salemaker);
                return \common\models\promotions\widgets\SpecialsCategory::widget(['details' => $this->settings]);
            }
        }
    }
}
