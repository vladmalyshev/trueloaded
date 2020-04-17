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

namespace common\models\Product;

use Yii;
use common\classes\platform;
use common\helpers\Customer;
use common\models\Product\Price;
use common\models\promotions\PromotionService;
use common\models\promotions\PromotionsAssignement;
use common\models\promotions\Promotions;

class PromotionPrice {

    private static $instanses = [];
    private static $promo = [];
    private static $settings = [];
    

    private function __construct() {
        $this->defineSettings();
    }
    
    public static function getSettings(){
        if (!self::$settings){
            self::defineSettings();
        }
        return self::$settings;
    }
    
    public static function defineSettings(){
        self::$settings = [
            'to_preferred' => [
                'only_to_base' => defined('PROMOTION_APPLY_TO_BASE_PRICE') && PROMOTION_APPLY_TO_BASE_PRICE == 'true',
                'only_to_inventory' => defined('PROMOTION_APPLY_TO_INVENTORY_PRICE') && PROMOTION_APPLY_TO_INVENTORY_PRICE == 'true',
            ],
            'to_both' => defined('PROMOTION_APPLY_TO_BOTH_PRICE') && PROMOTION_APPLY_TO_BOTH_PRICE == 'true',
            'icon_instead_class' => defined('PROMOTION_ICON_INSTEAD_SALE') && PROMOTION_ICON_INSTEAD_SALE == 'true',
        ];
    }

        public static function getInstance($uprid){
        if (!isset(self::$instanses[$uprid])) {
            self::$instanses[$uprid] = new self();
            self::$instanses[$uprid]->uprid = $uprid;
            self::$instanses[$uprid]->calculateAfter = false;
            self::$instanses[$uprid]->loadPriceInstanse();
        }
        return self::$instanses[$uprid];
    }
    
    private function loadPriceInstanse(){
        self::$instanses[$this->uprid]->prices = Price::getInstance($this->uprid);
    }
    
    public function setCalculateAfter($value){
        self::$instanses[$this->uprid]->calculateAfter = (bool)$value;
    }
    
    private function _setPrices(&$product_price, &$special_price){
        if (self::$settings['to_both']){
                if ($this->prices->inventory_price['value']){
                    $special_price = $this->prices->inventory_special_price['value'];
                    $product_price = $this->prices->inventory_price['value'];
                } else {
                    $special_price = $this->prices->special_price['value'];
                    $product_price = $this->prices->products_price['value'];
                }                
            } else {
                if(self::$settings['to_preferred']['only_to_base']){ // to base
                    $special_price = $this->prices->special_price['value'];
                    $product_price = $this->prices->products_price['value'];
                } else { // to inventory
                    if ($this->prices->calculate_full_price){
                        $special_price = $this->prices->inventory_special_price['value'];
                        $product_price = $this->prices->inventory_price['value'];
                    } else {
                        $special_price = $this->prices->inventory_special_price['value'] - $this->prices->special_price['value'];
                        $product_price = $this->prices->inventory_price['value'] - $this->prices->products_price['value'];
                    }
                }
            }
    }

    public function getPromotionPrice() {
        
        if (!$this->prices->dSettings->applyPromotion()) return false;
        
        if (!array_sum(self::$settings['to_preferred']) && !self::$settings['to_both']) return false;
        $salemaker_array = \common\components\Salemaker::init();
        if (is_array($salemaker_array) && count($salemaker_array) > 0) {
            
            $special_price = false; $product_price = false;
            $this->_setPrices($product_price, $special_price);
            //return false;
            if (!$special_price && !$product_price) return false;
            
            $service = new PromotionService();
            
            for ($i = 0, $n = sizeof($salemaker_array); $i < $n; $i++) {
                if (!PromotionService::isPersonalizedPromoToCustomer($salemaker_array[$i]['promo_id'])) continue;
                if (in_array((int) $this->uprid, $salemaker_array[$i]['products']) || in_array((int) $this->uprid, $salemaker_array[$i]['master'])) {
                    $promoClass = $salemaker_array[$i]['class'];
                    if (!isset(self::$promo[$promoClass])) {
                        self::$promo[$promoClass] = $service($promoClass);
                    }
                    
                    self::$promo[$promoClass]->priority = $salemaker_array[$i]['priority'];
                    $conditions = self::useConditions($this->uprid, $product_price, $special_price, $salemaker_array[$i]);
                    
                    self::$promo[$salemaker_array[$i]['class']]->load($conditions);
                    $this->attachDetailsBefore($salemaker_array[$i]);
                    
                    $result = false;
                    if (!$this->calculateAfter && method_exists(self::$promo[$promoClass], 'calculate')){
                        $result = self::$promo[$salemaker_array[$i]['class']]->calculate();
                    } else if ($this->calculateAfter && method_exists(self::$promo[$promoClass], 'calculateAfter')) {
                        $result = self::$promo[$salemaker_array[$i]['class']]->calculateAfter();
                    }
                    if ($result !== false) {
                        if (self::$settings['to_both']){
                            //
                        } else {
                            if(self::$settings['to_preferred']['only_to_base']){ // to base
                                if (!is_null($this->prices->inventory_price['value'])){ //inventory calculated
                                    $result += (float)$this->prices->inventory_price['value'] - (float)$this->prices->products_price['value'];
                                }
                            } else { // to inventory
                                if ($this->prices->calculate_full_price){
                                    //
                                } else {
                                    $result += (float)$this->prices->products_price['value'];
                                }
                            }
                        }
                        $this->attachDetailsAfter($salemaker_array[$i]);
                        
                        return (float) $result;
                    }
                }
            }
            //return $special_price >= $product_price ? false : $special_price;
        }
        return false;
    }
    
    protected function attachDetailsBefore(array $salemakerData){
        $product = Yii::$container->get('products')->getProduct($this->uprid);
        if ($product){
            $promoDetails = $product['promo_details']??[];
            $promo_id = $salemakerData['promo_id'];
            if ($promo_id){
                if (!isset($promoDetails[$promo_id])) $promoDetails[$promo_id] = [];
                $promoDetails[$promo_id] = array_merge($promoDetails[$promo_id], [
                    'promo_name' => Promotions::getPromotionName($promo_id),
                    'possible_promo' => $promoDetails[$promo_id]['possible_promo']??true,
                    'working_promo' => $promoDetails[$promo_id]['working_promo']??false,
                ]);
                if (!empty($salemakerData['promo_icon'])){
                    $iconPath = \common\classes\Images::getWSCatalogImagesPath() . 'promo_icons/' . $salemakerData['promo_icon'];
                    if (is_file($iconPath)){
                        $promoDetails[$promo_id]['promo_icon'] = $iconPath;
                    }
                }
                $product->attachDetails(['promo_details' => $promoDetails]);
            }
        }
    }
    
    protected function attachDetailsAfter(array $salemakerData){
        $product = Yii::$container->get('products')->getProduct($this->uprid);
        if ($product){
            $promoDetails = $product['promo_details']??[];
            is_object(self::$promo[$salemakerData['class']]) && self::$promo[$salemakerData['class']]->setPromoPriority($product);
            $promo_id = $salemakerData['promo_id'];
            if ($promo_id){
                if (!isset($promoDetails[$promo_id])) $promoDetails[$promo_id] = [];
                $promoDetails[$promo_id] = array_merge($promoDetails[$promo_id], [
                    'special_start_date' => $salemakerData['start_date'],
                    'special_expiration_date' => $salemakerData['expiration_date'],
                    'possible_promo' => false,
                    'working_promo' => true,
                ]);
                $product->attachDetails(['promo_details' => $promoDetails]);
                if (self::$settings['icon_instead_class']){
                    $product->removeDetails('promo_class');
                }
            }
        }
    }
    
    public function useConditions($uprid, $product_price, $special_price, $data){
        
        return array_merge($data['conditions'], [
            'products_id' =>  $uprid,
            'product_price' => $product_price,
            'special_price' => $special_price,
            'master' => $data['master'],
            'details' => $data['details'],
            'promo_id' => $data['promo_id'],
        ]);
    }

}
