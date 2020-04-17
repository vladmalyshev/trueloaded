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
use common\models\promotions\PromotionService;
use Yii;

abstract class ServiceAbstract{
    const DEFAULT_GROUP_TYPE = -1;
    
    public $priority = 0;

     // slave types : 0 - products, 1 - categories, 2 - brands, 3 - master category, 4 - master product
    public function rules(){
        return ['product', 'category'];
    }
    
    public function useTranslation(){
        //
    }
    
    public function getConditions(){
        return false;
    }
    
    public function clearMessage(){
        PromotionService::clearMessages();
    }
    
    public function getPromotionInfo($promo_id){
        return '';
    }
    
    public function getPromoFullDescription(){
        return '';
    }
    
    protected function tep_get_category_children(&$children, $platform_id, $categories_id) {
        if (!is_array($children))
            $children = array();
        foreach ($this->loadTree(['platform_id' => $platform_id, 'category_id' => $categories_id])['categories_tree'] as $item) {
            $children[] = $item;
            if ($item['folder']) {
                $this->tep_get_category_children($children, $platform_id, intval($item['categories_id']));
            }
        }
    }
    
        
    public function getCart(){
        global $cart;
        if (!is_object($cart)) {
            $manager = \common\services\OrderManager::loadManager();
            if ($manager->hasCart()){
                $cart = $manager->getCart();
            } else {
                return new \common\classes\shopping_cart();
            }
        }
        return $cart;
    }
    
    public $checkLimitInOldOrders = false;
    
    public function isLimitExceeded($qty){
        $this->vars['promo_limit'] = (int)$this->vars['promo_limit'];
        if ($this->vars['promo_limit']){
            if ($this->checkLimitInOldOrders){
                if (!\Yii::$app->user->isGuest){
                    $qty += \common\helpers\OrderProduct::getOrderedQty($this->vars['products_id'], ['customers_id' => \Yii::$app->user->getId(), 'promo_id' => $this->vars['promo_id']]);
                }
            }
            if ($qty > $this->vars['promo_limit']){
                $product = Yii::$container->get('products')->getProduct($this->vars['products_id']);
                if ($product){
                    $promoDetails = $product['promo_details']??[];
                    $promoDetails[$this->vars['promo_id']]['promo_message'] = TEXT_PROMO_LIMIT_EXCEEDED;
                    $data = ['promo_details' => $promoDetails];
                    if ($this->vars['promo_limit_block']) $data['block_in_cart'] = true;
                    $product->attachDetails($data);
                }
                return true;
            }
        }
        return false;
    }
    
    public function setPromoPriority(\common\components\ProductItem $product){
        $product->attachDetails(['promo_priority' => $this->priority]);
    }
    
    public function getProductName($products_id, $language_id = 0, $platformId = 0){
        if (\frontend\design\Info::isTotallyAdmin()){
            if ($name = \backend\models\ProductNameDecorator::getInternalName($products_id, $language_id, $platformId)){
                return $name;
            }
        }
        return \common\helpers\Product::get_products_name($products_id, $language_id, $platformId);
    }
    
    /**
     * 
     * @param float $firstPrice - normal price
     * @param float $secondPriceOld - price to compare old
     * @param float $secondPriceNew - price to compare new
     */
    public function getPriceAdvantages(float $firstPrice, float $secondPriceOld, float $secondPriceNew ): \stdClass {
        $advantage = new \stdClass();
        $advantage->diff = abs(($firstPrice + $secondPriceOld) - ($firstPrice + $secondPriceNew));
        $advantage->percDown = number_format(100 - (($firstPrice + $secondPriceNew) * 100 / ($firstPrice + $secondPriceOld)), 2) ;
        $advantage->percUp = number_format((($firstPrice + $secondPriceOld) * 100 / ($firstPrice + $secondPriceNew)) - 100, 2) ;
        $advantage->sign = ($secondPriceNew - $firstPrice > 0 ? '-': '+');
        $advantage->summNew = $firstPrice + $secondPriceNew;
        $advantage->summOld = $firstPrice + $secondPriceOld;
        return $advantage;
    }
}