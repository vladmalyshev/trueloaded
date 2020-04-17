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
use common\models\promotions\PromotionService;
use common\api\models\AR\Products;
use common\api\models\AR\Group;
use common\models\promotions\PromotionsSets;
use common\models\promotions\PromotionsConditions;
use common\classes\Images;
use common\helpers\Media;
use common\helpers\Categories;
use common\helpers\Product;

/*
 * This Promo Module provide discount no each quantity step product in cart
 * example: get 50% discount on price for each second product (test product (2EUR) x 2 qty = 2EUR)
 * module is based on cartdiscount promo module
 */

class NextDiscount extends ServiceAbstract implements ServiceInterface {

    //public $category;
    protected $vars;
    public $enabledGroups;
    public $groups = false;
    public $conditions = [];


    private $item_types = [
        PromotionService::MASTER_PRODUCT => 'p',
        PromotionService::MASTER_CATEGORY => 'c',
//        '2' => 'brn',
        PromotionService::SLAVE_CATEGORY => 'c',
        PromotionService::SLAVE_PRODUCT => 'p',
    ];

    CONST MAX_LOADING_ITEMS = 5000;

    public function __construct() {
        $this->enabledGroups = defined('CUSTOMERS_GROUPS_ENABLE') && CUSTOMERS_GROUPS_ENABLE == 'True';
        if ($this->enabledGroups) {
            $this->groups = array_map(function ($el) { return (object)$el; },  \common\helpers\Group::get_customer_groups(-1));
        }
    }

    public function rules() {
        return ['cart'];
    }

    public function getDescription() {
        return DISCOUNT_STEP_PRODUCT_IN_SHOPPING_CART;
    }

    public function getPromoFullDescription() {
        return (defined('TEXT_NEXTDISCOUNT_FULL_DESC') ? TEXT_NEXTDISCOUNT_FULL_DESC : '');
    }

    public function useTranslation() {
        \common\helpers\Translation::init('admin/specials');
        \common\helpers\Translation::init('admin/categories');
    }

    public function getSettingsTemplate() {
        return 'nextdiscount/next_discount.tpl';
    }

    public function addItem($params) {
        $item_id = $params['item_id'];
        $type = $params['type'];
        $quantity = isset($params['quantity']) ? $params['quantity'] : 1;

        $response = [];
        if ($type == 'p') {
            $product = new \stdClass();
            $product->id = (int) $item_id;
            $product->quantity = $quantity;
            $product->name = Product::get_products_name($product->id);
            $product->type = "product";
            $product->image = Images::getImageUrl($product->id, 'Medium');
            $response = [
                'product' => $product,
            ];
        } else {
            $category = new \stdClass();
            $category->id = (int) $item_id;
            $category->quantity = $quantity;
            $category->name = Categories::output_generated_category_path($category->id);
            $category->type = "category";
            $image = Categories::get_category_image($category->id);
            $category->image = '';
            if ($image) {
                if (file_exists(Images::getFSCatalogImagesPath() . $image['categories_image'])) {
                    $category->image = Images::getWSCatalogImagesPath() . $image['categories_image'];
                } elseif (file_exists(Images::getFSCatalogImagesPath() . $image['categories_image_2'])) {
                    $category->image = Images::getWSCatalogImagesPath() . $image['categories_image_2'];
                }
            }
            $response = [
                'category' => $category,
            ];
        }
        return $response;
    }

    public function loadSettings($params) {
        $this->settings['promo_id'] = @$params['promo_id'];
        $platform_id = (isset($params['platform_id']) ? $params['platform_id'] : 0);
        $this->settings['platform_id'] = $platform_id;
        if (\frontend\design\Info::isTotallyAdmin()) {
            $this->settings['lazy_loading'] = (\common\api\models\AR\Categories::find()->count() + \common\api\models\AR\Products::find()->count() > self::MAX_LOADING_ITEMS ? true : false);

            if (!$this->settings['lazy_loading']) {
                $this->settings['categories_tree'] = $this->loadTree(['platform_id' => $platform_id, 'category_id' => 0])['categories_tree'];
            } else {
                $this->settings['categories_tree'] = [];
            }
        }

        $this->settings['assigned_items'] = [
            'master' => [],
            'slave' => [],
        ];

        if (isset($params['promo_id'])) {
            $set = PromotionsSets::find()->where(['promo_id' => $params['promo_id']])->all();
            if (is_array($set)) {
                foreach ($set as $item) {
                    if (in_array($item->promo_slave_type, [PromotionService::MASTER_CATEGORY, PromotionService::MASTER_PRODUCT])) {
                        $this->settings['assigned_items']['master'][] = $this->addItem(['type' => $this->item_types[$item->promo_slave_type], 'item_id' => $item->promo_slave_id, 'quantity' => $item->promo_quantity]);
                    } else {
                        $this->settings['assigned_items']['slave'][] = $this->addItem(['type' => $this->item_types[$item->promo_slave_type], 'item_id' => $item->promo_slave_id]);
                    }
                }
            }
            //echo '<pre>';print_r($this->settings['assigned_items']);die;
        }
        $this->settings['conditions'] = []; //saved conditions
        if (isset($params['promo_id'])) {

            $this->settings['conditions'] = PromotionsConditions::find()->where(['promo_id' => $params['promo_id']])->asArray()->all();

            if (is_array($this->settings['conditions']) && count($this->settings['conditions'])) {
                $this->settings['conditions'] = \yii\helpers\ArrayHelper::index($this->settings['conditions'], 'groups_id');
            }

            if (is_array($this->groups)) {
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
        $this->settings['type'] = [/* '0' => TEXT_DISCOUNT, */ '1' => TEXT_PERCENT/* , '2' => TEXT_NEW_PRICE */]; //disabled by default
        $this->settings['condition'] = ['0' => IGNORE_SPECIAL_PRICE, '1' => IGNORE_SALE_CONDITION, '2' => APPLY_SALE_CONDITION_TO_SPECIAL_PRICE];

        $this->settings['groups'] = $this->groups;
    }

    public function load($details) {
        if (is_array($details)) {
            $this->vars = $details;
            return true;
        }
        return false;
    }

    public function loadTree($params) {
        $this->layout = false;
        $languages_id = \Yii::$app->settings->get('languages_id');
        $platform_id = $params['platform_id'];
        $do = $params['do'];

        $response_data = array();

        if ($do == 'missing_lazy') {
            $category_id = $params['id'];
            $selected = $params['selected'];
            $selected_data = tep_db_prepare_input($params['selected_data']);
            if (!is_array($selected_data))
                $selected_data = [];
            if (substr($category_id, 0, 1) == 'c')
                $category_id = intval(substr($category_id, 1));

            $response_data = $this->loadTree(['platform_id' => $platform_id, 'category_id' => $category_id]);

            /* foreach( $response_data['categories_tree'] as $_idx=>$_data ) {
              $response_data['categories_tree'][$_idx]['selected'] = in_array($_data['categories_id'], $selected_data);
              } */
            return $response_data['categories_tree'];
        } else if ($do == 'update_selected') {

            $cat_id = (int) $params['id'];
            $selected = $params['selected'];
            $select_children = $params['select_children'];
            $selected_data = tep_db_prepare_input($params['selected_data']);
            if (!is_array($selected_data))
                $selected_data = [];

            if ($selected) {
                $parent_ids = array((int) $cat_id);
                Categories::get_parent_categories($parent_ids, $parent_ids[0], false);

                foreach ($parent_ids as $parent_id) {
                    if (!in_array((int) $parent_id, $selected_data)) {
                        $response_data['update_selection'][(int) $parent_id] = true;
                        $selected_data[] = (int) $parent_id;
                    }
                }

                if ($select_children) {
                    $children = array();
                    $this->tep_get_category_children($children, $platform_id, $cat_id);
                    foreach ($children as $child_key) {
                        if (!in_array($child_key, $selected_data)) {
                            $response_data['update_selection'][$child_key] = true;
                            $selected_data[] = $child_key;
                        }
                    }
                }
                if ($cat_id && !in_array($cat_id, $selected_data)) {
                    $response_data['update_selection'][$id] = true;
                    $selected_data[] = $cat_id;
                }
            } else {
                $children = array();
                $this->tep_get_category_children($children, $platform_id, $cat_id);
                foreach ($children as $child_key) {
                    if (($_idx = array_search($child_key, $selected_data)) !== false) {
                        $response_data['update_selection'][$child_key] = false;
                        unset($selected_data[$_idx]);
                    }
                }
                if (($_idx = array_search($cat_id, $selected_data)) !== false) {
                    $response_data['update_selection'][$cat_id] = false;
                    unset($selected_data[$_idx]);
                }
            }

            $response_data['selected_data'] = $selected_data;
        } else { //init
            $get_categories_r = tep_db_query(
                    "SELECT c.categories_id, CONCAT('c',c.categories_id) as `key`, cd.categories_name as title, if(c.categories_status=0,'dis_prod','') as `extraClasses`  " .
                    "FROM " . TABLE_CATEGORIES_DESCRIPTION . " cd, " . TABLE_CATEGORIES . " c " .
                    " left join " . TABLE_PLATFORMS_CATEGORIES . " pc on pc.categories_id=c.categories_id and pc.platform_id='" . (int) $platform_id . "' " .
                    "WHERE cd.categories_id=c.categories_id and cd.language_id='" . $languages_id . "' AND cd.affiliate_id=0 and c.parent_id='" . (int) $params['category_id'] . "' " .
                    "order by c.sort_order, cd.categories_name"
            );

            $response_data['categories_tree'] = [];
            $children = [];
            while ($_categories = tep_db_fetch_array($get_categories_r)) {
                $children = [];
                $_categories['folder'] = true;
                //$_categories['lazy'] = true;
                $_categories['selected'] = false;
                $get_products_r = tep_db_query(
                        "SELECT concat('p',p.products_id,'_',p2c.categories_id) AS `key`, pd.products_name as title, if(p.products_status=0,'dis_prod','') as `extraClasses`  " .
                        //"IF(pp.products_id IS NULL, 0, 1) AS selected ".
                        "from " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c, " . TABLE_PRODUCTS . " p " .
                        "inner join " . TABLE_PLATFORMS_PRODUCTS . " pp on pp.products_id=p.products_id and pp.platform_id='" . $platform_id . "' " .
                        "WHERE pd.products_id=p.products_id and pd.language_id='" . $languages_id . "' and pd.platform_id='".intval(\common\classes\platform::defaultId())."' and p2c.products_id=p.products_id and p2c.categories_id='" . (int) $_categories['categories_id'] . "' " .
                        //($active? " AND p.products_status=1 " . Product::get_sql_product_restrictions(array('p', 'pd', 's', 'sp', 'pp')) . " ":"") .
//            (tep_not_null($search)?" and pd.products_name like '%{$search}%' " :"").
                        "order by p.sort_order, pd.products_name"
                );
                if (tep_db_num_rows($get_products_r) > 0) {
                    while ($_product = tep_db_fetch_array($get_products_r)) {
                        $children[] = $_product;
                    }
                }
                $this->tep_get_category_children($children, $platform_id, $_categories['categories_id']);
                if (count($children)) {
                    $_categories['children'] = $children;
                }
                $response_data['categories_tree'][] = $_categories;
            }
            //echo '<pre>';print_r($children);die;
            if (!$params['category_id']) {
                $get_products_r = tep_db_query(
                        "SELECT concat('p',p.products_id,'_',p2c.categories_id) AS `key`, pd.products_name as title, if(p.products_status=0,'dis_prod','') as `extraClasses` " .
                        //"IF(pp.products_id IS NULL, 0, 1) AS selected ".
                        "from " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c, " . TABLE_PRODUCTS . " p " .
                        "inner join " . TABLE_PLATFORMS_PRODUCTS . " pp on pp.products_id=p.products_id and pp.platform_id='" . $platform_id . "' " .
                        "WHERE pd.products_id=p.products_id and pd.language_id='" . $languages_id . "' and pd.platform_id='".intval(\common\classes\platform::defaultId())."' and p2c.products_id=p.products_id and p2c.categories_id='" . (int) $params['category_id'] . "' " .
                        //($active? " AND p.products_status=1 " . Product::get_sql_product_restrictions(array('p', 'pd', 's', 'sp', 'pp')) . " ":"") .
                        //            (tep_not_null($search)?" and pd.products_name like '%{$search}%' " :"").
                        "order by p.sort_order, pd.products_name"
                );
                if (tep_db_num_rows($get_products_r) > 0) {
                    while ($_product = tep_db_fetch_array($get_products_r)) {
                        $response_data['categories_tree'][] = $_product;
                    }
                }
            }
            //echo '<pre>';print_r($response_data['categories_tree']);
        }

        return $response_data;
    }

    /* moved to parent
    private function tep_get_category_children(&$children, $platform_id, $categories_id) {
        if (!is_array($children))
            $children = array();
        foreach ($this->loadTree(['platform_id' => $platform_id, 'category_id' => $categories_id])['categories_tree'] as $item) {
            //$key = $item['key'];
            $children[] = $item;
            if ($item['folder']) {
                $this->tep_get_category_children($children, $platform_id, intval($item['categories_id']));
            }
        }
    }*/

    public function savePromotions($promo_id = 0) {

        $ids = [];
        $saved_ids = [];

        if (is_array($this->vars['categories_id']['master'])) {
            $saved_ids[PromotionService::MASTER_CATEGORY] = $this->vars['categories_id']['master'];
            if (isset($this->vars['cat_master_qty']) && count($this->vars['cat_master_qty'])) {
                foreach ($saved_ids[PromotionService::MASTER_CATEGORY] as $idx => $id) {
                    $saved_ids[PromotionService::MASTER_CATEGORY][$idx] = ['id' => $id, 'quantity' => (int) $this->vars['cat_master_qty'][$id]];
                }
            }
        }

        if (is_array($this->vars['products_id']['master'])) {
            $saved_ids[PromotionService::MASTER_PRODUCT] = $this->vars['products_id']['master'];
            if (isset($this->vars['prod_master_qty']) && count($this->vars['prod_master_qty'])) {
                foreach ($saved_ids[PromotionService::MASTER_PRODUCT] as $idx => $id) {
                    $saved_ids[PromotionService::MASTER_PRODUCT][$idx] = ['id' => $id, 'quantity' => (int) $this->vars['prod_master_qty'][$id]];
                }
            }
        }

        if (is_array($this->vars['categories_id']['slave'])) {
            $saved_ids[PromotionService::SLAVE_CATEGORY] = $this->vars['categories_id']['slave'];
        }

        if (is_array($this->vars['products_id']['slave'])) {
            $saved_ids[PromotionService::SLAVE_PRODUCT] = $this->vars['products_id']['slave'];
        }

        if (is_array($this->vars['condition'])) {
            $this->conditions[0] = [
                'promo_deduction' => (int) $this->vars['deduction'][0],
                'promo_condition' => (int) $this->vars['condition'][0],
                'promo_type' => (int) $this->vars['type'][0],
                'groups_id' => (int) 0,
            ];
            if ($this->enabledGroups) {
                foreach ($this->vars['condition'] as $groups_id => $value) {
                    if ($this->vars['use_settings'][$groups_id]) {
                        $this->conditions[$groups_id] = [
                            'promo_deduction' => (int) $this->vars['deduction'][$groups_id],
                            'promo_condition' => (int) $value,
                            'promo_type' => (int) $this->vars['type'][$groups_id],
                            'groups_id' => (int) $groups_id,
                        ];
                    } else {
                        $this->conditions[$groups_id] = $this->conditions[0];
                        $this->conditions[$groups_id]['groups_id'] = $groups_id;
                    }
                }
            }
        }

        return $saved_ids;
    }

    public function hasConditions() {
        return count($this->conditions);
    }

    public function getConditions() {
        return $this->conditions;
    }

    public function calculate() {
        $cart = $this->getCart();

        if (!count($this->vars['master']) || !count($cart->contents))
            return false;

        static $cart_map = null;
        if (is_null($cart_map)) {
            $cart_map = [];
            array_map(function($value) use (&$cart_map) {
                $cart_map[$value] = intval($value);
            }, array_keys($cart->contents));
        }

        $existed = array_intersect(array_values($cart_map), $this->vars['master']);

        if ($existed && count($existed)) {

            $this->clearMessage();

            if (!in_array((int) $this->vars['products_id'], $existed))
                return false;

            $product_price = $this->vars['product_price'];
            $special_price = $this->vars['special_price'];

            $pdQty = $cart->getQty($this->vars['products_id'], false);

            $neededQty = $this->checkMainMinimalQty($this->vars['products_id'], $pdQty);

            if (!$neededQty)
                return false;

            if (!$special_price) {
                $tmp_special_price = $product_price;
            } else {
                $tmp_special_price = $special_price;
            }

            switch ($this->vars['promo_type']) {
                case 0: //disabled by default
                    $sale_product_price = $product_price - $this->vars['promo_deduction'];
                    $sale_special_price = $tmp_special_price - $this->vars['promo_deduction'];
                    break;
                case 1:
                    $dicounted = floor($pdQty / $neededQty);
                    $not_discounted = $pdQty - $dicounted;
                    $discountedPrice = $product_price - (($product_price * $this->vars['promo_deduction']) / 100);
                    $sale_product_price = (($product_price * $not_discounted) + ($discountedPrice * $dicounted)) / $pdQty;
                    $discountedSPrice = $tmp_special_price - (($tmp_special_price * $this->vars['promo_deduction']) / 100);
                    $sale_special_price = (($tmp_special_price * $not_discounted) + ($discountedSPrice * $dicounted)) / $pdQty;
                    break;
                case 2: //disabled by default
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
                if ($sale_product_price >= $product_price) {
                    return false;
                } else {
                    return number_format($sale_product_price, 4, '.', '');
                }
            } else {

                switch ($this->vars['promo_condition']) {
                    case 0:
                        if ($sale_product_price >= $product_price) {
                            return false;
                        } else {
                            return number_format($sale_product_price, 4, '.', '');
                        }
                        break;
                    case 1:
                        if ($special_price >= $product_price) {
                            return false;
                        } else {
                            return number_format($special_price, 4, '.', '');
                        }
                        break;
                    case 2:
                        if ($sale_special_price >= $product_price) {
                            return false;
                        } else {
                            return number_format($sale_special_price, 4, '.', '');
                        }
                        break;
                    default:
                        if ($special_price >= $product_price) {
                            return false;
                        } else {
                            return number_format($special_price, 4, '.', '');
                        }
                }
            }
            //return $sale_product_price;
        }
        return false;
    }

    public function checkMainMinimalQty($pid, $inCartQty) {
        $cart = $this->getCart();
        $pid = (int) $pid;
        if (is_array($this->vars['details'])) {
            //check products
            if (is_array($this->vars['details']['master']['products'])) {
                if (isset($this->vars['details']['master']['products'][$pid])) {
                    $_qty = $this->vars['details']['master']['products'][$pid];
                    if ($inCartQty >= $_qty) {
                        $this->setMessage();
                        return $_qty;
                    } else {
                        return false;
                    }
                }
            }

            if (is_array($this->vars['details']['master']['categories'])) { // categories                                
                foreach ($this->vars['details']['master']['categories'] as $cid => $data) {
                    $intersect_in_category = array_intersect([$pid], $data['ids']);
                    if ($intersect_in_category) {
                        $_qty = (int) $data['qty'];
                        if ($inCartQty >= $_qty) {
                            $this->setMessage();
                            return $_qty;
                            break;
                        } else {
                            return false;
                        }
                    }
                }
            }
        }
        return false;
    }

    public function setMessage() {
        PromotionService::createMessage(TEXT_CARTDISCOUNT_MESSAGE);
    }

    public function clearMessage() {
        parent::clearMessage();
    }

    public function getPromotionInfo($promo_id) {
        $salemaker_array = \common\components\Salemaker::init();
        $current_salemakers = \yii\helpers\ArrayHelper::index($salemaker_array, 'promo_id');
        if (!isset($current_salemakers[$promo_id]))
            return;
        $current_salemaker = $current_salemakers[$promo_id];
        //echo '<pre>';print_r($current_salemaker);

        if (isset($this->settings['assigned_items'])) {
            if (isset($this->settings['assigned_items']['master'])) {
                foreach ($this->settings['assigned_items'] as $type => &$items) {
                    foreach ($items as $idx => &$item) {
                        $info= current($item);
                        if ($idx == 'product') {
                            $info->href = tep_href_link('catalog/product', 'products_id=' . $info->id);
                            $info->image = Images::getImage($info->id, 'Medium');
                        }
                        if ($idx == 'category') {
                            $info->href = tep_href_link('catalog', Categories::get_path($info->id));
                            $info->image = Media::getAlias('@webCatalogImages/' . basename($info->image));
                            if (!is_file(Yii::getAlias('@webroot') . '/images/' . basename($info->image))) {
                                $info->image = '';
                            }
                        }
                        
                        if (isset($current_salemaker['conditions'])) {
                            $condition = $current_salemaker['conditions'];
                            if ($idx == 'category') {
                                $first_prid = $current_salemaker['details']['master']['categories'][$info->id]['ids'][0];
                                $product_tax_class_id = Product::get_products_info($first_prid, 'products_tax_class_id');
                            } else {
                                $product_tax_class_id = Product::get_products_info($info->id, 'products_tax_class_id');
                            }
                            switch ($condition['promo_type']) {
                                case 0:
                                    //$info->condition_string = $currenices->display_price($condition['promo_deduction'], \common\helpers\Tax::get_tax_rate($product_tax_class_id));
                                    break;
                                case 1:
                                    $info->condition_string = $condition['promo_deduction'];
                                    break;
                                case 2:
                                    //$info->condition_string = " You may buy for " . $currenices->display_price($condition['promo_deduction'], \common\helpers\Tax::get_tax_rate($product_tax_class_id));
                                    break;
                            }
                        }
                        
                    }
                }
            }
            $this->settings['promo_deduction'] = $current_salemaker['conditions']['promo_deduction'];

            return \common\models\promotions\widgets\NextDiscount::widget(['details' => $this->settings]);
        }
        return;
    }

}
