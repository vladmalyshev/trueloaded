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
use common\helpers\Product;
use common\classes\Images;
use common\models\promotions\Promotions;
use common\models\promotions\PromotionsSets;
use common\models\promotions\PromotionService;
use common\models\promotions\PromotionsSetsConditions;

class PersonalGift extends ServiceAbstract implements ServiceInterface {

    public $product;
    protected $vars;    
    public $currencies = false;
    public $defaultCurrency;
    public $settings = [];
    private $item_types = [
        PromotionService::SLAVE_PRODUCT => 'p',        
    ];
    
    CONST MAX_LOADING_ITEMS = 5000;

    public function __construct() {
        $currencies = Yii::$container->get('currencies');
        $this->defaultCurrency = $currencies->currencies[DEFAULT_CURRENCY]['id'];
    }

    public function rules() {
        return ['product'];
    }

    public function getDescription() {
        return PERSONAL_GIFT_ON_PURCHASE;
    }
    
    public function getPromoFullDescription(){
        return (defined('TEXT_PERSONALGIFT_FULL_DESC')?TEXT_PERSONALGIFT_FULL_DESC:'');
    }

    public function useTranslation() {
        \common\helpers\Translation::init('admin/categories');
    }

    public function getSettingsTemplate() {
        return 'personal_gift/gift.tpl';
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
        
        $this->settings['promo_skip_date_range'] = 0;
        if ($this->settings['promo_id']){
            $dPromo = \common\models\promotions\Promotions::findOne(['promo_id' => $this->settings['promo_id'] ]);
            if ($dPromo){
                $this->settings['promo_skip_date_range'] = $dPromo->promo_type;
                $this->settings['auto_push'] = $dPromo->auto_push;
            }
        }
        
        $this->settings['assigned_items'] = [];
        $this->settings['sets_conditions'] = [
            'category' => [],
            'product' => [],
        ];

        $this->settings['hash'] = [];

        if (isset($params['promo_id'])) {
            $set = PromotionsSets::find()->where(['promo_id' => $params['promo_id']])->orderBy('promo_sets_id')->all();
            if (is_array($set)) {
                foreach ($set as $item) {
                    $scd = PromotionsSetsConditions::find()->where(['promo_sets_id' => $item->promo_sets_id])->all();
                    if ($scd) {
                        foreach($scd as $_scd){
                            $assigned = $this->addItem(['type' => $this->item_types[$item->promo_slave_type], 'item_id' => $item->promo_slave_id, 'asset' => $_scd->promotions_sets_conditions_hash, 'quantity' => (int)$_scd->promotions_sets_conditions_discount, 'sets_conditions' =>  $_scd]);
                            //$this->settings['sets_conditions']['product'][$item->promo_slave_id] = $_scd;/
                            array_push($this->settings['assigned_items'], $assigned);
                        }
                    }
                }
            }
            //echo '<pre>';print_r($this->settings['hash']);die;
        }
        $this->settings['conditions'] = []; //saved conditions
        
        $this->settings['useMarketPrices'] = $this->useMarketPrices;
        $this->settings['currencies'] = $this->currencies;
        $this->settings['defaultCurrency'] = $this->defaultCurrency;
        
    }

   
    public function addItem($params) {
        $response = [];
        $item_id = $params['item_id'];
        $type = $params['type'];
        $quantity = isset($params['quantity']) ? $params['quantity'] : 1;
        $amount = isset($params['amount']) ? $params['amount'] : 0;
        $asset = isset($params['asset']) ? $params['asset'] : 0;
        $currencies = Yii::$container->get('currencies');
        if ($type == 'p') {
            $product = new \stdClass();
            $product->id = (int) $item_id;
            $product->quantity = $quantity;
            $product->amount = $amount;

            if (($special_price = Product::get_products_special_price($product->id)) !== false) {
                $price = $special_price;
            } else {
                $price = Product::get_products_price($product->id);
            }
            $product->assets = null;
            if (Product::hasAssets($product->id)){
                $assets = Product::getAssets($product->id,[]);
                if (is_array($assets)){
                    $values = [];
                    foreach($assets as $_asset){
                        $values[] = array_pop($_asset->assetValues);
                    }
                    $product->assets = '<div>Personal Asset'. \yii\helpers\Html::dropDownList('prod_asset['.$product->id.'][]', $asset, \yii\helpers\ArrayHelper::map($values, 'products_assets_id', 'products_assets_value'), ['class' => 'form-control', 'prompt'=> PULL_DOWN_DEFAULT ]) .'</div>';
                }
            }
            $product->name = "Product: " . Product::get_products_name($product->id) . (\frontend\design\Info::isTotallyAdmin()? "<br>Base Price: " . $currencies->display_price($price, false):'' );
            $product->image = Images::getImageUrl($product->id);
            $product->sets_conditions = $params['sets_conditions'];
            $response = [
                'product' => $product,
            ];
        }
        return $response;
    }
    
    public function getAssetData(\common\models\promotions\Promotions $promoRecord, $uprid){
        $set = $promoRecord->getSets()->where(['promo_slave_id' => (int)$uprid])->one();
        if ($set){
            return Product::getAsset($set->promo_hash);
        }
        return null;
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
                        "SELECT concat('p',p.products_id,'_',p2c.categories_id) AS `key`, p.products_id,  pd.products_name as title, if(p.products_status=0,'dis_prod','') as `extraClasses`  " .
                        //"IF(pp.products_id IS NULL, 0, 1) AS selected ".
                        "from " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c, " . TABLE_PRODUCTS . " p " .
                        "inner join " . TABLE_PLATFORMS_PRODUCTS . " pp on pp.products_id=p.products_id and pp.platform_id='" . $platform_id . "' " .
                        "WHERE pd.products_id=p.products_id and p.is_bundle=0 and pd.language_id='" . $languages_id . "' and pd.platform_id='".intval(\common\classes\platform::defaultId())."' and p2c.products_id=p.products_id and p2c.categories_id='" . (int) $_categories['categories_id'] . "' " .
                        //($active? " AND p.products_status=1 " . Product::get_sql_product_restrictions(array('p', 'pd', 's', 'sp', 'pp')) . " ":"") .
//            (tep_not_null($search)?" and pd.products_name like '%{$search}%' " :"").
                        "order by p.sort_order, pd.products_name"
                );
                if (tep_db_num_rows($get_products_r) > 0) {
                    while ($_product = tep_db_fetch_array($get_products_r)) {
                        if (!\common\helpers\Attributes::has_product_attributes($_product['products_id'])){
                            $children[] = $_product;
                        }
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
                        "SELECT concat('p',p.products_id,'_',p2c.categories_id) AS `key`, p.products_id, pd.products_name as title, if(p.products_status=0,'dis_prod','') as `extraClasses` " .
                        //"IF(pp.products_id IS NULL, 0, 1) AS selected ".
                        "from " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c, " . TABLE_PRODUCTS . " p " .
                        "inner join " . TABLE_PLATFORMS_PRODUCTS . " pp on pp.products_id=p.products_id and pp.platform_id='" . $platform_id . "' " .
                        "WHERE pd.products_id=p.products_id and p.is_bundle=0 and pd.language_id='" . $languages_id . "' and pd.platform_id='".intval(\common\classes\platform::defaultId())."' and p2c.products_id=p.products_id and p2c.categories_id='" . (int) $params['category_id'] . "' " .
                        //($active? " AND p.products_status=1 " . Product::get_sql_product_restrictions(array('p', 'pd', 's', 'sp', 'pp')) . " ":"") .
                        //            (tep_not_null($search)?" and pd.products_name like '%{$search}%' " :"").
                        "order by p.sort_order, pd.products_name"
                );
                if (tep_db_num_rows($get_products_r) > 0) {
                    while ($_product = tep_db_fetch_array($get_products_r)) {
                        if (!\common\helpers\Attributes::has_product_attributes($_product['products_id'])){
                            $response_data['categories_tree'][] = $_product;
                        }
                    }
                }
            }
            //echo '<pre>';print_r($response_data['categories_tree']);
        }

        return $response_data;
    }

    public function load($details) {
        if (is_array($details)) {
            $this->vars = $details;
            return true;
        }
        return false;
    }

    public function savePromotions($promo_id = 0) {
        $ids = [];
        $saved_ids = [];
        $_hash = [];
        if (is_array($this->vars['products_id'])) {
            foreach($this->vars['products_id'] as  $idx => $id){
                $saved_ids[PromotionService::SLAVE_PRODUCT][$id] = ['id' => $id,];// 'quantity' => (int)$this->vars['prod_quantity'][$id], 'hash' => (isset($this->vars['prod_asset'][$id])?(int)$this->vars['prod_asset'][$id]:0 )
            }
            if (is_array($this->vars['prod_amount'])) {
                foreach ($this->vars['prod_amount'] as $prid => $amountData) {
                    foreach ($amountData as $_idx => $amount){
                        $this->sets_conditions[PromotionService::SLAVE_PRODUCT . "_" . $prid][] = [
                            'promotions_sets_conditions_currency_id' => (int) $this->defaultCurrency,
                            'promotions_sets_conditions_amount' => (float) $amount,
                            'promotions_sets_conditions_discount' => (int)$this->vars['prod_quantity'][$prid][$_idx],
                            'promotions_sets_conditions_hash' => (isset($this->vars['prod_asset'][$prid][$_idx])?(int)$this->vars['prod_asset'][$prid][$_idx]:0) //has will be used as flag used condition
                        ];
                    }
                }
            }
        }

        return $saved_ids;   
    }
    
    public function hasConditions(){
        return false;
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
    
    public function calculate(){
        return false;
    }
    
    public function calculateAfter() {
        return false;
    }
    
    private $message;
    
    private function getCartTotal(\common\classes\shopping_cart $cart, Promotions $promo) {
        if ($promo->auto_push){ //excVat mode
            $currencies = \Yii::$container->get('currencies');
            $total = 0;
            foreach($cart->get_products() as $product){
                $total += $currencies->calculate_price($product['final_price'], 0, $product['quantity']);
            }
            return $total;
        } else {
            return $cart->show_total();
        }
    }
    
    public function getPersonalCondition($customer, $promo_id){
        $promo = Promotions::findOne(['promo_id' => $promo_id]);
        
        if ($promo && $customer){
            $pcc = \common\models\promotions\PromotionsCustomerCodes::find()
                    ->where(['customer_id' => $customer->customers_id, 'promo_id' => $promo->promo_id])->one();
            if ($pcc) return false;
            
            $cart = $this->getCart();
            
            $total = $this->getCartTotal($cart, $promo);
            if (!$promo->promo_type){ //if not skip order history
                if ($promo->auto_push){
                    $total += $customer->fetchOrderTotalAmount(false, $promo->promo_date_start, $promo->promo_date_expired);//with tax
                } else {
                    $total += $customer->fetchOrderTotalAmount(true, $promo->promo_date_start, $promo->promo_date_expired);//with tax
                }
            }
            
            if (is_array($this->vars['details']['slave']['products'])){
                $conditions = [];
                foreach($this->vars['details']['slave']['products'] as $products_id => $data){
                    if (isset($data['set_condition'])){
                        $data['set_condition'] = \common\models\promotions\PromotionsSetsConditions::findAll(['promo_id' => $promo->promo_id, 'promo_sets_id' => $data['set_condition']->promo_sets_id]);
                        if (\common\helpers\Product::check_product($products_id, false) && !\common\helpers\Attributes::has_product_attributes($products_id) && !\common\helpers\Product::get_products_info($products_id, 'is_bundle')){
                            foreach ($data['set_condition'] as $_set){
                                $conditions[$_set->promotions_sets_conditions_amount] = ['products_id' => $products_id, 'sets_id' => $_set->promo_sets_id, 'qty' => (int)$_set->promotions_sets_conditions_discount];
                            }
                        }
                    }
                }
                
                if ($conditions){
                    ksort($conditions);
                    
                    $timeLineCondition = $conditions;
                    $canAdded = [];
                    foreach($conditions as $amount => $condition){
                        $amount = (float)$amount;
                        if ($amount <= $total){
                            $canAdded = $condition;
                            unset($conditions[$amount]);
                        }
                    }
                    
                    if ($canAdded && $canAdded['products_id']){
                        /*$got = \common\models\OrdersProducts::find()->where(['products_id' => (int)$canAdded['products_id'], 'promo_id' => $this->vars['promo_id'] ])
                                ->joinWith(['order' => function(\yii\db\ActiveQuery $query) use ($customer) { return $query->onCondition(['customers_id' => $customer->customers_id]); }] );
                        if (!is_null($promo->promo_date_start)) {$got->andWhere(['>=', 'date_purchased', $promo->promo_date_start]);}
                        if (!is_null($promo->promo_date_expired)) {$got->andWhere(['<=', 'date_purchased', $promo->promo_date_expired]);}
                        if ($got->one()) { //may add the same product
                            $this->clearGW();
                            return false;
                        }*/
                    } else {
                        $this->clearGW();
                    }
                    
                    $canAdded['used'] = count($conditions) == 0;
                    $canAdded['ordered'] = $total;
                    $canAdded['time_line'] = $timeLineCondition;
                    $canAdded['promo'] = $promo;
                    return $canAdded;
                }
            }
        }
        return false;
    }
    
    private function clearGW(){
        $cart = $this->getCart();
        if (is_array($cart->giveaway) && $this->vars['promo_id']){
            foreach($cart->giveaway as $pid => $value){
                if ($value['promo_id'] && $value['promo_id'] == $this->vars['promo_id']){
                    $cart->remove_giveaway($pid);
                }
            }
        }
    }

    public function personalize(){
        
        if (!Yii::$app->user->isGuest){
            $cart = $this->getCart();
            $customer = Yii::$app->user->getIdentity();
            
            $canAdded = $this->getPersonalCondition($customer, $this->vars['promo_id']);

            if ($canAdded && $canAdded['products_id']){
                $cart = $this->getCart();
                
                if (!$cart->in_giveaway($canAdded['products_id']) || $cart->get_quantity($canAdded['products_id'], true) != $canAdded['qty']){
                    $cart->giveaway = array();
                    $cart->giveaway[$canAdded['products_id']] = array('qty' => $canAdded['qty'], 'gaw_id' => 0, 'promo_id' => $this->vars['promo_id']);
                    if ($canAdded['used']){
                        \common\models\promotions\PromotionsCustomerCodes::saveCode($canAdded['promo'], $customer->customers_id);
                    }
                    if ($cart->in_giveaway($canAdded['products_id'])){
                        $this->message = TEXT_PERSONAL_GIFT;
                        return true;
                    }
                }
            }
        }
        return false;
    }
    
    public function getMessage(){
        return $this->message;
    }
}
