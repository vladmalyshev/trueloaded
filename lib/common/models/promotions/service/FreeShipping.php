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
use common\helpers\Categories;
use common\helpers\Product;

class FreeShipping extends ServiceAbstract implements ServiceInterface {

    protected $vars;
    public $enabledGroups;
    public $groups = false;
    public $conditions = [];
    private $item_types = [
        PromotionService::SLAVE_CATEGORY => 'ca',
        PromotionService::SLAVE_PRODUCT => 'pr',
        PromotionService::SLAVE_MANUFACTURER => 'mn',
        PromotionService::SLAVE_COUNTRY => 'cn',
        PromotionService::SLAVE_ZONE => 'zo',
    ];

    CONST MAX_LOADING_ITEMS = 5000;

    public function __construct() {
        $this->useTranslation();
        $this->enabledGroups = defined('CUSTOMERS_GROUPS_ENABLE') && CUSTOMERS_GROUPS_ENABLE == 'True';
        if ($this->enabledGroups) {
            $this->groups = array_map(function ($el) {
                return (object) $el;
            }, \common\helpers\Group::get_customer_groups(self::DEFAULT_GROUP_TYPE));
        }
    }

    public function rules() {
        return ['cart', 'checkout'];
    }

    public function getDescription() {
        return FREE_SHIPPING_CONDITIONS;
    }

    public function getPromoFullDescription() {
        return (defined('FREE_SHIPPING_CONDITIONS_FULL_DESC')?FREE_SHIPPING_CONDITIONS_FULL_DESC:'');;
    }

    public function useTranslation() {
        \common\helpers\Translation::init('admin/promotions');
        \common\helpers\Translation::init('admin/categories');
    }

    public function getSettingsTemplate() {
        return 'free/free_shipping.tpl';
    }

    public function loadSettings($params) {
        $this->settings['promo_id'] = @$params['promo_id'];
        $platform_id = (isset($params['platform_id']) ? $params['platform_id'] : 0);
        $this->settings['platform_id'] = $platform_id;
        $fCats = PromotionsSets::find()->where(['promo_id' => $params['promo_id'], 'promo_slave_type' => [PromotionService::SLAVE_CATEGORY]])->asArray()->all();
        $fPros = PromotionsSets::find()->where(['promo_id' => $params['promo_id'], 'promo_slave_type' => [PromotionService::SLAVE_PRODUCT]])->asArray()->all();
        $assigned = \yii\helpers\ArrayHelper::getColumn($fCats, 'promo_slave_id', false);
        $assignedProds = \yii\helpers\ArrayHelper::getColumn($fPros, 'promo_slave_id', false);
        if (\frontend\design\Info::isTotallyAdmin()) {
            $categoree_tree = $this->loadTree(['platform_id' => $platform_id, 'category_id' => 0]);
            if (is_array($categoree_tree['categories_tree'])) {
                foreach ($categoree_tree['categories_tree'] as $idx => $branch) {
                    if (in_array($branch['categories_id'], $assigned)) {
                        $categoree_tree['categories_tree'][$idx]['selected'] = true;
                    }
                }
            }
            if ($assignedProds) {
                foreach ($assignedProds as $pp) {
                    $c = \common\helpers\Product::getCategories($pp);
                    if ($c) {
                        $assigned[] = 'p' . $pp . '_' . $c['categories_id'];
                    } else {
                        $assigned[] = 'p' . $pp;
                    }
                }
            }
        }
        $this->settings['categories_tree'] = $categoree_tree['categories_tree'];
        $this->settings['selected_categories'] = $assigned;
        $this->settings['selected_data'] = json_encode($assigned);

        $this->settings['manufacturers_tree'] = \common\helpers\Manufacturers::getManufacturersList();
        $this->settings['selected_manufacturers'] = [];

        $this->settings['countries_tree'] = \common\helpers\Country::new_get_countries();
        $this->settings['selected_countries'] = [];

        $this->settings['zones_tree'] = \yii\helpers\ArrayHelper::map(\common\models\GeoZones::find()->all(), 'geo_zone_id', 'geo_zone_name');
        $this->settings['selected_zones'] = [];

        $this->settings['conditions'] = [];
        if (isset($params['promo_id'])) {

            $this->settings['selected_manufacturers'] = PromotionsSets::find()->select('promo_slave_id')->where(['promo_id' => $params['promo_id'], 'promo_slave_type' => PromotionService::SLAVE_MANUFACTURER])->column();
            $this->settings['selected_countries'] = PromotionsSets::find()->select('promo_slave_id')->where(['promo_id' => $params['promo_id'], 'promo_slave_type' => PromotionService::SLAVE_COUNTRY])->column();
            $this->settings['selected_zones'] = PromotionsSets::find()->select('promo_slave_id')->where(['promo_id' => $params['promo_id'], 'promo_slave_type' => PromotionService::SLAVE_ZONE])->column();

            $this->settings['conditions'] = PromotionsConditions::find()->where(['promo_id' => $params['promo_id']])->asArray()->all();

            if (is_array($this->settings['conditions']) && count($this->settings['conditions'])) {
                $this->settings['conditions'] = \yii\helpers\ArrayHelper::index($this->settings['conditions'], 'groups_id');
            }

            if (is_array($this->groups)) {
                foreach ($this->groups as $group) {
                    if (!isset($this->settings['conditions'][$group->groups_id]))
                        $this->settings['conditions'][$group->groups_id] = [
                            'promo_type' => 1,
                            'promo_condition' => 0
                        ];
                }
            }

            if (!isset($this->settings['conditions'][0])) {
                $this->settings['conditions'][0] = [
                    'promo_type' => 1,
                    'promo_condition' => 0
                ];
            }
        }

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

            foreach ($response_data['categories_tree'] as $_idx => $_data) {
                if ($_data['categories_id']) {
                    $response_data['categories_tree'][$_idx]['selected'] = in_array($_data['categories_id'], $selected_data);
                } else {
                    $response_data['categories_tree'][$_idx]['selected'] = in_array($_data['key'], $selected_data);
                }
            }
            return $response_data['categories_tree'];
        } else if ($do == 'update_selected') {
            $cat_id = $params['id'];
            $isProduct = (strpos($cat_id, 'p') !== false);
            $cat_id = (int) $params['id'];
            $selected = $params['selected'];
            $select_children = $params['select_children'];
            $selected_data = tep_db_prepare_input($params['selected_data']);
            if (!is_array($selected_data))
                $selected_data = [];
            $response_data['update_selection'] = [];
            if ($isProduct) {
                $response_data['selected_data'] = $selected_data;
            } else {
                if ($selected) {
                    $parent_ids = array((int) $cat_id);
                    Categories::get_parent_categories($parent_ids, $parent_ids[0], false);
                    foreach ($parent_ids as $parent_id) {
                        if (!in_array((int) $parent_id, $selected_data)) {
                            //$response_data['update_selection'][(int) $parent_id] = true;
                            //$selected_data[] = (int) $parent_id;
                        }
                    }
                    if ($select_children) {
                        $children = array();
                        $this->tep_get_category_children($children, $platform_id, $cat_id);
                        foreach ($children as $child_key) {
                            if (is_array($child_key)) {
                                if (!in_array($child_key['key'], $selected_data)) {
                                    $response_data['update_selection'][$child_key['key']] = true;
                                    $selected_data[] = $child_key['key'];
                                }
                            } else {
                                if (!in_array($child_key, $selected_data)) {
                                    $response_data['update_selection'][$child_key] = true;
                                    $selected_data[] = $child_key;
                                }
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
            }


            $response_data['selected_data'] = $selected_data;
        } else { //init
            $get_categories_r = tep_db_query(
                    "SELECT c.categories_id, cd.categories_name as title " .
                    "FROM " . TABLE_CATEGORIES_DESCRIPTION . " cd, " . TABLE_CATEGORIES . " c " .
                    " left join " . TABLE_PLATFORMS_CATEGORIES . " pc on pc.categories_id=c.categories_id and pc.platform_id='" . (int) $platform_id . "' " .
                    "WHERE cd.categories_id=c.categories_id and cd.language_id='" . \Yii::$app->settings->get('languages_id') . "' AND cd.affiliate_id=0 and c.parent_id='" . (int) $params['category_id'] . "' " .
                    "order by c.sort_order, cd.categories_name"
            );

            $response_data['categories_tree'] = [];
            while ($_categories = tep_db_fetch_array($get_categories_r)) {
                $_categories['folder'] = true;
                $_categories['lazy'] = true;
                $_categories['selected'] = false;
                $_categories['key'] = $_categories['categories_id'];
                $response_data['categories_tree'][] = $_categories;
            }
            //if (!$params['category_id']) {
            $get_products_r = tep_db_query(
                    "SELECT concat('p',p.products_id,'_',p2c.categories_id) AS `key`, pd.products_name as title, if(p.products_status=0,'dis_prod','') as `extraClasses` " .
                    "from " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c, " . TABLE_PRODUCTS . " p " .
                    "inner join " . TABLE_PLATFORMS_PRODUCTS . " pp on pp.products_id=p.products_id and pp.platform_id='" . $platform_id . "' " .
                    "WHERE pd.products_id=p.products_id and pd.language_id='" . $languages_id . "' and pd.platform_id='" . intval(\common\classes\platform::defaultId()) . "' and p2c.products_id=p.products_id and p2c.categories_id='" . (int) $params['category_id'] . "' " .
                    "order by p.sort_order, pd.products_name"
            );
            if (tep_db_num_rows($get_products_r) > 0) {
                while ($_product = tep_db_fetch_array($get_products_r)) {
                    $response_data['categories_tree'][] = $_product;
                }
            }
            //}
            //echo '<pre>';print_r($response_data['categories_tree']);
        }

        return $response_data;
    }

    public function savePromotions($promo_id = 0) {

        $ids = [];
        $saved_ids = [];
        if (is_array($this->vars['cat_id'])) {
            foreach ($this->vars['cat_id'] as $idx => $id) {
                preg_match("/p([\d]*)/", $id, $matches);
                if ($matches[1]) {
                    $saved_ids[PromotionService::SLAVE_PRODUCT][] = ['id' => (int) $matches[1], 'quantity' => 1];
                } else {
                    $saved_ids[PromotionService::SLAVE_CATEGORY][] = ['id' => $id, 'quantity' => 1];
                }
            }
        }

        if (is_array($this->vars['man_id'])) {
            foreach ($this->vars['man_id'] as $man) {
                $saved_ids[PromotionService::SLAVE_MANUFACTURER][] = ['id' => $man, 'quantity' => 1];
            }
        }

        if (is_array($this->vars['countries'])) {
            foreach ($this->vars['countries'] as $country) {
                $saved_ids[PromotionService::SLAVE_COUNTRY][] = ['id' => $country, 'quantity' => 1];
            }
        }

        if (is_array($this->vars['zones'])) {
            foreach ($this->vars['zones'] as $zone) {
                $saved_ids[PromotionService::SLAVE_ZONE][] = ['id' => $zone, 'quantity' => 1];
            }
        }

        if (is_array($this->vars['deduction'])) {
            $this->conditions[0] = [
                'promo_deduction' => (int) $this->vars['deduction'][0],
                'groups_id' => 0,
            ];
            if ($this->enabledGroups) {
                foreach ($this->vars['deduction'] as $groups_id => $value) {
                    if ($this->vars['use_settings'][$groups_id]) {
                        $this->conditions[$groups_id] = [
                            'promo_deduction' => (int) $this->vars['deduction'][$groups_id],
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

    public function setMessage() {
        
    }

    public function clearMessage() {
        parent::clearMessage();
    }

    public function isFreeShipping($params) {
        $manager = $params['manager'] ?? \common\services\OrderManager::loadManager();
        $order = $manager->getOrderInstance();

        if ($order instanceof \common\classes\extended\OrderAbstract) {
            if ($this->enoughAmount($manager) && $this->productAllowed($manager) && $this->countryAllowed($manager) && $this->zonesAllowed($manager)) {
                $manager->getShippingCollection()->setFreeShippingOver($this->vars['promo_deduction']);
                return true;
            }
        }
        return false;
    }

    public function enoughAmount($manager) {
        try {
            return $manager->getCart()->show_total() >= $this->vars['promo_deduction'];
        } catch (\Exception $ex) {
            
        }
        return false;
    }

    public function productAllowed($manager) {
        try {
            static $_promotions_free_shipping_array = array();
            if (!isset($_promotions_free_shipping_array[$this->vars['promo_id']])) {
                $allowed = '';
                if (is_array($this->vars['details']['slave']['products'])) {
                    $allowed = array_keys($this->vars['details']['slave']['products']);
                    $allowed = trim(implode(',', $allowed), ', ');
                }
                if (is_array($this->vars['details']['slave']['categories'])) {
                    foreach ($this->vars['details']['slave']['categories'] as $c) {
                        if (is_array($c['ids'])) {
                            $allowed = trim($allowed . ',' . implode(',', $c['ids']), ', ');
                        }
                    }
                }

                if (is_array($this->vars['details']['slave']['brands'])) {
                    foreach ($this->vars['details']['slave']['brands'] as $b) {
                        if (is_array($b['ids'])) {
                            $allowed = trim($allowed . ',' . implode(',', $b['ids']), ', ');
                        }
                    }
                }
                $allowed = explode(',', $allowed);
                $_promotions_free_shipping_array[$this->vars['promo_id']] = $allowed;
            } else {
                $allowed = $_promotions_free_shipping_array[$this->vars['promo_id']];
            }

            $brandsAmount = PromotionsSets::find()->select('promo_slave_id')->where(['promo_id' => $this->vars['promo_id'], 'promo_slave_type' => PromotionService::SLAVE_MANUFACTURER])->count();
            if (!is_array($this->vars['details']['slave']['brands']) && $brandsAmount) {
                $allowed[] = '-1';
            }

            static $cart_map = null;
            if (is_null($cart_map)) {
                $cart_map = [];
                array_map(function($value) use (&$cart_map) {
                    $cart_map[] = intval($value);
                }, array_keys($manager->getCart()->contents));
            }
            
            if (count($allowed)) {
                return !count(array_diff($cart_map, $allowed));
            }
        } catch (\Exception $ex) {
            return false;
        }
        return true;
    }

    public function countryAllowed($manager) {
        try {
            static $countries = null;
            if (is_null($countries)) {
                $countries = PromotionsSets::find()->select('promo_slave_id')->where(['promo_id' => $this->vars['promo_id'], 'promo_slave_type' => PromotionService::SLAVE_COUNTRY])->column();
            }
            if (is_array($countries) && count($countries)) {
                $delivery = $manager->getDeliveryAddress();
                if (isset($delivery['country_id'])) {
                    return in_array($delivery['country_id'], $countries);
                }
            }
        } catch (\Exception $ex) {
            return false;
        }
        return true;
    }

    public function zonesAllowed($manager) {
        try {
            static $countries = null;
            if (is_null($countries)) {
                $countries = [];
                $zones = PromotionsSets::find()->select('promo_slave_id')->where(['promo_id' => $this->vars['promo_id'], 'promo_slave_type' => PromotionService::SLAVE_ZONE])->column();
                if ($zones) {
                    foreach (\common\models\GeoZones::find()->alias('gz')
                            ->joinWith('zones z')->where(['gz.geo_zone_id' => $zones])->all() as $geo) {

                        $countries = array_merge($countries, \yii\helpers\ArrayHelper::getColumn($geo->zones, 'zone_country_id'));
                    }
                }
            }
            if (is_array($countries) && count($countries)) {
                $delivery = $manager->getDeliveryAddress();
                if (isset($delivery['country_id'])) {
                    return in_array($delivery['country_id'], $countries);
                }
            }
        } catch (\Exception $ex) {
            return false;
        }
        return true;
    }

}
