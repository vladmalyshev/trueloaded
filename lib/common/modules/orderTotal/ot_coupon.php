<?php

/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */
namespace common\modules\orderTotal;

use common\classes\modules\ModuleTotal;
use common\classes\modules\ModuleStatus;
use common\classes\modules\ModuleSortOrder;
use frontend\design\CartDecorator;

class ot_coupon extends ModuleTotal {

    var $title, $output;
    protected $config;
    protected $products_in_order; // VL strange idea to save 2 arrays
    protected $valid_products;
    protected $validProducts; // same purpose but with all details from cart (tax etc)

    protected $defaultTranslationArray = [
        'MODULE_ORDER_TOTAL_COUPON_TITLE' => 'Discount Coupons',
        'MODULE_ORDER_TOTAL_COUPON_HEADER' => 'Gift Vouchers/Discount Coupons',
        'MODULE_ORDER_TOTAL_COUPON_DESCRIPTION' => 'Discount Coupon',
        'SHIPPING_NOT_INCLUDED' => ' [Shipping not included]',
        'TAX_NOT_INCLUDED' => ' [Tax not included]',
        'MODULE_ORDER_TOTAL_COUPON_USER_PROMPT' => '',
        'ERROR_NO_INVALID_REDEEM_COUPON' => 'Invalid Coupon Code',
        'ERROR_INVALID_STARTDATE_COUPON' => 'This coupon is not available yet',
        'ERROR_INVALID_FINISDATE_COUPON' => 'This coupon has expired',
        'ERROR_INVALID_USES_COUPON' => 'This coupon could only be used ',
        'TIMES' => ' times.',
        'ERROR_INVALID_USES_USER_COUPON' => 'You have used the coupon the maximum number of times allowed per customer.',
        'REDEEMED_COUPON' => 'a coupon worth ',
        'REDEEMED_MIN_ORDER' => 'on orders over ',
        'REDEEMED_RESTRICTIONS' => ' [Product-Category restrictions apply]',
        'TEXT_ENTER_COUPON_CODE' => 'Enter Redeem Code&nbsp;&nbsp;'
    ];

    function __construct() {
        parent::__construct();

        $this->code = 'ot_coupon';
        $this->header = MODULE_ORDER_TOTAL_COUPON_HEADER;
        $this->title = MODULE_ORDER_TOTAL_COUPON_TITLE;
        $this->description = MODULE_ORDER_TOTAL_COUPON_DESCRIPTION;
        if (!defined('MODULE_ORDER_TOTAL_COUPON_STATUS')) {
            $this->enabled = false;
            return false;
        }
        $this->user_prompt = '';
        $this->enabled = (defined('MODULE_ORDER_TOTAL_COUPON_STATUS') && MODULE_ORDER_TOTAL_COUPON_STATUS == 'true');
        $this->sort_order = MODULE_ORDER_TOTAL_COUPON_SORT_ORDER;
        $this->include_shipping = 0;
        $this->include_tax = 0;
        $this->calculate_tax = false;
        $this->tax_class = 0;
        $this->credit_class = true;
        $this->output = array();
        $this->config = array(
            'ONE_PAGE_CHECKOUT' => defined('ONE_PAGE_CHECKOUT') ? ONE_PAGE_CHECKOUT : 'False',
            'ONE_PAGE_SHOW_TOTALS' => defined('ONE_PAGE_SHOW_TOTALS') ? ONE_PAGE_SHOW_TOTALS : 'false',
        );
        $this->products_in_order = [];
        $this->valid_products = [];
        $this->validProducts = [];
    }

    function config($data) {
        if (is_array($data)) {
            $this->config = array_merge($this->config, $data);
        }
    }

    function process($replacing_value = -1, $visible = false) {

        $currencies = \Yii::$container->get('currencies');
        $order = $this->manager->getOrderInstance();

        $this->tax_before_calculation = $order->info['tax'];

        $order_total = $this->get_order_total();
        $result = $this->calculate_credit($order_total);

        $discountSumm = $result['deduct'];
        $method = $result['method'];
        $this->deduction = $discountSumm;

        $taxDiscountSumm = ($discountSumm > 0 ? $this->calculate_tax_deduction($order_total, $this->deduction) : 0);

        if ($replacing_value != -1) {
            if (is_array($replacing_value)) {
                $discountSumm = $replacing_value['ex'];
                $taxDiscountSumm = $replacing_value['in'] - $replacing_value['ex'];
            }
            $cart = $this->manager->getCart();
            $cart->setTotalKey($this->code, $replacing_value);
            if (($_title = $cart->getTotalTitle($this->code)) !== false && empty($this->coupon_code)) {
              $ex = array();
                preg_match("/\((.*)\)$/", $_title, $ex);
                if (is_array($ex) && isset($ex[1])) {
                    $this->coupon_code = $ex[1];
                }
                $order_total = $this->get_order_total();
                $result = $this->calculate_credit($order_total);
                $this->deduction = $discountSumm;
                $taxDiscountSumm = $this->calculate_tax_deduction($order_total, $this->deduction);
                if (!$this->tax_before_calculation) {
                    $taxDiscountSumm = 0;
                }
                if (is_array($replacing_value)) {
                    $replacing_value['ex'] = $discountSumm;
                    $replacing_value['in'] = $taxDiscountSumm + $discountSumm;
                }
                $cart->setTotalKey($this->code, $replacing_value);
            }
        }

        if ($discountSumm > 0 || $visible) {

            if (DISPLAY_PRICE_WITH_TAX == 'true') {
                $order->info['total'] = $order->info['total'] - ($discountSumm + $taxDiscountSumm);
            } else {
                $order->info['total'] = $order->info['total'] - $discountSumm;
            }
            $order->info['total_inc_tax'] = $order->info['total_inc_tax'] - ($discountSumm + $taxDiscountSumm);
            $order->info['total_exc_tax'] = $order->info['total_exc_tax'] - $discountSumm;
            if ($order->info['total'] < 0) {
                //$order->info['total']=0;
            }
            if ($order->info['total_inc_tax'] < 0) {
                //$order->info['total_inc_tax']=0;
            }
            if ($order->info['total_exc_tax'] < 0) {
                //$order->info['total_exc_tax']=0;
            }

            if (DISPLAY_PRICE_WITH_TAX == 'true') {
                $_od_amount = $discountSumm + $taxDiscountSumm;
            } else {
                $_od_amount = $discountSumm;
            }

            parent::$adjusting -= $currencies->format_clear($discountSumm, true, $order->info['currency'], $order->info['currency_value']);
            $this->output[] = array(
                'title' => $this->title . (tep_not_null($this->coupon_code) ? '&nbsp;(' . $this->coupon_code . ')' : '') . ($method == 'free_shipping' ? " " . TEXT_FREE_SHIPPING : ''),
                'text' => '-' . $currencies->format($_od_amount),
                'value' => $_od_amount,
                'text_exc_tax' => '-' . $currencies->format($discountSumm),
                'text_inc_tax' => '-' . $currencies->format($discountSumm + $taxDiscountSumm),
                'tax_class_id' => $this->tax_class,
                'value_exc_vat' => $discountSumm,
                'value_inc_tax' => $discountSumm + $taxDiscountSumm,
            );
        }
    }

    function selection_test() {
        return false;
    }

    function pre_confirmation_check($order_total) {
        $result = $this->calculate_credit($order_total);
        return $result['deduct'];
    }

    function collect_posts($collect_data) {
      $result = $this->_collect_posts($collect_data);
      return $result;
    }

    /**
     * validate coupon against all restrictions
     * @param \common\models\Coupons $coupon
     * @return bool|string true(ok) or error message
     */
    private function _validate($coupon) {
      $ret = true;

      if (!$coupon) {
        $ret = ERROR_NO_INVALID_REDEEM_COUPON;
      }

      elseif ($coupon->isStartDateInvalid()){
        $ret = ERROR_INVALID_STARTDATE_COUPON;
      }

      elseif ($coupon->isEndDateExpired()){
        $ret = ERROR_INVALID_FINISDATE_COUPON;
      }

      elseif ($coupon->uses_per_coupon>0
          && $coupon->uses_per_coupon <= \common\models\CouponRedeemTrack::find()->andWhere(['coupon_id' => $coupon->coupon_id])->count()) {
        $ret = ERROR_INVALID_USES_COUPON . $coupon->uses_per_coupon . TIMES;
      }

      elseif ($this->isRestrictedByCountry($coupon)){
        $ret = ERROR_INVALID_COUNTRY_COUPON;
      }
      
      elseif ($this->manager->isCustomerAssigned() && !empty($coupon->restrict_to_customers) &&
          strtolower(trim($this->manager->getCustomersIdentity()->customers_email_address)) != strtolower(trim($coupon->restrict_to_customers))
          ){
        $ret = ERROR_COUPON_FOR_OTHER_CUSTOMER;
      }

      elseif ($this->manager->isCustomerAssigned() && $coupon->uses_per_user>0
          && $coupon->uses_per_user <= \common\models\CouponRedeemTrack::find()->alias('crt')
          ->innerJoin(TABLE_ORDERS . ' o', 'crt.order_id = o.orders_id')
          ->andWhere(['coupon_id' => $coupon->coupon_id])
          ->andWhere([ 'or',
            ['crt.customer_id' => (int)$this->manager->getCustomerAssigned()],
            ['o.customers_email_address' => $this->manager->getCustomersIdentity()->customers_email_address]
              ])
          ->count()
          ) {
          
            $ret = ERROR_INVALID_USES_USER_COUPON . $coupon->uses_per_user . TIMES;
      }

      return $ret;

    }

    private function _collect_posts($collect_data) {
        if (isset($collect_data['gv_redeem_code']) && !empty($collect_data['gv_redeem_code']) &&
            (!$this->manager->has('cc_id') || ($this->manager->has('cc_id') && \common\helpers\Coupon::get_coupon_name($this->manager->get('cc_id')) != $collect_data['gv_redeem_code'] )  )) {

          $coupon_result  = \common\models\Coupons::find()->active()
              ->andWhere(['coupon_code' => $collect_data['gv_redeem_code']])
              ->one();

          $check = $this->_validate($coupon_result);
          if ($check !== true) {
            return array('error' => true, 'message' => $check);
          }

          if ($coupon_result['coupon_type'] != 'G') {
            if ($coupon_result['coupon_type'] == 'S') {
                $coupon_amount = TEXT_FREE_SHIPPING;
            } else {
                $coupon_amount = 'Code accepted';
            }

            $this->manager->set('cc_id', $coupon_result['coupon_id']);

            return array('error' => false, 'message' => $coupon_amount);
          }
          if (!$collect_data['gv_redeem_code']) {
              return array('error' => true, 'message' => ERROR_NO_REDEEM_CODE);
          }
        }
    }

    /**
     * calculates discount for specified amount 
     * @param number $order_total
     * @return array ['deduct' => nn.nn method=><free_shipping|standard> ]
     */
    function calculate_credit($order_total) {
        $order = $this->manager->getOrderInstance();
        $currencies = \Yii::$container->get('currencies');
        $od_amount = 0;
        $result = [];
        if ($this->manager->has('cc_id') || tep_not_null($this->coupon_code)) {
          if (tep_not_null($this->coupon_code)) {
            $where = ['or',
                    ['coupon_id' => (int) $this->manager->get('cc_id')],
                    ['coupon_code' => $this->coupon_code]
                ];
          } else {
            $where = ['coupon_id' => (int) $this->manager->get('cc_id')];
          }
          $get_result  = \common\models\Coupons::find()->active()->andWhere($where)->one();
          if ($get_result) {
              if ($this->_validate($get_result) === true){
                  $this->coupon_code = $get_result['coupon_code'];
                  $this->tax_class = $get_result['tax_class_id'];
                  $c_deduct = $get_result['coupon_amount'] * $currencies->get_market_price_rate($get_result['coupon_currency'], DEFAULT_CURRENCY);
                  $result['method'] = 'standard';
                  $get_result['coupon_minimum_order'] *= $currencies->get_market_price_rate($get_result['coupon_currency'], DEFAULT_CURRENCY);
                  if ($get_result['coupon_type'] == 'S') {
                      $od_amount = $order->info['shipping_cost'];
                      $result['method'] = 'free_shipping';
                  } else {
                      if ($get_result['coupon_minimum_order'] <= $order_total) {
                          if ($get_result['coupon_type'] != 'P') { // fixed discount  tax specified in coupon
                              if ($get_result['flag_with_tax']) {
                                $taxation = $this->getTaxValues($this->tax_class);
                                $tax_rate = $taxation['tax'];
                                $c_deduct = \common\helpers\Tax::get_untaxed_value($c_deduct, $tax_rate);
                              }
                              $od_amount = $c_deduct;
                          } else {
                              $od_amount = $order_total * $get_result['coupon_amount'] / 100;
                          }
                      }
                  }
              }
          }
          if ($od_amount > $order_total && $result['method'] != 'free_shipping') {
            $od_amount = $order_total;
          }
        }
        $result['deduct'] = $od_amount;
        return $result;
    }

    function calculate_tax_deduction($amount, $od_amount) {
        //global $customer_id, $cc_id, $cart, $shipping;
        $order = $this->manager->getOrderInstance();
        if (tep_not_null($this->coupon_code)) {
          $where = ['or',
                  ['coupon_id' => (int) $this->manager->get('cc_id')],
                  ['coupon_code' => $this->coupon_code]
              ];
        } else {
          $where = ['coupon_id' => (int) $this->manager->get('cc_id')];
        }
        $get_result  = \common\models\Coupons::find()->active()->andWhere($where)->one();
        if ($get_result) {
            if ($this->_validate($get_result) === true){
                $this->tax_class = $get_result['tax_class_id'];
                $this->include_shipping = $get_result['uses_per_shipping'];
                $shipping_tax_class_id = 0;
                if ($this->include_shipping || $get_result['coupon_type'] == 'S') {
                    $shipping = $this->manager->getShipping();
                    if (is_array($shipping)) {
                        $sModule = $this->manager->getShippingCollection()->get($shipping['module']);
                        if ($sModule) {
                            $shipping_tax_class_id = $sModule->tax_class;
                        }
                    }
                    $taxation = $this->getTaxValues($shipping_tax_class_id);
                    $shipping_tax = $taxation['tax'];
                    $shipping_tax_desc = $taxation['tax_description'];
                }
                $taxDiscountSumm = 0;
                $DISABLE_FOR_SPECIAL =  defined('MODULE_ORDER_TOTAL_COUPON_DISABLE_FOR_SPECIAL') && MODULE_ORDER_TOTAL_COUPON_DISABLE_FOR_SPECIAL=='True';
                if (!empty($get_result->disable_for_special)) {
                  $DISABLE_FOR_SPECIAL =  true;
                }
                if ($DISABLE_FOR_SPECIAL || $get_result['restrict_to_products'] || $get_result['restrict_to_categories']
                        || $get_result['exclude_categories']  || $get_result['exclude_products'] ) {

                  if (is_array($this->validProducts) && count($this->validProducts)) {
                    if ($get_result['coupon_type'] == 'F') {
                      $taxation = $this->getTaxValues($this->tax_class);
                      $tax_rate = $taxation['tax'];
                      if (!isset($order->info['tax_groups'][$taxation['tax_description']])) {
                        $tax_rate = 0;
                      }
                    }

                    foreach ($this->validProducts as $key => $value) {
                      if ($get_result['coupon_type'] == 'P') {
                        $taxation = $this->getTaxValues($value['tax_class_id']);
                        $tax_rate = $taxation['tax'];
                      }

                      if ($tax_rate) {
                        $prodTaxDiscount = $value['final_price_exc'] * $tax_rate / 100 * $get_result['coupon_amount']/100;
                        $taxDiscountSumm += $prodTaxDiscount;
                        if (!empty($order->info['tax_groups'][$taxation['tax_description']])) {
                          $order->info['tax_groups'][$taxation['tax_description']] -= $prodTaxDiscount;
                        }
                        $order->info['tax'] -= $prodTaxDiscount;
                      }
                    }
                  }
                  if ($this->include_shipping) {
                    $shippingTaxDiscountSumm = 0;
                    if ($get_result['coupon_type'] == 'P') {
                      $shipping_tax_calculated = \common\helpers\Tax::calculate_tax($order->info['shipping_cost_exc_tax'], $shipping_tax);
                      $shippingTaxDiscountSumm = $shipping_tax_calculated / 100 * $get_result['coupon_amount'];
                    } else { //fixed
                      //calculate discount on shipping: $amount contains shipping and max allowed discount is $od_amount;
                      if ($amount <= $od_amount) {
                        $shippingDiscount = $order->info['shipping_cost_exc_tax'];
                      } else {
                        $shippingDiscount = $amount - $od_amount;
                      }
                      $shippingDiscount = min($shippingDiscount, $order->info['shipping_cost_exc_tax']);
                      $shippingTaxDiscountSumm = \common\helpers\Tax::calculate_tax($shippingDiscount, $shipping_tax);
                    }
                    if ($shipping_tax_calculated && $shippingTaxDiscountSumm>0) {
                      if (isset($order->info['tax_groups'][$shipping_tax_desc])) {
                        $order->info['tax_groups'][$shipping_tax_desc] = $order->info['tax_groups'][$shipping_tax_desc] - $shippingTaxDiscountSumm;
                      }
                      $order->info['tax'] -= $shippingTaxDiscountSumm;
                      $taxDiscountSumm += $shippingTaxDiscountSumm;
                    }
                  }

/*
                        if (is_array($products_in_order) && count($products_in_order)) {
                            $products_in_order = \yii\helpers\ArrayHelper::map($this->products_in_order, 'quantity', 'final_price', 'id');
                            $products_in_order_taxes = \yii\helpers\ArrayHelper::map($this->products_in_order, 'id', 'tax_class_id');
                        } else {
                            $products_in_order = [];
                            $products_in_order_taxes = [];
                        }
                        if (is_array($products_in_order) && count($products_in_order) > 0) {
                            foreach ($products_in_order as $pid => $details) {
                                if (array_key_exists($pid, $this->valid_products)) {
                                    $quantity = key($details);
                                    $final_price = current($details);
                                    $t = \common\helpers\Tax::get_tax_rate($products_in_order_taxes[$pid], $this->delivery['country']['id'], $this->delivery['zone_id']);
                                    $ptod_amount += \common\helpers\Tax::calculate_tax(($quantity * $final_price), $t);
                                }
                            }
                        }

                        if ($get_result['coupon_type'] == 'P') {
                            if ($this->tax_class) {
                                $taxation = $this->getTaxValues($this->tax_class, $order);
                                $tax_rate = $taxation['tax'];
                                if ($tax_rate) {
                                    if ($ptod_amount > 0) {
                                        $taxDiscountSumm = $ptod_amount / 100 * $get_result['coupon_amount'];
                                    }
                                    if (is_array($order->info['tax_groups'])) foreach ($order->info['tax_groups'] as $key => $value) {
                                        $order->info['tax_groups'][$key] = $order->info['tax_groups'][$key] - $taxDiscountSumm;
                                    }
                                    if ($this->include_shipping) {
                                        $shipping_tax_calculated = \common\helpers\Tax::calculate_tax($order->info['shipping_cost_exc_tax'], $shipping_tax);
                                        $god_amount = $shipping_tax_calculated / 100 * $get_result['coupon_amount'];
                                        if ($shipping_tax_calculated) {
                                            if (isset($order->info['tax_groups'][$shipping_tax_desc]))
                                                $order->info['tax_groups'][$shipping_tax_desc] = $order->info['tax_groups'][$shipping_tax_desc] - $god_amount;
                                        }
                                    }
                                    $order->info['tax'] -= $taxDiscountSumm;
                                }
                            }
                        } else {
                            $taxation = $this->getTaxValues($this->tax_class, $order);
                            $tax_rate = $taxation['tax'];
                            $tax_desc = $taxation['tax_description'];

                            if ($get_result['coupon_type'] != 'S') {
                                $taxDiscountSumm = $od_amount / 100 * (100 + $tax_rate) - $od_amount; //$od_amount / (100 + $tax_rate) * $tax_rate;
                            }

                            if ($ptod_amount > 0) {
                                $taxDiscountSumm = min($taxDiscountSumm, $ptod_amount);
                                if ($this->include_shipping || $get_result['coupon_type'] == 'S') {

                                    $shipping_tax_calculated = \common\helpers\Tax::calculate_tax($order->info['shipping_cost'], $shipping_tax);
                                    if ($get_result['coupon_type'] == 'S') {
                                        $taxDiscountSumm = $shipping_tax_calculated;
                                    }
                                    $order->info['total_inc_tax'] -= $shipping_tax_calculated;

                                    $taxDiscountSumm = min($taxDiscountSumm, ($order->info['subtotal_inc_tax'] - $order->info['subtotal_exc_tax'] + $shipping_tax_calculated));
                                }
                                if (isset($order->info['tax_groups'][$tax_desc])) {
                                    $order->info['tax'] -= $taxDiscountSumm;
                                    $order->info['tax_groups'][$tax_desc] -= $taxDiscountSumm;
                                } else {
                                    $taxDiscountSumm = 0;
                                }
                            }
                        }

 */
                    } else {
                        if ($get_result['coupon_type'] == 'P') {
                            if ($this->tax_class) {
                                $taxation = $this->getTaxValues($this->tax_class, $order);
                                $tax_rate = $taxation['tax'];
                                if ($tax_rate) {

                                    if (is_array($order->info['tax_groups'])) foreach ($order->info['tax_groups'] as $key => $value) {
                                        $god_amount = $value / 100 * $get_result['coupon_amount'];
                                        $order->info['tax_groups'][$key] = $order->info['tax_groups'][$key] - $god_amount;
                                        $taxDiscountSumm += $god_amount;
                                    }
                                    if ($this->include_shipping) {
                                        $shipping_tax_calculated = \common\helpers\Tax::calculate_tax($order->info['shipping_cost_exc_tax'], $shipping_tax);
                                        $god_amount = $shipping_tax_calculated / 100 * $get_result['coupon_amount'];

                                        if ($shipping_tax_calculated) {
                                            if (isset($order->info['tax_groups'][$shipping_tax_desc]))
                                                $order->info['tax_groups'][$shipping_tax_desc] = $order->info['tax_groups'][$shipping_tax_desc] - $god_amount;
                                        }
                                        $taxDiscountSumm += $god_amount;
                                    }
                                    $order->info['tax'] -= $taxDiscountSumm;
                                }
                            }
                        } else { //F or S
                            $taxation = $this->getTaxValues($this->tax_class, $order);
                            $tax_rate = $taxation['tax'];
                            $tax_desc = $taxation['tax_description'];
                            $taxDiscountSumm = \common\helpers\Tax::calculate_tax($od_amount, $tax_rate);
                            if ($get_result['coupon_type'] != 'S') {
                                //$taxDiscountSumm = $od_amount / 100  * (100 + $tax_rate) - $od_amount;//$od_amount / (100 + $tax_rate) * $tax_rate;
                            }
                            $shipping_tax_calculated = 0;
                            if ($this->include_shipping || $get_result['coupon_type'] == 'S') {
                                $shipping_tax_calculated = \common\helpers\Tax::calculate_tax($order->info['shipping_cost'], $shipping_tax);

                                if ($get_result['coupon_type'] == 'S') {
                                    $taxDiscountSumm = $shipping_tax_calculated;
                                } else {
                                    $taxDiscountSumm = min($taxDiscountSumm, (float) $order->info['tax_groups'][$tax_desc] + $shipping_tax_calculated);
                                }
                            } else {
                                $taxDiscountSumm = min($taxDiscountSumm, (float) $order->info['tax_groups'][$tax_desc]);
                            }

                            $order->info['tax'] -= $taxDiscountSumm;
                            $order->info['tax_groups'][$tax_desc] -= $taxDiscountSumm;
                        }
                    }
            }
            
        }
        return $taxDiscountSumm;
    }

    function update_credit_account($i) {
        return false;
    }

    function apply_credit() {
        if ($this->deduction != 0 && $this->manager->has('cc_id')) {
            $coupon = \common\models\Coupons::findOne($this->manager->get('cc_id'));
            if ($coupon){
                $coupon->addRedeemTrack( $this->manager->getCustomerAssigned(), $this->manager->getOrderInstance()->order_id);
            }
        }
        $this->manager->remove('cc_id');
    }

    function get_order_total() {

        $order = $this->manager->getOrderInstance();
        $order_total = 0;
        if (tep_not_null($this->coupon_code)) {
          $where = ['or',
                  ['coupon_id' => (int) $this->manager->get('cc_id')],
                  ['coupon_code' => $this->coupon_code]
              ];
        } else {
          $where = ['coupon_id' => (int) $this->manager->get('cc_id')];
        }
        $get_result  = \common\models\Coupons::find()->active()->andWhere($where)->one();
        if ($get_result && $this->_validate($get_result) === true){
            $order_total = $order->info['subtotal_exc_tax'];
            
            $cart = $this->manager->getCart();
/** @var \common\classes\shopping_cart $cartDecorator */
            $cartDecorator = new CartDecorator($cart);
            $products_in_order = $cartDecorator->getProducts();
            $this->products_in_order = $products_in_order;

            if (is_array($products_in_order) && count($products_in_order)) {
              //'quantity', 'final_price', 'id'
              $DISABLE_FOR_SPECIAL =  defined('MODULE_ORDER_TOTAL_COUPON_DISABLE_FOR_SPECIAL') && MODULE_ORDER_TOTAL_COUPON_DISABLE_FOR_SPECIAL=='True';
              if (!empty($get_result->disable_for_special)) {
                $DISABLE_FOR_SPECIAL =  true;
              }
              if ($DISABLE_FOR_SPECIAL || $get_result['restrict_to_products'] || $get_result['restrict_to_categories']
                  || $get_result['exclude_categories']  || $get_result['exclude_products'] ) {

                $coupon_include_pids = array_map('intval',preg_split('/,/',$get_result['restrict_to_products'],-1,PREG_SPLIT_NO_EMPTY));
                $coupon_include_cids = array_map('intval',preg_split('/,/',$get_result['restrict_to_categories'],-1,PREG_SPLIT_NO_EMPTY));
                $coupon_exclude_pids = array_map('intval',preg_split('/,/',$get_result['exclude_products'],-1,PREG_SPLIT_NO_EMPTY));
                $coupon_exclude_cids = array_map('intval',preg_split('/,/',$get_result['exclude_categories'],-1,PREG_SPLIT_NO_EMPTY));

                $have_white_list = count($coupon_include_pids)>0 || count($coupon_include_cids)>0;
                $have_black_list = count($coupon_exclude_pids)>0 || count($coupon_exclude_cids)>0;
                $total = 0;
                //unset products which don't match restrictions
                foreach( $products_in_order as $_idx=>$product_info ) {
                  
                  if (!empty($product_info['parent'])) {
                    $_pid = intval(\common\helpers\Inventory::get_prid($product_info['parent']));
                  } else {
                    $_pid = intval(\common\helpers\Inventory::get_prid($product_info['id']));
                  }
                  /*if (!empty($product_info['sub_products'])) {
                    $is_valid = false;
                  } else*/
                  if ($product_info['ga']) {
                    $is_valid = false;
                  } elseif ($DISABLE_FOR_SPECIAL && !empty($product_info['special_price']) && $product_info['special_price']>0 ) {
                    $is_valid = false;
                  } else {
                    $is_valid = true;
                    if (!empty($coupon_exclude_cids) || !empty($coupon_include_cids)) {
                      $product_categories = \common\helpers\Product::getCategoriesIdListWithParents($_pid);
                    } else {
                      $product_categories = [];
                    }

                    //whitelist lower prio
                    if ( $have_white_list ) {
                      $is_valid = false;
                      if (count($coupon_include_pids) > 0 && in_array($_pid, $coupon_include_pids)) {
                        $is_valid = true;
                      }
                      if ($is_valid==false && count($coupon_include_cids) > 0 ) {
                        $is_valid = count(array_intersect($product_categories, $coupon_include_cids)) > 0;
                      }
                    }

                    if ( $have_black_list && $is_valid ) {
                      if (count($coupon_exclude_pids) > 0 && in_array($_pid, $coupon_exclude_pids)) {
                        $is_valid = false;
                      }
                      if ($is_valid && count($coupon_exclude_cids) > 0 ) {
                        if ( count(array_intersect($product_categories, $coupon_exclude_cids)) > 0 ) {
                          $is_valid = false;
                        }
                      }
                    }

                  }

                  if ( !$is_valid ) {
                    unset($products_in_order[$_idx]);
                  } else {
                    $quantity = $product_info['quantity'];
                    $final_price = $product_info['final_price_exc'];

                    //$total += $final_price * $quantity;
                    $total += $final_price;
                    $this->valid_products[$_pid] = [$quantity => $final_price/$quantity];
                    $this->validProducts[] = $product_info;
                  }
                }


                /*
                    if ($get_result['restrict_to_categories']) {
                        $get_result['restrict_to_categories'] = trim($get_result['restrict_to_categories']);
                        if(substr($get_result['restrict_to_categories'], -1) == ','){
                            $get_result['restrict_to_categories'] = trim(substr($get_result['restrict_to_categories'], 0, -1));
                        }
                        foreach ($products_in_order as $products_id => $details) {
                            $cat_query = tep_db_query("select distinct  products_id from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = '" . (int) $products_id . "' and categories_id IN (" . $get_result['restrict_to_categories'] . ")");
                            if (tep_db_num_rows($cat_query) != 0) {
                                $quantity = key($details);
                                $final_price = current($details);
                                $total += ($final_price * $quantity);
                                $this->valid_products[$products_id] = $products_in_order[$products_id];
                            }
                        }

                    }
                    if ($get_result['restrict_to_products']) {
                        $pr_ids = explode(",", $get_result['restrict_to_products']);

                        foreach ($products_in_order as $pid => $details) {
                            $quantity = key($details);
                            $final_price = current($details);
                            if (in_array(\common\helpers\Inventory::get_prid($pid), $pr_ids)) {
                                $total += ($final_price * $quantity);
                                $this->valid_products[$pid] = $products_in_order[$pid];
                            }
                        }
                    }
                    */
                    $order_total = $total;
                }
            }
        }

        if ($get_result['uses_per_shipping']) {
            $order_total += $order->info['shipping_cost'];
        }
        
        return $order_total;
    }
    
    function isRestrictedByCountry($get_result){
        if (tep_not_null($get_result['restrict_to_countries'])) {
            if  ($this->billing['country']['id']){
                return !in_array($this->billing['country']['id'], explode(',', $get_result['restrict_to_countries']));
            } else if ($this->delivery['country']['id']){
                return !in_array($this->delivery['country']['id'], explode(',', $get_result['restrict_to_countries']));
            }
        }
        return false;
    }

    public function describe_status_key() {
        return new ModuleStatus('MODULE_ORDER_TOTAL_COUPON_STATUS', 'true', 'false');
    }

    public function describe_sort_key() {
        return new ModuleSortOrder('MODULE_ORDER_TOTAL_COUPON_SORT_ORDER');
    }

    public function configure_keys() {
        return array(
            'MODULE_ORDER_TOTAL_COUPON_STATUS' =>
            array(
                'title' => 'Display Total',
                'value' => 'true',
                'description' => 'Do you want to display the Discount Coupon value?',
                'sort_order' => '1',
                'set_function' => 'tep_cfg_select_option(array(\'true\', \'false\'), ',
            ),
            'MODULE_ORDER_TOTAL_COUPON_SORT_ORDER' =>
            array(
                'title' => 'Sort Order',
                'value' => '9',
                'description' => 'Sort order of display.',
                'sort_order' => '2',
            ),
        );
    }

}