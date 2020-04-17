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

use Yii;

/**
 * Site controller
 */
class AffiliateController extends Sceleton {

    function __construct($id,$module=null) {
        \common\helpers\Translation::init('affiliate');
        return parent::__construct($id,$module);
    }
    
    public function actionIndex() {
        return $this->render('index.tpl', [
        ]);
    }

    public function actionLogin() {
        if (Yii::$app->request->isPost) {
            $affiliate_username = Yii::$app->request->post('affiliate_username');
            $affiliate_password = Yii::$app->request->post('affiliate_password');
            $check_affiliate = \common\models\AffiliateAffiliate::find()->where(['affiliate_email_address' => $affiliate_username])->one();
            if (is_object($check_affiliate)) {
                if (!\common\helpers\Password::validate_password($affiliate_password, $check_affiliate->affiliate_password)) {
                    return $this->redirect(['index', 'message' => 'fail']);
                } else {
                    \Yii::$app->settings->set('affiliate_id', $check_affiliate->affiliate_id);
                    tep_db_query("update affiliate_affiliate set affiliate_date_of_last_logon = now(), affiliate_number_of_logons = affiliate_number_of_logons + 1 where affiliate_id = '" . $check_affiliate->affiliate_id . "'");
                    return $this->redirect('central');
                }
            }
        }
        return $this->redirect('index');
    }
    
    public function actionLogoff()
    {
        \Yii::$app->settings->remove('affiliate_id');
        return $this->redirect('index');
    }

    public function actionSignup() {
        if (Yii::$app->request->isPost) {
            
            $form = new \frontend\forms\affiliate\Signup;
            if ($form->load(Yii::$app->request->post()) && $form->validate()){
                $affiliate_id = $form->createAffiliate();
                \Yii::$app->settings->set('affiliate_id', $affiliate_id);
                $response =  ['status' => 'success'];
            } else {
                $messages = $form->getErrorSummary(true);
                $response =  ['status' => 'error', 'messages' => $messages];
            }
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return $response;
        }

        $form = new \frontend\forms\affiliate\Signup();
        return $this->render('signup.tpl', [
            'model' => $form,
        ]);
    }
    
    public function actionSignupOk() {
        return $this->render('signup-ok.tpl', [
        ]);
    }
    
    public function actionCentral() {
        if (!\Yii::$app->settings->has('affiliate_id')) {
            return $this->redirect('index');
        }
        $affiliate_id = \Yii::$app->settings->get('affiliate_id');
        if ($affiliate_id <= 0) {
            return $this->redirect('index');
        }
        $affiliate = \common\models\AffiliateAffiliate::findOne($affiliate_id);
        if (!is_object($affiliate)) {
            return $this->redirect('index');
        }
        
        
        $affiliate_impressions = 0;
        $affiliate_clickthroughs = 0;
        
        $affiliate_sales_raw = "select count(*) as count, sum(a.affiliate_value) as total, sum(a.affiliate_payment) as payment from
            affiliate_sales a
            where a.affiliate_id = '" . (int) $affiliate_id . "'
              and a.affiliate_billing_status = 1
            ";
        $affiliate_sales_query = tep_db_query($affiliate_sales_raw);
        $affiliate_sales = tep_db_fetch_array($affiliate_sales_query);
        
        $affiliate_transactions = $affiliate_sales['count'];
        if ($affiliate_clickthroughs > 0) {
            $affiliate_conversions = tep_round(($affiliate_transactions / $affiliate_clickthroughs) * 100, 2) . "%";
        } else {
            $affiliate_conversions = "n/a";
        }
        $affiliate_amount = $affiliate_sales['total'];
        if ($affiliate_transactions > 0) {
            $affiliate_average = tep_round($affiliate_amount / $affiliate_transactions, 2);
        } else {
            $affiliate_average = "n/a";
        }
        $affiliate_commission = $affiliate_sales['payment'];

        $affiliate_percent = $affiliate->affiliate_commission_percent;
        if ($affiliate_percent < AFFILIATE_PERCENT) $affiliate_percent = AFFILIATE_PERCENT;
  
        // Query the pending amounts to give a complete picture
        $affiliate_pending_raw = "select count(*) as count, sum(a.affiliate_value) as total, sum(a.affiliate_payment) as payment from
            affiliate_sales a,
            " . TABLE_ORDERS . " o
            where a.affiliate_id = '" . (int)$affiliate_id . "'
              and a.affiliate_billing_status = 0
              and o.orders_id = a.affiliate_orders_id
              and o.orders_status = '" . AFFILIATE_PAYMENT_ORDER_MIN_STATUS . "'
            ";
        $affiliate_pending_query = tep_db_query($affiliate_pending_raw);
        $affiliate_pending = tep_db_fetch_array($affiliate_pending_query);

        $affiliate_pending_transactions = $affiliate_pending['count'];
        $affiliate_pending_amount = $affiliate_pending['total'];
        if ($affiliate_pending_transactions > 0) {
          $affiliate_pending_average = tep_round($affiliate_pending_amount / $affiliate_pending_transactions, 2);
        } else {
          $affiliate_pending_average = "n/a";
        }
        $affiliate_pending_commission = $affiliate_pending['payment'];

        $currencies = Yii::$container->get('currencies');
        
        if (defined('TEXT_AFFILIATE_NOT_APPROVED')) {
            $platformUrl = TEXT_AFFILIATE_NOT_APPROVED;
        } else {
            $platformUrl = '--';
        }
        
        if ($affiliate->platform_id > 0) {
            $plid =  \common\models\Platforms::findOne($affiliate->platform_id);
            if($plid->sattelite_platform_id > 0) {
                $platform = \common\models\Platforms::findOne($plid->sattelite_platform_id);
                if (is_object($platform)) {
                    $platformUrl = ($platform->ssl_enabled ? 'https://' : 'http://') .  $platform->platform_url .'/?code=' . $plid->platform_code;
                }
            }
        }
        
        return $this->render('central.tpl', [
            'platformUrl' => $platformUrl,
            'affiliate' => $affiliate,
            'currencies' => $currencies,
            'affiliate_impressions' => $affiliate_impressions,
            'affiliate_clickthroughs' => $affiliate_clickthroughs,
            'affiliate_transactions' => $affiliate_transactions,
            'affiliate_conversions' => $affiliate_conversions,
            'affiliate_amount' => $affiliate_amount,
            'affiliate_average' => $affiliate_average,
            'affiliate_commission' => $affiliate_commission,
            'affiliate_percent' => $affiliate_percent,
            'affiliate_pending_transactions' => $affiliate_pending_transactions,
            'affiliate_pending_amount' => $affiliate_pending_amount,
            'affiliate_pending_average' => $affiliate_pending_average,
            'affiliate_pending_commission' => $affiliate_pending_commission,
        ]);
    }
    
    public function actionContact() {
        //TODO
        return $this->redirect('index');
    }
    
    public function actionPasswordForgotten() {
        \common\helpers\Translation::init('account/password-forgotten');
        
        $messageStack = \Yii::$container->get('message_stack');
        $email_address = '';
        if (isset($_GET['action']) && ($_GET['action'] == 'process')) {
            $email_address = (string) tep_db_prepare_input($_POST['email_address']);

            if (empty($email_address)) {
                $checkAffiliate = false;
            } else {
                $checkAffiliate = \common\models\AffiliateAffiliate::find()
                        ->where(['affiliate_email_address' => tep_db_input($email_address)])
                        ->one();
            }
            if (is_object($checkAffiliate)) {
                $email_params = array();
                $email_params['STORE_NAME'] = STORE_NAME;
                $email_params['IP_ADDRESS'] = $_SERVER['REMOTE_ADDR'];
                
                $new_password = \common\helpers\Password::create_random_value(ENTRY_PASSWORD_MIN_LENGTH);
                $crypted_password = \common\helpers\Password::encrypt_password($new_password);
                
                $checkAffiliate->affiliate_password = $crypted_password;
                $checkAffiliate->save();
                
                $email_params['NEW_PASSWORD'] = $new_password;
                
                $email_params['CUSTOMER_FIRSTNAME'] = $checkAffiliate->affiliate_firstname;
                $e = explode("://", HTTP_SERVER);
                $email_params['HTTP_HOST'] = '<a href="' . HTTP_SERVER . DIR_WS_HTTP_CATALOG . '">' . $e[1] . '</a>';
                $email_params['CUSTOMER_EMAIL'] = $email_address;
                list($email_subject, $email_text) = \common\helpers\Mail::get_parsed_email_template('Password Forgotten', $email_params);
                
                \common\helpers\Mail::send($checkAffiliate->affiliate_firstname . ' ' . $checkAffiliate->affiliate_lastname, $email_address, TEXT_AFFILIATE . ' ' . $email_subject, $email_text, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, $email_params);

                $messageStack->add_session(SUCCESS_PASSWORD_SENT, 'login', 'success');


                if (!Yii::$app->request->isAjax) {
                    tep_redirect(tep_href_link('affiliate/', '', 'SSL'));
                }
                
            } else {
                $messageStack->add(TEXT_NO_EMAIL_ADDRESS_FOUND, 'password_forgotten');
            }
        }

      
        if (Yii::$app->request->isAjax && $messageStack->size('password_forgotten') > 0) {
            return json_encode($messageStack->asArray('password_forgotten'));
        } elseif (Yii::$app->request->isAjax) {
            return json_encode('ok');
        } else {
            $messages_password_forgotten = '';
            if ($messageStack->size('password_forgotten') > 0) {
                $messages_password_forgotten = $messageStack->output('password_forgotten');
            }
            return $this->render('password_forgotten.tpl', [
                        'messages_password_forgotten' => $messages_password_forgotten,
                        'email_address' => $email_address,
                        'link_back_href' => tep_href_link('affiliate', '', 'SSL'),
            ]);
        }
    }

}
