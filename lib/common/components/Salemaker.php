<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 * Price for all conditions
 */

namespace common\components;

use Yii;
use common\helpers\Categories;
use common\models\promotions\PromotionService;
use common\models\promotions\Promotions;

class Salemaker {

    public static function init($platform_id = 0) {
        static $salemakerPlatform = [];

        if (!$platform_id)
            $platform_id = \common\classes\platform::currentId();

        if (!isset($salemakerPlatform[$platform_id])){
            $salemakerPlatform[$platform_id] = [];
            $promotions = Promotions::getCurrentPromotions($platform_id)->all();

            if ($promotions && is_array($promotions)) {
                foreach ($promotions as $promo) {
                    $salemakerPlatform[$platform_id][] = self::getConditions($promo);
                }
            }
        }
        return $salemakerPlatform[$platform_id];
    }
    
    public static function getConditionsById($promo_id){
        if ($promo_id){
            $promo = Promotions::findOne($promo_id)->one();
            if ($promo){
                return self::getConditions($promo);
            }
        }
        return false;
    }

    public static function getConditions(Promotions $promo){
        
        static $cache = [];
        if (!isset($cache[$promo->promo_id])){
            $products = $master_products = $details = $setsConditions = [];
            $conditions = [];
            if ($promo->setsConditions) {
                $setsConditions = \yii\helpers\ArrayHelper::index($promo->setsConditions, 'promo_sets_id');
            }
            if ($promo->sets) {
                foreach ($promo->sets as $set) {
                    switch ($set->getAttribute('promo_slave_type')) {
                        case PromotionService::SLAVE_CATEGORY :
                            $cid = $set->getAttribute('promo_slave_id');
                            $ids = Categories::ids_products_in_category($cid, false, $promo->platform_id, false);
                            if (is_array($ids)) {
                                $products = array_merge($products, $ids);
                                $details['slave']['categories'][$cid] = [
                                    'qty' => $set->getAttribute('promo_quantity'),
                                    'ids' => $ids,
                                ];
                            }
                            if ($promo->setsConditions) {
                                $promo_sets_id = $set->getAttribute('promo_sets_id');
                                if (isset($setsConditions[$promo_sets_id])) {
                                    if (is_array($ids)) {
                                        foreach ($ids as $_id) {
                                            $details['slave']['categories'][$cid]['set_condition'][$_id] = $setsConditions[$promo_sets_id];
                                        }
                                    }
                                }
                            }
                            break;
                        case PromotionService::SLAVE_MANUFACTURER :
                            $ids = \common\helpers\Manufacturers::products_ids_manufacturer($set->getAttribute('promo_slave_id'), false, $promo->platform_id);
                            $products = array_merge($products, $ids);
                            if ($ids){
                                $details['slave']['brands'][$set->getAttribute('promo_slave_id')] = [
                                    'qty' => $set->getAttribute('promo_quantity'),
                                    'ids' => $ids,
                                ];
                            }
                            break;
                        case PromotionService::MASTER_CATEGORY :
                            $cid = $set->getAttribute('promo_slave_id');
                            $ids = Categories::ids_products_in_category($cid, false, $promo->platform_id, true);
                            if (is_array($ids)) {
                                $master_products = array_merge($master_products, $ids);
                                $details['master']['categories'][$cid] = [
                                    'qty' => $set->getAttribute('promo_quantity'),
                                    'ids' => $ids,
                                ];
                            }
                            break;
                        case PromotionService::MASTER_PRODUCT :
                            $pid = $set->getAttribute('promo_slave_id');
                            $master_products[] = $pid;
                            $details['master']['products'][$pid] = $set->getAttribute('promo_quantity');
                            break;
                        case PromotionService::SLAVE_PROPERTY :
                            $ids = \common\helpers\Properties::getProductsToProperty($set->getAttribute('promo_slave_id'));
                            if ($ids){
                                $ids = \yii\helpers\ArrayHelper::getColumn($ids, 'products_id');
                                $products = array_merge($products, $ids);
                                $details['slave']['properties'][$set->getAttribute('promo_slave_id')] = [
                                    'qty' => $set->getAttribute('promo_quantity'),
                                    'ids' => $ids,
                                ];
                                if ($promo->setsConditions) {
                                    $promo_sets_id = $set->getAttribute('promo_sets_id');
                                    if (isset($setsConditions[$promo_sets_id])) {
                                        if (is_array($ids)) {
                                            foreach ($ids as $_id) {
                                                $details['slave']['properties'][$set->getAttribute('promo_slave_id')]['set_condition'][$_id] = $setsConditions[$promo_sets_id];
                                            }
                                        }
                                    }
                                }
                            }
                            break;
                        case PromotionService::SLAVE_PROPERTY_VALUE :
                            $ids = \common\helpers\Properties::getProductsToPropertyValue($set->getAttribute('promo_slave_id'));
                            if ($ids){
                                $ids = \yii\helpers\ArrayHelper::getColumn($ids, 'products_id');
                                $products = array_merge($products, $ids);
                                $details['slave']['properties_values'][$set->getAttribute('promo_slave_id')] = [
                                    'qty' => $set->getAttribute('promo_quantity'),
                                    'ids' => $ids,
                                ];
                                if ($promo->setsConditions) {
                                    $promo_sets_id = $set->getAttribute('promo_sets_id');
                                    if (isset($setsConditions[$promo_sets_id])) {
                                        if (is_array($ids)) {
                                            foreach ($ids as $_id) {
                                                $details['slave']['properties_values'][$set->getAttribute('promo_slave_id')]['set_condition'][$_id] = $setsConditions[$promo_sets_id];
                                            }
                                        }
                                    }
                                }

                            }
                            break;
                        case PromotionService::MASTER_PROPERTY :
                            $ids = \common\helpers\Properties::getProductsToProperty($set->getAttribute('promo_slave_id'));
                            if ($ids){
                                $ids = \yii\helpers\ArrayHelper::getColumn($ids, 'products_id');                                    
                                $master_products = array_merge($master_products, $ids);
                                $details['master']['properties'][$set->getAttribute('promo_slave_id')] = [
                                    'qty' => $set->getAttribute('promo_quantity'),
                                    'ids' => $ids,
                                ];
                            }
                            break;
                        case PromotionService::MASTER_PROPERTY_VALUE :
                            $ids = \common\helpers\Properties::getProductsToPropertyValue($set->getAttribute('promo_slave_id'));
                            if ($ids){
                                $ids = \yii\helpers\ArrayHelper::getColumn($ids, 'products_id');                                    
                                $master_products = array_merge($master_products, $ids);
                                $details['master']['properties_values'][$set->getAttribute('promo_slave_id')] = [
                                    'qty' => $set->getAttribute('promo_quantity'),
                                    'ids' => $ids,
                                ];
                            }
                            break;
                        case PromotionService::SLAVE_PRODUCT: // products
                        //default:
                            $promo_slave_id = $set->getAttribute('promo_slave_id');
                            $products[] = $promo_slave_id;
                            $details['slave']['products'][$promo_slave_id] = [
                                    'qty' => $set->getAttribute('promo_quantity'),
                                    'qindex' => $set->getAttribute('promo_qindex'),
                                    'nindex' => $set->getAttribute('promo_nindex'),
                                    //'ids' => [$promo_slave_id],
                                ];
                            if ($promo->setsConditions) {
                                $promo_sets_id = $set->getAttribute('promo_sets_id');
                                if (isset($setsConditions[$promo_sets_id])) {
                                    $details['slave']['products'][$promo_slave_id]['set_condition'] = $setsConditions[$promo_sets_id];
                                }
                            }
                            break;
                    }
                }
                $products = array_unique($products);
            }

            if (count($master_products) && count($products)) {
                $master_products = array_unique($master_products);
                $products = array_diff($products, $master_products);
            }

            if (count($promo->conditions)) {
                $conditions = $promo->conditions[0];
            }
            $salemaker_array = [
                'products' => $products,
                'master' => $master_products,
                'details' => $details,
                'class' => $promo->promo_class,
                'conditions' => $conditions,
                'promo_id' => $promo->promo_id,
                'priority' => $promo->promo_priority,
                'promo_icon' => $promo->promo_icon,
                'start_date' => $promo->promo_date_start,
                'expiration_date' => $promo->promo_date_expired,
            ];
            $cache[$promo->promo_id] = $salemaker_array;
        }
        
        return $cache[$promo->promo_id];
    }

    public static function getNearestExpiringPromoTo($products_id, $platform_id = 0){
        $products_id = (int)$products_id;
        $expiration = [];
        foreach(self::init($platform_id) as $promo){
            if (in_array($products_id, $promo['products'])){
                if (strtotime($promo['expiration_date'])){
                    $expiration[strtotime($promo['expiration_date'])] = $promo['expiration_date'];
                }
            }
        }
        if ($expiration){
            return $expiration[min(array_keys($expiration))];
        }
        return false;
    }
    
    public static function getFirstExpiringPromoTo($products_id, $platform_id = 0){
        $products_id = (int)$products_id;
        $expiration = [];
        foreach(self::init($platform_id) as $promo){
            if (in_array($products_id, $promo['products'])){
                return $promo['expiration_date'];
            }
        }
        return false;
    }

}
