<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap\Products;

use backend\models\EP\Exception;
use backend\models\EP\Tools;
use common\api\models\Soap\Store\ArrayOfAssignedCustomerGroup;
use common\api\models\Soap\Store\ArrayOfAssignedPlatform;
use common\api\SoapServer\SoapHelper;
use common\api\models\Soap\SoapModel;
use common\api\SoapServer\ServerSession;
use common\models\promotions\Bonuses\SignNewsletter;
use yii\helpers\ArrayHelper;

class Product extends SoapModel
{
    /**
     * @var integer {minOccurs=0, maxOccurs=1}
     * @soap
     */
    public $products_id;

    /**
     * @var string {minOccurs=0}
     * @soap
     */
    public $products_model;

    /**
     * @var double {minOccurs=0}
     * @soap
     */
    public $products_weight;

    /**
     * @var integer {minOccurs=0}
     * @soap
     */
    public $products_status;

    /**
     * @var string {minOccurs=0}
     * @soap
     */
    public $products_ean;

    /**
     * @var string {minOccurs=0}
     * @soap
     */
    public $products_asin;

    /**
     * @var string {minOccurs=0}
     * @soap
     */
    public $products_isbn;

    /**
     * @var string {minOccurs=0}
     * @soap
     */
    public $products_upc;

    /**
     * @var integer {minOccurs=0}
     * @soap
     */
    public $is_virtual;

    /**
     * @var \common\api\models\Soap\Products\FeaturedInfo {nillable = 1, minOccurs=0, maxOccurs=1}
     * @soap
     */
    public $featured_info;

    /**
     * @var \common\api\models\Soap\Products\ArrayOfPrices Array of PriceType {nillable = 0, minOccurs=0, maxOccurs=1}
     * @soap
     */
    public $gift_wrap;

    /**
     * @var string {minOccurs=0}
     * @soap
     */
    public $products_tax_class_id;

    /**
     * @var integer {minOccurs=0}
     * @soap
     */
    public $manufacturers_id;

    /**
     * @var string {minOccurs=0}
     * @soap
     */
    public $manufacturers_name;

    /**
     * @var \common\api\models\Soap\Store\ArrayOfAssignedPlatform Array of AssignedPlatform {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $assigned_platforms;

    /**
     * @var \common\api\models\Soap\Store\ArrayOfAssignedCustomerGroup Array of ArrayOfAssignedCustomerGroup {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $assigned_customer_groups;

    /**
     * @var \common\api\models\Soap\Products\ArrayOfProductDescription Array of ProductDescription {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $descriptions;

    /**
     * @var \common\api\models\Soap\Products\ArrayOfPriceInfo Array of PriceInfo {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $prices;

    /**
     * @var \common\api\models\Soap\Products\PriceInfo {nillable = 1, minOccurs=1, maxOccurs = 1}
     * @-soap
     */
    //public $price_info;

    /**
     * @var \common\api\models\Soap\Products\StockInfo {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $stock_info;

    /**
     * @var \common\api\models\Soap\Products\OrderDetail {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $order_detail;

    /**
     * @var \common\api\models\Soap\Products\ArrayOfSupplierProductData {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $supplier_product_data;

    /**
     * @var \common\api\models\Soap\Products\ArrayOfAssignedCategories Array of AssignedCategories {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $assigned_categories;

    /**
     * @var \common\api\models\Soap\Products\ArrayOfAttributes Array of Attributes {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $attributes;

    /**
     * @var \common\api\models\Soap\Products\ArrayOfInventories Array of Inventory {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $inventories;

    /**
     * @var \common\api\models\Soap\Products\ArrayOfImages Array of Images {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $images;

    /**
     * @var \common\api\models\Soap\Products\Dimensions Dimensions {nillable = 1, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $dimensions;


    /**
     * @var \common\api\models\Soap\Products\ArrayOfProperties Array of Properties {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $properties;

    /**
     * @var \common\api\models\Soap\Products\ArrayOfXSell Array of XSell {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $xsells;


    /**
     * @var \common\api\models\Soap\Products\ArrayOfDocuments Array of Documents {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $documents;

    /**
     * @var datetime
     * @soap
     */
    public $products_date_added;//               | datetime         | NO     | MUL   | 0000-00-00 00:00:00 |                |

    /**
     * @var datetime {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $products_last_modified; //           | datetime         | YES    |       | <null>

    /**
     * @var boolean {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $is_own_product;

    protected $validate = [];

    public function __construct(array $config = [])
    {
        $is_own_product = false;
        if (!empty($config['created_by_department_id'])) {
            if (ServerSession::get()->getDepartmentId() && ServerSession::get()->getDepartmentId()==$config['created_by_department_id']){
                $config['is_own_product'] = true;
                $is_own_product = true;
            }elseif (ServerSession::get()->getPlatformId() && ServerSession::get()->getPlatformId()==$config['created_by_platform_id']){
                $config['is_own_product'] = true;
                $is_own_product = true;
            }
        }

        if ( isset($config['descriptions']) ) {
            $this->descriptions = new ArrayOfProductDescription($config['descriptions']);
            unset($config['descriptions']);
        }

        $this->prices = ArrayOfPriceInfo::forProduct($config['products_id'], $is_own_product);
        unset($config['prices']);

        if ( array_key_exists('products_quantity', $config) ) {
            $this->stock_info = new StockInfo($config);
        }else{
            $this->stock_info = new StockInfo();
        }

        if ( isset($config['assigned_categories']) ) {
            $this->assigned_categories = new ArrayOfAssignedCategories($config['assigned_categories']);
            unset($config['assigned_categories']);
        }
        if ( isset($config['attributes']) ) {
            $this->attributes = new ArrayOfAttributes($config['attributes']);
            unset($config['attributes']);
        }
        if ( isset($config['inventory']) ) {
            $this->inventories = new ArrayOfInventories($config['inventory']);
            unset($config['inventory']);
        }
        if ( isset($config['images']) ) {
            $this->images = new ArrayOfImages($config['images']);
            unset($config['images']);
        }
        if ( isset($config['properties']) ) {
            $this->properties = new ArrayOfProperties($config['properties']);
            unset($config['properties']);
        }

        if ( isset($config['xsell']) ) {
            $this->xsells = new ArrayOfXSell($config['xsell']);
            unset($config['xsell']);
        }

        if ( isset($config['documents']) ) {
            $this->documents = new ArrayOfDocuments($config['documents']);
            unset($config['documents']);
        }

        if ( !$config['is_virtual'] ) {
            $this->dimensions = new Dimensions($config);
        }
        if ( isset($config['suppliers_data']) && is_array($config['suppliers_data']) ) {
            $this->supplier_product_data = new ArrayOfSupplierProductData($config['suppliers_data']);
        }

        $this->order_detail = new OrderDetail($config);

        if ( isset($config['gift_wrap']) && is_array($config['gift_wrap']) ) {
            $_gift_wrap = [];
            foreach ($config['gift_wrap'] as $__idx=>$gw){
                if ( $gw->groups_id!=0 ) continue;
                $_gift_wrap[] = [
                    'currencies_id' => $gw['currencies_id'],
                    'price' => $gw['gift_wrap_price'],
                ];
            }
            $this->gift_wrap = new ArrayOfPrices($_gift_wrap);
            unset($config['gift_wrap']);
        }
        if ( isset($config['featured']) && is_array($config['featured']) ) {
            if ( count($config['featured'])>0 ) {
                $this->featured_info = new FeaturedInfo($config['featured'][0]);
            }
            unset($config['featured']);
        }

        if ( !ServerSession::get()->acl()->siteAccessPermission() ) {
            unset($config['assigned_platforms']);
            unset($config['assigned_customer_groups']);
        }else{
            $config['assigned_platforms'] = new ArrayOfAssignedPlatform($config['assigned_platforms']);
            $config['assigned_customer_groups'] = new ArrayOfAssignedCustomerGroup($config['assigned_customer_groups']);
        }

        parent::__construct($config);
        if ( !empty($this->products_date_added) ) {
            $this->products_date_added = \common\api\SoapServer\SoapHelper::soapDateTimeOut($this->products_date_added);
        }
        if ( !empty($this->products_last_modified) ) {
            $this->products_last_modified = \common\api\SoapServer\SoapHelper::soapDateTimeOut($this->products_last_modified);
        }
    }

    public function inputValidate()
    {
        $this->validate = [];
        if ( $this->stock_info && $this->stock_info ) {
            $this->validate = array_merge($this->validate, $this->stock_info->inputValidate());
        }

        if ( $this->inventories ) {
            $this->validate = array_merge($this->validate, $this->inventories->inputValidate());
        }
        return $this->validate;
    }

    protected function parseRequestPrice($price)
    {

        $products_group_price_pack_unit = '-2.000000';
        $products_group_discount_price_pack_unit = '';
        $products_group_price_packaging = '-2.000000';
        $products_group_discount_price_packaging = '';

        if (isset($price['pack'])) {
            if (isset($price['pack']['price'])) {
                $products_group_price_pack_unit = $price['pack']['price'];
            }
            if (isset($price['pack']['discount_table']) && isset($price['pack']['discount_table']['price'])) {
                $tmp_table = ArrayHelper::isIndexed($price['pack']['discount_table']['price']) ? $price['pack']['discount_table']['price'] : [$price['pack']['discount_table']['price']];
                $products_group_discount_price_pack_unit = SoapHelper::createDiscountTableString($tmp_table);
            }
        }
        if (isset($price['pallet'])) {
            if (isset($price['pallet']['price'])) {
                $products_group_price_packaging = $price['pallet']['price'];
            }
            if (isset($price['pallet']['discount_table']) && isset($price['pallet']['discount_table']['price'])) {
                $tmp_table = ArrayHelper::isIndexed($price['pallet']['discount_table']['price']) ? $price['pallet']['discount_table']['price'] : [$price['pallet']['discount_table']['price']];
                $products_group_discount_price_packaging = SoapHelper::createDiscountTableString($tmp_table);
            }
        }

        return [
            "products_group_price" => $price['price'],
            "products_group_discount_price" => SoapHelper::createDiscountTableString(isset($price['discount_table']['price']) ? $price['discount_table']['price'] : false),
            //"bonus_points_price" =>
            //"bonus_points_cost" =>
            "products_group_price_pack_unit" => $products_group_price_pack_unit,
            "products_group_discount_price_pack_unit" => $products_group_discount_price_pack_unit,
            "products_group_price_packaging" => $products_group_price_packaging,
            "products_group_discount_price_packaging" => $products_group_discount_price_packaging,
            //"products_price_configurator" =>
        ];
    }

    public function makeARArray()
    {
        $data = [
            '.errors' => [],
            '.warnings' => [],
        ];
        $objectVars = \Yii::getObjectVars($this);
        if ( isset($objectVars['attributes']) ) {
            $objectVars['attributes'] = json_decode(json_encode($objectVars['attributes']),true);
            if ( isset($objectVars['attributes']['attribute']) ) {
                $attributes = ArrayHelper::isIndexed($objectVars['attributes']['attribute'])?$objectVars['attributes']['attribute']:[$objectVars['attributes']['attribute']];
                $data['attributes'] = $attributes;
            }
            unset($objectVars['attributes']);
        }
        if ( isset($objectVars['inventories']) ) {
            $inventories = json_decode(json_encode($objectVars['inventories']),true);
            if ( isset($inventories['inventory']) ) {
                $inventories = ArrayHelper::isIndexed($inventories['inventory'])?$inventories['inventory']:[$inventories['inventory']];
                $data['inventory'] = [];
                foreach( $inventories as $idx=>$inventory ){
                    if ( isset($inventory['stock_info']) ) {
                        if ( is_array($inventory['stock_info']) ) {
                            $stock_info = $inventory['stock_info'];
                            unset($inventory['stock_info']);
                            $stock_info['products_quantity'] = $stock_info['quantity'];
                            unset($stock_info['quantity']);
                            if ( isset($stock_info['warehouses_stock']) ) {
                                $warehouses_stock = ArrayHelper::isIndexed($stock_info['warehouses_stock']['warehouse_stock'])?$stock_info['warehouses_stock']['warehouse_stock']:[$stock_info['warehouses_stock']['warehouse_stock']];
                                unset($stock_info['warehouses_stock']);
                                $stock_info['warehouses_products'] = $warehouses_stock;
                            }
                            $inventory = array_merge($inventory, $stock_info);
                        }
                    }
                    if ( isset($inventory['attribute_maps']) ) {
                        $attribute_maps = $inventory['attribute_maps'];
                        unset($inventory['attribute_maps']);
                        $attribute_map = [];
                        if ( is_array($attribute_maps) ) {
                            $attribute_map = ArrayHelper::isIndexed($attribute_maps['attribute_map'])?$attribute_maps['attribute_map']:[$attribute_maps['attribute_map']];
                        }
                        $inventory['attribute_map'] = $attribute_map;
                    }
                    $data['inventory'][] = $inventory;
                }
            }
            unset($objectVars['inventories']);
        }

        foreach ( $objectVars as $key=>$val) {
            if ( is_object($val) ) {
                $val = json_decode(json_encode($val),true);
            }
            if ($key=='descriptions') {
                if (!is_array($val) || !isset($val['description'])) continue;
                $descriptions = ArrayHelper::isIndexed($val['description']) ? $val['description'] : [$val['description']];
                $val = [];
                foreach ($descriptions as $description) {
                    $descriptionKey = $description['language'];
                    $val[$descriptionKey] = $description;
                }
            }elseif ( $key=='assigned_platforms' ) {
                if (isset($val['assigned_platform']) && is_array($val['assigned_platform'])) {
                    $assigned_platforms = ArrayHelper::isIndexed($val['assigned_platform']) ? $val['assigned_platform'] : [$val['assigned_platform']];
                    $val = $assigned_platforms;
                }else{
                    continue;
                }
            }elseif ( $key=='assigned_customer_groups' ) {
                if (isset($val['assigned_customer_group']) && is_array($val['assigned_customer_group'])) {
                    $assigned_customer_groups = ArrayHelper::isIndexed($val['assigned_customer_group']) ? $val['assigned_customer_group'] : [$val['assigned_customer_group']];
                    $val = $assigned_customer_groups;
                }else{
                    continue;
                }
            }elseif ( $key=='assigned_categories' && isset($val['assigned_category']) && is_array($val['assigned_category']) ) {
                $assigned_categories = ArrayHelper::isIndexed($val['assigned_category'])?$val['assigned_category']:[$val['assigned_category']];
                foreach( $assigned_categories as $__idx=>$assigned_category ) {
                    if ( !array_key_exists('categories_id',$assigned_category) ) unset($assigned_categories[$__idx]);
                    unset($assigned_category['categories_path']);
                    unset($assigned_category['categories_path_array']);
                    //echo '<pre>'; var_dump($assigned_category); echo '</pre>';
                }
                $val = $assigned_categories;
            }elseif($key=='dimensions' && is_array($val)) {
                $dimensionData = (array)$val;
                foreach ($dimensionData as $dimKey => $dimValue) {
                    if (preg_match('/(length|width|height)_cm$/', $dimKey)) {
                        $dimensionData[preg_replace('/_cm$/', '_in', $dimKey)] = round(0.393707143 * $dimValue, 2);
                        //$create_data_array[$metricKey.'cm'] = 2.539959*$create_data_array[$metricKey.'in'];
                    } elseif (preg_match('/(weight)_cm$/', $dimKey)) {
                        $dimensionData[preg_replace('/_cm$/', '_in', $dimKey)] = round(2.20462262 * $dimValue, 2);
                        //$create_data_array[$metricKey.'cm'] = 0.45359237*$create_data_array[$metricKey.'in'];
                    }
                }
                if (isset($dimensionData['weight_cm'])) {
                    $dimensionData['products_weight'] = $dimensionData['weight_cm'];
                }
                $data = array_merge($data, $dimensionData);
                continue;
            }elseif ($key=='order_detail' && !empty($val)){
                $data = array_merge($data, OrderDetail::makeAR($val));
                continue;
            }elseif ($key == 'featured_info' ){
                if ( is_array($val) ) {
                    $data['featured'] = FeaturedInfo::makeAR($val);
                }
                unset($data['featured_info']);
                continue;
            }elseif ($key=='stock_info'){
                if ( !is_array($val) ) continue;
                if ( !is_null($val['quantity']) )
                    $data['products_quantity'] = $val['quantity'];
                if ( !is_null($val['stock_indication_id']) )
                    $data['stock_indication_id'] = $val['stock_indication_id'];
                if ( !is_null($val['stock_indication_text']) )
                    $data['stock_indication_text'] = $val['stock_indication_text'];
                if ( !is_null($val['stock_delivery_terms_id']) )
                    $data['stock_delivery_terms_id'] = $val['stock_delivery_terms_id'];
                if ( !is_null($val['stock_delivery_terms_text']) )
                    $data['stock_delivery_terms_text'] = $val['stock_delivery_terms_text'];
                if ( isset($val['warehouses_stock']) && !empty($val['warehouses_stock']['warehouse_stock']) ) {
                    if ( !isset($data['inventory']) ) {
                        $warehouses_stock = ArrayHelper::isIndexed($val['warehouses_stock']['warehouse_stock']) ? $val['warehouses_stock']['warehouse_stock'] : [$val['warehouses_stock']['warehouse_stock']];
                        $data['warehouses_products'] = $warehouses_stock;
                    }
                }
                continue;
            }elseif ( $key=='prices' && isset($val['price_info']) ) {
                $prices = ArrayHelper::isIndexed($val['price_info']) ? $val['price_info'] : [$val['price_info']];
                $val = [];
                foreach ($prices as $price) {
                    if (array_key_exists('products_price_full', $price)) {
                        $data['products_price_full'] = $price['products_price_full'] ? '1' : '0';
                    }
                    $priceKEY = $price['currency'] . '_0';

                    if (isset($price['pack']) && isset($price['pack']['products_qty'])) {
                        $data['pack_unit'] = $price['pack']['products_qty'];
                    }
                    if (isset($price['pallet']) && isset($price['pallet']['products_qty'])) {
                       $data['packaging'] = $price['pallet']['products_qty'];
                    }
                    if (array_key_exists('sale_price', $price)) {
                        $data['special'] = SalePriceInfo::makeAR($price['sale_price']);
                    }

                    $val[$priceKEY] = $this->parseRequestPrice($price);
                    if ( isset($price['customer_groups_prices']) && isset($price['customer_groups_prices']['customer_groups_price']) ) {
                        $customer_groups_prices = ArrayHelper::isIndexed($price['customer_groups_prices']['customer_groups_price'])?$price['customer_groups_prices']['customer_groups_price']:[$price['customer_groups_prices']['customer_groups_price']];
                        if ( !ServerSession::get()->acl()->siteAccessPermission() ) {
                            $data['.warnings']['access_denied_price'] = "Need 'Site access permission' for setup customer group prices";
                        }else{
                            foreach ($customer_groups_prices as $customer_groups_price) {
                                if (empty($customer_groups_price['groups_id']) && !empty($customer_groups_price['groups_name'])) {
                                    $customer_groups_price['groups_id'] = Tools::getInstance()->getCustomerGroupId($customer_groups_price['groups_name']);
                                }
                                if (empty($customer_groups_price['groups_id'])) {
                                    $data['.warnings']['group_name_search_' . $customer_groups_price['groups_name']] = $customer_groups_price['groups_name'] . " group not found";
                                    continue;
                                }
                                if (is_null($customer_groups_price['price'])) $customer_groups_price['price'] = '-2';
                                $val[$price['currency'] . '_' . $customer_groups_price['groups_id']] = $this->parseRequestPrice($customer_groups_price);
                                if ( isset($data['special']) && is_array($data['special']) && count($data['special'])>0 && isset($customer_groups_price['sale_price']) ){
                                    if ( isset($customer_groups_price['sale_price']['status']) && !$customer_groups_price['sale_price']['status'] ){
                                        $group_sale_price = -1;
                                    }else{
                                        $group_sale_price = -2;
                                        if ( isset($customer_groups_price['sale_price']['price']) && is_numeric($customer_groups_price['sale_price']['price']) ) {
                                            $group_sale_price = $customer_groups_price['sale_price']['price'];
                                        }
                                    }
                                    $data['special'][0]['prices'][$price['currency'] . '_' . $customer_groups_price['groups_id']] = [
                                        'specials_new_products_price' => $group_sale_price,
                                    ];
                                }
                            }
                        }
                    }
                }

                static $objCurrencies = false;
                if ( $objCurrencies===false ) $objCurrencies = new \common\classes\Currencies();

                $useCurrency = \common\helpers\Currencies::systemCurrencyCode();
                $defPrice = false;
                if ( isset($val[\common\helpers\Currencies::systemCurrencyCode().'_0']) ) {
                    $defPrice = $val[\common\helpers\Currencies::systemCurrencyCode().'_0'];
                }else{
                    foreach (\common\helpers\Currencies::get_currencies() as $currency){
                        $checkKey = $currency['code'].'_0';
                        if ( isset($val[$checkKey]) ) {
                            $useCurrency = $currency['code'];
                            $rateConvertFrom = $objCurrencies->get_value($currency['code']);
                            $defSource = $val[$checkKey];
                            $defPrice = [];
                            if ( isset($defSource['products_group_price']) ) {
                                $defPrice['products_group_price'] = $this->applyRate($defSource['products_group_price'],1/$rateConvertFrom);
                            }
                            if ( isset($defSource['products_group_discount_price']) ) {
                                $defPrice['products_group_discount_price'] = $this->applyRate($defSource['products_group_discount_price'],1/$rateConvertFrom);
                            }
                            if ( isset($defSource['products_group_price_pack_unit']) ) {
                                $defPrice['products_group_price_pack_unit'] = $this->applyRate($defSource['products_group_price_pack_unit'],1/$rateConvertFrom);
                            }
                            if ( isset($defSource['products_group_discount_price_pack_unit']) ) {
                                $defPrice['products_group_discount_price_pack_unit'] = $this->applyRate($defSource['products_group_discount_price_pack_unit'],1/$rateConvertFrom);
                            }
                            if ( isset($defSource['products_group_price_packaging']) ) {
                                $defPrice['products_group_price_packaging'] = $this->applyRate($defSource['products_group_price_packaging'],1/$rateConvertFrom);
                            }
                            if ( isset($defSource['products_group_discount_price_packaging']) ) {
                                $defPrice['products_group_discount_price_packaging'] = $this->applyRate($defSource['products_group_discount_price_packaging'],1/$rateConvertFrom);
                            }
                            break;
                        }
                    }
                }
                if ($defPrice) {
                    if ( isset($defPrice['products_group_price']) ) {
                        $data['products_price'] = $defPrice['products_group_price'];
                    }
                    if ( isset($defPrice['products_group_discount_price']) ) {
                        $data['products_price_discount'] = $defPrice['products_group_discount_price'];
                    }
                    if ( isset($defPrice['products_group_price_pack_unit']) ) {
                        $data['products_price_pack_unit'] = $defPrice['products_group_price_pack_unit'];
                    }
                    if ( isset($defPrice['products_group_discount_price_pack_unit']) ) {
                        $data['products_price_discount_pack_unit'] = $defPrice['products_group_discount_price_pack_unit'];
                    }
                    if ( isset($defPrice['products_group_price_packaging']) ) {
                        $data['products_price_packaging'] = $defPrice['products_group_price_packaging'];
                    }
                    if ( isset($defPrice['products_group_discount_price_packaging']) ) {
                        $data['products_price_discount_packaging'] = $defPrice['products_group_discount_price_packaging'];
                    }
                } else {
                    throw new Exception('Default Currency not detected');
                }
            }elseif( $key=='inventories' ) {
                if ( !is_array($val) || !isset($val['image']) ) continue;
            }elseif ( $key=='images' ) {
                if ( !is_array($val) || !isset($val['image']) ) continue;
                $images = ArrayHelper::isIndexed($val['image'])?$val['image']:[$val['image']];

                $val = [];
                foreach( $images as $image ) {
                    if ( isset($image['image_descriptions']) ) {
                        $walk_image_descriptions = ArrayHelper::isIndexed($image['image_descriptions']['description'])?$image['image_descriptions']['description']:[$image['image_descriptions']['description']];
                        $image_descriptions = [];
                        foreach( $walk_image_descriptions as $image_description ) {
                            $image_description['use_external_images'] = 0;
                            foreach (array_keys($image_description) as $_imd_key) {
                                if ( is_array($image_description[$_imd_key]) ) unset($image_description[$_imd_key]);
                            }
                            $image_descriptions[$image_description['language']] = $image_description;
                        }
                        $image['image_description'] = $image_descriptions;
                        unset($image['image_descriptions']);
                    }
                    //$descriptionKey = $description['language'].'_0';
                    $val[] = $image;
                }
            }elseif ( $key=='documents' ) {
                if ( !is_array($val) || !isset($val['document']) ) {
                    //$data[$key] = [];
                    continue;
                }
                $documents = ArrayHelper::isIndexed($val['document'])?$val['document']:[$val['document']];

                $val = [];
                foreach( $documents as $document ) {
                    if ( isset($document['descriptions']) ) {
                        $walk_document_descriptions = ArrayHelper::isIndexed($document['descriptions']['description'])?$document['descriptions']['description']:[$document['descriptions']['description']];
                        $document_descriptions = [];
                        foreach( $walk_document_descriptions as $document_description ) {
                            $document_descriptions[ $document_description['language'] ] = $document_description;
                        }
                        $document['titles'] = $document_descriptions;
                        unset($document['descriptions']);
                    }

                    $val[] = $document;
                }
            }elseif ( $key=='properties' ) {
                if (!is_array($val) || !isset($val['property'])) continue;
                $properties = ArrayHelper::isIndexed($val['property']) ? $val['property'] : [$val['property']];
                $val = [];
                foreach ($properties as $property) {
                    unset($property['properties_id']); // don't use/trust client id
                    foreach (['names', 'name_path', 'values'] as $rebuildKey) {
                        if (isset($property[$rebuildKey])) {
                            $rebuildArray = ArrayHelper::isIndexed($property[$rebuildKey]['language_value']) ? $property[$rebuildKey]['language_value'] : [$property[$rebuildKey]['language_value']];
                            $property[$rebuildKey] = [];
                            foreach ($rebuildArray as $lang_value) {
                                $property[$rebuildKey][$lang_value['language']] = $lang_value['text'];
                            }
                        }
                    }
                    $val[] = $property;
                }
            }elseif ( $key=='xsells' ) {
                if ( !is_array($val) || !isset($val['xsell']) ) continue;

                $key = 'xsell';

            }elseif ( is_null($val) || is_array($val) ) {
                continue;
            }

            $data[$key] = $val;
        }
        if ( isset($data['products_date_added']) && $data['products_date_added']>1000 ) {
            $data['products_date_added'] = date('Y-m-d H:i:s', strtotime($data['products_date_added']));
        }
        if ( isset($data['products_last_modified']) && $data['products_last_modified']>1000 ) {
            $data['products_last_modified'] = date('Y-m-d H:i:s', strtotime($data['products_last_modified']));
        }

//echo '<pre>Restored Data '; var_dump($data); echo '</pre>'; die;
        return $data;
    }

    protected function applyRate($price, $rate)
    {
        if ( strpos($price,':')!==false ) {
            // table
            $table = preg_split('/[:;]/',$price,-1);
            $price = '';
            for($i=0; $i<count($table);$i+=2) {
                $price .= "{$table[$i]}:".$table[$i+1]*$rate.";";
            }
        }elseif ( $price>0 ) {
            $price = $price * $rate;
        }
        return $price;
    }
}