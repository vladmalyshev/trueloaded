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
use common\models\promotions\PromotionsSetsConditions;
use common\classes\Images;
use common\helpers\Categories;
use common\helpers\Product;
use common\helpers\Tax;
use common\helpers\Media;
use common\helpers\Properties;

class MultiDiscount extends ServiceAbstract implements ServiceInterface {

    //public $category;
    protected $vars;
    protected $cart;
    public $useMarketPrices;
    public $defaultCurrency;
    public $groups = false;
    public $sets_conditions = [];



    private $item_types = [
        PromotionService::SLAVE_CATEGORY => 'c',
        PromotionService::SLAVE_PRODUCT => 'p',
        PromotionService::SLAVE_PROPERTY => 'pr',
        PromotionService::SLAVE_PROPERTY_VALUE => 'pv',
    ];
    private $promo_products = [];

    CONST MAX_LOADING_ITEMS = 5000;

    public function __construct() {
        $this->useMarketPrices = defined('USE_MARKET_PRICES') && USE_MARKET_PRICES == 'True';
        $currencies = Yii::$container->get('currencies');
        $this->defaultCurrency = $currencies->currencies[DEFAULT_CURRENCY]['id'];
        $this->currencies = [];
        foreach ($currencies->currencies as $cur => $value) {
            $this->currencies[$currencies->currencies[$cur]['id']] = $currencies->currencies[$cur]['code'];
        }
    }

    public function rules() {
        return ['cart'];
    }

    public function getDescription() {
        return TEXT_MULTI_DISCOUNT;
    }
    
    public function getPromoFullDescription(){
        return (defined('TEXT_MULTIDISCOUNT_FULL_DESC')?TEXT_MULTIDISCOUNT_FULL_DESC:'');
    }

    public function useTranslation() {
        \common\helpers\Translation::init('admin/specials');
        \common\helpers\Translation::init('admin/categories');
    }

    public function getSettingsTemplate() {
        return 'multidiscount/multidiscount.tpl';
    }

    public function addItem($params) {
        $item_id = $params['item_id'];
        $type = $params['type'];
        $amount = isset($params['amount']) ? $params['amount'] : 0;
        $discount = isset($params['discount']) ? $params['discount'] : '';
        $hash = isset($params['hash']) ? $params['hash'] : 0;
        $qindex = isset($params['qindex']) ? $params['qindex'] : 0;
        $nindex = isset($params['nindex']) ? $params['nindex'] : 0;
        $quantity = isset($params['quantity']) ? $params['quantity'] : 1;
        $currencies = Yii::$container->get('currencies');

        $response = [];
        if ($type == 'p') {
            $product = new \stdClass();
            $product->id = (int) $item_id;
            $product->type = 'product';
            $product->amount = $amount;
            $product->quantity = $quantity;

            $price = 0;
            if (\frontend\design\Info::isTotallyAdmin()){
                if (($special_price = Product::get_products_special_price($product->id)) !== false) {
                    $price = $special_price;
                } else {
                    $price = Product::get_products_price($product->id);
                }
                $price = "<br>Base Price: " . $currencies->display_price($price, false);
            }
            
            $product->name = Product::get_products_name($product->id) . ($price ? $price : '' );
            $product->image = Images::getImageUrl($product->id, 'Medium');
            $product->discount = $discount;
            $product->hash = $hash;
            $product->qindex = $qindex;
            $product->nindex = $nindex;
            $product->already = [];
            if (isset($params['already_selected'])) {

                foreach ($params['already_selected'] as $items) {
                    if (substr($items, 0, 3) == 'cat') {
                        $current_cat = (int) substr($items, 4);
                        $subcategories = [$current_cat];

                        Categories::get_subcategories($subcategories, $current_cat);
                        $subcategories = array_values($subcategories);
                        if (count($subcategories)) {
                            $in = (new \yii\db\Query)->select(['products_id'])->from(TABLE_PRODUCTS_TO_CATEGORIES)
                                            ->where('products_id = :products_id', [':products_id' => $product->id])
                                            ->andWhere(['categories_id' => $subcategories])->count();
                            if ($in) {
                                $product->already[] = html_entity_decode(Categories::output_generated_category_path($current_cat));
                            }
                        }
                    }
                }
                //get_subcategories
            }
            $response = [
                'product' => $product,
            ];
        } elseif ($type == 'c') {
            $category = new \stdClass();
            $category->id = (int) $item_id;
            $category->type = 'category';
            $category->amount = $amount;
            $category->quantity = $quantity;
            $category->name = Categories::output_generated_category_path($category->id);
            $image = Categories::get_category_image($category->id);
            $category->image = '';
            $category->discount = $discount;
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
        } elseif ($type == 'pr'){ //properties
            $property = new \stdClass();
            $property->id = (int) $item_id;
            $property->type = 'property';
            $property->amount = $amount;
            $property->quantity = $quantity;
            $property->discount = $discount;
            $property->name = Properties::get_properties_name($property->id, Yii::$app->settings->get('languages_id'));
            $image = Properties::get_properties_image($property->id, Yii::$app->settings->get('languages_id'));
            $property->image = '';
            if ($image) {
                if (file_exists(Images::getFSCatalogImagesPath() . $image)) {
                    $property->image = Images::getWSCatalogImagesPath() . $image;
                }
            }
            $response = [
                'property' => $property,
            ];
        } elseif ($type == 'pv'){ //property values
            $prvalue = new \stdClass();
            $prvalue->id = (int) $item_id;
            $prvalue->type = 'property_value';
            $prvalue->quantity = $quantity;
            $prvalue->amount = $amount;
            $prvalue->discount = $discount;
            $prvalue->name = Properties::get_properties_value($prvalue->id, Yii::$app->settings->get('languages_id'))->values_text;
            $image = Properties::get_properties_value($prvalue->id, Yii::$app->settings->get('languages_id'))->values_image;
            $prvalue->image = '';
            if ($image) {
                if (file_exists(Images::getFSCatalogImagesPath() . $image)) {
                    $prvalue->image = Images::getWSCatalogImagesPath() . $image;
                }
            }
            $response = [
                'prvalue' => $prvalue,
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
        if ($this->useProperties){
            $properties = \common\helpers\Properties::getProperties();
            $this->settings['properties_tree'] = json_encode(\common\helpers\Properties::propertiesToFancy($properties, []));
        }

        $this->settings['assigned_items'] = [];
        $this->settings['sets_conditions'] = [
            'category' => [],
            'product' => [],
        ];

        $this->settings['hash'] = [];
        /*qindex => all products same qty, nindex => product is necessary*/
        if (isset($params['promo_id'])) {
            $set = PromotionsSets::find()->where(['promo_id' => $params['promo_id']])->orderBy('promo_hash, promo_sets_id')->all();
            if (is_array($set)) {
                foreach ($set as $item) {
                    $assigned = $this->addItem(['type' => $this->item_types[$item->promo_slave_type], 'item_id' => $item->promo_slave_id, 'hash' => $item->promo_hash, 'quantity' => $item->promo_quantity, 'qindex' => $item->promo_qindex,  'nindex' => $item->promo_nindex]);
                    $_type = key($assigned);
                    $object = current($assigned);
                    if (property_exists($object, 'hash') && $object->hash) {
                        $this->settings['hash'][$object->hash][] = $object->id;
                    }
                    $this->settings['assigned_items'][] = $assigned;
                    $scd = PromotionsSetsConditions::find()->where(['promo_sets_id' => $item->promo_sets_id])->all();
                    if ($scd) {
                        if ($item->promo_slave_type == PromotionService::SLAVE_CATEGORY) {
                            $this->settings['sets_conditions']['category'][$item->promo_slave_id] = $scd;
                        } else if ($item->promo_slave_type == PromotionService::SLAVE_PRODUCT){
                            $this->settings['sets_conditions']['product'][$item->promo_slave_id] = $scd;
                        } else if ($item->promo_slave_type == PromotionService::SLAVE_PROPERTY){
                            $this->settings['sets_conditions']['property'][$item->promo_slave_id] = $scd;
                        } else if ($item->promo_slave_type == PromotionService::SLAVE_PROPERTY_VALUE){
                            $this->settings['sets_conditions']['property_value'][$item->promo_slave_id] = $scd;
                        }
                    }
                }
            }
            //echo '<pre>';print_r($this->settings['hash']);die;
        }
        $this->settings['conditions'] = []; //saved conditions

        $this->settings['groups'] = $this->groups;

        $this->settings['useMarketPrices'] = $this->useMarketPrices;
        $this->settings['currencies'] = $this->currencies;
        $this->settings['defaultCurrency'] = $this->defaultCurrency;
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

    public function savePromotions($promo_id = 0) {

        $ids = [];
        $saved_ids = [];

        if (is_array($this->vars['categories_id'])) {
            foreach($this->vars['categories_id'] as $idx => $id){
                $saved_ids[PromotionService::SLAVE_CATEGORY][$idx] = ['id' => $id, 'quantity' => $this->vars['cat_quantity'][$id]];
            }
        }
        if ($this->useProperties){
            if (is_array($this->vars['properties_id'])) {
                foreach($this->vars['properties_id'] as $idx => $id){
                    $saved_ids[PromotionService::SLAVE_PROPERTY][$idx] = ['id' => $id, 'quantity' => $this->vars['pr_quantity'][$id]];
                }
            }

            if (is_array($this->vars['prvalues_id'])) {
                foreach($this->vars['prvalues_id'] as $idx => $id){
                    $saved_ids[PromotionService::SLAVE_PROPERTY_VALUE][$idx] = ['id' => $id, 'quantity' => $this->vars['prv_quantity'][$id]];
                }
            }
        }

        $_hash = [];
        if (is_array($this->vars['products_id'])) {
            $saved_ids[PromotionService::SLAVE_PRODUCT] = $this->vars['products_id'];
            if (isset($this->vars['products_hash']) && is_array($this->vars['products_hash'])) {
                $_hQty = [];
                foreach ($saved_ids[PromotionService::SLAVE_PRODUCT] as $idx => $id) {
                    if ($this->vars['prod_quantity'][$id]){
                        $_hQty[(int) $this->vars['products_hash'][$id]] = [
                            '_qty' => $this->vars['prod_quantity'][$id],
                            '_qindex' => (int)$this->vars['prod_qindex'][$id],
                            '_nindex' => (int)$this->vars['prod_nindex'][$id],
                        ];
                    }
                    $saved_ids[PromotionService::SLAVE_PRODUCT][$idx] = ['id' => $id, 'hash' => (int) $this->vars['products_hash'][$id], 'quantity' => $this->vars['prod_quantity'][$id], 'qindex' => $this->vars['prod_qindex'][$id], 'nindex' => $this->vars['prod_nindex'][$id]];
                    $_hash[(int) $this->vars['products_hash'][$id]][] = $id;
                }
                foreach ($saved_ids[PromotionService::SLAVE_PRODUCT] as &$sid){
                    if (isset($_hQty[$sid['hash']])){
                        $sid['quantity'] = (int)$_hQty[$sid['hash']]['_qty'];
                        $sid['qindex'] = (int)$_hQty[$sid['hash']]['_qindex'];
                        $sid['nindex'] = (int)$_hQty[$sid['hash']]['_nindex'];
                    }
                }
            } else {
                foreach ($saved_ids[PromotionService::SLAVE_PRODUCT] as $idx => $id) {
                    $saved_ids[PromotionService::SLAVE_PRODUCT][$idx] = ['id' => $id, 'quantity' => $this->vars['prod_quantity'][$id]];
                }
            }
        }

        if (is_array($this->vars['prod_amount'])) {
            if (count($_hash)) {
                foreach ($_hash as $_h => $prids) {
                    $copy_amount = 0;
                    $copy_discount = 0;
                    foreach ($prids as $prid) {
                        if (isset($this->vars['prod_amount'][$prid])) {
                            $copy_amount = $this->vars['prod_amount'][$prid];
                            $copy_discount = $this->vars['prod_discount'][$prid];
                            break;
                        }
                    }
                    foreach ($prids as $prid) {
                        if (!isset($this->vars['prod_amount'][$prid])) {
                            $this->vars['prod_amount'][$prid] = $copy_amount;
                            $this->vars['prod_discount'][$prid] = $copy_discount;
                        }
                    }
                }
            }
            if ($this->useMarketPrices) {
                foreach ($this->currencies as $id => $code) {
                    
                }
            } else {
                foreach ($this->vars['prod_amount'] as $prid => $amount) {
                    $this->sets_conditions[PromotionService::SLAVE_PRODUCT . "_" . $prid][] = [
                        'promotions_sets_conditions_currency_id' => (int) $this->defaultCurrency,
                        'promotions_sets_conditions_amount' => (float) $amount,
                        'promotions_sets_conditions_discount' => (float) (isset($this->vars['prod_discount'][$prid]) ? $this->vars['prod_discount'][$prid] : 0 ),
                        'promotions_sets_conditions_hash' => (isset($this->vars['products_hash'][$prid]) ? $this->vars['products_hash'][$prid] : 0)
                    ];
                }
            }
        }

        if (is_array($this->vars['cat_amount'])) {
            if ($this->useMarketPrices) {
                foreach ($this->currencies as $id => $code) {
                    //to do(:
                }
            } else {
                foreach ($this->vars['cat_amount'] as $cid => $amount) {
                    $this->sets_conditions[PromotionService::SLAVE_CATEGORY . "_" . $cid][] = [
                        'promotions_sets_conditions_currency_id' => (int) $this->defaultCurrency,
                        'promotions_sets_conditions_amount' => (float) $amount,
                        'promotions_sets_conditions_discount' => (float) (isset($this->vars['cat_amount'][$cid]) ? $this->vars['cat_discount'][$cid] : 0 )
                    ];
                }
            }
        }
        
        if ($this->useProperties){
            if (is_array($this->vars['pr_amount'])) {
                if ($this->useMarketPrices) {
                    foreach ($this->currencies as $id => $code) {
                        //to do(:
                    }
                } else {
                    foreach ($this->vars['pr_amount'] as $propId => $amount) {
                        $this->sets_conditions[PromotionService::SLAVE_PROPERTY . "_" . $propId][] = [
                            'promotions_sets_conditions_currency_id' => (int) $this->defaultCurrency,
                            'promotions_sets_conditions_amount' => (float) $amount,
                            'promotions_sets_conditions_discount' => (float) (isset($this->vars['pr_amount'][$propId]) ? $this->vars['pr_discount'][$propId] : 0 )
                        ];
                    }
                }
            }

            if (is_array($this->vars['prv_amount'])) {
                if ($this->useMarketPrices) {
                    foreach ($this->currencies as $id => $code) {
                        //to do(:
                    }
                } else {
                    foreach ($this->vars['prv_amount'] as $propvId => $amount) {
                        $this->sets_conditions[PromotionService::SLAVE_PROPERTY_VALUE . "_" . $propvId][] = [
                            'promotions_sets_conditions_currency_id' => (int) $this->defaultCurrency,
                            'promotions_sets_conditions_amount' => (float) $amount,
                            'promotions_sets_conditions_discount' => (float) (isset($this->vars['prv_amount'][$propvId]) ? $this->vars['prv_discount'][$propvId] : 0 )
                        ];
                    }
                }
            }
        }

        return $saved_ids;
    }

    public function getSetsConditions() {
        return $this->sets_conditions;
    }

    public function saveSetsConditions(PromotionsSets $set) {
        $conditions = $this->getSetsConditions();

        if (is_array($conditions) && is_object($set)) {
            $conditions = $conditions[$set->promo_slave_type . "_" . $set->promo_slave_id];
            if (is_array($conditions)) {
                foreach ($conditions as $key => $condition) {
                    $scd = new PromotionsSetsConditions();
                    $scd->setAttribute('promo_sets_id', $set->promo_sets_id);
                    $scd->setAttribute('promo_id', $set->promo_id);
                    $scd->setAttribute('promotions_sets_conditions_currency_id', $condition['promotions_sets_conditions_currency_id']);
                    $scd->setAttribute('promotions_sets_conditions_amount', $condition['promotions_sets_conditions_amount']);
                    $scd->setAttribute('promotions_sets_conditions_discount', $condition['promotions_sets_conditions_discount']);
                    $scd->setAttribute('promotions_sets_conditions_hash', $condition['promotions_sets_conditions_hash']);
                    if ($scd->validate() && !$scd->hasErrors()) {
                        $scd->save(false);
                    }
                }
            }
        }
    }

    public function hasConditions() {
        return false;
    }

    private function prepareCart() {
        $cart = $this->getCart();

        if (!empty($this->cart))
            return $this->cart;
        $currencies = \Yii::$container->get('currencies');
        $products = Yii::$container->get('products');
        foreach ($cart->contents as $ids => $v) {
            $product = $products->getProduct($ids);
            if ($product) {                
                $this->cart[(int) $ids]['sum'] += $currencies->calculate_price($product['standard_price'], Tax::get_tax_rate($product['tax_class_id']), $v['qty']);
                $this->cart[(int) $ids]['qty'] += $v['qty'];
                $this->cart[(int) $ids]['uprids'][] = $ids;
            }
        }
    }
    
    private function __collectSlaves($vars){
        if (is_array($vars)){
            foreach ($vars as $cid => $products_promotions) {
                if ($products_promotions['set_condition']){
                    $found = false;
                    $amount = 0;
                    $_qty = 0;
                    foreach ($products_promotions['set_condition'] as $prid => $conditions) {
                        if (isset($this->cart[$prid]) && $this->cart[$prid]['qty'] >= $products_promotions['qty']) {
                            $found = true;
                            $amount += $this->cart[$prid]['sum'];
                            $_qty = $this->cart[$prid]['qty'];
                        }
                    }
                    if ($found) {
                        foreach ($products_promotions['set_condition'] as $prid => $conditions) {
                            $this->promo_products[$this->priority][$prid][] = [
                                'amount' => $amount,
                                'qty' => $_qty,
                                'conditions' => [
                                    'set_condition' => $conditions,
                                    ]
                            ];
                        }
                    }
                }
            }
        }
    }

    private function prepareDiscount() {
        
        /*if (!empty($this->promo_products))
            return $this->promo_products;*/
        
        $this->prepareCart();
        
        //$this->promo_products = [];
        $this->__collectSlaves($this->vars['details']['slave']['categories']);
        if ($this->useProperties){
            $this->__collectSlaves($this->vars['details']['slave']['properties']);
            $this->__collectSlaves($this->vars['details']['slave']['properties_values']);
        }
        $products = Yii::$container->get('products');
        if (is_array($this->vars['details']['slave']['products'])) {
            $_hashed = [];
            foreach ($this->vars['details']['slave']['products'] as $products_id => $conditions) {
                if (isset($conditions['set_condition']['promotions_sets_conditions_hash']) && $conditions['set_condition']['promotions_sets_conditions_hash']) {
                    $_hashed[$conditions['set_condition']['promotions_sets_conditions_hash']][] = $products_id;
                }
            }
            
            foreach ($this->vars['details']['slave']['products'] as $products_id => $conditions) {
                if (isset($this->cart[$products_id])) {
                    if (isset($conditions['set_condition']['promotions_sets_conditions_hash']) && $conditions['set_condition']['promotions_sets_conditions_hash']) {
                        $_intersect = array_intersect($_hashed[$conditions['set_condition']['promotions_sets_conditions_hash']], array_keys($this->cart));
                        
                        $amount = 0;
                        $_qty = 0;
                        foreach ($_intersect as $pid) {
                            $amount += $this->cart[$pid]['sum'];
                            $_qty += $this->cart[$pid]['qty'];
                        }
                        
                        if (isset($_hashed[$conditions['set_condition']['promotions_sets_conditions_hash']]) && (($_intersect == $_hashed[$conditions['set_condition']['promotions_sets_conditions_hash']] && !$conditions['nindex']) || ($_qty >= $conditions['qty'] && $conditions['nindex'])) ) {
                            $toPromo = false;
                            if ($_qty >= $conditions['qty']){
                                $toPromo = true;
                                if ($conditions['qindex']){
                                    $qtyInCart = [];
                                    foreach ($_intersect as $pid) {
                                        $qtyInCart[] = $this->cart[$pid]['qty'];
                                        //$toPromo = $this->cart[$pid]['qty'] == $conditions['qty'] && $toPromo;
                                    }
                                    if ($qtyInCart){
                                        array_walk($qtyInCart, function($qty) use (&$toPromo, $qtyInCart){ $toPromo = ($qty == $qtyInCart[0]) && $toPromo; });
                                    }
                                }
                            } elseif ($amount >= $conditions['set_condition']['promotions_sets_conditions_amount'] && floatval($conditions['set_condition']['promotions_sets_conditions_amount'])>0 ) {
                                $toPromo = true;
                            }
                            if ($toPromo){
                                $this->promo_products[$this->priority][$products_id][] = [
                                    'amount' => $this->cart[$products_id]['sum'],
                                    'qty' => $this->cart[$products_id]['qty'],
                                    'conditions' => $conditions,
                                    'hash' => $conditions['set_condition']['promotions_sets_conditions_hash'],
                                ];
                            }
                        }
                    } else {
                        if ($this->cart[$products_id]['qty'] >= $conditions['qty']){
                            $this->promo_products[$this->priority][$products_id][] = [
                                'amount' => $this->cart[$products_id]['sum'],
                                'qty' => $this->cart[$products_id]['qty'],
                                'conditions' => $conditions,
                            ];
                        }
                    }
                }
            }
        }
        return $this->promo_products;
    }
    
    protected function canUseByPriority(){
        $products = Yii::$container->get('products');
        $canUse = true;
        $promotedGroup = $this->promo_products[$this->priority];
        foreach (array_keys($promotedGroup) as $products_id) {
            if (is_array($this->cart[$products_id]['uprids'])){
                foreach($this->cart[$products_id]['uprids'] as $urpid){
                    $product = $products->getProduct($urpid);
                    if ($product){
                        $canUse = (!$product['promo_priority'] || $this->priority <= $product['promo_priority'] ) && $canUse;
                    }
                }
            }
        }
        return $canUse;
    }
    
    public function calculateAfter() {
        
        $this->clearMessage();
        
        $cart = $this->getCart();

        if (!is_object($cart) || !count($this->vars['details']) || !count($cart->contents))
            return false;
        
        $products_id = $this->vars['products_id'];
        
        $this->prepareDiscount();
        
        if (is_array($this->promo_products[$this->priority]) && count($this->promo_products[$this->priority])) {
            $products = Yii::$container->get('products');
            $product = $products->getProduct($products_id);
            $promotedGroup = $this->promo_products[$this->priority];
            if (isset($promotedGroup[(int) $products_id])) {
                $promo_offered = false;
                
                foreach ($promotedGroup[(int) $products_id] as $details) {
                    if ($details['conditions']['set_condition']){
                        if (
                            ($details['amount'] >= $details['conditions']['set_condition']->promotions_sets_conditions_amount && floatval($details['conditions']['set_condition']->promotions_sets_conditions_amount) > 0)
                            || 
                            isset($details['hash'])
                            ||
                            $details['qty'] >= $details['conditions']['qty'])
                        {
                            if ($this->canUseByPriority()){
                                if ($this->vars['special_price']) {
                                    $price = $this->vars['special_price'];
                                    if ((int)$product['promo_priority'] > $this->priority){//better less
                                        $price = $product['standard_price'];
                                    }
                                } else {
                                    $price = $this->vars['product_price'];
                                }
                                //$this->setPromoPriority($product);

                                $price = $price - ($price * ($details['conditions']['set_condition']->promotions_sets_conditions_discount / 100));
                                if ($price < 0)
                                    $price = 0;
                                $promo_offered = true;
                            }
                        }
                    }
                }
                if ($promo_offered) {
                    $this->refuseRebate();
                    $this->setMessage();
                    return $price;
                }
            }
        }
        return false;
    }
    
    private function refuseRebate(){
        $products_id = $this->vars['products_id'];
        $products = Yii::$container->get('products');
        foreach (array_diff(array_keys($this->promo_products), [$this->priority]) as $priorityGroup){
            if (isset($this->promo_products[$priorityGroup][(int)$products_id])){
                foreach($this->promo_products[$priorityGroup] as $promo_products_id => $data){
                    if ($promo_products_id != (int)$products_id){
                        if (is_array($this->cart[$promo_products_id]['uprids'])){
                            foreach($this->cart[$promo_products_id]['uprids'] as $uprid){
                                $product = $products->getProduct($uprid);
                                if ($product){
                                    $promoPrice = \common\models\Product\PromotionPrice::getInstance($uprid);
                                    $promoPrice->setCalculateAfter(false);
                                    $product->removeDetails('promo_id');
                                    $product->removeDetails('promo_priority');
                                    $product->removeDetails('promo_type');
                                    $product->attachDetails(['final_price' => $product['standard_price'], 'special_price' => false, 'standard_price' => false]);
                                    $price = $promoPrice->getPromotionPrice();
                                    if ($price !== false){
                                        $product->attachDetails(['final_price' => $price, 'special_price' => $price, 'standard_price' => $product['final_price']]);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function setMessage() {
        PromotionService::createMessage(TEXT_MULTIDISCOUNT_MESSAGE);
    }

    public function clearMessage() {
        parent::clearMessage();
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
        $currenices = Yii::$container->get('currencies');
        $show = false;
        if (isset($this->settings['assigned_items']) && count($this->settings['assigned_items'])){
            foreach ($this->settings['assigned_items'] as $item){
                $key = key($item);
                $info = current($item);
                if ($key == 'category'){
                    if(is_null($this->identifiedrs['type']) || ($this->identifiedrs['type'] == PromotionService::SLAVE_CATEGORY && in_array($info->id, \common\helpers\Categories::getCategoryParentsIds($this->identifiedrs['id']))) ){
                        $show = true;
                        if (!is_null($this->identifiedrs['type'])){
                            $info->name = Categories::get_categories_name($this->identifiedrs['id']);
                            $info->href = tep_href_link('catalog', Categories::get_path($this->identifiedrs['id']));
                            $image = Categories::get_category_image($this->identifiedrs['id']);
                            if ($image) {
                                if (file_exists(Images::getFSCatalogImagesPath() . $image['categories_image'])) {
                                    $info->image = Images::getWSCatalogImagesPath() . $image['categories_image'];
                                } elseif (file_exists(Images::getFSCatalogImagesPath() . $image['categories_image_2'])) {
                                    $info->image = Images::getWSCatalogImagesPath() . $image['categories_image_2'];
                                }
                            }
                        } else {
                            $info->href = tep_href_link('catalog', Categories::get_path($info->id));
                            $info->image = Media::getAlias('@webCatalogImages/' . basename($info->image));
                        }
                        
                        if (!is_file(Yii::getAlias('@webroot') . '/images/' . basename($info->image))) {
                            $categories['img'] = 'no';
                        }
                        $info->condition = [
                            'amount_uf' => $this->settings['sets_conditions']['category'][$info->id][0]->promotions_sets_conditions_amount,
                            'amount' => $currenices->format($this->settings['sets_conditions']['category'][$info->id][0]->promotions_sets_conditions_amount),
                            'discount' => $this->settings['sets_conditions']['category'][$info->id][0]->promotions_sets_conditions_discount,
                            'quantity' => $info->quantity,
                        ];
                    }
                } else {
                    if(is_null($this->identifiedrs['type']) || ($this->identifiedrs['type'] == PromotionService::SLAVE_PRODUCT && $this->identifiedrs['id'] == $info->id) ){
                        $show = true;
                        $info->href = tep_href_link('catalog/product', 'products_id=' . $info->id);
                        $info->image = Images::getImageUrl($info->id, 'Medium');
                        $info->condition = [
                            'amount_uf' => $this->settings['sets_conditions']['product'][$info->id][0]->promotions_sets_conditions_amount,
                            'amount' => $currenices->format($this->settings['sets_conditions']['product'][$info->id][0]->promotions_sets_conditions_amount),
                            'discount' => $this->settings['sets_conditions']['product'][$info->id][0]->promotions_sets_conditions_discount,
                            'quantity' => $info->quantity,
                        ];
                    }
                }
            }
        }

        $countProductHash = 0;
        foreach ($this->settings['hash'] as $hash) {
            $countProductHash += count($hash);
        }
        $this->settings['one_discount'] = $countProductHash == count($this->settings['assigned_items']);

        if ($show){
            return \common\models\promotions\widgets\MultiDiscount::widget(['details' => $this->settings]);
        }
        return '';
    }
}
