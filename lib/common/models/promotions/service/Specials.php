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
use common\api\models\AR\Products;
use common\api\models\AR\Group;
use common\helpers\Categories;
use common\helpers\Product;
use common\classes\Images;
use common\models\promotions\Promotions;
use common\models\promotions\PromotionsSets;

/*DEPRICATED*/
class Specials extends ServiceAbstract implements ServiceInterface {

    public $product;
    protected $vars;
    public $useMarketPrices;
    public $enabledGroups;
    public $groups = false;
    public $currencies = false;
    public $defaultCurrency;
    public $settings = [];

    public function __construct() {
        $this->useMarketPrices = defined('USE_MARKET_PRICES') && USE_MARKET_PRICES == 'True';
        if ($this->useMarketPrices) {
            $currencies = Yii::$container->get('currencies');
            $this->defaultCurrency = $currencies->currencies[DEFAULT_CURRENCY]['id'];
            $this->currencies = [];
            $this->currencies[$currencies->currencies[DEFAULT_CURRENCY]['id']] = $currencies->currencies[DEFAULT_CURRENCY]['title'];
            foreach ($currencies->currencies as $cur => $value) {
                if ($cur == DEFAULT_CURRENCY)
                    continue;
                $this->currencies[$currencies->currencies[$cur]['id']] = $currencies->currencies[$cur]['title'];
            }
        }

        $this->enabledGroups = defined('CUSTOMERS_GROUPS_ENABLE') && CUSTOMERS_GROUPS_ENABLE == 'True';
        if ($this->enabledGroups) {
            $this->groups = array_map(function ($el) { return (object)$el; },  \common\helpers\Group::get_customer_groups(self::DEFAULT_GROUP_TYPE));
        }
    }

    public function rules() {
        return ['product'];
    }

    public function getDescription() {
        return SPECIAL_PRICE_ON_SINGLE_PRODUCT;
    }

    public function useTranslation() {
        \common\helpers\Translation::init('admin/specials');
        \common\helpers\Translation::init('admin/categories');
    }

    public function getSettingsTemplate() {
        return 'specials_product/specials_product.tpl';
    }

    public function loadSettings($params) {
        $tree = [];
        $platform_id = (isset($params['platform_id']) ? $params['platform_id'] : 0);
        $this->settings['tree'] = \common\helpers\Categories::get_full_category_tree(0, '', '', $tree, true, $platform_id, false);
        $this->settings['disable_categories'] = true;
        $this->settings['searchsuggest'] = false;
        $this->settings['assigned_products'] = [];
        $this->settings['hide_promo_start_date'] = true;
        if (isset($params['promo_id'])) {
            $set = PromotionsSets::find()->where(['promo_id' => $params['promo_id']])->all();
            if (is_array($set)) {
                foreach ($set as $item) {
                    $this->settings['assigned_products'][] = $this->addItem(['item_id' => 'prod_' . $item->promo_slave_id]);
                }
            }
            //echo '<pre>';print_r($this->settings['assigned_products']);die;
        }
    }

    public function getSpecials($params) {
        $products_id = $params['products_id'];
        $response = [];
        if ($products_id) {
            return Yii::$app->controller->renderAjax('specials_product/specials.tpl', [
                        'product' => Products::find()->where(['products_id' => $products_id])->one(),
                        'specials' => tep_db_fetch_array(tep_db_query("select * from " . TABLE_SPECIALS . " where products_id = '" . (int) $products_id . "'")),
                        'useMarketPrices' => $this->useMarketPrices,
                        'enabledGroups' => $this->enabledGroups,
                        'currencies' => $this->currencies,
                        'defaultCurrency' => $this->defaultCurrency,
                        'groups' => $this->groups,
            ]);
        }
    }

    public function addItem($params) {
        $item = $params['item_id'];
        $ex = explode("_", $item);
        sort($ex);
        $response = [];
        if (count($ex)) {
            $product = new \stdClass();
            $product->product_id = (int) $ex[0];
            $product->name = Product::get_products_name($product->product_id);
            $product->image = Images::getImageUrl($product->product_id);

            $response = [
                'product' => $product,
            ];
        }
        return $response;
    }

    public function load($details) {
        if (is_array($details)) {
            foreach ($details as $key => $value) {
                if (in_array($key, ['promo_status', 'specials_price_full', 'promo_date_expired'])) {
                    $details[$key] = $value;
                    continue;
                }
                if (!is_array($value)) {
                    $details[$key] = [];
                    $details[$key][] = $value;
                }
            }
            $this->vars = $details;
            return true;
        }
        return false;
    }

    public function savePromotions($promo_id = 0) {
        $ids = [];
        $saved_ids = [];
        if ($promo_id) {
            $sets = PromotionsSets::find()->where(['promo_id' => $promo_id])->all();
            if (is_array($sets)) {
                foreach ($sets as $set) {
                    $ids[] = $set->promo_slave_id;
                }
            }
        }
        $end_date = null;
        if (isset($this->vars['promo_date_expired']) && !empty($this->vars['promo_date_expired'])){
            $specials_expires_date_full = \common\helpers\Date::prepareInputDate($this->vars['promo_date_expired']);
        }
        $specials_price_full = null;
        if (isset($this->vars['specials_price_full']) && !empty($this->vars['specials_price_full'])){            
            $specials_price_full = $this->vars['specials_price_full'];
        }
        
        $status = (int) $this->vars['promo_status'] ? $this->vars['promo_status'] : 0;
        if ($this->useMarketPrices) {
            if (is_array($this->vars['products_id'])) {
                foreach ($this->vars['products_id'] as $key => $products_id) {
                    $specials_id = null;
                    if (isset($this->vars['specials_price'][$products_id])) {

                        $specials_expires_date = $this->vars['specials_expires_date'][$products_id];
                        if (!empty($specials_expires_date)) {
                            $specials_expires_date = \common\helpers\Date::prepareInputDate($specials_expires_date);
                        }
                        if (!is_null($specials_expires_date_full) && !is_null($specials_expires_date) && strtotime($specials_expires_date_full) > strtotime($specials_expires_date)) {
                            $specials_expires_date = $specials_expires_date_full;
                        }
                        $check = tep_db_fetch_array(tep_db_query("select specials_id from " . TABLE_SPECIALS . " where products_id = '" . (int) $products_id . "'"));
                        if ($check['specials_id'] > 0) {
                            $specials_id = $check['specials_id'];
                            tep_db_query("update " . TABLE_SPECIALS . " set specials_new_products_price = '0', specials_last_modified = now(), expires_date = '" . tep_db_input($specials_expires_date) . "', status = '" . $status . "' where specials_id = '" . (int) $specials_id . "'");
                        } else {
                            tep_db_query("insert into " . TABLE_SPECIALS . " set products_id = '" . (int) $products_id . "', specials_new_products_price = '0', specials_date_added = now(), expires_date = '" . tep_db_input($specials_expires_date) . "', status = '" . $status . "'");
                            $specials_id = tep_db_insert_id();
                        }
                        foreach ($this->currencies as $currID => $non) {
                            $specials_price = $this->vars['specials_price'][$products_id][$currID];
                            $products_price = (float) $this->vars['products_price'][$products_id][$currID];
                            if (substr($specials_price, -1) == '%') {
                                $specials_price = ($products_price - (($specials_price / 100) * $products_price));
                            }
                            Product::save_specials_prices($specials_id, 0, $currID, $specials_price);
                        }
                        if (is_array($this->groups) && $specials_id) {
                            foreach ($this->currencies as $currID => $non) {
                                $products_price = (float) $this->vars['products_price'][$products_id][$currID];
                                foreach ($this->groups as $group) {
                                    $special_group_price = (isset($this->vars['specials_groups_prices_' . $currID . '_' . $group->groups_id][$key]) ? $this->vars['specials_groups_prices_' . $currID . '_' . $group->groups_id][$key] : -2);
                                    if (substr($special_group_price, -1) == '%') {
                                        $special_group_price = ($products_price - (($special_group_price / 100) * $products_price));
                                    }
                                    Product::save_specials_prices($specials_id, $group->groups_id, $currID, $special_group_price);
                                }
                            }
                        }
                    }
                    $saved_ids[] = $products_id; //products in list
                    if (in_array($products_id, $ids)) {
                        $_tmp = array_flip($ids);
                        unset($_tmp[$products_id]);
                        $ids = array_flip($_tmp); //deleted products
                    }
                }
            }
        } else {
            if (is_array($this->vars['products_id'])) {
                foreach ($this->vars['products_id'] as $key => $products_id) {
                    $specials_id = null;
                    if (isset($this->vars['specials_price'][$key])) {
                        $specials_price = $this->vars['specials_price'][$key];
                        $products_price = (float) $this->vars['products_price'][$key];
                        if (substr($specials_price, -1) == '%') {
                            $specials_price = ($products_price - (($specials_price / 100) * $products_price));
                        }
                        if (!is_null($specials_price_full)){
                            if (substr($specials_price_full, -1) == '%') {
                                $specials_price = ($products_price - (($specials_price_full / 100) * $products_price));
                            }
                        }
                        $specials_expires_date = $this->vars['specials_expires_date'][$key];
                        if (!empty($specials_expires_date)) {
                            $specials_expires_date = \common\helpers\Date::prepareInputDate($specials_expires_date);
                        } 
                        if (!is_null($specials_expires_date_full) && !is_null($specials_expires_date) && strtotime($specials_expires_date_full) > strtotime($specials_expires_date)) {
                            $specials_expires_date = $specials_expires_date_full;
                        }
                        
                        $check = tep_db_fetch_array(tep_db_query("select specials_id from " . TABLE_SPECIALS . " where products_id = '" . (int) $products_id . "'"));
                        if ($check['specials_id'] > 0) {
                            $specials_id = $check['specials_id'];
                            tep_db_query("update " . TABLE_SPECIALS . " set specials_new_products_price = '" . (float) $specials_price . "', specials_last_modified = now(), expires_date = '" . tep_db_input($specials_expires_date) . "', status = '" . $status . "' where specials_id = '" . (int) $specials_id . "'");
                        } else {
                            tep_db_query("insert into " . TABLE_SPECIALS . " set products_id = '" . (int) $products_id . "', specials_new_products_price = '" . (float) $specials_price . "', specials_date_added = now(), expires_date = '" . tep_db_input($specials_expires_date) . "', status = '" . $status . "'");
                            $specials_id = tep_db_insert_id();
                        }
                        $saved_ids[] = $products_id;
                        if (in_array($products_id, $ids)) {
                            $_tmp = array_flip($ids);
                            unset($_tmp[$products_id]);
                            $ids = array_flip($_tmp);
                        }
                    }

                    if (is_array($this->groups) && $specials_id) {
                        foreach ($this->groups as $group) {
                            $special_group_price = (isset($this->vars['specials_groups_prices_' . $group->groups_id][$key]) ? $this->vars['specials_groups_prices_' . $group->groups_id][$key] : -2);
                            if (substr($special_group_price, -1) == '%') {
                                $special_group_price = ($products_price - (($special_group_price / 100) * $products_price));
                            }
                            Product::save_specials_prices($specials_id, $group->groups_id, 0, $special_group_price);
                        }
                    }
                }
            }
        }
        if (count($ids)) {
            foreach ($ids as $id) {
                tep_db_query("update " . TABLE_SPECIALS . " set status = '0', specials_last_modified = now() where products_id = '" . (int) $id . "'");
            }
        }
        return ['0' => $saved_ids]; //0 - products, 1 - categories, 2 - brands
    }
    
    public function hasConditions(){
        return false;
    }

}
