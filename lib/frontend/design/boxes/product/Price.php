<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\product;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;
use common\helpers\Tax;
use common\helpers\Product;

class Price extends Widget
{

    public $file;
    public $params;
    public $settings;

    public function init()
    {
        parent::init();
    }

    public function run()
    {
        $customer_groups_id = (int) \Yii::$app->storage->get('customer_groups_id');
        $params = Yii::$app->request->get();

        $special_ex = $old_ex = $current_ex = '';
        /** @var \common\classes\Currencies $currencies */
        $currencies = \Yii::$container->get('currencies');

        if (!$params['products_id']) {
            return '';
        }

        if ($ext = \common\helpers\Acl::checkExtension('PackUnits', 'checkPackPrice')) {
            $return_price = $ext::checkPackPrice($params['products_id']);
        }else{
            $return_price = true;
        }

        $products = Yii::$container->get('products');
        $product = $products->getProduct($params['products_id']);
        if (!$product->checkAttachedDetails($products::TYPE_STOCK)){
            $product_qty = Product::get_products_stock($params['products_id']);
            $stock_info = \common\classes\StockIndication::product_info(array(
                'products_id' => $params['products_id'],
                'products_quantity' => $product_qty,
            ));
            $product = $products->attachDetails($params['products_id'], [$products::TYPE_STOCK => $stock_info])->getProduct($params['products_id']);
        } else {
            $stock_info = $product[$products::TYPE_STOCK];
        }

        /**
         * $stock_indicator_public['display_price_options']
         * 0 - display
         * 1 - hide
         * 2 - hide if zero
         */
        if (($stock_info['flags']['request_for_quote'] && SHOW_PRICE_FOR_QUOTE_PRODUCT != 'True' /*&& $stock_info['flags']['display_price_options'] != 0*/) ||
            ($stock_info['flags']['display_price_options'] == 1) ||
            (abs($product['products_price']) < 0.01 && $stock_info['flags']['display_price_options'] == 2) ){
            $return_price = false;
        }

        if(!$return_price){
            return '';
        }

        if ((abs($product['products_price']) < 0.01 && defined('PRODUCT_PRICE_FREE') && PRODUCT_PRICE_FREE == 'true')) {
            //return TEXT_FREE;
            return IncludeTpl::widget(['file' => 'boxes/product/price.tpl', 'params' => [
                'special' => '',
                'old' => '',
                'current' => TEXT_FREE,
                'stock_info' => $stock_info,
                'expires_date' => $product['special_expiration_date'] ? date("Y-m-d", strtotime($product['special_expiration_date'])) : '',
            ]]);
        }
        
        if ($product['is_bundle']) {
            $details = \common\helpers\Bundles::getDetails(['products_id' => $product['products_id']]);
            if ($details['full_bundle_price_clear'] > $details['actual_bundle_price_clear']) {
                $special = $details['actual_bundle_price'];
                if (!empty($details['actual_bundle_price_ex'])) {
                  $special_ex = $details['actual_bundle_price_ex'];
                }
                $old = $details['full_bundle_price'];
                if (!empty($details['full_bundle_price_ex'])) {
                  $old_ex = $details['full_bundle_price_ex'];
                }
                $current = '';
            } else {
                $special = '';
                $old = '';
                $current = $details['actual_bundle_price'];
                if (!empty($details['actual_bundle_price_ex'])) {
                  $current_ex = $details['actual_bundle_price_ex'];
                }
            }
            $jsonPrice = $details['actual_bundle_price_clear'];
        } else {
            $priceInstance = \common\models\Product\Price::getInstance($product['products_id']);
            $product['products_price'] = $priceInstance->getInventoryPrice(['qty' => 1]);
            $product['special_price'] = $priceInstance->getInventorySpecialPrice(['qty' => 1]);
            if (isset($product['special_price']) && $product['special_price'] !== false) {
              /** @var \common\classes\Currencies $currencies */
                $special = $currencies->display_price($product['special_price'], $product['tax_rate'], 1, true, true);
                $old = $currencies->display_price($product['products_price'], $product['tax_rate']);
                if ($product['tax_rate']>0 && defined("DISPLAY_BOTH_PRICES") && DISPLAY_BOTH_PRICES =='True') { //&& (!\Yii::$app->storage->has('taxable') || (\Yii::$app->storage->has('taxable') && \Yii::$app->storage->get('taxable')))  - switcher from box and account ...
                  $special_ex = $currencies->display_price($product['special_price'], 0, 1, false, false);
                  $old_ex = $currencies->display_price($product['products_price'], 0, 1, false, false);
                }
                $current = '';
                $jsonPrice = $currencies->display_price_clear($product['special_price'], $product['tax_rate'], 1);
            } else {
                $special = '';
                $old = '';
                $current = $currencies->display_price($product['products_price'], $product['tax_rate'], 1, true, true);
                $jsonPrice = $currencies->display_price_clear($product['products_price'], $product['tax_rate'], 1);
                if ($product['tax_rate']>0 && defined("DISPLAY_BOTH_PRICES") && DISPLAY_BOTH_PRICES =='True') {
                  $current_ex = $currencies->display_price($product['products_price'], 0, 1, false, false);
                }
            }
        }

        if ($jsonPrice) {

            if ($ext = \common\helpers\Acl::checkExtension('BusinessToBusiness', 'changeShowPrice')) {
                if ($ext::changeShowPrice($customer_groups_id)) {
                    $special = $old = $current = '';
                    $special_ex = $old_ex = $current_ex = '';
                }
            }

            \frontend\design\JsonLd::addData(['Product' => [
                'offers' => [
                    '@type' => 'Offer',
                    'url' => Yii::$app->urlManager->createAbsoluteUrl(['catalog/product', 'products_id' => $params['products_id']]),
                    'availability' => 'https://schema.org/' . ($stock_info['stock_code'] == 'out-stock' ? 'OutOfStock' : 'InStock'),
                ]
            ]]);

            if ($product['special_expiration_date']) {
                \frontend\design\JsonLd::addData(['Product' => [
                    'offers' => [
                        'priceValidUntil' => date("Y-m-d", strtotime($product['special_expiration_date'])),
                    ]
                ]]);
            } else {
                \frontend\design\JsonLd::addData(['Product' => [
                    'offers' => [
                        'priceValidUntil' => date("Y-m-d", time() + 60*60*24*180),
                    ]
                ]]);
            }
            \frontend\design\JsonLd::addData(['Product' => [
                'offers' => [
                    'price' => $jsonPrice,
                    'priceCurrency' => \Yii::$app->settings->get('currency'),
                ]
            ]]);
        }

        return IncludeTpl::widget(['file' => 'boxes/product/price.tpl', 'params' => [
            'special' => $special,
            'old' => $old,
            'current' => $current,
            'special_ex' => $special_ex,
            'old_ex' => $old_ex,
            'current_ex' => $current_ex,
            'stock_info' => $stock_info,
            'expires_date' => $product['special_expiration_date'] ? date("Y-m-d", strtotime($product['special_expiration_date'])) : '',
        ]]);
    }
}