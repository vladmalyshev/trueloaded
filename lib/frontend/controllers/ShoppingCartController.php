<?php

/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\controllers;

use common\extensions\MultiCart\MultiCart;
use common\models\RecoverCartConfig;
use common\models\repositories\CouponRepository;
use frontend\design\boxes\cart\OrderTotal;
use frontend\design\boxes\cart\ShippingEstimator;
use frontend\design\boxes\packingslip\Products;
use frontend\design\Info;
use Yii;

/**
 * Site controller
 */
class ShoppingCartController extends Sceleton {

    public function actionIndex() {
        global $cart, $breadcrumb;

        if (GROUPS_DISABLE_CHECKOUT) {
            tep_redirect(tep_href_link(FILENAME_DEFAULT));
        }
        
        if (Yii::$app->user->isGuest && \common\helpers\PlatformConfig::getFieldValue('platform_please_login')) {
            tep_redirect(tep_href_link(FILENAME_LOGIN));
        }

        $breadcrumb->add(NAVBAR_TITLE, tep_href_link(FILENAME_SHOPPING_CART));
        $messageStack = \Yii::$container->get('message_stack');
        $currencies = \Yii::$container->get('currencies');

        if (Yii::$app->request->isPost && isset($_POST['ajax_estimate'])) {
            return $this->actionEstimate();
        }
        if (!$this->manager->hasCart()){
            $this->manager->loadCart($cart);
        }
        $this->manager->createOrderInstance('\common\classes\Order');

        $render_data = array(
            'action' => tep_href_link(FILENAME_SHOPPING_CART, 'action=update_product'),
            'manager' => $this->manager
        );
        $popupMode = (Yii::$app->request->isAjax && $_GET['popup'] && Info::themeSetting('after_add') == 'popup');
        if (!$popupMode) {
            $render_data = array_merge($render_data, $this->manager->prepareEstimateData());
        }

        $message_discount_coupon = '';
        if ($messageStack->size('cart_discount_coupon') > 0) {
            $message_discount_coupon = $messageStack->output('cart_discount_coupon');
        }
        $message_discount_gv = '';
        if ($messageStack->size('cart_discount_gv') > 0) {
            $message_discount_gv = $messageStack->output('cart_discount_gv');
        }
        $ot_gv_data = array(
            'can_apply_gv_credit' => false,
            'message_discount_gv' => $message_discount_gv,
            'credit_amount' => '',
            'credit_gv_in_use' => $this->manager->has('cot_gv'),
            'message_discount_coupon' => $message_discount_coupon,
            'message_shopping_cart' => ( $messageStack->size('shopping_cart') > 0 ? $messageStack->output('shopping_cart') : '' ),
        );
        
        $render_data = array_merge($render_data, $ot_gv_data);

        if ($popupMode) {
            return $this->render('popup.tpl', $render_data);
        } else {
            return $this->render('index.tpl', $render_data);
        }
    }   

    public function actionEstimate() {
        $this->layout = false;
        global $cart;
        
        $this->manager->loadCart($cart);
        $this->manager->createOrderInstance('\common\classes\Order');
        
        if (Yii::$app->request->isPost){
            $post = Yii::$app->request->post('estimate');
            
            if ($this->manager->isCustomerAssigned()){
                if ($post['sendto']){
                    $this->manager->changeCustomerAddressSelection('shipping', $post['sendto']);
                    $this->manager->changeCustomerAddressSelection('billing', $post['sendto']);
                    $this->manager->set('shipping', false);                
                }
            } elseif ($post['country_id']) {
                if ($this->manager->has('estimate_ship')){
                    $estimate = $this->manager->get('estimate_ship');
                    if ($estimate['country_id'] != $post['country_id']){
                        $this->manager->set('estimate_ship', ['country_id' => $post['country_id'], 'postcode' => $post['post_code']]);
                        $post['shipping'] = null;
                        $this->manager->set('shipping', false);
                    }
                } else {
                    $this->manager->set('estimate_ship', ['country_id' => $post['country_id'], 'postcode' => $post['post_code']]);
                }
            } 
            if ($post['shipping']){
                $this->manager->setSelectedShipping($post['shipping']);
            }
        }

        return json_encode(array('estimate' => ShippingEstimator::widget(['params' =>['manager' => $this->manager]]), 'total' => OrderTotal::widget(['params' =>['manager' => $this->manager]])));
    }

    /**
     * cron Recovery Cart
     */
    public function actionRecoveryCart() {
        $couponRepository = new CouponRepository();
        $recovery_carts = [];
        $sql = "SELECT cb.customers_id cid,
                cb.products_id pid,
                cb.customers_basket_quantity qty,
                cb.customers_basket_date_added bdate,
                cb.currency,
                cus.customers_firstname fname,
                cus.customers_lastname lname,
                cus.customers_telephone phone,
                cus.customers_newsletter newsletter,
                cus.customers_fax fax,
                cus.customers_email_address email,
                cb.basket_id, cb.platform_id,
                ci.token,
                cus.customers_gender, 
                ci.time_long, 
                TIMESTAMPDIFF(HOUR, ci.time_long,NOW() ) as hoursago,
                cb.language_id,
                st.offered_discount,
                if(ISNULL(st.offered_discount),0,st.offered_discount) as offered_discount_data
           FROM " . TABLE_CUSTOMERS_BASKET . " AS cb
           INNER JOIN " . TABLE_CUSTOMERS . " AS cus ON  cb.customers_id = cus.customers_id and cus.opc_temp_account=0
           LEFT JOIN " . TABLE_CUSTOMERS_INFO . " ci ON ci.customers_info_id = cus.customers_id
           LEFT JOIN " . TABLE_SCART . " st ON cus.customers_id=st.customers_id AND cb.basket_id=st.basket_id
           WHERE  (st.offered_discount < 3 or  ISNULL(st.offered_discount)) and (st.workedout = 0 or  ISNULL(st.workedout))  and (st.contacted = 0 or  ISNULL(st.contacted)) AND TIMESTAMPDIFF(HOUR, ci.time_long,NOW() ) > 0
           GROUP BY cb.customers_id
           ORDER BY ci.time_long";

        $query = tep_db_query($sql);
        $recovery_carts = [];
        if (tep_db_num_rows($query)) {
            while ($recovery_cart = tep_db_fetch_array($query)) {
                $recovery_carts[] = $recovery_cart;
            }
        }
        $currencies = \Yii::$container->get('currencies');

        $offered_discount = 0;
        $mline = '';
        $use_method = 'c';
        $tprice = 0;


        foreach ($recovery_carts as $recovery_cart) {

            $platform_id = (int) $recovery_cart['platform_id'];
            $recoveryCartConfig = RecoverCartConfig::getConfigForCart($platform_id);
            if (!$recoveryCartConfig) {
                continue;
            }

            if( (int) $recovery_cart['platform_id'] === 0 ) {
              continue;
            }

            if ($recoveryCartConfig->third_email_start && ( (int) $recovery_cart['hoursago'] >= $recoveryCartConfig->third_email_start ) && $recoveryCartConfig->third_email_coupon_id) {
                $offered_discount = 3;
                $output['coupon'] = $recoveryCartConfig->third_email_coupon_id;
                $output['name_tmpl'] = 'Recovery Cart third letter';
            } elseif ($recoveryCartConfig->second_email_start && ( (int) $recovery_cart['hoursago'] >= $recoveryCartConfig->second_email_start ) /*&& $recoveryCartConfig->second_email_coupon_id */&& $recovery_cart['offered_discount_data'] < 2) {
                $offered_discount = 2;
                $output['coupon'] = $recoveryCartConfig->second_email_coupon_id;
                $output['name_tmpl'] = 'Recovery Cart second letter';
            } elseif ($recoveryCartConfig->first_email_start && ( (int) $recovery_cart['hoursago'] >= $recoveryCartConfig->first_email_start ) /*&& $recoveryCartConfig->first_email_coupon_id*/ && $recovery_cart['offered_discount_data'] < 1) {
                $offered_discount = 1;
                $output['coupon'] = $recoveryCartConfig->first_email_coupon_id;
                $output['name_tmpl'] = 'Recovery Cart first letter';
            } else {
                continue;
            }

            $cid = (int) $recovery_cart['cid'];
            if ($cid === 0) {
                continue;
            }


            $recovery_cart['token'] = 'CT-' . strtoupper(substr(md5(microtime()), 0, 45));
            $sql_array = [
                'token' => $recovery_cart['token']
            ];
            tep_db_perform(TABLE_CUSTOMERS_INFO, $sql_array, 'update', "customers_info_id = '" . $cid . "'");

            $platform = new \common\classes\platform_config($recovery_cart['platform_id']);

            $url = $platform->getCatalogBaseUrl(true);

            $sql = "select cb.products_id pid, cb.customers_basket_quantity qty, p.products_price price,
                p.products_tax_class_id taxclass,p.products_id pidd,
                p.products_model model,
                pd.products_name name,
                cb.final_price as final_price
                from " . TABLE_CUSTOMERS_BASKET . " cb, " . TABLE_CUSTOMERS . " cus, " . TABLE_PRODUCTS . " p
                LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON pd.products_id=p.products_id
                  WHERE cb.customers_id = cus.customers_id AND cus.customers_id = '" . $cid . "'
                        AND p.products_id = CONVERT(cb.products_id, UNSIGNED INTEGER )
                        AND pd.products_id = p.products_id and pd.platform_id = '".(int)Yii::$app->get('platform')->config()->getPlatformToDescription()."' AND pd.language_id = " . (int) $recovery_cart['language_id'];

            $query2 = tep_db_query($sql);

            $ptoduct = '';
            $ptoductArr = [];
            $columns = 3;
            while ($inrec2 = tep_db_fetch_array($query2)) {

                $sprice = $inrec2['final_price'];
                $sprice += ( $sprice * \common\helpers\Tax::get_tax_rate($inrec2['taxclass']) / 100 );

                $tprice = $tprice + ( $inrec2['qty'] * $sprice );
                $pprice_formated = $currencies->format($sprice, false, $recovery_cart['currency'], $recovery_cart['currency']);
                $tpprice_formated = $currencies->format(( $inrec2['qty'] * $sprice), false, $recovery_cart['currency'], $recovery_cart['currency']);
                $image = '';

                $product_link = Yii::$app->urlManager->createAbsoluteUrl([
                    'catalog/product',
                    'products_id' => $inrec2['pid']
                        ]);
                if (EMAIL_USE_HTML == 'true') {
                    $image = \common\classes\Images::getImage($inrec2['pidd'], 'Small');

                    $ptoductArr[] = '
<div style="text-align: center; padding: 20px;">
    <div>' . ( $image ? '<a href="' . $product_link . '">' . $image . '</a>' : '' ) . '</div>
    <div style="margin-bottom: 10px"><a href="' . $product_link . '" style="font-size: 16px; font-weight: bold; color: #444444; text-decoration:none;">' . $inrec2['qty'] . ' x ' . $inrec2['name'] . '</a></div>
    <div style="font-size: 24px">' . $pprice_formated . '</div>
</div>';
                } else {
                    $mline .= $recovery_cart['qty'] . ' x  ' . $inrec2['name'] . "-" . $pprice_formated . "\n";
                }
            }

            if (EMAIL_USE_HTML == 'true') {
                $count = count($ptoductArr);
                $last = $count % $columns;
                $ptoduct .= '<table  cellpadding="0" cellspacing="0" width="100%" border="0"><tr style="vertical-align: top">';
                for ($i = 0; $i < ( $count - $last ); $i ++) {
                    if ($i != 0 && $i % $columns == 0) {
                        $ptoduct .= '</tr><tr style="vertical-align: top">';
                    }
                    $ptoduct .= '<td width="' . floor(100 / $columns) . '%">' . $ptoductArr[$i] . '</td>';
                }
                $ptoduct .= '</tr></table>';

                $ptoduct .= '<table  cellpadding="0" cellspacing="0" width="100%" border="0"><tr style="vertical-align: top">';
                for ($i; $i < $count; $i ++) {
                    $ptoduct .= '<td width="' . floor(100 / $last) . '%">' . $ptoductArr[$i] . '</td>';
                }
                $ptoduct .= '</tr></table>';
            }

            $mline = $ptoduct;


            $custname = $recovery_cart['fname'] . " " . $recovery_cart['lname'];

            $outEmailAddr = '"' . $custname . '" <' . $recovery_cart['email'] . '>';
            if (tep_not_null(RCS_EMAIL_COPIES_TO)) {
                $outEmailAddr .= ', ' . RCS_EMAIL_COPIES_TO;
            }


            $email_params = array();
            $email_params['STORE_NAME'] = $platform->const_value('STORE_NAME');
            $email_params['USER_GREETING'] = trim(\common\helpers\Translation::getTranslationValue('EMAIL_TEXT_SALUTATION', 'admin', $recovery_cart['language_id']) . $custname);
            $email_params['CUSTOMER_FIRSTNAME'] = $recovery_cart['fname'];
            $email_params['STORE_OWNER_EMAIL_ADDRESS'] = $platform->const_value('STORE_OWNER_EMAIL_ADDRESS');
            $email_params['PRODUCTS_ORDERED'] = $mline;
            $email_params['ORDER_COMMENTS'] = ( tep_not_null($output['message']) ? stripcslashes(urldecode($output['message'])) : '' );
            $email_params['COUPON_'] = ( tep_not_null($output['message']) ? stripcslashes(urldecode($output['message'])) : '' );


            if (!empty($output['coupon']) && $use_method == 'c') {
                $_c = $couponRepository->getByIdAsArray($output['coupon']);

                $email_params['COUPON_CODE'] = nl2br(sprintf(\common\helpers\Translation::getTranslationValue('TEXT_COUPON_OFFER', 'admin/recover_cart_sales', $recovery_cart['language_id']), $_c['coupon_code'], ( $_c['coupon_type'] == 'F' ? $currencies->format($_c['coupon_amount'], false, $_c['coupon_currency']) : ( $_c['coupon_type'] == 'P' ? round($_c['coupon_amount'], 2) . '%' : /* maybe for free shipping S */
                                '' ))));

                if (EMAIL_USE_HTML == 'true') {
                    $email_params['COUPON_CODE'] .= '<br><a href="' . $url . FILENAME_SHOPPING_CART . '?' . http_build_query([
                                'action' => 'recovery_restore',
                                'email_address' => $recovery_cart['email'],
                                'credit_apply[coupon][gv_redeem_code]' => $_c['coupon_code'],
                                'utmgclid' => 'recoveryemail',
                                'currency' => $recovery_cart['currency'],
                                'token' => $recovery_cart['token']
                            ]) . '">' . \common\helpers\Translation::getTranslationValue('TEXT_RETURN', 'admin/recover_cart_sales', $recovery_cart['language_id']) . '</a>'; //tep_catalog_href_link('shopping-cart', 'action=recovery_restore&email_address='.$recovery_cart['email'].'&credit_apply[coupon][gv_redeem_code]='.$_c['coupon_code'].'&utmgclid=recoveryemail&token=' . $recovery_cart['token']
                } else {
                    $email_params['COUPON_CODE'] .= "\n" . $url . FILENAME_SHOPPING_CART . '?' . http_build_query([
                                'action' => 'recovery_restore',
                                'email_address' => $recovery_cart['email'],
                                'credit_apply[coupon][gv_redeem_code]' => $_c['coupon_code'],
                                'utmgclid' => 'recoveryemail',
                                'currency' => $recovery_cart['currency'],
                                'token' => $recovery_cart['token']
                            ]); //tep_catalog_href_link('shopping-cart', 'action=recovery_restore&email_address='.$recovery_cart['email'].'&credit_apply[coupon][gv_redeem_code]='.$_c['coupon_code'].'&utmgclid=recoveryemail&token=' . $recovery_cart['token']);
                }
            } elseif (isset($output['amount']) && $use_method == 'v') {
                $output['amount'] = (float) $output['amount'];
                $data = \common\helpers\Coupon::generate_customer_gvcc($coupon_id, $recovery_cart['email'], $output['amount'], $output['coupon_currency'], $cid, $bid[$ckey]);
                $email_params['COUPON_CODE'] = nl2br(sprintf(\common\helpers\Translation::getTranslationValue('TEXT_COUPON_OFFER', 'admin/recover_cart_sales', $recovery_cart['language_id']), $data['id1'], $data['amount']));
                if (EMAIL_USE_HTML == 'true') {
                    $email_params['COUPON_CODE'] .= '<br><a href="' . $url . FILENAME_SHOPPING_CART . '?' . http_build_query([
                                'action' => 'recovery_restore',
                                'email_address' => $recovery_cart['email'],
                                'credit_apply[gv][gv_redeem_code]' => $data['id1'],
                                'utmgclid' => 'recoveryemail',
                                'currency' => $recovery_cart['currency'],
                                'token' => $recovery_cart['token']
                            ]) . '">' . \common\helpers\Translation::getTranslationValue('TEXT_RETURN', 'admin/recover_cart_sales', $recovery_cart['language_id']) . '</a>'; //tep_catalog_href_link('shopping-cart', 'action=recovery_restore&email_address='.$recovery_cart['email'].'&credit_apply[coupon][gv_redeem_code]='.$_c['coupon_code'].'&utmgclid=recoveryemail&token=' . $recovery_cart['token']
                } else {
                    $email_params['COUPON_CODE'] .= "\n" . $url . FILENAME_SHOPPING_CART . '?' . http_build_query([
                                'action' => 'recovery_restore',
                                'email_address' => $recovery_cart['email'],
                                'credit_apply[gv][gv_redeem_code]' => $data['id1'],
                                'utmgclid' => 'recoveryemail',
                                'currency' => $recovery_cart['currency'],
                                'token' => $recovery_cart['token']
                            ]); //tep_catalog_href_link('shopping-cart', 'action=recovery_restore&email_address='.$recovery_cart['email'].'&credit_apply[coupon][gv_redeem_code]='.$_c['coupon_code'].'&utmgclid=recoveryemail&token=' . $recovery_cart['token']);
                }
            } else {
                if (EMAIL_USE_HTML == 'true') {
                    $email_params['COUPON_CODE'] = '<br><a href="' . $url . FILENAME_SHOPPING_CART . '?' . http_build_query([
                                'action' => 'recovery_restore',
                                'email_address' => $recovery_cart['email'],
                                'utmgclid' => 'recoveryemail',
                                'currency' => $recovery_cart['currency'],
                                'token' => $recovery_cart['token']
                            ]) . '">' . \common\helpers\Translation::getTranslationValue('TEXT_RETURN', 'admin/recover_cart_sales', $recovery_cart['language_id']) . '</a>';
                    ;
                } else {
                    $email_params['COUPON_CODE'] = $url . FILENAME_SHOPPING_CART . '?' . http_build_query([
                                'action' => 'recovery_restore',
                                'email_address' => $recovery_cart['email'],
                                'utmgclid' => 'recoveryemail',
                                'currency' => $recovery_cart['currency'],
                                'token' => $recovery_cart['token']
                            ]);
                }
            }

            list( $email_subject, $email_text ) = \common\helpers\Mail::get_parsed_email_template($output['name_tmpl'], $email_params, $recovery_cart['language_id'], $recovery_cart['platform_id']);

            if (!empty($_c['coupon_id'])) {
              tep_db_query("insert into " . TABLE_COUPON_EMAIL_TRACK . " (coupon_id, customer_id_sent, basket_id, sent_firstname, sent_lastname, emailed_to, date_sent) values ('" . $_c['coupon_id'] . "', '" . (int) $cid . "', '" . (int) $recovery_cart['basket_id'] . "', 'Auto Send', 'Task Cron', '" . $recovery_cart['email'] . "', now() )");
            }
// either do not check or to subscribers
            if (((defined('RCS_CHECK_NEWSLETTER') && RCS_CHECK_NEWSLETTER!='True') || $recovery_cart['newsletter'] == '1') && $recoveryCartConfig->enable_email_delivery) {
              $headers = '';
              if(defined('RCS_EMAIL_COPIES_TO') && tep_not_null(RCS_EMAIL_COPIES_TO) ) {
                $headers = 'BCC: ' . RCS_EMAIL_COPIES_TO;
              }
                \common\helpers\Mail::send(
                    $custname,//$to_name
                    //'jkasyanova@holbi.co.uk',
                    $recovery_cart['email'],//$to_email_address
                    $email_subject,//$email_subject
                    $email_text,//$email_text
                    $platform->const_value('STORE_OWNER'),//$from_email_name
                    $platform->const_value('STORE_OWNER_EMAIL_ADDRESS'),//$from_email_address
                    [],//$email_params
                    $headers,//$headers
                    false,//$attachments
                    ['add_br' => 'no']//$settings
                );
            }
            if (is_null($recovery_cart['offered_discount'])) {
                tep_db_query("insert into " . TABLE_SCART . " (customers_id, basket_id, dateadded, datemodified, offered_discount ) values ('" . (int) $recovery_cart['cid'] . "', '" . (int) $recovery_cart['basket_id'] . "','" . $this->seadate('0') . "', '" . $this->seadate('0') . "' , '" . $offered_discount . "')");
            } else {
                tep_db_query("update " . TABLE_SCART . " set datemodified = '" . $this->seadate('0') . "', offered_discount = " . $offered_discount . " where customers_id = '" . (int) $recovery_cart['cid'] . "' and basket_id= '" . (int) $recovery_cart['basket_id'] . "'");
            }
        }
//		echo '<h2>Done!</h2>';

        exit;
    }

    private function seadate($day) {
        $rawtime = strtotime("-" . $day . " days");
        $ndate = date("Ymd", $rawtime);

        return $ndate;
    }

    public function actionSaveCart() {
        if ($ext = \common\helpers\Acl::checkExtension('MultiCart', 'initCarts')) {
            MultiCart::saveCart();
        }
        return $this->redirect('index');
    }

    public function actionApplyCart($uid) {
        if ($ext = \common\helpers\Acl::checkExtension('MultiCart', 'initCarts')) {
            MultiCart::applyCart($uid);
        }
        return $this->redirect('index');
    }

    public $manager;

    public function __construct($id, $module, $config = []) {
        parent::__construct($id, $module, $config);
        $this->manager = new \common\services\OrderManager(Yii::$app->get('storage'));
        $this->manager->setModulesVisibility(['shop_order']);
        Yii::configure($this->manager, [
            'combineShippings' => true,
        ]);
    }

}
