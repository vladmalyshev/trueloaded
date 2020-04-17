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
use common\helpers\Tax;
use common\helpers\Product as ProductHelper;
use common\helpers\Inventory as InventoryHelper;
use common\helpers\Customer;
use common\models\Products;
use common\models\ProductsPrices;

class Price {

    use \common\helpers\SqlTrait;

    private static $instanses = [];
    private static $vars = [];

    /*
     * [urpid] => [
     *          'products_price' => [
     *               'value' => 0,
     *               'vars' => [] - settings for caluclation
     *           ],
     *          'special_price' => [
     *               'value' => 0
     *           ]
     *        ]
     */

    private function __construct($uprid) {
        $this->origin_uprid = $uprid;
        $this->uprid = \common\helpers\Product::priceProductId($uprid);
        $this->products_price = [
            'value' => null,
            'vars' => [],
//                'type' => 'unit',
        ];
        $this->calculate_full_price = false;
        $this->type = 'unit';
        $this->qty = 1;
        $this->vids = []; //options
        $this->appendDisablingSettings();
    }

    /**
     * @param $uprid
     * @return self
     */
    public static function getInstance($uprid) {
        if (!isset(self::$instanses[$uprid])) {
            self::$instanses[$uprid] = new self($uprid);
        }
        return self::$instanses[$uprid];
    }
    
    public function appendDisablingSettings(){
        $this->dSettings = new \common\components\DisabledSettings($this->uprid);
    }

    public function setParams(&$params) {
        if (isset($params['widg_id']))
            unset($params['widg_id']);
        if (!isset($params['curr_id']))
            $params['curr_id'] = 0;
        if (!isset($params['group_id']))
            $params['group_id'] = 0;
        return $this;
    }

    public function isChangedQtyType($qty) {
        $changed = false;
        if (is_array($qty)) {

            /* if (count($qty) > 1) {
              //die('stop');
              $fullPrice = 0;
              foreach ($qty as $key => $value) {
              $fullPrice += $this->getProductPrice([$key => $value]);
              }
              $this->products_price['value'] = $fullPrice;
              return $this->products_price['value'];
              } */
            foreach ($qty as $key => $value) {
                if ($this->type != $key) {
                    $changed = true;
                    $this->type = $key;
                    //$this->products_price['type'] = $key;
                }
                //$qty = $value;
                break;
            }
        } else {
            $this->type = 'unit';
            if ($this->qty != $qty){
                $changed = true;
                $this->qty = $qty;
            }
        }
        return $changed;
    }

    /*
     * ready product price
     */

    //$products_id, $qty = 1, $price = 0, $curr_id = 0, $group_id = 0 $customers_id = 0
    public function getProductPrice($params) {

        $this->setParams($params);

        $qty = (isset($params['qty']) ? $params['qty'] : 1);

        $isChanged = $this->isChangedQtyType($qty);

        if (isset($this->products_price['vars']) && $this->products_price['vars'] == $params && !$isChanged) {
            return $this->products_price['value'];
        } else {
            $this->products_price['vars'] = $params;
        }

        $customer_groups_id = (int) \Yii::$app->storage->get('customer_groups_id');
        $_currency_id = (int) (isset($params['curr_id']) && $params['curr_id'] ? $params['curr_id'] : \Yii::$app->settings->get('currency_id'));
        $_customer_groups_id = (int) (isset($params['group_id']) && $params['group_id'] ? $params['group_id'] : $customer_groups_id);

        if ($ext = \common\helpers\Acl::checkExtension('BusinessToBusiness', 'checkShowPrice')) {
            if ($ext::checkShowPrice($_customer_groups_id)) {
                return false;
            }
        }

        //$data = tep_db_fetch_array(tep_db_query("select products_price, products_price_full, pack_unit, products_price_pack_unit, packaging, products_price_packaging from " . TABLE_PRODUCTS . " where products_id = '" . (int) $this->uprid . "'"));
        //echo $this->uprid . "#### <PRE>" .print_r(Yii::$container->get('products'), 1) ."</PRE>";

        $productItem = Yii::$container->get('products')->getProduct((int) $this->uprid);
        if ($productItem) {
            $tmp = $productItem->getArrayCopy();
        }else{
            $productItem = new \common\components\ProductItem(['products_id'=>$this->uprid]);
        }

        $qFields = ['products_price', 'products_price_full', 'pack_unit', 'products_price_pack_unit', 'packaging', 'products_price_packaging'];
        if ($tmp && 
            count(array_diff_key( array_flip($qFields), $tmp)) == 0 // all keys exists
            ) {
          $product = $tmp;

        } else {

          $product = Products::find()->select($qFields)
                        ->where('products_id=:products_id', [':products_id' => (int) $this->uprid])->asArray()->one();
          if (is_array($tmp)) {
            Yii::$container->get('products')->loadProducts($product);
          }
        }

        $this->calculate_full_price = (bool) $product['products_price_full'];
        $this->products_price['value'] = $product['products_price'];

        if (PRODUCTS_BUNDLE_SETS != 'True' && USE_MARKET_PRICES != 'True' && CUSTOMERS_GROUPS_ENABLE != 'True' && $this->products_price['value'] > 0 && $qty == 1) {
            return $this->products_price['value'];
        }

        $data = null;
        /* @var $CustomerProducts \common\extensions\CustomerProducts\CustomerProducts */
        if ($CustomerProducts = \common\helpers\Acl::checkExtension('CustomerProducts', 'allowed')) {
            if ($CustomerProducts::allowed() && !Yii::$app->user->isGuest){
              $customer_id = (int) \Yii::$app->user->getId();
              $_customer_id = (int) (isset($params['customer_id']) && $params['customer_id'] ? $params['customer_id'] : $customer_id);
              $data = $CustomerProducts::getPrice((int) $this->uprid, (int)$_customer_id, (USE_MARKET_PRICES == 'True' ? $_currency_id : '0'));
            }
        }

        $apply_discount = false;
        if (!$data) {
          if (USE_MARKET_PRICES == 'True' || (CUSTOMERS_GROUPS_ENABLE == 'True' && (int)$_customer_groups_id>0) ){
              $data = $productItem->getProductsPrices([':products_id' => (int) $this->uprid, ':groups_id' => (int) $_customer_groups_id, ':currencies_id' => (USE_MARKET_PRICES == 'True' ? $_currency_id : '0')]);
//              $data = ProductsPrices::find()
//                              ->select('products_group_price as products_price, products_group_price_pack_unit as products_price_pack_unit, products_group_price_packaging as products_price_packaging')
//                              ->where('products_id=:products_id and groups_id = :groups_id and currencies_id =:currencies_id', [':products_id' => (int) $this->uprid, ':groups_id' => (int) $_customer_groups_id, ':currencies_id' => (USE_MARKET_PRICES == 'True' ? $_currency_id : '0')])
//                              ->asArray()->one();
              if (!$data || ($data['products_price'] == -2) || (USE_MARKET_PRICES != 'True' && $_customer_groups_id == 0)) {
                  if (USE_MARKET_PRICES == 'True') {
//                      $data = ProductsPrices::find()
//                              ->select('products_group_price as products_price, products_group_price_pack_unit as products_price_pack_unit, products_group_price_packaging as products_price_packaging')
//                                      ->where('products_id=:products_id and groups_id = :groups_id and currencies_id =:currencies_id', [':products_id' => (int) $this->uprid, ':groups_id' => 0, ':currencies_id' => $_currency_id])
//                                      ->asArray()->one();
                      $data = $productItem->getProductsPrices([':products_id' => (int) $this->uprid, ':groups_id' => 0, ':currencies_id' => $_currency_id]);
                  } else {
                      $data = $product;
                  }
                  $apply_discount = true;
              }
          } else {
              $data = $product;
          }
        }

        $this->products_price['value'] = $data['products_price'];

        if ($this->type != 'unit') {
            $this->applyPacks($data['products_price_pack_unit'], $data['products_price_packaging']);
        }

        if ($apply_discount) {
            $this->applyGroupDiscount($_customer_groups_id);
        }

        if ($qty > 1) {
            $this->getProductsDiscountPrice(['qty' => $qty]);
        }

        return $this->products_price['value'];
    }

    private function getPackInfo() {
        return Products::find()->select('pack_unit, packaging')
                        ->where('products_id =:products_id', [':products_id' => (int) $this->uprid])->one();
    }

    public function applyPacks($products_price_pack_unit, $products_price_packaging, $toBase = true) {
        $pack_info = $this->getPackInfo();

        if ($toBase) {
            switch ($this->type) {
                case 'packaging':
                    if ($products_price_packaging > 0) {
                        $this->products_price['value'] = (float) $products_price_packaging;
                    } elseif ($pack_info['pack_unit'] > 0 && $pack_info['packaging'] > 0) {
                        $this->products_price['value'] *= $pack_info['pack_unit'] * $pack_info['packaging'];
                    }
                    break;
                case 'pack_unit':
                    if ($products_price_pack_unit > 0) {
                        $this->products_price['value'] = (float) $products_price_pack_unit;
                    } elseif ($pack_info['pack_unit'] > 0) {
                        $this->products_price['value'] *= $pack_info['pack_unit'];
                    }
                    break;
                case 'unit':
                default :
                    break;
            }
        } else {
            if ($this->calculate_full_price || true) {
                switch ($this->type) {
                    case 'packaging':
                        if ($pack_info['pack_unit'] > 0 && $pack_info['packaging'] > 0) {
                            $this->inventory_price['value'] *= $pack_info['pack_unit'] * $pack_info['packaging'];
                        }
                        break;
                    case 'pack_unit':
                        if ($pack_info['pack_unit'] > 0) {
                            $this->inventory_price['value'] *= $pack_info['pack_unit'];
                        }
                        break;
                    case 'unit':
                    default :
                        break;
                }
            }
        }
    }

    public function applyGroupDiscount($_customer_groups_id) {
        if ($_customer_groups_id && $this->dSettings->applyGroupDiscount()) {
            $discount = Customer::check_customer_groups($_customer_groups_id, 'groups_discount');
            $this->products_price['value'] = $this->products_price['value'] * (1 - ($discount / 100));
        }
    }

    /* params: $qty, $products_price, $curr_id = 0, $group_id = 0 */

    public function getProductsDiscountPrice($params) {
        //[$type => $qty], $data['products_price']

        $this->setParams($params);

        if (is_null($this->products_price['value'])) {
            $this->getProductPrice($params);
        }

        $qty = (isset($params['qty']) ? $params['qty'] : 1);
        $type = 'unit';
        if (is_array($qty)) {
            $qty = current($params['qty']);
            $type = key($params['qty']);
            //list($type, $qty) = each($params['qty']);
        }

        $customer_groups_id = (int) \Yii::$app->storage->get('customer_groups_id');
        $_currency_id = (isset($params['curr_id']) && $params['curr_id'] ? $params['curr_id'] : \Yii::$app->settings->get('currency_id'));
        $_customer_groups_id = (isset($params['group_id']) && $params['group_id'] ? $params['group_id'] : $customer_groups_id);

        if ($this->dSettings->applyQtyDiscount()){
            $apply_discount = false;
            if (USE_MARKET_PRICES == 'True' || CUSTOMERS_GROUPS_ENABLE == 'True') {
                $data = ProductsPrices::find()->select(['products_group_discount_price as products_price_discount', 'products_group_price',
                                    'products_group_discount_price_pack_unit as products_price_discount_pack_unit', 'products_group_price_pack_unit',
                                    'products_group_discount_price_packaging as products_price_discount_packaging', 'products_group_price_packaging'])
                                ->where('products_id = :products_id and groups_id = :groups_id and currencies_id = :currencies_id', [
                                    ':products_id' => (int) $this->uprid,
                                    ':groups_id' => (int) $_customer_groups_id,
                                    ':currencies_id' => (USE_MARKET_PRICES == 'True' ? $_currency_id : '0')
                                ])->asArray()->one();
                if (!$data || ($data['products_price_discount'] == '' && $data['products_group_price'] == -2) || $data['products_price_discount'] == -2 || (USE_MARKET_PRICES != 'True' && $_customer_groups_id == 0)) {
                    if (USE_MARKET_PRICES == 'True') {
                        $data = ProductsPrices::find()->select(['products_group_discount_price as products_price_discount',
                                            'products_group_discount_price_pack_unit as products_price_discount_pack_unit',
                                            'products_group_discount_price_packaging as products_price_discount_packaging'])
                                        ->where('products_id =:products_id and groups_id = 0 and currencies_id = :currencies_id', [
                                            ':products_id' => (int) $this->uprid,
                                            ':currencies_id' => (int) $_currency_id
                                        ])->asArray()->one();
                    } else {
                        $data = Products::find()->select('products_price_discount, products_price_discount_pack_unit, products_price_discount_packaging')
                                        ->where('products_id = :products_id', [':products_id' => (int) $this->uprid])->asArray()->one();
                    }
                    $apply_discount = true;
                }
            } else {
                $data = Products::find()->select('products_price_discount, products_price_discount_pack_unit, products_price_discount_packaging')
                                ->where('products_id = :products_id', [':products_id' => (int) $this->uprid])->asArray()->one();
            }

            switch ($type) {
                case 'packaging':
                    $data['products_price_discount'] = $data['products_price_discount_packaging'];
                    break;
                case 'pack_unit':
                    $data['products_price_discount'] = $data['products_price_discount_pack_unit'];
                    break;
                case 'unit':
                default :
                    break;
            }


            $data['products_price_discount'] = trim($data['products_price_discount'], '; ');

            if ($data['products_price_discount'] == '' || $data['products_price_discount'] == -1 || $data['products_price_discount'] == -2) {
                return $this->products_price['value'];
            }
            $ar = preg_split("/[:;]/", preg_replace('/;\s*$/', '', $data['products_price_discount'])); // remove final separator

            if (!is_array($ar) || count($ar)<2 || count($ar)%2==1) { // incorrect table format - skip
              return $this->products_price['value'];
            }

            for ($i = 0, $n = sizeof($ar); $i < $n; $i = $i + 2) {
                if ($qty < (int)$ar[$i]) {
                    if ($i == 0) {
                        return $this->products_price['value'];
                    }
                    $price = $ar[$i - 1];
                    break;
                }
            }
            if (isset($ar[$i - 2])  && (int)$ar[$i - 2]>0 && $qty >= (int)$ar[$i - 2]) {
                $this->products_price['value'] = $ar[$i - 1];
            }

            if ($apply_discount) {
                $this->applyGroupDiscount($_customer_groups_id);
            }
        }
        
        return $this->products_price['value'];
    }

    /*
     * ready special price
     */

    public function getProductSpecialPrice($params) {

        $this->setParams($params);

        $customer_groups_id = (int) \Yii::$app->storage->get('customer_groups_id');
        $_currency_id = (isset($params['curr_id']) && $params['curr_id'] ? $params['curr_id'] : \Yii::$app->settings->get('currency_id'));
        $_customer_groups_id = (isset($params['group_id']) && $params['group_id'] ? $params['group_id'] : $customer_groups_id);

        if ($ext = \common\helpers\Acl::checkExtension('BusinessToBusiness', 'checkShowPrice')) {
            if ($ext::checkShowPrice($customer_groups_id)) {
                return false;
            }
        }

        if (method_exists(get_called_class(), 'check_product')) {
            if (!ProductHelper::check_product($this->uprid, 1, true)) {
                return false;
            }
        }

        if (is_null($this->products_price['value'])) {
            $this->getProductPrice($params);
        }

        if (is_null($this->special_price['value'])) {
            $this->special_price = [
                'value' => null,
                'vars' => []
            ];
        }

        if (isset($this->special_price['vars']) && $this->special_price['vars'] == $params) {
            /*if ($this->special_price['value']) {
                $this->attachToProduct(['promo_class' => 'sale']);
            }*/
            return $this->special_price['value'];
        } else {
            $this->special_price['vars'] = $params;
        }

        $qty = (isset($params['qty']) ? $params['qty'] : 1);

        $apply_discount = true;
/*
        if ($ext = \common\helpers\Acl::checkExtension('ProductBundles', 'getBundleProducts')) {
            $bundle_products = $ext::getBundleProducts(\common\helpers\Inventory::get_prid($this->uprid), \common\classes\platform::currentId());
            if (count($bundle_products) > 0) {
                $check = Products::find()->select('use_sets_discount, products_sets_discount')->where('products_id=:products_id', [':products_id' => (int)\common\helpers\Inventory::get_prid($this->uprid)])->asArray()->one();
                if ($check['use_sets_discount'] && $check['products_sets_discount'] > 0 && $this->dSettings->applyBundleDiscount()) {
                    $this->special_price['value'] = ($this->products_price['value'] * (100 - $check['products_sets_discount']) / 100);
                    $this->attachToProduct(['promo_class' => 'sale']);
                    $apply_discount = false;
                    //return $this->special_price['value'];
                }
            }
        }
*/
        if ($apply_discount) {
            if ($this->dSettings->applySale()) {
                $special_date_columns = ", s.expires_date AS special_expiration_date ";
                $special_date_columns .= ", IF(s.date_status_change IS NULL,IFNULL(s.specials_last_modified,specials_date_added), GREATEST(s.date_status_change,IFNULL(s.specials_last_modified,s.specials_date_added)) ) AS special_start_date ";
                if (USE_MARKET_PRICES == 'True' || CUSTOMERS_GROUPS_ENABLE == 'True') {
                    if (USE_MARKET_PRICES != 'True' && $_customer_groups_id == 0) {
                        $specials_query = tep_db_query("select s.specials_new_products_price {$special_date_columns} from " . TABLE_SPECIALS . " s where s.products_id = '" . (int) $this->uprid . "' and s.status=1");
                    } else {
                        $specials_query = tep_db_query("select s.specials_id, if(sp.specials_new_products_price is NULL, -2, sp.specials_new_products_price) as specials_new_products_price {$special_date_columns} from " . TABLE_SPECIALS . " s left join " . TABLE_SPECIALS_PRICES . " sp on s.specials_id = sp.specials_id and sp.groups_id = '" . (int) $customer_groups_id . "'  and sp.currencies_id = '" . (USE_MARKET_PRICES == 'True' ? $_currency_id : '0') . "' where s.products_id = '" . (int) $this->uprid . "'  and if(sp.specials_new_products_price is NULL, 1, sp.specials_new_products_price != -1 ) and s.status ");
                    }
                } else {
                    $specials_query = tep_db_query("select s.specials_new_products_price {$special_date_columns} from " . TABLE_SPECIALS . " s where s.products_id = '" . (int) $this->uprid . "' and s.status=1");
                }

                $special_start_date = false;
                $special_expiration_date = false;
                if (tep_db_num_rows($specials_query)) {
                    $special = tep_db_fetch_array($specials_query);
                    $special_start_date = $special['special_start_date'];
                    $special_expiration_date = $special['special_expiration_date'];
                    $this->special_price['value'] = $special['specials_new_products_price'];
                    if ($this->special_price['value'] == -2) {
                        if ($_customer_groups_id != 0) {
                            if (USE_MARKET_PRICES == 'True') {
                                $specials_query = tep_db_query("select specials_new_products_price from " . TABLE_SPECIALS_PRICES . " where specials_id = '" . (int) $special['specials_id'] . "' and currencies_id = '" . (int) $_currency_id . "' and groups_id = 0");
                            } else {
                                $specials_query = tep_db_query("select specials_new_products_price from " . TABLE_SPECIALS . " where products_id = '" . (int) $this->uprid . "' and status");
                            }
                            if (tep_db_num_rows($specials_query)) {
                                $special = tep_db_fetch_array($specials_query);
                                if (Customer::check_customer_groups($_customer_groups_id, 'apply_groups_discount_to_specials')) {
                                    $discount = Customer::check_customer_groups($_customer_groups_id, 'groups_discount');
                                    $this->special_price['value'] = $special['specials_new_products_price'] * (1 - ($discount / 100));
                                } else {
                                    $this->special_price['value'] = $special['specials_new_products_price'];
                                }
                            } else {
                                $this->special_price['value'] = false;
                            }
                        } else {
                            $this->special_price['value'] = false;
                        }
                    }
                } else {
                    $this->special_price['value'] = false;
                }

                if ($this->special_price['value'] <= 0) {
                    $this->special_price['value'] = false;
                }
                if ($this->special_price['value'] >= $this->products_price['value']) {
                    $this->special_price['value'] = false;
                }
                if ($this->special_price['value'] !== false) {
                    $this->attachToProduct(['promo_class' => 'sale', 'special_start_date'=>$special_start_date, 'special_expiration_date' => $special_expiration_date]);
                }
            } else {
                $this->special_price['value'] = false;
            }
        }

        if ($this->dSettings->applyPromotion()){
            $promo = \common\models\Product\PromotionPrice::getInstance($this->uprid);
            $promoSettings = $promo->getSettings();
            if ($promoSettings['to_both'] || $promoSettings['to_preferred']['only_to_base']) {
                if (debug_backtrace()[1]['function'] != 'getInventorySpecialPrice'/* && !\common\helpers\Attributes::has_product_attributes((int)$this->uprid)*/) {
                    $promo_price = $promo->getPromotionPrice();
                    if ($promo_price !== false) {
                        $this->special_price['value'] = $promo_price;
                    }
                }
            }
        }

        return $this->special_price['value'];
    }

    public function updateUprid($new) {
        $this->origin_uprid = $new;
        $this->uprid = \common\helpers\Product::priceProductId($new);
        $this->tax_class_id = null;
        return $this;
    }

    public function getTaxClassId()
    {
        if ( !isset($this->tax_class_id) || is_null($this->tax_class_id) ) {
            $_uprid = InventoryHelper::normalizeInventoryPriceId($this->uprid, $vids, $virtual_vids);
            $_get_class_r = tep_db_query(
                "SELECT IFNULL(i.inventory_tax_class_id,p.products_tax_class_id) AS tax_class_id " .
                "FROM " . TABLE_PRODUCTS . " p " .
                " LEFT JOIN " . TABLE_INVENTORY . " i ON p.products_id=i.prid AND i.products_id='".tep_db_input($_uprid)."' " .
                "WHERE p.products_id='".(int)$_uprid."'"
            );
            if ( tep_db_num_rows($_get_class_r)>0 ) {
                $_get_class = tep_db_fetch_array($_get_class_r);
                $this->tax_class_id = $_get_class['tax_class_id'];
            }
        }
        return $this->tax_class_id;
    }

    public function getInventoryPrice($params) {

        $this->setParams($params);

        if (is_null($this->inventory_price['value'])) {
            $this->inventory_price = [
                'value' => 0,
                'vars' => [],
                'calculated' => false
            ];
        }

        if (isset($this->inventory_price['vars']) && $this->inventory_price['vars'] == $params) {
            return $this->inventory_price['value'];
        } else {
            $this->inventory_price['vars'] = $params;
        }


        if (isset($params['id'])) {
            unset($params['id']);
        }

        $qty = (isset($params['qty']) ? $params['qty'] : 1);

        $isChanged = $this->isChangedQtyType($qty);
        /*
          if (is_null($this->products_price['value']) || $isChanged) {
          $this->getProductPrice($params);
          } */
        $this->getProductPrice($params);

        if (strpos($this->uprid, '%') !== false) {
            $this->inventory_price['value'] = $this->products_price['value'];
            return $this->inventory_price['value'];
        }
        /*
          if (is_array($this->inventory_price['vars']['id'])){
          foreach($this->inventory_price['vars']['id'] as $option => $value){
          if (!$value){
          // return $this->products_price['value'];
          }
          }
          } */
/*
        if (\common\helpers\Acl::checkExtension('ProductBundles', 'allowed') && PRODUCTS_BUNDLE_SETS == 'True') {

            global $currency_id;
            $customer_groups_id = (int) \Yii::$app->storage->get('customer_groups_id');
            $_currency_id = (isset($params['curr_id']) && $params['curr_id'] ? $params['curr_id'] : $currency_id);
            $_customer_groups_id = (isset($params['group_id']) && $params['group_id'] ? $params['group_id'] : $customer_groups_id);

            $products2c_join = '';
            if (platform::activeId()) {
                $products2c_join .= self::sqlProductsToPlatformCategories();
            }

            $bundle_sets_query = tep_db_query("select distinct p.products_id, sp.num_product from " . TABLE_PRODUCTS . " p {$products2c_join} left join " . TABLE_PRODUCTS_PRICES . " pgp on p.products_id = pgp.products_id and pgp.groups_id = '" . (int) $_customer_groups_id . "' and pgp.currencies_id = '" . (int) (USE_MARKET_PRICES == 'True' ? $_currency_id : 0) . "', " . TABLE_SETS_PRODUCTS . " sp where sp.product_id = p.products_id and sp.sets_id = '" . (int) $this->uprid . "' " . ProductHelper::getState(true) . ProductHelper::get_sql_product_restrictions(array('p', 'pd', 's', 'sp', 'pp')) . " and if(pgp.products_group_price is null, 1, pgp.products_group_price != -1 ) order by sp.sort_order");
            if (tep_db_num_rows($bundle_sets_query) > 0) {
                $bundle_sets_price = 0;
                $vids = [];
                $mainVids = [];
                if (isset($this->inventory_price['vars']['id']) && is_array($this->inventory_price['vars']['id'])) {
                    foreach ($this->inventory_price['vars']['id'] as $option => $value) {
                        $this->uprid .= '{' . $option . '}' . $value;
                    }
                }
                $mainUprid = InventoryHelper::normalize_id($this->uprid, $vids);
                $this->vids = [];
                if ($vids) {
                    foreach ($vids as $pk => $pv) {
                        if (strpos($pk, '-') !== false) {
                            $temp = explode("-", $pk);
                            $this->vids[$temp[1]][$temp[0]] = $pv;//products options in bundle
                        } else {
                            $mainVids[$pk] = $pv;//main product option
                        }
                    }
                    if ($this->vids) {
                        $this->updateUprid(InventoryHelper::get_uprid((int) $this->uprid, $mainVids));//to get price for only main product
                    }
                }
            }
        }
*/
        $vids = [];
        $_uprid = InventoryHelper::normalizeInventoryPriceId($this->uprid, $vids, $virtual_vids);
        InventoryHelper::normalizeInventoryPriceId($this->origin_uprid, $__vids, $virtual_vids);

        if (PRODUCTS_INVENTORY == 'True' && !InventoryHelper::disabledOnProduct($_uprid)) {

            $check_inventory = tep_db_fetch_array(tep_db_query("select inventory_id, if(price_prefix = '-', -inventory_price, inventory_price) as inventory_price, inventory_full_price as inventory_full_price from " . TABLE_INVENTORY . " i where products_id = '" . tep_db_input($_uprid) . "' and non_existent = '0' " . InventoryHelper::get_sql_inventory_restrictions(array('i', 'ip')) . " limit 1"));
            if ($check_inventory) {
                if (!$this->calculate_full_price) {
                    $this->inventory_price['value'] = $check_inventory['inventory_price'];
                    $inventory_price = $this->getInventoryGroupPrice($params);
                    if ($inventory_price != -1) {
                        $this->inventory_price['value'] = (float) $this->products_price['value'] + $inventory_price;
                    }
                } else {
                    $this->inventory_price['value'] = $check_inventory['inventory_full_price'];
                    $inventory_price = $this->getInventoryGroupPrice($params);
                    if ($inventory_price != -1) {
                        $this->inventory_price['value'] = (float) $inventory_price;
                    }
                }
            } else {
                $this->inventory_price['value'] = $this->products_price['value'];
            }
            if ($virtual_vids) {
                $this->inventory_price['value'] += \common\helpers\Attributes::get_virtual_attribute_price($this->origin_uprid, $virtual_vids, $params['qty'], $this->inventory_price['value']);
            }
        } else {
            $this->inventory_price['value'] = $this->products_price['value'];
            if ($vids) {
                $attributes_price_percents = [];
                $attributes_price_percents_base = [];
                $attributes_price_fixed = 0;
                foreach ($vids as $options_id => $value) {
                    $option_arr = explode('-', $options_id);
                    $attribute_price_query = tep_db_query("select products_attributes_id, options_values_price, price_prefix, products_attributes_weight, products_attributes_weight_prefix from " . TABLE_PRODUCTS_ATTRIBUTES . " where products_id = '" . (int) ($option_arr[1] > 0 ? $option_arr[1] : $_uprid) . "' and options_id = '" . (int) $option_arr[0] . "' and options_values_id = '" . (int) $value . "'");
                    $attribute_price = tep_db_fetch_array($attribute_price_query);
                    $attribute_price['options_values_price'] = \common\helpers\Attributes::get_options_values_price($attribute_price['products_attributes_id'], $params['qty']);

                    if ( $attribute_price['price_prefix']=='-%' ) {
                        $attributes_price_percents[] = 1-$attribute_price['options_values_price']/100;
                    }elseif($attribute_price['price_prefix'] == '+%'){
                        $attributes_price_percents[] = 1+$attribute_price['options_values_price']/100;
                    }elseif($attribute_price['price_prefix'] == '+%b'){
                        $attributes_price_percents_base[] = $attribute_price['options_values_price']/100;
                    }elseif($attribute_price['price_prefix'] == '-%b'){
                        $attributes_price_percents_base[] = -1*$attribute_price['options_values_price']/100;
                    }else{
                        $attributes_price_fixed += (($attribute_price['price_prefix']=='-')?-1:1)*$attribute_price['options_values_price'];
                    }
                }
                $tmp = $this->inventory_price['value'] += $attributes_price_fixed;
                foreach( $attributes_price_percents_base as $attributes_price_percent ) {
                    $this->inventory_price['value'] += $tmp*$attributes_price_percent;
                }
                foreach( $attributes_price_percents as $attributes_price_percent ) {
                    $this->inventory_price['value'] *= $attributes_price_percent;
                }
            }
        }
/*
        if (\common\helpers\Acl::checkExtension('ProductBundles', 'allowed') && PRODUCTS_BUNDLE_SETS == 'True') {

            if (tep_db_num_rows($bundle_sets_query) > 0) {
                $bundle_sets_price = 0;

                if (is_array($qty)) {
                    $pack_info = $this->getPackInfo();
                    switch ($this->type) {
                        case "pack_unit":
                            $_qty = $pack_info['pack_unit'];
                            $qty = $qty[$this->type] * $pack_info['pack_unit'];
                            break;
                        case "packaging":
                            $_qty = $pack_info['pack_unit'] * $pack_info['packaging'];
                            $qty = $qty[$this->type] * $pack_info['pack_unit'] * $pack_info['packaging'];
                            break;
                        case "unit":default:
                            $_qty = 1;
                            $qty = $qty[$this->type];
                            break;
                    }
                } else {
                    $_qty = 1;
                }
                while ($bundle_sets = tep_db_fetch_array($bundle_sets_query)) {
                    $bundleUprid = $bundle_sets['products_id'];
                    if (isset($this->vids[$bundle_sets['products_id']])) {
                        $bundleUprid = InventoryHelper::normalize_id(InventoryHelper::get_uprid($bundleUprid, $this->vids[$bundle_sets['products_id']]));
                    }

                    $priceInstance = self::getInstance($bundleUprid);
                    try {
                        $bundle_sets_price += $_qty * $bundle_sets['num_product'] * $priceInstance->getInventoryPrice(['qty' => $qty * $bundle_sets['num_product']]);
                    } catch (\Exception $ex) {
                        var_dump($ex);
                    }
                }
                $this->inventory_price['value'] += (float) $bundle_sets_price;
            }
        }
*/

        if ($ext = \common\helpers\Acl::checkExtension('ProductBundles', 'getBundleProducts')) {
            $bundle_products = $ext::getBundleProducts(\common\helpers\Inventory::get_prid($this->uprid), \common\classes\platform::currentId());
            if (count($bundle_products) > 0) {
                $check = Products::find()->select('use_sets_discount, products_sets_discount, products_sets_price_formula')->where('products_id=:products_id', [':products_id' => (int)\common\helpers\Inventory::get_prid($this->uprid)])->asArray()->one();
                if ($check['use_sets_discount'] && $this->dSettings->applyBundleDiscount()) {
                    if (!empty($check['products_sets_price_formula'])) {
                        $products_sets_price_formula = json_decode($check['products_sets_price_formula'], true);
                        if (is_array($products_sets_price_formula) && isset($products_sets_price_formula['formula'])) {
                            $this->inventory_price['value'] = \common\helpers\PriceFormula::apply(
                                            $products_sets_price_formula, [
                                        'price' => floatval($this->inventory_price['value']),
                                        'discount' => floatval($check['products_sets_discount']),
                                        'margin' => 0,
                                        'surcharge' => 0,
                            ]);
                        }
                    } elseif ($check['products_sets_discount'] > 0) {
                        $this->inventory_price['value'] *= (1 - ($check['products_sets_discount'] / 100));
                    }
                }
            }
        }

        //echo '<pre>';print_r($this);
        $this->inventory_price['calculated'] = true;
        return (float) $this->inventory_price['value'];
    }

    /*
     * used for final inventory price
     */

    private function getInventoryGroupPrice($params) {

        $customer_groups_id = (int) \Yii::$app->storage->get('customer_groups_id');
        $_currency_id = (isset($params['curr_id']) && $params['curr_id'] ? $params['curr_id'] : \Yii::$app->settings->get('currency_id'));
        $_customer_groups_id = (isset($params['group_id']) && $params['group_id'] ? $params['group_id'] : $customer_groups_id);

        if ($ext = \common\helpers\Acl::checkExtension('BusinessToBusiness', 'checkShowPrice')) {
            if ($ext::checkShowPrice($_customer_groups_id)) {
                return false;
            }
        }

        if (PRODUCTS_BUNDLE_SETS != 'True' && USE_MARKET_PRICES != 'True' && CUSTOMERS_GROUPS_ENABLE != 'True' && $params['qty'] == 1) {
            return $this->inventory_price['value'];
        }
                
        $discount = 0;
        if (!$this->calculate_full_price) {
            if (USE_MARKET_PRICES == 'True' || CUSTOMERS_GROUPS_ENABLE == 'True') {
                $query = tep_db_query("select inventory_group_price as inventory_price from " . TABLE_INVENTORY_PRICES . " where products_id = '" . tep_db_input($this->uprid) . "' and groups_id = '" . (int) $_customer_groups_id . "' and currencies_id = '" . (USE_MARKET_PRICES == 'True' ? (int) $_currency_id : 0) . "' order by inventory_price asc limit 1");
                $data = tep_db_fetch_array($query);
                if (!$data || ($data['inventory_price'] == -2) || (USE_MARKET_PRICES != 'True' && $_customer_groups_id == 0)) {
                    if (USE_MARKET_PRICES == 'True') {
                        $data = tep_db_fetch_array(tep_db_query("select inventory_group_price as inventory_price from " . TABLE_INVENTORY_PRICES . " where products_id = '" . tep_db_input($this->uprid) . "' and groups_id = '0' and currencies_id = '" . (USE_MARKET_PRICES == 'True' ? (int) $_currency_id : 0) . "' order by inventory_price asc limit 1"));
                    } else {
                        $data['inventory_price'] = $this->inventory_price['value'];
                    }
                    if ($this->dSettings->applyGroupDiscount()){
                        $discount = Customer::check_customer_groups($_customer_groups_id, 'groups_discount');
                        $this->inventory_price['value'] = $data['inventory_price'] * (1 - ($discount / 100));
                    }
                } else {
                    $this->inventory_price['value'] = $data['inventory_price'];
                }
            }
        } else { //do for full price        
            if (USE_MARKET_PRICES == 'True' || CUSTOMERS_GROUPS_ENABLE == 'True') {
                $query = tep_db_query("select inventory_full_price from " . TABLE_INVENTORY_PRICES . " where products_id like '" . tep_db_input($this->uprid) . "' and groups_id = '" . (int) $_customer_groups_id . "' and currencies_id = '" . (USE_MARKET_PRICES == 'True' ? (int) $_currency_id : 0) . "' order by inventory_full_price asc limit 1");
                $data = tep_db_fetch_array($query);
                if (!$data || ($data['inventory_full_price'] == -2) || (USE_MARKET_PRICES != 'True' && $_customer_groups_id == 0)) {
                    if (USE_MARKET_PRICES == 'True') {
                        $data = tep_db_fetch_array(tep_db_query("select inventory_full_price from " . TABLE_INVENTORY_PRICES . " where products_id like '" . tep_db_input($this->uprid) . "' and groups_id = '0' and currencies_id = '" . (USE_MARKET_PRICES == 'True' ? (int) $_currency_id : 0) . "' order by inventory_full_price asc limit 1"));
                    } else {
                        $data['inventory_full_price'] = $this->inventory_price['value']; //tep_db_fetch_array(tep_db_query("select inventory_full_price from " . TABLE_INVENTORY . " where products_id like '" . tep_db_input($this->uprid) . "' order by inventory_full_price asc limit 1"));
                    }
                    if ($this->dSettings->applyGroupDiscount()){
                        $discount = Customer::check_customer_groups($_customer_groups_id, 'groups_discount');
                        $this->inventory_price['value'] = $data['inventory_full_price'] * (1 - ($discount / 100));
                    }
                } else {
                    $this->inventory_price['value'] = $data['inventory_full_price'];
                }
            }
        }

        if ($this->type != 'unit') {
            $this->applyPacks(0, 0, false);
        }


        if (((is_array($params['qty']) && array_sum($params['qty']) > 1) || (!is_array($params['qty']) && $params['qty'] > 1)) && $this->inventory_price['value'] > 0) {
            $this->inventory_price['value'] = $this->getInventoryDiscountPrice($params);
        }
        return $this->inventory_price['value'];
    }

    public function getInventoryDiscountPrice($params) {

        $this->setParams($params);

        $customer_groups_id = (int) \Yii::$app->storage->get('customer_groups_id');

        $_currency_id = (isset($params['curr_id']) && $params['curr_id'] ? $params['curr_id'] : \Yii::$app->settings->get('currency_id'));
        $_customer_groups_id = (isset($params['group_id']) && $params['group_id'] ? $params['group_id'] : $customer_groups_id);

        $qty = (is_array($params['qty']) ? $params['qty'][$this->type] : $params['qty']);

        if ($this->dSettings->applyQtyDiscount()){
            if (!$this->calculate_full_price) {
                $apply_discount = false;
                if (USE_MARKET_PRICES == 'True' || CUSTOMERS_GROUPS_ENABLE == 'True') {
                    $query = tep_db_query("select inventory_group_discount_price as inventory_discount_price, inventory_group_price as inventory_price from " . TABLE_INVENTORY_PRICES . " where products_id = '" . tep_db_input($this->uprid) . "' and groups_id = '" . (int) $_customer_groups_id . "' and currencies_id = '" . (USE_MARKET_PRICES == 'True' ? (int) $_currency_id : 0) . "'");
                    $data = tep_db_fetch_array($query);
                    if (!$data || ($data['inventory_discount_price'] == '' && $data['inventory_price'] == -2) || $data['inventory_discount_price'] == -2 || (USE_MARKET_PRICES != 'True' && $_customer_groups_id == 0)) {
                        if (USE_MARKET_PRICES == 'True') {
                            $data = tep_db_fetch_array(tep_db_query("select inventory_group_discount_price as inventory_discount_price from " . TABLE_INVENTORY_PRICES . " where products_id = '" . tep_db_input($this->uprid) . "' and groups_id = '0' and currencies_id = '" . (USE_MARKET_PRICES == 'True' ? (int) $_currency_id : 0) . "'"));
                        } else {
                            $data = tep_db_fetch_array(tep_db_query("select inventory_discount_price from " . TABLE_INVENTORY . " where products_id = '" . tep_db_input($this->uprid) . "'"));
                        }
                        $apply_discount = true;
                    }
                } else {
                    $data = tep_db_fetch_array(tep_db_query("select inventory_discount_price from " . TABLE_INVENTORY . " where products_id = '" . tep_db_input($this->uprid) . "'"));
                }

                if ($data['inventory_discount_price'] == '') {
                    return $this->inventory_price['value'];
                }
                $ar = preg_split("/[:;]/", preg_replace('/;\s*$/', '', $data['inventory_discount_price'])); // remove final separator
                for ($i = 0, $n = sizeof($ar); $i < $n; $i = $i + 2) {
                    if ($qty < $ar[$i]) {
                        if ($i == 0) {
                            return $this->inventory_price['value'];
                        } else {
                            $this->inventory_price['value'] = $ar[$i - 1];
                            break;
                        }
                    }
                }
                if ($qty >= $ar[$i - 2]) {
                    $this->inventory_price['value'] = $ar[$i - 1];
                }
                if ($apply_discount && $this->dSettings->applyGroupDiscount()) {
                    $discount = Customer::check_customer_groups($_customer_groups_id, 'groups_discount');
                    $this->inventory_price['value'] = $this->inventory_price['value'] * (1 - ($discount / 100));
                }
            } else { // full price
                $apply_discount = false;
                if ((USE_MARKET_PRICES == 'True' || CUSTOMERS_GROUPS_ENABLE == 'True')) {
                    $query = tep_db_query("select inventory_discount_full_price, inventory_full_price from " . TABLE_INVENTORY_PRICES . " where products_id = '" . tep_db_input($this->uprid) . "' and groups_id = '" . (int) $_customer_groups_id . "' and currencies_id = '" . (USE_MARKET_PRICES == 'True' ? (int) $_currency_id : 0) . "'");
                    $data = tep_db_fetch_array($query);
                    if (!$data || ($data['inventory_discount_full_price'] == '' && $data['inventory_full_price'] == -2) || $data['inventory_discount_full_price'] == -2 || (USE_MARKET_PRICES != 'True' && $_customer_groups_id == 0)) {
                        if (USE_MARKET_PRICES == 'True') {
                            $data = tep_db_fetch_array(tep_db_query("select inventory_discount_full_price as inventory_discount_full_price from " . TABLE_INVENTORY_PRICES . " where products_id = '" . tep_db_input($this->uprid) . "' and groups_id = '0' and currencies_id = '" . (USE_MARKET_PRICES == 'True' ? (int) $_currency_id : 0) . "'"));
                        } else {
                            $data = tep_db_fetch_array(tep_db_query("select inventory_discount_full_price from " . TABLE_INVENTORY . " where products_id = '" . tep_db_input($this->uprid) . "'"));
                        }
                        $apply_discount = true;
                    }
                } else {
                    $data = tep_db_fetch_array(tep_db_query("select inventory_discount_full_price from " . TABLE_INVENTORY . " where products_id = '" . tep_db_input($this->uprid) . "'"));
                }

                if ($data['inventory_discount_full_price'] == '') {
                    return $this->inventory_price['value'];
                }
                $ar = preg_split("/[:;]/", preg_replace('/;\s*$/', '', $data['inventory_discount_full_price'])); // remove final separator
                for ($i = 0, $n = sizeof($ar); $i < $n; $i = $i + 2) {
                    if ($qty < $ar[$i]) {
                        if ($i == 0) {
                            return $this->inventory_price['value'];
                        } else {
                            $this->inventory_price['value'] = $ar[$i - 1];
                            break;
                        }
                    }
                }
                if ($qty >= $ar[$i - 2]) {
                    $this->inventory_price['value'] = $ar[$i - 1];
                }
                if ($apply_discount && $this->dSettings->applyGroupDiscount()) {
                    $discount = Customer::check_customer_groups($_customer_groups_id, 'groups_discount');
                    $this->inventory_price['value'] = $this->inventory_price['value'] * (1 - ($discount / 100));
                }
            }
        }

        return $this->inventory_price['value'];
    }

    public function getInventorySpecialPrice($params) {

        $this->setParams($params);

        if (is_null($this->inventory_special_price['value'])) {
            $this->inventory_special_price = [
                'value' => false,
                'vars' => []
            ];
        }

        $qty = (isset($params['qty']) ? $params['qty'] : 1);
        $is_changed = $this->isChangedQtyType($qty);

        if (is_null($this->special_price['value']) || $is_changed) {
            $this->getProductSpecialPrice($params);
        }

        if (!isset($this->inventory_price['calculated']) || !$this->inventory_price['calculated'] || $is_changed) {
            $this->getInventoryPrice($params);
        }

        if (isset($this->inventory_special_price['vars']) && $this->inventory_special_price['vars'] == $params && !$is_changed) {
            if ($this->inventory_special_price['value']) {
                $this->attachToProduct(['promo_class' => 'sale']);
            }
            return $this->inventory_special_price['value'];
        } else {
            $this->inventory_special_price['vars'] = $params;
        }

        $apply_discount = true;
/*
        if (\common\helpers\Acl::checkExtension('ProductBundles', 'allowed') && PRODUCTS_BUNDLE_SETS == 'True') {

            global $currency_id;
            $customer_groups_id = (int) \Yii::$app->storage->get('customer_groups_id');
            $_currency_id = (isset($params['curr_id']) && $params['curr_id'] ? $params['curr_id'] : $currency_id);
            $_customer_groups_id = (isset($params['group_id']) && $params['group_id'] ? $params['group_id'] : $customer_groups_id);

            $products2c_join = '';
            if (platform::activeId()) {
                $products2c_join .= self::sqlProductsToPlatformCategories();
            }

            $bundle_sets_query = tep_db_query("select distinct p.products_id, sp.num_product from " . TABLE_PRODUCTS . " p {$products2c_join} left join " . TABLE_PRODUCTS_PRICES . " pgp on p.products_id = pgp.products_id and pgp.groups_id = '" . (int) $_customer_groups_id . "' and pgp.currencies_id = '" . (int) (USE_MARKET_PRICES == 'True' ? $_currency_id : 0) . "', " . TABLE_SETS_PRODUCTS . " sp where sp.product_id = p.products_id and sp.sets_id = '" . (int) $this->uprid . "' " . ProductHelper::getState(true) . ProductHelper::get_sql_product_restrictions(array('p', 'pd', 's', 'sp', 'pp')) . " and if(pgp.products_group_price is null, 1, pgp.products_group_price != -1 ) order by sp.sort_order");
            if (tep_db_num_rows($bundle_sets_query) > 0) {
                $sets_discount = tep_db_fetch_array(tep_db_query("select products_sets_discount from " . TABLE_PRODUCTS . " where products_id = '" . (int) $this->uprid . "'"));
                if ($sets_discount['products_sets_discount'] > 0 && $this->dSettings->applyBundleDiscount()) {
                    $this->inventory_special_price['value'] = ($this->inventory_price['value'] * (100 - $sets_discount['products_sets_discount']) / 100);
                    $this->attachToProduct(['promo_class' => 'sale']);
                    $apply_discount = false;
                    //return $this->inventory_special_price['value'];
                }
            }
        }
*/
        if ($apply_discount) {
            if (PRODUCTS_INVENTORY == 'True' && $this->dSettings->applySale()) { //2do specials for inventory
                if ($this->calculate_full_price) {
                    if ($this->special_price['value'] !== false) {
                        $this->inventory_special_price['value'] = $this->special_price['value'];
                    } else {
                        $this->inventory_special_price['value'] = false; //temporary while absent specials on inventory
                    }
                } else {
                    if ($this->special_price['value'] !== false) {
                        $this->inventory_special_price['value'] = $this->inventory_price['value'] - $this->products_price['value'];
                        // {{ PERCENT DISCOUNT TO ATTRIBUTES PRICE
                        if ( false && $this->products_price['value']!=0 ) {
                            $special_price_rate = $this->special_price['value'] * 100 / $this->products_price['value'] / 100;
                            $this->inventory_special_price['value'] *= $special_price_rate;
                        }
                        // }} PERCENT DISCOUNT TO ATTRIBUTES PRICE
                        //
                        $_special = $this->special_price['value'];

                        if ($this->type != 'unit') {
                            $pack_info = $this->getPackInfo();
                            if ($this->type == 'packaging') {
                                $_special *= $pack_info['pack_unit'] * $pack_info['packaging'];
                            } elseif ($this->type == 'pack_unit') {
                                $_special *= $pack_info['pack_unit'];
                            }
                        }
                        $this->inventory_special_price['value'] += $_special;

                        if ($this->inventory_special_price['value'] < 0) {
                            $this->inventory_special_price['value'] = 0;
                        }
                    }
                }
                if ($this->inventory_special_price['value']) {
                    $this->attachToProduct(['promo_class' => 'sale']);
                }
            } else {
                $this->inventory_special_price['value'] = $this->special_price['value'];
            }
        }
/*
        if (\common\helpers\Acl::checkExtension('ProductBundles', 'allowed') && PRODUCTS_BUNDLE_SETS == 'True' && $apply_discount) {
            if (tep_db_num_rows($bundle_sets_query) > 0) {
                $bundle_sets_price = 0;

                if (is_array($qty)) {
                    $pack_info = $this->getPackInfo();
                    switch ($this->type) {
                        case "pack_unit":
                            $_qty = $pack_info['pack_unit'];
                            $qty = $qty[$this->type] * $pack_info['pack_unit'];
                            break;
                        case "packaging":
                            $_qty = $pack_info['pack_unit'] * $pack_info['packaging'];
                            $qty = $qty[$this->type] * $pack_info['pack_unit'] * $pack_info['packaging'];
                            break;
                        case "unit":default:
                            $_qty = 1;
                            $qty = $qty[$this->type];
                            break;
                    }
                } else {
                    $_qty = 1;
                }
                $bundles = [];
                $use_specials = false;
                $bases = [];
                while ($bundle_sets = tep_db_fetch_array($bundle_sets_query)) {
                    $bundleUprid = $bundle_sets['products_id'];
                    if (isset($this->vids[$bundle_sets['products_id']])) {
                        $bundleUprid = InventoryHelper::normalize_id(InventoryHelper::get_uprid($bundleUprid, $this->vids[$bundle_sets['products_id']]));
                    }
                    $priceInstance = self::getInstance($bundleUprid);
                    try {
                        $_special = $priceInstance->getInventorySpecialPrice(['qty' => $qty * $bundle_sets['num_product']]);
                        $_price = $priceInstance->getInventoryPrice(['qty' => $qty * $bundle_sets['num_product']]);
                        if ($_special !== false) {
                            $bundles[] = $_qty * $bundle_sets['num_product'] * $_special;
                            $use_specials = true;
                        } else {
                            $bundles[] = $_qty * $bundle_sets['num_product'] * $_price;
                        }
                        $bases[] = $_qty * $bundle_sets['num_product'] * $_price;
                    } catch (\Exception $ex) {
                        var_dump($ex);
                    }
                }
                if ($use_specials) {
                    if ($this->inventory_special_price['value'] !== false) {
                        $this->inventory_special_price['value'] = $this->inventory_special_price['value'] - (float) array_sum($bases) + (float) array_sum($bundles);
                    } else {
                        $this->inventory_special_price['value'] = $this->inventory_price['value'] - array_sum($bases) + array_sum($bundles);
                    }
                }
            }
        }
*/
        if ($this->dSettings->applyPromotion()){
            $promo = \common\models\Product\PromotionPrice::getInstance($this->uprid);
            //$promoSettings = $promo->getSettings();
            //if ($promoSettings['to_both'] || $promoSettings['to_preferred']['only_to_inventory']||true) {
                $promo_price = $promo->getPromotionPrice();
                if ($promo_price !== false) {
                    $this->inventory_special_price['value'] = $promo_price;
                }
            //}
        }

        return $this->inventory_special_price['value'];
    }

    public function attachToProduct($params) {
        $products = \Yii::$container->get('products');
        $product = $products->getProduct($this->uprid);
        if ($product) {
            $product->attachDetails($params);
        }
        return;
    }

}
